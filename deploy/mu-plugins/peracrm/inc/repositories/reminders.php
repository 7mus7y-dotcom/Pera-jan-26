<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_reminders_table_exists()
{
    global $wpdb;

    static $exists = null;
    if (null !== $exists) {
        return $exists;
    }

    $table = peracrm_table('crm_reminders');
    $query = $wpdb->prepare('SHOW TABLES LIKE %s', $table);
    $exists = $wpdb->get_var($query) === $table;

    return $exists;
}

function peracrm_reminder_add($client_id, $advisor_user_id, $due_at, $note)
{
    // due_at is expected in site timezone (per admin datetime parsing for UX consistency).
    $client_id = (int) $client_id;
    $advisor_user_id = (int) $advisor_user_id;
    if ($client_id <= 0 || $advisor_user_id <= 0) {
        return 0;
    }

    $due_at = peracrm_reminders_sanitize_due_at($due_at);
    if ($due_at === '') {
        return 0;
    }

    $note = peracrm_reminders_sanitize_note($note, 5000);

    if (peracrm_reminders_table_exists()) {
        return peracrm_reminders_insert_table($client_id, $advisor_user_id, $due_at, $note);
    }

    return peracrm_reminders_insert_fallback($client_id, $advisor_user_id, $due_at, $note);
}

function peracrm_reminder_update_status($reminder_id, $status, $actor_user_id)
{
    $reminder_id = (int) $reminder_id;
    $actor_user_id = (int) $actor_user_id;
    if ($reminder_id <= 0 || $actor_user_id <= 0) {
        return false;
    }

    $status = peracrm_reminders_sanitize_status($status);
    if ($status === '') {
        return false;
    }

    if (peracrm_reminders_table_exists()) {
        return peracrm_reminders_update_status_table($reminder_id, $status);
    }

    return peracrm_reminders_update_status_fallback($reminder_id, $status);
}

function peracrm_reminders_list_for_client($client_id, $limit = 20, $offset = 0, $status = null)
{
    if (peracrm_reminders_table_exists()) {
        return peracrm_reminders_list_for_client_table($client_id, $limit, $offset, $status);
    }

    return peracrm_reminders_list_for_client_fallback($client_id, $limit, $offset, $status);
}

function peracrm_reminders_count_for_client($client_id, $status = null)
{
    if (peracrm_reminders_table_exists()) {
        return peracrm_reminders_count_for_client_table($client_id, $status);
    }

    return peracrm_reminders_count_for_client_fallback($client_id, $status);
}

function peracrm_reminders_count_open_by_client($client_id)
{
    $client_id = (int) $client_id;
    if ($client_id <= 0) {
        return 0;
    }

    static $cache = [];
    if (isset($cache[$client_id])) {
        return $cache[$client_id];
    }

    if (peracrm_reminders_table_exists()) {
        global $wpdb;

        $table = peracrm_table('crm_reminders');
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE client_id = %d AND status = %s",
            $client_id,
            'pending'
        );

        $count = (int) $wpdb->get_var($query);
        $cache[$client_id] = $count;
        return $count;
    }

    $reminders = peracrm_reminders_fallback_get($client_id);
    $count = 0;
    foreach ($reminders as $reminder) {
        if (isset($reminder['status']) && $reminder['status'] === 'pending') {
            $count++;
        }
    }

    $cache[$client_id] = $count;
    return $count;
}

function peracrm_reminders_count_overdue_by_client($client_id)
{
    $client_id = (int) $client_id;
    if ($client_id <= 0) {
        return 0;
    }

    static $cache = [];
    if (isset($cache[$client_id])) {
        return $cache[$client_id];
    }

    $now = current_time('timestamp');

    if (peracrm_reminders_table_exists()) {
        global $wpdb;

        $table = peracrm_table('crm_reminders');
        $now_mysql = wp_date('Y-m-d H:i:s', $now);
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE client_id = %d AND status = %s AND due_at < %s",
            $client_id,
            'pending',
            $now_mysql
        );

        $count = (int) $wpdb->get_var($query);
        $cache[$client_id] = $count;
        return $count;
    }

    $reminders = peracrm_reminders_fallback_get($client_id);
    $count = 0;
    foreach ($reminders as $reminder) {
        if (!isset($reminder['status']) || $reminder['status'] !== 'pending') {
            continue;
        }
        $due_at = isset($reminder['due_at']) ? $reminder['due_at'] : '';
        $due_ts = $due_at ? strtotime($due_at) : 0;
        if ($due_ts && $due_ts < $now) {
            $count++;
        }
    }

    $cache[$client_id] = $count;
    return $count;
}

