<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_admin_user_can_manage()
{
    return current_user_can('manage_options') || current_user_can('edit_crm_clients');
}

function peracrm_admin_get_client($client_id)
{
    $client = get_post((int) $client_id);
    if (!$client || 'crm_client' !== $client->post_type) {
        return null;
    }

    return $client;
}

function peracrm_admin_get_reminder($reminder_id)
{
    global $wpdb;

    $table = peracrm_table('crm_reminders');

    $query = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d",
        (int) $reminder_id
    );

    $row = $wpdb->get_row($query, ARRAY_A);
    if (!$row) {
        return null;
    }

    return $row;
}

function peracrm_admin_get_client_reminders($client_id, $limit = 20)
{
    global $wpdb;

    $table = peracrm_table('crm_reminders');

    $query = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE client_id = %d AND status = %s ORDER BY due_at ASC LIMIT %d",
        (int) $client_id,
        'pending',
        (int) $limit
    );

    return $wpdb->get_results($query, ARRAY_A);
}

function peracrm_admin_get_advisor_reminders_until($advisor_user_id, $until_mysql, $limit = 200)
{
    global $wpdb;

    $table = peracrm_table('crm_reminders');

    $query = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE advisor_user_id = %d AND status = %s AND due_at <= %s ORDER BY due_at ASC LIMIT %d",
        (int) $advisor_user_id,
        'pending',
        sanitize_text_field($until_mysql),
        (int) $limit
    );

    return $wpdb->get_results($query, ARRAY_A);
}

function peracrm_admin_get_client_property_count($client_id, $relation_type)
{
    global $wpdb;

    $table = peracrm_table('crm_client_property');

    $query = $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE client_id = %d AND relation_type = %s",
        (int) $client_id,
        sanitize_key($relation_type)
    );

    return (int) $wpdb->get_var($query);
}

function peracrm_admin_client_table_has_linked_user_column()
{
    static $has_column = null;

    if (null !== $has_column) {
        return $has_column;
    }

    global $wpdb;

    $table = peracrm_table('crm_client');
    $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if (!$table_exists) {
        $has_column = false;
        return $has_column;
    }

    $column = $wpdb->get_col("SHOW COLUMNS FROM {$table} LIKE 'linked_user_id'");
    $has_column = !empty($column);

    return $has_column;
}

function peracrm_admin_get_client_linked_user_id($client_id)
{
    $client_id = (int) $client_id;
    if ($client_id <= 0) {
        return 0;
    }

    if (peracrm_admin_client_table_has_linked_user_column()) {
        global $wpdb;
        $table = peracrm_table('crm_client');
        $linked_user_id = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT linked_user_id FROM {$table} WHERE id = %d", $client_id)
        );
        if ($linked_user_id > 0) {
            return $linked_user_id;
        }
    }

    return (int) get_post_meta($client_id, 'linked_user_id', true);
}

function peracrm_admin_find_linked_user_id($client_id)
{
    $linked_user_id = peracrm_admin_get_client_linked_user_id($client_id);
    if ($linked_user_id > 0) {
        return $linked_user_id;
    }

    $users = get_users([
        'meta_key' => 'crm_client_id',
        'meta_value' => (int) $client_id,
        'number' => 1,
        'fields' => 'ids',
    ]);

    if (empty($users)) {
        return 0;
    }

    return (int) $users[0];
}

function peracrm_admin_update_client_linked_user_id($client_id, $user_id)
{
    $client_id = (int) $client_id;
    $user_id = (int) $user_id;
    if ($client_id <= 0) {
        return false;
    }

    if (peracrm_admin_client_table_has_linked_user_column()) {
        global $wpdb;
        $table = peracrm_table('crm_client');
        $result = $wpdb->update(
            $table,
            ['linked_user_id' => $user_id > 0 ? $user_id : null],
            ['id' => $client_id],
            ['%d'],
            ['%d']
        );
        if (false !== $result) {
            return true;
        }
    }

    if ($user_id > 0) {
        return (bool) update_post_meta($client_id, 'linked_user_id', $user_id);
    }

    delete_post_meta($client_id, 'linked_user_id');
    return true;
}

