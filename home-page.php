<?php
/**
 * Template Name: Home page (Final 2025 version)
 * Custom About Us page using lean header/footer + main.css hero
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main 2025-home-page">
<?php
$archive_base_url = get_post_type_archive_link('property'); // your /property/
$hero_img_id      = 55484;
$hero_img_url     = wp_get_attachment_image_url($hero_img_id, 'pera-card');
?>

    <section class="hero hero--center" aria-label="Homepage hero search">
      <?php if ( $hero_img_url ) : ?>
        <img class="hero-media" src="<?php echo esc_url( $hero_img_url ); ?>" alt="" aria-hidden="true">
      <?php endif; ?>
      <div class="hero-overlay" aria-hidden="true"></div>
    
      <div class="hero-content">
    
        <h1>Find Property in Istanbul</h1>
    
        <div class="lead">
          <p>Search new projects and resales across Istanbul’s prime districts.</p>
        </div>
    
        <div class="width-restricter centered">
    
          <form
            method="get"
            class="hero-search-lite glass glass--strong glass--card"
            action="<?php echo esc_url( $archive_base_url ); ?>"
          >
    
            <!-- PROPERTY TYPE -->
            <div class="filter-group text-center">
              <div class="filter-group__label">Property type</div>
    
              <div class="filter-pill-row flex-center" role="radiogroup" aria-label="Property type">
                <label class="pill pill--outline filter-pill pill--active">
                  <input type="radio" name="property_type" value="" checked>
                  <span>All</span>
                </label>
    
                <label class="pill pill--outline filter-pill">
                  <input type="radio" name="property_type" value="apartments">
                  <span>Apartment</span>
                </label>
    
                <label class="pill pill--outline filter-pill">
                  <input type="radio" name="property_type" value="villas">
                  <span>Villa</span>
                </label>
              </div>
            </div>
    
            <!-- BEDROOMS -->
            <div class="filter-group text-center">
              <div class="filter-group__label">Bedrooms</div>
    
              <div class="filter-pill-row flex-center">
                <button
                  type="button"
                  class="pill pill--outline filter-pill filter-pill--all pill--active"
                  data-clear-group="bedrooms"
                >
                  <span>
                    <svg class="icon icon-bed" aria-hidden="true">
                      <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-bed' ); ?>"></use>
                    </svg>
                    Any
                  </span>
                </button>
    
                <?php
                $beds = get_terms( array(
                  'taxonomy'   => 'bedrooms',
                  'hide_empty' => true,
                  'orderby'    => 'name',
                ) );
    
                if ( ! is_wp_error( $beds ) ) :
                  foreach ( $beds as $bed ) :
                    ?>
                    <label class="pill pill--outline filter-pill">
                      <input type="checkbox" name="bedrooms[]" value="<?php echo esc_attr( $bed->slug ); ?>">
                      <span>
                        <svg class="icon icon-bed" aria-hidden="true">
                          <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-bed' ); ?>"></use>
                        </svg>
                        <?php echo esc_html( $bed->name ); ?>
                      </span>
                    </label>
                  <?php
                  endforeach;
                endif;
                ?>
              </div>
            </div>
    
            <!-- LOCATION -->
            <div class="filter-group text-center">
              <div class="filter-group__label">Location</div>
    
              <div class="filter-pill-row flex-center">
                <button
                  type="button"
                  class="pill pill--outline filter-pill filter-pill--all pill--active"
                  data-clear-group="district"
                >
                  <span>Any</span>
                </button>
    
                <?php
                $top_district_slugs = array( 'besiktas', 'sisli', 'kadikoy', 'uskudar', 'sariyer' );
    
                foreach ( $top_district_slugs as $slug ) :
                  $term = get_term_by( 'slug', $slug, 'district' );
                  if ( ! $term || is_wp_error( $term ) ) {
                    continue;
                  }
                  ?>
                  <label class="pill pill--outline filter-pill">
                    <input type="checkbox" name="district[]" value="<?php echo esc_attr( $term->slug ); ?>">
                    <span><?php echo esc_html( $term->name ); ?></span>
                  </label>
                <?php endforeach; ?>
    
                <a class="pill pill--outline" href="<?php echo esc_url( $archive_base_url ); ?>">
                  More areas
                </a>
              </div>
            </div>
    
            <!-- BUDGET -->
            <div class="filter-group text-center">
              <div class="filter-group__label">Budget (USD)</div>
    
              <input type="hidden" name="min_price" id="hero-min-price" value="">
              <input type="hidden" name="max_price" id="hero-max-price" value="">
    
              <div class="filter-pill-row flex-center" role="radiogroup" aria-label="Budget presets">
                <button type="button" class="pill pill--outline filter-pill pill--active" data-budget="">Any</button>
                <button type="button" class="pill pill--outline filter-pill" data-budget="0,250000">Up to $250k</button>
                <button type="button" class="pill pill--outline filter-pill" data-budget="250000,500000">$250k–$500k</button>
                <button type="button" class="pill pill--outline filter-pill" data-budget="500000,1000000">$500k–$1m</button>
                <button type="button" class="pill pill--outline filter-pill" data-budget="1000000,">$1m+</button>
              </div>
            </div>
    
            <!-- KEYWORD + ACTIONS -->
            <div class="filter-row filter-row--footer flex-center" style="margin-top: 16px;">
              <div class="filter-group filter-group--grow text-center">
                <div class="filter-group__label">Keyword</div>
                <input type="text" name="s" placeholder="Search by title or description">
              </div>
    
              <div class="form-actions flex-center">
                <button type="submit" class="btn btn--solid btn--green">Search</button>
                <a class="btn btn btn--solid btn--blue" href="<?php echo esc_url( $archive_base_url . '#results' ); ?>">
                  All filters
                </a>
              </div>
            </div>
    
          </form>
    
        </div>
      </div>
    </section>
    
    
    
    <!-- ABOUT + HOW WE WORK -->
<section class="content-panel">
  <div class="content-panel-box">
    <div class="content-panel-grid">

      <!-- LEFT SIDE: ABOUT + SIGNOFF -->
      <div class="content-panel-left">

        <div class="section-header">
          <h2>ABOUT OUR COMPANY</h2>

          <p>
            Pera Property is a consultancy-led real estate agency focused exclusively on Istanbul.
            We work with both new developments and resale properties, advising clients from initial
            strategy through to title deed.
          </p>

          <p>
            <em>
              Our impartial, whole-of-market approach ensures each client reaches the optimal outcome
              based on their goals — not sales pressure.
            </em>
          </p>
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

      <!-- RIGHT SIDE: HOW WE WORK -->
      <div class="content-panel-right">

        <div class="section-header">
          <h3>How we help you buy in Istanbul</h3>
          <p>
            A clear, structured process designed to reduce risk and remove uncertainty.
          </p>
        </div>

        <ol class="process-steps">
          <li>
            <strong>Understand your objectives</strong>

          </li>

          <li>
            <strong>Shortlist the right options</strong>
          
          </li>

          <li>
            <strong>Guide you through to completion</strong>
         
          </li>
          <li>
            <strong>Manage your investment for as long as you need it</strong>
         
          </li>
        </ol>

        <div class="hero-actions" style="margin-top: 16px;">
          <a class="btn btn--solid btn--green" href="/contact/">Speak to an advisor</a>
          <a class="btn btn--ghost btn--blue" href="/buying-guide/">Read the buyer’s guide</a>
        </div>

      </div>

    </div>
  </div>
</section>


<?php
/* ======================================================
   FEATURED OPPORTUNITIES (HOME)
   Uses existing parts/property-card
   ====================================================== */
