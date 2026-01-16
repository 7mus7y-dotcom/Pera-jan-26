<?php
/**
 * Template Name: Contact Us (New)
 * Custom Contact page using lean header/footer + main.css hero
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

        <!-- =====================================================
         HERO – CONTACT PAGE
         Canonical structure + WP image ID 55756
         ===================================================== -->
        <section class="hero hero--left hero--contact" id="contact-hero">
        
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
            <h1>Contact Pera Property</h1>
        
            <p class="lead">
              Have a question about buying, selling or managing property in Istanbul?
              Reach out to our experienced consultants for straightforward, honest advice.
            </p>
        
            <div class="hero-actions">
              <a href="tel:+905320639978" class="btn btn--solid btn--blue">Call us</a>
              <a href="https://wa.me/905320639978" class="btn btn--solid btn--green" target="_blank" rel="noopener">
                  <svg class="icon">
                    <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-whatsapp"></use>
                  </svg> WhatsApp
              </a>
            </div>
          </div>
        
        </section>




 


  <!-- CONTACT DETAILS + MAP -->
  <section class="section" id="contact_details">
    <div class="container">
      <div class="content-panel-grid">

        <!-- LEFT: DETAILS -->
        <div class="content-panel-left">

          <div class="section-header">
            <h2>VISIT OUR ISTANBUL OFFICE</h2>
            <p>
              Our consultants are based in G&uuml;m&uuml;şsuyu, just above the Bosphorus and
              a short walk from Taksim Square and Dolmabah&ccedil;e.
            </p>
          </div>

          <div class="stacked-text">

         
            <div class="contact-details">
              <h3>Telephone</h3>
            
              <div class="contact-card">
                <div class="contact-card__icon" aria-hidden="true">
                  <svg class="icon">
                    <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-whatsapp"></use>
                  </svg>
                </div>
            
                <div class="contact-card__body">
                  <a href="tel:+905320639978" class="contact-card__number">+90 532 063 99 78</a>
                  <a href="https://wa.me/905320639978" class="contact-card__action" target="_blank" rel="noopener">
                    Message on WhatsApp
                  </a>
                </div>
              </div>
            
              <div class="contact-card">
                <div class="contact-card__icon" aria-hidden="true">
                  <svg class="icon">
                    <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-whatsapp"></use>
                  </svg>
                </div>
            
                <div class="contact-card__body">
                  <a href="tel:+905452054356" class="contact-card__number">+90 545 205 43 56</a>
                  <a href="https://wa.me/905452054356" class="contact-card__action" target="_blank" rel="noopener">
                    Message on WhatsApp
                  </a>
                </div>
              </div>
            </div>



        
          <h3>Email</h3>
          <p>
            <a href="mailto:info@peraproperty.com">info@peraproperty.com</a>
          </p>
        
          <h3>Address</h3>
          <p>
            G&uuml;m&uuml;şsuyu Mah. Ankara Palas, <br>
            İn&ouml;n&uuml; Cd. No 59/1, 34437 Beyoğlu / İstanbul
          </p>
        
          <h3>Working hours</h3>
          <p>
            Monday – Friday: 09:30 – 18:00<br>
            Saturday & Sunday: By appointment only
          </p>
        
          <a href="https://maps.app.goo.gl/QkLhU1YnNGQvEGr59"
             class="btn btn--solid btn--blue btn-card"
             target="_blank" rel="noopener">
            Get Directions
          </a>
        
        </div>

        </div>

        <!-- RIGHT: GOOGLE MAP -->
        <div class="content-panel-right">
            <div class="media-frame media-frame--map">
                <iframe
                class="media-embed media-embed--map"
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3009.4841913041314!2d28.989965700000003!3d41.036539499999996!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14cab71b29d934f5%3A0x50e7f6acdbad5b91!2sPera%20Property!5e0!3m2!1sen!2str!4v1764243753511!5m2!1sen!2str"
                  style="border:0;"
                  allowfullscreen=""
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
          </div>
        </div>



      </div><!-- /.content-panel-grid -->
    </div><!-- /.content-panel-box -->
  </section>

<?php get_template_part( 'parts/our-services-card' ); ?>

  

</main>

<?php
get_footer();
