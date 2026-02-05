# PeraCRM MU Plugin Audit (read-only)

## Scope and method
- Audited repository copy under `deploy/mu-plugins/peracrm*` only (live server path unavailable in this environment).
- Did **not** enable plugin, run migrations, or change behavior.

## A) Architecture overview
- **MU boot path**: `deploy/mu-plugins/peracrm.php` exits unless `ABSPATH` is defined, then hard-stops when `PERA_CRM_DISABLED` is true, then requires `peracrm/peracrm.php` if present.
- **Plugin bootstrap**: `peracrm/peracrm.php` defines constants, then loads `inc/bootstrap.php`.
- **Module graph** (`inc/bootstrap.php`): helpers, schema, roles, CPT, favourites, activity, activity capture, health, enquiry ingest, repositories, services, and admin modules (admin-only).
- **Upgrade/load hooks**:
  - `admin_init`: schema upgrades for admins.
  - `init` priority 5: schema upgrades for logged-in users with `manage_options` or `edit_crm_clients`.
  - `admin_init`: ensure roles/caps for admins.
  - `init` priority 5: register `crm_client` CPT.
- **Frontend hooks**:
  - `template_redirect`: capture property view and `/account` visits.
  - `wp_login`: capture login activity.
  - `admin_post[_nopriv]_peracrm_toggle_favourite`: favourite toggle endpoint.
- **Admin hooks**:
  - `admin_menu`, `add_meta_boxes`, `admin_enqueue_scripts`, `admin_notices`, list-table filters/sort clauses, and many `admin_post_*` handlers.
- **No REST routes / no wp_ajax routes / no wp-cron schedules** found.

## B) Feature inventory (by module)

### Core model & bootstrap
- `crm_client` as a non-public CPT with custom capabilities and UI enabled.
- Roles bootstrap creates/updates `advisor` role and grants CRM caps to admins/advisors.

### Enquiry ingestion (`inc/enquiry-ingest.php`, services)
- Public callable ingestion entrypoint: `peracrm_ingest_enquiry(array $payload)`.
- Resolves client by:
  1) logged-in user’s `crm_client_id`,
  2) WP user by email + `crm_client_id`,
  3) CRM client email meta lookup,
  4) create client if missing.
- Populates profile/meta fields (name/email/phone/source/status), normalizes email, rate-limits creation per normalized email via transient.
- Logs `enquiry` activity (de-dup window 15 min), links property relation (`enquiry`) when valid published `property`, and optionally creates 1-day follow-up reminder for assigned advisor.
- Legacy compatibility path still calls `peracrm_find_or_create_client_by_email` when resolver is unavailable.

### Activity capture/logging
- Event storage in `crm_activity` custom table.
- Activity events from:
  - property views,
  - account page visits,
  - logins,
  - enquiry events,
  - status changes / advisor reassignments from admin actions,
  - favourite/unfavourite.
- Duplicate suppression includes per-request static de-dupe and recent-window checks.

### Favourites integration (`inc/favourites.php`)
- Uses `crm_client_property` relation table with `relation_type = favourite`.
- Add/remove/check/list favourites.
- Frontend POST endpoint via `admin-post.php` + nonce, verifies logged-in user has linked `crm_client_id` and property is published.
- Writes activity event for favourite/unfavourite when activity table exists.

### Admin UI entry points
- CPT list/edit UI for `crm_client`.
- Additional submenu pages under CRM Clients:
  - My Reminders,
  - Work Queue,
  - Pipeline,
  - Client View.
- CRM metaboxes on client edit: profile, notes, reminders, timeline/activity, linked properties, account link, health, assigned advisor.

### Roles/capabilities
- Advisors get `read_crm_client`, `edit_crm_client`, `edit_crm_clients`, `read_private_crm_clients`.
- Admins get full CRUD CRM client caps.
- Action handlers usually gate by login + nonce + edit capability + assignment/override capability.

### Health/diagnostics
- Client health engine buckets (`hot`, `warm`, `cold`, `at_risk`, `none`) using activity freshness and reminder counts.
- Request-level cache priming for activity/reminder aggregates.
- Work Queue warns when activity/reminder tables are absent.

### Notifications / integrations
- No email notifications, outbound webhooks, or external API integrations found.
- Reminder creation and activity logging are internal only.

## C) Data model & schema