$featured_count = 6;

$featured_query = new WP_Query( array(
  'post_type'           => 'property',
  'post_status'         => 'publish',
  'posts_per_page'      => $featured_count,
  'orderby'             => 'date',
  'order'               => 'DESC',
  'ignore_sticky_posts' => true,
) );
?>

<section class="section">
  <div class="container">

    <div class="section-header section-header--center">
      <h2>Featured opportunities</h2>
      <p class="lead">A selection of current listings across Istanbul.</p>
    </div>

    <div class="cards-slider cards-slider--features cards-slider--snap cards-slider--grid-lg" aria-label="Featured properties">
      <?php if ( $featured_query->have_posts() ) : ?>
        <?php while ( $featured_query->have_posts() ) : $featured_query->the_post(); ?>

          <div class="slider-card">
            <?php
              set_query_var( 'pera_property_card_args', array(
                'variant' => 'archive',
              ) );
              get_template_part( 'parts/property-card-v2' );
              set_query_var( 'pera_property_card_args', null );
            ?>
          </div>

        <?php endwhile; ?>
      <?php else : ?>
        <p class="no-results">No featured properties available at the moment.</p>
      <?php endif; ?>
    </div>

  </div>
</section>


<?php get_template_part( 'parts/featured-villa' ); ?>




