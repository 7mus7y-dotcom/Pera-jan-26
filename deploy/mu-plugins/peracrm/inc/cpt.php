<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_register_cpt_crm_client()
{
    $labels = [
        'name' => 'CRM Clients',
        'singular_name' => 'CRM Client',
    ];

    register_post_type('crm_client', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-id',
        'supports' => ['title'],
        'show_in_rest' => true,
        'capability_type' => ['crm_client', 'crm_clients'],
        'map_meta_cap' => true,
        'has_archive' => false,
        'rewrite' => false,
    ]);
}
