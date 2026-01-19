<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_admin_is_crm_client_edit_screen($post_id = 0)
{
    if (!is_admin()) {
        return false;
    }

    global $pagenow;
    if ($pagenow !== 'post.php') {
        return false;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'crm_client') {
        return false;
    }

    $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';
    if ($action !== 'edit') {
        return false;
    }

    if ($post_id && get_post_type($post_id) !== 'crm_client') {
        return false;
    }

    return true;
}

function peracrm_render_timeline_metabox($post)
{
    if (!$post || empty($post->ID) || ($post->post_type ?? '') !== 'crm_client') {
        return;
    }

    $post_id = (int) $post->ID;
    $post_status = isset($post->post_status) ? (string) $post->post_status : '';
    $should_log = defined('WP_DEBUG') && WP_DEBUG;
    if ($should_log) {
        error_log(sprintf('[peracrm] metabox timeline start client=%d', $post_id));
    }

    if ($post_status === 'auto-draft') {
        echo '<p>' . esc_html('Save draft to enable CRM panels.') . '</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox timeline end client=%d', $post_id));
        }
        return;
    }

    if (!peracrm_admin_is_crm_client_edit_screen($post_id)) {
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox timeline end client=%d', $post_id));
        }
        return;
    }

    if (!current_user_can('edit_post', $post_id) || !current_user_can('manage_options')) {
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox timeline end client=%d', $post_id));
        }
        return;
    }

    $has_notes_table = function_exists('peracrm_notes_table_exists') && peracrm_notes_table_exists();
    $has_reminders_table = function_exists('peracrm_reminders_table_exists') && peracrm_reminders_table_exists();
    $has_activity_table = function_exists('peracrm_activity_table_exists') && peracrm_activity_table_exists();
    if (!$has_notes_table && !$has_reminders_table && !$has_activity_table) {
        echo '<p class="peracrm-empty">Unavailable (missing table).</p>';
        if ($should_log) {
            error_log(sprintf('[peracrm] metabox timeline end client=%d', $post_id));
        }
        return;
    }

    $filter = peracrm_timeline_get_filter();
    $items = peracrm_timeline_get_items($post_id, 50, $filter);
    $missing_sources = peracrm_timeline_missing_sources($filter);

    echo '<div class="peracrm-metabox peracrm-timeline">';
    echo '<div class="peracrm-timeline-filters">';
    echo peracrm_timeline_filter_links($post_id, $filter);
    echo '</div>';

    if (!empty($missing_sources)) {
        echo '<p class="description">' . esc_html(implode(' ', $missing_sources)) . '</p>';
    }

    if (empty($items)) {
        echo '<p class="peracrm-empty">No timeline items yet.</p>';
    } else {
        echo '<ul class="peracrm-list peracrm-timeline-list">';
        foreach ($items as $item) {
            $type_label = peracrm_timeline_type_label($item['type'] ?? '');
            $title = isset($item['title']) ? (string) $item['title'] : '';
            $detail = isset($item['detail']) ? (string) $item['detail'] : '';
            $ts = isset($item['ts']) ? (int) $item['ts'] : 0;
            $time = peracrm_timeline_time_display($ts);
            $meta_line = peracrm_timeline_meta_line($item['meta'] ?? []);

            echo '<li class="peracrm-timeline-item">';
            echo '<div class="peracrm-timeline-header">';
            echo '<span class="peracrm-timeline-badge">' . esc_html($type_label) . '</span>';
            if ($time['relative']) {
                echo ' <span class="peracrm-timeline-time" title="' . esc_attr($time['title']) . '">' . esc_html($time['relative']) . '</span>';
            }
            echo '</div>';
            if ($title !== '') {
                echo '<div class="peracrm-timeline-title"><strong>' . esc_html($title) . '</strong></div>';
            }
            if ($detail !== '') {
                echo '<div class="peracrm-timeline-detail">' . esc_html($detail) . '</div>';
            }
            if ($meta_line !== '') {
                echo '<div class="peracrm-timeline-meta">' . esc_html($meta_line) . '</div>';
            }
            echo '</li>';
        }
        echo '</ul>';
    }

    echo '</div>';

    if ($should_log) {
        error_log(sprintf('[peracrm] metabox timeline end client=%d', $post_id));
    }
}

