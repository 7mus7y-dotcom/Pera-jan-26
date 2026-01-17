<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Single Property SEO / Social meta
 * Uses:
 * - WP excerpt => meta description
 * - ACF main_image (image array) => og:image / twitter:image
 */

if ( ! function_exists('pera_property_get_social_image') ) {
  function pera_property_get_social_image( int $post_id ): array {

    $url = '';
    $alt = '';

    if ( function_exists('get_field') ) {
      $main_image = get_field('main_image', $post_id);

      if ( is_array($main_image) ) {
        if ( ! empty($main_image['url']) ) $url = (string) $main_image['url'];
        if ( ! empty($main_image['alt']) ) $alt = (string) $main_image['alt'];

        if ( empty($url) && ! empty($main_image['ID']) ) {
          $resolved = wp_get_attachment_image_url((int) $main_image['ID'], 'full');
          if ( $resolved ) $url = (string) $resolved;
        }

        if ( empty($alt) && ! empty($main_image['ID']) ) {
          $resolved_alt = get_post_meta((int) $main_image['ID'], '_wp_attachment_image_alt', true);
          if ( is_string($resolved_alt) && $resolved_alt !== '' ) $alt = $resolved_alt;
        }
      }
    }

    if ( empty($url) ) {
      $thumb_id = get_post_thumbnail_id($post_id);
      if ( $thumb_id ) {
        $url = (string) wp_get_attachment_image_url((int) $thumb_id, 'full');
        $thumb_alt = get_post_meta((int) $thumb_id, '_wp_attachment_image_alt', true);
        if ( is_string($thumb_alt) && $thumb_alt !== '' ) $alt = $thumb_alt;
      }
    }

    return array(
      'url' => $url ? esc_url($url) : '',
      'alt' => $alt ? trim($alt) : '',
    );
  }
}

if ( ! function_exists('pera_property_get_meta_description') ) {
  function pera_property_get_meta_description( int $post_id ): string {

    $desc = wp_strip_all_tags( get_the_excerpt($post_id) );
    $desc = trim( preg_replace('/\s+/', ' ', $desc) );

    if ( function_exists('mb_substr') ) $desc = mb_substr($desc, 0, 160);
    else $desc = substr($desc, 0, 160);

    return $desc;
  }
}

add_action('wp_head', function () {

  if ( ! is_singular('property') ) return;

  $post_id = (int) get_queried_object_id();
  if ( ! $post_id ) return;

  $title = wp_strip_all_tags( get_the_title($post_id) );
  $url   = get_permalink($post_id);

  $desc    = pera_property_get_meta_description($post_id);
  $img     = pera_property_get_social_image($post_id);
  $img_url = $img['url'];
  $img_alt = $img['alt'] ?: $title;

  echo "\n<!-- Pera: Single Property SEO / Social -->\n";

  if ( $desc !== '' ) {
    echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
  }

  // edited out as WP handles this part. echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";

  // Open Graph
  echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo('name') ) . '">' . "\n";
  echo '<meta property="og:type" content="article">' . "\n";
  echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
  echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";

  if ( $desc !== '' ) {
    echo '<meta property="og:description" content="' . esc_attr($desc) . '">' . "\n";
  }
  if ( $img_url ) {
    echo '<meta property="og:image" content="' . esc_url($img_url) . '">' . "\n";
    echo '<meta property="og:image:alt" content="' . esc_attr($img_alt) . '">' . "\n";
  }

  // Twitter
  echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
  echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";

  if ( $desc !== '' ) {
    echo '<meta name="twitter:description" content="' . esc_attr($desc) . '">' . "\n";
  }
  if ( $img_url ) {
    echo '<meta name="twitter:image" content="' . esc_url($img_url) . '">' . "\n";
    echo '<meta name="twitter:image:alt" content="' . esc_attr($img_alt) . '">' . "\n";
  }

  echo "<!-- /Pera: Single Property SEO / Social -->\n\n";

}, 12);
