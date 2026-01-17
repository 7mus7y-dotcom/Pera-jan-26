<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once PERACRM_INC . '/admin/actions.php';
require_once PERACRM_INC . '/admin/metaboxes.php';
require_once PERACRM_INC . '/admin/pages.php';
require_once PERACRM_INC . '/admin/assets.php';

add_action('admin_menu', 'peracrm_register_admin_menu');
add_action('add_meta_boxes', 'peracrm_register_metaboxes');

add_action('admin_post_peracrm_add_note', 'peracrm_handle_add_note');
add_action('admin_post_peracrm_add_reminder', 'peracrm_handle_add_reminder');
add_action('admin_post_peracrm_mark_reminder_done', 'peracrm_handle_mark_reminder_done');

add_action('admin_notices', 'peracrm_admin_notices');
add_action('admin_enqueue_scripts', 'peracrm_admin_enqueue_assets');