function peracrm_admin_parse_datetime($raw_datetime)
{
    $raw_datetime = sanitize_text_field($raw_datetime);
    if ($raw_datetime === '') {
        return '';
    }

    $timezone = wp_timezone();
    $formats = ['Y-m-d\TH:i', 'Y-m-d H:i'];

    foreach ($formats as $format) {
        $date = date_create_from_format($format, $raw_datetime, $timezone);
        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d H:i:s');
        }
    }

    $timestamp = strtotime($raw_datetime);
    if ($timestamp) {
        return wp_date('Y-m-d H:i:s', $timestamp, $timezone);
    }

    return '';
}

function peracrm_admin_redirect_with_notice($url, $notice)
{
    $url = add_query_arg('peracrm_notice', $notice, $url);
    wp_safe_redirect($url);
    exit;
}

function peracrm_admin_search_user_for_link($search_term, $client_id = 0)
{
    $client_id = (int) $client_id;
    if ($client_id > 0 && !current_user_can('edit_post', $client_id)) {
        return [];
    }

    $search_term = sanitize_text_field($search_term);
    $search_term = trim($search_term);
    if (strlen($search_term) > 100) {
        $search_term = substr($search_term, 0, 100);
    }
    if ($search_term === '') {
        return [];
    }

    return get_users([
        'search' => '*' . $search_term . '*',
        'search_columns' => ['user_login', 'user_email', 'display_name'],
        'number' => 5,
    ]);
}

function peracrm_handle_add_note()
{
    if (!peracrm_admin_user_can_manage()) {
        wp_die('Unauthorized');
    }

    check_admin_referer('peracrm_add_note');

    $client_id = isset($_POST['peracrm_client_id']) ? (int) $_POST['peracrm_client_id'] : 0;
    $client = peracrm_admin_get_client($client_id);
    if (!$client) {
        wp_die('Invalid client');
    }

    $note_body = isset($_POST['peracrm_note_body']) ? sanitize_textarea_field(wp_unslash($_POST['peracrm_note_body'])) : '';
    if ($note_body === '') {
        peracrm_admin_redirect_with_notice(get_edit_post_link($client_id, 'raw'), 'note_missing');
    }

    $note_id = peracrm_notes_create($client_id, get_current_user_id(), $note_body, 'internal');
    if (!$note_id) {
        peracrm_admin_redirect_with_notice(get_edit_post_link($client_id, 'raw'), 'note_failed');
    }

    peracrm_admin_redirect_with_notice(get_edit_post_link($client_id, 'raw'), 'note_added');
}

function peracrm_handle_link_user()
{
    $client_id = isset($_POST['peracrm_client_id']) ? (int) $_POST['peracrm_client_id'] : 0;
    $client = peracrm_admin_get_client($client_id);
    if (!$client) {
        wp_die('Invalid client');
    }

    if (!current_user_can('edit_post', $client_id)) {
        wp_die('Unauthorized');
    }

    check_admin_referer('peracrm_link_user');

    $search_term = isset($_POST['peracrm_user_search']) ? wp_unslash($_POST['peracrm_user_search']) : '';
    $users = peracrm_admin_search_user_for_link($search_term, $client_id);
    if (empty($users)) {
        peracrm_admin_redirect_with_notice(get_edit_post_link($client_id, 'raw'), 'user_missing');
    }

    if (count($users) > 1) {
        peracrm_admin_redirect_with_notice(get_edit_post_link($client_id, 'raw'), 'user_ambiguous');
    }

    $user = $users[0];
    $user_id = (int) $user->ID;
    if ($user_id <= 0) {
        peracrm_admin_redirect_with_notice(get_edit_post_link($client_id, 'raw'), 'user_missing');
    }

    $existing_client_id = (int) get_user_meta($user_id, 'crm_client_id', true);
    if ($existing_client_id > 0 && $existing_client_id !== $client_id) {
        peracrm_admin_redirect_with_notice(get_edit_post_link($client_id, 'raw'), 'user_already_linked');
    }

    $existing_user_id = peracrm_admin_find_linked_user_id($client_id);
    if ($existing_user_id > 0 && $existing_user_id !== $user_id) {
        peracrm_admin_redirect_with_notice(get_edit_post_link($client_id, 'raw'), 'client_already_linked');
    }

    update_user_meta($user_id, 'crm_client_id', $client_id);
    $linked = peracrm_admin_update_client_linked_user_id($client_id, $user_id);

    if (!$linked) {
        if ($existing_client_id > 0) {
            update_user_meta($user_id, 'crm_client_id', $existing_client_id);
        } else {
            delete_user_meta($user_id, 'crm_client_id');
        }
        peracrm_admin_redirect_with_notice(get_edit_post_link($client_id, 'raw'), 'link_failed');
    }

    peracrm_admin_redirect_with_notice(get_edit_post_link($client_id, 'raw'), 'link_success');
}

