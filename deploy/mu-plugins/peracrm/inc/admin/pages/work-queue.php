<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_admin_work_queue_assigned_meta_keys()
{
    return ['assigned_advisor_user_id', 'crm_assigned_advisor'];
}

function peracrm_admin_work_queue_get_assigned_advisor_id($client_id)
{
    if (function_exists('peracrm_client_get_assigned_advisor_id')) {
        return (int) peracrm_client_get_assigned_advisor_id($client_id);
    }

    return 0;
}

function peracrm_admin_work_queue_bucket_client_ids($bucket, $advisor_id, $has_activity_table, $has_reminders_table)
{
    global $wpdb;

    $bucket = sanitize_key($bucket);
    $advisor_id = (int) $advisor_id;
    if ('unassigned' === $bucket) {
        $advisor_id = 0;
    }

    $needs_activity = in_array($bucket, ['cold', 'hot_no_tasks'], true);
    $needs_reminders = in_array($bucket, ['at_risk', 'due_soon', 'cold', 'hot_no_tasks'], true);

    if ($needs_activity && !$has_activity_table) {
        return [];
    }

    if ($needs_reminders && !$has_reminders_table) {
        return [];
    }

    $posts_table = $wpdb->posts;
    $meta_table = $wpdb->postmeta;
    $joins = '';
    $where = [
        "p.post_type = 'crm_client'",
        "p.post_status <> 'trash'",
    ];

    $meta_keys = peracrm_admin_work_queue_assigned_meta_keys();
    $meta_placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));

    if ($advisor_id > 0) {
        $where[] = $wpdb->prepare(
            "EXISTS (SELECT 1 FROM {$meta_table} AS pm WHERE pm.post_id = p.ID AND pm.meta_key IN ({$meta_placeholders}) AND pm.meta_value = %d)",
            array_merge($meta_keys, [$advisor_id])
        );
    } elseif ('unassigned' === $bucket) {
        $where[] = $wpdb->prepare(
            "NOT EXISTS (SELECT 1 FROM {$meta_table} AS pm WHERE pm.post_id = p.ID AND pm.meta_key IN ({$meta_placeholders}) AND CAST(pm.meta_value AS UNSIGNED) > 0)",
            $meta_keys
        );
    }

    if ('at_risk' === $bucket) {
        $reminders_table = peracrm_table('crm_reminders');
        $now_mysql = current_time('mysql');
        $reminders_sub = $wpdb->prepare(
            "SELECT DISTINCT client_id FROM {$reminders_table} WHERE status = %s AND due_at < %s",
            'pending',
            $now_mysql
        );
        $joins .= " INNER JOIN ({$reminders_sub}) AS r ON r.client_id = p.ID";
    } elseif ('due_soon' === $bucket) {
        $reminders_table = peracrm_table('crm_reminders');
        $now_ts = current_time('timestamp');
        $now_mysql = wp_date('Y-m-d H:i:s', $now_ts);
        $future_mysql = wp_date('Y-m-d H:i:s', $now_ts + DAY_IN_SECONDS * 7);
        $reminders_sub = $wpdb->prepare(
            "SELECT DISTINCT client_id FROM {$reminders_table} WHERE status = %s AND due_at >= %s AND due_at <= %s",
            'pending',
            $now_mysql,
            $future_mysql
        );
        $joins .= " INNER JOIN ({$reminders_sub}) AS r ON r.client_id = p.ID";
    } elseif ('hot_no_tasks' === $bucket) {
        $activity_table = peracrm_table('crm_activity');
        $reminders_table = peracrm_table('crm_reminders');
        $now_ts = current_time('timestamp');
        $seven_days = wp_date('Y-m-d H:i:s', $now_ts - DAY_IN_SECONDS * 7);
        $now_mysql = wp_date('Y-m-d H:i:s', $now_ts);
        $activity_sub = "SELECT client_id, MAX(created_at) AS last_activity_at FROM {$activity_table} GROUP BY client_id";
        $health_reminders_sub = $wpdb->prepare(
            "(SELECT client_id,
                     SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) AS open_count,
                     SUM(CASE WHEN status = %s AND due_at < %s THEN 1 ELSE 0 END) AS overdue_count
              FROM {$reminders_table}
              GROUP BY client_id)",
            'pending',
            'pending',
            $now_mysql
        );
        $joins .= " LEFT JOIN ({$activity_sub}) AS a ON a.client_id = p.ID";
        $joins .= " LEFT JOIN {$health_reminders_sub} AS r ON r.client_id = p.ID";

        $scope_reminders_sub = $health_reminders_sub;
        if ($advisor_id > 0) {
            $scope_reminders_sub = $wpdb->prepare(
                "(SELECT client_id,
                         SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) AS open_count,
                         SUM(CASE WHEN status = %s AND due_at < %s THEN 1 ELSE 0 END) AS overdue_count
                  FROM {$reminders_table}
                  WHERE advisor_user_id = %d
                  GROUP BY client_id)",
                'pending',
                'pending',
                $now_mysql,
                $advisor_id
            );
        }
        $joins .= " LEFT JOIN {$scope_reminders_sub} AS rs ON rs.client_id = p.ID";

        $where[] = $wpdb->prepare('a.last_activity_at >= %s', $seven_days);
        $where[] = 'COALESCE(r.open_count, 0) > 0';
        $where[] = 'COALESCE(r.overdue_count, 0) = 0';
        $where[] = 'COALESCE(rs.open_count, 0) = 0';
        $where[] = 'COALESCE(rs.overdue_count, 0) = 0';
    } elseif ('cold' === $bucket) {
        $activity_table = peracrm_table('crm_activity');
        $now_ts = current_time('timestamp');
        $thirty_days = wp_date('Y-m-d H:i:s', $now_ts - DAY_IN_SECONDS * 30);
        $activity_sub = "SELECT client_id, MAX(created_at) AS last_activity_at FROM {$activity_table} GROUP BY client_id";
        $joins .= " LEFT JOIN ({$activity_sub}) AS a ON a.client_id = p.ID";

        $reminders_table = peracrm_table('crm_reminders');
        $now_mysql = wp_date('Y-m-d H:i:s', $now_ts);
        $reminders_sub = $wpdb->prepare(
            "(SELECT client_id,
                     SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) AS open_count,
                     SUM(CASE WHEN status = %s AND due_at < %s THEN 1 ELSE 0 END) AS overdue_count
              FROM {$reminders_table}
              GROUP BY client_id)",
            'pending',
            'pending',
            $now_mysql
        );
        $joins .= " LEFT JOIN {$reminders_sub} AS r ON r.client_id = p.ID";
        $where[] = $wpdb->prepare(
            '(a.last_activity_at < %s OR (a.last_activity_at IS NULL AND COALESCE(r.open_count, 0) > 0 AND COALESCE(r.overdue_count, 0) = 0))',
            $thirty_days
        );
    }

    $sql = "SELECT DISTINCT p.ID FROM {$posts_table} AS p {$joins} WHERE " . implode(' AND ', $where);

    $client_ids = $wpdb->get_col($sql);
    return array_values(array_map('intval', (array) $client_ids));
}