### Storage strategy
- **CPT**: `crm_client` (primary client entity).
- **Custom tables**:
  - `{$wpdb->prefix}crm_notes`
  - `{$wpdb->prefix}crm_reminders`
  - `{$wpdb->prefix}crm_activity`
  - `{$wpdb->prefix}crm_client_property`
- **Meta/usermeta/options**:
  - client post meta for profile/status/source/contact and assignment.
  - user meta `crm_client_id` for account-client linkage.
  - option `peracrm_schema_version` for migration version.
  - user meta `_peracrm_pipeline_views` for saved pipeline views.

### Schema/indexes/versioning
- Schema version constant: `PERACRM_SCHEMA_VERSION = 1`.
- Upgrade path: `peracrm_maybe_upgrade_schema()` -> `peracrm_upgrade_schema_to()` -> `dbDelta(...)`.
- Indexed columns include:
  - notes: `(client_id, created_at)`, `(advisor_user_id, created_at)`
  - reminders: `(advisor_user_id, status, due_at)`, `(client_id, status, due_at)`
  - activity: `(client_id, created_at)`, `(event_type, created_at)`
  - client-property: unique `(client_id, property_id, relation_type)` + relation/time indexes.

## D) Security review checklist

### ✅ Good controls observed
- Kill switch early return in MU loader before plugin include.
- Most state-changing handlers use `check_admin_referer(...)`.
- Most admin handlers enforce capability and ownership/assignment checks.
- SQL uses `$wpdb->prepare` / `$wpdb->delete` / sanitized values in most query paths.
- Output escaping in most admin HTML (`esc_html`, `esc_attr`, `esc_url`).

### Findings
1. **P0 / fatal risk**: infinite recursion between advisor lookup helpers.
   - `peracrm_client_get_assigned_advisor_id()` calls `peracrm_enquiry_get_assigned_advisor_id()` when available.
   - `peracrm_enquiry_get_assigned_advisor_id()` calls `peracrm_client_get_assigned_advisor_id()` when available.
   - This mutual call chain can trigger stack exhaustion/fatal in any path that resolves advisor assignment.
2. **P1 / access-control surface**: favourite endpoint exposed to `nopriv` action (by design for front-end), but relies entirely on nonce + login redirect and does not check any explicit role/capability. Recommend explicitly documenting intended trust model and adding capability guard if business rules require it.
3. **P2 / data disclosure risk (minor)**: CSV export includes phone/email; access control is `edit_crm_clients` (or admin constraints). If advisor scope rules become stricter later, this export path should be reviewed against least privilege.

## E) Performance review checklist

### Findings
1. **P1/P2**: list-table and queue flows can execute heavy joins/subqueries against activity/reminder tables on every CRM client list load (`posts_clauses` custom SQL, work queue bucket queries).
2. **P2**: Work Queue computes full bucket client ID sets, then passes large `post__in` arrays to `WP_Query`, which can degrade at scale.
3. **P2**: table-existence checks (`SHOW TABLES LIKE`) happen per request in several modules (cached statically per request only, not cross-request).
4. **P2**: Email-based client lookups are meta-query-driven (no dedicated unique index on normalized email), so high-volume ingestion may degrade.

## F) Bug list + reproduction notes

1. **Fatal recursion in advisor resolver (P0)**
   - Trigger: any call to `peracrm_client_get_assigned_advisor_id($client_id)` with positive `client_id` once both helper files are loaded.
   - Expected reproduction path: profile save/reassign/note/reminder/pipeline actions that fetch assigned advisor.
   - Result: recursive function loop -> memory exhaustion/fatal.

2. **Potential duplicate clients under concurrent enquiry ingestion (P1)**
   - Cause: find-then-create flow over postmeta without DB uniqueness on normalized email.
   - Repro idea: submit same new email concurrently from two requests; both can miss before insert.

3. **Inconsistent email matching between service and ingestion resolvers (P1)**
   - `client_service` checks `crm_primary_email` exact match only.
   - `enquiry-ingest` checks normalized and legacy keys.
   - Can produce inconsistent de-dup behavior across integration callsites.

4. **Legacy custom-table assumptions in admin account linkage helpers (P2 correctness/maintainability)**
   - Functions probe `{$prefix}crm_client` table/`linked_user_id` column even though primary model is CPT.
   - Falls back safely to post meta when table missing, but path adds complexity/confusion.

## G) Recommended patch plan (prioritized)

