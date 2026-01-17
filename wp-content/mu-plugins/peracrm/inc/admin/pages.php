<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_register_admin_menu()
{
    if (!peracrm_admin_user_can_manage()) {
        return;
    }

    $capability = peracrm_admin_required_capability();
    $parent_slug = 'edit.php?post_type=crm_client';

    $hook = add_submenu_page(
        $parent_slug,
        'My Reminders',
        'My Reminders',
        $capability,
        'peracrm-my-reminders',
        'peracrm_render_my_reminders_page'
    );

    if ($hook) {
        $GLOBALS['peracrm_my_reminders_hook'] = $hook;
    }
}

function peracrm_admin_required_capability()
{
    return current_user_can('manage_options') ? 'manage_options' : 'edit_crm_clients';
}

function peracrm_admin_is_my_reminders_screen($hook)
{
    $stored = isset($GLOBALS['peracrm_my_reminders_hook']) ? $GLOBALS['peracrm_my_reminders_hook'] : '';

    return $stored !== '' && $hook === $stored;
}

function peracrm_render_my_reminders_page()
{
    if (!peracrm_admin_user_can_manage()) {
        wp_die('Unauthorized');
    }

    $advisor_id = get_current_user_id();
    $timezone = wp_timezone();
    $today_start = (new DateTime('today', $timezone))->getTimestamp();
    $today_end = (new DateTime('tomorrow', $timezone))->getTimestamp() - 1;
    $until = (new DateTime('+14 days', $timezone))->setTime(23, 59, 59)->getTimestamp();

    $reminders = peracrm_admin_get_advisor_reminders_until(
        $advisor_id,
        wp_date('Y-m-d H:i:s', $until, $timezone),
        200
    );

    $sections = [
        'overdue' => [
            'title' => 'Overdue',
            'items' => [],
        ],
        'today' => [
            'title' => 'Due Today',
            'items' => [],
        ],
        'upcoming' => [
            'title' => 'Upcoming (next 14 days)',
            'items' => [],
        ],
    ];

    foreach ($reminders as $reminder) {
        $due_ts = strtotime($reminder['due_at']);
        if ($due_ts < $today_start) {
            $sections['overdue']['items'][] = $reminder;
        } elseif ($due_ts <= $today_end) {
            $sections['today']['items'][] = $reminder;
        } else {
            $sections['upcoming']['items'][] = $reminder;
        }
    }

    echo '<div class="wrap">';
    echo '<h1>My Reminders</h1>';

    foreach ($sections as $section) {
        echo '<h2>' . esc_html($section['title']) . '</h2>';
        if (empty($section['items'])) {
            echo '<p class="peracrm-empty">No reminders.</p>';
            continue;
        }

        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>Due</th><th>Client</th><th>Note</th><th>Action</th></tr></thead>';
        echo '<tbody>';
        foreach ($section['items'] as $reminder) {
            $client_id = (int) $reminder['client_id'];
            $client_title = get_the_title($client_id);
            if (!$client_title) {
                $client_title = 'Client #' . $client_id;
            }
            $client_link = get_edit_post_link($client_id, '');
            $due_at = mysql2date('Y-m-d H:i', $reminder['due_at']);
            $note = $reminder['note'] ? $reminder['note'] : '';

            echo '<tr>';
            echo '<td>' . esc_html($due_at) . '</td>';
            echo '<td>';
            if ($client_link) {
                echo '<a href="' . esc_url($client_link) . '">' . esc_html($client_title) . '</a>';
            } else {
                echo esc_html($client_title);
            }
            echo '</td>';
            echo '<td>' . esc_html($note) . '</td>';
            echo '<td>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-inline-form">';
            wp_nonce_field('peracrm_mark_reminder_done');
            echo '<input type="hidden" name="action" value="peracrm_mark_reminder_done" />';
            echo '<input type="hidden" name="peracrm_reminder_id" value="' . esc_attr($reminder['id']) . '" />';
            echo '<input type="hidden" name="peracrm_redirect" value="' . esc_url(admin_url('edit.php?post_type=crm_client&page=peracrm-my-reminders')) . '" />';
            echo '<button type="submit" class="button">Mark done</button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}
