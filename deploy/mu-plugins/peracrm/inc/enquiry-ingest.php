<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_normalize_email($email)
{
    $email = strtolower(trim((string) $email));
    $email = sanitize_email($email);
    if ($email === '' || !is_email($email)) {
        return '';
    }

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
        'orderby' => 'ID',
        'order' => 'ASC',
        'meta_query' => $meta_query,
    ]);

    if (empty($existing)) {
        return 0;
    }

    return (int) $existing[0];
}

function peracrm_find_client_id_by_email($email_norm)
{
    $email_norm = peracrm_normalize_email($email_norm);
    if ($email_norm === '') {
        return 0;
    }

    return peracrm_find_client_by_email($email_norm);
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
            update_post_meta($client_id, 'primary_email_normalized', $normalized);
            update_post_meta($client_id, 'crm_primary_email_normalized', $normalized);
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

function peracrm_enquiry_table_exists($table_name)
{
    global $wpdb;

    if ($table_name === '') {
        return false;
    }

    $query = $wpdb->prepare('SHOW TABLES LIKE %s', $table_name);

    return $wpdb->get_var($query) === $table_name;
}

function peracrm_ingest_enquiry(array $payload)
{
    $email = isset($payload['email']) ? sanitize_email($payload['email']) : '';
    if (!is_email($email)) {
        return 0;
    }

    $first_name = '';
    $last_name = '';
    $full_name = '';
    if (!empty($payload['name'])) {
        $full_name = sanitize_text_field($payload['name']);
        $parts = peracrm_enquiry_split_name($full_name);
        $first_name = $parts['first_name'];
        $last_name = $parts['last_name'];
    } else {
        $first_name = isset($payload['first_name']) ? sanitize_text_field($payload['first_name']) : '';
        $last_name = isset($payload['last_name']) ? sanitize_text_field($payload['last_name']) : '';
        $full_name = trim($first_name . ' ' . $last_name);
    }

    $phone = isset($payload['phone']) ? sanitize_text_field($payload['phone']) : '';
    $source = isset($payload['form_source']) ? sanitize_key($payload['form_source']) : 'web_enquiry';

    $client_id = 0;
    if (function_exists('peracrm_resolve_client_id_from_enquiry')) {
        $client_id = (int) peracrm_resolve_client_id_from_enquiry($email, $full_name, $phone, $source, true);
    } elseif (function_exists('peracrm_find_or_create_client_by_email')) {
        $client_id = (int) peracrm_find_or_create_client_by_email($email, [
            'first_name' => isset($first_name) ? $first_name : '',
            'last_name' => isset($last_name) ? $last_name : '',
            'phone' => $phone,
            'source' => $source,
            'status' => 'enquiry',
        ]);
    }

    if ($client_id <= 0) {
        return 0;
    }

    if (function_exists('peracrm_client_get_profile') && function_exists('peracrm_client_update_profile')) {
        $profile = peracrm_client_get_profile($client_id);
        $status = $profile['status'] !== '' ? $profile['status'] : 'enquiry';
        $client_type = $profile['client_type'];

        if ($client_type === '') {
            $type_hint = isset($payload['client_type']) ? sanitize_key($payload['client_type']) : '';
            $enquiry_type = isset($payload['enquiry_type']) ? sanitize_key($payload['enquiry_type']) : '';
            if ($type_hint !== '') {
                $client_type = $type_hint;
            } elseif (in_array($enquiry_type, ['property', 'investment'], true)) {
                $client_type = 'investor';
            } elseif ($enquiry_type === 'citizenship') {
                $client_type = 'citizenship';
            } elseif ($enquiry_type === 'general') {
                $client_type = 'lifestyle';
            }
        }

        $profile_data = [
            'status' => $status,
            'client_type' => $client_type,
            'preferred_contact' => $profile['preferred_contact'],
            'phone' => $phone !== '' ? $phone : $profile['phone'],
            'email' => $email,
        ];

        if (array_key_exists('budget_min_usd', $payload)) {
            $profile_data['budget_min_usd'] = $payload['budget_min_usd'];
        }

        if (array_key_exists('budget_max_usd', $payload)) {
            $profile_data['budget_max_usd'] = $payload['budget_max_usd'];
        }

        peracrm_client_update_profile($client_id, $profile_data);
    } else {
        if ($email !== '') {
            update_post_meta($client_id, '_peracrm_email', $email);
        }
        if ($phone !== '') {
            update_post_meta($client_id, '_peracrm_phone', $phone);
        }

        $existing_status = get_post_meta($client_id, '_peracrm_status', true);
        if ($existing_status === '') {
            update_post_meta($client_id, '_peracrm_status', 'enquiry');
        }
    }

    $property_id = isset($payload['property_id']) ? absint($payload['property_id']) : 0;
    $message = isset($payload['message']) ? wp_strip_all_tags($payload['message']) : '';
    if (strlen($message) > 200) {
        $message = substr($message, 0, 200);
    }

    $activity_payload = [
        'source' => $source,
        'form' => isset($payload['form']) ? sanitize_key($payload['form']) : '',
    ];

    if ($property_id > 0) {
        $activity_payload['property_id'] = $property_id;
    }

    if ($message !== '') {
        $activity_payload['message_excerpt'] = $message;
    }

    $can_log_activity = function_exists('peracrm_activity_table_exists') && peracrm_activity_table_exists();
    if ($can_log_activity && function_exists('peracrm_log_event')) {
        $should_log = true;
        if (function_exists('peracrm_activity_recent_exists')) {
            $should_log = !peracrm_activity_recent_exists($client_id, 'enquiry', $property_id, 15 * MINUTE_IN_SECONDS);
        }

        if ($should_log) {
            peracrm_log_event($client_id, 'enquiry', $activity_payload);
        }
    }

    if ($property_id > 0 && function_exists('peracrm_client_property_link') && function_exists('peracrm_table')) {
        $property = get_post($property_id);
        $table = peracrm_table('crm_client_property');
        if ($property && 'property' === $property->post_type && 'publish' === $property->post_status
            && peracrm_enquiry_table_exists($table)) {
            peracrm_client_property_link($client_id, $property_id, 'enquiry');
        }
    }

    if (function_exists('peracrm_reminder_add') && function_exists('peracrm_reminders_table_exists')) {
        if (peracrm_reminders_table_exists()) {
            $advisor_id = function_exists('peracrm_client_get_assigned_advisor_id')
                ? (int) peracrm_client_get_assigned_advisor_id($client_id)
                : 0;

            if ($advisor_id > 0) {
                $timestamp = current_time('timestamp') + DAY_IN_SECONDS;
                $due_at = date_i18n('Y-m-d H:i:s', $timestamp, false);
                peracrm_reminder_add($client_id, $advisor_id, $due_at, 'Follow up: new enquiry received');
            }
        }
    }

    return $client_id;
}
