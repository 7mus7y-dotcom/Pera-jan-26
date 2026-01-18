<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_normalize_email($email)
{
    $email = strtolower(trim((string) $email));

    return $email;
}

function peracrm_find_client_by_email($email)
{
    $email = sanitize_email($email);
    if ($email === '') {
        return 0;
    }

    if (!post_type_exists('crm_client')) {
        return 0;
    }

    $normalized = peracrm_normalize_email($email);
    if ($normalized === '') {
        return 0;
    }

    $values = array_values(array_unique(array_filter([$email, $normalized])));

    $meta_query = [
        'relation' => 'OR',
        [
            'key' => 'primary_email_normalized',
            'value' => $normalized,
            'compare' => '=',
        ],
        [
            'key' => 'crm_primary_email_normalized',
            'value' => $normalized,
            'compare' => '=',
        ],
    ];

    if (!empty($values)) {
        $meta_query[] = [
            'key' => 'primary_email',
            'value' => $values,
            'compare' => 'IN',
        ];
        $meta_query[] = [
            'key' => 'crm_primary_email',
            'value' => $values,
            'compare' => 'IN',
        ];
    }

    $existing = get_posts([
        'post_type' => 'crm_client',
        'posts_per_page' => 1,
        'post_status' => 'any',
        'fields' => 'ids',
        'meta_query' => $meta_query,
    ]);

    if (empty($existing)) {
        return 0;
    }

    return (int) $existing[0];
}

function peracrm_update_client_from_enquiry($client_id, $email, $name, $phone, $source)
{
    $client_id = (int) $client_id;
    if ($client_id <= 0) {
        return;
    }

    $email = sanitize_email($email);
    $phone = sanitize_text_field($phone);
    $source = sanitize_key($source);

    $name_parts = peracrm_enquiry_split_name($name);

    peracrm_enquiry_update_meta_if_empty($client_id, 'first_name', $name_parts['first_name']);
    peracrm_enquiry_update_meta_if_empty($client_id, 'last_name', $name_parts['last_name']);
    peracrm_enquiry_update_meta_if_empty($client_id, 'full_name', $name_parts['full_name']);
    peracrm_enquiry_update_meta_if_empty($client_id, 'crm_first_name', $name_parts['first_name']);
    peracrm_enquiry_update_meta_if_empty($client_id, 'crm_last_name', $name_parts['last_name']);

    if ($email !== '') {
        $normalized = peracrm_normalize_email($email);
        if ($normalized !== '') {
            peracrm_enquiry_update_meta_if_empty($client_id, 'primary_email_normalized', $normalized);
            peracrm_enquiry_update_meta_if_empty($client_id, 'crm_primary_email_normalized', $normalized);
        }

        if (!peracrm_enquiry_has_email($client_id)) {
            update_post_meta($client_id, 'primary_email', $email);
            update_post_meta($client_id, 'crm_primary_email', $email);
        }
    }

    peracrm_enquiry_update_meta_if_empty($client_id, 'phone', $phone);
    peracrm_enquiry_update_meta_if_empty($client_id, 'crm_phone', $phone);

    if ($source !== '') {
        peracrm_enquiry_update_meta_if_empty($client_id, 'source', $source);
        peracrm_enquiry_update_meta_if_empty($client_id, 'crm_source', $source);
    }

    peracrm_enquiry_update_meta_if_empty($client_id, 'status', 'enquiry');
    peracrm_enquiry_update_meta_if_empty($client_id, 'crm_status', 'enquiry');

    peracrm_enquiry_assign_advisor_if_missing($client_id);
}

function peracrm_create_client_from_enquiry($email, $name, $phone, $source)
{
    $email = sanitize_email($email);
    if ($email === '' || !is_email($email)) {
        return 0;
    }

    if (!post_type_exists('crm_client')) {
        return 0;
    }

    $normalized = peracrm_normalize_email($email);
    if (!peracrm_enquiry_can_create_email($normalized)) {
        return 0;
    }

    $name_parts = peracrm_enquiry_split_name($name);
    $title = $name_parts['full_name'] !== '' ? $name_parts['full_name'] : $email;

    $post_id = wp_insert_post([
        'post_type' => 'crm_client',
        'post_title' => $title,
        'post_status' => 'publish',
    ], true);

    if (is_wp_error($post_id)) {
        return 0;
    }

    $post_id = (int) $post_id;

    peracrm_update_client_from_enquiry($post_id, $email, $name, $phone, $source);
    peracrm_enquiry_touch_email_rate_limit($normalized);

    if (function_exists('peracrm_log_event')) {
        peracrm_log_event($post_id, 'client_created', [
            'email' => $email,
            'source' => $source,
        ]);
    }

    return $post_id;
}

