<?php

if (!defined('ABSPATH')) {
    exit;
}

function peracrm_register_metaboxes($post_type, $post)
{
    if ('crm_client' !== $post_type) {
        return;
    }

    add_meta_box(
        'peracrm_notes',
        'Advisor Notes',
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

    if ($post && current_user_can('edit_post', $post->ID)) {
        add_meta_box(
            'peracrm_activity_timeline',
            'Activity Timeline',
            'peracrm_render_activity_timeline_metabox',
            'crm_client',
            'normal',
            'default'
        );
    }

    add_meta_box(
        'peracrm_properties',
        'Linked Properties',
        'peracrm_render_properties_metabox',
        'crm_client',
        'side',
        'default'
    );

    add_meta_box(
        'peracrm_account_link',
        'Account',
        'peracrm_render_account_metabox',
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

    $limit = 20;
    $offset = isset($_GET['notes_offset']) ? absint($_GET['notes_offset']) : 0;
    $notes = peracrm_notes_list($post->ID, $limit, $offset);
    $total = peracrm_notes_count($post->ID);

    $base_url = add_query_arg(
        [
            'post' => $post->ID,
            'action' => 'edit',
        ],
        admin_url('post.php')
    );

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

    $pagination = [];
    if ($offset > 0) {
        $new_offset = max(0, $offset - $limit);
        $pagination[] = '<a href="' . esc_url(add_query_arg('notes_offset', $new_offset, $base_url)) . '">Newer</a>';
    }
    if ($total > ($offset + $limit)) {
        $older_offset = $offset + $limit;
        $pagination[] = '<a href="' . esc_url(add_query_arg('notes_offset', $older_offset, $base_url)) . '">Older</a>';
    }
    if (!empty($pagination)) {
        echo '<p class="peracrm-pagination">' . implode(' | ', $pagination) . '</p>';
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

function peracrm_render_activity_timeline_metabox($post)
{
    if (!current_user_can('edit_post', $post->ID)) {
        return;
    }

    $event_labels = [
        'view_property' => 'Viewed property',
        'login' => 'Logged in',
        'account_visit' => 'Visited account area',
        'enquiry' => 'Submitted enquiry',
    ];

    $allowed_filters = array_keys($event_labels);
    $activity_type = isset($_GET['activity_type']) ? sanitize_key(wp_unslash($_GET['activity_type'])) : '';
    if (!in_array($activity_type, $allowed_filters, true)) {
        $activity_type = '';
    }

    $limit = 50;
    $offset = isset($_GET['activity_offset']) ? absint($_GET['activity_offset']) : 0;

    $activity = peracrm_activity_list($post->ID, $limit, $offset, $activity_type ?: null);
    $total = peracrm_activity_count($post->ID, $activity_type ?: null);

    $base_args = [
        'post' => $post->ID,
        'action' => 'edit',
    ];
    if ($activity_type) {
        $base_args['activity_type'] = $activity_type;
    }
    $base_url = add_query_arg($base_args, admin_url('post.php'));

    echo '<div class="peracrm-metabox">';

    echo '<form method="get" action="' . esc_url(admin_url('post.php')) . '" class="peracrm-inline-form">';
    echo '<input type="hidden" name="post" value="' . esc_attr($post->ID) . '" />';
    echo '<input type="hidden" name="action" value="edit" />';
    echo '<label for="peracrm_activity_type" class="screen-reader-text">Filter activity</label>';
    echo '<select name="activity_type" id="peracrm_activity_type">';
    echo '<option value="">All activity</option>';
    foreach ($event_labels as $type => $label) {
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr($type),
            selected($activity_type, $type, false),
            esc_html($label)
        );
    }
    echo '</select> ';
    echo '<button type="submit" class="button">Filter</button>';
    echo '</form>';

    if (empty($activity)) {
        echo '<p class="peracrm-empty">No activity recorded yet.</p>';
    } else {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>Activity</th><th>Context</th><th>Date</th></tr></thead>';
        echo '<tbody>';
        foreach ($activity as $event) {
            $payload = peracrm_json_decode($event['event_payload']);
            $property_id = isset($payload['property_id']) ? absint($payload['property_id']) : 0;
            $context = '&mdash;';
            if ($property_id > 0) {
                $title = get_the_title($property_id);
                if (!$title) {
                    $title = 'Property #' . $property_id;
                }
                $edit_link = get_edit_post_link($property_id, '');
                if ($edit_link) {
                    $context = '<a href="' . esc_url($edit_link) . '">' . esc_html($title) . '</a>';
                } else {
                    $context = esc_html($title);
                }
            }
            $event_type = $event['event_type'];
            $label = isset($event_labels[$event_type]) ? $event_labels[$event_type] : $event_type;
            printf(
                '<tr><td>%1$s</td><td>%2$s</td><td>%3$s</td></tr>',
                esc_html($label),
                $context,
                esc_html(mysql2date('Y-m-d H:i', $event['created_at']))
            );
        }
        echo '</tbody></table>';

        $pagination = [];
        if ($offset > 0) {
            $new_offset = max(0, $offset - $limit);
            $pagination[] = '<a href="' . esc_url(add_query_arg('activity_offset', $new_offset, $base_url)) . '">Newer</a>';
        }
        if ($total > ($offset + $limit)) {
            $older_offset = $offset + $limit;
            $pagination[] = '<a href="' . esc_url(add_query_arg('activity_offset', $older_offset, $base_url)) . '">Older</a>';
        }
        if (!empty($pagination)) {
            echo '<p class="peracrm-pagination">' . implode(' | ', $pagination) . '</p>';
        }
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

function peracrm_render_account_metabox($post)
{
    $linked_user_id = peracrm_admin_find_linked_user_id($post->ID);
    $linked_user = $linked_user_id ? get_userdata($linked_user_id) : null;

    echo '<div class="peracrm-metabox">';

    if ($linked_user) {
        $edit_link = get_edit_user_link($linked_user->ID);
        echo '<p><strong>Linked user</strong></p>';
        if ($edit_link) {
            echo '<p><a href="' . esc_url($edit_link) . '">User #' . esc_html($linked_user->ID) . '</a></p>';
        } else {
            echo '<p>User #' . esc_html($linked_user->ID) . '</p>';
        }
        echo '<p>Email: ' . esc_html($linked_user->user_email) . '</p>';
        echo '<p>Username: ' . esc_html($linked_user->user_login) . '</p>';
    } else {
        echo '<p><strong>Not linked</strong></p>';
        echo '<p>No user account is linked to this CRM client.</p>';
    }

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-form">';
    wp_nonce_field('peracrm_link_user');
    echo '<input type="hidden" name="action" value="peracrm_link_user" />';
    echo '<input type="hidden" name="peracrm_client_id" value="' . esc_attr($post->ID) . '" />';
    echo '<p><label for="peracrm_user_search">Search user (email or username)</label></p>';
    echo '<p><input type="text" name="peracrm_user_search" id="peracrm_user_search" class="widefat" /></p>';
    echo '<p><button type="submit" class="button button-primary">Link user</button></p>';
    echo '</form>';

    if ($linked_user) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="peracrm-form">';
        wp_nonce_field('peracrm_unlink_user');
        echo '<input type="hidden" name="action" value="peracrm_unlink_user" />';
        echo '<input type="hidden" name="peracrm_client_id" value="' . esc_attr($post->ID) . '" />';
        echo '<p><button type="submit" class="button">Unlink</button></p>';
        echo '</form>';
    }

    echo '</div>';
}
