(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    var header = document.querySelector(".devhub-hero__cat-header");
    if (!header) {
      return;
    }

    var nav = header.closest(".devhub-hero__categories");
    var list = nav ? nav.querySelector(".devhub-hero__cat-list") : null;

    if (!nav || !list) {
      return;
    }

    var isAnimating = false;

    header.setAttribute("role", "button");
    header.setAttribute("tabindex", "0");
    header.setAttribute("aria-expanded", "true");

    function setExpandedState(isExpanded) {
      header.setAttribute("aria-expanded", isExpanded ? "true" : "false");
      nav.classList.toggle("is-collapsed", !isExpanded);
    }

    function expandList() {
      if (isAnimating || !nav.classList.contains("is-collapsed")) {
        return;
      }

      isAnimating = true;
      nav.classList.add("is-animating");
      list.hidden = false;
      list.style.height = "0px";
      list.getBoundingClientRect();
      setExpandedState(true);
      list.style.height = list.scrollHeight + "px";
    }

    function collapseList() {
      if (isAnimating || nav.classList.contains("is-collapsed")) {
        return;
      }

      isAnimating = true;
      nav.classList.add("is-animating");
      list.hidden = false;
      list.style.height = list.scrollHeight + "px";
      list.getBoundingClientRect();
      setExpandedState(false);
      list.style.height = "0px";
    }

    function toggleList() {
      if (nav.classList.contains("is-collapsed")) {
        expandList();
        return;
      }

      collapseList();
    }

    header.addEventListener("click", function () {
      toggleList();
    });

    header.addEventListener("keydown", function (event) {
      if (event.key !== "Enter" && event.key !== " ") {
        return;
      }

      event.preventDefault();
      toggleList();
    });

    list.addEventListener("transitionend", function (event) {
      if (event.propertyName !== "height") {
        return;
      }

      if (nav.classList.contains("is-collapsed")) {
        list.hidden = true;
        list.style.height = "0px";
      } else {
        list.style.height = "auto";
      }

      isAnimating = false;
      nav.classList.remove("is-animating");
    });

    window.addEventListener("resize", function () {
      if (nav.classList.contains("is-collapsed") || isAnimating) {
        return;
      }

      list.style.height = "auto";
    });
  });
})();
