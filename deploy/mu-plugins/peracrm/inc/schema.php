<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_maybe_upgrade_schema()
{
    $installed = (int) get_option('peracrm_schema_version', 0);
    if ($installed >= PERACRM_SCHEMA_VERSION) {
        return;
    }

    peracrm_upgrade_schema_to(PERACRM_SCHEMA_VERSION);
    update_option('peracrm_schema_version', PERACRM_SCHEMA_VERSION);
}

function peracrm_upgrade_schema_to($version)
{
    if ((int) $version < 1) {
        return;
    }

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $notes_table = peracrm_table('crm_notes');
    $reminders_table = peracrm_table('crm_reminders');
    $activity_table = peracrm_table('crm_activity');
    $client_property_table = peracrm_table('crm_client_property');

    $sql_notes = "CREATE TABLE {$notes_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        client_id BIGINT UNSIGNED NOT NULL,
        advisor_user_id BIGINT UNSIGNED NOT NULL,
        note_body LONGTEXT NOT NULL,
        visibility VARCHAR(20) NOT NULL DEFAULT 'internal',
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY client_created (client_id, created_at),
        KEY advisor_created (advisor_user_id, created_at)
    ) {$charset_collate};";

    $sql_reminders = "CREATE TABLE {$reminders_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        client_id BIGINT UNSIGNED NOT NULL,
        advisor_user_id BIGINT UNSIGNED NOT NULL,
        due_at DATETIME NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        note TEXT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY  (id),
        KEY advisor_due_status (advisor_user_id, status, due_at),
        KEY client_status_due (client_id, status, due_at)
    ) {$charset_collate};";

    $sql_activity = "CREATE TABLE {$activity_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        client_id BIGINT UNSIGNED NOT NULL,
        event_type VARCHAR(50) NOT NULL,
        event_payload LONGTEXT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY client_created (client_id, created_at),
        KEY type_created (event_type, created_at)
    ) {$charset_collate};";

    $sql_client_property = "CREATE TABLE {$client_property_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        client_id BIGINT UNSIGNED NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        relation_type VARCHAR(30) NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY uniq_client_property_type (client_id, property_id, relation_type),
        KEY client_rel (client_id, relation_type, created_at),
        KEY property_rel (property_id, relation_type, created_at)
    ) {$charset_collate};";

    dbDelta($sql_notes);
    dbDelta($sql_reminders);
    dbDelta($sql_activity);
    dbDelta($sql_client_property);
}
