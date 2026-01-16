<?php
/**
 * Template Name: About Us (New)
 * Custom About Us page using lean header/footer + main.css hero
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main about-page">
        
        
    <!-- =====================================================
     HERO – ABOUT PAGE
     Canonical structure + WP image ID 55756
     ===================================================== -->
        <section class="hero hero--left hero--about" id="about-hero">
        
          <div class="hero__media" aria-hidden="true">
            <?php
              // Prefer a featured image if you ever add one; otherwise fallback to vopbesiktas.svg (ID 55756)
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
              }
            ?>
            <div class="hero-overlay" aria-hidden="true"></div>
          </div>
        
          <div class="hero-content">
            <h1>About Pera Property</h1>
        
            <p class="lead">
              Our focus is Istanbul and Turkish real estate. We combine decades of market
              experience with independent advice to help our clients buy, sell and invest
              with confidence.
            </p>
        
            <div class="hero-actions">
              <a href="/property/" class="btn btn--solid btn--blue">View properties</a>
              <a href="#meet_the_team" class="btn btn--solid btn-whatsapp">Meet the team</a>
            </div>
          </div>
        
        </section>



      <!-- ABOUT COMPANY SECTION -->
      <section class="content-panel">
        <div class="content-panel-box border-dm">
          <div class="content-panel-grid">
    
            <!-- LEFT SIDE: HEADING + TEXT + SIGNOFF CARD -->
            <div class="content-panel-left">
    
              <div class="section-header">
                <h2>ABOUT OUR COMPANY</h2>
                <p>
                  Pera Property brings together the most experienced minds of the real estate industry.
                  It is a strategy which has created a large portfolio of new build as well as unique
                  property in Turkey.
                </p>
                <p><em>Our impartial whole of market approach ensures our clients achieve the optimal end goal.</em></p>
              </div>
    
              <div class="signoff-card">
                <div class="signoff-avatar">
                      <?php
                        echo wp_get_attachment_image(
                          55492,
                          'thumbnail',
                          false,
                          array(
                            'class'   => 'signoff-avatar-img',
                            'loading' => 'lazy',
                            'alt'     => 'D Koray Dillioglu',
                          )
                        );
                      ?>

                </div>
                <div class="signoff-text">
                  <h5>D Koray Dillioglu</h5>
                  <p>– Director @ Pera Property</p>
                </div>
              </div>
    
            </div>
    
            <!-- RIGHT SIDE: VIDEO -->
            <div class="content-panel-right">
              <div class="media-frame">
                <iframe
                  class="media-embed"
                  src="https://www.youtube-nocookie.com/embed/wZ6UTMFV39s?controls=1&rel=0&playsinline=1"
                  title="Pera Property"
                  frameborder="0"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                  allowfullscreen
                  referrerpolicy="strict-origin-when-cross-origin"
                ></iframe>
              </div>
            </div>
    
          </div><!-- /.content-panel-grid -->
        </div><!-- /.content-panel-box -->
      </section>
      <!-- WHY PERA / FEATURE SLIDER -->
      <section class="section" id="why_pera">
        <div class="container">
    
          <header class="section-header section-header--center">
            <h2>WHY PERA</h2>
            <p>
              Our experienced founders and team have focussed on the real estate sector in Istanbul since 2016.
              We pride ourselves on delivering exceptional real estate services tailored to unique needs.
            </p>
          </header>
    
          <!-- SLIDER VERSION OF WHY PERA -->
          <div class="cards-slider cards-slider--features cards-slider--snap cards-slider--grid-lg">
    
            <!-- SINCE 2016 -->
            <article class="slider-card feature-card">
              <div class="feature-card-header">
                <h3>SINCE 2016</h3>
              </div>
              <div class="feature-card-body">
                <p>
                  With over a combined 50 years in the real estate industry amongst our team, Pera brings
                  unparalleled knowledge and expertise to every transaction. Whether you’re a first-time
                  homebuyer or a seasoned investor, we have the insights to help you navigate the tricky
                  Istanbul market with confidence.
                </p>
              </div>
            </article>
    
            <!-- IMPARTIAL -->
            <article class="slider-card feature-card">
              <div class="feature-card-header">
                <h3>IMPARTIAL</h3>
              </div>
              <div class="feature-card-body">
                <p>
                  Our experienced property advisors ensure our clients are given the facts to ensure
                  a safe and reliable investment. This impartiality ensures our firm looks after our
                  investors’ best interests at all times.
                </p>
              </div>
              <div class="feature-card-footer">
                <a href="#" class="btn btn--solid btn--black">Read more &gt;&gt;</a>
              </div>
            </article>
    
            <!-- MANAGEMENT -->
            <article class="slider-card feature-card">
              <div class="feature-card-header">
                <h3>MANAGEMENT</h3>
              </div>
              <div class="feature-card-body">
                <p>
                  Pera provides full property management to ensure your investment is looked after from A to Z.
                  Our firm employs specialist staff to look after the $100m+ portfolio currently under our management.
                </p>
              </div>
              <div class="feature-card-footer">
                <a href="https://www.peraproperty.com/rent-your-istanbul-real-estate/" class="btn btn--solid btn--black">
                  Read more &gt;&gt;
                </a>
              </div>
            </article>
    
            <!-- EXCLUSIVE -->
            <article class="slider-card feature-card">
              <div class="feature-card-header">
                <h3>EXCLUSIVE</h3>
              </div>
              <div class="feature-card-body">
                <p>
                  We offer an extensive portfolio of property for sale in Istanbul, including exclusive listings
                  you won’t find anywhere else. From waterfront villas to city centre apartments, our selection
                  caters to a range of tastes and budgets.
                </p>
              </div>
            </article>
    
            <!-- TAILORED -->
            <article class="slider-card feature-card">
              <div class="feature-card-header">
                <h3>TAILORED</h3>
              </div>
              <div class="feature-card-body">
                <p>
                  Every search for the ideal property for sale in Istanbul is personal, and we treat it that way.
                  Our dedicated agents work closely with you to understand your needs and preferences.
                </p>
              </div>
              <div class="feature-card-footer">
                <a href="https://www.peraproperty.com/taxes-expenses-and-costs-when-buying_3098/" class="btn btn--solid btn--black">
                  Read more &gt;&gt;
                </a>
              </div>
            </article>
    
            <!-- LOCAL -->
            <article class="slider-card feature-card">
              <div class="feature-card-header">
                <h3>LOCAL</h3>
              </div>
              <div class="feature-card-body">
                <p>
                  Pera demands that every team member has a deep understanding of Istanbul’s diverse
                  neighborhoods. This allows us to provide valuable insights into the best areas to buy property.
                </p>
              </div>
            </article>
    
          </div><!-- /.cards-slider -->
    
        </div><!-- /.container -->
      </section>

        <?php get_template_part( 'parts/our-services-card' ); ?>


    
    
        <section class="section" id="meet_the_team">
          <div class="container">
        
            <header class="section-header section-header--center">
              <h2>MEET THE TEAM</h2>
              <p>
                Pera Property has brought together the best and most experienced minds of the industry.
              </p>
            </header>
        
            <div class="cards-slider cards-slider--features cards-slider--snap cards-slider--grid-lg">
        
              <?php
              $team_query = new WP_Query([
                'post_type'      => 'team',
                'posts_per_page' => -1,
                'orderby'        => 'menu_order',
                'order'          => 'ASC',
              ]);
        
              if ( $team_query->have_posts() ) :
                while ( $team_query->have_posts() ) :
                  $team_query->the_post();
        
                  // ACF fields
                $name        = get_field( 'name' );
                $position    = get_field( 'position' );
                $description = get_field( 'description' );
                $photo       = get_field( 'photo' );
    
                // Fallbacks
                $name     = $name ?: get_the_title();
                $position = $position ?: '';
                $description = $description ?: '';
    
                // Handle image array OR ID
                $photo_html = '';
                if ( $photo ) {
                    if ( is_array( $photo ) && ! empty( $photo['ID'] ) ) {
                        $photo_id = (int) $photo['ID'];
                    } elseif ( is_numeric( $photo ) ) {
                        $photo_id = (int) $photo;
                    } else {
                        $photo_id = 0;
                    }
    
                    if ( $photo_id ) {
                        $photo_html = wp_get_attachment_image(
                            $photo_id,
                            'large',
                            false,
                            array(
                                'loading'  => 'lazy',
                                'decoding' => 'async',
                            )
                        );
                    }
                }
                ?>
                  
                  <article class="slider-card feature-card team-card">
                    
                    <?php if ( $photo_id ) : ?>
                      <figure class="team-card-media">
                        <?php echo wp_get_attachment_image(
                          $photo_id,
                          'medium_large',
                          false,
                          [
                            'class'   => 'team-card-img',
                            'loading' => 'lazy',
                            'decoding'=> 'async',
                          ]
                        ); ?>
                      </figure>
                    <?php endif; ?>
        
                    <div class="feature-card-body team-card-body">
                      <h3><?php echo esc_html( $name ); ?></h3>
        
                      <?php if ( $position ) : ?>
                        <p class="team-card-position">
                          <?php echo esc_html( $position ); ?>
                        </p>
                      <?php endif; ?>
        
                      <?php if ( $description ) : ?>
                        <p class="team-card-desc">
                          <?php echo esc_html( $description ); ?>
                        </p>
                      <?php endif; ?>
                    </div>
        
                  </article>
        
                  <?php
                endwhile;
                wp_reset_postdata();
              endif;
              ?>
        
            </div><!-- /.cards-slider -->
        
          </div><!-- /.container -->
        </section>
    
        <!-- CONTACT CTA (imported part) -->
        <section id="contact" class="section section-soft">
            <div class="container">
                <?php
                // Reuse global contact CTA panel
                get_template_part( 'parts/contact-cta' );
                ?>
            </div>
        </section>

</main>

<?php
get_footer();