function peracrm_handle_unlink_user()
{
    $client_id = isset($_POST['peracrm_client_id']) ? (int) $_POST['peracrm_client_id'] : 0;
    $client = peracrm_admin_get_client($client_id);
    if (!$client) {
        wp_die('Invalid client');
    }

    if (!current_user_can('edit_post', $client_id)) {
        wp_die('Unauthorized');
    }

    check_admin_referer('peracrm_unlink_user');

    $linked_user_id = peracrm_admin_find_linked_user_id($client_id);
    if ($linked_user_id <= 0) {
        peracrm_admin_redirect_with_notice(get_edit_post_link($client_id, 'raw'), 'unlink_missing');
    }

    $updated = peracrm_admin_update_client_linked_user_id($client_id, 0);
    if (!$updated) {
        peracrm_admin_redirect_with_notice(get_edit_post_link($client_id, 'raw'), 'unlink_failed');
    }

    $current_client_id = (int) get_user_meta($linked_user_id, 'crm_client_id', true);
    if ($current_client_id === $client_id) {
        $deleted = delete_user_meta($linked_user_id, 'crm_client_id');
        if (!$deleted) {
            peracrm_admin_update_client_linked_user_id($client_id, $linked_user_id);
            peracrm_admin_redirect_with_notice(get_edit_post_link($client_id, 'raw'), 'unlink_failed');
        }
    }

    peracrm_admin_redirect_with_notice(get_edit_post_link($client_id, 'raw'), 'unlink_success');
}

function peracrm_handle_add_reminder()
{
    if (!peracrm_admin_user_can_manage()) {
        wp_die('Unauthorized');
    }

    check_admin_referer('peracrm_add_reminder');

    $client_id = isset($_POST['peracrm_client_id']) ? (int) $_POST['peracrm_client_id'] : 0;
    $client = peracrm_admin_get_client($client_id);
    if (!$client) {
        wp_die('Invalid client');
    }

    $due_at_raw = isset($_POST['peracrm_due_at']) ? wp_unslash($_POST['peracrm_due_at']) : '';
    $due_at_mysql = peracrm_admin_parse_datetime($due_at_raw);
    if ($due_at_mysql === '') {
        peracrm_admin_redirect_with_notice(get_edit_post_link($client_id, 'raw'), 'reminder_missing');
    }

    $note = isset($_POST['peracrm_reminder_note']) ? sanitize_textarea_field(wp_unslash($_POST['peracrm_reminder_note'])) : '';

    $reminder_id = peracrm_reminders_create($client_id, get_current_user_id(), $due_at_mysql, $note);
    if (!$reminder_id) {
        peracrm_admin_redirect_with_notice(get_edit_post_link($client_id, 'raw'), 'reminder_failed');
    }

    peracrm_admin_redirect_with_notice(get_edit_post_link($client_id, 'raw'), 'reminder_added');
}

