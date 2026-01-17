<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_register_metaboxes()
{
    add_meta_box(
        'peracrm_notes',
        'CRM Notes',
        'peracrm_render_notes_metabox',
        'crm_client',
        'normal',
        'default'
    );

    add_meta_box(
        'peracrm_reminders',
        'CRM Reminders',
        'peracrm_render_reminders_metabox',
        'crm_client',
        'normal',
        'default'
    );

    add_meta_box(
        'peracrm_activity',
        'CRM Activity',
        'peracrm_render_activity_metabox',
        'crm_client',
        'normal',
        'default'
    );

    add_meta_box(
        'peracrm_properties',
        'Linked Properties',
        'peracrm_render_properties_metabox',
        'crm_client',
        'side',
        'default'
    );
}

function peracrm_render_notes_metabox($post)
{
    if (!peracrm_admin_user_can_manage()) {
        echo '<p>You do not have permission to view CRM notes.</p>';
        return;
    }

    $notes = peracrm_notes_list($post->ID, 20);

    echo '<div class="peracrm-metabox">';

    if (empty($notes)) {
        echo '<p class="peracrm-empty">No notes yet.</p>';
    } else {
        echo '<ul class="peracrm-list">';
        foreach ($notes as $note) {
            $author = get_userdata((int) $note['advisor_user_id']);
            $author_name = $author ? $author->display_name : 'Advisor';
            printf(
                '<li><div class="peracrm-list__meta">%1$s · %2$s</div><div class="peracrm-list__body">%3$s</div></li>',
                esc_html(mysql2date('Y-m-d H:i', $note['created_at'])),
                esc_html($author_name),
                esc_html($note['note_body'])
            );
        }
        echo '</ul>';
    }

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-form">';
    wp_nonce_field('peracrm_add_note');
    echo '<input type="hidden" name="action" value="peracrm_add_note" />';
    echo '<input type="hidden" name="peracrm_client_id" value="' . esc_attr($post->ID) . '" />';
    echo '<p><label for="peracrm_note_body">Add note</label></p>';
    echo '<p><textarea name="peracrm_note_body" id="peracrm_note_body" rows="4" class="widefat"></textarea></p>';
    echo '<p><button type="submit" class="button button-primary">Add Note</button></p>';
    echo '</form>';

    echo '</div>';
}

function peracrm_render_reminders_metabox($post)
{
    if (!peracrm_admin_user_can_manage()) {
        echo '<p>You do not have permission to view CRM reminders.</p>';
        return;
    }

    $reminders = peracrm_admin_get_client_reminders($post->ID, 20);

    echo '<div class="peracrm-metabox">';

    if (empty($reminders)) {
        echo '<p class="peracrm-empty">No pending reminders.</p>';
    } else {
        echo '<ul class="peracrm-list">';
        foreach ($reminders as $reminder) {
            $due_at = mysql2date('Y-m-d H:i', $reminder['due_at']);
            echo '<li>'; 
            echo '<div class="peracrm-list__meta">Due ' . esc_html($due_at) . '</div>';
            if (!empty($reminder['note'])) {
                echo '<div class="peracrm-list__body">' . esc_html($reminder['note']) . '</div>';
            }
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-inline-form">';
            wp_nonce_field('peracrm_mark_reminder_done');
            echo '<input type="hidden" name="action" value="peracrm_mark_reminder_done" />';
            echo '<input type="hidden" name="peracrm_reminder_id" value="' . esc_attr($reminder['id']) . '" />';
            echo '<button type="submit" class="button">Mark done</button>';
            echo '</form>';
            echo '</li>';
        }
        echo '</ul>';
    }

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-form">';
    wp_nonce_field('peracrm_add_reminder');
    echo '<input type="hidden" name="action" value="peracrm_add_reminder" />';
    echo '<input type="hidden" name="peracrm_client_id" value="' . esc_attr($post->ID) . '" />';
    echo '<p><label for="peracrm_due_at">Due date</label></p>';
    echo '<p><input type="datetime-local" name="peracrm_due_at" id="peracrm_due_at" class="widefat" /></p>';
    echo '<p><label for="peracrm_reminder_note">Note</label></p>';
    echo '<p><textarea name="peracrm_reminder_note" id="peracrm_reminder_note" rows="3" class="widefat"></textarea></p>';
    echo '<p><button type="submit" class="button button-primary">Add Reminder</button></p>';
    echo '</form>';

    echo '</div>';
}

function peracrm_render_activity_metabox($post)
{
    $activity = peracrm_activity_list($post->ID, 30);

    echo '<div class="peracrm-metabox">';

    if (empty($activity)) {
        echo '<p class="peracrm-empty">No activity logged.</p>';
    } else {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>Date</th><th>Type</th><th>Payload</th></tr></thead>';
        echo '<tbody>';
        foreach ($activity as $event) {
            $payload = peracrm_json_decode($event['event_payload']);
            $preview = $payload ? wp_json_encode($payload) : '';
            if (strlen($preview) > 120) {
                $preview = substr($preview, 0, 117) . '...';
            }
            printf(
                '<tr><td>%1$s</td><td>%2$s</td><td><code>%3$s</code></td></tr>',
                esc_html(mysql2date('Y-m-d H:i', $event['created_at'])),
                esc_html($event['event_type']),
                esc_html($preview)
            );
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}

function peracrm_render_properties_metabox($post)
{
    $relation_types = [
        'favourite' => 'Favourites',
        'enquiry' => 'Enquiries',
        'viewed' => 'Viewed',
        'portfolio' => 'Portfolio',
    ];

    echo '<div class="peracrm-metabox">';

    foreach ($relation_types as $relation => $label) {
        $count = peracrm_admin_get_client_property_count($post->ID, $relation);
        $items = peracrm_client_property_list($post->ID, $relation, 10);

        echo '<div class="peracrm-property-group">';
        echo '<strong>' . esc_html($label) . '</strong> (' . esc_html($count) . ')';

        if (empty($items)) {
            echo '<p class="peracrm-empty">No properties.</p>';
        } else {
            echo '<ul class="peracrm-list">';
            foreach ($items as $item) {
                $property_id = (int) $item['property_id'];
                $edit_link = get_edit_post_link($property_id, '');
                $title = get_the_title($property_id);
                if (!$title) {
                    $title = 'Property #' . $property_id;
                }
                if ($edit_link) {
                    echo '<li><a href="' . esc_url($edit_link) . '">' . esc_html($title) . '</a></li>';
                } else {
                    echo '<li>' . esc_html($title) . '</li>';
                }
            }
            echo '</ul>';
        }

        echo '</div>';
    }

    echo '</div>';
}
