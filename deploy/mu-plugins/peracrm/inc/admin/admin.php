<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once PERACRM_INC . '/admin/actions.php';
require_once PERACRM_INC . '/admin/metaboxes.php';
require_once PERACRM_INC . '/admin/pages.php';
require_once PERACRM_INC . '/admin/assets.php';

add_action('admin_menu', 'peracrm_register_admin_menu');
add_action('add_meta_boxes', 'peracrm_register_metaboxes', 10, 2);

add_action('admin_post_peracrm_add_note', 'peracrm_handle_add_note');
add_action('admin_post_peracrm_add_reminder', 'peracrm_handle_add_reminder');
add_action('admin_post_peracrm_mark_reminder_done', 'peracrm_handle_mark_reminder_done');
add_action('admin_post_peracrm_link_user', 'peracrm_handle_link_user');
add_action('admin_post_peracrm_unlink_user', 'peracrm_handle_unlink_user');

add_action('admin_notices', 'peracrm_admin_notices');
add_action('admin_enqueue_scripts', 'peracrm_admin_enqueue_assets');

add_filter('manage_crm_client_posts_columns', 'peracrm_admin_add_client_columns');
add_action('manage_crm_client_posts_custom_column', 'peracrm_admin_render_client_columns', 10, 2);
add_filter('manage_edit-crm_client_sortable_columns', 'peracrm_admin_client_sortable_columns');
add_action('restrict_manage_posts', 'peracrm_admin_client_filters');
add_action('pre_get_posts', 'peracrm_admin_client_list_query');
add_filter('posts_clauses', 'peracrm_admin_client_list_clauses', 10, 2);
