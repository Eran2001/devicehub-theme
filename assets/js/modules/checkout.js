( function () {
	'use strict';

	const config = window.devhubCheckoutData || {};
	const fields = config.fields || {};
	const locations = Array.isArray( config.pickupLocations ) ? config.pickupLocations : [];
	const messages = config.messages || {};

	const DELIVERY_FIELD = fields.deliveryMethod || 'devicehub/delivery_method';
	const PICKUP_FIELD = fields.pickupStore || 'devicehub/pickup_store';
	const CHECKOUT_STORE_KEY = window.wc?.wcBlocksData?.CHECKOUT_STORE_KEY || 'wc/store/checkout';
	const VALIDATION_STORE_KEY = window.wc?.wcBlocksData?.VALIDATION_STORE_KEY || 'wc/store/validation';
	const DELIVERY_ERROR_KEY = 'devhub-pickup-store';
	const PLACE_ORDER_SELECTOR = '.wc-block-components-checkout-place-order-button';
	const COUPON_BUTTON_SELECTOR = '.wp-block-woocommerce-checkout-order-summary-coupon-form-block .wc-block-components-totals-coupon__button';
	const COUPON_INPUT_SELECTOR = '.wp-block-woocommerce-checkout-order-summary-coupon-form-block .wc-block-components-totals-coupon__input input';
	const COUPON_INPUT_LABEL_SELECTOR = '.wp-block-woocommerce-checkout-order-summary-coupon-form-block .wc-block-components-totals-coupon__input label';
	const CONTACT_EMAIL_INPUT_SELECTOR = '.wc-block-checkout__contact-fields .wc-block-components-text-input input[type="email"]';
	const CONTACT_EMAIL_LABEL_SELECTOR = '.wc-block-checkout__contact-fields .wc-block-components-text-input label';
	const ADDRESS_LINE_2_TOGGLE_SELECTOR = '.wc-block-components-address-form__address_2-toggle';

	const state = {
		search: '',
	};

	let root = null;
	let unsubscribe = null;
	let lastSignature = '';

	function getCheckoutStore() {
		return window.wp?.data?.select?.( CHECKOUT_STORE_KEY ) || null;
	}

	function getCheckoutDispatch() {
		return window.wp?.data?.dispatch?.( CHECKOUT_STORE_KEY ) || null;
	}

	function getValidationDispatch() {
		return window.wp?.data?.dispatch?.( VALIDATION_STORE_KEY ) || null;
	}

	function getAdditionalFields() {
		return getCheckoutStore()?.getAdditionalFields?.() || {};
	}

	function patchAdditionalFields( patch ) {
		const dispatch = getCheckoutDispatch();
		if ( ! dispatch?.setAdditionalFields ) {
			return;
		}

		dispatch.setAdditionalFields( {
			...getAdditionalFields(),
			...patch,
		} );
	}

	function setPrefersCollection( method ) {
		const dispatch = getCheckoutDispatch();
		if ( dispatch?.setPrefersCollection ) {
			dispatch.setPrefersCollection( method === 'pickup' );
		}
	}

	function escapeHtml( value ) {
		return String( value ?? '' )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	function isValidMethod( method ) {
		return method === 'home_delivery' || method === 'pickup';
	}

	function getLocationMap() {
		return locations.reduce( ( carry, location ) => {
			carry[ location.value ] = location;
			return carry;
		}, {} );
	}

	function syncDefaults() {
		const additionalFields = getAdditionalFields();
		const patch = {};
		const currentMethod = additionalFields[ DELIVERY_FIELD ];

		if ( ! isValidMethod( currentMethod ) ) {
			patch[ DELIVERY_FIELD ] = locations.length ? 'home_delivery' : 'home_delivery';
		}

		if ( additionalFields[ DELIVERY_FIELD ] === 'pickup' && ! locations.length ) {
			patch[ DELIVERY_FIELD ] = 'home_delivery';
		}

		if ( Object.keys( patch ).length ) {
			patchAdditionalFields( patch );
			return false;
		}

		return true;
	}

	function ensureRoot() {
		const contactStep = document.querySelector( '.wc-block-checkout__contact-fields' );

		if ( ! contactStep ) {
			return null;
		}

		if ( root && root.isConnected ) {
			return root;
		}

		root = document.createElement( 'section' );
		root.className = 'devhub-delivery-method';
		contactStep.insertAdjacentElement( 'afterend', root );

		return root;
	}

	function setValidationState( method, pickupStore ) {
		const validation = getValidationDispatch();

		if ( ! validation?.setValidationErrors || ! validation?.clearValidationError ) {
			return;
		}

		if ( method === 'pickup' && ! pickupStore ) {
			validation.setValidationErrors( {
				[ DELIVERY_ERROR_KEY ]: {
					message: messages.pickupRequired || 'Please select a pickup store to continue.',
					hidden: false,
				},
			} );
			return;
		}

		validation.clearValidationError( DELIVERY_ERROR_KEY );
	}

	function bindEffectSixButton( button ) {
		if ( ! button || button.dataset.devhubEffectSixBound === 'true' ) {
			return;
		}

		const getOriginalHtml = () => button.dataset.devhubOriginalHtml || button.innerHTML;
		const isDisabled = () => button.disabled || button.getAttribute( 'aria-disabled' ) === 'true';

		button.dataset.devhubEffectSixBound = 'true';

		button.addEventListener( 'mouseover', () => {
			const originalHTML = getOriginalHtml();

			if (
				! originalHTML ||
				isDisabled() ||
				button.classList.contains( 'animating' ) ||
				button.classList.contains( 'mouseover' )
			) {
				return;
			}

			button.classList.add( 'animating', 'mouseover' );

			const tempDiv = document.createElement( 'div' );
			tempDiv.innerHTML = originalHTML;

			const chars = Array.from( tempDiv.childNodes );
			window.setTimeout( () => button.classList.remove( 'animating' ), ( chars.length + 1 ) * 50 );

			const animationType = button.dataset.animation || 'text-spin';
			button.innerHTML = '';

			chars.forEach( ( node ) => {
				if ( node.nodeType === Node.TEXT_NODE ) {
					node.textContent.split( '' ).forEach( ( char ) => {
						button.innerHTML += `<span class="letter">${ char === ' ' ? '&nbsp;' : char }</span>`;
					} );
					return;
				}

				button.innerHTML += `<span class="letter">${ node.outerHTML }</span>`;
			} );

			button.querySelectorAll( '.letter' ).forEach( ( span, index ) => {
				window.setTimeout( () => span.classList.add( animationType ), 50 * index );
			} );
		} );

		button.addEventListener( 'mouseout', () => {
			button.classList.remove( 'mouseover' );
			button.innerHTML = getOriginalHtml();
		} );
	}

	function enhanceActionButton( button, customClass, fallbackText ) {
		if ( ! button ) {
			return;
		}

		button.classList.add( 'wf-btn', 'wf-btn-primary', customClass );

		const text = ( button.textContent || button.getAttribute( 'aria-label' ) || fallbackText ).trim();
		const desiredHtml = `${ text }<i class="fas fa-arrow-right" aria-hidden="true"></i>`;

		if ( button.dataset.devhubOriginalHtml !== desiredHtml ) {
			button.dataset.devhubOriginalHtml = desiredHtml;
		}

		if (
			! button.classList.contains( 'mouseover' ) &&
			! button.className.includes( '--loading' ) &&
			button.innerHTML !== desiredHtml
		) {
			button.innerHTML = desiredHtml;
		}

		bindEffectSixButton( button );
	}

	function enhancePlaceOrderButton() {
		enhanceActionButton(
			document.querySelector( PLACE_ORDER_SELECTOR ),
			'devhub-checkout-place-order-button',
			'Place Order'
		);
	}

	function enhanceCouponButton() {
		enhanceActionButton(
			document.querySelector( COUPON_BUTTON_SELECTOR ),
			'devhub-checkout-coupon-button',
			'Apply'
		);
	}

	function enhanceCouponInput() {
		const input = document.querySelector( COUPON_INPUT_SELECTOR );
		const label = document.querySelector( COUPON_INPUT_LABEL_SELECTOR );

		if ( ! input ) {
			return;
		}

		input.placeholder = 'Enter code';

		if ( label ) {
			label.textContent = 'Coupon code';
		}
	}

	function enhanceContactInput() {
		const input = document.querySelector( CONTACT_EMAIL_INPUT_SELECTOR );
		const label = document.querySelector( CONTACT_EMAIL_LABEL_SELECTOR );

		if ( ! input ) {
			return;
		}

		input.placeholder = 'Enter email address';

		if ( label ) {
			label.textContent = 'Email address';
		}
	}

	function expandAddressLineTwo() {
		document.querySelectorAll( ADDRESS_LINE_2_TOGGLE_SELECTOR ).forEach( ( toggle ) => {
			if ( toggle instanceof HTMLElement ) {
				toggle.click();
			}
		} );
	}

	function render() {
		if ( ! syncDefaults() ) {
			return;
		}

		const mountNode = ensureRoot();
		if ( ! mountNode ) {
			return;
		}

		const additionalFields = getAdditionalFields();
		const method = isValidMethod( additionalFields[ DELIVERY_FIELD ] ) ? additionalFields[ DELIVERY_FIELD ] : 'home_delivery';
		const pickupStore = additionalFields[ PICKUP_FIELD ] || '';
		const locationMap = getLocationMap();
		const selectedLocation = locationMap[ pickupStore ] || null;
		const search = state.search.trim().toLowerCase();
		const filteredLocations = locations.filter( ( location ) => {
			if ( ! search ) {
				return true;
			}

			const haystack = [
				location.name,
				location.address,
				location.details,
			]
				.join( ' ' )
				.toLowerCase();

			return haystack.includes( search );
		} );

		const signature = JSON.stringify( {
			method,
			pickupStore,
			search,
			locationCount: locations.length,
		} );

		if ( signature === lastSignature ) {
			return;
		}

		lastSignature = signature;
		setPrefersCollection( method );
		setValidationState( method, pickupStore );

		mountNode.innerHTML = `
			<div class="devhub-delivery-method__inner">
				<div class="devhub-delivery-method__header">
					<h2 class="devhub-delivery-method__title">${ escapeHtml( messages.title || 'Your Delivery Method' ) }</h2>
				</div>

				<div class="devhub-delivery-method__options" role="radiogroup" aria-label="${ escapeHtml( messages.title || 'Your Delivery Method' ) }">
					<button type="button" class="devhub-delivery-method__option ${ method === 'pickup' ? 'is-active' : '' } ${ ! locations.length ? 'is-disabled' : '' }" data-method="pickup" aria-pressed="${ method === 'pickup' }" ${ ! locations.length ? 'disabled' : '' }>
						<span class="devhub-delivery-method__option-title">${ escapeHtml( messages.pickupLabel || 'Pick Up at Store' ) }</span>
						<span class="devhub-delivery-method__option-copy">${ escapeHtml( messages.pickupHint || 'Collect from a Hutch service location.' ) }</span>
					</button>

					<button type="button" class="devhub-delivery-method__option ${ method === 'home_delivery' ? 'is-active' : '' }" data-method="home_delivery" aria-pressed="${ method === 'home_delivery' }">
						<span class="devhub-delivery-method__option-title">${ escapeHtml( messages.deliveryLabel || 'Home Delivery' ) }</span>
						<span class="devhub-delivery-method__option-copy">${ escapeHtml( messages.deliveryHint || 'Delivery via courier to the billing address.' ) }</span>
					</button>
				</div>

				<div class="devhub-delivery-method__pickup ${ method === 'pickup' ? '' : 'is-hidden' }">
					<h3 class="devhub-delivery-method__pickup-title">${ escapeHtml( messages.pickupTitle || 'Pick up at store' ) }</h3>
					<p class="devhub-delivery-method__pickup-copy">${ escapeHtml( messages.pickupSubtitle || 'Select the Hutch location for collection.' ) }</p>

					<div class="devhub-delivery-method__search">
						<input
							type="search"
							class="devhub-delivery-method__search-input"
							placeholder="${ escapeHtml( messages.searchPlaceholder || 'Search stores' ) }"
							value="${ escapeHtml( state.search ) }"
							autocomplete="off"
						/>
						<p class="devhub-delivery-method__search-copy">${ escapeHtml( messages.searchHelp || 'Search for your nearest Hutch store.' ) }</p>
					</div>

					<div class="devhub-delivery-method__store-list">
						${ ! locations.length ? `<p class="devhub-delivery-method__empty">${ escapeHtml( messages.pickupUnavailable || 'Pickup is currently unavailable.' ) }</p>` : '' }
						${ locations.length && ! filteredLocations.length ? `<p class="devhub-delivery-method__empty">${ escapeHtml( messages.emptySearch || 'No stores match your search.' ) }</p>` : '' }
						${ filteredLocations.map( ( location ) => `
							<button type="button" class="devhub-delivery-method__store ${ pickupStore === location.value ? 'is-active' : '' }" data-store="${ escapeHtml( location.value ) }" aria-pressed="${ pickupStore === location.value }">
								<span class="devhub-delivery-method__store-indicator" aria-hidden="true"></span>
								<span class="devhub-delivery-method__store-content">
									<span class="devhub-delivery-method__store-name">${ escapeHtml( location.name ) }</span>
									${ location.address ? `<span class="devhub-delivery-method__store-address">${ escapeHtml( location.address ) }</span>` : '' }
									${ location.details ? `<span class="devhub-delivery-method__store-details">${ escapeHtml( location.details ) }</span>` : '' }
								</span>
							</button>
						` ).join( '' ) }
					</div>

					${ method === 'pickup' && ! pickupStore ? `<p class="devhub-delivery-method__error">${ escapeHtml( messages.pickupRequired || 'Please select a pickup store to continue.' ) }</p>` : '' }
					${ method === 'pickup' && selectedLocation ? `<p class="devhub-delivery-method__summary">${ escapeHtml( selectedLocation.name ) }</p>` : '' }
				</div>
			</div>
		`;

		mountNode.querySelectorAll( '[data-method]' ).forEach( ( button ) => {
			button.addEventListener( 'click', () => {
				const nextMethod = button.getAttribute( 'data-method' );

				if ( ! isValidMethod( nextMethod ) ) {
					return;
				}

				patchAdditionalFields( {
					[ DELIVERY_FIELD ]: nextMethod,
				} );
			} );
		} );

		mountNode.querySelectorAll( '[data-store]' ).forEach( ( button ) => {
			button.addEventListener( 'click', () => {
				const nextStore = button.getAttribute( 'data-store' ) || '';

				patchAdditionalFields( {
					[ PICKUP_FIELD ]: nextStore,
				} );
			} );
		} );

		const searchInput = mountNode.querySelector( '.devhub-delivery-method__search-input' );
		if ( searchInput ) {
			searchInput.addEventListener( 'input', ( event ) => {
				state.search = event.target.value || '';
				lastSignature = '';
				render();
			} );
		}

		enhancePlaceOrderButton();
		enhanceCouponButton();
		enhanceCouponInput();
		enhanceContactInput();
		expandAddressLineTwo();
	}

	function boot() {
		if ( ! document.querySelector( '.wc-block-checkout, .wp-block-woocommerce-checkout' ) ) {
			return;
		}

		if ( ! window.wp?.data || ! window.wc?.wcBlocksData ) {
			window.setTimeout( boot, 150 );
			return;
		}

		render();
		enhancePlaceOrderButton();
		enhanceCouponButton();
		enhanceCouponInput();
		enhanceContactInput();
		expandAddressLineTwo();

		if ( unsubscribe ) {
			return;
		}

		unsubscribe = window.wp.data.subscribe( () => {
			render();
			enhancePlaceOrderButton();
			enhanceCouponButton();
			enhanceCouponInput();
			enhanceContactInput();
			expandAddressLineTwo();
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
