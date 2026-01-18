<?php

if (!defined('ABSPATH')) {
    exit;
}

function &peracrm_client_health_cache_state()
{
    static $cache = [
        'health' => [],
        'activity' => [],
        'open' => [],
        'overdue' => [],
    ];

    return $cache;
}

function peracrm_client_health_prime_cache($client_ids)
{
    $client_ids = array_values(array_unique(array_filter(array_map('absint', (array) $client_ids))));
    if (empty($client_ids)) {
        return;
    }

    $cache = &peracrm_client_health_cache_state();

    $has_activity_table = function_exists('peracrm_activity_table_exists') && peracrm_activity_table_exists();
    if ($has_activity_table) {
        $cached_ids = array_map('intval', array_keys($cache['activity']));
        $uncached = array_values(array_diff($client_ids, $cached_ids));
        if (!empty($uncached)) {
            global $wpdb;
            $table = peracrm_table('crm_activity');
            $placeholders = implode(',', array_fill(0, count($uncached), '%d'));
            $query = $wpdb->prepare(
                "SELECT client_id, MAX(created_at) AS last_activity_at
                 FROM {$table}
                 WHERE client_id IN ({$placeholders})
                 GROUP BY client_id",
                $uncached
            );
            $rows = $wpdb->get_results($query, ARRAY_A);
            $found = [];
            foreach ($rows as $row) {
                $client_id = (int) $row['client_id'];
                $timestamp = $row['last_activity_at'] ? strtotime($row['last_activity_at']) : 0;
                $cache['activity'][$client_id] = $timestamp ?: 0;
                $found[$client_id] = true;
            }
            foreach ($uncached as $client_id) {
                if (!isset($found[$client_id])) {
                    $cache['activity'][$client_id] = 0;
                }
            }
        }
    }

    $has_reminders_table = function_exists('peracrm_reminders_table_exists') && peracrm_reminders_table_exists();
    $cached_open = array_map('intval', array_keys($cache['open']));
    $cached_overdue = array_map('intval', array_keys($cache['overdue']));
    $uncached = array_values(array_diff($client_ids, array_unique(array_merge($cached_open, $cached_overdue))));
    if (empty($uncached)) {
        return;
    }

    if ($has_reminders_table) {
        global $wpdb;
        $table = peracrm_table('crm_reminders');
        $placeholders = implode(',', array_fill(0, count($uncached), '%d'));
        $now_mysql = current_time('mysql');
        $params = array_merge(['pending', 'pending', $now_mysql], $uncached);
        $query = $wpdb->prepare(
            "SELECT client_id,
                    SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) AS open_count,
                    SUM(CASE WHEN status = %s AND due_at < %s THEN 1 ELSE 0 END) AS overdue_count
             FROM {$table}
             WHERE client_id IN ({$placeholders})
             GROUP BY client_id",
            $params
        );
        $rows = $wpdb->get_results($query, ARRAY_A);
        $found = [];
        foreach ($rows as $row) {
            $client_id = (int) $row['client_id'];
            $cache['open'][$client_id] = isset($row['open_count']) ? (int) $row['open_count'] : 0;
            $cache['overdue'][$client_id] = isset($row['overdue_count']) ? (int) $row['overdue_count'] : 0;
            $found[$client_id] = true;
        }
        foreach ($uncached as $client_id) {
            if (!isset($found[$client_id])) {
                $cache['open'][$client_id] = 0;
                $cache['overdue'][$client_id] = 0;
            }
        }
        return;
    }

    update_meta_cache('post', $uncached);
    foreach ($uncached as $client_id) {
        if (function_exists('peracrm_reminders_count_open_by_client')) {
            $cache['open'][$client_id] = peracrm_reminders_count_open_by_client($client_id);
        } else {
            $cache['open'][$client_id] = 0;
        }
        if (function_exists('peracrm_reminders_count_overdue_by_client')) {
            $cache['overdue'][$client_id] = peracrm_reminders_count_overdue_by_client($client_id);
        } else {
            $cache['overdue'][$client_id] = 0;
        }
    }
}

