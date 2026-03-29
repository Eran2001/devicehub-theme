/**
 * DeviceHub — API Utility
 *
 * devhubConfig is injected by wp_localize_script (inc/enqueue.php):
 *   devhubConfig.ajaxUrl   — wp-admin/admin-ajax.php
 *   devhubConfig.restUrl   — WC REST API v3 base URL
 *   devhubConfig.nonce     — WC REST nonce
 *   devhubConfig.cartUrl
 *   devhubConfig.isLoggedIn
 *
 * This file must exist for the devhub-utils handle to register,
 * which other modules (flash-countdown, cart, etc.) depend on.
 */
