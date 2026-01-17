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
