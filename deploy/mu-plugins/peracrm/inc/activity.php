<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_activity_recent_exists($client_id, $event_type, $object_id, $window_seconds)
{
    $client_id = (int) $client_id;
    $event_type = sanitize_key($event_type);
    $object_id = (int) $object_id;
    $window_seconds = (int) $window_seconds;

    if ($client_id <= 0 || $event_type === '' || $window_seconds <= 0) {
        return false;
    }

    if (function_exists('peracrm_activity_table_exists') && peracrm_activity_table_exists()) {
        global $wpdb;

        $table = peracrm_table('crm_activity');
        $since = date('Y-m-d H:i:s', current_time('timestamp') - $window_seconds);

        if ($object_id > 0) {
            $like = '%"property_id":' . $object_id . '%';
            $query = $wpdb->prepare(
                "SELECT id FROM {$table}
                 WHERE client_id = %d AND event_type = %s AND created_at >= %s AND event_payload LIKE %s
                 LIMIT 1",
                $client_id,
                $event_type,
                $since,
                $like
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT id FROM {$table}
                 WHERE client_id = %d AND event_type = %s AND created_at >= %s
                 LIMIT 1",
                $client_id,
                $event_type,
                $since
            );
        }

        return (bool) $wpdb->get_var($query);
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        return false;
    }

    $linked_client_id = (int) get_user_meta($user_id, 'crm_client_id', true);
    if ($linked_client_id !== $client_id) {
        return false;
    }

    $key = peracrm_activity_throttle_key($client_id, $event_type, $object_id);
    $last_seen = (int) get_user_meta($user_id, $key, true);

    if ($last_seen <= 0) {
        return false;
    }

    return (time() - $last_seen) < $window_seconds;
}

function peracrm_activity_log($client_id, $event_type, array $payload = [])
{
    $client_id = (int) $client_id;
    $event_type = sanitize_key($event_type);
    $object_id = isset($payload['property_id']) ? absint($payload['property_id']) : 0;

    if ($client_id <= 0 || $event_type === '') {
        return false;
    }

    static $logged = [];
    $logged_key = $client_id . '|' . $event_type . '|' . $object_id;
    if (isset($logged[$logged_key])) {
        return false;
    }
    $logged[$logged_key] = true;

    if (!function_exists('peracrm_activity_table_exists') || !peracrm_activity_table_exists()) {
        return false;
    }

    if (!function_exists('peracrm_activity_insert')) {
        return false;
    }

    return (bool) peracrm_activity_insert($client_id, $event_type, $payload);
}

function peracrm_activity_list($client_id, $limit = 50, $offset = 0, $event_type = null)
{
    $client_id = (int) $client_id;
    $limit = (int) $limit;
    $offset = (int) $offset;
    $event_type = null === $event_type ? null : sanitize_key($event_type);

    if ($client_id <= 0 || $limit <= 0) {
        return [];
    }

    if (!function_exists('peracrm_activity_table_exists') || !peracrm_activity_table_exists()) {
        return [];
    }

    global $wpdb;

    $table = peracrm_table('crm_activity');

    if ($event_type) {
        $query = $wpdb->prepare(
            "SELECT id, client_id, event_type, event_payload, created_at
             FROM {$table}
             WHERE client_id = %d AND event_type = %s
             ORDER BY created_at DESC
             LIMIT %d OFFSET %d",
            $client_id,
            $event_type,
            $limit,
            $offset
        );
    } else {
        $query = $wpdb->prepare(
            "SELECT id, client_id, event_type, event_payload, created_at
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

function peracrm_activity_count($client_id, $event_type = null)
{
    $client_id = (int) $client_id;
    $event_type = null === $event_type ? null : sanitize_key($event_type);

    if ($client_id <= 0) {
        return 0;
    }

    if (!function_exists('peracrm_activity_table_exists') || !peracrm_activity_table_exists()) {
        return 0;
    }

    global $wpdb;

    $table = peracrm_table('crm_activity');

    if ($event_type) {
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
             WHERE client_id = %d AND event_type = %s",
            $client_id,
            $event_type
        );
    } else {
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
             WHERE client_id = %d",
            $client_id
        );
    }

    return (int) $wpdb->get_var($query);
}

function peracrm_activity_throttle_key($client_id, $event_type, $object_id)
{
    $client_id = absint($client_id);
    $event_type = sanitize_key($event_type);
    $object_id = absint($object_id);

    return sprintf('peracrm_activity_throttle_%d_%s_%d', $client_id, $event_type, $object_id);
}

function peracrm_activity_throttle_touch($client_id, $event_type, $object_id)
{
    $user_id = get_current_user_id();
    if (!$user_id) {
        return;
    }

    $client_id = (int) $client_id;
    $linked_client_id = (int) get_user_meta($user_id, 'crm_client_id', true);
    if ($client_id <= 0 || $linked_client_id !== $client_id) {
        return;
    }

    $key = peracrm_activity_throttle_key($client_id, $event_type, $object_id);
    update_user_meta($user_id, $key, time());
}
