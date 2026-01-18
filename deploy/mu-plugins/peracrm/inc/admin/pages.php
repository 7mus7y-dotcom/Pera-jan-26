<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once PERACRM_INC . '/admin/pages/work-queue.php';
require_once PERACRM_INC . '/admin/pages/client-view.php';
require_once PERACRM_INC . '/admin/pages/pipeline.php';

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

    add_submenu_page(
        $parent_slug,
        'Work Queue',
        'Work Queue',
        'edit_crm_clients',
        'peracrm-work-queue',
        'peracrm_render_work_queue_page'
    );

    $pipeline_hook = add_submenu_page(
        $parent_slug,
        'Pipeline',
        'Pipeline',
        'edit_crm_clients',
        'peracrm-pipeline',
        'peracrm_render_pipeline_page'
    );

    if ($pipeline_hook) {
        $GLOBALS['peracrm_pipeline_hook'] = $pipeline_hook;
    }

    add_submenu_page(
        $parent_slug,
        'Client View',
        'Client View',
        'edit_crm_clients',
        'peracrm-client-view',
        'peracrm_render_client_view_page'
    );
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

function peracrm_admin_is_pipeline_screen($hook)
{
    $stored = isset($GLOBALS['peracrm_pipeline_hook']) ? $GLOBALS['peracrm_pipeline_hook'] : '';

    return $stored !== '' && $hook === $stored;
}

