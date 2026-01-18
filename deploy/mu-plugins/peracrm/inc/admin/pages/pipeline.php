<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_pipeline_status_labels()
{
    return [
        'enquiry' => 'Enquiry',
        'active' => 'Active',
        'dormant' => 'Dormant',
        'closed' => 'Closed',
    ];
}

function peracrm_pipeline_client_type_options()
{
    return [
        'all' => 'All types',
        'citizenship' => 'Citizenship',
        'investor' => 'Investor',
        'lifestyle' => 'Lifestyle',
    ];
}

function peracrm_pipeline_health_options()
{
    return [
        'all' => 'All health',
        'hot' => 'Hot',
        'warm' => 'Warm',
        'cold' => 'Cold',
        'at_risk' => 'At risk',
        'none' => 'None',
    ];
}

function peracrm_pipeline_assigned_meta_keys()
{
    if (function_exists('peracrm_admin_work_queue_assigned_meta_keys')) {
        return peracrm_admin_work_queue_assigned_meta_keys();
    }

    return ['assigned_advisor_user_id', 'crm_assigned_advisor'];
}

function peracrm_render_pipeline_page()
{
    if (!peracrm_admin_user_can_manage()) {
        wp_die('Unauthorized');
    }

    $is_admin = current_user_can('manage_options');
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
    if ($is_admin) {
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

    echo '<div class="wrap peracrm-pipeline">';
    echo '<h1>Pipeline</h1>';

    if (!$has_reminders_table) {
        echo '<div class="notice notice-warning is-dismissible"><p>';
        echo esc_html('Reminders data unavailable. Counts will display as 0 or —.');
        echo '</p></div>';
    }

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

    $columns = [];
    $all_query_ids = [];
    $meta_keys = peracrm_pipeline_assigned_meta_keys();

    foreach ($statuses as $status_key => $status_label) {
        $paged_param = 'paged_' . $status_key;
        $paged = isset($_GET[$paged_param]) ? max(1, absint($_GET[$paged_param])) : 1;

        $meta_query = [
            'relation' => 'AND',
            [
                'key' => '_peracrm_status',
                'value' => $status_key,
                'compare' => '=',
            ],
        ];

        if ($client_type !== 'all') {
            $meta_query[] = [
                'key' => '_peracrm_client_type',
                'value' => $client_type,
                'compare' => '=',
            ];
        }

        if ($scope_advisor_id > 0 && !empty($meta_keys)) {
            $assigned_query = ['relation' => 'OR'];
            foreach ($meta_keys as $meta_key) {
                $assigned_query[] = [
                    'key' => $meta_key,
                    'value' => $scope_advisor_id,
                    'compare' => '=',
                ];
            }
            $meta_query[] = $assigned_query;
        }

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
    echo '</div>';
}
