<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_now_mysql()
{
    return current_time('mysql');
}

function peracrm_table($suffix)
{
    global $wpdb;

    return $wpdb->prefix . $suffix;
}

function peracrm_json_encode($data)
{
    $encoded = wp_json_encode($data);
    if (false === $encoded || null === $encoded) {
        return '{}';
    }

    return $encoded;
}

function peracrm_json_decode($json)
{
    if (!is_string($json) || $json === '') {
        return [];
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return [];
    }

    return $decoded;
}

function peracrm_client_get_assigned_advisor_id($client_id)
{
    $client_id = (int) $client_id;
    if ($client_id <= 0) {
        return 0;
    }

    if (function_exists('peracrm_enquiry_get_assigned_advisor_id')) {
        return (int) peracrm_enquiry_get_assigned_advisor_id($client_id);
    }

    $advisor_id = (int) get_post_meta($client_id, 'assigned_advisor_user_id', true);
    if ($advisor_id > 0) {
        return $advisor_id;
    }

    return (int) get_post_meta($client_id, 'crm_assigned_advisor', true);
}
