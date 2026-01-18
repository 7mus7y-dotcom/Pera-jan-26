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
add_action('admin_post_peracrm_update_reminder_status', 'peracrm_handle_update_reminder_status');
add_action('admin_post_peracrm_link_user', 'peracrm_handle_link_user');
add_action('admin_post_peracrm_unlink_user', 'peracrm_handle_unlink_user');
add_action('admin_post_peracrm_save_client_profile', 'peracrm_handle_save_client_profile');
add_action('admin_post_peracrm_reassign_client_advisor', 'peracrm_handle_reassign_client_advisor');
add_action('admin_post_peracrm_pipeline_save_view', 'peracrm_handle_pipeline_save_view');
add_action('admin_post_peracrm_pipeline_delete_view', 'peracrm_handle_pipeline_delete_view');
add_action('admin_post_peracrm_pipeline_move_stage', 'peracrm_handle_pipeline_move_stage');
add_action('admin_post_peracrm_pipeline_bulk_action', 'peracrm_handle_pipeline_bulk_action');
add_action('admin_post_peracrm_pipeline_export_csv', 'peracrm_handle_pipeline_export_csv');

add_action('admin_notices', 'peracrm_admin_notices');
add_action('admin_enqueue_scripts', 'peracrm_admin_enqueue_assets');

add_filter('manage_crm_client_posts_columns', 'peracrm_admin_add_client_columns');
add_action('manage_crm_client_posts_custom_column', 'peracrm_admin_render_client_columns', 10, 2);
add_filter('manage_edit-crm_client_sortable_columns', 'peracrm_admin_client_sortable_columns');
add_action('restrict_manage_posts', 'peracrm_admin_client_filters');
add_action('pre_get_posts', 'peracrm_admin_client_list_query');
add_filter('posts_clauses', 'peracrm_admin_client_list_clauses', 10, 2);
add_filter('posts_results', 'peracrm_admin_prime_client_health_cache', 10, 2);
