<?php
/**
 * Archive Template (Blog, Categories, Tags, Authors, Dates)
 * Uses lean header/footer and main.css + blog.css layout.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main">

        <?php
        // Work out archive title & description
        $archive_title       = '';
        $archive_subtitle    = '';
        $archive_description = '';
        
        if ( is_category() ) {
        
            $term = get_queried_object();
        
            $archive_title = single_cat_title( '', false );
        
            // 1) Use custom category excerpt if available
            if ( $term && ! is_wp_error( $term ) && function_exists( 'pera_get_category_excerpt' ) ) {
                $archive_subtitle = pera_get_category_excerpt( $term->term_id, 28 );
            }
        
            // 2) Fallbacks if excerpt is empty
            if ( empty( $archive_subtitle ) ) {
                $archive_subtitle = __( 'Articles filed under this category', 'peraproperty' );
            }
        
            // Optional: long description (used elsewhere if needed)
            $archive_description = term_description( $term->term_id );
        
        } elseif ( is_tag() ) {
        
            $archive_title       = single_tag_title( '', false );
            $archive_subtitle    = __( 'Articles tagged with this topic', 'peraproperty' );
            $archive_description = term_description();
        
        } elseif ( is_author() ) {
        
            $author_obj          = get_queried_object();
            $archive_title       = sprintf(
                __( 'Articles by %s', 'peraproperty' ),
                esc_html( $author_obj->display_name )
            );
            $archive_subtitle    = __( 'Insights and commentary from this author', 'peraproperty' );
            $archive_description = get_the_author_meta( 'description', $author_obj->ID );
        
        } elseif ( is_year() ) {
        
            $archive_title    = get_the_date( _x( 'Y', 'yearly archives date format', 'peraproperty' ) );
            $archive_subtitle = __( 'Yearly archive', 'peraproperty' );
        
        } elseif ( is_month() ) {
        
            $archive_title    = get_the_date( _x( 'F Y', 'monthly archives date format', 'peraproperty' ) );
            $archive_subtitle = __( 'Monthly archive', 'peraproperty' );
        
        } elseif ( is_day() ) {
        
            $archive_title    = get_the_date();
            $archive_subtitle = __( 'Daily archive', 'peraproperty' );
        
        } elseif ( is_post_type_archive() && 'post' !== get_post_type() ) {
        
            $archive_title    = post_type_archive_title( '', false );
            $archive_subtitle = __( 'Archive', 'peraproperty' );
        
        } else {
        
            $archive_title    = __( 'Articles & Insights', 'peraproperty' );
            $archive_subtitle = __( 'Latest updates, market commentary, and buying guides from Pera Property.', 'peraproperty' );
        
        }
        ?>


    <!-- =====================================================
     HERO – ARCHIVE
     Canonical structure + fallback background (ID 55756)
     ===================================================== -->
        <section class="hero hero--left hero--archive" id="archive-hero">
        
          <div class="hero__media" aria-hidden="true">
            <?php
              // Archive pages typically don't have featured images.
              // If you later add a custom image field, you can swap this logic.
              echo wp_get_attachment_image(
                55756,
                'full',
                false,
                array(
                  'class'         => 'hero-media',
                  'fetchpriority' => 'high',
                  'loading'       => 'eager',
                  'decoding'      => 'async',
                )
              );
            ?>
            <div class="hero-overlay" aria-hidden="true"></div>
          </div>
        
          <div class="hero-content">
            <h1><?php echo esc_html( $archive_title ); ?></h1>
        
            <?php
            
            if ( ! empty( $archive_subtitle ) ) : ?>
              <p class="lead"><?php echo esc_html( $archive_subtitle ); ?></p>
            <?php
            endif;
            
            ?>
        
            

          </div>
        
        </section>




    <!-- POSTS GRID -->
    <section class="section section-posts">
        <div class="container">
            <?php if ( ! empty( $archive_description ) ) : ?>
              <div class="archive-hero-desc">
                <div class="archive-hero-desc__content lead">
                  <?php echo wp_kses_post( wpautop( $archive_description ) ); ?>
                </div>
            
              </div>
            <?php endif; ?>

            <?php if ( have_posts() ) : ?>

                <div class="cards-grid">
                  <?php
                  while ( have_posts() ) :
                    the_post();
                
                    // Primary category (for pill)
                    $cats        = get_the_category();
                    $primary_cat = ( ! empty( $cats ) && ! is_wp_error( $cats ) ) ? $cats[0] : null;
                
                    set_query_var( 'pera_post_card_args', array(
                      'variant'       => 'grid',            // archive / grid context
                      'card_classes'  => '',                // optional extra classes
                      'show_excerpt'  => true,
                      'excerpt_words' => 28,
                      'thumb_size'    => 'medium_large',
                      'show_cat_pill' => true,
                      'pill_class'    => 'pill pill--outline',
                      'show_readmore' => true,
                    ) );
                
                    get_template_part( 'parts/post-card' );
                
                  endwhile;
                
                  // IMPORTANT: clean up
                  set_query_var( 'pera_post_card_args', null );
                  ?>
                </div><!-- /.cards-grid -->


                <?php
                // Custom pagination to match .posts-pagination styles
                global $wp_query;

                $big   = 999999999;
                $links = paginate_links(
                    array(
                        'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                        'format'    => '?paged=%#%',
                        'current'   => max( 1, get_query_var( 'paged' ) ),
                        'total'     => $wp_query->max_num_pages,
                        'type'      => 'array',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                    )
                );

                if ( ! empty( $links ) ) : ?>
                    <nav class="posts-pagination" aria-label="<?php esc_attr_e( 'Posts pagination', 'peraproperty' ); ?>">
                        <ul>
                            <?php foreach ( $links as $link ) : ?>
                                <li><?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

          

            <?php else : ?>

                <div class="no-posts">
                    <p><?php esc_html_e( 'No articles found in this archive.', 'peraproperty' ); ?></p>
                    <a class="btn btn--ghost btn--blue" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <?php esc_html_e( 'Back to homepage', 'peraproperty' ); ?>
                    </a>
                </div>

            <?php endif; ?>

        </div><!-- /.container -->
    </section>
    
        <?php
        // OTHER CATEGORIES SECTION (shown on category archives)
        if ( is_category() ) :
        
          $current_cat = get_queried_object();
          $current_id  = isset( $current_cat->term_id ) ? (int) $current_cat->term_id : 0;
        
          // Re-fetch ALL categories, then filter manually.
          $all_cats = get_categories( array(
            'taxonomy'   => 'category',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
          ) );
        
          // Remove current category
          $other_cats = array_filter( $all_cats, function( $cat ) use ( $current_id ) {
            return (int) $cat->term_id !== $current_id;
          } );
        
          // Optionally remove “Uncategorized”
          $other_cats = array_filter( $other_cats, function( $cat ) {
            return $cat->slug !== 'uncategorized';
          } );
        
          // Limit to 6 cards
          $other_cats = array_slice( $other_cats, 0, 6 );
        
          if ( ! empty( $other_cats ) ) : ?>
            <section class="section section-archive-cats">
              <div class="container archive-cats">
                <h2 class="archive-cats-title">Explore other topics</h2>
            
                <div class="archive-cats-grid cards-scroll-mobile">
                  <?php foreach ( $other_cats as $cat ) :
            
                    $cat_link = get_category_link( $cat->term_id );
            
                    $desc = ! empty( $cat->description )
                      ? wp_trim_words( wp_strip_all_tags( $cat->description ), 24, '…' )
                      : sprintf( '%d post%s', (int) $cat->count, $cat->count === 1 ? '' : 's' );
                    ?>
                    <article class="archive-cat-card card-shell">
            
                      <h3 class="post-card-title">
                        <a href="<?php echo esc_url( $cat_link ); ?>">
                          <?php echo esc_html( $cat->name ); ?>
                        </a>
                      </h3>
            
                      <p class="archive-cat-desc">
                        <?php echo esc_html( $desc ); ?>
                      </p>
            
                      <!-- Use existing .card-meta-row (justify-content: space-between) + push button right -->
                      <div class="card-meta-row">
                        <span></span>
                        <a href="<?php echo esc_url( $cat_link ); ?>" class="btn btn--ghost btn--black btn-card">
                          View posts
                        </a>
                      </div>
            
                    </article>
                  <?php endforeach; ?>
                </div>
              </div>
            </section>

            
          <?php endif; ?>
        
        <?php endif; ?>




        <!-- BOTTOM CONTACT SECTION (outside article layout) -->
        <?php get_template_part( 'parts/contact-cta' ); ?>
</main>


        <script>
        document.addEventListener('DOMContentLoaded', () => {
          const blocks = document.querySelectorAll('.archive-hero-desc');
          if (!blocks.length) return;
        
          blocks.forEach((block) => {
            const content = block.querySelector('.archive-hero-desc__content');
            const btn = block.querySelector('.archive-hero-desc__toggle');
            if (!content || !btn) return;
        
            // Decide whether the toggle is even needed (if content is short)
            const needsToggle = content.scrollHeight > content.clientHeight + 8;
        
            // Force initial collapsed state (your markup has data-collapsed="true")
            const startCollapsed = block.getAttribute('data-collapsed') !== 'false';
        
            function applyState(isCollapsed) {
              block.setAttribute('data-collapsed', isCollapsed ? 'true' : 'false');
              btn.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
              btn.textContent = isCollapsed ? 'Read more' : 'Read less';
            }
        
            // If you want to hide the button when not needed:
            // (Wait a tick for layout/fonts to settle)
            requestAnimationFrame(() => {
              const reallyNeedsToggle = content.scrollHeight > content.clientHeight + 8;
              btn.style.display = reallyNeedsToggle ? '' : 'none';
            });
        
            applyState(startCollapsed);
        
            // Prevent any parent click handlers from interfering
            btn.addEventListener('click', (e) => {
              e.preventDefault();
              e.stopPropagation();
        
              const isCollapsed = block.getAttribute('data-collapsed') !== 'false';
              applyState(!isCollapsed);
            });
          });
        });
        </script>



<?php
get_footer();
