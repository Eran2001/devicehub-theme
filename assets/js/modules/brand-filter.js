/**
 * DeviceHub — Brand Filter
 *
 * Filters product cards within a section grid by brand slug.
 * Works by toggling visibility on .devhub-product-card[data-brands].
 *
 * Markup contract:
 *  - Filter buttons: .devhub-brand-tab[data-brand][data-section]
 *  - Grid container: #{section_id}-grid
 *  - Product cards:  .devhub-product-card[data-brands="slug1 slug2"]
 *
 * No dependencies. Loaded only on is_front_page() via inc/enqueue.php.
 */

(function () {
  "use strict";

  function initBrandFilters() {
    document.querySelectorAll(".devhub-brand-tab").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const sectionId = this.dataset.section;
        const brand = this.dataset.brand;

        // Update active state — scoped to this section's tabs only
        document
          .querySelectorAll("#" + sectionId + " .devhub-brand-tab")
          .forEach(function (tab) {
            tab.classList.remove("devhub-brand-tab--active");
            tab.setAttribute("aria-pressed", "false");
          });

        this.classList.add("devhub-brand-tab--active");
        this.setAttribute("aria-pressed", "true");

        // Filter cards — scoped to this section's grid only
        const grid = document.querySelector("#" + sectionId + "-grid");
        let visibleCount = 0;

        grid.querySelectorAll(".devhub-product-card").forEach(function (card) {
          const cardBrands = card.dataset.brands
            ? card.dataset.brands.split(" ")
            : [];

          const visible = brand === "all" || cardBrands.includes(brand);
          card.hidden = !visible;
          if (visible) visibleCount++;
        });

        // Show/hide empty state
        let empty = grid.querySelector(".devhub-brand-empty");
        if (visibleCount === 0) {
          if (!empty) {
            empty = document.createElement("div");
            empty.className = "devhub-brand-empty";
            empty.innerHTML =
              '<p>No products found for this brand.</p>';
            grid.appendChild(empty);
          }
          empty.hidden = false;
        } else if (empty) {
          empty.hidden = true;
        }
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initBrandFilters);
  } else {
    initBrandFilters();
  }
})();