function peracrm_reminders_count_open_by_client_ids($client_ids, $advisor_user_id = null)
{
    $client_ids = array_values(array_unique(array_filter(array_map('absint', (array) $client_ids))));
    if (empty($client_ids)) {
        return [];
    }

    $results = array_fill_keys($client_ids, 0);

    if (!peracrm_reminders_table_exists()) {
        return $results;
    }

    global $wpdb;
    $table = peracrm_table('crm_reminders');
    $placeholders = implode(',', array_fill(0, count($client_ids), '%d'));
    $conditions = ["client_id IN ({$placeholders})", 'status = %s'];
    $params = array_merge($client_ids, ['pending']);

    $advisor_user_id = (int) $advisor_user_id;
    if ($advisor_user_id > 0) {
        $conditions[] = 'advisor_user_id = %d';
        $params[] = $advisor_user_id;
    }

    $query = $wpdb->prepare(
        "SELECT client_id, COUNT(*) AS open_count
         FROM {$table}
         WHERE " . implode(' AND ', $conditions) . '
         GROUP BY client_id',
        $params
    );

    $rows = $wpdb->get_results($query, ARRAY_A);
    foreach ($rows as $row) {
        $client_id = (int) $row['client_id'];
        $results[$client_id] = isset($row['open_count']) ? (int) $row['open_count'] : 0;
    }

    return $results;
}

function peracrm_reminders_count_overdue_by_client_ids($client_ids, $advisor_user_id = null)
{
    $client_ids = array_values(array_unique(array_filter(array_map('absint', (array) $client_ids))));
    if (empty($client_ids)) {
        return [];
    }

    $results = array_fill_keys($client_ids, 0);

    if (!peracrm_reminders_table_exists()) {
        return $results;
    }

    global $wpdb;
    $table = peracrm_table('crm_reminders');
    $now_mysql = current_time('mysql');
    $placeholders = implode(',', array_fill(0, count($client_ids), '%d'));
    $conditions = ["client_id IN ({$placeholders})", 'status = %s', 'due_at < %s'];
    $params = array_merge($client_ids, ['pending', $now_mysql]);

    $advisor_user_id = (int) $advisor_user_id;
    if ($advisor_user_id > 0) {
        $conditions[] = 'advisor_user_id = %d';
        $params[] = $advisor_user_id;
    }

    $query = $wpdb->prepare(
        "SELECT client_id, COUNT(*) AS overdue_count
         FROM {$table}
         WHERE " . implode(' AND ', $conditions) . '
         GROUP BY client_id',
        $params
    );

    $rows = $wpdb->get_results($query, ARRAY_A);
    foreach ($rows as $row) {
        $client_id = (int) $row['client_id'];
        $results[$client_id] = isset($row['overdue_count']) ? (int) $row['overdue_count'] : 0;
    }

    return $results;
}

function peracrm_reminders_next_due_by_client_ids($client_ids, $advisor_user_id = null)
{
    $client_ids = array_values(array_unique(array_filter(array_map('absint', (array) $client_ids))));
    if (empty($client_ids)) {
        return [];
    }

    $results = array_fill_keys($client_ids, '');

    if (!peracrm_reminders_table_exists()) {
        return $results;
    }

    global $wpdb;
    $table = peracrm_table('crm_reminders');
    $placeholders = implode(',', array_fill(0, count($client_ids), '%d'));
    $conditions = ["client_id IN ({$placeholders})", 'status = %s'];
    $params = array_merge($client_ids, ['pending']);

    $advisor_user_id = (int) $advisor_user_id;
    if ($advisor_user_id > 0) {
        $conditions[] = 'advisor_user_id = %d';
        $params[] = $advisor_user_id;
    }

    $query = $wpdb->prepare(
        "SELECT client_id, MIN(due_at) AS next_due
         FROM {$table}
         WHERE " . implode(' AND ', $conditions) . '
         GROUP BY client_id',
        $params
    );

    $rows = $wpdb->get_results($query, ARRAY_A);
    foreach ($rows as $row) {
        $client_id = (int) $row['client_id'];
        $results[$client_id] = isset($row['next_due']) ? (string) $row['next_due'] : '';
    }

    return $results;
}

