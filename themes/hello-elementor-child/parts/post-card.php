<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Template Part: Post Card
 * Source of truth for options: $card_args (query var bridge)
 */

$card_args = get_query_var( 'pera_post_card_args' );
$card_args = is_array( $card_args ) ? $card_args : array();

$variant = isset( $card_args['variant'] ) ? sanitize_key( $card_args['variant'] ) : 'archive';

/* Optional knobs */
$show_excerpt  = array_key_exists( 'show_excerpt',  $card_args ) ? (bool) $card_args['show_excerpt']  : true;
$excerpt_words = isset( $card_args['excerpt_words'] ) ? (int) $card_args['excerpt_words'] : 28;
$thumb_size    = isset( $card_args['thumb_size'] ) ? sanitize_key( $card_args['thumb_size'] ) : 'pera-card';

$show_cat_pill = array_key_exists( 'show_cat_pill', $card_args ) ? (bool) $card_args['show_cat_pill'] : true;
$pill_class    = isset( $card_args['pill_class'] ) ? sanitize_text_field( $card_args['pill_class'] ) : 'pill pill--outline';

$show_readmore = array_key_exists( 'show_readmore', $card_args ) ? (bool) $card_args['show_readmore'] : true;
$btn_class     = isset( $card_args['btn_class'] ) ? sanitize_text_field( $card_args['btn_class'] ) : 'btn btn--solid btn--blue';
$btn_label     = isset( $card_args['btn_label'] ) ? sanitize_text_field( $card_args['btn_label'] ) : __( 'Read more', 'peraproperty' );

$extra_classes = isset( $card_args['card_classes'] ) ? sanitize_text_field( $card_args['card_classes'] ) : '';
$card_classes  = trim( $extra_classes . ' post-card post-card--' . $variant );

$post_id = get_the_ID();

/* Primary category (first category) */
$cats        = get_the_category( $post_id );
$primary_cat = ( ! empty( $cats ) && ! is_wp_error( $cats ) ) ? $cats[0] : null;

$cat_name = $primary_cat ? $primary_cat->name : '';
$cat_link = $primary_cat ? get_category_link( $primary_cat->term_id ) : '';
?>

<article <?php post_class( $card_classes, $post_id ); ?>>

  <?php if ( has_post_thumbnail( $post_id ) ) : ?>
    <a href="<?php the_permalink(); ?>" class="post-card-thumb">
      <?php
      echo get_the_post_thumbnail(
        $post_id,
        $thumb_size,
        array(
          'loading'  => 'lazy',
          'decoding' => 'async',
        )
      );
      ?>
    </a>
  <?php else : ?>
    <div class="post-card-thumb-placeholder post-card-thumb-logo" aria-hidden="true">
      <img
        src="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/pera-logo.svg' ); ?>"
        alt=""
        class="post-card-placeholder-logo"
        loading="lazy"
        decoding="async"
      >
    </div>
  <?php endif; ?>

  <div class="post-card-body">

    <div class="post-card-meta">
        <span class="post-card-date">
          <?php echo esc_html( get_the_date( 'M d, y' ) ); ?>
        </span>


      <?php if ( $show_cat_pill && $cat_name ) : ?>
        <a href="<?php echo esc_url( $cat_link ); ?>" class="<?php echo esc_attr( $pill_class ); ?>">
          <?php echo esc_html( $cat_name ); ?>
        </a>
      <?php endif; ?>
    </div>

    <h2 class="post-card-title">
      <a href="<?php the_permalink(); ?>">
        <?php the_title(); ?>
      </a>
    </h2>

    <?php if ( $show_excerpt ) : ?>
      <div class="post-card-excerpt">
        <?php echo esc_html( wp_trim_words( get_the_excerpt(), $excerpt_words, '…' ) ); ?>
      </div>
    <?php endif; ?>

    <?php if ( $show_readmore ) : ?>
      <div class="post-card-readmore">
        <a href="<?php the_permalink(); ?>" class="<?php echo esc_attr( $btn_class ); ?>">
          <?php echo esc_html( $btn_label ); ?>
        </a>
      </div>
    <?php endif; ?>

  </div>
</article>
