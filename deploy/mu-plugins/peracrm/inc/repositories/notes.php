<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_notes_create($client_id, $advisor_user_id, $note_body, $visibility = 'internal')
{
    return peracrm_notes_insert($client_id, $advisor_user_id, $note_body, $visibility);
}

function peracrm_notes_table_exists()
{
    global $wpdb;

    static $exists = null;
    if (null !== $exists) {
        return $exists;
    }

    $table = peracrm_table('crm_notes');
    $query = $wpdb->prepare('SHOW TABLES LIKE %s', $table);
    $exists = $wpdb->get_var($query) === $table;

    return $exists;
}

function peracrm_note_add($client_id, $advisor_user_id, $note_body)
{
    return peracrm_notes_insert($client_id, $advisor_user_id, $note_body, 'internal');
}

function peracrm_notes_list($client_id, $limit = 50, $offset = 0)
{
    if (peracrm_notes_table_exists()) {
        return peracrm_notes_list_table($client_id, $limit, $offset);
    }

    return peracrm_notes_list_fallback($client_id, $limit, $offset);
}

function peracrm_notes_count($client_id)
{
    if (peracrm_notes_table_exists()) {
        return peracrm_notes_count_table($client_id);
    }

    return peracrm_notes_count_fallback($client_id);
}

function peracrm_notes_insert($client_id, $advisor_user_id, $note_body, $visibility)
{
    $client_id = (int) $client_id;
    $advisor_user_id = (int) $advisor_user_id;
    if ($client_id <= 0 || $advisor_user_id <= 0) {
        return 0;
    }

    $note_body = sanitize_textarea_field($note_body);
    $note_body = trim($note_body);
    if ($note_body === '') {
        return 0;
    }

    $visibility = sanitize_key($visibility);
    if ($visibility === '') {
        $visibility = 'internal';
    }

    if (peracrm_notes_table_exists()) {
        return peracrm_notes_insert_table($client_id, $advisor_user_id, $note_body, $visibility);
    }

    if ($visibility !== 'internal') {
        return 0;
    }

    return peracrm_notes_insert_fallback($client_id, $advisor_user_id, $note_body);
}

function peracrm_notes_table_has_visibility_column()
{
    global $wpdb;

    static $has_column = null;
    if (null !== $has_column) {
        return $has_column;
    }

    if (!peracrm_notes_table_exists()) {
        $has_column = false;
        return $has_column;
    }

    $table = peracrm_table('crm_notes');
    $query = $wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", 'visibility');
    $has_column = !empty($wpdb->get_var($query));

    return $has_column;
}

function peracrm_notes_insert_table($client_id, $advisor_user_id, $note_body, $visibility)
{
    global $wpdb;

    $table = peracrm_table('crm_notes');
    $created_at = peracrm_now_mysql();

    if (peracrm_notes_table_has_visibility_column()) {
        $query = $wpdb->prepare(
            "INSERT INTO {$table} (client_id, advisor_user_id, note_body, visibility, created_at)
             VALUES (%d, %d, %s, %s, %s)",
            $client_id,
            $advisor_user_id,
            $note_body,
            $visibility,
            $created_at
        );
    } else {
        $query = $wpdb->prepare(
            "INSERT INTO {$table} (client_id, advisor_user_id, note_body, created_at)
             VALUES (%d, %d, %s, %s)",
            $client_id,
            $advisor_user_id,
            $note_body,
            $created_at
        );
    }

    $result = $wpdb->query($query);
    if (false === $result) {
        return 0;
    }

    return (int) $wpdb->insert_id;
}

function peracrm_notes_list_table($client_id, $limit, $offset)
{
    global $wpdb;

    $client_id = (int) $client_id;
    $limit = max(1, (int) $limit);
    $offset = max(0, (int) $offset);

    $table = peracrm_table('crm_notes');
    if (peracrm_notes_table_has_visibility_column()) {
        $query = $wpdb->prepare(
            "SELECT id, client_id, advisor_user_id, note_body, created_at
             FROM {$table}
             WHERE client_id = %d AND visibility = %s
             ORDER BY created_at DESC
             LIMIT %d OFFSET %d",
            $client_id,
            'internal',
            $limit,
            $offset
        );
    } else {
        $query = $wpdb->prepare(
            "SELECT id, client_id, advisor_user_id, note_body, created_at
             FROM {$table}
             WHERE client_id = %d
             ORDER BY created_at DESC
             LIMIT %d OFFSET %d",
            $client_id,
            $limit,
            $offset
        );
    }

    return $wpdb->get_results($query, ARRAY_A);
}

function peracrm_notes_count_table($client_id)
{
    global $wpdb;

    $client_id = (int) $client_id;
    $table = peracrm_table('crm_notes');

    if (peracrm_notes_table_has_visibility_column()) {
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE client_id = %d AND visibility = %s",
            $client_id,
            'internal'
        );
    } else {
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE client_id = %d",
            $client_id
        );
    }

    return (int) $wpdb->get_var($query);
}

function peracrm_notes_insert_fallback($client_id, $advisor_user_id, $note_body)
{
    $note_body = peracrm_notes_truncate_fallback($note_body);
    if ($note_body === '') {
        return 0;
    }

    $notes = peracrm_notes_fallback_get($client_id);

    array_unshift($notes, [
        'advisor_user_id' => $advisor_user_id,
        'note_body' => $note_body,
        'created_at' => peracrm_now_mysql(),
    ]);

    if (count($notes) > 50) {
        $notes = array_slice($notes, 0, 50);
    }

    $updated = update_post_meta($client_id, '_peracrm_notes_fallback', $notes);
    if (!$updated) {
        return 0;
    }

    return 1;
}

function peracrm_notes_list_fallback($client_id, $limit, $offset)
{
    $notes = peracrm_notes_fallback_get($client_id);
    $limit = max(1, (int) $limit);
    $offset = max(0, (int) $offset);

    if ($offset >= count($notes)) {
        return [];
    }

    $limit = min($limit, 50);

    return array_slice($notes, $offset, $limit);
}

function peracrm_notes_count_fallback($client_id)
{
    $notes = peracrm_notes_fallback_get($client_id);
    return count($notes);
}

function peracrm_notes_fallback_get($client_id)
{
    $client_id = (int) $client_id;
    if ($client_id <= 0) {
        return [];
    }

    $notes = get_post_meta($client_id, '_peracrm_notes_fallback', true);
    if (!is_array($notes)) {
        return [];
    }

    return $notes;
}

function peracrm_notes_truncate_fallback($note_body)
{
    $note_body = trim($note_body);
    if ($note_body === '') {
        return '';
    }

    if (strlen($note_body) > 2000) {
        $note_body = substr($note_body, 0, 2000);
    }

    return $note_body;
}
