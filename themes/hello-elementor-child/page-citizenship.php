<?php
/**
 * Template Name: Turkish Citizenship by Investment (Lean)
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main">

        <!-- =====================================================
         HERO – CITIZENSHIP
         Canonical structure + fallback background (ID 55756)
         ===================================================== -->
            <section class="hero hero--left hero--citizenship" id="citizenship-hero">
            
              <div class="hero__media" aria-hidden="true">
                <?php

                  {
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
            
                    /*
                    // If you prefer to "grey out" the background for now, comment the block above
                    // and leave the hero as a solid-colour hero (background: var(--brand)).
                    */
                  }
                ?>
                <div class="hero-overlay" aria-hidden="true"></div>
              </div>
            
              <div class="hero-content">
                <h1>Turkish Citizenship by Real Estate Investment</h1>
            
                <p class="lead">
                  Obtain a Turkish passport for you and your family with a
                  minimum USD&nbsp;400,000 real estate investment in Istanbul.
                  Pera Property manages the entire process from property
                  selection to passports in hand.
                </p>
            
                <div class="hero-actions">
                  <a href="#citizenship-callback" class="btn btn--solid btn--green">
                    Book a consultation
                  </a>
            
                  <?php /* Enable later when PDF is ready
                  <a href="/property/?special=citizenship" class="btn btn-secondary">
                    Download guide
                  </a>
                  */ ?>
                </div>
              </div>
            
            </section>


  <!-- =====================================================
       OVERVIEW PANEL: FULL-SERVICE CONSULTANCY
       ====================================================== -->
  <section class="content-panel">
    <div class="content-panel-box">
      <div class="content-panel-grid">

        <!-- LEFT: TEXT -->
        <div>
          <header class="section-header">
            <h2>Full-service citizenship consultancy in Istanbul</h2>
            <p>
              Since 2016, Pera’s founders and legal partners have assisted
              international clients with Turkish Citizenship by Investment,
              combining specialist real estate knowledge with a dedicated
              immigration and legal team.
            </p>
          </header>

            <ul class="checklist">
              <li>
                <svg class="icon icon-tick" aria-hidden="true">
                  <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                </svg>
                End-to-end guidance from first call to Turkish passports.
              </li>
            
              <li>
                <svg class="icon icon-tick" aria-hidden="true">
                  <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                </svg>
                Carefully curated portfolio of CBI-eligible properties in Istanbul.
              </li>
            
              <li>
                <svg class="icon icon-tick" aria-hidden="true">
                  <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                </svg>
                Coordination with experienced Turkish lawyers and tax advisors.
              </li>
            
              <li>
                <svg class="icon icon-tick" aria-hidden="true">
                  <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                </svg>
                Support with bank accounts, tax numbers and residency permits.
              </li>
            
              <li>
                <svg class="icon icon-tick" aria-hidden="true">
                  <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                </svg>
                Transparent reporting and regular updates throughout the process.
              </li>
            </ul>


          <div class="signoff-card">
            <div class="signoff-avatar">
                <?php
                echo wp_get_attachment_image(
                    55700,
                    'full',
                    false,
                    array(
                        'class'   => '',
                        'alt'     => 'Pera Property Director',
                        'loading' => 'lazy',
                        'decoding'=> 'async',
                    )
                );
                ?>
            </div>

            <div class="signoff-text">
              <h5>Pera Property Directors</h5>
              <p>
                Nearly 40 years’ combined experience in Istanbul real estate
                and citizenship by investment.
              </p>
            </div>
          </div>
        </div>
        
        <!-- RIGHT: MEDIA (IMAGE) -->
        <div>
            <div class="media-frame media-frame--image-fill">
            <?php
            echo wp_get_attachment_image(
                55703,
                'full',
                false,
                array(
                    'class'    => 'media-image',
                    'loading'  => 'lazy',
                    'decoding' => 'async',
                    'alt'      => esc_attr(
                        'Family reviewing Turkish citizenship by investment options in a modern Istanbul apartment'
                    ),
                )
            );
            ?>
          </div>
        </div>



      </div><!-- /.content-panel-grid -->
    </div><!-- /.content-panel-box -->
  </section>

  <!-- =====================================================
       OUR FULL-PACKAGE CITIZENSHIP SERVICE
       ====================================================== -->
  <section class="section section-soft">
    <div class="container">

      <header class="section-header section-header--center">
        <h2>Our full-package citizenship service</h2>
        <p>
          Everything you need for a successful Turkish citizenship application,
          delivered as a single coordinated package by Pera and our legal partners.
        </p>
      </header>

      <div class="feature-grid">

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>CBI-eligible properties</h3>
          </div>
          <div class="feature-card-body">
            <p>
              Access a curated list of Istanbul properties that fully comply with
              the USD&nbsp;400,000 citizenship requirement and land registry rules.
            </p>
          </div>
        </article>

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>Legal due diligence</h3>
          </div>
          <div class="feature-card-body">
            <p>
              Independent Turkish lawyers check title deeds, permits and encumbrances
              before you commit to any investment.
            </p>
          </div>
        </article>

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>Banking &amp; tax numbers</h3>
          </div>
          <div class="feature-card-body">
            <p>
              Support with obtaining your Turkish tax number, opening local bank
              accounts and arranging secure fund transfers.
            </p>
          </div>
        </article>

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>Purchase &amp; title deed</h3>
          </div>
          <div class="feature-card-body">
            <p>
              End-to-end assistance with the property purchase, including sales
              contracts, valuation reports and title deed transfer.
            </p>
          </div>
        </article>

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>Citizenship filing</h3>
          </div>
          <div class="feature-card-body">
            <p>
              Preparation and submission of residency and citizenship files for
              all eligible family members with ongoing follow-up.
            </p>
          </div>
        </article>

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>After-sales &amp; rentals</h3>
          </div>
          <div class="feature-card-body">
            <p>
              Optional property management and rental services to help you
              generate income from your citizenship investment.
            </p>
          </div>
        </article>

      </div><!-- /.feature-grid -->

    </div><!-- /.container -->
  </section>

  <!-- =====================================================
       KEY FACTS ABOUT THE PROGRAMME
       ====================================================== -->
  <section class="section section-soft" id="citizenship-key-facts">
    <div class="container">

      <header class="section-header section-header--center">
        <h2>Key facts about Turkish Citizenship by Investment</h2>
        <p>
          A fast, flexible route to Turkish citizenship for families who invest in qualifying real estate.
        </p>
      </header>

      <div class="info-steps">

        <article class="info-step">
          <div class="info-step-icon">
            <span class="info-step-number">1</span>
          </div>
          <div class="info-step-body">
            <h3 class="info-step-title">USD 400,000+ real estate</h3>
            <p class="info-step-text">
              You must invest at least USD&nbsp;400,000 in one or more Turkish properties that meet the
              programme’s legal and valuation requirements.
            </p>
          </div>
        </article>

        <article class="info-step">
          <div class="info-step-icon">
            <span class="info-step-number">2</span>
          </div>
          <div class="info-step-body">
            <h3 class="info-step-title">Spouse &amp; children included</h3>
            <p class="info-step-text">
              Your spouse and children under 18 can be included in the same citizenship application.
              Adult children or parents may require separate routes.
            </p>
          </div>
        </article>

        <article class="info-step">
          <div class="info-step-icon">
            <span class="info-step-number">3</span>
          </div>
          <div class="info-step-body">
            <h3 class="info-step-title">Approx. 4–6 month timeline</h3>
            <p class="info-step-text">
              From property purchase and residence permits to full citizenship approval, most complete
              files are finalised within around 4–6 months.
            </p>
          </div>
        </article>

        <article class="info-step">
          <div class="info-step-icon">
            <span class="info-step-number">4</span>
          </div>
          <div class="info-step-body">
            <h3 class="info-step-title">Dual citizenship possible</h3>
            <p class="info-step-text">
              Many nationalities can keep their existing passport when obtaining Turkish citizenship,
              subject to their home country’s rules on dual nationality.
            </p>
          </div>
        </article>

      </div><!-- /.info-steps -->

    </div><!-- /.container -->
  </section>

  <!-- =====================================================
       CONDITIONS TO BE MET
       ====================================================== -->
  <section class="section" id="conditions">
    <div class="container">

      <header class="section-header section-header--center">
        <h2>Conditions to be met</h2>
      </header>

      <div class="info-steps">

        <!-- VALUE -->
        <article class="info-step">
          <div class="info-step-icon">
            <svg class="icon" aria-hidden="true" width="24" height="24">
              <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-currency"></use>
            </svg>
          </div>
          <div class="info-step-body">
            <h3 class="info-step-title">Value</h3>
            <p class="info-step-text">
              The total value of the assets must be at least USD&nbsp;400,000 at the time of purchase and valuation.
            </p>
          </div>
        </article>

        <!-- TITLE -->
        <article class="info-step">
          <div class="info-step-icon">
            <svg class="icon" aria-hidden="true" width="24" height="24">
              <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-tapu"></use>
            </svg>
          </div>
          <div class="info-step-body">
            <h3 class="info-step-title">Title</h3>
            <p class="info-step-text">
              Each asset must have its own legal title deed (TAPU) and be properly registered at the land registry.
            </p>
          </div>
        </article>

        <!-- MULTIPLE PROPERTY -->
        <article class="info-step">
          <div class="info-step-icon">
            <svg class="icon" aria-hidden="true" width="24" height="24">
              <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-property"></use>
            </svg>
          </div>
          <div class="info-step-body">
            <h3 class="info-step-title">Multiple properties</h3>
            <p class="info-step-text">
              You may use one or more properties to reach the minimum amount. They do not have to be in the same building or project.
            </p>
          </div>
        </article>

        <!-- LEGAL CHARGE -->
        <article class="info-step">
          <div class="info-step-icon">
            <svg class="icon" aria-hidden="true" width="24" height="24">
              <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-hammer"></use>
            </svg>
          </div>
          <div class="info-step-body">
            <h3 class="info-step-title">Legal charge</h3>
            <p class="info-step-text">
              A restriction is registered on the title deed(s) confirming the property(ies)
              cannot be sold for at least three years.
            </p>
          </div>
        </article>

        <!-- VALUATION -->
        <article class="info-step">
          <div class="info-step-icon">
            <svg class="icon" aria-hidden="true" width="24" height="24">
              <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-valuation"></use>
            </svg>
          </div>
          <div class="info-step-body">
            <h3 class="info-step-title">Valuation</h3>
            <p class="info-step-text">
              The total value must be confirmed by a valuation report issued by an SPK-licensed surveyor.
              Properties sold by GYOs are exempt.
            </p>
          </div>
        </article>

      </div><!-- /.info-steps -->

    </div><!-- /.container -->
  </section>

  <!-- =====================================================
       WHO CAN APPLY?
       ====================================================== -->
  <section class="section section-soft" id="who-can-apply">
    <div class="container">

      <header class="section-header section-header--center">
        <h2>Who can apply under one citizenship file?</h2>
        <p>
          The Turkish Citizenship by Investment programme allows the main investor to include
          their immediate family under a single coordinated application.
        </p>
      </header>

      <div class="feature-grid">

        <!-- MAIN APPLICANT -->
        <article class="feature-card">
          <div class="feature-card-header">
            <svg class="icon" width="28" height="28" aria-hidden="true">
              <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-user"></use>
            </svg>
            <h3>Main investor</h3>
          </div>
          <div class="feature-card-body">
            <p>
              The primary applicant making the qualifying real estate investment of at least
              USD&nbsp;400,000.
            </p>
          </div>
        </article>

        <!-- SPOUSE -->
        <article class="feature-card">
          <div class="feature-card-header">
            <svg class="icon" width="28" height="28" aria-hidden="true">
              <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-users"></use>
            </svg>
            <h3>Spouse</h3>
          </div>
          <div class="feature-card-body">
            <p>
              Your husband or wife can be added to the same citizenship file as a dependent
              without any extra investment requirement.
            </p>
          </div>
        </article>

        <!-- CHILDREN UNDER 18 -->
        <article class="feature-card">
          <div class="feature-card-header">
            <svg class="icon" width="28" height="28" aria-hidden="true">
              <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-user-add"></use>
            </svg>
            <h3>Children under 18</h3>
          </div>
          <div class="feature-card-body">
            <p>
              All children below 18 years of age can be included under the same application
              as long as the family relationship is documented.
            </p>
          </div>
        </article>

        <!-- ADULT CHILDREN (SPECIAL NEEDS) -->
        <article class="feature-card">
          <div class="feature-card-header">
            <svg class="icon" width="28" height="28" aria-hidden="true">
              <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-shield-check"></use>
            </svg>
            <h3>Adult children (special needs)</h3>
          </div>
          <div class="feature-card-body">
            <p>
              Adult children with officially recognised disabilities or special needs may
              qualify as dependents, subject to supporting medical reports.
            </p>
          </div>
        </article>

        <!-- PARENTS (SEPARATE ROUTE) -->
        <article class="feature-card">
          <div class="feature-card-header">
            <svg class="icon" width="28" height="28" aria-hidden="true">
              <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-home"></use>
            </svg>
            <h3>
              Parents <span style="opacity:.6; font-weight:400;">(separate route)</span>
            </h3>
          </div>
          <div class="feature-card-body">
            <p>
              Parents are not included in the main citizenship file, but we can advise on
              suitable residency options or parallel applications.
            </p>
          </div>
        </article>

        <!-- COORDINATED FILE -->
        <article class="feature-card">
          <div class="feature-card-header">
            <svg class="icon" width="28" height="28" aria-hidden="true">
              <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-folder-duplicate"></use>
            </svg>
            <h3>One coordinated process</h3>
          </div>
          <div class="feature-card-body">
            <p>
              Your lawyers prepare and file all family applications together, keeping the
              documentation, timelines and approvals under tight control.
            </p>
          </div>
        </article>

      </div><!-- /.feature-grid -->

    </div><!-- /.container -->
  </section>

  <!-- =====================================================
       CITIZENSHIP BENEFITS
       ====================================================== -->
  <section class="section" id="citizenship-benefits">
    <div class="container">

      <header class="section-header section-header--center">
        <h2>Citizenship benefits</h2>
      </header>

      <div class="feature-grid">

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>Fast-track citizenship</h3>
          </div>
          <div class="feature-card-body">
            <p>Passports typically granted within 4–6 months, subject to government processing times.</p>
          </div>
        </article>

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>Affordable second passport</h3>
          </div>
          <div class="feature-card-body">
            <p>Obtain Turkish citizenship with a qualifying real estate investment from USD&nbsp;400,000.</p>
          </div>
        </article>

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>Visa-free travel</h3>
          </div>
          <div class="feature-card-body">
            <p>Access to a wide network of countries with a Turkish passport, plus easy visas to key markets.</p>
          </div>
        </article>

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>No residence requirement</h3>
          </div>
          <div class="feature-card-body">
            <p>No obligation to live in Türkiye before or after citizenship approval.</p>
          </div>
        </article>

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>Entire family eligible</h3>
          </div>
          <div class="feature-card-body">
            <p>Include your spouse and children under 18 in the same application.</p>
          </div>
        </article>

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>Real estate investment</h3>
          </div>
          <div class="feature-card-body">
            <p>Invest in income-generating, Istanbul property with long-term capital growth potential.</p>
          </div>
        </article>

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>3-year holding period</h3>
          </div>
          <div class="feature-card-body">
            <p>After three years you are free to restructure, sell or reinvest your property portfolio.</p>
          </div>
        </article>

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>Attractive tax planning</h3>
          </div>
          <div class="feature-card-body">
            <p>Citizenship can form part of a broader tax, residency and asset-planning strategy.</p>
          </div>
        </article>

      </div><!-- /.feature-grid -->

    </div><!-- /.container -->
  </section>
            
           
        

            
    <!-- =====================================================
       REQUEST A CALLBACK (FORM CTA)
       ====================================================== -->
                   
             

  <section class="section section-soft" id="citizenship-callback">
    <div class="container">

      <div class="enquiry-cta">
        <?php if ( isset( $_GET['enquiry'] ) && $_GET['enquiry'] === 'ok' ) : ?>
          <div class="alert alert-success">
            Thank you – your enquiry has been received. We’ll contact you shortly.
          </div>
        <?php endif; ?>

          <header class="enquiry-cta-header">
            <h2>Request a call back</h2>
            <p>
              Share a few details and one of our consultants will contact you
              to discuss your plans and answer any questions.
            </p>
          </header>


    <section id="citizenship-form" class="citizenship-form-section">
        <?php if ( isset( $_GET['enquiry'] ) ) : ?>
            <?php
            $status     = sanitize_text_field( $_GET['enquiry'] );
            $is_success = ( $status === 'ok' );
            ?>
            <div class="citizenship-alert citizenship-alert--<?php echo $is_success ? 'success' : 'error'; ?>">
                <?php if ( $is_success ) : ?>
                    <p>Thank you for your enquiry. Our team will contact you shortly.</p>
                <?php else : ?>
                    <p>Sorry, your message could not be sent. Please try again or contact us directly.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>


        <form
          class="enquiry-cta-form"
          method="post"
          action="<?php echo esc_url( get_permalink() ); ?>"
        >
          <?php wp_nonce_field( 'pera_citizenship_enquiry', 'pera_citizenship_nonce'); ?>
          <input type="hidden" name="pera_citizenship_action" value="1">

      
          <div class="enquiry-cta-grid">

            <!-- LEFT: REQUIRED INFORMATION -->
            <div class="enquiry-cta-column">
              <h3 class="enquiry-cta-subtitle">Required information</h3>

              <div class="cta-fieldset">
                <label class="cta-field">
                  <span class="cta-label">Name</span>
                  <input
                    type="text"
                    name="name"
                    class="cta-control"
                    placeholder="Your full name"
                    required
                  >
                </label>

                <label class="cta-field">
                  <span class="cta-label">Phone</span>
                  <input
                    type="tel"
                    name="phone"
                    class="cta-control"
                    placeholder="+90 ..."
                    required
                  >
                </label>

                <label class="cta-field">
                  <span class="cta-label">Email</span>
                  <input
                    type="email"
                    name="email"
                    class="cta-control"
                    placeholder="you@example.com"
                    required
                  >
                </label>
              </div>

              <div class="cta-fieldset cta-fieldset--inline">
                <span class="cta-label cta-label--muted">Preferred contact method</span>
                <div class="cta-options">
                  <label class="cta-checkbox">
                    <input type="checkbox" name="contact_method[]" value="phone">
                    <span>Phone</span>
                  </label>
                  <label class="cta-checkbox">
                    <input type="checkbox" name="contact_method[]" value="email">
                    <span>Email</span>
                  </label>
                  <label class="cta-checkbox">
                    <input type="checkbox" name="contact_method[]" value="whatsapp">
                    <span>WhatsApp</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- RIGHT: ADDITIONAL INFORMATION -->
            <div class="enquiry-cta-column">
              <h3 class="enquiry-cta-subtitle">Additional information</h3>

              <div class="cta-fieldset">
                <label class="cta-field">
                  <span class="cta-label">Type of enquiry</span>
                  <select name="enquiry_type" class="cta-control">
                    <option value="general">General enquiry</option>
                    <option value="citizenship-only">Citizenship only</option>
                    <option value="citizenship-property">Citizenship &amp; property investment</option>
                    <option value="consultation">Schedule a video consultation</option>
                  </select>
                </label>

                <label class="cta-field">
                  <span class="cta-label">Family members</span>
                  <input
                    type="text"
                    name="family"
                    class="cta-control"
                    placeholder="Number of applicants, ages of children, etc."
                  >
                </label>

                <label class="cta-field">
                  <span class="cta-label">Questions or comments</span>
                  <textarea
                    name="message"
                    rows="3"
                    class="cta-control"
                    placeholder="Tell us a little about your situation or preferred timeline."
                  ></textarea>
                </label>
              </div>
            </div>

          </div><!-- /.enquiry-cta-grid -->

          <!-- CONSENT + SUBMIT -->
          <div class="enquiry-cta-footer">
            <label class="cta-checkbox">
              <input type="checkbox" name="policy" required>
              <span>
                I agree to the terms of the
                <a href="/privacy-policy/" target="_blank" rel="noopener">Privacy Policy</a>.
              </span>
            </label>

            <button type="submit" class="btn btn--ghost btn--green">
              Request a call back
            </button>
          </div>

        </form>
        </section>

      </div><!-- /.enquiry-cta -->
    </div><!-- /.container -->
  </section>



  <!-- =====================================================
       WHY CHOOSE PERA
       ====================================================== -->
  <section class="section">
    <div class="container">

      <header class="section-header section-header--center">
        <h2>Why choose Pera for Turkish citizenship?</h2>
        <p>
          A specialist Istanbul real estate agency with a dedicated focus on
          citizenship and residency investors.
        </p>
      </header>

      <div class="feature-grid">

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>Specialist CBI portfolio</h3>
          </div>
          <div class="feature-card-body">
            <p>
              We focus on modern, well-located Istanbul developments that appeal
              both to citizenship investors and future tenants.
            </p>
          </div>
        </article>

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>Transparent fees</h3>
          </div>
          <div class="feature-card-body">
            <p>
              Clear, upfront fee structures for both real estate and legal work,
              with no hidden extras during the process.
            </p>
          </div>
        </article>

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>Local + international team</h3>
          </div>
          <div class="feature-card-body">
            <p>
              English and Turkish-speaking consultants based in Istanbul,
              backed by experienced immigration lawyers.
            </p>
          </div>
        </article>

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>End-to-end project management</h3>
          </div>
          <div class="feature-card-body">
            <p>
              One point of contact coordinating developers, valuers, banks and
              lawyers so your application stays on track.
            </p>
          </div>
        </article>

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>Istanbul market insight</h3>
          </div>
          <div class="feature-card-body">
            <p>
              Advice on which districts hold long-term value, rental demand
              and resale liquidity once your lock-in period ends.
            </p>
          </div>
        </article>

        <article class="feature-card">
          <div class="feature-card-header">
            <h3>Long-term relationship</h3>
          </div>
          <div class="feature-card-body">
            <p>
              Ongoing support with rentals, resale or portfolio restructuring
              once your citizenship has been granted.
            </p>
          </div>
        </article>

      </div><!-- /.feature-grid -->

    </div><!-- /.container -->
  </section>


