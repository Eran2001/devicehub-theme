(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    var slider = document.getElementById("devhubHeroSlider");

    if (!slider) {
      return;
    }

    var track = slider.querySelector(".devhub-hero__track");
    var slides = slider.querySelectorAll(".devhub-hero__slide");
    var prevBtn = slider.querySelector("#devhubHeroPrev");
    var nextBtn = slider.querySelector("#devhubHeroNext");
    var dots = slider.querySelectorAll(".devhub-hero__dot");
    var viewport = slider.querySelector(".devhub-hero__viewport");
    var currentIndex = 0;
    var touchStartX = 0;
    var touchDeltaX = 0;
    var autoPlayDelay = 3000;
    var autoPlayTimer = null;

    if (!track || slides.length <= 1) {
      return;
    }

    function render() {
      track.style.transform = "translateX(-" + currentIndex * 100 + "%)";

      if (prevBtn) {
        prevBtn.disabled = currentIndex === 0;
      }

      if (nextBtn) {
        nextBtn.disabled = currentIndex === slides.length - 1;
      }

      dots.forEach(function (dot, index) {
        var isActive = index === currentIndex;
        dot.classList.toggle("is-active", isActive);
        dot.setAttribute("aria-current", isActive ? "true" : "false");
      });
    }

    function goTo(index) {
      currentIndex = Math.max(0, Math.min(index, slides.length - 1));
      render();
    }

    function goToNextLoop() {
      if (currentIndex >= slides.length - 1) {
        goTo(0);
        return;
      }

      goTo(currentIndex + 1);
    }

    function stopAutoPlay() {
      if (autoPlayTimer) {
        window.clearInterval(autoPlayTimer);
        autoPlayTimer = null;
      }
    }

    function startAutoPlay() {
      stopAutoPlay();
      autoPlayTimer = window.setInterval(goToNextLoop, autoPlayDelay);
    }

    if (prevBtn) {
      prevBtn.addEventListener("click", function () {
        goTo(currentIndex - 1);
        startAutoPlay();
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener("click", function () {
        goTo(currentIndex + 1);
        startAutoPlay();
      });
    }

    dots.forEach(function (dot) {
      dot.addEventListener("click", function () {
        goTo(Number(dot.getAttribute("data-slide-index")));
        startAutoPlay();
      });
    });

    if (viewport) {
      viewport.addEventListener("mouseenter", stopAutoPlay);
      viewport.addEventListener("mouseleave", startAutoPlay);

      viewport.addEventListener(
        "touchstart",
        function (event) {
          stopAutoPlay();
          touchStartX = event.touches[0].clientX;
          touchDeltaX = 0;
        },
        { passive: true },
      );

      viewport.addEventListener(
        "touchmove",
        function (event) {
          touchDeltaX = event.touches[0].clientX - touchStartX;
        },
        { passive: true },
      );

      viewport.addEventListener("touchend", function () {
        if (Math.abs(touchDeltaX) < 40) {
          startAutoPlay();
          return;
        }

        if (touchDeltaX < 0) {
          goTo(currentIndex + 1);
          startAutoPlay();
          return;
        }

        goTo(currentIndex - 1);
        startAutoPlay();
      });
    }

    render();
    startAutoPlay();
  });
})();
