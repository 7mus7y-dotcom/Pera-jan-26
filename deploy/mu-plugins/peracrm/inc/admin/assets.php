<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_admin_is_crm_client_screen($hook)
{
    if (!in_array($hook, ['post.php', 'post-new.php', 'edit.php'], true)) {
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
    if (!peracrm_admin_is_crm_client_screen($hook)
        && !peracrm_admin_is_my_reminders_screen($hook)
        && !peracrm_admin_is_pipeline_screen($hook)
    ) {
        return;
    }

    $admin_css_path = PERACRM_PATH . '/assets/admin.css';
    $version = defined('PERACRM_VERSION') ? PERACRM_VERSION : (file_exists($admin_css_path) ? filemtime($admin_css_path) : null);

    wp_enqueue_style(
        'peracrm-admin',
        plugins_url('peracrm/assets/admin.css', PERACRM_MAIN_FILE),
        [],
        $version
    );
}
