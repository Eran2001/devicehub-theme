/**
 * My Account — accordion toggle for "Your account" group
 */
(function () {
    'use strict';

    function initAccountAccordion() {
        var btn = document.querySelector('.devhub-account-nav__group-btn');
        if (!btn) return;

        var group = btn.parentElement;

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var isOpen = group.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAccountAccordion);
    } else {
        initAccountAccordion();
    }
}());
