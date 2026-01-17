<?php
/**
 * Single Post (Lean)
 * Uses the lean header/footer and main.css layout.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main">

    <?php if ( have_posts() ) : ?>
        <?php while ( have_posts() ) : the_post(); ?>

            <?php
                // Primary category (for hero + related posts)
                $cats         = get_the_category();
                $primary_cat  = ! empty( $cats ) ? $cats[0] : null;
                $primary_name = $primary_cat ? $primary_cat->name : '';
                $primary_link = $primary_cat ? get_category_link( $primary_cat->term_id ) : '';
                
                // Related posts query (same category)
                $related_query = null;
                
                if ( $primary_cat && ! is_wp_error( $primary_cat ) ) {
                  $related_query = new WP_Query( array(
                    'post_type'           => 'post',
                    'posts_per_page'      => 3,
                    'post__not_in'        => array( get_the_ID() ),
                    'cat'                 => (int) $primary_cat->term_id,
                    'ignore_sticky_posts' => true,
                    'no_found_rows'       => true,
                  ) );
                }

            ?>

            <!-- =====================================
             HERO (SINGLE POST)
             ===================================== -->
            <section class="hero hero--left hero--post" id="post-hero">
              <div class="hero__media" aria-hidden="true">
                <?php
                  $hero_img_id = get_post_thumbnail_id();
            
                  if ( $hero_img_id ) {
                    echo wp_get_attachment_image(
                      $hero_img_id,
                      'full',
                      false,
                      array(
                        'class'    => 'hero-media',
                        'loading'  => 'eager',
                        'decoding' => 'async',
                      )
                    );
                  } else {
                    // Fallback background (vopbesiktas.svg uploaded to WP)
                    echo wp_get_attachment_image(
                      55756,
                      'full',
                      false,
                      array(
                        'class'    => 'hero-media',
                        'loading'  => 'eager',
                        'decoding' => 'async',
                      )
                    );
                  }
                ?>
                <div class="hero-overlay" aria-hidden="true"></div>
              </div>
            
              <div class="hero-content">
                <div class="article-meta-top">
                  <?php if ( $primary_cat ) : ?>
                    <a class="article-meta-cat" href="<?php echo esc_url( $primary_link ); ?>">
                      <?php echo esc_html( $primary_name ); ?>
                    </a>
                  <?php endif; ?>
                </div>
            
                <h1><?php the_title(); ?></h1>
            
                <?php if ( has_excerpt() ) : ?>
                  <p class="lead"><?php echo get_the_excerpt(); ?></p>
                <?php endif; ?>
            
                <div class="article-meta-secondary">
                  <span class="article-meta-item">Date uploaded <?php echo esc_html( get_the_date() ); ?></span>
                  <span class="article-meta-separator"> / </span>
                  <span class="article-meta-item">Updated <?php echo esc_html( get_the_modified_date() ); ?></span>
                  <span class="article-meta-separator"> / </span>
                  <span class="article-meta-item">Written by <?php echo esc_html( get_the_author() ); ?></span>
                </div>
              </div>
            </section>



            <!-- MAIN ARTICLE + SIDEBAR -->
            <section class="section section-article">
                <div class="container article-layout">

                    <!-- LEFT: ARTICLE CONTENT -->
                    <div class="article-main">
                        <article <?php post_class( 'article-body' ); ?>>
                            <?php the_content(); ?>
                        </article>
                    </div>


                    <!-- RIGHT: SIDEBAR -->
                    <aside class="article-sidebar" aria-label="Article sidebar">

                      <?php if ( isset( $related_query ) && $related_query instanceof WP_Query && $related_query->have_posts() ) : ?>
                    
                        <section class="sidebar-block sidebar-block--related">
                          <h3>More in <?php echo esc_html( $primary_name ); ?></h3>
                    
                          <div class="cards-slider cards-slider--sidebar">
                            <div class="slider-track">
                              <?php
                              while ( $related_query->have_posts() ) :
                                  $related_query->the_post();
                                
                                  set_query_var( 'pera_post_card_args', array(
                                    'variant'       => 'sidebar',
                                    'card_classes'  => 'slider-card',
                                    'show_excerpt'  => true,
                                    'excerpt_words' => 22,
                                    'thumb_size'    => 'medium_large',
                                    'show_cat_pill' => true,
                                    'pill_class'    => 'pill pill--outline',
                                    'show_readmore' => false,
                                  ) );
                                
                                  get_template_part( 'parts/post-card' );
                                
                                endwhile;
                                
                                set_query_var( 'pera_post_card_args', null );
                                wp_reset_postdata();

                              ?>
                            </div><!-- /.slider-track -->
                          </div><!-- /.cards-slider -->
                    
                          <div class="sidebar-cta">
                            <a class="btn btn-primary" href="<?php echo esc_url( $primary_link ); ?>">
                              See all posts
                            </a>
                          </div>
                    
                        </section>
                    
                        <?php wp_reset_postdata(); ?>
                    
                      <?php endif; ?>


                    
                        <!-- 2. SELL WITH PERA -->
                        <section class="sidebar-block sidebar-block--sell">
                            <h3>Sell with Pera</h3>
                            <p class="sidebar-text">
                                Thinking of selling your Istanbul property? Our consultants help you price, market,
                                and negotiate with qualified buyers from Turkey and abroad.
                            </p>
                            <div class="sidebar-cta">
                                <a class="btn btn-primary"
                                   href="https://www.peraproperty.com/sell-your-istanbul-real-estate/">
                                    Sell!
                                </a>
                            </div>
                        </section>
                    
                        <!-- 3. RENT WITH PERA -->
                        <section class="sidebar-block sidebar-block--rent">
                            <h3>Rent with Pera</h3>
                            <p class="sidebar-text">
                                Need a reliable tenant and a hassle-free rental process? We manage viewings, contracts,
                                and move-in so you can enjoy secure, consistent income.
                            </p>
                            <div class="sidebar-cta">
                                <a class="btn btn-primary"
                                   href="https://www.peraproperty.com/rent-your-istanbul-real-estate/">
                                    Rent!
                                </a>
                            </div>
                        </section>
                    
                        <!-- 4. LATEST PROPERTIES -->
                        <section class="sidebar-block sidebar-block--properties">
                          <h3>Latest properties</h3>
                        
                          <?php
                          $properties = new WP_Query( array(
                            'post_type'           => 'property',
                            'posts_per_page'      => 3,
                            'post_status'         => 'publish',
                            'no_found_rows'       => true,
                            'ignore_sticky_posts' => true,
                          ) );
                        
                          if ( $properties->have_posts() ) : ?>
                            
                            <div class="cards-slider cards-slider--sidebar cards-slider--snap">
                              <div class="slider-track">
                                <?php
                                while ( $properties->have_posts() ) :
                                  $properties->the_post();
                        
                                  set_query_var( 'pera_property_card_args', array(
                                    'variant'       => 'sidebar',
                                    'card_classes'  => 'slider-card',
                                    'show_badges'   => true,
                                    'show_admin'    => false,
                                    'show_excerpt'  => true,
                                    'excerpt_words' => 18,
                                    'image_size'    => 'large', // consider 'medium_large' for sidebar perf
                                  ) );
                        
                                  get_template_part( 'parts/property-card' );
                        
                                endwhile;
                        
                                // IMPORTANT: clear state + reset postdata
                                set_query_var( 'pera_property_card_args', null );
                                wp_reset_postdata();
                                ?>
                              </div>
                            </div>
                        
                          <?php endif; ?>
                        </section>


                
                    </aside><!-- /.article-sidebar -->
                
                
                </div><!-- /.container.article-layout -->
            </section><!-- /.section-article -->


            <!-- BOTTOM CONTACT SECTION (outside article layout) -->
            <?php get_template_part( 'parts/contact-cta' ); ?>


        <?php endwhile; ?>

    <?php else : ?>

        <section class="section section-article">
            <div class="container narrow">
                <p>Sorry, we couldn’t find this article.</p>
                <a class="btn btn-outline" href="<?php echo esc_url( home_url( '/' ) ); ?>">Go to homepage</a>
            </div>
        </section>

    <?php endif; ?>

</main>

<?php
get_footer();
