<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_activity_insert($client_id, $event_type, $payload = null)
{
    global $wpdb;

    $table = peracrm_table('crm_activity');

    $result = $wpdb->insert(
        $table,
        [
            'client_id' => (int) $client_id,
            'event_type' => sanitize_key($event_type),
            'event_payload' => $payload !== null ? peracrm_json_encode($payload) : null,
            'created_at' => peracrm_now_mysql(),
        ],
        [
            '%d',
            '%s',
            '%s',
            '%s',
        ]
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