function peracrm_timeline_get_items($client_id, $limit = 50, $type = 'all')
{
    $client_id = (int) $client_id;
    $limit = max(1, (int) $limit);
    $type = peracrm_timeline_sanitize_type($type);

    if ($client_id <= 0) {
        return [];
    }

    $notes = [];
    $reminders = [];
    $activity = [];

    if ($type === 'all' || $type === 'notes') {
        $notes = function_exists('peracrm_notes_list')
            ? peracrm_notes_list($client_id, $limit, 0)
            : [];
    }

    if (($type === 'all' || $type === 'reminders')
        && function_exists('peracrm_reminders_table_exists')
        && peracrm_reminders_table_exists()
        && function_exists('peracrm_reminders_list_for_client')) {
        $reminders = peracrm_reminders_list_for_client($client_id, $limit, 0, null);
    }

    if (($type === 'all' || $type === 'activity')
        && function_exists('peracrm_activity_table_exists')
        && peracrm_activity_table_exists()
        && function_exists('peracrm_activity_list')) {
        $activity = peracrm_activity_list($client_id, $limit, 0, null);
    }

    $user_ids = peracrm_timeline_collect_user_ids($notes, $reminders, $activity);
    $user_map = peracrm_timeline_get_user_map($user_ids);

    $items = [];

    foreach ($notes as $note) {
        $item = peracrm_timeline_normalize_note($note, $user_map);
        if ($item) {
            $items[] = $item;
        }
    }

    foreach ($reminders as $reminder) {
        $item = peracrm_timeline_normalize_reminder($reminder, $user_map);
        if ($item) {
            $items[] = $item;
        }
    }

    foreach ($activity as $event) {
        $item = peracrm_timeline_normalize_activity($event, $user_map);
        if ($item) {
            $items[] = $item;
        }
    }

    usort($items, function ($a, $b) {
        return ($b['ts'] ?? 0) <=> ($a['ts'] ?? 0);
    });

    return array_slice($items, 0, $limit);
}

function peracrm_timeline_sanitize_type($type)
{
    $type = sanitize_key((string) $type);
    $allowed = ['all', 'activity', 'notes', 'reminders'];

    return in_array($type, $allowed, true) ? $type : 'all';
}

function peracrm_timeline_get_filter()
{
    $filter = isset($_GET['peracrm_timeline']) ? sanitize_key(wp_unslash($_GET['peracrm_timeline'])) : 'all';

    return peracrm_timeline_sanitize_type($filter);
}

function peracrm_timeline_filter_links($post_id, $current)
{
    $filters = [
        'all' => 'All',
        'activity' => 'Activity',
        'notes' => 'Notes',
        'reminders' => 'Reminders',
    ];

    $base_url = add_query_arg(
        [
            'post' => (int) $post_id,
            'action' => 'edit',
        ],
        admin_url('post.php')
    );

    $links = [];
    foreach ($filters as $key => $label) {
        $url = add_query_arg('peracrm_timeline', $key, $base_url);
        if ($key === $current) {
            $links[] = '<strong>' . esc_html($label) . '</strong>';
        } else {
            $links[] = '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        }
    }

    return implode(' | ', $links);
}

function peracrm_timeline_missing_sources($type)
{
    $messages = [];

    if (($type === 'all' || $type === 'activity')
        && (!function_exists('peracrm_activity_table_exists') || !peracrm_activity_table_exists())) {
        $messages[] = 'Activity table not available.';
    }

    if (($type === 'all' || $type === 'reminders')
        && (!function_exists('peracrm_reminders_table_exists') || !peracrm_reminders_table_exists())) {
        $messages[] = 'Reminders table not available.';
    }

    return $messages;
}

function peracrm_timeline_collect_user_ids(array $notes, array $reminders, array $activity)
{
    $user_ids = [];

    foreach ($notes as $note) {
        if (!empty($note['advisor_user_id'])) {
            $user_ids[] = (int) $note['advisor_user_id'];
        }
    }

    foreach ($reminders as $reminder) {
        if (!empty($reminder['advisor_user_id'])) {
            $user_ids[] = (int) $reminder['advisor_user_id'];
        }
    }

    foreach ($activity as $event) {
        if (empty($event['event_payload'])) {
            continue;
        }
        $payload = peracrm_json_decode($event['event_payload']);
        if (!is_array($payload)) {
            continue;
        }
        $actor_id = 0;
        if (!empty($payload['actor_user_id'])) {
            $actor_id = (int) $payload['actor_user_id'];
        } elseif (!empty($payload['advisor_user_id'])) {
            $actor_id = (int) $payload['advisor_user_id'];
        }
        if ($actor_id > 0) {
            $user_ids[] = $actor_id;
        }
    }

    $user_ids = array_values(array_unique(array_filter($user_ids)));

    return $user_ids;
}

function peracrm_timeline_get_user_map(array $user_ids)
{
    if (empty($user_ids)) {
        return [];
    }

    $users = get_users([
        'include' => $user_ids,
        'fields' => ['ID', 'display_name'],
    ]);

    $map = [];
    foreach ($users as $user) {
        $map[(int) $user->ID] = $user->display_name;
    }

    return $map;
}

function peracrm_timeline_normalize_note(array $note, array $user_map)
{
    $created_at = isset($note['created_at']) ? (string) $note['created_at'] : '';
    $timestamp = $created_at ? strtotime($created_at) : 0;
    if ($timestamp <= 0) {
        return null;
    }

    $advisor_id = isset($note['advisor_user_id']) ? (int) $note['advisor_user_id'] : 0;
    $advisor_name = $advisor_id && isset($user_map[$advisor_id]) ? $user_map[$advisor_id] : '';

    $detail = isset($note['note_body']) ? peracrm_timeline_excerpt($note['note_body']) : '';

    return [
        'type' => 'note',
        'ts' => $timestamp,
        'title' => 'Advisor note',
        'detail' => $detail,
        'meta' => array_filter([
            'by' => $advisor_name,
        ]),
        'icon' => 'note',
    ];
}

function peracrm_timeline_normalize_reminder(array $reminder, array $user_map)
{
    $due_at = isset($reminder['due_at']) ? (string) $reminder['due_at'] : '';
    $created_at = isset($reminder['created_at']) ? (string) $reminder['created_at'] : '';
    $timestamp = $due_at ? strtotime($due_at) : 0;
    if ($timestamp <= 0 && $created_at) {
        $timestamp = strtotime($created_at);
    }
    if ($timestamp <= 0) {
        return null;
    }

    $status = isset($reminder['status']) ? peracrm_timeline_status_label($reminder['status']) : '';
    $advisor_id = isset($reminder['advisor_user_id']) ? (int) $reminder['advisor_user_id'] : 0;
    $advisor_name = $advisor_id && isset($user_map[$advisor_id]) ? $user_map[$advisor_id] : '';
    $detail = isset($reminder['note']) ? peracrm_timeline_excerpt($reminder['note']) : '';

    $meta = [];
    if ($due_at) {
        $meta['due'] = wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($due_at));
    }
    if ($status) {
        $meta['status'] = $status;
    }
    if ($advisor_name) {
        $meta['advisor'] = $advisor_name;
    }

    return [
        'type' => 'reminder',
        'ts' => $timestamp,
        'title' => 'Reminder',
        'detail' => $detail,
        'meta' => $meta,
        'icon' => 'clock',
    ];
}