function peracrm_reminders_counts_by_client_ids($client_ids, $advisor_user_id = null)
{
    $client_ids = array_values(array_unique(array_filter(array_map('absint', (array) $client_ids))));
    if (empty($client_ids)) {
        return [
            'open_count' => [],
            'overdue_count' => [],
            'next_due' => [],
        ];
    }

    if (!peracrm_reminders_table_exists()) {
        return [
            'open_count' => [],
            'overdue_count' => [],
            'next_due' => [],
        ];
    }

    global $wpdb;
    $table = peracrm_table('crm_reminders');
    $placeholders = implode(',', array_fill(0, count($client_ids), '%d'));
    $now_mysql = current_time('mysql');
    $conditions = ["client_id IN ({$placeholders})"];
    $params = $client_ids;

    $advisor_user_id = (int) $advisor_user_id;
    if ($advisor_user_id > 0) {
        $conditions[] = 'advisor_user_id = %d';
        $params[] = $advisor_user_id;
    }

    $params = array_merge(['pending', 'pending', $now_mysql, 'pending'], $params);

    $query = $wpdb->prepare(
        "SELECT client_id,
                SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) AS open_count,
                SUM(CASE WHEN status = %s AND due_at < %s THEN 1 ELSE 0 END) AS overdue_count,
                MIN(CASE WHEN status = %s THEN due_at ELSE NULL END) AS next_due
         FROM {$table}
         WHERE " . implode(' AND ', $conditions) . '
         GROUP BY client_id',
        $params
    );

    $rows = $wpdb->get_results($query, ARRAY_A);
    $open_counts = [];
    $overdue_counts = [];
    $next_due = [];

    foreach ($rows as $row) {
        $client_id = (int) $row['client_id'];
        $open_counts[$client_id] = isset($row['open_count']) ? (int) $row['open_count'] : 0;
        $overdue_counts[$client_id] = isset($row['overdue_count']) ? (int) $row['overdue_count'] : 0;
        $next_due[$client_id] = isset($row['next_due']) ? (string) $row['next_due'] : '';
    }

    return [
        'open_count' => $open_counts,
        'overdue_count' => $overdue_counts,
        'next_due' => $next_due,
    ];
}

function peracrm_reminders_list_for_advisor($advisor_user_id, $limit = 50, $offset = 0, $status = null, $range = null, $order = 'asc')
{
    if (peracrm_reminders_table_exists()) {
        return peracrm_reminders_list_for_advisor_table($advisor_user_id, $limit, $offset, $status, $range, $order);
    }

    return peracrm_reminders_list_for_advisor_fallback($advisor_user_id, $limit, $offset, $status, $range, $order);
}

function peracrm_reminders_count_for_advisor($advisor_user_id, $status = null, $range = null)
{
    if (peracrm_reminders_table_exists()) {
        return peracrm_reminders_count_for_advisor_table($advisor_user_id, $status, $range);
    }

    return peracrm_reminders_count_for_advisor_fallback($advisor_user_id, $status, $range);
}

function peracrm_reminders_get($reminder_id)
{
    $reminder_id = (int) $reminder_id;
    if ($reminder_id <= 0) {
        return null;
    }

    if (peracrm_reminders_table_exists()) {
        return peracrm_reminders_get_table($reminder_id);
    }

    return peracrm_reminders_get_fallback($reminder_id);
}

function peracrm_reminders_allowed_statuses()
{
    return ['pending', 'done', 'dismissed'];
}

function peracrm_reminders_sanitize_status($status)
{
    $status = sanitize_key($status);
    if (!in_array($status, peracrm_reminders_allowed_statuses(), true)) {
        return '';
    }

    return $status;
}

function peracrm_reminders_sanitize_due_at($due_at)
{
    $due_at = sanitize_text_field($due_at);
    if ($due_at === '') {
        return '';
    }

    return $due_at;
}

