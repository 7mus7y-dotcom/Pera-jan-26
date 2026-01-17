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

function peracrm_activity_list($client_id, $limit = 100, $offset = 0)
{
    global $wpdb;

    $table = peracrm_table('crm_activity');

    $query = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE client_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
        (int) $client_id,
        (int) $limit,
        (int) $offset
    );

    return $wpdb->get_results($query, ARRAY_A);
}