### P0 — white-screen/security critical
1. **Break advisor-lookup recursion**
   - Files: `inc/helpers.php`, `inc/enquiry-ingest.php`
   - Functions: `peracrm_client_get_assigned_advisor_id`, `peracrm_enquiry_get_assigned_advisor_id`
   - Approach: make one canonical implementation and remove cross-calls.

```diff
diff --git a/deploy/mu-plugins/peracrm/inc/helpers.php b/deploy/mu-plugins/peracrm/inc/helpers.php
@@
 function peracrm_client_get_assigned_advisor_id($client_id)
 {
@@
-    if (function_exists('peracrm_enquiry_get_assigned_advisor_id')) {
-        return (int) peracrm_enquiry_get_assigned_advisor_id($client_id);
-    }
-
     $assigned_id = (int) get_post_meta($client_id, 'assigned_advisor_user_id', true);
     $crm_id = (int) get_post_meta($client_id, 'crm_assigned_advisor', true);
@@
 }
diff --git a/deploy/mu-plugins/peracrm/inc/enquiry-ingest.php b/deploy/mu-plugins/peracrm/inc/enquiry-ingest.php
@@
 function peracrm_enquiry_get_assigned_advisor_id($client_id)
 {
     $client_id = (int) $client_id;
     if ($client_id <= 0) {
         return 0;
     }
-
-    if (function_exists('peracrm_client_get_assigned_advisor_id')) {
-        return (int) peracrm_client_get_assigned_advisor_id($client_id);
-    }
-
-    return 0;
+
+    $assigned_id = (int) get_post_meta($client_id, 'assigned_advisor_user_id', true);
+    $crm_id = (int) get_post_meta($client_id, 'crm_assigned_advisor', true);
+    if (function_exists('peracrm_user_is_valid_advisor')) {
+        if (peracrm_user_is_valid_advisor($assigned_id)) {
+            return $assigned_id;
+        }
+        if (peracrm_user_is_valid_advisor($crm_id)) {
+            return $crm_id;
+        }
+    }
+
+    return 0;
 }
```

### P1 — correctness/data integrity
1. **Normalize and unify email dedupe behavior**
   - Files: `inc/services/client_service.php`, `inc/enquiry-ingest.php`
   - Functions: `peracrm_find_or_create_client_by_email`, `peracrm_find_client_by_email`
   - Approach: use the same normalized-email lookup path everywhere; write both normalized and legacy keys consistently.

```diff
diff --git a/deploy/mu-plugins/peracrm/inc/services/client_service.php b/deploy/mu-plugins/peracrm/inc/services/client_service.php
@@
 function peracrm_find_or_create_client_by_email($email, array $data = [])
 {
     $email = sanitize_email($email);
     if ($email === '') {
         return 0;
     }
+
+    if (function_exists('peracrm_find_client_by_email')) {
+        $existing_id = (int) peracrm_find_client_by_email($email);
+        if ($existing_id > 0) {
+            return $existing_id;
+        }
+    }
@@
-    update_post_meta($post_id, 'crm_primary_email', $email);
+    $normalized = function_exists('peracrm_normalize_email') ? peracrm_normalize_email($email) : strtolower($email);
+    update_post_meta($post_id, 'crm_primary_email', $email);
+    update_post_meta($post_id, 'primary_email', $email);
+    if ($normalized !== '') {
+        update_post_meta($post_id, 'crm_primary_email_normalized', $normalized);
+        update_post_meta($post_id, 'primary_email_normalized', $normalized);
+    }
```

2. **(Optional hardening) Add ingestion lock transient around create path**
   - File: `inc/enquiry-ingest.php`
   - Function: `peracrm_create_client_from_enquiry`
   - Approach: short-lived lock key (`set_transient`) before insert; re-check existing record after lock acquisition.

### P2 — performance/maintainability
- Add optional query caps / pagination-aware bucket SQL for Work Queue to avoid huge `post__in` sets.
- Consider persistent cache for table-existence checks (option/transient) to avoid repeated `SHOW TABLES` across requests.
- Evaluate dedicated lookup table or taxonomy for normalized client email if ingestion throughput grows.
- Simplify/remove legacy `{$prefix}crm_client` table probing unless truly needed.

### P3 — polish/UX
- Add an explicit diagnostics/admin status page showing: schema version, table existence, last upgrade run, and disabled-state reason.
- Document external integration contract for `peracrm_ingest_enquiry(...)` and favourite endpoint behavior.

## Kill-switch confirmation
- Confirmed: MU loader returns early when `PERA_CRM_DISABLED` is truthy, before `peracrm/peracrm.php` is required.