<!-- =====================================================
     CITIZENSHIP TIMELINE
     ====================================================== -->
<section class="section section-soft" id="citizenship-timeline">
  <div class="container">

    <header class="section-header section-header--center">
      <h2>Turkish citizenship acquisition timeline</h2>
      <p>
        An indicative timeline from your first consultation with Pera to receiving
        your Turkish passports, assuming a complete and correctly prepared file.
      </p>
    </header>

    <ol class="timeline">
      <!-- 1 -->
      <li class="timeline-step">
        <div class="timeline-side">
          <span class="timeline-duration">3–5 days</span>
          <span class="timeline-phase">Preparation</span>
        </div>

        <div class="timeline-marker">
          <span class="timeline-number">1</span>
        </div>

        <div class="timeline-body">
          <h3>Consultation &amp; planning</h3>
          <p>
            We assess your family situation, timeline and budget, and explain the latest programme rules and documentation requirements.
          </p>
        </div>
      </li>

      <!-- 2 -->
      <li class="timeline-step">
        <div class="timeline-side">
          <span class="timeline-duration">2–4 weeks</span>
          <span class="timeline-phase">Document collection</span>
        </div>

        <div class="timeline-marker">
          <span class="timeline-number">2</span>
        </div>

        <div class="timeline-body">
          <h3>Prepare documents</h3>
          <p>
            Our lawyers provide a detailed checklist and help you gather passports,
            civil documents, photos, powers of attorney and any required translations
            and apostilles.
          </p>
        </div>
      </li>

      <!-- 3 -->
      <li class="timeline-step">
        <div class="timeline-side">
          <span class="timeline-duration">1–2 weeks</span>
          <span class="timeline-phase">Investment</span>
        </div>

        <div class="timeline-marker">
          <span class="timeline-number">3</span>
        </div>

        <div class="timeline-body">
          <h3>Select &amp; reserve property</h3>
          <p>
            Together we shortlist CBI-eligible projects in Istanbul, arrange viewings
            (in person or remote) and reserve your chosen units with the developer.
          </p>
        </div>
      </li>

      <!-- 4 -->
      <li class="timeline-step">
        <div class="timeline-side">
          <span class="timeline-duration">2–4 weeks</span>
          <span class="timeline-phase">Completion</span>
        </div>

        <div class="timeline-marker">
          <span class="timeline-number">4</span>
        </div>

        <div class="timeline-body">
          <h3>Complete investment &amp; title deed</h3>
          <p>
            You transfer funds, we obtain the valuation report and our lawyers complete title deed
            registrations with the 3-year no-sale restriction recorded on the TAPU.
          </p>
        </div>
      </li>

      <!-- 5 -->
      <li class="timeline-step">
        <div class="timeline-side">
          <span class="timeline-duration">4–8 weeks</span>
          <span class="timeline-phase">Processing</span>
        </div>

        <div class="timeline-marker">
          <span class="timeline-number">5</span>
        </div>

        <div class="timeline-body">
          <h3>Residence permits &amp; citizenship filing</h3>
          <p>
            Your family’s residence permits and citizenship files are submitted.
            Your lawyers track the application and respond to any requests from
            the authorities.
          </p>
        </div>
      </li>

      <!-- 6 -->
      <li class="timeline-step">
        <div class="timeline-side">
          <span class="timeline-duration">Approx. 4–6 months total</span>
          <span class="timeline-phase">Approval</span>
        </div>

        <div class="timeline-marker">
          <span class="timeline-number">6</span>
        </div>

        <div class="timeline-body">
          <h3>Receive Turkish passports</h3>
          <p>
            Once approved, ID cards and passports are issued for all successful
            applicants, either in Türkiye or via your local consulate.
          </p>
        </div>
      </li>
    </ol>

  </div>
