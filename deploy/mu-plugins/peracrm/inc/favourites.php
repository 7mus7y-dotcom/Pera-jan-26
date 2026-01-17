<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_favourites_table_exists()
{
    global $wpdb;

    static $exists = null;
    if (null !== $exists) {
        return $exists;
    }

    $table = peracrm_table('crm_client_property');

    $query = $wpdb->prepare('SHOW TABLES LIKE %s', $table);

    $exists = $wpdb->get_var($query) === $table;

    return $exists;
}

function peracrm_favourites_unique_key_exists()
{
    global $wpdb;

    static $exists = null;
    if (null !== $exists) {
        return $exists;
    }

    if (!peracrm_favourites_table_exists()) {
        return false;
    }

    $table = peracrm_table('crm_client_property');

    $query = $wpdb->prepare(
        "SHOW INDEX FROM {$table} WHERE Key_name = %s AND Non_unique = 0",
        'uniq_client_property_type'
    );

    $exists = !empty($wpdb->get_var($query));

    return $exists;
}

function peracrm_favourite_add($client_id, $property_id)
{
    global $wpdb;

    if (!peracrm_favourites_table_exists()) {
        return false;
    }

    $client_id = (int) $client_id;
    $property_id = (int) $property_id;

    if ($client_id <= 0 || $property_id <= 0) {
        return false;
    }

    if (!peracrm_favourites_unique_key_exists()) {
        if (peracrm_favourite_is_favourited($client_id, $property_id)) {
            return true;
        }
    }

    $table = peracrm_table('crm_client_property');

    $query = $wpdb->prepare(
        "INSERT INTO {$table} (client_id, property_id, relation_type, created_at)
         VALUES (%d, %d, %s, %s)
         ON DUPLICATE KEY UPDATE created_at = created_at",
        $client_id,
        $property_id,
        'favourite',
        peracrm_now_mysql()
    );

    $result = $wpdb->query($query);

    return $result !== false;
}

function peracrm_favourite_remove($client_id, $property_id)
{
    global $wpdb;

    if (!peracrm_favourites_table_exists()) {
        return false;
    }

    $client_id = (int) $client_id;
    $property_id = (int) $property_id;

    if ($client_id <= 0 || $property_id <= 0) {
        return false;
    }

    $table = peracrm_table('crm_client_property');

    $query = $wpdb->prepare(
        "DELETE FROM {$table} WHERE client_id = %d AND property_id = %d AND relation_type = %s",
        $client_id,
        $property_id,
        'favourite'
    );

    $result = $wpdb->query($query);

    return $result !== false;
}

function peracrm_favourite_is_favourited($client_id, $property_id)
{
    global $wpdb;

    if (!peracrm_favourites_table_exists()) {
        return false;
    }

    $client_id = (int) $client_id;
    $property_id = (int) $property_id;

    if ($client_id <= 0 || $property_id <= 0) {
        return false;
    }

    $table = peracrm_table('crm_client_property');

    $query = $wpdb->prepare(
        "SELECT id FROM {$table} WHERE client_id = %d AND property_id = %d AND relation_type = %s LIMIT 1",
        $client_id,
        $property_id,
        'favourite'
    );

    return (bool) $wpdb->get_var($query);
}

function peracrm_favourite_list($client_id, $limit = 50, $offset = 0)
{
    global $wpdb;

    if (!peracrm_favourites_table_exists()) {
        return [];
    }

    $client_id = (int) $client_id;
    $limit = (int) $limit;
    $offset = (int) $offset;

    if ($client_id <= 0 || $limit <= 0) {
        return [];
    }

    $table = peracrm_table('crm_client_property');

    $query = $wpdb->prepare(
        "SELECT property_id, created_at FROM {$table}
         WHERE client_id = %d AND relation_type = %s
         ORDER BY created_at DESC
         LIMIT %d OFFSET %d",
        $client_id,
        'favourite',
        $limit,
        $offset
    );

    return $wpdb->get_results($query, ARRAY_A);
}

function peracrm_activity_table_exists()
{
    global $wpdb;

    static $exists = null;
    if (null !== $exists) {
        return $exists;
    }

    $table = peracrm_table('crm_activity');

    $query = $wpdb->prepare('SHOW TABLES LIKE %s', $table);

    $exists = $wpdb->get_var($query) === $table;

    return $exists;
}

function peracrm_handle_toggle_favourite()
{
    $redirect_back = wp_get_referer();
    if (!$redirect_back) {
        $redirect_back = home_url('/');
    }

    if (!is_user_logged_in()) {
        wp_safe_redirect(wp_login_url($redirect_back));
        exit;
    }

    check_admin_referer('peracrm_toggle_favourite');

    $client_id = (int) get_user_meta(get_current_user_id(), 'crm_client_id', true);
    if ($client_id <= 0) {
        wp_safe_redirect(add_query_arg('fav', 0, $redirect_back));
        exit;
    }

    $property_id = isset($_POST['property_id']) ? absint($_POST['property_id']) : 0;
    $property = $property_id ? get_post($property_id) : null;

    if (!$property || 'property' !== $property->post_type || 'publish' !== $property->post_status) {
        wp_safe_redirect(add_query_arg('fav', 0, $redirect_back));
        exit;
    }

    if (!peracrm_favourites_table_exists()) {
        wp_safe_redirect(add_query_arg('fav', 0, $redirect_back));
        exit;
    }

    $was_favourited = peracrm_favourite_is_favourited($client_id, $property_id);
    $success = false;
    $event_type = '';
    $query_arg = '';

    if ($was_favourited) {
        $success = peracrm_favourite_remove($client_id, $property_id);
        $event_type = 'unfavourite';
        $query_arg = 'unfav';
    } else {
        $success = peracrm_favourite_add($client_id, $property_id);
        $event_type = 'favourite';
        $query_arg = 'fav';
    }

    if ($success && function_exists('peracrm_activity_insert') && peracrm_activity_table_exists()) {
        peracrm_activity_insert($client_id, $event_type, [
            'property_id' => $property_id,
        ]);
    }

    if ($query_arg) {
        $redirect_back = add_query_arg($query_arg, $success ? 1 : 0, $redirect_back);
    }

    wp_safe_redirect($redirect_back);
    exit;
}

add_action('admin_post_peracrm_toggle_favourite', 'peracrm_handle_toggle_favourite');
add_action('admin_post_nopriv_peracrm_toggle_favourite', 'peracrm_handle_toggle_favourite');
