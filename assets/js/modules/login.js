/**
 * My Account / checkout auth flow.
 * Handles panel switching plus mobile OTP request and verification.
 */
(function () {
    'use strict';

    function initLoginPanels() {
        var root = document.querySelector('[data-devhub-auth]');
        if (!root) return;

        var panels = Array.prototype.slice.call(root.querySelectorAll('[data-devhub-panel]'));
        var chooserStatus = root.querySelector('[data-devhub-status]');
        var initialPanel = root.getAttribute('data-initial-panel') || 'chooser';
        var requestForm = root.querySelector('[data-devhub-mobile-request]');
        var verifyForm = root.querySelector('[data-devhub-mobile-verify]');
        var requestStatus = root.querySelector('[data-devhub-mobile-request-status]');
        var verifyStatus = root.querySelector('[data-devhub-mobile-verify-status]');
        var debugStatus = root.querySelector('[data-devhub-mobile-debug]');
        var verifyCopy = root.querySelector('[data-devhub-mobile-verify-copy]');
        var resendButton = root.querySelector('[data-devhub-mobile-resend]');
        var currentMobilePhone = '';

        function focusPanel(panel) {
            var target = panel.querySelector('input, button, a, select, textarea');
            if (target) target.focus();
        }

        function clearStatus(node) {
            if (!node) return;
            node.hidden = true;
            node.textContent = '';
            node.removeAttribute('data-state');
        }

        function setStatus(node, message, state) {
            if (!node) return;
            node.hidden = !message;
            node.textContent = message || '';

            if (message && state) {
                node.setAttribute('data-state', state);
            } else {
                node.removeAttribute('data-state');
            }
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
            clearStatus(chooserStatus);
            clearStatus(requestStatus);
            clearStatus(verifyStatus);

            if (name !== 'mobile-verify') {
                clearStatus(debugStatus);
            }

            if (shouldFocus && activePanel) {
                focusPanel(activePanel);
            }
        }

        function setSubmitting(form, isSubmitting) {
            if (!form) return;

            form.classList.toggle('is-submitting', !!isSubmitting);

            Array.prototype.forEach.call(form.querySelectorAll('button'), function (field) {
                field.disabled = !!isSubmitting;
            });
        }

        function getAjaxUrl() {
            if (window.devhubLoginData && window.devhubLoginData.ajaxUrl) {
                return window.devhubLoginData.ajaxUrl;
            }

            if (window.devhubConfig && window.devhubConfig.ajaxUrl) {
                return window.devhubConfig.ajaxUrl;
            }

            return '';
        }

        function getMessage(key, fallback) {
            if (window.devhubLoginData && window.devhubLoginData.messages && window.devhubLoginData.messages[key]) {
                return window.devhubLoginData.messages[key];
            }

            return fallback;
        }

        function postForm(form) {
            var ajaxUrl = getAjaxUrl();

            if (!ajaxUrl) {
                return Promise.reject(new Error('Mobile OTP is not configured yet.'));
            }

            var payload = new FormData(form);

            return fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: payload
            }).then(function (response) {
                return response.json().catch(function () {
                    return {
                        success: false,
                        data: {
                            message: 'Unexpected server response.'
                        }
                    };
                });
            }).then(function (payload) {
                if (!payload || payload.success !== true) {
                    var message = payload && payload.data && payload.data.message ? payload.data.message : 'Request failed.';
                    throw new Error(message);
                }

                return payload.data || {};
            });
        }

        function syncVerifyPanel(data) {
            var maskedPhone = data && data.maskedPhone ? data.maskedPhone : currentMobilePhone;
            var message = data && data.message ? data.message : 'Enter the 6-digit code sent to your mobile number.';

            currentMobilePhone = data && data.phone ? data.phone : currentMobilePhone;

            if (verifyForm && verifyForm.elements.phone) {
                verifyForm.elements.phone.value = currentMobilePhone;
            }

            if (verifyCopy) {
                verifyCopy.textContent = message;
            }

            if (debugStatus) {
                if (data && data.debugOtp) {
                    setStatus(debugStatus, 'Local OTP: ' + data.debugOtp, 'success');
                } else {
                    clearStatus(debugStatus);
                }
            }

            if (maskedPhone && requestForm && requestForm.elements.phone) {
                requestForm.elements.phone.value = currentMobilePhone;
            }
        }

        function sendOtp(form, statusTarget) {
            clearStatus(statusTarget);
            clearStatus(verifyStatus);

            setSubmitting(form, true);

            return postForm(form).then(function (data) {
                syncVerifyPanel(data);
                setPanel('mobile-verify', true);
                if (data && data.debugOtp && verifyStatus) {
                    setStatus(verifyStatus, 'OTP sent. Use the local code shown below while this site is in development.', 'success');
                }

                return data;
            }).catch(function (error) {
                setStatus(statusTarget, error.message || getMessage('requestError', 'We could not send your OTP right now. Please try again.'), 'error');
                throw error;
            }).finally(function () {
                setSubmitting(form, false);
            });
        }

        function normalizeOtpInput(input) {
            if (!input) return;
            input.value = (input.value || '').replace(/\D+/g, '').slice(0, 6);
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
            if (!placeholderTrigger || !chooserStatus) return;

            event.preventDefault();
            setStatus(
                chooserStatus,
                placeholderTrigger.getAttribute('data-devhub-message') || 'This sign-in method will be connected later.',
                'error'
            );
        });

        if (requestForm) {
            requestForm.addEventListener('submit', function (event) {
                event.preventDefault();
                sendOtp(requestForm, requestStatus);
            });
        }

        if (verifyForm) {
            var otpInput = verifyForm.querySelector('input[name="otp"]');

            if (otpInput) {
                otpInput.addEventListener('input', function () {
                    normalizeOtpInput(otpInput);
                });
            }

            verifyForm.addEventListener('submit', function (event) {
                event.preventDefault();

                if (otpInput) {
                    normalizeOtpInput(otpInput);
                }

                clearStatus(verifyStatus);
                setSubmitting(verifyForm, true);

                postForm(verifyForm).then(function (data) {
                    var redirectUrl = data && data.redirect
                        ? data.redirect
                        : (verifyForm.elements.redirect && verifyForm.elements.redirect.value)
                            ? verifyForm.elements.redirect.value
                            : (window.devhubLoginData && window.devhubLoginData.redirectUrl)
                                ? window.devhubLoginData.redirectUrl
                                : window.location.href;

                    window.location.assign(redirectUrl);
                }).catch(function (error) {
                    setStatus(verifyStatus, error.message || getMessage('verifyError', 'We could not verify that OTP. Please try again.'), 'error');
                }).finally(function () {
                    setSubmitting(verifyForm, false);
                });
            });
        }

        var guestForm      = root.querySelector('[data-devhub-guest-form]');
        var guestStatus    = root.querySelector('[data-devhub-guest-status]');
        var billingSameChk = guestForm && guestForm.querySelector('[data-devhub-billing-same]');
        var billingFields  = guestForm && guestForm.querySelector('[data-devhub-billing-fields]');

        // Toggle billing address section
        function syncBillingFields() {
            if (!billingSameChk || !billingFields) return;
            var same = billingSameChk.checked;
            billingFields.hidden = same;
            billingFields.querySelectorAll('input').forEach(function (input) {
                input.required = !same;
            });
        }

        if (billingSameChk) {
            billingSameChk.addEventListener('change', syncBillingFields);
            syncBillingFields();
        }

        if (guestForm) {
            guestForm.addEventListener('submit', function (event) {
                event.preventDefault();

                var el = guestForm.elements;
                var val = function (name) { return el[name] ? el[name].value.trim() : ''; };
                var same = billingSameChk ? billingSameChk.checked : true;

                // Contact
                if (!val('full_name')) {
                    setStatus(guestStatus, 'Please enter your full name.', 'error');
                    el.full_name.focus();
                    return;
                }
                if (!val('phone') || !/^[0-9+\s\-()\u00B7]{7,20}$/.test(val('phone'))) {
                    setStatus(guestStatus, 'Please enter a valid mobile number.', 'error');
                    el.phone.focus();
                    return;
                }
                if (!val('email') || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val('email'))) {
                    setStatus(guestStatus, 'Please enter a valid email address.', 'error');
                    el.email.focus();
                    return;
                }

                // Billing address (always required)
                if (!val('billing_address_1')) {
                    setStatus(guestStatus, 'Please enter your billing address.', 'error');
                    el.billing_address_1.focus();
                    return;
                }
                if (!val('billing_city')) {
                    setStatus(guestStatus, 'Please enter your billing city.', 'error');
                    el.billing_city.focus();
                    return;
                }
                if (!val('billing_postcode')) {
                    setStatus(guestStatus, 'Please enter your billing postal code.', 'error');
                    el.billing_postcode.focus();
                    return;
                }

                // Shipping address (only when different from billing)
                if (!same) {
                    if (!val('shipping_address_1')) {
                        setStatus(guestStatus, 'Please enter your shipping address.', 'error');
                        el.shipping_address_1.focus();
                        return;
                    }
                    if (!val('shipping_city')) {
                        setStatus(guestStatus, 'Please enter your shipping city.', 'error');
                        el.shipping_city.focus();
                        return;
                    }
                    if (!val('shipping_postcode')) {
                        setStatus(guestStatus, 'Please enter your shipping postal code.', 'error');
                        el.shipping_postcode.focus();
                        return;
                    }
                }

                clearStatus(guestStatus);
                setSubmitting(guestForm, true);

                postForm(guestForm).then(function (data) {
                    var redirectUrl = data && data.redirect ? data.redirect : window.location.href;
                    window.location.assign(redirectUrl);
                }).catch(function (error) {
                    setStatus(guestStatus, error.message || 'Something went wrong. Please try again.', 'error');
                }).finally(function () {
                    setSubmitting(guestForm, false);
                });
            });
        }

        if (resendButton && requestForm) {
            resendButton.addEventListener('click', function (event) {
                event.preventDefault();

                if (requestForm.elements.phone && currentMobilePhone) {
                    requestForm.elements.phone.value = currentMobilePhone;
                }

                sendOtp(requestForm, verifyStatus);
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLoginPanels);
    } else {
        initLoginPanels();
    }
}());
