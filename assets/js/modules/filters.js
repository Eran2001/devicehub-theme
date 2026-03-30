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

    // ── Custom sort dropdown — replaces native <select> ──────────────────────
    var ordering = document.querySelector('.woocommerce-ordering');
    if (ordering) {
        var nativeSelect = ordering.querySelector('select');
        if (nativeSelect) {
            // Build custom dropdown markup
            var wrapper = document.createElement('div');
            wrapper.className = 'devhub-sort-dropdown';

            var trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'devhub-sort-dropdown__trigger';
            trigger.setAttribute('aria-haspopup', 'listbox');
            trigger.setAttribute('aria-expanded', 'false');

            var label = document.createElement('span');
            label.className = 'devhub-sort-dropdown__label';
            label.textContent = nativeSelect.options[nativeSelect.selectedIndex].text;

            var chevron = document.createElement('span');
            chevron.className = 'devhub-sort-dropdown__chevron';
            chevron.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"><path fill="currentColor" d="M6 8L1 3h10z"/></svg>';

            trigger.appendChild(label);
            trigger.appendChild(chevron);

            var list = document.createElement('ul');
            list.className = 'devhub-sort-dropdown__list';
            list.setAttribute('role', 'listbox');

            Array.from(nativeSelect.options).forEach(function (opt) {
                var item = document.createElement('li');
                item.className = 'devhub-sort-dropdown__item' + (opt.selected ? ' devhub-sort-dropdown__item--active' : '');
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', opt.selected ? 'true' : 'false');
                item.dataset.value = opt.value;
                item.textContent = opt.text;

                item.addEventListener('click', function () {
                    nativeSelect.value = opt.value;
                    label.textContent = opt.text;
                    list.querySelectorAll('.devhub-sort-dropdown__item').forEach(function (el) {
                        el.classList.remove('devhub-sort-dropdown__item--active');
                        el.setAttribute('aria-selected', 'false');
                    });
                    item.classList.add('devhub-sort-dropdown__item--active');
                    item.setAttribute('aria-selected', 'true');
                    closeDropdown();
                    ordering.submit();
                });

                list.appendChild(item);
            });

            wrapper.appendChild(trigger);
            wrapper.appendChild(list);
            nativeSelect.style.display = 'none';
            ordering.appendChild(wrapper);

            function openDropdown() {
                wrapper.classList.add('devhub-sort-dropdown--open');
                trigger.setAttribute('aria-expanded', 'true');
            }

            function closeDropdown() {
                wrapper.classList.remove('devhub-sort-dropdown--open');
                trigger.setAttribute('aria-expanded', 'false');
            }

            trigger.addEventListener('click', function () {
                wrapper.classList.contains('devhub-sort-dropdown--open') ? closeDropdown() : openDropdown();
            });

            document.addEventListener('click', function (e) {
                if (!wrapper.contains(e.target)) closeDropdown();
            });
        }
    }
}());