function peracrm_handle_mark_reminder_done()
{
    if (!peracrm_admin_user_can_manage()) {
        wp_die('Unauthorized');
    }

    check_admin_referer('peracrm_mark_reminder_done');

    $reminder_id = isset($_POST['peracrm_reminder_id']) ? (int) $_POST['peracrm_reminder_id'] : 0;
    $reminder = peracrm_admin_get_reminder($reminder_id);
    if (!$reminder) {
        wp_die('Invalid reminder');
    }

    $client_id = (int) $reminder['client_id'];
    $client = peracrm_admin_get_client($client_id);
    if (!$client) {
        wp_die('Invalid client');
    }

    if (!current_user_can('manage_options') && (int) $reminder['advisor_user_id'] !== get_current_user_id()) {
        wp_die('Unauthorized');
    }

    $redirect = isset($_POST['peracrm_redirect']) ? esc_url_raw(wp_unslash($_POST['peracrm_redirect'])) : '';
    if ($redirect === '') {
        $redirect = get_edit_post_link($client_id, 'raw');
    }

    $success = peracrm_reminders_mark_done($reminder_id);
    if (!$success) {
        peracrm_admin_redirect_with_notice($redirect, 'reminder_failed');
    }

    peracrm_admin_redirect_with_notice($redirect, 'reminder_done');
}

function peracrm_admin_notices()
{
    if (!isset($_GET['peracrm_notice'])) {
        return;
    }

    $notice = sanitize_key(wp_unslash($_GET['peracrm_notice']));
    $messages = [
        'note_added' => ['success', 'CRM note added.'],
        'note_missing' => ['error', 'Please add a note before saving.'],
        'note_failed' => ['error', 'Unable to save CRM note.'],
        'reminder_added' => ['success', 'CRM reminder created.'],
        'reminder_missing' => ['error', 'Please provide a due date for the reminder.'],
        'reminder_failed' => ['error', 'Unable to update CRM reminder.'],
        'reminder_done' => ['success', 'CRM reminder marked as done.'],
        'link_success' => ['success', 'User linked to CRM client.'],
        'link_failed' => ['error', 'Unable to link user to CRM client.'],
        'unlink_success' => ['success', 'User unlinked from CRM client.'],
        'unlink_failed' => ['error', 'Unable to unlink user from CRM client.'],
        'user_missing' => ['error', 'Please enter a valid user email or username.'],
        'user_ambiguous' => ['error', 'Multiple users matched. Please use a more specific search.'],
        'user_already_linked' => ['error', 'That user is already linked to another CRM client.'],
        'client_already_linked' => ['error', 'This CRM client is already linked to another user.'],
        'unlink_missing' => ['error', 'This CRM client does not have a linked user.'],
    ];

    if (!isset($messages[$notice])) {
        return;
    }

    [$class, $message] = $messages[$notice];

    printf(
        '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
        esc_attr($class),
        esc_html($message)
    );
}

function peracrm_admin_add_client_columns($columns)
{
    $columns['peracrm_account'] = 'Account';
    $columns['last_activity'] = 'Last activity';
    return $columns;
}

function peracrm_admin_client_sortable_columns($columns)
{
    $columns['last_activity'] = 'last_activity';
    return $columns;
}

function peracrm_admin_client_filters()
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || 'edit-crm_client' !== $screen->id) {
        return;
    }

    $selected = peracrm_admin_get_engagement_filter();
    $options = [
        '' => 'All',
        'hot' => 'Hot',
        'warm' => 'Warm',
        'cold' => 'Cold',
        'none' => 'None',
    ];

    echo '<label for="peracrm-engagement-filter" class="screen-reader-text">Engagement</label>';
    echo '<select name="engagement" id="peracrm-engagement-filter">';
    foreach ($options as $value => $label) {
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr($value),
            selected($selected, $value, false),
            esc_html($label)
        );
    }
    echo '</select>';
}

function peracrm_admin_client_list_query($query)
{
    $context = peracrm_admin_client_list_context($query);
    if (!$context['is_client_list']) {
        return;
    }

    if (!$context['has_activity_table'] && 'none' === $context['engagement']) {
        $query->set('post__in', [0]);
    }
}