function peracrm_render_my_reminders_page()
{
    if (!peracrm_admin_user_can_manage()) {
        wp_die('Unauthorized');
    }

    echo '<div class="wrap">';
    echo '<h1>My Reminders</h1>';

    $advisor_id = get_current_user_id();
    $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
    $range = isset($_GET['range']) ? sanitize_key(wp_unslash($_GET['range'])) : '';
    $orderby = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : 'due_at';
    $order = isset($_GET['order']) ? strtolower(sanitize_key(wp_unslash($_GET['order']))) : 'asc';

    $allowed_statuses = array_merge([''], peracrm_reminders_allowed_statuses());
    if (!in_array($status, $allowed_statuses, true)) {
        $status = '';
    }

    $allowed_ranges = ['', 'overdue', 'next_7', 'next_30', 'all'];
    if (!in_array($range, $allowed_ranges, true)) {
        $range = '';
    }

    $allowed_orderby = ['due_at', 'status'];
    if (!in_array($orderby, $allowed_orderby, true)) {
        $orderby = 'due_at';
    }

    $order = $order === 'desc' ? 'desc' : 'asc';
    $order_param = $orderby === 'status' ? 'status_' . $order : $order;

    $per_page = 50;
    $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $offset = ($paged - 1) * $per_page;

    $reminders = peracrm_reminders_list_for_advisor(
        $advisor_id,
        $per_page,
        $offset,
        $status !== '' ? $status : null,
        $range !== '' ? $range : null,
        $order_param
    );

    $total = peracrm_reminders_count_for_advisor(
        $advisor_id,
        $status !== '' ? $status : null,
        $range !== '' ? $range : null
    );

    $client_titles = [];
    $client_ids = [];
    foreach ($reminders as $reminder) {
        if (!empty($reminder['client_id'])) {
            $client_ids[] = (int) $reminder['client_id'];
        }
    }
    $client_ids = array_values(array_unique($client_ids));
    if (!empty($client_ids)) {
        $clients = get_posts([
            'post_type' => 'crm_client',
            'post__in' => $client_ids,
            'posts_per_page' => count($client_ids),
            'post_status' => 'any',
        ]);
        foreach ($clients as $client) {
            $client_titles[(int) $client->ID] = $client->post_title;
        }
    }

    echo '<form method="get" class="peracrm-filters">';
    echo '<input type="hidden" name="post_type" value="crm_client" />';
    echo '<input type="hidden" name="page" value="peracrm-my-reminders" />';
    echo '<label for="peracrm-filter-status" class="screen-reader-text">Status</label>';
    echo '<select name="status" id="peracrm-filter-status">';
    $status_options = [
        '' => 'All statuses',
        'pending' => 'Pending',
        'done' => 'Done',
        'dismissed' => 'Dismissed',
    ];
    foreach ($status_options as $value => $label) {
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr($value),
            selected($status, $value, false),
            esc_html($label)
        );
    }
    echo '</select>';
    echo '<label for="peracrm-filter-range" class="screen-reader-text">Date range</label>';
    echo '<select name="range" id="peracrm-filter-range">';
    $range_options = [
        '' => 'All dates',
        'overdue' => 'Overdue',
        'next_7' => 'Next 7 days',
        'next_30' => 'Next 30 days',
    ];
    foreach ($range_options as $value => $label) {
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr($value),
            selected($range, $value, false),
            esc_html($label)
        );
    }
    echo '</select>';
    echo '<label for="peracrm-filter-orderby" class="screen-reader-text">Sort by</label>';
    echo '<select name="orderby" id="peracrm-filter-orderby">';
    $orderby_options = [
        'due_at' => 'Sort by due date',
        'status' => 'Sort by status',
    ];
    foreach ($orderby_options as $value => $label) {
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr($value),
            selected($orderby, $value, false),
            esc_html($label)
        );
    }
    echo '</select>';
    echo '<label for="peracrm-filter-order" class="screen-reader-text">Order</label>';
    echo '<select name="order" id="peracrm-filter-order">';
    $order_options = [
        'asc' => 'Ascending',
        'desc' => 'Descending',
    ];
    foreach ($order_options as $value => $label) {
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr($value),
            selected($order, $value, false),
            esc_html($label)
        );
    }
    echo '</select>';
    echo '<button type="submit" class="button">Filter</button>';
    echo '</form>';

    if (empty($reminders)) {
        echo '<p class="peracrm-empty">No reminders found.</p>';
        echo '</div>';
        return;
    }

    $current_url = add_query_arg(
        [
            'post_type' => 'crm_client',
            'page' => 'peracrm-my-reminders',
            'status' => $status,
            'range' => $range,
            'orderby' => $orderby,
            'order' => $order,
            'paged' => $paged,
        ],
        admin_url('edit.php')
    );

    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>Due</th><th>Client</th><th>Note</th><th>Status</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    foreach ($reminders as $reminder) {
        $client_id = (int) $reminder['client_id'];
        $client_title = isset($client_titles[$client_id]) && $client_titles[$client_id] !== '' ? $client_titles[$client_id] : 'Client #' . $client_id;
        $client_link = get_edit_post_link($client_id, '');
        $due_at = mysql2date('Y-m-d H:i', $reminder['due_at']);
        $note = $reminder['note'] ? wp_trim_words($reminder['note'], 15, '…') : '';
        $status_label = ucfirst($reminder['status']);

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
        echo '<td>' . esc_html($status_label) . '</td>';
        echo '<td>';
        if ($reminder['status'] === 'pending') {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-inline-form">';
            wp_nonce_field('peracrm_update_reminder_status');
            echo '<input type="hidden" name="action" value="peracrm_update_reminder_status" />';
            echo '<input type="hidden" name="peracrm_reminder_id" value="' . esc_attr($reminder['id']) . '" />';
            echo '<input type="hidden" name="peracrm_status" value="done" />';
            echo '<input type="hidden" name="peracrm_redirect" value="' . esc_url($current_url) . '" />';
            echo '<button type="submit" class="button">Mark done</button>';
            echo '</form>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-inline-form">';
            wp_nonce_field('peracrm_update_reminder_status');
            echo '<input type="hidden" name="action" value="peracrm_update_reminder_status" />';
            echo '<input type="hidden" name="peracrm_reminder_id" value="' . esc_attr($reminder['id']) . '" />';
            echo '<input type="hidden" name="peracrm_status" value="dismissed" />';
            echo '<input type="hidden" name="peracrm_redirect" value="' . esc_url($current_url) . '" />';
            echo '<button type="submit" class="button">Dismiss</button>';
            echo '</form>';
        } else {
            echo '—';
        }
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';

    $total_pages = (int) ceil($total / $per_page);
    if ($total_pages > 1) {
        $page_links = paginate_links([
            'base' => add_query_arg(
                [
                    'post_type' => 'crm_client',
                    'page' => 'peracrm-my-reminders',
                    'status' => $status,
                    'range' => $range,
                    'orderby' => $orderby,
                    'order' => $order,
                    'paged' => '%#%',
                ],
                admin_url('edit.php')
            ),
            'format' => '',
            'current' => $paged,
            'total' => $total_pages,
            'type' => 'list',
        ]);

        if ($page_links) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
        }
    }

    echo '</div>';
}
