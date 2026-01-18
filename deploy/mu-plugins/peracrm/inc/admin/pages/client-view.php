<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_render_client_view_page()
{
    if (!peracrm_admin_user_can_manage()) {
        wp_die('Unauthorized');
    }

    $client_id = isset($_GET['client_id']) ? absint($_GET['client_id']) : 0;
    $client = $client_id ? get_post($client_id) : null;

    if (!$client_id || !$client || $client->post_type !== 'crm_client') {
        wp_die('Client not found.');
    }

    if (!current_user_can('edit_post', $client_id)) {
        wp_die('You do not have permission to view this client.');
    }

    $can_manage_all = current_user_can('manage_options') || current_user_can('peracrm_manage_all_clients');
    if (!$can_manage_all) {
        $assigned_id = function_exists('peracrm_client_get_assigned_advisor_id')
            ? (int) peracrm_client_get_assigned_advisor_id($client_id)
            : 0;
        if ($assigned_id !== get_current_user_id()) {
            wp_die('You are not assigned to this client.');
        }
    }

    $profile = function_exists('peracrm_client_get_profile')
        ? peracrm_client_get_profile($client_id)
        : [
            'status' => '',
            'client_type' => '',
            'preferred_contact' => '',
            'budget_min_usd' => '',
            'budget_max_usd' => '',
            'phone' => '',
            'email' => '',
        ];

    $health = function_exists('peracrm_client_health_get') ? peracrm_client_health_get($client_id) : [];
    $badge = function_exists('peracrm_client_health_badge_html')
        ? peracrm_client_health_badge_html($health)
        : esc_html(isset($health['label']) ? $health['label'] : 'None');

    $now_ts = current_time('timestamp');
    $last_activity_ts = isset($health['last_activity_ts']) ? (int) $health['last_activity_ts'] : 0;
    $last_activity = $last_activity_ts ? human_time_diff($last_activity_ts, $now_ts) . ' ago' : '—';

    $phone = isset($profile['phone']) ? trim((string) $profile['phone']) : '';
    $email_raw = isset($profile['email']) ? (string) $profile['email'] : '';
    $email = $email_raw !== '' ? sanitize_email($email_raw) : '';
    if ($email !== '' && !is_email($email)) {
        $email = '';
    }

    $tel_link = $phone !== '' ? 'tel:' . rawurlencode($phone) : '';
    $wa_link = '';
    $phone_trimmed = ltrim($phone);
    if ($phone_trimmed !== '' && strpos($phone_trimmed, '+') === 0) {
        $wa_digits = preg_replace('/\D+/', '', $phone_trimmed);
        if ($wa_digits !== '') {
            $wa_link = 'https://wa.me/' . $wa_digits;
        }
    }
    $mailto_link = $email !== '' ? 'mailto:' . rawurlencode($email) : '';

    $status_labels = [
        'enquiry' => 'Enquiry',
        'active' => 'Active',
        'dormant' => 'Dormant',
        'closed' => 'Closed',
    ];

    $type_labels = [
        'citizenship' => 'Citizenship',
        'investor' => 'Investor',
        'lifestyle' => 'Lifestyle',
    ];

    $contact_labels = [
        '' => 'No preference',
        'phone' => 'Phone',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
    ];

    $status = isset($profile['status']) ? (string) $profile['status'] : '';
    $client_type = isset($profile['client_type']) ? (string) $profile['client_type'] : '';
    $preferred_contact = isset($profile['preferred_contact']) ? (string) $profile['preferred_contact'] : '';

    $status_label = isset($status_labels[$status]) ? $status_labels[$status] : '—';
    $type_label = isset($type_labels[$client_type]) ? $type_labels[$client_type] : '—';
    $contact_label = array_key_exists($preferred_contact, $contact_labels)
        ? $contact_labels[$preferred_contact]
        : '—';

    $budget_min_raw = $profile['budget_min_usd'] ?? '';
    $budget_max_raw = $profile['budget_max_usd'] ?? '';
    $budget_min = is_numeric($budget_min_raw) ? (int) $budget_min_raw : null;
    $budget_max = is_numeric($budget_max_raw) ? (int) $budget_max_raw : null;

    $budget_label = '—';
    if (null !== $budget_min && null !== $budget_max) {
        $budget_label = sprintf(
            '$%s - $%s',
            number_format_i18n($budget_min),
            number_format_i18n($budget_max)
        );
    } elseif (null !== $budget_min) {
        $budget_label = sprintf('$%s+', number_format_i18n($budget_min));
    } elseif (null !== $budget_max) {
        $budget_label = sprintf('Up to $%s', number_format_i18n($budget_max));
    }

    $reminders_url = add_query_arg(
        [
            'post_type' => 'crm_client',
            'page' => 'peracrm-my-reminders',
            'client_id' => $client_id,
        ],
        admin_url('edit.php')
    );

    $has_reminders_table = function_exists('peracrm_reminders_table_exists') && peracrm_reminders_table_exists();
    $open_count = $has_reminders_table && function_exists('peracrm_reminders_count_open_by_client')
        ? peracrm_reminders_count_open_by_client($client_id)
        : null;
    $overdue_count = $has_reminders_table && function_exists('peracrm_reminders_count_overdue_by_client')
        ? peracrm_reminders_count_overdue_by_client($client_id)
        : null;

    $notes = function_exists('peracrm_notes_list') ? peracrm_notes_list($client_id, 10, 0) : [];

    $timeline_filter = function_exists('peracrm_timeline_get_filter') ? peracrm_timeline_get_filter() : 'all';
    $timeline_items = function_exists('peracrm_timeline_get_items')
        ? peracrm_timeline_get_items($client_id, 50, $timeline_filter)
        : [];
    $timeline_missing = function_exists('peracrm_timeline_missing_sources')
        ? peracrm_timeline_missing_sources($timeline_filter)
        : [];

    $edit_link = get_edit_post_link($client_id, '');

    echo '<div class="wrap peracrm-client-view">';
    echo '<h1 class="wp-heading-inline">' . esc_html($client->post_title) . '</h1>';
    if ($edit_link) {
        echo ' <a class="page-title-action" href="' . esc_url($edit_link) . '">Edit</a>';
    }

    echo '<div class="peracrm-client-view__meta">';
    echo '<p><strong>Health:</strong> ' . $badge . ' <span class="description">Last activity ' . esc_html($last_activity) . '</span></p>';
    echo '</div>';

    echo '<hr />';

    echo '<h2>Quick Actions</h2>';
    if ($tel_link || $wa_link || $mailto_link) {
        echo '<p>';
        if ($tel_link) {
            echo '<a class="button" href="' . esc_url($tel_link) . '">Call</a> ';
        }
        if ($wa_link) {
            echo '<a class="button" href="' . esc_url($wa_link) . '" target="_blank" rel="noopener">WhatsApp</a> ';
        }
        if ($mailto_link) {
            echo '<a class="button" href="' . esc_url($mailto_link) . '">Email</a>';
        }
        echo '</p>';
    } else {
        echo '<p class="peracrm-empty">No quick actions available.</p>';
    }

    echo '<h2>Client Profile</h2>';
    echo '<table class="widefat striped">';
    echo '<tbody>';
    echo '<tr><th>Status</th><td>' . esc_html($status_label) . '</td></tr>';
    echo '<tr><th>Client type</th><td>' . esc_html($type_label) . '</td></tr>';
    echo '<tr><th>Budget range</th><td>' . esc_html($budget_label) . '</td></tr>';
    echo '<tr><th>Preferred contact</th><td>' . esc_html($contact_label) . '</td></tr>';
    echo '</tbody>';
    echo '</table>';
    if ($edit_link) {
        echo '<p><a href="' . esc_url($edit_link) . '">Edit profile</a></p>';
    }

    echo '<h2>Reminders</h2>';
    if ($has_reminders_table) {
        echo '<p>Open: <strong>' . esc_html((string) $open_count) . '</strong> · Overdue: <strong>' . esc_html((string) $overdue_count) . '</strong></p>';
    } else {
        echo '<p class="peracrm-empty">Reminders data unavailable.</p>';
    }
    echo '<p><a href="' . esc_url($reminders_url) . '">View reminders</a></p>';

    echo '<h2>Notes</h2>';
    if (empty($notes)) {
        echo '<p class="peracrm-empty">No notes yet.</p>';
    } else {
        echo '<ul class="peracrm-list">';
        foreach ($notes as $note) {
            $author = get_userdata((int) $note['advisor_user_id']);
            $author_name = $author ? $author->display_name : 'Advisor';
            printf(
                '<li><div class="peracrm-list__meta">%1$s · %2$s</div><div class="peracrm-list__body">%3$s</div></li>',
                esc_html(mysql2date('Y-m-d H:i', $note['created_at'])),
                esc_html($author_name),
                esc_html($note['note_body'])
            );
        }
        echo '</ul>';
    }

    if (peracrm_admin_user_can_manage()) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-form">';
        wp_nonce_field('peracrm_add_note');
        echo '<input type="hidden" name="action" value="peracrm_add_note" />';
        echo '<input type="hidden" name="peracrm_client_id" value="' . esc_attr($client_id) . '" />';
        echo '<p><label for="peracrm_note_body">Add note</label></p>';
        echo '<p><textarea name="peracrm_note_body" id="peracrm_note_body" rows="4" class="widefat"></textarea></p>';
        echo '<p><button type="submit" class="button button-primary">Add Note</button></p>';
        echo '</form>';
    }

    echo '<h2>Timeline</h2>';
    $timeline_base_url = add_query_arg(
        [
            'page' => 'peracrm-client-view',
            'client_id' => $client_id,
        ],
        admin_url('admin.php')
    );
    $timeline_filters = [
        'all' => 'All',
        'activity' => 'Activity',
        'notes' => 'Notes',
        'reminders' => 'Reminders',
    ];

    echo '<div class="peracrm-timeline-filters">';
    $filter_links = [];
    foreach ($timeline_filters as $key => $label) {
        $url = add_query_arg('peracrm_timeline', $key, $timeline_base_url);
        if ($key === $timeline_filter) {
            $filter_links[] = '<strong>' . esc_html($label) . '</strong>';
        } else {
            $filter_links[] = '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        }
    }
    echo implode(' | ', $filter_links);
    echo '</div>';

    if (!empty($timeline_missing)) {
        echo '<p class="description">' . esc_html(implode(' ', $timeline_missing)) . '</p>';
    }

    if (empty($timeline_items)) {
        echo '<p class="peracrm-empty">No timeline items yet.</p>';
    } else {
        echo '<ul class="peracrm-list peracrm-timeline-list">';
        foreach ($timeline_items as $item) {
            $type_label = function_exists('peracrm_timeline_type_label')
                ? peracrm_timeline_type_label($item['type'] ?? '')
                : '';
            $title = isset($item['title']) ? (string) $item['title'] : '';
            $detail = isset($item['detail']) ? (string) $item['detail'] : '';
            $ts = isset($item['ts']) ? (int) $item['ts'] : 0;
            $time = function_exists('peracrm_timeline_time_display')
                ? peracrm_timeline_time_display($ts)
                : ['relative' => '', 'title' => ''];
            $meta_line = function_exists('peracrm_timeline_meta_line')
                ? peracrm_timeline_meta_line($item['meta'] ?? [])
                : '';

            echo '<li class="peracrm-timeline-item">';
            echo '<div class="peracrm-timeline-header">';
            echo '<span class="peracrm-timeline-badge">' . esc_html($type_label) . '</span>';
            if (!empty($time['relative'])) {
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
}
