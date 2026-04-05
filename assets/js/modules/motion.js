(function () {
  "use strict";

  function prefersReducedMotion() {
    return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  }

  function registerPlugin(gsap, plugin) {
    if (!gsap || !plugin) {
      return false;
    }

    if (typeof gsap.registerPlugin === "function") {
      gsap.registerPlugin(plugin);
      return true;
    }

    return false;
  }

  function revealBatch(gsap, ScrollTrigger, selector, options) {
    var elements = gsap.utils.toArray(selector);

    if (!elements.length) {
      return;
    }

    gsap.set(elements, {
      autoAlpha: 0,
      y: options.y || 24,
      scale: options.scale || 0.985,
      transformOrigin: "center top",
    });

    ScrollTrigger.batch(elements, {
      start: options.start || "top 86%",
      once: true,
      onEnter: function (batch) {
        gsap.to(batch, {
          autoAlpha: 1,
          y: 0,
          scale: 1,
          duration: options.duration || 0.8,
          ease: options.ease || "power3.out",
          stagger: options.stagger || 0.08,
          clearProps: "opacity,visibility,transform",
        });
      },
    });
  }

  function initHeroMotion(gsap) {
    var hero = document.querySelector(".devhub-hero");

    if (!hero) {
      return;
    }

    var timeline = gsap.timeline({
      defaults: {
        duration: 0.85,
        ease: "power3.out",
      },
    });

    var categoryPanel = hero.querySelector(".devhub-hero__categories");
    var banner = hero.querySelector(".devhub-hero__banner");
    var controls = hero.querySelectorAll(
      ".devhub-hero__arrow, .devhub-hero__dot"
    );

    if (categoryPanel) {
      timeline.from(
        categoryPanel,
        {
          autoAlpha: 0,
          x: -28,
        },
        0
      );
    }

    if (banner) {
      timeline.from(
        banner,
        {
          autoAlpha: 0,
          y: 26,
          scale: 0.985,
        },
        0.08
      );
    }

    if (controls.length) {
      timeline.from(
        controls,
        {
          autoAlpha: 0,
          y: 10,
          stagger: 0.05,
          duration: 0.45,
        },
        0.4
      );
    }
  }

  function initSectionMotion(gsap, ScrollTrigger) {
    revealBatch(
      gsap,
      ScrollTrigger,
      ".devhub-flash, .devhub-products, .devhub-categories, .devhub-preorder, .devhub-broadbands, .devhub-archive__header, .devhub-archive__toolbar, .devhub-single__tabs, .devhub-account-wrap, .devhub-auth__card, .devhub-delivery-method",
      {
        y: 28,
        duration: 0.85,
        stagger: 0.12,
      }
    );

    revealBatch(
      gsap,
      ScrollTrigger,
      ".devhub-products__grid .devhub-product-card, .devhub-archive__grid .devhub-product-card, .devhub-dashboard-card, .devhub-address-card, .devhub-empty-state__icon-wrap, .devhub-empty-state__text, .devhub-empty-state__actions",
      {
        y: 34,
        scale: 0.975,
        duration: 0.72,
        stagger: 0.1,
      }
    );

    revealBatch(
      gsap,
      ScrollTrigger,
      ".devhub-single__gallery, .devhub-single__info > *, .wc-block-cart__main > *, .wc-block-cart__sidebar > *, .wc-block-checkout__main > *, .wc-block-checkout__sidebar > *, .devhub-account-sidebar > *, .devhub-account-content > *",
      {
        y: 22,
        scale: 0.99,
        duration: 0.7,
        stagger: 0.06,
      }
    );
  }

  function initCardHover(gsap) {
    var cards = document.querySelectorAll(
      ".devhub-product-card, .devhub-dashboard-card, .devhub-address-card, .devhub-delivery-method__option, .devhub-delivery-method__store"
    );

    cards.forEach(function (card) {
      var hoverTween = gsap.to(card, {
        y: -6,
        duration: 0.28,
        ease: "power2.out",
        paused: true,
      });

      card.addEventListener("mouseenter", function () {
        hoverTween.play();
      });

      card.addEventListener("mouseleave", function () {
        hoverTween.reverse();
      });
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    var gsap = window.gsap;
    var ScrollTrigger = window.ScrollTrigger;

    if (!gsap || prefersReducedMotion()) {
      return;
    }

    if (!registerPlugin(gsap, ScrollTrigger)) {
      return;
    }

    document.documentElement.classList.add("devhub-motion-ready");

    initHeroMotion(gsap);
    initSectionMotion(gsap, ScrollTrigger);
    initCardHover(gsap);

    ScrollTrigger.refresh();
  });
})();
