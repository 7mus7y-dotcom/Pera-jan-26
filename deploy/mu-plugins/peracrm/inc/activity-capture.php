<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_capture_property_view()
{
    if (is_admin() || !is_user_logged_in()) {
        return;
    }

    if (!is_singular('property')) {
        return;
    }

    $user_id = get_current_user_id();
    $client_id = (int) get_user_meta($user_id, 'crm_client_id', true);
    if ($client_id <= 0) {
        return;
    }

    $property_id = get_queried_object_id();
    if ($property_id <= 0) {
        return;
    }

    $window_seconds = 24 * HOUR_IN_SECONDS;
    if (peracrm_activity_recent_exists($client_id, 'view_property', $property_id, $window_seconds)) {
        return;
    }

    $has_table = function_exists('peracrm_activity_table_exists') && peracrm_activity_table_exists();
    if (!$has_table) {
        peracrm_activity_throttle_touch($client_id, 'view_property', $property_id);
        return;
    }

    $property = get_post($property_id);
    $payload = [
        'property_id' => $property_id,
    ];

    if ($property) {
        $title = sanitize_text_field($property->post_title);
        if ($title !== '') {
            $payload['property_title'] = $title;
        }
    }

    peracrm_activity_log($client_id, 'view_property', $payload);
}

function peracrm_capture_account_visit()
{
    if (is_admin() || !is_user_logged_in()) {
        return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
    $path = $request_uri ? wp_parse_url($request_uri, PHP_URL_PATH) : '';
    $path = is_string($path) ? $path : '';

    if ($path !== '/account' && strpos($path, '/account/') !== 0) {
        return;
    }

    $user_id = get_current_user_id();
    $client_id = (int) get_user_meta($user_id, 'crm_client_id', true);
    if ($client_id <= 0) {
        return;
    }

    $window_seconds = 24 * HOUR_IN_SECONDS;
    if (peracrm_activity_recent_exists($client_id, 'account_visit', 0, $window_seconds)) {
        return;
    }

    $has_table = function_exists('peracrm_activity_table_exists') && peracrm_activity_table_exists();
    if (!$has_table) {
        peracrm_activity_throttle_touch($client_id, 'account_visit', 0);
        return;
    }

    peracrm_activity_log($client_id, 'account_visit', []);
}

function peracrm_capture_login($user_login, $user)
{
    if (!$user instanceof WP_User) {
        return;
    }

    $client_id = (int) get_user_meta($user->ID, 'crm_client_id', true);
    if ($client_id <= 0) {
        return;
    }

    $window_seconds = 12 * HOUR_IN_SECONDS;
    if (peracrm_activity_recent_exists($client_id, 'login', 0, $window_seconds)) {
        return;
    }

    $has_table = function_exists('peracrm_activity_table_exists') && peracrm_activity_table_exists();
    if (!$has_table) {
        peracrm_activity_throttle_touch($client_id, 'login', 0);
        return;
    }

    peracrm_activity_log($client_id, 'login', []);
}

add_action('template_redirect', 'peracrm_capture_property_view', 9);
add_action('template_redirect', 'peracrm_capture_account_visit', 9);
add_action('wp_login', 'peracrm_capture_login', 10, 2);
