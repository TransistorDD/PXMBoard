/**
 * HTMX Skin - Skin-specific JavaScript
 *
 * Handles:
 * - Layout mode toggle (Desktop/Stacked) with localStorage
 * - Theme switching (Light/Dark/Auto) with localStorage
 * - View mode toggle (Mobile/Desktop/Auto) with localStorage
 * - HTMX event listeners (afterSwap, indicators)
 * - Badge updates
 * - Notification handling
 * - Editor initialization helpers
 * - Mobile page switching, swipe gesture, card click
 */

(function () {
  'use strict';

  // ====================================================================
  // CSRF PROTECTION
  // ====================================================================

  function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
  }

  document.addEventListener('htmx:configRequest', function (e) {
    e.detail.headers['X-CSRF-Token'] = getCsrfToken();
  });

  // ====================================================================
  // THEME MANAGEMENT
  // ====================================================================

  /**
   * Apply theme to the document
   * @param {string} theme - 'light', 'dark', or 'auto'
   */
  function applyTheme(theme) {
    var resolved = theme;
    if (theme === 'auto') {
      resolved = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    document.documentElement.setAttribute('data-theme', resolved);
  }

  /**
   * Set and persist theme choice
   * @param {string} theme - 'light', 'dark', or 'auto'
   */
  window.setTheme = function (theme) {
    if (theme === 'auto') {
      localStorage.removeItem('pxmboard-skin-theme');
    } else {
      localStorage.setItem('pxmboard-skin-theme', theme);
    }
    applyTheme(theme);
    updateThemeCheckmarks(theme);
  };

  function updateThemeCheckmarks(theme) {
    var options = document.querySelectorAll('[data-theme-option]');
    options.forEach(function (btn) {
      var checkmark = btn.querySelector('[data-checkmark]');
      if (checkmark) {
        checkmark.textContent = (btn.getAttribute('data-theme-option') === theme) ? '\u2713 ' : '';
      }
    });
  }

  // Initialize theme on load
  var savedTheme = localStorage.getItem('pxmboard-skin-theme') || 'auto';
  applyTheme(savedTheme);

  // Listen for system color scheme changes when on 'auto'
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
    if (localStorage.getItem('pxmboard-skin-theme') === 'auto') {
      applyTheme('auto');
    }
  });

  // ====================================================================
  // LAYOUT MANAGEMENT
  // ====================================================================

  /**
   * Set and persist layout mode.
   * On 'auto': removes inline grid styles (CSS defaults apply), saved splits are preserved.
   * @param {string} layout - 'sidebyside', 'stacked', or 'auto'
   */
  window.setLayout = function (layout) {
    if (layout === 'auto') {
      localStorage.removeItem('pxmboard-skin-layout');
    } else {
      localStorage.setItem('pxmboard-skin-layout', layout);
    }
    applyLayout(layout);
    if (layout === 'auto') {
      // Reset inline styles only — saved split positions remain in localStorage
      var el = document.getElementById('board-layout');
      if (el) { el.style.gridTemplateColumns = ''; el.style.gridTemplateRows = ''; }
    } else {
      applyGridSplits();
    }
    updateLayoutCheckmarks(layout);
  };

  function applyLayout(layout) {
    var el = document.getElementById('board-layout');
    if (!el) return;

    if (layout === 'auto' || !layout) {
      el.removeAttribute('data-layout');
    } else {
      el.setAttribute('data-layout', layout);
    }
  }

  function updateLayoutCheckmarks(layout) {
    var options = document.querySelectorAll('[data-layout-option]');
    options.forEach(function (btn) {
      var checkmark = btn.querySelector('[data-checkmark]');
      if (checkmark) {
        checkmark.textContent = (btn.getAttribute('data-layout-option') === layout) ? '\u2713 ' : '';
      }
    });
  }

  // ====================================================================
  // DIVIDER / SPLIT MANAGEMENT
  // ====================================================================

  // ====================================================================
  // VIEW MANAGEMENT (Mobile / Desktop / Auto)
  // ====================================================================

  var VIEW_KEY = 'pxmboard-skin-view';

  /**
   * Determine whether mobile view is currently active.
   * Forced via data-view attribute or auto-detected by viewport width.
   * @returns {boolean}
   */
  function isMobileView() {
    var v = document.documentElement.getAttribute('data-view');
    if (v === 'mobile') return true;
    if (v === 'desktop') return false;
    return window.innerWidth <= 768;
  }

  /**
   * Sync the 'is-mobile' class on <html> with the current mobile view state.
   * Called on init, view change, and resize so CSS can use a single selector.
   */
  function syncMobileClass() {
    document.documentElement.classList.toggle('is-mobile', isMobileView());
  }

  /**
   * Apply view mode to the document.
   * @param {string} view - 'mobile', 'desktop', or 'auto'
   */
  function applyView(view) {
    if (view === 'auto' || !view) {
      document.documentElement.removeAttribute('data-view');
    } else {
      document.documentElement.setAttribute('data-view', view);
    }
    // When switching away from mobile, reset the page slide and mobile flex styles
    if (!isMobileView()) {
      var el = document.getElementById('board-layout');
      if (el) el.classList.remove('mobile-show-detail');
      var mc = document.getElementById('message-container');
      var tc = document.getElementById('thread-container');
      if (mc) mc.style.flex = '';
      if (tc) { tc.style.flex = ''; tc.style.maxHeight = ''; }
      applyGridSplits();
    } else {
      // Clear inline grid styles so mobile CSS takes full control
      var bl = document.getElementById('board-layout');
      if (bl) { bl.style.gridTemplateColumns = ''; bl.style.gridTemplateRows = ''; }
      applyMobileSplit();
    }
    syncMobileClass();
  }

  /**
   * Set and persist view mode choice.
   * @param {string} view - 'mobile', 'desktop', or 'auto'
   */
  window.setView = function (view) {
    if (view === 'auto') {
      localStorage.removeItem(VIEW_KEY);
    } else {
      localStorage.setItem(VIEW_KEY, view);
    }
    applyView(view);
    updateViewCheckmarks(view);
  };

  function updateViewCheckmarks(view) {
    document.querySelectorAll('[data-view-option]').forEach(function (btn) {
      var checkmark = btn.querySelector('[data-checkmark]');
      if (checkmark) {
        checkmark.textContent = (btn.getAttribute('data-view-option') === view) ? '\u2713 ' : '';
      }
    });
  }

  // Initialize view on load (before DOMContentLoaded for early attribute setting)
  var savedView = localStorage.getItem(VIEW_KEY) || 'auto';
  applyView(savedView);

  var SPLIT_KEY_SIDEBYSIDE = 'pxmboard-split-sidebyside';
  var SPLIT_KEY_STACKED = 'pxmboard-split-stacked';
  var SPLIT_KEY_MOBILE = 'pxmboard-split-mobile';
  var SPLIT_MIN_PCT = 10;

  function loadSplitSideBySide() {
    try {
      var v = localStorage.getItem(SPLIT_KEY_SIDEBYSIDE);
      if (v) {
        var o = JSON.parse(v);
        if (o && o.left >= SPLIT_MIN_PCT && o.left <= 100 - SPLIT_MIN_PCT &&
          o.top >= SPLIT_MIN_PCT && o.top <= 100 - SPLIT_MIN_PCT) {
          return o;
        }
      }
    } catch (e) { }
    return null;
  }

  function loadSplitStacked() {
    try {
      var v = localStorage.getItem(SPLIT_KEY_STACKED);
      if (v) {
        var o = JSON.parse(v);
        if (o && !Array.isArray(o) &&
          o.top >= SPLIT_MIN_PCT && o.mid >= SPLIT_MIN_PCT &&
          o.top + o.mid <= 100 - SPLIT_MIN_PCT) {
          return { h1: o.top, h2: o.mid };
        }
      }
    } catch (e) { }
    return null;
  }

  function loadSplitMobile() {
    try {
      var v = localStorage.getItem(SPLIT_KEY_MOBILE);
      if (v) {
        var pct = parseInt(v, 10);
        if (pct >= SPLIT_MIN_PCT && pct <= 100 - SPLIT_MIN_PCT) {
          return pct;
        }
      }
    } catch (e) { }
    return null;
  }

  /**
   * Apply saved mobile split to #detail-page containers.
   * Sets flex-basis percentages on message-container and thread-container.
   */
  function applyMobileSplit() {
    if (!isMobileView()) return;
    var pct = loadSplitMobile();
    var mc = document.getElementById('message-container');
    var tc = document.getElementById('thread-container');
    if (!mc || !tc) return;
    if (pct) {
      mc.style.flex = '0 0 ' + pct + '%';
      tc.style.flex = '0 0 ' + (100 - pct) + '%';
      tc.style.maxHeight = 'none';
    } else {
      mc.style.flex = '';
      tc.style.flex = '';
      tc.style.maxHeight = '';
    }
  }

  function clamp(val, min, max) {
    return Math.max(min, Math.min(max, val));
  }

  /**
   * Apply saved (or default) grid split sizes to #board-layout.
   * Only sets inline styles when saved values exist; otherwise CSS defaults apply.
   */
  function applyGridSplits() {
    var el = document.getElementById('board-layout');
    if (!el) return;

    // In mobile view, CSS controls the grid; clear any stale inline styles and bail
    if (isMobileView()) {
      el.style.gridTemplateColumns = '';
      el.style.gridTemplateRows = '';
      return;
    }

    var layout = localStorage.getItem('pxmboard-skin-layout') || 'auto';
    var isStacked = layout === 'stacked' ||
      (layout !== 'sidebyside' && window.innerWidth < 1025);

    // Always clear inline grid styles first to avoid stale values
    // persisting across layout/breakpoint changes
    el.style.gridTemplateColumns = '';
    el.style.gridTemplateRows = '';

    if (!isStacked) {
      var sbs = loadSplitSideBySide();
      if (sbs) {
        el.style.gridTemplateColumns = sbs.left + 'fr 6px ' + (100 - sbs.left) + 'fr';
        el.style.gridTemplateRows = sbs.top + 'fr 6px ' + (100 - sbs.top) + 'fr';
      }
    } else {
      var st = loadSplitStacked();
      if (st) {
        el.style.gridTemplateRows = st.h1 + 'fr 6px ' + st.h2 + 'fr 6px ' + (100 - st.h1 - st.h2) + 'fr';
      }
    }
  }

  /**
   * Delete all saved split sizes and reset inline grid styles.
   */
  function clearSplits() {
    localStorage.removeItem(SPLIT_KEY_SIDEBYSIDE);
    localStorage.removeItem(SPLIT_KEY_STACKED);
    localStorage.removeItem(SPLIT_KEY_MOBILE);
    var el = document.getElementById('board-layout');
    if (el) {
      el.style.gridTemplateColumns = '';
      el.style.gridTemplateRows = '';
    }
    // Reset mobile flex styles
    var mc = document.getElementById('message-container');
    var tc = document.getElementById('thread-container');
    if (mc) mc.style.flex = '';
    if (tc) { tc.style.flex = ''; tc.style.maxHeight = ''; }
  }

  /**
   * Update split checkmarks in the gear dropdown.
   * 'custom' is selected when any split value is saved; 'auto' otherwise.
   */
  function updateSplitCheckmarks() {
    var hasCustom = localStorage.getItem(SPLIT_KEY_SIDEBYSIDE) !== null ||
      localStorage.getItem(SPLIT_KEY_STACKED) !== null ||
      localStorage.getItem(SPLIT_KEY_MOBILE) !== null;
    var active = hasCustom ? 'custom' : 'auto';
    document.querySelectorAll('[data-split-option]').forEach(function (btn) {
      var checkmark = btn.querySelector('[data-checkmark]');
      if (checkmark) {
        checkmark.textContent = (btn.getAttribute('data-split-option') === active) ? '\u2713 ' : '';
      }
    });
  }

  /**
   * Reset split layout to defaults (clears saved values).
   * @param {string} mode - currently only 'auto' is supported
   */
  window.setSplitLayout = function (mode) {
    if (mode === 'auto') {
      clearSplits();
      updateSplitCheckmarks();
    }
  };

  // ====================================================================
  // DIVIDER DRAG
  // ====================================================================

  var _drag = null;

  function initDividerDrag() {
    document.querySelectorAll('.htmx-divider').forEach(function (div) {
      div.addEventListener('mousedown', function (e) { startDrag(e, div); });
      div.addEventListener('touchstart', function (e) { startDrag(e, div); }, { passive: false });
    });
  }

  function startDrag(e, divider) {
    var el = document.getElementById('board-layout');
    if (!el) return;
    e.preventDefault();

    var type = divider.getAttribute('data-divider');

    // Mobile mode: only h2 divider (between message and thread) is active
    if (isMobileView()) {
      if (type !== 'h2') return;
      var detailPage = document.getElementById('detail-page');
      if (!detailPage) return;
      var rect = detailPage.getBoundingClientRect();
      var mobilePct = loadSplitMobile() || 60;
      _drag = {
        type: 'h2',
        isMobile: true,
        rect: rect,
        mobilePct: mobilePct
      };
      divider.classList.add('htmx-dragging');
      document.body.style.cursor = 'row-resize';
      document.body.style.userSelect = 'none';
      document.addEventListener('mousemove', onDrag);
      document.addEventListener('mouseup', stopDrag);
      document.addEventListener('touchmove', onDrag, { passive: false });
      document.addEventListener('touchend', stopDrag);
      return;
    }

    var layout = localStorage.getItem('pxmboard-skin-layout') || 'auto';
    var isStacked = layout === 'stacked' ||
      (layout !== 'sidebyside' && window.innerWidth < 1025);
    var rect = el.getBoundingClientRect();

    // Pre-load current split state for stacked secondary divider
    var st = loadSplitStacked() || { h1: 33, h2: 34 };
    _drag = {
      type: type,
      isStacked: isStacked,
      rect: rect,
      h1: st.h1,
      h2: st.h2
    };

    divider.classList.add('htmx-dragging');
    document.body.style.cursor = (type === 'v') ? 'col-resize' : 'row-resize';
    document.body.style.userSelect = 'none';

    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', stopDrag);
    document.addEventListener('touchmove', onDrag, { passive: false });
    document.addEventListener('touchend', stopDrag);
  }

  function onDrag(e) {
    if (!_drag) return;
    e.preventDefault();

    var clientX = e.touches ? e.touches[0].clientX : e.clientX;
    var clientY = e.touches ? e.touches[0].clientY : e.clientY;
    var rect = _drag.rect;
    var pct;

    // Mobile: adjust message/thread split within #detail-page
    if (_drag.isMobile) {
      pct = clamp(Math.round((clientY - rect.top) / rect.height * 100), 15, 85);
      var mc = document.getElementById('message-container');
      var tc = document.getElementById('thread-container');
      if (mc && tc) {
        mc.style.flex = '0 0 ' + pct + '%';
        tc.style.flex = '0 0 ' + (100 - pct) + '%';
        tc.style.maxHeight = 'none';
      }
      _drag.mobilePct = pct;
      return;
    }

    var el = document.getElementById('board-layout');

    if (_drag.type === 'v') {
      // Vertical: adjust left‑column width in side-by-side
      pct = clamp(Math.round((clientX - rect.left) / rect.width * 100), 20, 80);
      el.style.gridTemplateColumns = pct + 'fr 6px ' + (100 - pct) + 'fr';
      _drag.saveLeft = pct;

    } else if (_drag.type === 'h' && !_drag.isStacked) {
      // Horizontal in side-by-side: adjust threadlist row height
      pct = clamp(Math.round((clientY - rect.top) / rect.height * 100), 20, 80);
      el.style.gridTemplateRows = pct + 'fr 6px ' + (100 - pct) + 'fr';
      _drag.saveTop = pct;

    } else if (_drag.type === 'h' && _drag.isStacked) {
      // First horizontal in stacked: resize threadlist (h1)
      pct = clamp(Math.round((clientY - rect.top) / rect.height * 100), 10, 80 - _drag.h2);
      _drag.h1 = pct;
      el.style.gridTemplateRows = _drag.h1 + 'fr 6px ' + _drag.h2 + 'fr 6px ' + (100 - _drag.h1 - _drag.h2) + 'fr';

    } else if (_drag.type === 'h2') {
      // Second horizontal in stacked: resize thread (h2)
      var dividerFromTop = clamp(Math.round((clientY - rect.top) / rect.height * 100), _drag.h1 + 10, 90);
      _drag.h2 = clamp(dividerFromTop - _drag.h1, 10, 80 - _drag.h1);
      el.style.gridTemplateRows = _drag.h1 + 'fr 6px ' + _drag.h2 + 'fr 6px ' + (100 - _drag.h1 - _drag.h2) + 'fr';
    }
  }

  function stopDrag() {
    if (!_drag) return;

    // Persist the final split values
    if (_drag.isMobile) {
      localStorage.setItem(SPLIT_KEY_MOBILE, String(_drag.mobilePct));
    } else if (_drag.saveLeft !== undefined || _drag.saveTop !== undefined) {
      var sbs = loadSplitSideBySide() || { left: 60, top: 50 };
      if (_drag.saveLeft !== undefined) sbs.left = _drag.saveLeft;
      if (_drag.saveTop !== undefined) sbs.top = _drag.saveTop;
      localStorage.setItem(SPLIT_KEY_SIDEBYSIDE, JSON.stringify(sbs));
    }
    if (_drag.isStacked && (_drag.type === 'h' || _drag.type === 'h2')) {
      localStorage.setItem(SPLIT_KEY_STACKED, JSON.stringify({ top: _drag.h1, mid: _drag.h2 }));
    }

    _drag = null;
    document.querySelectorAll('.htmx-divider.htmx-dragging').forEach(function (d) {
      d.classList.remove('htmx-dragging');
    });
    document.body.style.cursor = '';
    document.body.style.userSelect = '';

    updateSplitCheckmarks();

    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
    document.removeEventListener('touchmove', onDrag);
    document.removeEventListener('touchend', stopDrag);
  }

  // ====================================================================
  // BADGE MANAGEMENT
  // ====================================================================

  /**
   * Update a notification badge count
   * @param {string} type - 'pm' or 'notification'
   * @param {number} count - new count
   */
  window.updateBadge = function (type, count) {
    var badge = document.getElementById(type + '-badge');
    if (count > 0) {
      if (badge) {
        badge.textContent = count;
        badge.style.display = 'flex';
      }
    } else {
      if (badge) {
        badge.style.display = 'none';
      }
    }
  };

  // ====================================================================
  // NOTIFICATION HANDLING
  // ====================================================================

  /**
   * Handle notification click: mark as read, then either swap modal content
   * (for private messages) or navigate via full page load (for board links).
   */
  window.handleNotificationClick = function (evt, nid) {
    evt.preventDefault();
    var url = evt.currentTarget.href;
    if (url.indexOf('mode=privatemessage') !== -1) {
      // PM: wait for mark-read, then swap modal content in place
      fetch('pxmboard.php?mode=ajaxnotificationmarkread&nid=' + nid, { credentials: 'same-origin', headers: { 'X-CSRF-Token': getCsrfToken() } })
        .finally(function () {
          document.getElementById('htmxModalTitle').textContent = 'Private Nachrichten';
          htmx.ajax('GET', url, { target: '#htmxModalBody', swap: 'innerHTML' });
        });
    } else {
      // Board link: navigate immediately, mark-read fire-and-forget
      fetch('pxmboard.php?mode=ajaxnotificationmarkread&nid=' + nid, { credentials: 'same-origin', keepalive: true, headers: { 'X-CSRF-Token': getCsrfToken() } });
      window.location.href = url;
    }
  };

  /**
   * Toggle email notification on reply for a message
   */
  window.toggleNotifyOnReply = function (btn, msgId, brdId) {
    var svgOn = '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"/></svg>';
    var svgOff = '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"/><line x1="2" y1="2" x2="22" y2="22"/></svg>';
    fetch('pxmboard.php?mode=ajaxMessagenotifyonreply&brdid=' + brdId, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': getCsrfToken() },
      body: 'msgid=' + msgId
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          btn.innerHTML = data.active ? svgOn : svgOff;
          btn.title = data.active ? 'Mailbenachrichtigung deaktivieren' : 'Mailbenachrichtigung aktivieren';
          btn.dataset.active = data.active ? '1' : '0';
        }
      })
      .catch(function (e) {
        console.error('toggleNotifyOnReply error:', e);
      });
  };

  /**
   * Toggle message watch notification
   */
  window.toggleMessageNotification = function (msgId, brdId, btn) {
    fetch('pxmboard.php?mode=ajaxMessagenotificationtoggle&msgid=' + msgId + '&brdid=' + brdId, {
      credentials: 'same-origin',
      headers: { 'X-CSRF-Token': getCsrfToken() }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          var svgOn = '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
          var svgOff = '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>';
          btn.innerHTML = data.active ? svgOn : svgOff;
          btn.title = data.active ? 'Beobachten deaktivieren' : 'Beobachten';
        }
      })
      .catch(function (e) {
        console.error('toggleMessageNotification error:', e);
      });
  };

  // ====================================================================
  // MODAL DIALOG
  // ====================================================================

  // ====================================================================
  // ADMIN ACTIONS
  // ====================================================================

  /**
   * Internal helper: execute an admin AJAX call, then refresh panels.
   * @param {string} url          - fetch URL
   * @param {number} brdid        - board id for threadlist reload
   * @param {boolean} clearPanels - clear thread+message panels after success
   */
  function _adminAjax(url, brdid, clearPanels, reloadThreadId) {
    fetch(url, { credentials: 'same-origin', headers: { 'X-CSRF-Token': getCsrfToken() } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          htmx.ajax('GET',
            'pxmboard.php?mode=threadlist&brdid=' + brdid,
            { target: '#threadlist-container', swap: 'innerHTML' }
          );
          if (clearPanels) {
            var tc = document.getElementById('thread-container');
            if (tc) tc.innerHTML = '';
            var mc = document.getElementById('message-container');
            if (mc) mc.innerHTML = '';
          } else if (reloadThreadId) {
            htmx.ajax('GET',
              'pxmboard.php?mode=thread&brdid=' + brdid + '&thrdid=' + reloadThreadId,
              { target: '#thread-container', swap: 'innerHTML' }
            );
          }
        } else {
          alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
        }
      })
      .catch(function (e) {
        alert('Verbindungsfehler: ' + e.message);
      });
  }

  /**
   * Show the styled confirm dialog and call onConfirm if accepted.
   * Falls back to native confirm() when the dialog element is unavailable.
   * @param {string}   question  - Question text to display
   * @param {Function} onConfirm - Called when the user confirms
   */
  function _pxmConfirm(question, onConfirm) {
    var dialog = document.getElementById('htmxConfirmDialog');
    var questionEl = document.getElementById('htmxConfirmQuestion');
    if (!dialog || !questionEl) {
      if (window.confirm(question)) onConfirm();
      return;
    }
    questionEl.textContent = question;
    dialog.showModal();

    var okBtn = document.getElementById('htmxConfirmOk');
    var cancelBtn = document.getElementById('htmxConfirmCancel');

    function onOk() {
      dialog.close();
      okBtn.removeEventListener('click', onOk);
      cancelBtn.removeEventListener('click', onCancel);
      onConfirm();
    }
    function onCancel() {
      dialog.close();
      okBtn.removeEventListener('click', onOk);
      cancelBtn.removeEventListener('click', onCancel);
    }

    okBtn.addEventListener('click', onOk);
    cancelBtn.addEventListener('click', onCancel);
  }

  /**
   * Execute admin action on a thread/message.
   * Called from onchange on admin dropdowns in thread.tpl and message.tpl.
   * @param {string}      action    - action key
   * @param {number}      brdid     - board id
   * @param {number}      id        - message/thread id
   * @param {number}      threadid  - thread id (optional, only passed from message.tpl)
   * @param {HTMLElement} selectEl  - the <select> element that triggered the change
   */
  window.adminaction = function (action, brdid, id, threadid, selectEl) {
    if (!action) return;

    // Reset dropdown to placeholder after selection
    if (selectEl) {
      selectEl.selectedIndex = 0;
    }

    switch (action) {
      case 'threadstatus':
        _adminAjax(
          'pxmboard.php?mode=ajaxThreadchangestatus&brdid=' + brdid + '&id=' + id,
          brdid, false, id
        );
        break;

      case 'fixthread':
        _adminAjax(
          'pxmboard.php?mode=ajaxThreadchangefixed&brdid=' + brdid + '&id=' + id,
          brdid, false, id
        );
        break;

      case 'movethread':
        fetch('pxmboard.php?mode=ajaxThreadmove&brdid=' + brdid + '&id=' + id, { credentials: 'same-origin', headers: { 'X-CSRF-Token': getCsrfToken() } })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!data.boards) {
              _pxmConfirm('Fehler: ' + (data.error || 'Unbekannter Fehler'), function () { });
              return;
            }
            var dlg = document.createElement('dialog');
            dlg.className = 'rounded-lg shadow-2xl p-0 max-w-sm backdrop:bg-black/50 fixed inset-0 m-auto h-fit bg-surface-primary text-content-primary';
            var options = data.boards.map(function (b) {
              return '<option value="' + b.id + '">' + b.name + '</option>';
            }).join('');
            dlg.innerHTML = '<div class="p-4">'
              + '<p class="mb-2 font-medium text-content-primary">Thread verschieben nach:</p>'
              + '<select id="dlg-destboard" class="w-full mb-4 text-sm rounded px-2 py-1 bg-surface-secondary text-content-primary border border-border-default">' + options + '</select>'
              + '<div class="flex justify-end gap-2">'
              + '<button type="button" class="htmx-btn-primary text-sm px-4 py-1" id="dlg-confirm">Verschieben</button>'
              + '<button type="button" class="htmx-btn text-sm px-4 py-1" id="dlg-cancel">Abbrechen</button>'
              + '</div>'
              + '</div>';
            document.body.appendChild(dlg);
            dlg.showModal();
            dlg.querySelector('#dlg-cancel').addEventListener('click', function () {
              dlg.close();
              dlg.remove();
            });
            dlg.querySelector('#dlg-confirm').addEventListener('click', function () {
              var destId = dlg.querySelector('#dlg-destboard').value;
              dlg.close();
              dlg.remove();
              _adminAjax(
                'pxmboard.php?mode=ajaxMessagetreemove&brdid=' + brdid + '&id=' + id + '&destid=' + destId,
                brdid, true
              );
            });
          })
          .catch(function (e) {
            _pxmConfirm('Verbindungsfehler: ' + e.message, function () { });
          });
        break;

      case 'deletethread':
        _pxmConfirm('Thread wirklich löschen?', function () {
          _adminAjax(
            'pxmboard.php?mode=ajaxMessagetreedelete&brdid=' + brdid + '&msgid=' + id + '&thrdid=' + (threadid || id),
            brdid, true
          );
        });
        break;

      case 'deletemessage':
        _pxmConfirm('Nachricht wirklich löschen?', function () {
          _adminAjax(
            'pxmboard.php?mode=ajaxMessagedelete&brdid=' + brdid + '&msgid=' + id,
            brdid, true
          );
        });
        break;

      case 'deletesubthread':
        _pxmConfirm('Subthread wirklich löschen?', function () {
          _adminAjax(
            'pxmboard.php?mode=ajaxMessagetreedelete&brdid=' + brdid + '&msgid=' + id + '&thrdid=' + (threadid || id),
            brdid, true
          );
        });
        break;

      case 'extractsubthread':
        _pxmConfirm('Subthread wirklich ausgliedern?', function () {
          _adminAjax(
            'pxmboard.php?mode=ajaxMessagetreeextract&brdid=' + brdid + '&msgid=' + id,
            brdid, true
          );
        });
        break;

      case 'selectmove':
        if (typeof MessageMove !== 'undefined') {
          var subject = MessageMove._extractSubject();
          var author = MessageMove._extractAuthor();
          var date = MessageMove._extractDate();
          MessageMove.selectMessageForMove(id, subject, author, date, brdid);
        }
        break;

      case 'inserthere':
        if (typeof MessageMove !== 'undefined') {
          var tSubject = MessageMove._extractSubject();
          var tAuthor = MessageMove._extractAuthor();
          var tDate = MessageMove._extractDate();
          MessageMove.performMove(id, tSubject, tAuthor, tDate);
        }
        break;
    }
  };

  /**
   * Board status popup (admin only)
   */
  var _statusPopup = null;
  var _statusPopupBoardId = null;

  window.openStatusPopup = function (btn, boardId) {
    if (_statusPopupBoardId === boardId && _statusPopup && _statusPopup.style.display !== 'none') {
      closeStatusPopup();
      return;
    }
    closeStatusPopup();
    _statusPopupBoardId = boardId;
    if (!_statusPopup) {
      _statusPopup = document.getElementById('boardStatusPopup');
    }
    _statusPopup.querySelectorAll('button[data-boardid]').forEach(function (b) {
      b.setAttribute('data-boardid', boardId);
    });
    var rect = btn.getBoundingClientRect();
    _statusPopup.style.top = (rect.bottom + 4) + 'px';
    _statusPopup.style.left = (rect.left - 40) + 'px';
    _statusPopup.style.display = 'flex';
  };

  window.closeStatusPopup = function () {
    if (_statusPopup) { _statusPopup.style.display = 'none'; }
    _statusPopupBoardId = null;
  };

  document.addEventListener('click', function (e) {
    if (_statusPopup && _statusPopup.style.display !== 'none' &&
      !_statusPopup.contains(e.target) && !e.target.closest('.status-icon-btn')) {
      closeStatusPopup();
    }
  });

  window.changeBoardStatus = function (boardId, newStatus) {
    closeStatusPopup();
    fetch('pxmboard.php?mode=ajaxboardchangestatus', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': getCsrfToken() },
      body: 'boardid=' + boardId + '&status=' + newStatus
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          location.reload();
        } else {
          alert('Fehler beim Aendern des Status: ' + (data.error || 'Unbekannter Fehler'));
        }
      })
      .catch(function (e) {
        console.error('Error:', e);
        alert('Fehler beim Aendern des Status');
      });
  };

  // ====================================================================
  // THREAD + MESSAGE DUAL LOADING
  // ====================================================================

  // Tracks which message is currently shown in #message-container
  var currentMsgId = 0;
  // Direct reference to the currently highlighted row (avoids O(n) iteration)
  var _highlightedRow = null;

  /**
   * Select a message in the thread tree (set highlight).
   * Exposed globally so onclick handlers in templates can call it.
   * @param {number} msgId - the message id to highlight
   */
  window.selectMessage = function (msgId) {
    currentMsgId = msgId;
    updateThreadHighlight();
  };

  /**
   * Update thread tree highlighting based on currentMsgId.
   * O(1): removes class from cached previous row, adds to new row.
   */
  function updateThreadHighlight() {
    // Remove old highlight (O(1) via cached reference)
    if (_highlightedRow) {
      _highlightedRow.classList.remove('htmx-msg-selected');
      _highlightedRow = null;
    }

    if (currentMsgId > 0) {
      var container = document.getElementById('thread-container');
      if (!container) return;
      var row = container.querySelector('.htmx-thread-msg-row[data-msgid="' + currentMsgId + '"]');
      if (row) {
        row.classList.add('htmx-msg-selected');
        _highlightedRow = row;
        row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    }
  }

  /**
   * Load a message into #message-container AND the thread tree into
   * #thread-container simultaneously. Highlights the message in the tree.
   * Used by threadlist subject and last-reply links.
   *
   * @param {number} brdid  - board id
   * @param {number} msgid  - message id to display and highlight
   * @param {number} thrdid - thread id for the tree
   */
  window.loadThreadAndMessage = function (brdid, msgid, thrdid) {
    currentMsgId = msgid;
    htmx.ajax('GET',
      'pxmboard.php?mode=message&brdid=' + brdid + '&msgid=' + msgid,
      { target: '#message-container', swap: 'innerHTML' }
    );
    htmx.ajax('GET',
      'pxmboard.php?mode=thread&brdid=' + brdid + '&thrdid=' + thrdid,
      { target: '#thread-container', swap: 'innerHTML' }
    );
    if (isMobileView()) showMobileDetailPage();
  };

  /**
   * Load message (and thread only if not already loaded).
   * Used when navigating from search results.
   */
  window.loadMessageSmart = function (brdid, msgid, thrdid) {
    var threadEl = document.querySelector('#thread-container > div[data-thrdid]');
    var loadedThrdId = threadEl ? threadEl.getAttribute('data-thrdid') : null;
    if (loadedThrdId && loadedThrdId == thrdid) {
      // Thread already loaded: only fetch the message.
      // The thread tree's hx-indicator spinner handles load feedback.
      currentMsgId = msgid;
      htmx.ajax('GET',
        'pxmboard.php?mode=message&brdid=' + brdid + '&msgid=' + msgid,
        { target: '#message-container', swap: 'innerHTML' }
      );
    } else {
      // Thread needs loading: show the threadlist status spinner.
      var row = document.getElementById('thread_' + thrdid);
      if (row) {
        var icon = row.querySelector('.thread-status-icon');
        var spinner = row.querySelector('.thread-status-spinner');
        if (icon) icon.hidden = true;
        if (spinner) spinner.hidden = false;
      }
      loadThreadAndMessage(brdid, msgid, thrdid);
    }
    if (isMobileView()) showMobileDetailPage();
  };

  /**
   * Load only the thread tree into #thread-container (no message panel update).
   * Used by the reply-count link in the threadlist.
   *
   * @param {number} brdid  - board id
   * @param {number} thrdid - thread id
   */
  window.loadThreadOnly = function (brdid, thrdid) {
    var row = document.getElementById('thread_' + thrdid);
    if (row) {
      var icon = row.querySelector('.thread-status-icon');
      var spinner = row.querySelector('.thread-status-spinner');
      if (icon) icon.hidden = true;
      if (spinner) spinner.hidden = false;
    }
    currentMsgId = 0;
    htmx.ajax('GET',
      'pxmboard.php?mode=thread&brdid=' + brdid + '&thrdid=' + thrdid,
      { target: '#thread-container', swap: 'innerHTML' }
    );
    if (isMobileView()) showMobileDetailPage();
  };

  // ====================================================================
  // MOBILE PAGE SWITCHING
  // ====================================================================

  /**
   * Show the detail page (message + thread) in mobile view.
   */
  function showMobileDetailPage() {
    var el = document.getElementById('board-layout');
    if (el) el.classList.add('mobile-show-detail');
  }
  window.showMobileDetailPage = showMobileDetailPage;

  /**
   * Show the threadlist page in mobile view.
   */
  function showMobileThreadlistPage() {
    var el = document.getElementById('board-layout');
    if (el) el.classList.remove('mobile-show-detail');
  }
  window.showMobileThreadlistPage = showMobileThreadlistPage;

  /**
   * Mobile footer back button handler.
   * On detail page: go back to threadlist.
   * On threadlist: go back to board list.
   */
  window.mobileGoBack = function () {
    var el = document.getElementById('board-layout');
    if (el && el.classList.contains('mobile-show-detail')) {
      showMobileThreadlistPage();
    } else {
      window.location.href = 'pxmboard.php';
    }
  };

  /**
   * Mobile footer "Neuer Beitrag" handler.
   * Loads the message form and switches to detail page.
   * @param {number} brdid - board id
   */
  window.mobileNewPost = function (brdid) {
    htmx.ajax('GET',
      'pxmboard.php?mode=messageform&brdid=' + brdid,
      { target: '#message-container', swap: 'innerHTML' }
    );
    showMobileDetailPage();
  };

  // ====================================================================
  // MOBILE CARD CLICK HANDLER
  // ====================================================================

  /**
   * Event delegation for mobile threadlist card clicks.
   * When a card (htmx-thread-row) is clicked outside of a specific link,
   * load the thread and switch to detail page.
   */
  function initMobileCardClick() {
    var container = document.getElementById('threadlist-container');
    if (!container) return;

    container.addEventListener('click', function (e) {
      if (!isMobileView()) return;

      // Find the clicked thread row
      var row = e.target.closest('.htmx-thread-row');
      if (!row) return;

      // Allow clicks on explicit links (lastpost, replies, author profile) to work normally
      var clickedLink = e.target.closest('a');
      if (clickedLink && !clickedLink.classList.contains('htmx-col-subject')) {
        return; // Let the link handler work
      }

      // Prevent the default subject link behavior (we handle it via card click)
      e.preventDefault();
      e.stopPropagation();

      var brdid = row.getAttribute('data-brdid');
      var msgid = row.getAttribute('data-msgid');
      var thrdid = row.getAttribute('data-thrdid');
      if (brdid && msgid && thrdid) {
        loadThreadAndMessage(+brdid, +msgid, +thrdid);
      }
    });
  }

  // ====================================================================
  // MOBILE SWIPE GESTURE
  // ====================================================================

  var _swipe = null;

  /**
   * Initialize swipe-to-go-back gesture on the detail page.
   * Swiping right slides back to the threadlist.
   */
  function initSwipeGesture() {
    var detailPage = document.getElementById('detail-page');
    if (!detailPage) return;

    detailPage.addEventListener('touchstart', function (e) {
      if (!isMobileView()) return;
      var boardLayout = document.getElementById('board-layout');
      if (!boardLayout || !boardLayout.classList.contains('mobile-show-detail')) return;

      _swipe = {
        startX: e.touches[0].clientX,
        startY: e.touches[0].clientY,
        moved: false
      };
    }, { passive: true });

    detailPage.addEventListener('touchmove', function (e) {
      if (!_swipe) return;

      var deltaX = e.touches[0].clientX - _swipe.startX;
      var deltaY = e.touches[0].clientY - _swipe.startY;

      // Only track horizontal swipes (right direction)
      if (!_swipe.moved && Math.abs(deltaY) > Math.abs(deltaX)) {
        // Vertical scroll dominates: cancel swipe tracking
        _swipe = null;
        return;
      }

      if (deltaX > 10) {
        _swipe.moved = true;
        // Visual feedback: partially slide the board layout
        var boardLayout = document.getElementById('board-layout');
        if (boardLayout) {
          var offset = Math.min(deltaX, 200); // Cap visual feedback
          boardLayout.style.transition = 'none';
          boardLayout.style.transform = 'translateX(calc(-100% + ' + offset + 'px))';
        }
      }
    }, { passive: true });

    detailPage.addEventListener('touchend', function () {
      if (!_swipe) return;

      var boardLayout = document.getElementById('board-layout');
      if (boardLayout) {
        boardLayout.style.transition = ''; // re-enable CSS transition
        if (_swipe.moved) {
          var currentTransform = boardLayout.style.transform;
          var matchOffset = currentTransform.match(/calc\(-100% \+ (\d+)px\)/);
          var offset = matchOffset ? parseInt(matchOffset[1], 10) : 0;
          if (offset > 80) {
            // Animate fully to position 0, then hand control back to CSS class
            boardLayout.style.transform = 'translateX(0)';
            boardLayout.addEventListener('transitionend', function cleanup() {
              boardLayout.removeEventListener('transitionend', cleanup);
              boardLayout.style.transform = '';
              showMobileThreadlistPage();
            });
          } else {
            // Snap back to detail page, then clear inline style
            boardLayout.style.transform = 'translateX(-100%)';
            boardLayout.addEventListener('transitionend', function cleanup() {
              boardLayout.removeEventListener('transitionend', cleanup);
              boardLayout.style.transform = ''; // CSS class .mobile-show-detail takes over
            });
          }
        } else {
          boardLayout.style.transform = '';
        }
      }
      _swipe = null;
    });
  }

  // ====================================================================
  // BROWSER HISTORY (BACK/FORWARD)
  // ====================================================================

  window.addEventListener('popstate', function () {
    var params = new URLSearchParams(window.location.search);
    var mode = params.get('mode');
    var brdid = params.get('brdid');

    if (mode === 'board' && brdid) {
      var thrdid = params.get('thrdid');
      var msgid = params.get('msgid');
      if (thrdid && msgid) {
        var threadEl = document.querySelector('#thread-container > div[data-thrdid]');
        var loadedThrdId = threadEl ? threadEl.getAttribute('data-thrdid') : null;
        if (loadedThrdId && loadedThrdId === thrdid) {
          currentMsgId = +msgid;
          htmx.ajax('GET',
            'pxmboard.php?mode=message&brdid=' + brdid + '&msgid=' + msgid,
            { target: '#message-container', swap: 'innerHTML' }
          );
        } else {
          loadThreadAndMessage(+brdid, +msgid, +thrdid);
        }
        if (isMobileView()) showMobileDetailPage();
      } else {
        // No thread/message in URL: show threadlist in mobile
        if (isMobileView()) showMobileThreadlistPage();
      }
    }
  });

  // ====================================================================
  // HTMX EVENT LISTENERS
  // ====================================================================

  // Replace native window.confirm() for hx-confirm attributes with custom dialog
  document.addEventListener('htmx:confirm', function (evt) {
    if (!evt.detail.question) return;
    evt.preventDefault();
    var dialog = document.getElementById('htmxConfirmDialog');
    var question = document.getElementById('htmxConfirmQuestion');
    if (!dialog || !question) {
      // Fallback to native confirm when dialog is not present (e.g. other skins)
      if (window.confirm(evt.detail.question)) {
        evt.detail.issueRequest(true);
      }
      return;
    }
    question.textContent = evt.detail.question;
    dialog.showModal();

    var okBtn = document.getElementById('htmxConfirmOk');
    var cancelBtn = document.getElementById('htmxConfirmCancel');

    function onOk() {
      dialog.close();
      okBtn.removeEventListener('click', onOk);
      cancelBtn.removeEventListener('click', onCancel);
      evt.detail.issueRequest(true);
    }

    function onCancel() {
      dialog.close();
      okBtn.removeEventListener('click', onOk);
      cancelBtn.removeEventListener('click', onCancel);
    }

    okBtn.addEventListener('click', onOk);
    cancelBtn.addEventListener('click', onCancel);
  });


  document.addEventListener('htmx:afterSwap', function (evt) {
    // Re-apply theme/layout/view checkmarks after content swap
    var currentTheme = localStorage.getItem('pxmboard-skin-theme') || 'auto';
    updateThemeCheckmarks(currentTheme);

    var currentLayout = localStorage.getItem('pxmboard-skin-layout') || 'auto';
    updateLayoutCheckmarks(currentLayout);

    var currentView = localStorage.getItem(VIEW_KEY) || 'auto';
    updateViewCheckmarks(currentView);

    updateSplitCheckmarks();
  });

  // Scroll to top of swapped target for main content changes
  document.addEventListener('htmx:afterSwap', function (evt) {
    var target = evt.detail.target;
    if (target && target.id === 'main-content') {
      target.scrollTop = 0;
    }
  });

  // After thread tree loads: reset all status spinners, invalidate cached row ref, apply highlight
  document.addEventListener('htmx:afterSwap', function (evt) {
    if (evt.detail.target.id === 'thread-container') {
      document.querySelectorAll('.thread-status-spinner').forEach(function (s) { s.hidden = true; });
      document.querySelectorAll('.thread-status-icon').forEach(function (i) { i.hidden = false; });
      _highlightedRow = null;
      updateThreadHighlight();
    }
  });

  // After message container loads: update thread highlight and move dropdown options
  document.addEventListener('htmx:afterSwap', function (evt) {
    if (evt.detail.target.id === 'message-container') {
      var elt = evt.detail.elt;
      if (elt && elt.dataset && elt.dataset.msgid) {
        currentMsgId = parseInt(elt.dataset.msgid, 10);
        updateThreadHighlight();
      }
      if (typeof MessageMove !== 'undefined') {
        MessageMove.updateDropdownOptions();
      }
      // After a form submission (POST) the thread tree is stale — invalidate the
      // cached thread ID so loadMessageSmart forces a fresh reload on next click.
      if (evt.detail.requestConfig && evt.detail.requestConfig.verb === 'post') {
        var threadEl = document.querySelector('#thread-container > div[data-thrdid]');
        if (threadEl) threadEl.setAttribute('data-thrdid', '0');
      }
    }
  });

  // Update header badge counts after each HTMX swap
  document.addEventListener('htmx:afterSwap', function (event) {
    var d = event.detail.target.querySelector('#badge-data');
    if (!d) return;
    var pm = parseInt(d.dataset.pm) || 0;
    var notif = parseInt(d.dataset.notif) || 0;
    var pmBadge = document.getElementById('pm-badge');
    var notifBadge = document.getElementById('notification-badge');
    if (pmBadge) {
      pmBadge.textContent = pm;
      pmBadge.classList.toggle('hidden', pm <= 0);
    }
    if (notifBadge) {
      notifBadge.textContent = notif;
      notifBadge.classList.toggle('hidden', notif <= 0);
    }
  });

  // ====================================================================
  // DOM READY INITIALIZATION
  // ====================================================================

  document.addEventListener('DOMContentLoaded', function () {
    // Clear modal body when native dialog closes (ESC key, backdrop click, or close button)
    var htmxModal = document.getElementById('htmxModal');
    if (htmxModal) {
      htmxModal.addEventListener('close', function () {
        var body = document.getElementById('htmxModalBody');
        if (body) body.innerHTML = '';
      });
    }

    // Apply saved layout preference
    var savedLayout = localStorage.getItem('pxmboard-skin-layout') || 'auto';
    applyLayout(savedLayout);
    updateLayoutCheckmarks(savedLayout);

    // Apply saved panel split sizes on every layout mode
    if (isMobileView()) {
      applyMobileSplit();
    } else {
      applyGridSplits();
    }

    // Re-apply grid splits on every resize to clear stale inline styles.
    // applyGridSplits() always clears gridTemplateColumns/Rows first, so
    // CSS media queries can take effect (e.g. after Windows Restore button).
    // applyMobileSplit() is called additionally when entering mobile mode.
    var _lastMobile = isMobileView();
    window.addEventListener('resize', function () {
      var nowMobile = isMobileView();
      syncMobileClass();
      applyGridSplits();
      if (nowMobile && !_lastMobile) {
        applyMobileSplit();
      }
      _lastMobile = nowMobile;
    });

    // Wire up all divider drag handles
    initDividerDrag();

    // Update theme checkmarks
    updateThemeCheckmarks(savedTheme);

    // Update view checkmarks
    updateViewCheckmarks(savedView);

    // Update split checkmarks
    updateSplitCheckmarks();

    // Initialize mobile card click handler (event delegation)
    initMobileCardClick();

    // Initialize swipe gesture on detail page
    initSwipeGesture();

    // If page loaded with msgid/thrdid params, show detail page in mobile
    if (isMobileView()) {
      var params = new URLSearchParams(window.location.search);
      if (params.get('msgid') || params.get('thrdid')) {
        showMobileDetailPage();
      }
    }
  });

  // ====================================================================
  // MESSAGE MOVE - HTMX SKIN DOM ADAPTER
  // ====================================================================

  // Override MessageMove extractors to work with the htmx skin DOM structure.
  // These run after message-move.js has loaded (script order in layout_footer.tpl).
  if (typeof MessageMove !== 'undefined') {
    MessageMove._extractSubject = function () {
      var mc = document.getElementById('message-container');
      if (!mc) return '';
      // Subject is the font-semibold span inside the Thema section (py-2 header)
      var el = mc.querySelector('.px-4.py-2 .font-semibold');
      return el ? el.textContent.trim() : '';
    };

    MessageMove._extractAuthor = function () {
      var mc = document.getElementById('message-container');
      if (!mc) return '';
      // Registered user: anchor to userprofile inside the py-3 header
      var link = mc.querySelector('.px-4.py-3 .font-semibold a[href*="userprofile"]');
      if (link) return link.textContent.trim();
      // Guest: plain text inside font-semibold div
      var nameDiv = mc.querySelector('.px-4.py-3 .font-semibold');
      return nameDiv ? nameDiv.textContent.trim() : '';
    };

    MessageMove._extractDate = function () {
      var mc = document.getElementById('message-container');
      if (!mc) return '';
      // Date is inside the text-xs.text-content-secondary div in the py-3 header
      var dateDiv = mc.querySelector('.px-4.py-3 .text-xs.text-content-secondary');
      if (dateDiv) {
        var match = dateDiv.textContent.match(/am\s+(.+?)\s+Uhr/);
        if (match) return match[1].trim();
      }
      return '';
    };

    // Show the move badge bar directly (no iframe postMessage needed)
    MessageMove.showMoveBadge = function (messageId, subject, author, date) {
      var badge = document.getElementById('move-badge');
      var msg = document.getElementById('move-badge-message');
      if (badge && msg) {
        msg.textContent = MessageMove._buildBadgeText(messageId, subject, author, date);
        badge.classList.remove('hidden');
      }
    };

    // Hide the move badge bar
    MessageMove.hideMoveBadge = function () {
      var badge = document.getElementById('move-badge');
      if (badge) {
        badge.classList.add('hidden');
      }
    };

    // Wire up "Auswahl aufheben" cancel button
    var cancelBtn = document.getElementById('btn-cancel-move');
    if (cancelBtn) {
      cancelBtn.addEventListener('click', function () {
        MessageMove.clearMoveSelection();
      });
    }
  }

})();