function peracrm_timeline_normalize_activity(array $event, array $user_map)
{
    $created_at = isset($event['created_at']) ? (string) $event['created_at'] : '';
    $timestamp = $created_at ? strtotime($created_at) : 0;
    if ($timestamp <= 0) {
        return null;
    }

    $event_type = isset($event['event_type']) ? (string) $event['event_type'] : '';
    $title = function_exists('peracrm_admin_activity_label')
        ? peracrm_admin_activity_label($event_type)
        : ($event_type !== '' ? ucfirst($event_type) : 'Activity');

    $detail = '';
    $meta = [];
    if (!empty($event['event_payload'])) {
        $payload = peracrm_json_decode($event['event_payload']);
        if (is_array($payload) && !empty($payload['property_id'])) {
            $property_id = absint($payload['property_id']);
            if ($property_id > 0) {
                $property_title = get_the_title($property_id);
                if (!$property_title) {
                    $property_title = 'Property #' . $property_id;
                }
                $detail = peracrm_timeline_excerpt('Property: ' . $property_title);
            }
        }

        $actor_id = 0;
        if (!empty($payload['actor_user_id'])) {
            $actor_id = (int) $payload['actor_user_id'];
        } elseif (!empty($payload['advisor_user_id'])) {
            $actor_id = (int) $payload['advisor_user_id'];
        }
        if ($actor_id && isset($user_map[$actor_id])) {
            $meta['by'] = $user_map[$actor_id];
        }
    }

    return [
        'type' => 'activity',
        'ts' => $timestamp,
        'title' => $title,
        'detail' => $detail,
        'meta' => $meta,
        'icon' => 'activity',
    ];
}

function peracrm_timeline_type_label($type)
{
    $labels = [
        'activity' => 'Activity',
        'note' => 'Note',
        'reminder' => 'Reminder',
    ];

    return isset($labels[$type]) ? $labels[$type] : 'Item';
}

function peracrm_timeline_meta_line(array $meta)
{
    if (empty($meta)) {
        return '';
    }

    $parts = [];
    foreach ($meta as $key => $value) {
        $label = '';
        switch ($key) {
            case 'due':
                $label = 'Due';
                break;
            case 'status':
                $label = 'Status';
                break;
            case 'advisor':
                $label = 'Advisor';
                break;
            case 'by':
                $label = 'By';
                break;
        }
        $value = (string) $value;
        if ($value === '') {
            continue;
        }
        $parts[] = $label !== '' ? $label . ': ' . $value : $value;
    }

    return implode(' · ', $parts);
}

function peracrm_timeline_time_display($timestamp)
{
    if ($timestamp <= 0) {
        return [
            'relative' => '',
            'title' => '',
        ];
    }

    $now = current_time('timestamp');
    if ($timestamp >= $now) {
        $relative = 'in ' . human_time_diff($now, $timestamp);
    } else {
        $relative = human_time_diff($timestamp, $now) . ' ago';
    }
    $title = wp_date(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);

    return [
        'relative' => $relative,
        'title' => $title,
    ];
}

function peracrm_timeline_status_label($status)
{
    $status = sanitize_key((string) $status);
    if ($status === 'done') {
        return 'Done';
    }

    if ($status === 'pending') {
        return 'Pending';
    }

    return $status !== '' ? ucfirst($status) : '';
}

function peracrm_timeline_excerpt($text, $length = 24)
{
    $text = wp_strip_all_tags((string) $text);

    return wp_trim_words($text, $length, '…');
}
