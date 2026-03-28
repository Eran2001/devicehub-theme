/**
 * DeviceHub — Single Product
 *
 * Handles: tabs, color swatches, storage options,
 *          variation ID resolution, bundle carousel, buy now.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        devhubInitTabs();
        devhubInitColorSwatches();
        devhubInitStorageOptions();
        devhubInitBundleCarousel();
        devhubInitBuyNow();
    });


    // ── Tabs ──────────────────────────────────────────────────────────────────

    function devhubInitTabs() {
        var tabBtns   = document.querySelectorAll('.devhub-single__tab-btn');
        var tabPanels = document.querySelectorAll('.devhub-single__tab-panel');
        if (!tabBtns.length) return;

        tabBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = btn.getAttribute('data-tab');

                tabBtns.forEach(function (b) {
                    b.classList.remove('devhub-single__tab-btn--active');
                    b.setAttribute('aria-selected', 'false');
                });
                tabPanels.forEach(function (p) {
                    p.classList.remove('devhub-single__tab-panel--active');
                    p.setAttribute('hidden', '');
                });

                btn.classList.add('devhub-single__tab-btn--active');
                btn.setAttribute('aria-selected', 'true');

                // Panel IDs: devhubTabFeatures, devhubTabSpecs
                var panelId = 'devhubTab' + target.charAt(0).toUpperCase() + target.slice(1);
                var panel   = document.getElementById(panelId);
                if (panel) {
                    panel.classList.add('devhub-single__tab-panel--active');
                    panel.removeAttribute('hidden');
                }
            });
        });
    }


    // ── Color swatches ────────────────────────────────────────────────────────

    function devhubInitColorSwatches() {
        var swatches = document.querySelectorAll('.devhub-single__color-swatch');
        if (!swatches.length) return;

        swatches.forEach(function (swatch) {
            swatch.addEventListener('click', function () {
                swatches.forEach(function (s) {
                    s.classList.remove('devhub-single__color-swatch--active');
                });
                swatch.classList.add('devhub-single__color-swatch--active');

                var input = document.getElementById('devhubAttr_pa_color');
                if (input) input.value = swatch.getAttribute('data-value');

                devhubResolveVariation();
            });
        });
    }


    // ── Storage options ───────────────────────────────────────────────────────

    function devhubInitStorageOptions() {
        var btns = document.querySelectorAll('.devhub-single__storage-btn');
        if (!btns.length) return;

        btns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                btns.forEach(function (b) {
                    b.classList.remove('devhub-single__storage-btn--active');
                });
                btn.classList.add('devhub-single__storage-btn--active');

                var input = document.getElementById('devhubAttr_pa_storage');
                if (input) input.value = btn.getAttribute('data-value');

                devhubResolveVariation();
            });
        });
    }


    // ── Variation resolver ────────────────────────────────────────────────────
    // When both color + storage are selected, finds the matching variation_id
    // and updates the hidden form fields so WooCommerce can process the cart.

    function devhubResolveVariation() {
        var el = document.querySelector('.devhub-single');
        if (!el) return;

        var variations;
        try {
            variations = JSON.parse(el.getAttribute('data-variations') || '[]');
        } catch (e) {
            return;
        }
        if (!variations.length) return;

        var colorInput   = document.getElementById('devhubAttr_pa_color');
        var storageInput = document.getElementById('devhubAttr_pa_storage');
        var varIdInput   = document.getElementById('devhubVariationId');

        var selectedColor   = colorInput   ? colorInput.value   : '';
        var selectedStorage = storageInput ? storageInput.value : '';

        var match = null;
        for (var i = 0; i < variations.length; i++) {
            var v    = variations[i];
            var attr = v.attributes;
            // Empty string means "Any" — matches everything
            var colorOk   = !attr['attribute_pa_color']   || attr['attribute_pa_color']   === selectedColor;
            var storageOk = !attr['attribute_pa_storage']  || attr['attribute_pa_storage']  === selectedStorage;
            if (colorOk && storageOk) { match = v; break; }
        }

        if (match && varIdInput) {
            varIdInput.value = match.id;
        }
    }


    // ── Bundle carousel ───────────────────────────────────────────────────────
    // Shows 3 cards at a time; prev/next arrows slide by 1 when > 3 bundles.
    // Card widths are set in JS to ensure exact 3-up layout.

    function devhubInitBundleCarousel() {
        var viewport = document.querySelector('.devhub-single__bundles-viewport');
        var track    = document.getElementById('devhubBundlesTrack');
        var nextBtn  = document.getElementById('devhubBundleNext');
        var prevBtn  = document.getElementById('devhubBundlePrev');
        if (!track) return;

        var cards   = track.querySelectorAll('.devhub-single__bundle-card');
        var VISIBLE = 3;
        var GAP     = 12; // must match CSS gap on .devhub-single__bundles-track
        var current = 0;
        var total   = cards.length;

        // ── Card selection (click to activate) ─────────────────────────────
        cards.forEach(function (card) {
            card.addEventListener('click', function (e) {
                // Don't intercept the "View Details" link
                if (e.target.closest('.devhub-single__bundle-link')) return;
                cards.forEach(function (c) {
                    c.classList.remove('devhub-single__bundle-card--active');
                });
                card.classList.add('devhub-single__bundle-card--active');
            });
        });

        // ── Carousel sliding ────────────────────────────────────────────────
        function getCardWidth() {
            if (!viewport) return 0;
            return (viewport.clientWidth - GAP * (VISIBLE - 1)) / VISIBLE;
        }

        function setCardWidths() {
            var w = getCardWidth();
            cards.forEach(function (card) {
                card.style.width = w + 'px';
            });
        }

        function slide() {
            setCardWidths();
            var cardWidth = getCardWidth();
            var offset    = current * (cardWidth + GAP);
            track.style.transform = 'translateX(-' + offset + 'px)';

            if (prevBtn) prevBtn.style.visibility = (current <= 0) ? 'hidden' : 'visible';
            if (nextBtn) nextBtn.style.visibility = (current + VISIBLE >= total) ? 'hidden' : 'visible';
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                if (current + VISIBLE < total) { current++; slide(); }
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                if (current > 0) { current--; slide(); }
            });
        }

        // Initial render + re-render on resize
        slide();
        window.addEventListener('resize', slide);
    }


    // ── Buy Now ───────────────────────────────────────────────────────────────
    // Adds a hidden `devhub_buy_now` field then submits the cart form.
    // hooks.php catches that field server-side and redirects to checkout.

    function devhubInitBuyNow() {
        var buyBtn    = document.querySelector('.devhub-single__btn--buy');
        var form      = document.querySelector('.devhub-single__cart-form');
        var submitBtn = form ? form.querySelector('button[name="add-to-cart"]') : null;
        if (!buyBtn || !form || !submitBtn) return;

        buyBtn.addEventListener('click', function () {
            // Add devhub_buy_now flag (once)
            if (!form.querySelector('[name="devhub_buy_now"]')) {
                var flag   = document.createElement('input');
                flag.type  = 'hidden';
                flag.name  = 'devhub_buy_now';
                flag.value = '1';
                form.appendChild(flag);
            }
            // Click the real submit button so add-to-cart name/value is sent
            submitBtn.click();
        });
    }

}());
