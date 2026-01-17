<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_notes_create($client_id, $advisor_user_id, $note_body, $visibility = 'internal')
{
    global $wpdb;

    $table = peracrm_table('crm_notes');

    $result = $wpdb->insert(
        $table,
        [
            'client_id' => (int) $client_id,
            'advisor_user_id' => (int) $advisor_user_id,
            'note_body' => sanitize_text_field($note_body),
            'visibility' => sanitize_key($visibility),
            'created_at' => peracrm_now_mysql(),
        ],
        [
            '%d',
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

function peracrm_notes_list($client_id, $limit = 50, $offset = 0)
{
    global $wpdb;

    $table = peracrm_table('crm_notes');

    $query = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE client_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
        (int) $client_id,
        (int) $limit,
        (int) $offset
    );

    return $wpdb->get_results($query, ARRAY_A);
}
