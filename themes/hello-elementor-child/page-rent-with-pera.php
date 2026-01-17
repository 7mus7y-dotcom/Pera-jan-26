<?php
/**
 * Template Name: Rent with Pera
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$hero_heading = $args['hero_heading'] ?? 'Talk to Pera about your Istanbul plans';
$hero_intro   = $args['hero_intro']   ?? 'Whether you’re buying, selling, or renting in Istanbul, our team can walk you through the numbers, the legal steps, and the neighbourhoods that fit your strategy.';


get_header();
?>

<main id="primary" class="site-main">

    <!-- =====================================
     HERO (RENT WITH PERA)
     Canonical structure + existing content
     ===================================== -->
        <section class="hero hero--left hero--rent" id="rent-hero">
        
          <div class="hero__media" aria-hidden="true">
            <?php
              // Optional featured image support (future-proof)
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
                // Fallback background (vopbesiktas.svg – attachment ID 55756)
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

        
            <h1>Rent your Istanbul property with confidence.</h1>
        
            <p class="lead">
              From tenant sourcing to full management, Pera handles every detail so you earn the best return with zero stress.
            </p>
        
            <div class="hero-actions">
              <a href="#pricing" class="btn btn--solid btn--blue">See pricing</a>
              <a href="#contact" class="btn btn--solid btn--green">Get a valuation</a>
            </div>
          </div>
        
        </section>



    <!-- WHY RENT WITH PERA -->
    <section class="content-panel">
        <div class="content-panel-box">
            <div class="content-panel-grid">

                <!-- LEFT -->
                <div>
                    <header class="section-header">
                        <h2>Why rent your property with Pera?</h2>
                        <p>
                            We maximise your rental returns through professional marketing, strong tenant checks,
                            and hands-on management that protects your time and your investment.
                        </p>
                    </header>

                    <ul class="checklist">

                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Marketing on all major platforms.
                        </li>

                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Full tenant screening &amp; ID verification.
                        </li>

                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Maintenance, repairs and inspections.
                        </li>

                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            24/7 support for tenants.
                        </li>

                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Monthly statements &amp; full transparency.
                        </li>

                    </ul>
                </div>
                
                <!-- RIGHT: MEDIA / VISUAL -->
                <div>
                    <div class="media-frame media-frame--image-fill">
                        <?php
                        echo wp_get_attachment_image(
                            55695,
                            'full',
                            false,
                            array(
                                'class'    => 'media-image', // IMPORTANT: this class
                                'loading'  => 'lazy',
                                'decoding' => 'async',
                                'alt'      => esc_attr(
                                    'Istanbul real estate market overview by Pera Property'
                                ),
                            )
                        );
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PRICING -->
    <section id="pricing" class="section section-soft">
        <div class="section-header section-header--center">
            <h2>Our rental management services</h2>
            <p>Choose the service level that fits your investment strategy.</p>
        </div>

        <div class="feature-grid">

            <!-- LETTINGS ONLY -->
            <article class="feature-card">
                <div class="feature-card-header">
                    <h3>Lettings Only</h3>
                    <p class="price-tag">8% + VAT</p>
                </div>

                <div class="feature-card-body">
                    <ul class="checklist">
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Advertising on all major rental platforms
                        </li>
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Tenant viewings and shortlisting
                        </li>
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Full tenant screening &amp; ID verification
                        </li>
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Tenancy agreement preparation and signing
                        </li>
                    </ul>
                </div>

                <div class="feature-card-footer">
                    <a href="#contact" class="btn btn--solid btn--green">Get valuation</a>
                </div>
            </article>

            <!-- FULL MANAGEMENT -->
            <article class="feature-card">
                <div class="feature-card-header">
                    <h3>Full Management</h3>
                    <p class="price-tag">12% + VAT</p>
                </div>

                <div class="feature-card-body">
                    <ul class="checklist">
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Everything in Lettings Only
                        </li>
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Dedicated property manager in Istanbul
                        </li>
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Rent collection and arrears management
                        </li>
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Organising repairs, quotes &amp; contractors
                        </li>
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Regular inspections with condition reports
                        </li>
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Utility transfers, deposits &amp; move-out checks
                        </li>
                    </ul>
                </div>

                <div class="feature-card-footer">
                    <a href="#contact" class="btn btn--solid btn--green">Get valuation</a>
                </div>
            </article>

        </div>
    </section>




    <!-- SHORT TERM RENTALS -->
    <section class="section">
        <div class="content-panel-box">
            <div class="content-panel-grid">

                <!-- LEFT -->
                <div>
                    <header class="section-header">
                        <h2>The short term rental market (“Airbnb”)</h2>
                        <p>
                            International owners trust Pera Property to manage their apartments on Istanbul’s short-term rental
                            market. We provide an armchair experience while your property generates strong returns.
                        </p>
                    </header>

                    <ul class="checklist">

                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Check-in / check-out management
                        </li>

                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Cleaning &amp; maintenance
                        </li>

                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Guest communication
                        </li>

                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Supplies &amp; inventory management
                        </li>

                    </ul>
                </div>

                <!-- RIGHT -->
                <div>
                    <div class="media-frame">
                        <img class="media-embed"
                             src="<?php echo get_stylesheet_directory_uri(); ?>/images/airbnb-istanbul.jpg"
                             alt="Airbnb management Istanbul – Pera Property">
                    </div>
                </div>

            </div>
        </div>
    </section>



    <!-- ABOUT PERA -->
    <?php get_template_part( 'parts/about-pera' ); ?>


    <section class="section section-soft" id="contact">
            <div class="content-panel-box">
        
                <!-- =========================
                     1) HERO CTA GRID (LEFT TEXT + RIGHT IMAGE)
                     ========================== -->
                <div class="content-panel-grid">
        
                    <!-- LEFT COLUMN -->
                    <div>
                        <header class="section-header">
                            <h2><?php echo esc_html( $hero_heading ); ?></h2>
                            <p><?php echo esc_html( $hero_intro ); ?></p>
                        </header>
        
                        <ul class="checklist">
                            <li>
                                <svg class="icon icon-tick" aria-hidden="true">
                                    <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                                </svg>
                                Reliable, data-driven advice.
                            </li>
        
                            <li>
                                <svg class="icon icon-tick" aria-hidden="true">
                                    <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                                </svg>
                                On-the-ground Istanbul expertise.
                            </li>
        
                            <li>
                                <svg class="icon icon-tick" aria-hidden="true">
                                    <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                                </svg>
                                Multi-lingual support.
                            </li>
                        </ul>
                    </div>
        
                    <!-- RIGHT COLUMN -->
                    <div class="media-frame">
        
                        <!-- RESPONSIVE BACKGROUND IMAGE -->
                        <div class="media-frame__bg">
                            <?php
                            echo wp_get_attachment_image(
                                55686,
                                'large',
                                false,
                                array(
                                    'class'    => 'media-frame__bg-img',
                                    'loading'  => 'lazy',
                                    'decoding' => 'async',
                                    'alt'      => 'Isometric illustration of Beşiktaş'
                                )
                            );
                            ?>
                        </div>
        
                        <div class="hero-overlay"></div>
        
                        <div class="hero-content section--center">
                            <h3 class="text-light">Speak with a Consultant</h3>
        
                            <div class="hero-actions flex-center">
                                <a href="https://www.peraproperty.com/contact-us/" class="btn btn--solid btn--green">
                                    Book a consultation
                                </a>
        
                                <a href="https://wa.me/905452054356?text=Hello%20Pera%20Property%2C%20I%27d%20like%20to%20discuss%20Istanbul%20real%20estate."
                                   class="btn btn--solid btn--green">
                                    Chat on WhatsApp
                                </a>
                            </div>
                        </div>
        
                    </div><!-- .media-frame -->
        
                </div><!-- .content-panel-grid -->
    
                <div>
        
                    <?php if ( isset( $_GET['sr_success'] ) && $_GET['sr_success'] === '1' ) : ?>
                        <div class="form-success">
                            Thank you – we have received your details. A Pera consultant will contact you shortly.
                        </div>
                    <?php endif; ?>
        
                    <div class="enquiry-cta-header">
                        <h2><?php echo esc_html( $form_heading ); ?></h2>
                        <p><?php echo esc_html( $form_intro ); ?></p>
                    </div>
        
                     <?php
                    get_template_part('parts/enquiry-form', null, array(
                      'context'      => 'rent',
                      'heading'      => 'Request a free appraisal',
                      'intro'        => 'Share a few details and we will prepare an initial rent strategy and price guidance for your property in Istanbul.',
                      'submit_label' => 'Send my details',
                      'form_context' => 'rent-page',
                    ));
            
                    ?>
                    
                </div><!-- .enquiry-cta -->
            </div><!-- .content-panel-box -->
        </section>

        

</main>

<?php get_footer(); ?>
