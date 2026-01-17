<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_log_event($client_id, $event_type, array $payload = [])
{
    $payload['ts'] = peracrm_now_mysql();

    if (function_exists('peracrm_activity_log')) {
        return peracrm_activity_log($client_id, $event_type, $payload);
    }

    return peracrm_activity_insert($client_id, $event_type, $payload);
}