</section>

<!-- =====================================================
     DOCUMENTS REQUIRED
     ====================================================== -->
<section class="section section-soft" id="citizenship-documents">
  <div class="container">

    <header class="section-header section-header--center">
      <h2>Documents required</h2>
      <p>
        Key documents typically needed for a complete Turkish citizenship
        application. Your lawyer will confirm the exact list based on your
        family situation and nationality.
      </p>
    </header>

    <div class="docs-list">

      <!-- 1. Passport -->
      <details class="doc-item">
        <summary>
          <span class="doc-title">Valid passport</span>
          <span class="doc-icon" aria-hidden="true"></span>
        </summary>
        <div class="doc-body">
          <p>A valid passport or recognised travel document for each applicant.</p>
        </div>
      </details>

      <!-- 2. Marriage / marital status -->
      <details class="doc-item">
        <summary>
          <span class="doc-title">Marriage certificate / marital status</span>
          <span class="doc-icon" aria-hidden="true"></span>
        </summary>
        <div class="doc-body">
          <p>
            If married, a marriage certificate. If divorced, a divorce certificate.
            If never married, an official certificate of single status.
          </p>
        </div>
      </details>

      <!-- 3. Birth certificate -->
      <details class="doc-item">
        <summary>
          <span class="doc-title">Birth certificates</span>
          <span class="doc-icon" aria-hidden="true"></span>
        </summary>
        <div class="doc-body">
          <p>
            Birth certificates for all applicants. If unavailable, a consular
            affidavit confirming your place and date of birth may be needed.
          </p>
        </div>
      </details>

      <!-- 4. Spouse & children -->
      <details class="doc-item">
        <summary>
          <span class="doc-title">Details of spouse and children</span>
          <span class="doc-icon" aria-hidden="true"></span>
        </summary>
        <div class="doc-body">
          <p>
            Passports and birth certificates for your spouse and all children
            included in the application.
          </p>
        </div>
      </details>

      <!-- 5. Turkish tax number -->
      <details class="doc-item">
        <summary>
          <span class="doc-title">Turkish tax number</span>
          <span class="doc-icon" aria-hidden="true"></span>
        </summary>
        <div class="doc-body">
          <p>
            A Turkish tax number for the main applicant, issued by any tax
            office or online through the Revenue Administration.
          </p>
        </div>
      </details>

      <!-- 6. Appraisal reports -->
      <details class="doc-item">
        <summary>
          <span class="doc-title">Appraisal reports</span>
          <span class="doc-icon" aria-hidden="true"></span>
        </summary>
        <div class="doc-body">
          <p>
            Each property must be
            independently valued by an SPK-licensed surveyor to confirm that
            the total investment meets the legal threshold.
          </p>
        </div>
      </details>

      <!-- 7. Title deeds -->
      <details class="doc-item">
        <summary>
          <span class="doc-title">Title deeds of the purchased assets</span>
          <span class="doc-icon" aria-hidden="true"></span>
        </summary>
        <div class="doc-body">
          <p>
            Title deeds (TAPU) for each qualifying asset, with the three-year
            no-sale restriction registered where required.
          </p>
        </div>
      </details>

      <!-- 8. Confirmation of investment -->
      <details class="doc-item">
        <summary>
          <span class="doc-title">Confirmation of investment</span>
          <span class="doc-icon" aria-hidden="true"></span>
        </summary>
        <div class="doc-body">
          <p>
            Official confirmation from the land registry or relevant authority
            that the investment has been completed in line with the citizenship rules.
          </p>
        </div>
      </details>

      <!-- 9. Authentication / legalisation -->
      <details class="doc-item">
        <summary>
          <span class="doc-title">Authentication &amp; legalisation</span>
          <span class="doc-icon" aria-hidden="true"></span>
        </summary>
        <div class="doc-body">
          <p>
            Certain documents (birth, marriage, divorce certificates or
            single-status confirmations) must be apostilled or otherwise
            legalised and officially translated into Turkish. Your lawyer will
            advise on the exact process for your country.
          </p>
        </div>
      </details>

    </div><!-- /.docs-list -->

  </div>