<?php wp_reset_postdata(); ?>


<!-- ======================================================
     FEATURED DISTRICTS (LOCATION GATEWAY)
     ====================================================== -->
        <section class="section">
          <div class="container">
        
            <div class="section-header section-header--center">
              <h2>Explore Istanbul’s prime districts</h2>
              <p class="lead">
                Start with the areas most requested by international buyers — each with its own character,
                lifestyle, and long-term appeal.
              </p>
            </div>
        
            <div class="cards-slider cards-slider--wide cards-slider--snap cards-slider--grid-lg" aria-label="Featured districts">
        
          <!-- Beşiktaş -->
          <article class="card-shell slider-card">
            <span class="pill pill--brand pill--sm">Central</span>
        
            <h3 style="margin-top: 10px;">Beşiktaş</h3>
        
            <p class="muted" style="margin: 0;">
              Bosphorus living, historic landmarks, universities, and consistently high demand.
            </p>
        
            <div class="property-facilities__pills" style="margin-top: 12px;">
              <span class="pill pill--outline">Bosphorus</span>
              <span class="pill pill--outline">Universities</span>
              <span class="pill pill--outline">City life</span>
            </div>
        
            <div class="hero-actions" style="margin-top: 14px;">
              <a class="btn btn--solid btn--blue" href="/district/besiktas/#results">View listings</a>
              <a class="btn btn--ghost btn--blue" href="https://www.peraproperty.com/besiktas-from-bronze-age-to-ottoman-palaces_51249/">
                Area guide
              </a>
            </div>
          </article>
        
          <!-- Şişli -->
          <article class="card-shell slider-card">
            <span class="pill pill--brand pill--sm">Business &amp; Lifestyle</span>
        
            <h3 style="margin-top: 10px;">Şişli</h3>
        
            <p class="muted" style="margin: 0;">
              Central access to business districts, shopping, and established neighbourhoods.
            </p>
        
            <div class="property-facilities__pills" style="margin-top: 12px;">
              <span class="pill pill--outline">Nişantaşı</span>
              <span class="pill pill--outline">Metro</span>
              <span class="pill pill--outline">Urban living</span>
            </div>
        
            <div class="hero-actions" style="margin-top: 14px;">
              <a class="btn btn--solid btn--blue" href="/district/sisli/#results">View listings</a>
              <a class="btn btn--ghost btn--blue" href="https://www.peraproperty.com/sisli-the-heart-of-modern-istanbul_51392/">
                Area guide
              </a>
            </div>
          </article>
        
          <!-- Sarıyer -->
          <article class="card-shell slider-card">
            <span class="pill pill--brand pill--sm">Premium &amp; Green</span>
        
            <h3 style="margin-top: 10px;">Sarıyer</h3>
        
            <p class="muted" style="margin: 0;">
              Green surroundings, villa communities, and proximity to Istanbul’s key business hubs.
            </p>
        
            <div class="property-facilities__pills" style="margin-top: 12px;">
              <span class="pill pill--outline">Villas</span>
              <span class="pill pill--outline">Nature</span>
              <span class="pill pill--outline">Long-term value</span>
            </div>
        
            <div class="hero-actions" style="margin-top: 14px;">
              <a class="btn btn--solid btn--green" href="/district/sariyer/#results">View listings</a>
              <a class="btn btn--ghost btn--green" href="https://www.peraproperty.com/buying-property-in-sariyer-istanbul-explore-coastal-charm_50776/">
                Area guide
              </a>
            </div>
          </article>
        
        </div>


    <div class="hero-actions flex-center" style="margin-top: 18px;">
      <a class="btn btn--solid btn--blue" href="/property/">Browse all districts</a>
      <a class="btn btn--solid btn--green" href="/contact-us/">Not sure where to start?</a>
    </div>

  </div>