function peracrm_client_health_get($client_id)
{
    $client_id = (int) $client_id;
    if ($client_id <= 0) {
        return [
            'key' => 'none',
            'label' => 'None',
            'severity' => 'neutral',
            'last_activity_ts' => 0,
            'open_reminders' => 0,
            'overdue_reminders' => 0,
            'explain' => 'No client selected.',
        ];
    }

    $cache = &peracrm_client_health_cache_state();
    if (isset($cache['health'][$client_id])) {
        return $cache['health'][$client_id];
    }

    if (!isset($cache['activity'][$client_id])) {
        $activity_ts = 0;
        if (function_exists('peracrm_activity_last')) {
            $activity = peracrm_activity_last($client_id);
            $created_at = $activity && isset($activity['created_at']) ? $activity['created_at'] : '';
            $activity_ts = $created_at ? strtotime($created_at) : 0;
        }
        $cache['activity'][$client_id] = $activity_ts ?: 0;
    }

    if (!isset($cache['open'][$client_id])) {
        $cache['open'][$client_id] = function_exists('peracrm_reminders_count_open_by_client')
            ? peracrm_reminders_count_open_by_client($client_id)
            : 0;
    }

    if (!isset($cache['overdue'][$client_id])) {
        $cache['overdue'][$client_id] = function_exists('peracrm_reminders_count_overdue_by_client')
            ? peracrm_reminders_count_overdue_by_client($client_id)
            : 0;
    }

    $last_activity_ts = (int) $cache['activity'][$client_id];
    $open_reminders = (int) $cache['open'][$client_id];
    $overdue_reminders = (int) $cache['overdue'][$client_id];

    $now = current_time('timestamp');
    $key = 'none';
    $label = 'None';
    $severity = 'neutral';
    $explain = 'No activity or reminders recorded.';

    if ($overdue_reminders > 0) {
        $key = 'at_risk';
        $label = 'At risk';
        $severity = 'bad';
        $explain = 'Overdue reminders need attention.';
    } elseif ($last_activity_ts && $last_activity_ts >= ($now - DAY_IN_SECONDS * 7) && $open_reminders > 0) {
        $key = 'hot';
        $label = 'Hot';
        $severity = 'good';
        $explain = 'Recent activity with open reminders.';
    } elseif ($last_activity_ts && $last_activity_ts >= ($now - DAY_IN_SECONDS * 14)) {
        $key = 'warm';
        $label = 'Warm';
        $severity = 'neutral';
        $explain = 'Recent activity in the last 14 days.';
    } elseif (($last_activity_ts && $last_activity_ts < ($now - DAY_IN_SECONDS * 30)) || (!$last_activity_ts && $open_reminders > 0 && $overdue_reminders === 0)) {
        $key = 'cold';
        $label = 'Cold';
        $severity = 'warn';
        $explain = 'No recent activity recorded.';
    } elseif (!$last_activity_ts && $open_reminders === 0 && $overdue_reminders === 0) {
        $key = 'none';
        $label = 'None';
        $severity = 'neutral';
        $explain = 'No activity or reminders recorded.';
    }

    $cache['health'][$client_id] = [
        'key' => $key,
        'label' => $label,
        'severity' => $severity,
        'last_activity_ts' => $last_activity_ts,
        'open_reminders' => $open_reminders,
        'overdue_reminders' => $overdue_reminders,
        'explain' => $explain,
    ];

    return $cache['health'][$client_id];
}

function peracrm_client_health_badge_html($health)
{
    $label = isset($health['label']) ? $health['label'] : 'None';
    $severity = isset($health['severity']) ? $health['severity'] : 'neutral';

    return sprintf(
        '<span class="peracrm-health-badge peracrm-health-badge--%1$s">%2$s</span>',
        esc_attr($severity),
        esc_html($label)
    );
}
