<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_admin_is_crm_client_screen($hook)
{
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
        return false;
    }

    $screen = get_current_screen();
    if (!$screen) {
        return false;
    }

    return $screen->post_type === 'crm_client';
}

function peracrm_admin_enqueue_assets($hook)
{
    if (!peracrm_admin_is_crm_client_screen($hook) && !peracrm_admin_is_my_reminders_screen($hook)) {
        return;
    }

    wp_enqueue_style(
        'peracrm-admin',
        plugins_url('assets/admin.css', PERACRM_PATH . '/peracrm.php'),
        [],
        PERACRM_VERSION
    );
}