</section>

<?php get_template_part( 'parts/featured-apartment' ); ?>


<!-- BUYER JOURNEY (INFO STEPS – 4 STEPS) -->
<section class="section">
  <div class="container">

    <header class="section-header section-header--center">
      <h2>Buyer journey</h2>
      <p>
        A clear, structured process from initial consultation to ownership —
        designed to reduce risk and remove uncertainty.
      </p>
    </header>

    <div class="info-steps">

      <!-- STEP 1 -->
      <article class="info-step">
        <div class="info-step-icon">
          <span class="info-step-number">1</span>
        </div>
        <div class="info-step-body">
          <h3 class="info-step-title">Strategy &amp; shortlist</h3>
          <p class="info-step-text">
            We define your objectives — lifestyle, rental yield, or capital growth —
            and curate suitable projects and resale opportunities across Istanbul.
          </p>
        </div>
      </article>

      <!-- STEP 2 -->
      <article class="info-step">
        <div class="info-step-icon">
          <span class="info-step-number">2</span>
        </div>
        <div class="info-step-body">
          <h3 class="info-step-title">Viewings &amp; due diligence</h3>
          <p class="info-step-text">
            We coordinate viewings (in person or remotely), explain pricing,
            and guide legal and technical checks with trusted professionals.
          </p>
        </div>
      </article>

      <!-- STEP 3 -->
      <article class="info-step">
        <div class="info-step-icon">
          <span class="info-step-number">3</span>
        </div>
        <div class="info-step-body">
          <h3 class="info-step-title">Negotiation &amp; purchase</h3>
          <p class="info-step-text">
            We manage negotiations, payment milestones, and the purchase process
            through to title deed registration.
          </p>
        </div>
      </article>

      <!-- STEP 4 -->
      <article class="info-step">
        <div class="info-step-icon">
          <span class="info-step-number">4</span>
        </div>
        <div class="info-step-body">
          <h3 class="info-step-title">After-sales support</h3>
          <p class="info-step-text">
            Beyond completion, we assist with rentals, property management,
            resale strategy, and ongoing advisory support as your plans evolve.
          </p>
        </div>
      </article>

    </div>

    <div class="hero-actions flex-center" style="margin-top: 16px;">
      <a class="btn btn--solid btn--green" href="/contact-us/">Speak to an advisor</a>
    </div>

  </div>
</section>





<!-- ======================================================
     SELL WITH PERA (HOMEPAGE TEASER — OPTION 2)
     ====================================================== -->
<section class="content" id="sell-with-pera">
  <div class="content-panel-box">
    <div class="content-panel-grid">

      <div class="content-panel-left">
        <header class="section-header">
          <h2>Sell with Pera</h2>
          <p>
            We treat every listing like a bespoke project — pricing strategy, premium marketing,
            buyer qualification, negotiation, and completion coordination.
          </p>
        </header>

        <ul class="checklist">
          <li>
            <svg class="icon icon-tick" aria-hidden="true">
              <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-check' ); ?>"></use>
            </svg>
            Accurate pricing strategy (not guesswork)
          </li>
          <li>
            <svg class="icon icon-tick" aria-hidden="true">
              <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-check' ); ?>"></use>
            </svg>
            Strong visibility to local &amp; international buyers
          </li>
          <li>
            <svg class="icon icon-tick" aria-hidden="true">
              <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-check' ); ?>"></use>
            </svg>
            End-to-end negotiation and paperwork handling
          </li>
        </ul>

        <p style="margin-top: 12px; margin-bottom: 0;">
          <a href="/sell-your-istanbul-real-estate/">Learn how we sell property in Istanbul</a>
        </p>
      </div>

      <div class="content-panel-right">
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

        <p class="muted" style="margin-top: 10px; margin-bottom: 0;">
          If you already have a property in mind, <a href="/contact/">request a valuation</a>.
        </p>
      </div>

    </div>
  </div>
</section>



</main>


<?php
get_footer();
