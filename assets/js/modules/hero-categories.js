(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    var header = document.querySelector(".devhub-hero__cat-header");
    if (!header) return;

    var nav = header.closest(".devhub-hero__categories");

    header.addEventListener("click", function () {
      nav.classList.toggle("is-collapsed");
    });
  });
})();
