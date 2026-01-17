document.addEventListener('DOMContentLoaded', function () {

  /* -----------------------------------------
     1. OFF-CANVAS NAV (open/close with body class)
     ----------------------------------------- */
  var body        = document.body;
  var menuToggle  = document.querySelector('.header-menu-toggle');
  var navBackdrop = document.querySelector('.offcanvas-backdrop');
  var navCloseBtn = document.querySelector('.offcanvas-close');

  // -----------------------------------------
  // Tracking helpers (GA4 + Meta Pixel)
  // -----------------------------------------
  var analyticsLoaded = false;
  var marketingLoaded = false;

  function loadScript(src, id) {
    if (id && document.getElementById(id)) return;
    var s = document.createElement('script');
    s.async = true;
    s.src = src;
    if (id) s.id = id;
    document.head.appendChild(s);
  }

  function initGA() {
    if (analyticsLoaded) return;
    analyticsLoaded = true;

    if (!window.peraTrackingConfig || !window.peraTrackingConfig.gaMeasurementId) return;
    var gaId = window.peraTrackingConfig.gaMeasurementId;

    loadScript('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(gaId), 'pera-ga4');

    window.dataLayer = window.dataLayer || [];
    function gtag(){ window.dataLayer.push(arguments); }
    window.gtag = window.gtag || gtag;

    window.gtag('js', new Date());
    window.gtag('config', gaId);
  }

  function initMetaPixel() {
    if (marketingLoaded) return;
    marketingLoaded = true;

    if (!window.peraTrackingConfig || !window.peraTrackingConfig.metaPixelId) return;
    var pixelId = window.peraTrackingConfig.metaPixelId;

    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');

    window.fbq('init', pixelId);
    window.fbq('track', 'PageView');
  }


  function openNav() {
    body.classList.add('is-nav-open');
  }

  function closeNav() {
    body.classList.remove('is-nav-open');
  }

  // Toggle from burger button
  if (menuToggle) {
    menuToggle.addEventListener('click', function () {
      if (body.classList.contains('is-nav-open')) {
        closeNav();
      } else {
        openNav();
      }
    });
  }

  // Close when clicking backdrop
  if (navBackdrop) {
    navBackdrop.addEventListener('click', function () {
      closeNav();
    });
  }

  // Close from "X" button inside the panel
  if (navCloseBtn) {
    navCloseBtn.addEventListener('click', function () {
      closeNav();
    });
  }

  // Close with Esc key
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeNav();
    }
  });

  /* -----------------------------------------
     2. Accordion for:
        - Offcanvas menu
        - Footer menus
     ----------------------------------------- */

  var accordionSelectors = [
    '.offcanvas-menu .menu-item-has-children > a',
    '.footer-links .menu-item-has-children > a'
  ];

  document.querySelectorAll(accordionSelectors.join(',')).forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();

      var li = this.parentElement;
      if (!li) return;

      li.classList.toggle('is-open');
    });
  });

  /* -----------------------------------------
     3. COOKIE MONSTER – GOV.UK STYLE MINIMAL
     ----------------------------------------- */

  // Bump the version so old prefs don't hide the new banner
  var COOKIE_KEY = 'pera_cookie_pref_v2';
  var banner     = document.getElementById('cookie-banner');

  // If there is no cookie banner on this page, do nothing
  if (!banner) {
    return;
  }

  var btnAcceptAll = document.getElementById('cookie-accept-all');
  var btnReject    = document.getElementById('cookie-reject');
  var btnSave      = document.getElementById('cookie-save');   // may not exist (hidden in GOV layout)
  var btnManage    = document.getElementById('cookie-manage');
  var chkAnalytics = document.getElementById('cookie-analytics');
  var chkMarketing = document.getElementById('cookie-marketing');

  function getPrefs() {
    try {
      var raw = localStorage.getItem(COOKIE_KEY);
      if (!raw) return null;
      return JSON.parse(raw);
    } catch (e) {
      return null;
    }
  }

  function savePrefs(prefs) {
    try {
      localStorage.setItem(COOKIE_KEY, JSON.stringify(prefs));
    } catch (e) {
      // storage blocked, ignore
    }
  }

  function applyPrefs(prefs) {
    if (!prefs) return;

    if (chkAnalytics && typeof prefs.analytics === 'boolean') {
      chkAnalytics.checked = prefs.analytics;
    }
    if (chkMarketing && typeof prefs.marketing === 'boolean') {
      chkMarketing.checked = prefs.marketing;
    }

    // Load tracking only when:
    // 1) user has consented, AND
    // 2) the page has a tracking config (VOP only for now)
    if (prefs.analytics && window.peraTrackingConfig) {
      initGA();
    }
    if (prefs.marketing && window.peraTrackingConfig) {
      initMetaPixel();
    }
  }



  function showBanner() {
    banner.classList.add('cookie-banner--visible');
  }

  function hideBanner() {
    banner.classList.remove('cookie-banner--visible');
    banner.classList.remove('manage-open');
  }

  // Global hook for “Cookie settings” link in footer
  window.peraOpenCookieSettings = function () {
    var prefs = getPrefs();
    if (prefs) {
      applyPrefs(prefs);
    }
    banner.classList.add('manage-open');  // open directly in detailed mode
    showBanner();
  };

  // Initial load: show banner if no prefs
  var existing = getPrefs();
  if (!existing) {
    // No preference stored yet → show banner
    showBanner();
  } else {
    // Already have prefs → apply silently (no banner)
    applyPrefs(existing);
  }

  // Button: Accept all cookies
  if (btnAcceptAll) {
    btnAcceptAll.addEventListener('click', function () {
      var prefs = {
        necessary: true,
        analytics: true,
        marketing: true,
        timestamp: Date.now()
      };
      savePrefs(prefs);
      applyPrefs(prefs);
      hideBanner();
    });
  }

  // Button: Reject optional cookies
  if (btnReject) {
    btnReject.addEventListener('click', function () {
      var prefs = {
        necessary: true,
        analytics: false,
        marketing: false,
        timestamp: Date.now()
      };
      savePrefs(prefs);
      applyPrefs(prefs);
      hideBanner();
    });
  }

  // Button: Save choices (if you ever show it in detailed view)
  if (btnSave) {
    btnSave.addEventListener('click', function () {
      var prefs = {
        necessary: true,
        analytics: !!(chkAnalytics && chkAnalytics.checked),
        marketing: !!(chkMarketing && chkMarketing.checked),
        timestamp: Date.now()
      };
      savePrefs(prefs);
      applyPrefs(prefs);
      hideBanner();
    });
  }

  // Button: Manage cookie settings → toggle expanded GOV-style panel
  if (btnManage) {
    btnManage.addEventListener('click', function () {
      banner.classList.toggle('manage-open');
    });
  }
  
    const header = document.getElementById('site-header');
    if (header) {
      const SCROLL_TRIGGER = 40;
      let last = null;
      let ticking = false;
    
      function update() {
        const isScrolled = window.scrollY > SCROLL_TRIGGER;
        if (isScrolled !== last) {
          header.classList.toggle('is-scrolled', isScrolled);
          last = isScrolled;
        }
        ticking = false;
      }
    
      function onScroll() {
        if (!ticking) {
          ticking = true;
          requestAnimationFrame(update);
        }
      }
    
      update();
      window.addEventListener('scroll', onScroll, { passive: true });
    }

});