function peracrm_reminders_sanitize_note($note, $max_length)
{
    $note = sanitize_textarea_field($note);
    $note = trim($note);
    if ($note === '') {
        return '';
    }

    if (strlen($note) > $max_length) {
        $note = substr($note, 0, $max_length);
    }

    return $note;
}

function peracrm_reminders_insert_table($client_id, $advisor_user_id, $due_at, $note)
{
    global $wpdb;

    $table = peracrm_table('crm_reminders');
    $created_at = peracrm_now_mysql();

    $query = $wpdb->prepare(
        "INSERT INTO {$table} (client_id, advisor_user_id, due_at, status, note, created_at, updated_at)
         VALUES (%d, %d, %s, %s, %s, %s, %s)",
        $client_id,
        $advisor_user_id,
        $due_at,
        'pending',
        $note !== '' ? $note : null,
        $created_at,
        null
    );

    $result = $wpdb->query($query);
    if (false === $result) {
        return 0;
    }

    return (int) $wpdb->insert_id;
}

function peracrm_reminders_update_status_table($reminder_id, $status)
{
    global $wpdb;

    $table = peracrm_table('crm_reminders');
    $updated_at = peracrm_now_mysql();

    $result = $wpdb->update(
        $table,
        [
            'status' => $status,
            'updated_at' => $updated_at,
        ],
        [
            'id' => $reminder_id,
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

function peracrm_reminders_list_for_client_table($client_id, $limit, $offset, $status)
{
    global $wpdb;

    $client_id = (int) $client_id;
    $limit = max(1, (int) $limit);
    $offset = max(0, (int) $offset);

    $table = peracrm_table('crm_reminders');

    $conditions = ['client_id = %d'];
    $params = [$client_id];

    $status = $status !== null ? peracrm_reminders_sanitize_status($status) : '';
    if ($status !== '') {
        $conditions[] = 'status = %s';
        $params[] = $status;
    }

    $params[] = $limit;
    $params[] = $offset;

    $where = implode(' AND ', $conditions);
    $query = $wpdb->prepare(
        "SELECT id, client_id, advisor_user_id, due_at, status, note, created_at, updated_at
         FROM {$table}
         WHERE {$where}
         ORDER BY due_at ASC, created_at DESC
         LIMIT %d OFFSET %d",
        $params
    );

    return $wpdb->get_results($query, ARRAY_A);
}

function peracrm_reminders_count_for_client_table($client_id, $status)
{
    global $wpdb;

    $client_id = (int) $client_id;
    $table = peracrm_table('crm_reminders');

    $conditions = ['client_id = %d'];
    $params = [$client_id];

    $status = $status !== null ? peracrm_reminders_sanitize_status($status) : '';
    if ($status !== '') {
        $conditions[] = 'status = %s';
        $params[] = $status;
    }

    $where = implode(' AND ', $conditions);
    $query = $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE {$where}",
        $params
    );

    return (int) $wpdb->get_var($query);
}

function peracrm_reminders_list_for_advisor_table($advisor_user_id, $limit, $offset, $status, $range, $order)
{
    global $wpdb;

    $advisor_user_id = (int) $advisor_user_id;
    $limit = max(1, (int) $limit);
    $offset = max(0, (int) $offset);

    $order = strtolower($order);

    $table = peracrm_table('crm_reminders');

    $conditions = ['advisor_user_id = %d'];
    $params = [$advisor_user_id];

    $status = $status !== null ? peracrm_reminders_sanitize_status($status) : '';
    if ($status !== '') {
        $conditions[] = 'status = %s';
        $params[] = $status;
    }

    $range_clause = peracrm_reminders_range_clause($range, $params);
    if ($range_clause !== '') {
        $conditions[] = $range_clause;
    }

    $params[] = $limit;
    $params[] = $offset;

    $where = implode(' AND ', $conditions);

    if ($order === 'status_desc' || $order === 'status_asc') {
        $direction = $order === 'status_desc' ? 'DESC' : 'ASC';
        $order_by = "status {$direction}, due_at ASC, id ASC";
    } else {
        $direction = $order === 'desc' ? 'DESC' : 'ASC';
        $order_by = "due_at {$direction}, id {$direction}";
    }

    $query = $wpdb->prepare(
        "SELECT id, client_id, advisor_user_id, due_at, status, note, created_at, updated_at
         FROM {$table}
         WHERE {$where}
         ORDER BY {$order_by}
         LIMIT %d OFFSET %d",
        $params
    );

    return $wpdb->get_results($query, ARRAY_A);
}

function peracrm_reminders_count_for_advisor_table($advisor_user_id, $status, $range)
{
    global $wpdb;

    $advisor_user_id = (int) $advisor_user_id;
    $table = peracrm_table('crm_reminders');

    $conditions = ['advisor_user_id = %d'];
    $params = [$advisor_user_id];

    $status = $status !== null ? peracrm_reminders_sanitize_status($status) : '';
    if ($status !== '') {
        $conditions[] = 'status = %s';
        $params[] = $status;
    }

    $range_clause = peracrm_reminders_range_clause($range, $params);
    if ($range_clause !== '') {
        $conditions[] = $range_clause;
    }

    $where = implode(' AND ', $conditions);
    $query = $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE {$where}",
        $params
    );

    return (int) $wpdb->get_var($query);
}

function peracrm_reminders_get_table($reminder_id)
{
    global $wpdb;

    $table = peracrm_table('crm_reminders');
    $query = $wpdb->prepare(
        "SELECT id, client_id, advisor_user_id, due_at, status, note, created_at, updated_at
         FROM {$table} WHERE id = %d",
        $reminder_id
    );

    $row = $wpdb->get_row($query, ARRAY_A);
    if (!$row) {
        return null;
    }

    return $row;
}

function peracrm_reminders_range_clause($range, &$params)
{
    $range = $range !== null ? sanitize_key($range) : '';
    if ($range === '' || $range === 'all') {
        return '';
    }

    $timezone = wp_timezone();
    $now = current_time('timestamp');

    if ($range === 'overdue') {
        $params[] = wp_date('Y-m-d H:i:s', $now, $timezone);
        return 'due_at < %s';
    }

    $days = 0;
    if ($range === 'next_7') {
        $days = 7;
    } elseif ($range === 'next_30') {
        $days = 30;
    }

    if ($days > 0) {
        $start = wp_date('Y-m-d H:i:s', $now, $timezone);
        $end = wp_date('Y-m-d H:i:s', strtotime('+' . $days . ' days', $now), $timezone);
        $params[] = $start;
        $params[] = $end;
        return 'due_at >= %s AND due_at <= %s';
    }

    return '';
}

function peracrm_reminders_insert_fallback($client_id, $advisor_user_id, $due_at, $note)
{
    $note = peracrm_reminders_sanitize_note($note, 2000);

    $reminders = peracrm_reminders_fallback_get($client_id);
    $next_id = peracrm_reminders_fallback_next_id($reminders);

    $reminders[] = [
        'id' => $next_id,
        'client_id' => $client_id,
        'advisor_user_id' => $advisor_user_id,
        'due_at' => $due_at,
        'status' => 'pending',
        'note' => $note,
        'created_at' => peracrm_now_mysql(),
        'updated_at' => null,
    ];

    $reminders = peracrm_reminders_fallback_trim($reminders);

    $updated = update_post_meta($client_id, '_peracrm_reminders_fallback', $reminders);
    if (!$updated) {
        return 0;
    }

    return $next_id;
}

function peracrm_reminders_update_status_fallback($reminder_id, $status)
{
    $clients = peracrm_reminders_fallback_client_ids();
    if (empty($clients)) {
        return false;
    }

    foreach ($clients as $client_id) {
        $reminders = peracrm_reminders_fallback_get($client_id);
        $updated = false;
        foreach ($reminders as $index => $reminder) {
            if ((int) $reminder['id'] === $reminder_id) {
                $reminders[$index]['status'] = $status;
                $reminders[$index]['updated_at'] = peracrm_now_mysql();
                $updated = true;
                break;
            }
        }

        if ($updated) {
            return (bool) update_post_meta($client_id, '_peracrm_reminders_fallback', $reminders);
        }
    }

    return false;
}

function peracrm_reminders_list_for_client_fallback($client_id, $limit, $offset, $status)
{
    $reminders = peracrm_reminders_fallback_get($client_id);
    $limit = max(1, (int) $limit);
    $offset = max(0, (int) $offset);

    if ($status !== null) {
        $status = peracrm_reminders_sanitize_status($status);
        if ($status !== '') {
            $reminders = array_values(array_filter($reminders, function ($reminder) use ($status) {
                return isset($reminder['status']) && $reminder['status'] === $status;
            }));
        }
    }

    usort($reminders, function ($a, $b) {
        return strcmp($a['due_at'], $b['due_at']);
    });

    if ($offset >= count($reminders)) {
        return [];
    }

    $limit = min($limit, 50);

    return array_slice($reminders, $offset, $limit);
}

function peracrm_reminders_count_for_client_fallback($client_id, $status)
{
    $reminders = peracrm_reminders_fallback_get($client_id);

    if ($status !== null) {
        $status = peracrm_reminders_sanitize_status($status);
        if ($status !== '') {
            $reminders = array_filter($reminders, function ($reminder) use ($status) {
                return isset($reminder['status']) && $reminder['status'] === $status;
            });
        }
    }

    return count($reminders);
}

function peracrm_reminders_list_for_advisor_fallback($advisor_user_id, $limit, $offset, $status, $range, $order)
{
    $advisor_user_id = (int) $advisor_user_id;
    $limit = max(1, (int) $limit);
    $offset = max(0, (int) $offset);

    $reminders = peracrm_reminders_fallback_collect_for_advisor($advisor_user_id, $status, $range);

    $order = strtolower($order);
    if ($order === 'status_desc' || $order === 'status_asc') {
        $direction = $order === 'status_desc' ? 'desc' : 'asc';
        usort($reminders, function ($a, $b) use ($direction) {
            $status_compare = strcmp($a['status'], $b['status']);
            if ($status_compare !== 0) {
                return $direction === 'desc' ? -$status_compare : $status_compare;
            }
            $date_compare = strcmp($a['due_at'], $b['due_at']);
            return $direction === 'desc' ? -$date_compare : $date_compare;
        });
    } else {
        $direction = $order === 'desc' ? 'desc' : 'asc';
        usort($reminders, function ($a, $b) use ($direction) {
            $result = strcmp($a['due_at'], $b['due_at']);
            return $direction === 'desc' ? -$result : $result;
        });
    }

    if ($offset >= count($reminders)) {
        return [];
    }

    $limit = min($limit, 50);

    return array_slice($reminders, $offset, $limit);
}

function peracrm_reminders_count_for_advisor_fallback($advisor_user_id, $status, $range)
{
    $reminders = peracrm_reminders_fallback_collect_for_advisor((int) $advisor_user_id, $status, $range);
    return count($reminders);
}

function peracrm_reminders_get_fallback($reminder_id)
{
    $clients = peracrm_reminders_fallback_client_ids();
    if (empty($clients)) {
        return null;
    }

    foreach ($clients as $client_id) {
        $reminders = peracrm_reminders_fallback_get($client_id);
        foreach ($reminders as $reminder) {
            if ((int) $reminder['id'] === $reminder_id) {
                return $reminder;
            }
        }
    }

    return null;
}

function peracrm_reminders_fallback_collect_for_advisor($advisor_user_id, $status, $range)
{
    $clients = peracrm_reminders_fallback_client_ids();
    if (empty($clients)) {
        return [];
    }

    $status = $status !== null ? peracrm_reminders_sanitize_status($status) : '';
    $range = $range !== null ? sanitize_key($range) : '';

    $now = current_time('timestamp');
    $timezone = wp_timezone();
    $range_start = wp_date('Y-m-d H:i:s', $now, $timezone);
    $range_end = null;

    if ($range === 'next_7') {
        $range_end = wp_date('Y-m-d H:i:s', strtotime('+7 days', $now), $timezone);
    } elseif ($range === 'next_30') {
        $range_end = wp_date('Y-m-d H:i:s', strtotime('+30 days', $now), $timezone);
    }

    $filtered = [];
    foreach ($clients as $client_id) {
        $reminders = peracrm_reminders_fallback_get($client_id);
        foreach ($reminders as $reminder) {
            if ((int) $reminder['advisor_user_id'] !== $advisor_user_id) {
                continue;
            }

            if ($status !== '' && $reminder['status'] !== $status) {
                continue;
            }

            if ($range === 'overdue' && $reminder['due_at'] >= $range_start) {
                continue;
            }

            if ($range_end && ($reminder['due_at'] < $range_start || $reminder['due_at'] > $range_end)) {
                continue;
            }

            $filtered[] = $reminder;
        }
    }

    return $filtered;
}

function peracrm_reminders_fallback_client_ids()
{
    $query = new WP_Query([
        'post_type' => 'crm_client',
        'post_status' => 'any',
        'posts_per_page' => 200,
        'fields' => 'ids',
        'meta_key' => '_peracrm_reminders_fallback',
        'no_found_rows' => true,
    ]);

    if (!$query->have_posts()) {
        return [];
    }

    return array_map('intval', $query->posts);
}

function peracrm_reminders_fallback_get($client_id)
{
    $client_id = (int) $client_id;
    if ($client_id <= 0) {
        return [];
    }

    $reminders = get_post_meta($client_id, '_peracrm_reminders_fallback', true);
    if (!is_array($reminders)) {
        return [];
    }

    $normalized = [];
    foreach ($reminders as $reminder) {
        if (!is_array($reminder) || empty($reminder['due_at'])) {
            continue;
        }

        $status = isset($reminder['status']) ? peracrm_reminders_sanitize_status($reminder['status']) : '';
        if ($status === '') {
            $status = 'pending';
        }

        $normalized[] = [
            'id' => isset($reminder['id']) ? (int) $reminder['id'] : 0,
            'client_id' => $client_id,
            'advisor_user_id' => isset($reminder['advisor_user_id']) ? (int) $reminder['advisor_user_id'] : 0,
            'due_at' => sanitize_text_field($reminder['due_at']),
            'status' => $status,
            'note' => isset($reminder['note']) ? peracrm_reminders_sanitize_note($reminder['note'], 2000) : '',
            'created_at' => isset($reminder['created_at']) ? sanitize_text_field($reminder['created_at']) : '',
            'updated_at' => isset($reminder['updated_at']) ? sanitize_text_field($reminder['updated_at']) : null,
        ];
    }

    return $normalized;
}

function peracrm_reminders_fallback_next_id($reminders)
{
    $max_id = 0;
    foreach ($reminders as $reminder) {
        if (isset($reminder['id'])) {
            $max_id = max($max_id, (int) $reminder['id']);
        }
    }

    return $max_id + 1;
}

function peracrm_reminders_fallback_trim($reminders)
{
    usort($reminders, function ($a, $b) {
        return strcmp($b['created_at'], $a['created_at']);
    });

    if (count($reminders) > 50) {
        $reminders = array_slice($reminders, 0, 50);
    }

    return array_values($reminders);
}

function peracrm_reminders_create($client_id, $advisor_user_id, $due_at_mysql, $note = '')
{
    return peracrm_reminder_add($client_id, $advisor_user_id, $due_at_mysql, $note);
}

function peracrm_reminders_due_for_advisor($advisor_user_id, $until_mysql, $limit = 100)
{
    $advisor_user_id = (int) $advisor_user_id;
    $limit = max(1, (int) $limit);
    $until_mysql = sanitize_text_field($until_mysql);
    if ($advisor_user_id <= 0 || $until_mysql === '') {
        return [];
    }

    if (peracrm_reminders_table_exists()) {
        global $wpdb;
        $table = peracrm_table('crm_reminders');
        $query = $wpdb->prepare(
            "SELECT id, client_id, advisor_user_id, due_at, status, note, created_at, updated_at
             FROM {$table}
             WHERE advisor_user_id = %d AND status = %s AND due_at <= %s
             ORDER BY due_at ASC
             LIMIT %d",
            $advisor_user_id,
            'pending',
            $until_mysql,
            $limit
        );

        return $wpdb->get_results($query, ARRAY_A);
    }

    $reminders = peracrm_reminders_list_for_advisor($advisor_user_id, $limit, 0, 'pending', 'all', 'asc');

    return array_values(array_filter($reminders, function ($reminder) use ($until_mysql) {
        return isset($reminder['due_at']) && $reminder['due_at'] <= $until_mysql;
    }));
}

function peracrm_reminders_mark_done($id)
{
    return peracrm_reminder_update_status($id, 'done', get_current_user_id());
}