function peracrm_admin_client_list_clauses($clauses, $query)
{
    $context = peracrm_admin_client_list_context($query);
    if (!$context['is_client_list']) {
        return $clauses;
    }

    if (!$context['has_activity_table']) {
        return $clauses;
    }

    if ('last_activity' !== $context['orderby'] && $context['engagement'] === '') {
        return $clauses;
    }

    global $wpdb;

    $activity_table = peracrm_table('crm_activity');
    $activity_alias = 'peracrm_activity';

    if (false === strpos($clauses['join'], " {$activity_table} ")) {
        $clauses['join'] .= " LEFT JOIN {$activity_table} AS {$activity_alias} ON {$wpdb->posts}.ID = {$activity_alias}.client_id";
    }

    if (false === strpos($clauses['fields'], 'peracrm_last_activity_at')) {
        $clauses['fields'] .= ", MAX({$activity_alias}.created_at) AS peracrm_last_activity_at";
    }

    if (empty($clauses['groupby'])) {
        $clauses['groupby'] = "{$wpdb->posts}.ID";
    } elseif (false === strpos($clauses['groupby'], "{$wpdb->posts}.ID")) {
        $clauses['groupby'] .= ", {$wpdb->posts}.ID";
    }

    $having_conditions = [];
    if ($context['engagement'] !== '') {
        $now = current_time('timestamp');
        $seven_days = date('Y-m-d H:i:s', $now - DAY_IN_SECONDS * 7);
        $thirty_days = date('Y-m-d H:i:s', $now - DAY_IN_SECONDS * 30);

        if ('hot' === $context['engagement']) {
            $having_conditions[] = $wpdb->prepare('peracrm_last_activity_at >= %s', $seven_days);
        } elseif ('warm' === $context['engagement']) {
            $having_conditions[] = $wpdb->prepare('peracrm_last_activity_at < %s', $seven_days);
            $having_conditions[] = $wpdb->prepare('peracrm_last_activity_at >= %s', $thirty_days);
        } elseif ('cold' === $context['engagement']) {
            $having_conditions[] = $wpdb->prepare('peracrm_last_activity_at < %s', $thirty_days);
        } elseif ('none' === $context['engagement']) {
            $having_conditions[] = 'peracrm_last_activity_at IS NULL';
        }
    }

    if (!empty($having_conditions)) {
        $existing_having = trim($clauses['having']);
        $append_having = implode(' AND ', $having_conditions);
        $clauses['having'] = $existing_having === '' ? $append_having : "{$existing_having} AND {$append_having}";
    }

    if ('last_activity' === $context['orderby']) {
        $clauses['orderby'] = 'peracrm_last_activity_at IS NULL, peracrm_last_activity_at DESC';
    }

    return $clauses;
}

function peracrm_admin_client_list_context($query)
{
    static $cache = [];
    $key = is_object($query) ? spl_object_hash($query) : 'default';

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $is_client_list = false;
    if ($query instanceof WP_Query && is_admin() && $query->is_main_query()) {
        global $pagenow;
        $post_type = $query->get('post_type');
        $is_client_list = ('edit.php' === $pagenow && 'crm_client' === $post_type);
        if ($is_client_list && function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen && 'edit-crm_client' !== $screen->id) {
                $is_client_list = false;
            }
        }
    }

    $has_activity_table = function_exists('peracrm_activity_table_exists') && peracrm_activity_table_exists();
    $orderby = $query instanceof WP_Query ? sanitize_key($query->get('orderby')) : '';

    $cache[$key] = [
        'is_client_list' => $is_client_list,
        'has_activity_table' => $has_activity_table,
        'orderby' => $orderby,
        'engagement' => peracrm_admin_get_engagement_filter(),
    ];

    return $cache[$key];
}

