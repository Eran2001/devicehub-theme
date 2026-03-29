/**
 * DeviceHub — Single Product
 *
 * Handles: tabs, color swatches, storage options,
 *          variation ID resolution, bundle carousel, buy now.
 */
(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    devhubInitTabs();
    devhubInitColorSwatches();
    devhubInitStorageOptions();
    devhubInitBundleCarousel();
    devhubInitBuyNow();
  });

  // ── Tabs ──────────────────────────────────────────────────────────────────

  function devhubInitTabs() {
    var tabBtns = document.querySelectorAll(".devhub-single__tab-btn");
    var tabPanels = document.querySelectorAll(".devhub-single__tab-panel");
    if (!tabBtns.length) return;

    tabBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var target = btn.getAttribute("data-tab");

        tabBtns.forEach(function (b) {
          b.classList.remove("devhub-single__tab-btn--active");
          b.setAttribute("aria-selected", "false");
        });
        tabPanels.forEach(function (p) {
          p.classList.remove("devhub-single__tab-panel--active");
          p.setAttribute("hidden", "");
        });

        btn.classList.add("devhub-single__tab-btn--active");
        btn.setAttribute("aria-selected", "true");

        var panelId =
          "devhubTab" + target.charAt(0).toUpperCase() + target.slice(1);
        var panel = document.getElementById(panelId);
        if (panel) {
          panel.classList.add("devhub-single__tab-panel--active");
          panel.removeAttribute("hidden");
        }
      });
    });
  }

  // ── Color swatches ────────────────────────────────────────────────────────

  function devhubInitColorSwatches() {
    var swatches = document.querySelectorAll(".devhub-single__color-swatch");
    if (!swatches.length) return;

    swatches.forEach(function (swatch) {
      swatch.addEventListener("click", function () {
        swatches.forEach(function (s) {
          s.classList.remove("devhub-single__color-swatch--active");
        });
        swatch.classList.add("devhub-single__color-swatch--active");

        var input = document.getElementById("devhubAttr_pa_color");
        if (input) input.value = swatch.getAttribute("data-value");

        devhubResolveVariation();
      });
    });

    // Auto-select if only one option
    if (swatches.length === 1) {
      swatches[0].click();
    }
  }

  // ── Storage options ───────────────────────────────────────────────────────

  function devhubInitStorageOptions() {
    var btns = document.querySelectorAll(".devhub-single__storage-btn");
    if (!btns.length) return;

    btns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        btns.forEach(function (b) {
          b.classList.remove("devhub-single__storage-btn--active");
        });
        btn.classList.add("devhub-single__storage-btn--active");

        var input = document.getElementById("devhubAttr_pa_storage");
        if (input) input.value = btn.getAttribute("data-value");

        devhubResolveVariation();
      });
    });

    // Auto-select if only one option
    if (btns.length === 1) {
      btns[0].click();
    }
  }

  // ── Variation resolver ────────────────────────────────────────────────────

  function devhubResolveVariation() {
    var el = document.querySelector(".devhub-single");
    if (!el) return;

    var variations;
    try {
      variations = JSON.parse(el.getAttribute("data-variations") || "[]");
    } catch (e) {
      return;
    }
    if (!variations.length) return;

    var colorInput = document.getElementById("devhubAttr_pa_color");
    var storageInput = document.getElementById("devhubAttr_pa_storage");
    var varIdInput = document.getElementById("devhubVariationId");

    var selectedColor = colorInput ? colorInput.value : "";
    var selectedStorage = storageInput ? storageInput.value : "";

    var match = null;
    for (var i = 0; i < variations.length; i++) {
      var v = variations[i];
      var attr = v.attributes;
      var colorOk =
        !attr["attribute_pa_color"] ||
        attr["attribute_pa_color"] === selectedColor;
      var storageOk =
        !attr["attribute_pa_storage"] ||
        attr["attribute_pa_storage"] === selectedStorage;
      if (colorOk && storageOk) {
        match = v;
        break;
      }
    }

    if (match && varIdInput) varIdInput.value = match.id;
  }

  // ── Bundle carousel ───────────────────────────────────────────────────────
  // Fix: use requestAnimationFrame to defer initial slide() until after
  // the browser has painted and viewport has its real width.

  function devhubInitBundleCarousel() {
    var viewport = document.querySelector(".devhub-single__bundles-viewport");
    var track = document.getElementById("devhubBundlesTrack");
    var nextBtn = document.getElementById("devhubBundleNext");
    var prevBtn = document.getElementById("devhubBundlePrev");
    if (!track || !viewport) return;

    var cards = track.querySelectorAll(".devhub-single__bundle-card");
    var VISIBLE = 4;
    var GAP = 12;
    var current = 0;
    var total = cards.length;

    // Card selection
    cards.forEach(function (card) {
      card.addEventListener("click", function (e) {
        if (e.target.closest(".devhub-single__bundle-link")) return;
        cards.forEach(function (c) {
          c.classList.remove("devhub-single__bundle-card--active");
        });
        card.classList.add("devhub-single__bundle-card--active");
      });
    });

    function getCardWidth() {
      // Use viewport's actual rendered width — never 0
      var vw = viewport.getBoundingClientRect().width;
      return (vw - GAP * (VISIBLE - 1)) / VISIBLE;
    }

    function slide() {
      var cardWidth = getCardWidth();

      // Guard: if viewport not rendered yet, retry on next frame
      if (cardWidth <= 0) {
        requestAnimationFrame(slide);
        return;
      }

      cards.forEach(function (card) {
        card.style.width = cardWidth + "px";
        card.style.flexShrink = "0";
      });

      var offset = current * (cardWidth + GAP);
      track.style.transform = "translateX(-" + offset + "px)";

      if (prevBtn)
        prevBtn.style.visibility = current <= 0 ? "hidden" : "visible";
      if (nextBtn)
        nextBtn.style.visibility =
          current + VISIBLE >= total ? "hidden" : "visible";
    }

    if (nextBtn) {
      nextBtn.addEventListener("click", function () {
        if (current + VISIBLE < total) {
          current++;
          slide();
        }
      });
    }

    if (prevBtn) {
      prevBtn.addEventListener("click", function () {
        if (current > 0) {
          current--;
          slide();
        }
      });
    }

    // Defer initial render until browser has painted
    requestAnimationFrame(slide);

    var resizeTimer;
    window.addEventListener("resize", function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        current = 0;
        slide();
      }, 100);
    });
  }

  // ── Buy Now ───────────────────────────────────────────────────────────────

  function devhubInitBuyNow() {
    var buyBtn = document.querySelector(".devhub-single__btn--buy");
    var form = document.querySelector(".devhub-single__cart-form");
    var submitBtn = form
      ? form.querySelector('button[name="add-to-cart"]')
      : null;
    if (!buyBtn || !form || !submitBtn) return;

    buyBtn.addEventListener("click", function () {
      if (!form.querySelector('[name="devhub_buy_now"]')) {
        var flag = document.createElement("input");
        flag.type = "hidden";
        flag.name = "devhub_buy_now";
        flag.value = "1";
        form.appendChild(flag);
      }
      submitBtn.click();
    });
  }
})();
