<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_activity_insert($client_id, $event_type, $payload = null)
{
    if (!function_exists('peracrm_activity_table_exists') || !peracrm_activity_table_exists()) {
        return 0;
    }

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

function peracrm_activity_list_recent_pipeline($limit, $advisor_id = 0)
{
    $limit = max(1, (int) $limit);
    $advisor_id = (int) $advisor_id;

    if (!function_exists('peracrm_activity_table_exists') || !peracrm_activity_table_exists()) {
        return [];
    }

    global $wpdb;

    $table = peracrm_table('crm_activity');
    $event_types = ['status_changed', 'advisor_reassigned', 'reminder_added'];
    $type_placeholders = implode(',', array_fill(0, count($event_types), '%s'));
    $params = $event_types;
    $joins = '';
    $where = "event_type IN ({$type_placeholders})";

    if ($advisor_id > 0) {
        $meta_keys = function_exists('peracrm_pipeline_assigned_meta_keys')
            ? peracrm_pipeline_assigned_meta_keys()
            : ['assigned_advisor_user_id', 'crm_assigned_advisor'];
        $meta_keys = array_values(array_filter(array_map('sanitize_key', $meta_keys)));
        if (empty($meta_keys)) {
            return [];
        }

        $meta_placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));
        $joins .= " INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = {$table}.client_id";
        $joins .= " AND pm.meta_key IN ({$meta_placeholders}) AND pm.meta_value = %d";
        $params = array_merge($params, $meta_keys, [$advisor_id]);
    }

    $params[] = $limit;

    $query = $wpdb->prepare(
        "SELECT DISTINCT {$table}.id, {$table}.client_id, {$table}.event_type, {$table}.event_payload, {$table}.created_at
         FROM {$table}
         {$joins}
         WHERE {$where}
         ORDER BY {$table}.created_at DESC
         LIMIT %d",
        $params
    );

    return $wpdb->get_results($query, ARRAY_A);
}
