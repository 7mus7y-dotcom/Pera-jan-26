<?php
/*
Template Name: VOP Besiktas Collection
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main">

  <!-- =====================================================
       HERO – VOP BEŞİKTAŞ COLLECTION
       ====================================================== -->
<section class="hero" id="vop-besiktas-hero">
  <!-- LCP hero image -->
  <?php
    echo wp_get_attachment_image(
      55482,
      'full',
      false,
      [
        'class'         => 'hero-media',
        'alt'           => 'Beşiktaş Collection',
        'fetchpriority' => 'high',
        'loading'       => 'eager',
        'decoding'      => 'async',
      ]
    );
  ?>

  <div class="hero-overlay"></div>

  <div class="hero-content">
    <h1>Beşiktaş Collection</h1>

    <p class="lead">
      Three brand new boutique residences redefining modern living
      in the heart of Istanbul.
    </p>

    <div class="hero-actions">
      <a href="#projects" class="btn btn--solid btn--blue">Explore projects</a>
      <a href="#contact" class="btn btn--solid btn--green">Contact us</a>
    </div>
  </div>
</section>



  <!-- =====================================================
       ABOUT – LIFESTYLE ENGINEERING
       ====================================================== -->
  <section id="about" class="section">
    <div class="container two-col">
      <div>
        <h2>Lifestyle engineering</h2>
        <p>
          Human-focused planning, solid construction and central Beşiktaş addresses.
          Each project is designed for both living and investment.
        </p>

        <div class="pillars">
          <div>Earthquake-resistant</div>
          <div>Energy efficient</div>
          <div>Smart layouts</div>
          <div>Central Beşiktaş</div>
        </div>
      </div>

      <div>
        <img
          src="<?php echo content_url( '/uploads/vop/vop-concept-thumb.jpg' ); ?>"
          alt="VOP concept"
          loading="lazy"
          decoding="async"
        >
      </div>
    </div>
  </section>


  <!-- =====================================================
       NEIGHBOURHOOD
       ====================================================== -->
  <section id="neighborhood" class="section section-soft">
    <div class="container">
      <div class="grid-2">
        <!-- LEFT: TEXT -->
        <div>
          <h2>In the heart of Beşiktaş</h2>
          <p>
            All three residences sit within minutes of world-famous landmarks —
            ideal for the short rental market or as a vacation home (or both).
          </p>
        </div>

        <!-- RIGHT: IMAGE -->
        <div>
          <img
            src="<?php echo content_url( '/uploads/vop/vop-map.webp' ); ?>"
            alt="Map of Beşiktaş projects"
            class="rounded"
            loading="lazy"
            decoding="async"
          >
        </div>
      </div>
    </div>
  </section>


  <!-- =====================================================
       PROJECTS – CARD GRID
       ====================================================== -->
  <section id="projects" class="section">
    <div class="container">
      <h2 class="center">Our projects</h2>

      <div class="cards">
          
        <!-- VOP – IHLAMUR -->
        <article class="project-card">
          <img
            src="<?php echo content_url( '/uploads/vop/vop-ihlamur.webp' ); ?>"
            alt="VOP Ihlamur"
            loading="lazy"
            decoding="async"
          >

          <div class="content">
            <h3>Ihlamur</h3>
            <p>Ihlamurdere Caddesi, Beşiktaş</p>
            <ul>
              <li>32 apartments + 2 shops</li>
              <li>1+1 to 4+1 duplex</li>
              <li>Next to Dünya Barış Park</li>
              <li>Delivery: Feb 2026</li>
            </ul>

            <div class="card-actions">
              <!-- Brochure -->
              <a
                href="<?php echo content_url( '/uploads/files/ihlamur-catalogue.pdf' ); ?>"
                target="_blank"
                class="btn btn--solid btn--black btn-card"
              >
                <svg class="icon" aria-hidden="true">
                  <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-pdf"></use>
                </svg>
                Brochure
              </a>

              <!-- Location -->
              <a
                href="https://maps.app.goo.gl/V2gMd4763WsAQKU56"
                target="_blank"
                rel="noopener"
                class="btn btn--solid btn--blue btn-card"
              >
                <svg class="icon" aria-hidden="true">
                  <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-map"></use>
                </svg>
                Location
              </a>
            </div>
          </div>
        </article>


        <!-- VOP – DIKILITAŞ -->
        <article class="project-card">
          <img
            src="<?php echo content_url( '/uploads/vop/vop-dikilitas.webp' ); ?>"
            alt="VOP Dikilitaş"
            loading="lazy"
            decoding="async"
          >

          <div class="content">
            <h3>Dikilitaş</h3>
            <p>Mukataacı Sokak No: 3</p>
            <ul>
              <li>29 boutique apartments</li>
              <li>Next to Dikilitaş Park</li>
              <li>1+1 – 4+1 duplex</li>
              <li>Delivery: Jan 2026</li>
            </ul>

            <div class="card-actions">
              <!-- Brochure -->
              <a
                href="<?php echo content_url( '/uploads/files/dikilitas-catalogue.pdf' ); ?>"
                target="_blank"
                class="btn btn--solid btn--black btn-card"
              >
                <svg class="icon" aria-hidden="true">
                  <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-pdf"></use>
                </svg>
                Brochure
              </a>

              <!-- Location -->
              <a
                href="https://maps.app.goo.gl/zuT5Maw3akYxjhkM9"
                target="_blank"
                rel="noopener"
                class="btn btn--solid btn--blue btn-card"
              >
                <svg class="icon" aria-hidden="true">
                  <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-map"></use>
                </svg>
                Location
              </a>
            </div>
          </div>
        </article>


        <!-- VOP – ABBASAĞA -->
        <article class="project-card">
          <img
            src="<?php echo content_url( '/uploads/vop/vop-abbasaga.webp' ); ?>"
            alt="VOP Abbasağa"
            loading="lazy"
            decoding="async"
          >

          <div class="content">
            <h3>Abbasağa</h3>
            <p>Zafer Sokak, Abbasağa</p>
            <ul>
              <li>28 apartments</li>
              <li>Opposite Dünya Barış Park</li>
              <li>Delivery: Feb 2026</li>
            </ul>

            <div class="card-actions">
              <!-- Brochure -->
              <a
                href="<?php echo content_url( '/uploads/files/abbasaga-catalogue.pdf' ); ?>"
                target="_blank"
                class="btn btn--solid btn--black btn-card"
              >
                <svg class="icon" aria-hidden="true">
                  <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-pdf"></use>
                </svg>
                Brochure
              </a>

              <!-- Location -->
              <a
                href="https://maps.app.goo.gl/z6GVigSBdiLJt28C7"
                target="_blank"
                rel="noopener"
                class="btn btn--solid btn--blue btn-card"
              >
                <svg class="icon" aria-hidden="true">
                  <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-map"></use>
                </svg>
                Location
              </a>
            </div>
          </div>
        </article>

      </div>
    </div>
  </section>


  <!-- =====================================================
       COMPARISON TABLE
       ====================================================== -->
  <section id="comparison" class="section section-soft">
    <div class="container">
      <h2 class="center">Project overview</h2>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Project</th>
              <th>Units</th>
              <th>Delivery</th>
              <th>Nearby</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Ihlamur</td>
              <td>32 + 2 shops</td>
              <td>Feb 2026</td>
              <td>Ihlamurdere Cad.</td>
            </tr>
            <tr>
              <td>Dikilitaş</td>
              <td>29 units</td>
              <td>Jan 2026</td>
              <td>Dikilitaş Park</td>
            </tr>
            <tr>
              <td>Abbasağa</td>
              <td>28 units</td>
              <td>Feb 2026</td>
              <td>Abbasağa Park</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>


  <!-- =====================================================
       LIFESTYLE
       ====================================================== -->
  <section id="lifestyle" class="section">
    <div class="container two-col">
      <div>
        <h2>Life in Beşiktaş</h2>
        <p>
          Walk to cafés, parks and the Bosphorus. Close to Nişantaşı,
          transport links and universities.
        </p>
        <p>
          We combine central locations with boutique, low-unit buildings.
        </p>
      </div>

      <div>
        <?php
            echo wp_get_attachment_image(
              55701,
              'full',
              false,
              [
                'alt'      => 'Lifestyle in Beşiktaş',
                'loading'  => 'lazy',
                'decoding' => 'async',
              ]
            );?>

      </div>
    </div>
  </section>


  <!-- =====================================================
       CTA – CONTACT
       ====================================================== -->
  <section id="contact" class="section section-soft">
    <div class="container center">
      <h2>Request full details</h2>
      <p>Get floor plans, availability and current pricing.</p>

      <div class="hero-actions">
        <a
          href="https://wa.me/905452054356"
          class="btn btn--solid btn--black"
          target="_blank"
          rel="noopener"
        >
          WhatsApp us
        </a>

        <a
          href="mailto:info@peraproperty.com"
          class="btn btn-secondary"
        >
          Email us
        </a>
      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>
