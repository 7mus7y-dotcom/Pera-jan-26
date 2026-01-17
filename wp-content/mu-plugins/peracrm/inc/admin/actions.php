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
    if (!current_user_can('edit_post', $client_id)) {
        wp_die('Unauthorized');
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
    if (!current_user_can('edit_post', $client_id)) {
        wp_die('Unauthorized');
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
