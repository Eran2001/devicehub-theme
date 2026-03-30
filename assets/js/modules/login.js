/**
 * My Account guest auth flow.
 * Keeps the WooCommerce form intact and only switches UI panels.
 */
(function () {
    'use strict';

    function initLoginPanels() {
        var root = document.querySelector('[data-devhub-auth]');
        if (!root) return;

        var panels = Array.prototype.slice.call(root.querySelectorAll('[data-devhub-panel]'));
        var status = root.querySelector('[data-devhub-status]');
        var initialPanel = root.getAttribute('data-initial-panel') || 'chooser';

        function focusPanel(panel) {
            var target = panel.querySelector('input, button, a, select, textarea');
            if (target) target.focus();
        }

        function setPanel(name, shouldFocus) {
            var activePanel = null;

            panels.forEach(function (panel) {
                var isActive = panel.getAttribute('data-devhub-panel') === name;
                panel.hidden = !isActive;
                panel.classList.toggle('is-active', isActive);

                if (isActive) activePanel = panel;
            });

            root.setAttribute('data-active-panel', name);

            if (status) {
                status.hidden = true;
                status.textContent = '';
            }

            if (shouldFocus && activePanel) {
                focusPanel(activePanel);
            }
        }

        root.classList.add('is-enhanced');
        setPanel(initialPanel, false);

        root.addEventListener('click', function (event) {
            var openTrigger = event.target.closest('[data-devhub-auth-open]');
            if (openTrigger) {
                event.preventDefault();
                setPanel(openTrigger.getAttribute('data-devhub-auth-open'), true);
                return;
            }

            var placeholderTrigger = event.target.closest('[data-devhub-placeholder]');
            if (!placeholderTrigger || !status) return;

            event.preventDefault();
            status.hidden = false;
            status.textContent = placeholderTrigger.getAttribute('data-devhub-message') || 'This sign-in method will be connected later.';
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLoginPanels);
    } else {
        initLoginPanels();
    }
}());