</section>

<!-- =====================================================
     FREQUENTLY ASKED QUESTIONS
     ====================================================== -->
<section class="section section-soft" id="citizenship-faq">
  <div class="container">

    <header class="section-header section-header--center">
      <h2>Frequently Asked Questions</h2>
      <p>
        Common questions about the Turkish Citizenship by Investment process,
        documents and practical requirements.
      </p>
    </header>

    <div class="doc-accordion">

      <!-- 1 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: What is the surveyor’s role and what is an appraisal report?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>
            The surveyor provides a report which must match or exceed the purchase price
            of the property. This is to ensure that the buyer and seller are carrying out
            a transaction in good faith. The Ministry of Urbanisation then verifies this
            report and confirms the value and purchase price. It must exceed $400,000.
          </p>
        </div>
      </details>

      <!-- 2 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: Do I need to be in Turkey for the process?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>
            The applicant and spouse (but not any children under 18) must visit Turkey to
            attend the residency application (where biometrics are taken), and then again
            on successful outcome of the application in order to receive the Turkish ID and
            passport. The immigration office can only be attended by the applicant or the
            applicant’s solicitor. The solicitor must be registered with the Turkish Bar
            Association.
          </p>
        </div>
      </details>

      <!-- 3 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: How long does it take, some people say maximum 60 days?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>
            Impossible. The purchase process usually takes 3–4 weeks in total. If a VAT
            exemption is involved, a further 2–3 weeks is needed just for that process.
            Document preparation by the applicant usually takes 3–4 weeks. Even if documents
            are prepared well in advance, the application process itself can take around
            4 weeks. The assessment of the application takes a minimum of two months, and
            on average around three months. Expect to be granted citizenship after a minimum
            period of three months; in most cases citizenship is granted around six months
            after the date of application.
          </p>
        </div>
      </details>

      <!-- 4 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: What is the currency certificate (DAB)?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>
            This part of the process was introduced in February 2022. It requires
            international buyers to use euros, pounds sterling, or US dollars in
            transactions. The purchase price must be exchanged to Turkish lira via
            the Central Bank by the buyer or the seller. The Central Bank then
            provides a purchase certificate, which must be presented to the land
            registry offices during the title (tapu) exchanges. This certificate
            is called the DAB (Doviz alım belgesi).
          </p>
        </div>
      </details>

      <!-- 5 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: I bought a property in Turkey before the change in law, does it qualify?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>
            Property transactions can now be backdated to 2017, provided all relevant
            payment receipts can be provided from the developer’s bank account.
          </p>
        </div>
      </details>

      <!-- 6 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: Does the property have to be residential or does commercial qualify?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>
            Commercial property can also be used as part of the application process.
          </p>
        </div>
      </details>

      <!-- 7 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: How long does this process take?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>
            The granting of citizenship takes between four and six weeks once all
            documents have been submitted to the authorities.
          </p>
        </div>
      </details>

      <!-- 8 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: Can I buy multiple properties or is it just one?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>
            Multiple properties can be purchased provided the total exceeds $250,000.
          </p>
        </div>
      </details>

      <!-- 9 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: What conditions apply to the exchange rate between dollars and lira?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>
            This rule changed in June 2021. The official exchange rate now used is
            the Turkish Central Bank rate (TCMB) published at 4pm one day before
            the funds are credited to the seller’s account. The date of the tapu
            transfer is no longer used. The payment can only be made in USD, EUR,
            or GBP. This must then be exchanged for lira by the seller with the
            TCMB, which issues a currency certificate (the DAB). The amount
            exchanged <strong>must</strong> exceed $400,000.
          </p>
        </div>
      </details>

      <!-- 10 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: As a new citizen can I work in Turkey or open a business?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>
            Yes. As part of the application you are also required to take a residence
            permit, which gives you the immediate right of employment in Turkey.
          </p>
        </div>
      </details>

      <!-- 11 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: I bought property on instalments but did not register the title until after the new law, does it qualify?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>
            It can qualify subject to Ministry approval and assuming you can provide
            all the backdated receipts for every single payment. The exchange rate
            used will be one day before the funds are credited to the seller’s account.
          </p>
        </div>
      </details>

      <!-- 12 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: Is a criminal check required?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>
            The Ministry reserves the right to carry out a security check to ensure
            the applicant poses no threat to the Turkish public or national security.
          </p>
        </div>
      </details>

      <!-- 13 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: Will I or my children have to complete military service in Turkey?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>
            Children under 18 will need to complete military service in Turkey as
            Turkish nationals. However, as dual nationals they qualify for exemption
            and can pay a fee instead of serving.
          </p>
        </div>
      </details>

      <!-- 14 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: Can my parents obtain citizenship through my purchase?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>
            No, they must make their own individual purchase.
          </p>
        </div>
      </details>

      <!-- 15 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: My child is over 18, can they take citizenship through my purchase?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>
            No, they must make their own individual purchase.
          </p>
        </div>
      </details>

      <!-- 16 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: Do I need to learn the language or take any test/interview?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>No.</p>
        </div>
      </details>

      <!-- 17 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: I have a travel document but no passport, can I qualify?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>Yes.</p>
        </div>
      </details>

      <!-- 18 -->
      <details class="doc-item">
        <summary class="doc-summary">
          <span class="doc-title">
            Q: Does Turkey allow for dual nationality?
          </span>
          <span class="doc-icon" aria-hidden="true">
            <span class="doc-icon-line doc-icon-line--horizontal"></span>
            <span class="doc-icon-line doc-icon-line--vertical"></span>
          </span>
        </summary>
        <div class="doc-panel">
          <p>
            Yes, new citizens do not need to give up their nationality, provided
            their original country also allows dual nationality.
          </p>
        </div>
      </details>

    </div><!-- /.doc-accordion -->


  </div>
</section>




  <!-- =====================================================
       FINAL CTA (EMAIL / WHATSAPP)
       ====================================================== -->
  <section class="section cta" id="citizenship-enquiry">
    <div class="container">
      <h2>Ready to explore Turkish citizenship by investment?</h2>
      <p>
        Share your details and one of our consultants will contact you to discuss
        your plans and outline the best options for your family.
      </p>

      <div class="hero-actions">
        <a
          href="mailto:info@peraproperty.com?subject=Turkish%20Citizenship%20Enquiry"
          class="btn btn--solid btn--green"
        >
          Email our citizenship team
        </a>
        <a
          href="https://wa.me/905320639978?text=Hello%20Pera%20Property%2C%20I%27m%20interested%20in%20Turkish%20citizenship%20by%20investment."
          class="btn btn--ghost btn--green"
        >
          Chat on WhatsApp
        </a>
      </div>
    </div>
  </section>

</main>

<?php
get_footer();
