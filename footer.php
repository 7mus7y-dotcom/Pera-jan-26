<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the site content and all content after.
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
?>

<footer class="site-footer">
  <div class="footer-inner">
    <!-- COLUMN 1 -->
    <div class="footer-col">
      <h3>ABOUT PERA</h3>
      <p>Pera has helped hundreds of people buy, sell, and rent property in Istanbul!</p>
      <ul class="footer-links">
        <li><a href="https://www.peraproperty.com/about-us/">About Pera Property</a></li>
        <li><a href="https://www.peraproperty.com/about-us/#meet_the_team">Meet the team</a></li>
        <li><a href="https://www.peraproperty.com/about-us/#why_pera">Why Pera Property?</a></li>
        <li><a href="https://www.peraproperty.com/about-us/#our_services">Our services</a></li>
        <li><a href="https://www.peraproperty.com/join-our-team/">Join us</a></li>
        <li><a href="https://www.peraproperty.com/contact-us/">Contact us</a></li>
      </ul>

      <div class="footer-social">
        <!-- socials unchanged -->
        <a href="https://wa.me/905320639978?text=Hello%20Pera%20Property%2C%20I%27d%20like%20to%20learn%20more%20about%20your%20Istanbul%20properties."
           class="footer-social-link"
           aria-label="WhatsApp Pera Property"
           target="_blank"
           rel="noopener">
          <svg class="icon" aria-hidden="true">
            <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-whatsapp' ); ?>"></use>
          </svg>
        </a>

        <a href="https://instagram.com/peraproperty"
           class="footer-social-link"
           aria-label="Pera Property on Instagram"
           target="_blank"
           rel="noopener">
          <svg class="icon" aria-hidden="true">
            <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-instagram' ); ?>"></use>
          </svg>
        </a>

        <a href="https://www.youtube.com/channel/UCCCiEx5X14mJizqXcsYh1fQ"
           class="footer-social-link"
           aria-label="Pera Property on YouTube"
           target="_blank"
           rel="noopener">
          <svg class="icon" aria-hidden="true">
            <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-youtube' ); ?>"></use>
          </svg>
        </a>

        <a href="https://facebook.com/perapropertycom"
           class="footer-social-link"
           aria-label="Pera Property on Facebook"
           target="_blank"
           rel="noopener">
          <svg class="icon" aria-hidden="true">
            <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-facebook' ); ?>"></use>
          </svg>
        </a>

        <a href="https://tr.linkedin.com/company/peraproperty"
           class="footer-social-link"
           aria-label="Pera Property on LinkedIn"
           target="_blank"
           rel="noopener">
          <svg class="icon" aria-hidden="true">
            <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-linkedin' ); ?>"></use>
          </svg>
        </a>

        <a href="mailto:info@peraproperty.com"
           class="footer-social-link"
           aria-label="Email Pera Property">
          <svg class="icon" aria-hidden="true">
            <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-envelope' ); ?>"></use>
          </svg>
        </a>
      </div>
    </div>

    <!-- COLUMN 2 -->
    <div class="footer-col">
      <h3>OUR PORTFOLIO</h3>
      <p>See all our 1000+ property for sale in Istanbul</p>
      <?php
      if ( has_nav_menu( 'footer_menu' ) ) {
        wp_nav_menu( array(
          'theme_location' => 'footer_menu',
          'container'      => false,
          'menu_class'     => 'footer-links',
          'fallback_cb'    => false,
        ) );
      }
      ?>
    </div>

    <!-- COLUMN 3 -->
    <div class="footer-col">
      <h3>OUR GUIDANCE</h3>
      <p>Read through hundreds of articles written by the experts</p>
      <?php
      if ( has_nav_menu( 'guidance' ) ) {
        wp_nav_menu( array(
          'theme_location' => 'guidance',
          'container'      => false,
          'menu_class'     => 'footer-links',
          'fallback_cb'    => false,
        ) );
      }
      ?>
    </div>
  </div>
</footer>

<div class="footer-disclaimer">
  <div class="footer-inner disclaimer-inner">
    <p>
      PeraProperty.com is a licensed real estate agency in Turkey. Our fees are set at 4% for property sales and vary for rental services depending on the level of service required. Please refer to our dedicated
      <a href="https://www.peraproperty.com/sell-your-istanbul-real-estate/">Sales</a>
      and
      <a href="https://www.peraproperty.com/rent-your-istanbul-real-estate/">Rental</a>
      pages for the most current advice and information. While we strive to provide accurate and up-to-date content, all information on our website is subject to change. For specific queries, we recommend contacting our team directly.
    </p>

    <p class="footer-rights">
      All rights reserved. <strong>©</strong> 2025 <strong>Pera Property Ltd Şti</strong>
      <span class="footer-separator">¦</span>

      <a class="cookie-settings-link"
         href="https://www.peraproperty.com/privacy-policy/"
         target="_blank"
         rel="noopener">
        Privacy policy
      </a>
      <span class="footer-separator">¦</span>

      <a href="javascript:void(0)"
         class="cookie-settings-link"
         onclick="if (window.peraOpenCookieSettings) { window.peraOpenCookieSettings(); }">
        <svg class="icon cookie-icon" aria-hidden="true">
          <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-cookie' ); ?>"></use>
        </svg>
        Cookie settings
      </a>
    </p>
  </div>
</div>

<!-- COOKIE BANNER -->
<div class="cookie-banner" id="cookie-banner">
  <div class="cookie-banner__inner">
    <p class="cookie-banner__description">
      We use some essential cookies to make this website work. We’d also like to set optional analytics and marketing cookies to help us improve it.
    </p>

    <div class="cookie-banner__options">
      <div class="cookie-option">
        <h3>Strictly necessary cookies</h3>
        <p>These cookies are required to make the site work. They are always on.</p>
        <label class="cookie-switch disabled">
          <input type="checkbox" checked disabled>
          <span class="label-text">Always on</span>
        </label>
      </div>

      <div class="cookie-option">
        <h3>Analytics cookies</h3>
        <p>We’d like to use analytics cookies so we can understand how visitors use the site.</p>
        <label class="cookie-switch">
          <input type="checkbox" id="cookie-analytics">
          <span class="label-text">Allow analytics cookies</span>
        </label>
      </div>

      <div class="cookie-option">
        <h3>Marketing cookies</h3>
        <p>We use these to show you relevant advertising and measure its effectiveness.</p>
        <label class="cookie-switch">
          <input type="checkbox" id="cookie-marketing">
          <span class="label-text">Allow marketing cookies</span>
        </label>
      </div>
    </div>

    <div class="cookie-banner__actions">
      <button type="button" id="cookie-accept-all" class="btn btn-primary">Accept all cookies</button>
      <button type="button" id="cookie-reject" class="btn btn-secondary">Reject optional cookies</button>
      <button type="button" id="cookie-manage" class="btn">Manage cookie settings</button>
    </div>

    <p class="cookie-banner__footer-note">
      You can change your cookie settings at any time via “Cookie settings” in the footer.
    </p>
  </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