function peracrm_admin_get_engagement_filter()
{
    static $filter = null;
    if (null !== $filter) {
        return $filter;
    }

    $value = isset($_GET['engagement']) ? sanitize_key(wp_unslash($_GET['engagement'])) : '';
    $allowed = ['hot', 'warm', 'cold', 'none'];
    if (!in_array($value, $allowed, true)) {
        $value = '';
    }

    $filter = $value;
    return $filter;
}

function peracrm_admin_render_client_columns($column, $post_id)
{
    if ('peracrm_account' === $column) {
        static $linked_user_cache = [];
        static $user_cache = [];

        if (array_key_exists($post_id, $linked_user_cache)) {
            $linked_user_id = $linked_user_cache[$post_id];
        } else {
            $linked_user_id = peracrm_admin_get_client_linked_user_id($post_id);
            if ($linked_user_id <= 0) {
                $users = get_users([
                    'meta_key' => 'crm_client_id',
                    'meta_value' => (int) $post_id,
                    'number' => 1,
                    'fields' => 'ids',
                ]);
                $linked_user_id = empty($users) ? 0 : (int) $users[0];
            }
            $linked_user_cache[$post_id] = $linked_user_id;
        }

        if ($linked_user_id <= 0) {
            echo 'Not linked';
            return;
        }

        if (isset($user_cache[$linked_user_id])) {
            $user = $user_cache[$linked_user_id];
        } else {
            $user = get_userdata($linked_user_id);
            $user_cache[$linked_user_id] = $user;
        }
        if (!$user) {
            echo 'Not linked';
            return;
        }

        $edit_link = get_edit_user_link($user->ID);
        $email = esc_html($user->user_email);
        if ($edit_link) {
            echo 'Linked: <a href="' . esc_url($edit_link) . '">' . $email . '</a>';
            return;
        }

        echo 'Linked: ' . $email;
        return;
    }

    if ('last_activity' !== $column) {
        return;
    }

    if (!function_exists('peracrm_activity_last')) {
        echo '&mdash;';
        return;
    }

    $activity = peracrm_activity_last($post_id);
    if (!$activity) {
        echo '&mdash;';
        return;
    }

    $event_type = isset($activity['event_type']) ? $activity['event_type'] : '';
    $label = peracrm_admin_activity_label($event_type);
    $created_at = isset($activity['created_at']) ? $activity['created_at'] : '';

    $bucket = function_exists('peracrm_activity_engagement_bucket')
        ? peracrm_activity_engagement_bucket($created_at)
        : 'none';
    $badge = peracrm_admin_activity_badge($bucket);

    $timestamp = $created_at ? strtotime($created_at) : 0;
    $relative = '';
    $title = '';
    if ($timestamp) {
        $relative = human_time_diff($timestamp, current_time('timestamp')) . ' ago';
        $title = wp_date(
            get_option('date_format') . ' ' . get_option('time_format'),
            $timestamp
        );
    }

    echo $badge . esc_html($label);
    if ($relative) {
        echo ' <span title="' . esc_attr($title) . '">' . esc_html($relative) . '</span>';
    }
}

function peracrm_admin_activity_label($event_type)
{
    $event_type = sanitize_key($event_type);
    $labels = [
        'view_property' => 'Viewed property',
        'login' => 'Logged in',
        'account_visit' => 'Visited account',
        'enquiry' => 'Submitted enquiry',
    ];

    if (isset($labels[$event_type])) {
        return $labels[$event_type];
    }

    if ($event_type === '') {
        return 'Activity';
    }

    return ucfirst($event_type);
}

function peracrm_admin_activity_badge($bucket)
{
    $bucket = sanitize_key($bucket);
    $colors = [
        'hot' => '#46b450',
        'warm' => '#dba617',
        'cold' => '#99a1a7',
        'none' => '#ccd0d4',
    ];

    $color = isset($colors[$bucket]) ? $colors[$bucket] : $colors['none'];

    return sprintf(
        '<span aria-hidden="true" style="display:inline-block;width:8px;height:8px;border-radius:50%%;background:%1$s;margin-right:6px;vertical-align:middle;"></span>',
        esc_attr($color)
    );
}
