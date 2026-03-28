/**
 * DeviceHub — Archive Filter Accordion
 *
 * Handles open/close of filter groups in the sidebar.
 * Filtering itself is URL-based (no JS needed for that part).
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.devhub-filter-group__toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var group      = btn.closest('.devhub-filter-group');
                var list       = group.querySelector('.devhub-filter-group__list');
                var icon       = btn.querySelector('.fas');
                var isExpanded = btn.getAttribute('aria-expanded') === 'true';

                btn.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
                list.classList.toggle('devhub-filter-group__list--collapsed', isExpanded);

                if (icon) {
                    icon.classList.toggle('fa-chevron-up', !isExpanded);
                    icon.classList.toggle('fa-chevron-down', isExpanded);
                }
            });
        });
    });
}());
