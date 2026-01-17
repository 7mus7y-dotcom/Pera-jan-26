<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_activity_insert($client_id, $event_type, $payload = null)
{
    global $wpdb;

    $table = peracrm_table('crm_activity');

    $result = $wpdb->query(
        $wpdb->prepare(
            "INSERT INTO {$table} (client_id, event_type, event_payload, created_at)
             VALUES (%d, %s, %s, %s)",
            (int) $client_id,
            sanitize_key($event_type),
            $payload !== null ? peracrm_json_encode($payload) : null,
            peracrm_now_mysql()
        )
    );

    if (false === $result) {
        return 0;
    }

    return (int) $wpdb->insert_id;
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
