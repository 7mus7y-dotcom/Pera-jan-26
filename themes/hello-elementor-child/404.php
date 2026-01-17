<?php
/**
 * 404 Template
 * Uses ONLY existing classes from main.css (no new CSS, no JS).
 */
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

get_header();
?>

<main id="primary" class="site-main">

  <!-- HERO -->
  <section class="hero hero-legal">
    <div class="container">
      <div class="hero-content">

        <p class="pill pill--green pill--sm">Page not found</p>

        <h1>404</h1>

        <p class="lead">
          Sorry — the page you’re looking for doesn’t exist, or it has moved.
        </p>

        <div class="hero-actions">
          <a class="btn btn--solid btn--black" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to Home</a>
          <a class="btn btn--solid btn--black" href="<?php echo esc_url( home_url( '/property/' ) ); ?>">View Properties</a>
          <a class="btn btn--solid btn--green" href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>">Contact Us</a>
        </div>

      </div>
    </div>
  </section>

  <!-- CONTENT PANEL (single card wrapper, then grid inside) -->
  <section class="content-panel">
    <div class="container">

      <div class="content-panel-box">
        <div class="content-panel-grid">

          <!-- Left: Helpful links + search -->
          <div>

            <div class="section-header">
              <h2>Where you can go next</h2>
              <p>These are some of the most visited areas of the site.</p>
            </div>

            <ul class="checklist">
              <li>
                <svg class="icon icon-tick" aria-hidden="true" viewBox="0 0 24 24">
                  <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                <a href="<?php echo esc_url( home_url( '/property/' ) ); ?>">Browse all listings</a>
              </li>

              <li>
                <svg class="icon icon-tick" aria-hidden="true" viewBox="0 0 24 24">
                  <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                <a href="<?php echo esc_url( home_url( '/category/buyer-guides' ) ); ?>">Buyer’s guide</a>
              </li>

              <li>
                <svg class="icon icon-tick" aria-hidden="true" viewBox="0 0 24 24">
                  <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                <a href="<?php echo esc_url( home_url( '/category/regional-guides/' ) ); ?>">Istanbul area guides</a>
              </li>

              <li>
                <svg class="icon icon-tick" aria-hidden="true" viewBox="0 0 24 24">
                  <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                <a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>">About Pera Property</a>
              </li>
            </ul>

          </div>

         
        </div><!-- /.content-panel-grid -->
      </div><!-- /.content-panel-box -->

    </div><!-- /.container -->
  </section>

</main>

<?php get_footer(); ?>
