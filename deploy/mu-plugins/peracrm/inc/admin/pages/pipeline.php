<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_render_pipeline_page()
{
    if (!peracrm_admin_user_can_manage()) {
        wp_die('Unauthorized');
    }

    $is_admin = current_user_can('manage_options');
    $can_reassign = function_exists('peracrm_admin_user_can_reassign') && peracrm_admin_user_can_reassign();
    $statuses = peracrm_pipeline_status_labels();
    $client_type_options = peracrm_pipeline_client_type_options();
    $health_options = peracrm_pipeline_health_options();

    $views = peracrm_pipeline_get_user_views(get_current_user_id());
    $view_map = [];
    foreach ($views as $view) {
        if (!empty($view['id'])) {
            $view_map[$view['id']] = $view;
        }
    }

    $active_view_id = isset($_GET['view_id']) ? sanitize_text_field(wp_unslash($_GET['view_id'])) : '';
    if ($active_view_id !== '' && !isset($view_map[$active_view_id])) {
        $active_view_id = '';
    }

    $view_filters = [];
    if ($active_view_id !== '' && isset($view_map[$active_view_id]['filters']) && is_array($view_map[$active_view_id]['filters'])) {
        $view_filters = $view_map[$active_view_id]['filters'];
    }
    if (!empty($view_filters)) {
        $view_filters['client_type'] = isset($view_filters['client_type']) ? sanitize_key($view_filters['client_type']) : 'all';
        if (!isset($client_type_options[$view_filters['client_type']])) {
            $view_filters['client_type'] = 'all';
        }

        $view_filters['health'] = isset($view_filters['health']) ? sanitize_key($view_filters['health']) : 'all';
        if (!isset($health_options[$view_filters['health']])) {
            $view_filters['health'] = 'all';
        }

        $view_filters['hide_empty_columns'] = !empty($view_filters['hide_empty_columns']) ? 1 : 0;

        if ($is_admin) {
            $view_filters['advisor_id'] = isset($view_filters['advisor_id']) ? absint($view_filters['advisor_id']) : 0;
            if ($view_filters['advisor_id'] > 0 && !peracrm_user_is_valid_advisor($view_filters['advisor_id'])) {
                $view_filters['advisor_id'] = 0;
            }
        } else {
            unset($view_filters['advisor_id']);
        }
    }

    $client_type_source = $active_view_id !== '' && isset($view_filters['client_type'])
        ? $view_filters['client_type']
        : (isset($_GET['client_type']) ? sanitize_key(wp_unslash($_GET['client_type'])) : 'all');
    $client_type = sanitize_key($client_type_source);
    if (!isset($client_type_options[$client_type])) {
        $client_type = 'all';
    }

    $health_source = $active_view_id !== '' && isset($view_filters['health'])
        ? $view_filters['health']
        : (isset($_GET['health']) ? sanitize_key(wp_unslash($_GET['health'])) : 'all');
    $health_filter = sanitize_key($health_source);
    if (!isset($health_options[$health_filter])) {
        $health_filter = 'all';
    }

    $hide_empty_source = $active_view_id !== '' && array_key_exists('hide_empty_columns', $view_filters)
        ? $view_filters['hide_empty_columns']
        : (isset($_GET['hide_empty_columns']) ? wp_unslash($_GET['hide_empty_columns']) : 0);
    $hide_empty_columns = !empty($hide_empty_source) ? 1 : 0;

    $advisor_options = [];
    $advisor_map = [];
    if ($is_admin || $can_reassign) {
        $advisor_options = get_users([
            'fields' => ['ID', 'display_name'],
            'capability' => 'edit_crm_clients',
            'orderby' => 'display_name',
            'order' => 'ASC',
        ]);
        foreach ($advisor_options as $advisor) {
            $advisor_map[(int) $advisor->ID] = $advisor->display_name;
        }
    }

    $advisor_source = $active_view_id !== '' && array_key_exists('advisor_id', $view_filters)
        ? $view_filters['advisor_id']
        : ($_GET['advisor'] ?? 0);
    $advisor_id = $is_admin ? absint($advisor_source) : get_current_user_id();
    if ($is_admin && $advisor_id > 0 && !isset($advisor_map[$advisor_id])) {
        $advisor_id = 0;
    }
    if (!$is_admin) {
        $advisor_id = get_current_user_id();
    }

    $scope_advisor_id = $is_admin ? $advisor_id : get_current_user_id();
    $reminder_scope = ($is_admin && $advisor_id === 0) ? null : $scope_advisor_id;

    $per_page = 10;
    $has_activity_table = function_exists('peracrm_activity_table_exists') && peracrm_activity_table_exists();
    $has_reminders_table = function_exists('peracrm_reminders_table_exists') && peracrm_reminders_table_exists();
    $recent_events = [];
    $recent_payloads = [];
    $recent_client_map = [];
    $recent_user_map = [];

    if ($has_activity_table && function_exists('peracrm_activity_list_recent_pipeline')) {
        $recent_scope = $is_admin ? $advisor_id : get_current_user_id();
        $recent_events = peracrm_activity_list_recent_pipeline(20, $recent_scope);
        if (!empty($recent_events)) {
            $client_ids = [];
            $user_ids = [];
            $index = 0;
            foreach ($recent_events as $event) {
                $client_ids[] = isset($event['client_id']) ? (int) $event['client_id'] : 0;
                $payload = !empty($event['event_payload']) ? peracrm_json_decode($event['event_payload']) : [];
                $recent_payloads[$index] = is_array($payload) ? $payload : [];

                $actor_id = 0;
                if (!empty($payload['actor_user_id'])) {
                    $actor_id = (int) $payload['actor_user_id'];
                } elseif (!empty($payload['advisor_user_id'])) {
                    $actor_id = (int) $payload['advisor_user_id'];
                }
                if ($actor_id > 0) {
                    $user_ids[] = $actor_id;
                }
                if (isset($payload['from']) && is_numeric($payload['from'])) {
                    $user_ids[] = (int) $payload['from'];
                }
                if (isset($payload['to']) && is_numeric($payload['to'])) {
                    $user_ids[] = (int) $payload['to'];
                }

                $index++;
            }

            $client_ids = array_values(array_unique(array_filter($client_ids)));
            if (!empty($client_ids)) {
                $clients = get_posts([
                    'post_type' => 'crm_client',
                    'post_status' => 'any',
                    'include' => $client_ids,
                    'numberposts' => count($client_ids),
                ]);
                foreach ($clients as $client) {
                    $recent_client_map[(int) $client->ID] = $client->post_title;
                }
            }

            $user_ids = array_values(array_unique(array_filter($user_ids)));
            if (!empty($user_ids)) {
                $users = get_users([
                    'include' => $user_ids,
                    'fields' => ['ID', 'display_name'],
                ]);
                foreach ($users as $user) {
                    $recent_user_map[(int) $user->ID] = $user->display_name;
                }
            }
        }
    }

    echo '<div class="wrap peracrm-pipeline">';
    echo '<h1>Pipeline</h1>';

    if (isset($_GET['peracrm_notice'])) {
        $notice = sanitize_key(wp_unslash($_GET['peracrm_notice']));
        $notice_messages = [
            'stage_moved' => ['success', 'Client stage updated.'],
            'stage_denied' => ['error', 'You are not allowed to move this client.'],
            'stage_invalid' => ['error', 'Please choose a valid pipeline stage.'],
        ];
        if (isset($notice_messages[$notice])) {
            [$class, $message] = $notice_messages[$notice];
            printf(
                '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
                esc_attr($class),
                esc_html($message)
            );
        }
    }

    $bulk_done = isset($_GET['bulk_done']) ? absint($_GET['bulk_done']) : null;
    $bulk_failed = isset($_GET['bulk_failed']) ? absint($_GET['bulk_failed']) : null;
    $bulk_capped = !empty($_GET['bulk_capped']);
    if (null !== $bulk_done || null !== $bulk_failed) {
        if (!empty($bulk_done)) {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html(sprintf('Bulk action applied to %d clients.', $bulk_done))
            );
        }
        if (!empty($bulk_failed)) {
            printf(
                '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
                esc_html(sprintf('%d clients skipped (not authorized / invalid / missing tables).', $bulk_failed))
            );
        }
        if ($bulk_capped) {
            printf(
                '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
                esc_html('Bulk action limited to the first 200 clients.')
            );
        }
    }

    if (!$has_reminders_table) {
        echo '<div class="notice notice-warning is-dismissible"><p>';
        echo esc_html('Reminders data unavailable. Counts will display as 0 or —.');
        echo '</p></div>';
    }

    echo '<div class="card peracrm-pipeline-recent">';
    echo '<h2>Recent Pipeline Changes</h2>';
    if (!$has_activity_table) {
        echo '<p>' . esc_html('Activity tracking unavailable.') . '</p>';
    } elseif (empty($recent_events)) {
        echo '<p>' . esc_html('No recent pipeline changes.') . '</p>';
    } else {
        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html('Time') . '</th>';
        echo '<th>' . esc_html('Client') . '</th>';
        echo '<th>' . esc_html('Event') . '</th>';
        echo '<th>' . esc_html('Actor') . '</th>';
        echo '<th>' . esc_html('Details') . '</th>';
        echo '</tr></thead><tbody>';
        $status_labels = peracrm_pipeline_status_labels();
        $now_ts = current_time('timestamp');
        foreach ($recent_events as $index => $event) {
            $created_at = isset($event['created_at']) ? (string) $event['created_at'] : '';
            $timestamp = $created_at ? strtotime($created_at) : 0;
            $relative = $timestamp ? human_time_diff($timestamp, $now_ts) . ' ago' : '—';

            $client_id = isset($event['client_id']) ? (int) $event['client_id'] : 0;
            $client_title = $client_id && isset($recent_client_map[$client_id])
                ? $recent_client_map[$client_id]
                : ($client_id ? 'Client #' . $client_id : '—');
            $view_link = $client_id && function_exists('peracrm_render_client_view_page')
                ? add_query_arg(
                    [
                        'page' => 'peracrm-client-view',
                        'client_id' => $client_id,
                    ],
                    admin_url('admin.php')
                )
                : '';
            $client_link = $view_link ?: ($client_id ? get_edit_post_link($client_id, '') : '');

            $event_type = isset($event['event_type']) ? (string) $event['event_type'] : '';
            $event_label = function_exists('peracrm_admin_activity_label')
                ? peracrm_admin_activity_label($event_type)
                : ($event_type !== '' ? ucfirst(str_replace('_', ' ', $event_type)) : 'Activity');

            $payload = isset($recent_payloads[$index]) ? $recent_payloads[$index] : [];
            $actor_id = 0;
            if (!empty($payload['actor_user_id'])) {
                $actor_id = (int) $payload['actor_user_id'];
            } elseif (!empty($payload['advisor_user_id'])) {
                $actor_id = (int) $payload['advisor_user_id'];
            }
            $actor_label = $actor_id && isset($recent_user_map[$actor_id]) ? $recent_user_map[$actor_id] : '—';

            $detail = '—';
            if ('status_changed' === $event_type) {
                $from_key = isset($payload['from']) ? (string) $payload['from'] : '';
                $to_key = isset($payload['to']) ? (string) $payload['to'] : '';
                $from_label = $from_key !== '' && isset($status_labels[$from_key]) ? $status_labels[$from_key] : $from_key;
                $to_label = $to_key !== '' && isset($status_labels[$to_key]) ? $status_labels[$to_key] : $to_key;
                if ($from_label || $to_label) {
                    $detail = trim($from_label) !== '' || trim($to_label) !== ''
                        ? sprintf('%s → %s', $from_label ?: '—', $to_label ?: '—')
                        : '—';
                }
                if (!empty($payload['context']) && 'pipeline_bulk' === $payload['context']) {
                    $detail = $detail !== '—' ? $detail . ' (Bulk update)' : 'Bulk update';
                }
            } elseif ('advisor_reassigned' === $event_type) {
                $from_id = isset($payload['from']) ? (int) $payload['from'] : 0;
                $to_id = isset($payload['to']) ? (int) $payload['to'] : 0;
                $from_label = $from_id && isset($recent_user_map[$from_id]) ? $recent_user_map[$from_id] : ($from_id ? 'User #' . $from_id : '—');
                $to_label = $to_id && isset($recent_user_map[$to_id]) ? $recent_user_map[$to_id] : ($to_id ? 'User #' . $to_id : '—');
                $detail = sprintf('%s → %s', $from_label, $to_label);
            } elseif ('reminder_added' === $event_type) {
                if (!empty($payload['due_at'])) {
                    $detail = 'Due ' . mysql2date('Y-m-d H:i', $payload['due_at']);
                } else {
                    $detail = 'Reminder added';
                }
            }

            echo '<tr>';
            echo '<td>' . esc_html($relative) . '</td>';
            echo '<td>';
            if ($client_link) {
                echo '<a href="' . esc_url($client_link) . '">' . esc_html($client_title) . '</a>';
            } else {
                echo esc_html($client_title);
            }
            echo '</td>';
            echo '<td>' . esc_html($event_label) . '</td>';
            echo '<td>' . esc_html($actor_label) . '</td>';
            echo '<td>' . esc_html($detail) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    echo '</div>';

    echo '<div class="peracrm-pipeline-views">';
    echo '<form method="get" class="peracrm-pipeline-views__form">';
    echo '<input type="hidden" name="post_type" value="crm_client" />';
    echo '<input type="hidden" name="page" value="peracrm-pipeline" />';
    echo '<label for="peracrm-pipeline-view" class="peracrm-pipeline-views__label">View:</label>';
    echo '<select name="view_id" id="peracrm-pipeline-view">';
    printf(
        '<option value=""%s>%s</option>',
        selected($active_view_id, '', false),
        esc_html('Default')
    );
    foreach ($views as $view) {
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr($view['id']),
            selected($active_view_id, $view['id'], false),
            esc_html($view['name'])
        );
    }
    echo '</select>';
    echo '<button type="submit" class="button">Apply</button>';
    echo '</form>';

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-pipeline-views__form">';
    echo '<input type="hidden" name="action" value="peracrm_pipeline_save_view" />';
    wp_nonce_field('peracrm_pipeline_save_view');
    echo '<label for="peracrm-pipeline-view-name" class="screen-reader-text">View name</label>';
    echo '<input type="text" name="view_name" id="peracrm-pipeline-view-name" maxlength="40" placeholder="View name" />';
    echo '<input type="hidden" name="client_type" value="' . esc_attr($client_type) . '" />';
    echo '<input type="hidden" name="health" value="' . esc_attr($health_filter) . '" />';
    echo '<input type="hidden" name="hide_empty_columns" value="' . esc_attr($hide_empty_columns) . '" />';
    if ($is_admin) {
        echo '<input type="hidden" name="advisor" value="' . esc_attr($advisor_id) . '" />';
    }
    echo '<button type="submit" class="button">Save current view</button>';
    echo '</form>';

    if ($active_view_id !== '') {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-pipeline-views__form">';
        echo '<input type="hidden" name="action" value="peracrm_pipeline_delete_view" />';
        wp_nonce_field('peracrm_pipeline_delete_view');
        echo '<input type="hidden" name="view_id" value="' . esc_attr($active_view_id) . '" />';
        echo '<button type="submit" class="button">Delete view</button>';
        echo '</form>';
    }
    echo '</div>';

    echo '<form method="get" class="peracrm-filters">';
    echo '<input type="hidden" name="post_type" value="crm_client" />';
    echo '<input type="hidden" name="page" value="peracrm-pipeline" />';

    if ($is_admin) {
        echo '<label for="peracrm-pipeline-advisor" class="screen-reader-text">Advisor</label>';
        echo '<select name="advisor" id="peracrm-pipeline-advisor">';
        printf(
            '<option value="0"%s>%s</option>',
            selected($advisor_id, 0, false),
            esc_html('All advisors')
        );
        foreach ($advisor_options as $advisor) {
            printf(
                '<option value="%1$d"%2$s>%3$s</option>',
                (int) $advisor->ID,
                selected($advisor_id, (int) $advisor->ID, false),
                esc_html($advisor->display_name)
            );
        }
        echo '</select>';
    } else {
        echo '<input type="hidden" name="advisor" value="' . esc_attr($advisor_id) . '" />';
    }

    echo '<label for="peracrm-pipeline-client-type" class="screen-reader-text">Client type</label>';
    echo '<select name="client_type" id="peracrm-pipeline-client-type">';
    foreach ($client_type_options as $value => $label) {
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr($value),
            selected($client_type, $value, false),
            esc_html($label)
        );
    }
    echo '</select>';

    echo '<label for="peracrm-pipeline-health" class="screen-reader-text">Health</label>';
    echo '<select name="health" id="peracrm-pipeline-health">';
    foreach ($health_options as $value => $label) {
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr($value),
            selected($health_filter, $value, false),
            esc_html($label)
        );
    }
    echo '</select>';

    echo '<button type="submit" class="button">Filter</button>';
    echo '<label for="peracrm-pipeline-hide-empty" class="peracrm-pipeline-hide-empty">';
    echo '<input type="checkbox" name="hide_empty_columns" id="peracrm-pipeline-hide-empty" value="1"' . checked($hide_empty_columns, 1, false) . ' />';
    echo '<span>Hide empty columns</span>';
    echo '</label>';
    echo '</form>';

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-pipeline-export">';
    echo '<input type="hidden" name="action" value="peracrm_pipeline_export_csv" />';
    wp_nonce_field('peracrm_pipeline_export_csv');
    echo '<input type="hidden" name="view_id" value="' . esc_attr($active_view_id) . '" />';
    echo '<input type="hidden" name="client_type" value="' . esc_attr($client_type) . '" />';
    echo '<input type="hidden" name="health" value="' . esc_attr($health_filter) . '" />';
    echo '<input type="hidden" name="advisor_id" value="' . esc_attr($advisor_id) . '" />';
    echo '<input type="hidden" name="hide_empty_columns" value="' . esc_attr($hide_empty_columns) . '" />';
    echo '<button type="submit" class="button">Export CSV</button>';
    echo '</form>';

    $columns = [];
    $all_query_ids = [];

    foreach ($statuses as $status_key => $status_label) {
        $paged_param = 'paged_' . $status_key;
        $paged = isset($_GET[$paged_param]) ? max(1, absint($_GET[$paged_param])) : 1;

        $meta_query = peracrm_pipeline_build_base_meta_query($client_type, $scope_advisor_id);
        $meta_query[] = [
            'key' => '_peracrm_status',
            'value' => $status_key,
            'compare' => '=',
        ];

        $query = new WP_Query([
            'post_type' => 'crm_client',
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => $meta_query,
        ]);

        $ids = array_values(array_map('intval', $query->posts));
        $columns[$status_key] = [
            'label' => $status_label,
            'query' => $query,
            'paged' => $paged,
            'paged_param' => $paged_param,
            'ids' => $ids,
            'display_ids' => [],
        ];
        $all_query_ids = array_merge($all_query_ids, $ids);
    }

    $all_query_ids = array_values(array_unique($all_query_ids));
    if (!empty($all_query_ids)) {
        update_meta_cache('post', $all_query_ids);
        if ($has_activity_table && function_exists('peracrm_client_health_prime_cache')) {
            peracrm_client_health_prime_cache($all_query_ids);
        }
    }

    $health_map = [];
    if ($has_activity_table && function_exists('peracrm_client_health_get')) {
        foreach ($all_query_ids as $client_id) {
            $health_map[$client_id] = peracrm_client_health_get($client_id);
        }
    }

    $display_ids = [];
    foreach ($columns as $status_key => $column) {
        foreach ($column['ids'] as $client_id) {
            if ($scope_advisor_id > 0 && function_exists('peracrm_client_get_assigned_advisor_id')) {
                $assigned_id = (int) peracrm_client_get_assigned_advisor_id($client_id);
                if ($assigned_id !== $scope_advisor_id) {
                    continue;
                }
            }

            if ($health_filter !== 'all') {
                $health_key = isset($health_map[$client_id]['key']) ? $health_map[$client_id]['key'] : 'none';
                if ($health_key !== $health_filter) {
                    continue;
                }
            }

            $columns[$status_key]['display_ids'][] = $client_id;
            $display_ids[] = $client_id;
        }
    }

    $display_ids = array_values(array_unique($display_ids));
    $reminder_counts = ['open_count' => [], 'overdue_count' => [], 'next_due' => []];
    if ($has_reminders_table && function_exists('peracrm_reminders_counts_by_client_ids')) {
        $reminder_counts = peracrm_reminders_counts_by_client_ids($display_ids, $reminder_scope);
    }

    $open_counts = isset($reminder_counts['open_count']) ? $reminder_counts['open_count'] : [];
    $overdue_counts = isset($reminder_counts['overdue_count']) ? $reminder_counts['overdue_count'] : [];
    $next_due_map = isset($reminder_counts['next_due']) ? $reminder_counts['next_due'] : [];

    $assigned_advisors = [];
    if ($is_admin && !empty($display_ids)) {
        $advisor_ids = [];
        foreach ($display_ids as $client_id) {
            if (function_exists('peracrm_client_get_assigned_advisor_id')) {
                $assigned_id = (int) peracrm_client_get_assigned_advisor_id($client_id);
                if ($assigned_id > 0) {
                    $advisor_ids[] = $assigned_id;
                }
            }
        }
        $advisor_ids = array_values(array_unique($advisor_ids));
        if (!empty($advisor_ids)) {
            $advisors = get_users([
                'include' => $advisor_ids,
                'fields' => ['ID', 'display_name'],
            ]);
            foreach ($advisors as $advisor) {
                $assigned_advisors[(int) $advisor->ID] = $advisor->display_name;
            }
        }
    }

    $now_ts = current_time('timestamp');
    $base_params = [
        'post_type' => 'crm_client',
        'page' => 'peracrm-pipeline',
        'client_type' => $client_type,
        'health' => $health_filter,
    ];
    if ($is_admin) {
        $base_params['advisor'] = $advisor_id;
    }
    if ($hide_empty_columns) {
        $base_params['hide_empty_columns'] = 1;
    }
    if ($active_view_id !== '') {
        $base_params['view_id'] = $active_view_id;
    }

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-pipeline-bulk" id="peracrm-pipeline-bulk">';
    echo '<input type="hidden" name="action" value="peracrm_pipeline_bulk_action" />';
    wp_nonce_field('peracrm_pipeline_bulk_action');
    foreach ($base_params as $param_key => $param_value) {
        echo '<input type="hidden" name="' . esc_attr($param_key) . '" value="' . esc_attr($param_value) . '" />';
    }
    echo '<label for="peracrm-bulk-action">Bulk action</label>';
    echo '<select name="bulk_action" id="peracrm-bulk-action">';
    echo '<option value="move_stage">' . esc_html('Move stage') . '</option>';
    if ($can_reassign) {
        echo '<option value="reassign_advisor">' . esc_html('Reassign advisor') . '</option>';
    }
    echo '<option value="add_reminder">' . esc_html('Add reminder') . '</option>';
    echo '</select>';

    echo '<label for="peracrm-bulk-stage" class="screen-reader-text">Stage</label>';
    echo '<select name="to_status" id="peracrm-bulk-stage">';
    foreach ($statuses as $status_key => $status_label) {
        printf(
            '<option value="%1$s">%2$s</option>',
            esc_attr($status_key),
            esc_html($status_label)
        );
    }
    echo '</select>';

    if ($can_reassign) {
        echo '<label for="peracrm-bulk-advisor" class="screen-reader-text">Advisor</label>';
        echo '<select name="advisor_user_id" id="peracrm-bulk-advisor">';
        echo '<option value="0">' . esc_html('Select advisor') . '</option>';
        foreach ($advisor_options as $advisor) {
            printf(
                '<option value="%1$d">%2$s</option>',
                (int) $advisor->ID,
                esc_html($advisor->display_name)
            );
        }
        echo '</select>';
    }

    echo '<label for="peracrm-bulk-due-at" class="screen-reader-text">Reminder due at</label>';
    echo '<input type="datetime-local" name="bulk_due_at" id="peracrm-bulk-due-at" />';
    echo '<label for="peracrm-bulk-note" class="screen-reader-text">Reminder note</label>';
    echo '<input type="text" name="bulk_note" id="peracrm-bulk-note" maxlength="200" placeholder="' . esc_attr('Reminder note') . '" />';
    if ($is_admin) {
        echo '<label for="peracrm-bulk-reminder-advisor" class="screen-reader-text">Reminder advisor</label>';
        echo '<select name="reminder_advisor_user_id" id="peracrm-bulk-reminder-advisor">';
        echo '<option value="0">' . esc_html('Assign to me') . '</option>';
        foreach ($advisor_options as $advisor) {
            printf(
                '<option value="%1$d">%2$s</option>',
                (int) $advisor->ID,
                esc_html($advisor->display_name)
            );
        }
        echo '</select>';
    }
    echo '<button type="submit" class="button button-primary">Apply</button>';
    echo '</form>';

    echo '<div class="peracrm-pipeline-board">';
    foreach ($columns as $status_key => $column) {
        $label = $column['label'];
        $ids = $column['display_ids'];
        $paged_param = $column['paged_param'];
        $paged = $column['paged'];

        if ($hide_empty_columns && empty($ids)) {
            continue;
        }

        echo '<div class="peracrm-pipeline-column">';
        echo '<div class="peracrm-pipeline-column__header">' . esc_html($label) . '</div>';
        echo '<label class="peracrm-pipeline-column__select">';
        echo '<input type="checkbox" class="peracrm-pipeline-select-all" data-status="' . esc_attr($status_key) . '" form="peracrm-pipeline-bulk" />';
        echo '<span>' . esc_html('Select all in column') . '</span>';
        echo '</label>';

        if (empty($ids)) {
            echo '<p class="peracrm-empty">No clients found.</p>';
        } else {
            foreach ($ids as $client_id) {
                $client_title = get_the_title($client_id);
                $view_link = function_exists('peracrm_render_client_view_page')
                    ? add_query_arg(
                        [
                            'page' => 'peracrm-client-view',
                            'client_id' => $client_id,
                        ],
                        admin_url('admin.php')
                    )
                    : '';
                $edit_link = get_edit_post_link($client_id, '');
                $client_link = $view_link ?: $edit_link;

                $health = isset($health_map[$client_id]) ? $health_map[$client_id] : [];
                $badge = function_exists('peracrm_client_health_badge_html')
                    ? peracrm_client_health_badge_html($health)
                    : esc_html(isset($health['label']) ? $health['label'] : 'None');
                $last_activity_ts = $has_activity_table && isset($health['last_activity_ts']) ? (int) $health['last_activity_ts'] : 0;
                $last_activity = $last_activity_ts
                    ? human_time_diff($last_activity_ts, $now_ts) . ' ago'
                    : '—';

                $open = isset($open_counts[$client_id]) ? (int) $open_counts[$client_id] : 0;
                $overdue = isset($overdue_counts[$client_id]) ? (int) $overdue_counts[$client_id] : 0;
                $next_due = isset($next_due_map[$client_id]) ? $next_due_map[$client_id] : '';
                $next_due_label = '—';
                $due_ts = 0;
                if ($next_due) {
                    $due_ts = strtotime($next_due);
                    if ($due_ts) {
                        $relative = human_time_diff($due_ts, $now_ts);
                        $suffix = $due_ts < $now_ts ? 'ago' : 'from now';
                        $next_due_label = sprintf(
                            '%s (%s %s)',
                            esc_html(mysql2date('Y-m-d', $next_due)),
                            esc_html($relative),
                            esc_html($suffix)
                        );
                    }
                }

                $hints = [];
                if ($overdue > 0) {
                    $hints[] = ['label' => 'Overdue', 'class' => 'overdue'];
                }
                $due_soon_limit = $now_ts + (7 * DAY_IN_SECONDS);
                if ($overdue === 0 && $open > 0 && $due_ts && $due_ts >= $now_ts && $due_ts <= $due_soon_limit) {
                    $hints[] = ['label' => 'Due soon', 'class' => 'due-soon'];
                }
                if ($has_activity_table && $last_activity_ts > 0 && $last_activity_ts < ($now_ts - (30 * DAY_IN_SECONDS))) {
                    $hints[] = ['label' => 'No activity', 'class' => 'no-activity'];
                }
                if ($has_activity_table && $status_key === 'enquiry' && $last_activity_ts === 0 && $open === 0 && $overdue === 0) {
                    $hints[] = ['label' => 'New enquiry', 'class' => 'new-enquiry'];
                }

                echo '<div class="peracrm-pipeline-card">';
                echo '<div class="peracrm-pipeline-card__select">';
                echo '<input type="checkbox" class="peracrm-pipeline-select" data-status="' . esc_attr($status_key) . '" form="peracrm-pipeline-bulk" name="client_ids[]" value="' . esc_attr($client_id) . '" />';
                echo '</div>';
                echo '<div class="peracrm-pipeline-card__title">';
                if ($client_link) {
                    echo '<a href="' . esc_url($client_link) . '">' . esc_html($client_title) . '</a>';
                } else {
                    echo esc_html($client_title);
                }
                echo '</div>';
                if (!empty($hints)) {
                    echo '<div class="peracrm-pipeline-card__hints">';
                    foreach ($hints as $hint) {
                        printf(
                            '<span class="peracrm-pipeline-hint peracrm-pipeline-hint--%1$s">%2$s</span>',
                            esc_attr($hint['class']),
                            esc_html($hint['label'])
                        );
                    }
                    echo '</div>';
                }
                echo '<div class="peracrm-pipeline-card__meta">';
                echo '<div><strong>Health:</strong> ' . $badge . '</div>';
                echo '<div><strong>Last activity:</strong> ' . esc_html($last_activity) . '</div>';
                echo '<div><strong>Open reminders:</strong> ' . esc_html($open) . '</div>';
                echo '<div><strong>Overdue reminders:</strong> ' . esc_html($overdue) . '</div>';
                echo '<div><strong>Next due:</strong> ' . $next_due_label . '</div>';
                if ($is_admin) {
                    $assigned_id = function_exists('peracrm_client_get_assigned_advisor_id')
                        ? (int) peracrm_client_get_assigned_advisor_id($client_id)
                        : 0;
                    $assigned_label = $assigned_id > 0 && isset($assigned_advisors[$assigned_id])
                        ? $assigned_advisors[$assigned_id]
                        : '—';
                    echo '<div><strong>Advisor:</strong> ' . esc_html($assigned_label) . '</div>';
                }
                echo '</div>';
                $can_override = current_user_can('manage_options') || current_user_can('peracrm_manage_assignments');
                $assigned_id = function_exists('peracrm_client_get_assigned_advisor_id')
                    ? (int) peracrm_client_get_assigned_advisor_id($client_id)
                    : 0;
                $is_assigned_advisor = $assigned_id > 0 && $assigned_id === get_current_user_id();
                $can_move = current_user_can('edit_post', $client_id) && ($can_override || $is_assigned_advisor);
                if ($can_move) {
                    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-pipeline-card__move">';
                    echo '<input type="hidden" name="action" value="peracrm_pipeline_move_stage" />';
                    wp_nonce_field('peracrm_pipeline_move_stage');
                    echo '<input type="hidden" name="client_id" value="' . esc_attr($client_id) . '" />';
                    echo '<input type="hidden" name="from_status" value="' . esc_attr($status_key) . '" />';
                    foreach ($base_params as $param_key => $param_value) {
                        echo '<input type="hidden" name="' . esc_attr($param_key) . '" value="' . esc_attr($param_value) . '" />';
                    }
                    echo '<label class="screen-reader-text" for="peracrm-move-' . esc_attr($client_id) . '">Move to</label>';
                    echo '<select name="to_status" id="peracrm-move-' . esc_attr($client_id) . '">';
                    foreach ($statuses as $move_key => $move_label) {
                        if ($move_key === $status_key) {
                            continue;
                        }
                        printf(
                            '<option value="%1$s">%2$s</option>',
                            esc_attr($move_key),
                            esc_html($move_label)
                        );
                    }
                    echo '</select>';
                    echo '<button type="submit" class="button button-small">Move</button>';
                    echo '</form>';
                }
                echo '</div>';
            }
        }

        $total_pages = (int) $column['query']->max_num_pages;
        if ($total_pages > 1) {
            $page_links = paginate_links([
                'base' => add_query_arg(
                    array_merge($base_params, [$paged_param => '%#%']),
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
    echo '</div>';
    echo '<script>
    document.addEventListener("DOMContentLoaded", function () {
        var bulkSelects = document.querySelectorAll(".peracrm-pipeline-select-all");
        bulkSelects.forEach(function (toggle) {
            toggle.addEventListener("change", function () {
                var status = toggle.getAttribute("data-status");
                var checkboxes = document.querySelectorAll(".peracrm-pipeline-select[data-status=\'" + status + "\']");
                checkboxes.forEach(function (box) {
                    box.checked = toggle.checked;
                });
            });
        });
    });
    </script>';
    echo '</div>';
}