function peracrm_resolve_client_id_from_enquiry($email, $name = '', $phone = '', $source = 'web_enquiry', $create_if_missing = true)
{
    $client_id = 0;

    if (is_user_logged_in()) {
        $client_id = (int) get_user_meta(get_current_user_id(), 'crm_client_id', true);
        if ($client_id > 0) {
            peracrm_update_client_from_enquiry($client_id, $email, $name, $phone, $source);
            return $client_id;
        }
    }

    $email = sanitize_email($email);
    if (!is_email($email)) {
        return 0;
    }

    $user = get_user_by('email', $email);
    if ($user) {
        $client_id = (int) get_user_meta($user->ID, 'crm_client_id', true);
        if ($client_id > 0) {
            peracrm_update_client_from_enquiry($client_id, $email, $name, $phone, $source);
            return $client_id;
        }
    }

    $client_id = peracrm_find_client_by_email($email);
    if ($client_id > 0) {
        peracrm_update_client_from_enquiry($client_id, $email, $name, $phone, $source);
        return $client_id;
    }

    if (!$create_if_missing) {
        return 0;
    }

    $client_id = peracrm_create_client_from_enquiry($email, $name, $phone, $source);
    if ($client_id > 0) {
        return $client_id;
    }

    return 0;
}

function peracrm_enquiry_split_name($name)
{
    $name = trim((string) $name);
    if ($name === '') {
        return [
            'first_name' => '',
            'last_name' => '',
            'full_name' => '',
        ];
    }

    $parts = preg_split('/\s+/', $name);
    $first = array_shift($parts);
    $last = $parts ? implode(' ', $parts) : '';

    return [
        'first_name' => sanitize_text_field($first),
        'last_name' => sanitize_text_field($last),
        'full_name' => sanitize_text_field($name),
    ];
}

function peracrm_enquiry_update_meta_if_empty($client_id, $meta_key, $value)
{
    $client_id = (int) $client_id;
    $value = is_string($value) ? trim($value) : $value;
    if ($client_id <= 0 || $value === '' || $value === null) {
        return;
    }

    $existing = get_post_meta($client_id, $meta_key, true);
    if ($existing !== '' && $existing !== null) {
        return;
    }

    update_post_meta($client_id, $meta_key, $value);
}

function peracrm_enquiry_has_email($client_id)
{
    $client_id = (int) $client_id;
    if ($client_id <= 0) {
        return false;
    }

    $primary = get_post_meta($client_id, 'primary_email', true);
    if ($primary !== '') {
        return true;
    }

    $legacy = get_post_meta($client_id, 'crm_primary_email', true);

    return $legacy !== '';
}

function peracrm_enquiry_can_create_email($normalized_email)
{
    $normalized_email = peracrm_normalize_email($normalized_email);
    if ($normalized_email === '') {
        return false;
    }

    $key = peracrm_enquiry_email_rate_limit_key($normalized_email);
    $existing = get_transient($key);

    return empty($existing);
}

function peracrm_enquiry_touch_email_rate_limit($normalized_email)
{
    $normalized_email = peracrm_normalize_email($normalized_email);
    if ($normalized_email === '') {
        return;
    }

    $key = peracrm_enquiry_email_rate_limit_key($normalized_email);
    set_transient($key, time(), DAY_IN_SECONDS);
}

function peracrm_enquiry_email_rate_limit_key($normalized_email)
{
    $normalized_email = peracrm_normalize_email($normalized_email);

    return 'peracrm_ingest_email_' . md5($normalized_email);
}

function peracrm_enquiry_get_assigned_advisor_id($client_id)
{
    $client_id = (int) $client_id;
    if ($client_id <= 0) {
        return 0;
    }

    if (function_exists('peracrm_client_get_assigned_advisor_id')) {
        return (int) peracrm_client_get_assigned_advisor_id($client_id);
    }

    return 0;
}

function peracrm_enquiry_assign_advisor_if_missing($client_id)
{
    $client_id = (int) $client_id;
    if ($client_id <= 0) {
        return;
    }

    $advisor_id = peracrm_enquiry_get_assigned_advisor_id($client_id);
    if ($advisor_id > 0) {
        return;
    }

    if (!is_user_logged_in()) {
        return;
    }

    if (!current_user_can('edit_crm_clients') && !current_user_can('manage_options')) {
        return;
    }

    $advisor_id = get_current_user_id();
    if ($advisor_id <= 0) {
        return;
    }

    update_post_meta($client_id, 'assigned_advisor_user_id', $advisor_id);
    update_post_meta($client_id, 'crm_assigned_advisor', $advisor_id);
}
