<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_reminders_create($client_id, $advisor_user_id, $due_at_mysql, $note = '')
{
    global $wpdb;

    $table = peracrm_table('crm_reminders');

    $result = $wpdb->insert(
        $table,
        [
            'client_id' => (int) $client_id,
            'advisor_user_id' => (int) $advisor_user_id,
            'due_at' => sanitize_text_field($due_at_mysql),
            'status' => 'pending',
            'note' => $note !== '' ? sanitize_text_field($note) : null,
            'created_at' => peracrm_now_mysql(),
            'updated_at' => null,
        ],
        [
            '%d',
            '%d',
            '%s',
            '%s',
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

function peracrm_reminders_due_for_advisor($advisor_user_id, $until_mysql, $limit = 100)
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

function peracrm_reminders_mark_done($id)
{
    global $wpdb;

    $table = peracrm_table('crm_reminders');

    $result = $wpdb->update(
        $table,
        [
            'status' => 'done',
            'updated_at' => peracrm_now_mysql(),
        ],
        [
            'id' => (int) $id,
        ],
        [
            '%s',
            '%s',
        ],
        [
            '%d',
        ]
    );

    return $result !== false;
}
