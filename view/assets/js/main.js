/* ==========================================================================
   Lumen — UI-скрипт темы TeaseAdultCMS
   --------------------------------------------------------------------------
   Три независимых поведения, без зависимостей:
     1) мега-меню категорий (hover + клавиатура + клик),
     2) мобильное меню (drawer),
     3) переключатель светлой/тёмной темы (localStorage + prefers-color-scheme).
   Разметка шапки: view/inc/header.php
   ========================================================================== */
(function () {
  'use strict';

  var DESKTOP_BREAKPOINT = 768;

  /* ==========================================================
     Categories — single source of truth
     The desktop mega-menu holds the canonical <ul data-cat-list>.
     Its items are mirrored into the mobile accordion so both
     menus always show the exact same set of categories.
     The <li> items are laid out into columns purely by CSS
     (multi-column), so adding/removing an <li> is enough —
     the columns re-balance automatically.
     ========================================================== */
  var catList = document.querySelector('[data-cat-list]');
  var mobileList = document.querySelector('[data-cat-mobile-list]');

  if (catList && mobileList && !mobileList.children.length) {
    Array.prototype.forEach.call(catList.children, function (item) {
      mobileList.appendChild(item.cloneNode(true));
    });
  }

  /* ---------- Categories mega-menu (desktop) ---------- */
  var trigger = document.querySelector('[data-cat-trigger]');
  var panel = document.querySelector('[data-cat-panel]');

  if (trigger && panel) {
    var wrap = trigger.closest('.cat-wrap') || trigger.parentElement;
    var openTimer = null;
    var closeTimer = null;
    var HOVER_DELAY = 100;
    var CLOSE_DELAY = 180;
    var openedByClick = false;
    var focusInside = false;
    var justClosedByClick = false;

    function show() {
      clearTimeout(closeTimer);
      panel.classList.add('is-open');
      trigger.setAttribute('aria-expanded', 'true');
    }

    function hide() {
      clearTimeout(openTimer);
      clearTimeout(closeTimer);
      panel.classList.remove('is-open');
      trigger.setAttribute('aria-expanded', 'false');
      openedByClick = false;
    }

    function openOnHover() {
      if (justClosedByClick) return;
      clearTimeout(closeTimer);
      clearTimeout(openTimer);
      openTimer = setTimeout(function () {
        if (!focusInside) openedByClick = false;
        show();
      }, HOVER_DELAY);
    }

    function closeOnLeave() {
      justClosedByClick = false;
      clearTimeout(openTimer);
      closeTimer = setTimeout(function () {
        if (!focusInside) hide();
      }, CLOSE_DELAY);
    }

    if (wrap) {
      wrap.addEventListener('mouseenter', openOnHover);
      wrap.addEventListener('mouseleave', closeOnLeave);
      /* Re-open when the pointer moves over the trigger while the panel
         is closed (mouseenter alone does not fire if the pointer never
         leaves the element). */
      wrap.addEventListener('mousemove', function () {
        if (!panel.classList.contains('is-open') && !focusInside) openOnHover();
      });
    }

    /* Keyboard focus on the trigger opens the panel. */
    trigger.addEventListener('focus', function () {
      clearTimeout(closeTimer);
      show();
    });

    panel.addEventListener('focusin', function () {
      focusInside = true;
      clearTimeout(closeTimer);
      show();
    });

    panel.addEventListener('focusout', function (e) {
      if (!panel.contains(e.relatedTarget) && e.relatedTarget !== trigger) {
        focusInside = false;
        hide();
      }
    });

    /* Click toggles. If the panel was only opened by hover, the
       first click keeps it open instead of closing it straight away. */
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      if (panel.classList.contains('is-open') && openedByClick) {
        hide();
        /* Do not immediately re-open on the next pointer move. */
        justClosedByClick = true;
      } else {
        clearTimeout(openTimer);
        clearTimeout(closeTimer);
        justClosedByClick = false;
        openedByClick = true;
        show();
      }
    });

    /* Escape closes and returns focus to the trigger. */
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && panel.classList.contains('is-open')) {
        hide();
        trigger.focus();
      }
    });

    /* Click outside closes. */
    document.addEventListener('click', function (e) {
      if (!wrap.contains(e.target) && !panel.contains(e.target)) {
        hide();
      }
    });

    /* Leaving the desktop breakpoint must not leave the panel open. */
    window.addEventListener('resize', function () {
      if (window.innerWidth < DESKTOP_BREAKPOINT) hide();
    });
  }

  /* ---------- Mobile nav toggle ---------- */
  var mobileToggle = document.querySelector('[data-mobile-toggle]');
  var mobileNav = document.querySelector('[data-mobile-nav]');

  if (mobileToggle && mobileNav) {
    function setMobileNav(open) {
      mobileNav.classList.toggle('is-open', open);
      mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      mobileToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    }

    mobileToggle.addEventListener('click', function () {
      setMobileNav(!mobileNav.classList.contains('is-open'));
    });

    /* Following a link closes the drawer. */
    mobileNav.addEventListener('click', function (e) {
      if (e.target.closest('a')) setMobileNav(false);
    });

    /* Escape closes. */
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && mobileNav.classList.contains('is-open')) {
        setMobileNav(false);
        mobileToggle.focus();
      }
    });

    /* Click outside closes. */
    document.addEventListener('click', function (e) {
      if (!mobileNav.contains(e.target) && !mobileToggle.contains(e.target)) {
        setMobileNav(false);
      }
    });

    /* Never keep the drawer open across the desktop breakpoint. */
    window.addEventListener('resize', function () {
      if (window.innerWidth >= DESKTOP_BREAKPOINT) setMobileNav(false);
    });
  }

  /* ---------- Theme toggle ---------- */
  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    try { localStorage.setItem('theme', theme); } catch (e) {}
    var btn = document.querySelector('[data-theme-toggle]');
    if (btn) {
      var isDark = theme === 'dark';
      btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
      btn.setAttribute('aria-label', isDark ? 'Switch to light theme' : 'Switch to dark theme');
      var sun = btn.querySelector('[data-icon-sun]');
      var moon = btn.querySelector('[data-icon-moon]');
      /* Toggle via the hidden attribute so no inline styles are introduced. */
      if (sun) sun.hidden = isDark;
      if (moon) moon.hidden = !isDark;
    }
  }

  var themeBtn = document.querySelector('[data-theme-toggle]');
  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      var current = document.documentElement.getAttribute('data-theme') || 'light';
      applyTheme(current === 'dark' ? 'light' : 'dark');
    });
  }

  /* Sync button state on load (inline script already set data-theme) */
  applyTheme(document.documentElement.getAttribute('data-theme') || 'light');
})();