function peracrm_render_work_queue_page()
{
    if (!peracrm_admin_user_can_manage()) {
        wp_die('Unauthorized');
    }

    $is_admin = current_user_can('manage_options');
    $advisor_id = $is_admin ? absint($_GET['advisor'] ?? 0) : get_current_user_id();
    $advisor_id = $advisor_id > 0 ? $advisor_id : 0;
    if (!$is_admin) {
        $advisor_id = get_current_user_id();
    }

    $bucket = isset($_GET['bucket']) ? sanitize_key(wp_unslash($_GET['bucket'])) : 'at_risk';
    $allowed_buckets = [
        'at_risk' => 'At risk',
        'due_soon' => 'Due soon',
        'cold' => 'Cold',
        'hot_no_tasks' => 'Hot / no tasks',
    ];
    if ($is_admin) {
        $allowed_buckets['unassigned'] = 'Unassigned';
    }
    if (!isset($allowed_buckets[$bucket])) {
        $bucket = 'at_risk';
    }
    if ('unassigned' === $bucket && $is_admin) {
        $advisor_id = 0;
    }

    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $per_page = 20;

    $has_activity_table = function_exists('peracrm_activity_table_exists') && peracrm_activity_table_exists();
    $has_reminders_table = function_exists('peracrm_reminders_table_exists') && peracrm_reminders_table_exists();

    $missing_signals = [];
    if (!$has_activity_table) {
        $missing_signals[] = 'activity';
    }
    if (!$has_reminders_table) {
        $missing_signals[] = 'reminders';
    }

    echo '<div class="wrap">';
    echo '<h1>Work Queue</h1>';

    if (!empty($missing_signals)) {
        echo '<div class="notice notice-warning is-dismissible"><p>';
        echo esc_html('Some signals are unavailable: ' . implode(', ', $missing_signals) . '. Buckets that depend on missing data will be empty.');
        echo '</p></div>';
    }

    $bucket_client_ids = peracrm_admin_work_queue_bucket_client_ids(
        $bucket,
        $advisor_id,
        $has_activity_table,
        $has_reminders_table
    );

    $query_args = [
        'post_type' => 'crm_client',
        'post_status' => 'any',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'orderby' => 'title',
        'order' => 'ASC',
        'post__in' => !empty($bucket_client_ids) ? $bucket_client_ids : [0],
    ];

    if ($search !== '') {
        $query_args['s'] = $search;
        $query_args['search_columns'] = ['post_title'];
    }

    $query = new WP_Query($query_args);

    $client_ids = [];
    foreach ($query->posts as $post) {
        if ($post instanceof WP_Post) {
            $client_ids[] = (int) $post->ID;
        }
    }

    if (!empty($client_ids)) {
        update_meta_cache('post', $client_ids);
        if (function_exists('peracrm_client_health_prime_cache')) {
            peracrm_client_health_prime_cache($client_ids);
        }
    }

    $reminder_scope = $advisor_id > 0 ? $advisor_id : null;
    $reminder_counts = function_exists('peracrm_reminders_counts_by_client_ids')
        ? peracrm_reminders_counts_by_client_ids($client_ids, $reminder_scope)
        : ['open_count' => [], 'overdue_count' => [], 'next_due' => []];
    $open_counts = isset($reminder_counts['open_count']) ? $reminder_counts['open_count'] : [];
    $overdue_counts = isset($reminder_counts['overdue_count']) ? $reminder_counts['overdue_count'] : [];
    $next_due_map = isset($reminder_counts['next_due']) ? $reminder_counts['next_due'] : [];

    $advisor_map = [];
    if ($is_admin && !empty($client_ids)) {
        $advisor_ids = [];
        foreach ($client_ids as $client_id) {
            $assigned = peracrm_admin_work_queue_get_assigned_advisor_id($client_id);
            if ($assigned > 0) {
                $advisor_ids[] = $assigned;
            }
        }
        $advisor_ids = array_values(array_unique($advisor_ids));
        if (!empty($advisor_ids)) {
            $advisors = get_users([
                'include' => $advisor_ids,
                'fields' => ['ID', 'display_name'],
            ]);
            foreach ($advisors as $advisor) {
                $advisor_map[(int) $advisor->ID] = $advisor->display_name;
            }
        }
    }

    $base_params = [
        'post_type' => 'crm_client',
        'page' => 'peracrm-work-queue',
        's' => $search,
    ];
    if ($is_admin) {
        $base_params['advisor'] = $advisor_id;
    }

    echo '<h2 class="nav-tab-wrapper">';
    foreach ($allowed_buckets as $key => $label) {
        $url = add_query_arg(array_merge($base_params, ['bucket' => $key]), admin_url('edit.php'));
        $class = $key === $bucket ? 'nav-tab nav-tab-active' : 'nav-tab';
        printf(
            '<a class="%1$s" href="%2$s">%3$s</a>',
            esc_attr($class),
            esc_url($url),
            esc_html($label)
        );
    }
    echo '</h2>';

    echo '<form method="get" class="peracrm-filters">';
    echo '<input type="hidden" name="post_type" value="crm_client" />';
    echo '<input type="hidden" name="page" value="peracrm-work-queue" />';
    echo '<input type="hidden" name="bucket" value="' . esc_attr($bucket) . '" />';

    if ($is_admin) {
        $advisor_options = get_users([
            'fields' => ['ID', 'display_name'],
            'capability' => 'edit_crm_clients',
            'orderby' => 'display_name',
            'order' => 'ASC',
        ]);
        echo '<label for="peracrm-work-queue-advisor" class="screen-reader-text">Advisor</label>';
        echo '<select name="advisor" id="peracrm-work-queue-advisor">';
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

    echo '<label for="peracrm-work-queue-search" class="screen-reader-text">Search clients</label>';
    echo '<input type="search" name="s" id="peracrm-work-queue-search" value="' . esc_attr($search) . '" />';
    echo '<button type="submit" class="button">Filter</button>';
    echo '</form>';

    if (empty($query->posts)) {
        echo '<p class="peracrm-empty">No clients found.</p>';
        echo '</div>';
        return;
    }

    $now_ts = current_time('timestamp');
    $reminders_url = add_query_arg(
        [
            'post_type' => 'crm_client',
            'page' => 'peracrm-my-reminders',
        ],
        admin_url('edit.php')
    );

    echo '<table class="widefat fixed striped">';
    echo '<thead><tr>';
    echo '<th>Client</th><th>Health</th><th>Last activity</th><th>Open</th><th>Overdue</th><th>Next due</th>';
    if ($is_admin) {
        echo '<th>Assigned advisor</th>';
    }
    echo '<th>Actions</th>';
    echo '</tr></thead><tbody>';

    foreach ($query->posts as $post) {
        $client_id = (int) $post->ID;
        $client_link = get_edit_post_link($client_id, '');
        $view_link = add_query_arg(
            [
                'page' => 'peracrm-client-view',
                'client_id' => $client_id,
            ],
            admin_url('admin.php')
        );
        $health = function_exists('peracrm_client_health_get') ? peracrm_client_health_get($client_id) : [];
        $badge = function_exists('peracrm_client_health_badge_html')
            ? peracrm_client_health_badge_html($health)
            : esc_html(isset($health['label']) ? $health['label'] : 'None');
        $last_activity_ts = isset($health['last_activity_ts']) ? (int) $health['last_activity_ts'] : 0;
        $last_activity = $last_activity_ts
            ? human_time_diff($last_activity_ts, $now_ts) . ' ago'
            : '—';

        $open = isset($open_counts[$client_id]) ? (int) $open_counts[$client_id] : 0;
        $overdue = isset($overdue_counts[$client_id]) ? (int) $overdue_counts[$client_id] : 0;
        $next_due = isset($next_due_map[$client_id]) ? $next_due_map[$client_id] : '';
        $next_due_label = '—';
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

        echo '<tr>';
        echo '<td>';
        if ($client_link) {
            echo '<a href="' . esc_url($client_link) . '">' . esc_html(get_the_title($client_id)) . '</a>';
        } else {
            echo esc_html(get_the_title($client_id));
        }
        echo '</td>';
        echo '<td>' . $badge . '</td>';
        echo '<td>' . esc_html($last_activity) . '</td>';
        echo '<td>' . esc_html($open) . '</td>';
        echo '<td>' . esc_html($overdue) . '</td>';
        echo '<td>' . $next_due_label . '</td>';
        if ($is_admin) {
            $assigned = peracrm_admin_work_queue_get_assigned_advisor_id($client_id);
            $assigned_label = $assigned > 0 && isset($advisor_map[$assigned]) ? $advisor_map[$assigned] : '—';
            echo '<td>' . esc_html($assigned_label) . '</td>';
        }
        echo '<td>';
        echo '<a href="' . esc_url($view_link) . '">View</a>';
        if ($client_link) {
            echo ' | <a href="' . esc_url($client_link) . '">Edit</a>';
        }
        echo ' | <a href="' . esc_url($reminders_url) . '">View reminders</a>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    $total_pages = (int) $query->max_num_pages;
    if ($total_pages > 1) {
        $page_links = paginate_links([
            'base' => add_query_arg(
                array_merge($base_params, ['bucket' => $bucket, 'paged' => '%#%']),
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
