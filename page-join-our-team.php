<?php
/**
 * Template Name: Join Our Team
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main">

    <!-- =====================================
     HERO – CAREERS
     Canonical structure + fallback background
     ===================================== -->
        <section class="hero hero--center hero--careers" id="careers-hero">
        
          <div class="hero__media" aria-hidden="true">
            <?php
              // Optional featured image support for the future
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
            <div class="pill pill--brand">Careers at Pera Property</div>
        
            <h1>Join our team in Istanbul.</h1>
        
            <p class="lead">
              We’re always interested in meeting talented people who are passionate about
              Istanbul real estate, client service and building long-term relationships.
            </p>
        
            <div class="hero-actions flex-center">
              <a href="#open-roles" class="btn btn--solid btn--blue">
                View open positions
              </a>
            </div>
          </div>
        
        </section>



    <!-- WHY WORK WITH PERA -->
    <section class="section">
        <div class="content-panel-box">
            <div class="content-panel-grid">

                <div>
                    <header class="section-header">
                        <h2>Why work with Pera?</h2>
                        <p>
                            We combine an entrepreneurial culture with the stability of a well-established
                            Istanbul agency. You’ll work directly with international investors, local owners
                            and developers on meaningful transactions.
                        </p>
                    </header>

                    <ul class="checklist">
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Hands-on experience across sales, lettings and investment.
                        </li>
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Work with international clients every day.
                        </li>
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Training and mentoring from senior consultants.
                        </li>
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Central Istanbul location, modern working environment.
                        </li>
                    </ul>
                </div>

                 <!-- <div>
                    <div class="media-frame">
                        <img class="media-embed"
                             src="<?php echo get_stylesheet_directory_uri(); ?>/images/team-office.jpg"
                             alt="Pera Property team in Istanbul office">
                    </div>
                </div>-->

            </div>
        </div>
    </section>


    <!-- OPEN ROLES -->
    <section id="open-roles" class="section section-soft">
        <div class="section-header section-header--center">
            <h2>Current open positions</h2>
            <p>If you don’t see the right role, you can still send us your CV using the form below.</p>
        </div>

        <div class="feature-grid">

            <!-- ROLE 1 -->
            <article class="feature-card">
                <div class="feature-card-header">
                    <h3>Senior Property Consultant</h3>
                    <p class="price-tag">Full-time · Istanbul</p>
                </div>
                <div class="feature-card-body">
                    <ul class="checklist">
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Advise international and local buyers on Istanbul property.
                        </li>
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Manage a portfolio of new-build and resale listings.
                        </li>
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Fluent English required; Turkish a strong advantage.
                        </li>
                    </ul>
                </div>
                <div class="feature-card-footer">
                    <a href="#contact" class="btn btn--solid btn--blue">Apply for this role</a>
                </div>
            </article>

            <!-- ROLE 2 -->
            <article class="feature-card">
                <div class="feature-card-header">
                    <h3>Lettings &amp; Property Manager</h3>
                    <p class="price-tag">Full-time · Istanbul</p>
                </div>
                <div class="feature-card-body">
                    <ul class="checklist">
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Oversee day-to-day management of long-term rentals.
                        </li>
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Coordinate maintenance, inspections and tenant relations.
                        </li>
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Strong organisational skills and client focus.
                        </li>
                    </ul>
                </div>
                <div class="feature-card-footer">
                    <a href="#contact" class="btn btn--solid btn--green">Apply for this role</a>
                </div>
            </article>

        </div>
    </section>


    <!-- ABOUT PERA (PARTIAL) -->
    <?php get_template_part( 'parts/about-pera' ); ?>

</main>

<?php get_footer(); ?>
