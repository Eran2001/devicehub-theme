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
	const ORDER_SUMMARY_SELECTOR = '.wc-block-checkout__sidebar .wp-block-woocommerce-checkout-order-summary-block';
	const CHECKOUT_SIDEBAR_SELECTOR = '.wc-block-checkout__sidebar';
	const ORDER_NOTE_PLACEHOLDER_SELECTOR = '.devhub-checkout-order-note-placeholder';
	const PAYMENT_STEP_SELECTOR = '.wp-block-woocommerce-checkout-payment-block';
	const PAYMENT_PLACEHOLDER_SELECTOR = '.devhub-checkout-payment-placeholder';
	const SIDEBAR_RELOCATION_CLASS = 'devhub-checkout--sidebar-relocation';
	const EMPTY_CHECKOUT_BUTTON_SELECTOR = '.wc-block-checkout-empty .wp-block-button__link';
	const COUPON_BUTTON_SELECTOR = '.wp-block-woocommerce-checkout-order-summary-coupon-form-block .wc-block-components-totals-coupon__button';
	const COUPON_INPUT_SELECTOR = '.wp-block-woocommerce-checkout-order-summary-coupon-form-block .wc-block-components-totals-coupon__input input';
	const COUPON_INPUT_LABEL_SELECTOR = '.wp-block-woocommerce-checkout-order-summary-coupon-form-block .wc-block-components-totals-coupon__input label';
	const CONTACT_EMAIL_INPUT_SELECTOR = '.wc-block-checkout__contact-fields .wc-block-components-text-input input[type="email"]';
	const CONTACT_EMAIL_LABEL_SELECTOR = '.wc-block-checkout__contact-fields .wc-block-components-text-input label';
	const ADDRESS_LINE_2_TOGGLE_SELECTOR = '.wc-block-components-address-form__address_2-toggle';
	const NATIVE_PICKUP_STEP_SELECTOR = '.wc-block-checkout__pickup-options';
	const NATIVE_PICKUP_OPTION_SELECTOR = '.wc-block-checkout__pickup-options .wc-block-components-radio-control__option';
	const NATIVE_PICKUP_INPUT_SELECTOR = '.wc-block-checkout__pickup-options input[type="radio"]';
	const DESKTOP_SIDEBAR_MEDIA = '(min-width: 782px)';

	const state = {};

	let root = null;
	let unsubscribe = null;
	let lastSignature = '';
	let hasBoundViewportListener = false;

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

	function normalizeText( value ) {
		return String( value ?? '' )
			.replace( /\s+/g, ' ' )
			.trim()
			.toLowerCase();
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

	function getNativePickupOptions() {
		return Array.from( document.querySelectorAll( NATIVE_PICKUP_OPTION_SELECTOR ) ).map( ( option ) => ( {
			option,
			input: option.querySelector( 'input[type="radio"]' ),
			text: normalizeText( option.textContent ),
		} ) );
	}

	function findLocationByNativeText( text ) {
		const normalizedText = normalizeText( text );

		return locations.find( ( location ) => {
			const name = normalizeText( location.name );
			const address = normalizeText( location.address );
			return (
				( name && normalizedText.includes( name ) ) ||
				( address && normalizedText.includes( address ) )
			);
		} ) || null;
	}

	function syncPickupStoreFromNativeSelection() {
		const additionalFields = getAdditionalFields();
		const method = additionalFields[ DELIVERY_FIELD ];
		const pickupStore = additionalFields[ PICKUP_FIELD ] || '';

		if ( method !== 'pickup' || pickupStore ) {
			return false;
		}

		const selectedOption = getNativePickupOptions().find( ( option ) => option.input?.checked );
		const matchedLocation = selectedOption ? findLocationByNativeText( selectedOption.text ) : null;

		if ( ! matchedLocation ) {
			return false;
		}

		patchAdditionalFields( {
			[ PICKUP_FIELD ]: matchedLocation.value,
		} );

		return true;
	}

	function syncNativePickupSelection( pickupStore ) {
		const selectedLocation = getLocationMap()[ pickupStore ] || null;

		if ( ! selectedLocation ) {
			return;
		}

		const targetOption = getNativePickupOptions().find( ( option ) => {
			const matchedLocation = findLocationByNativeText( option.text );
			return matchedLocation?.value === selectedLocation.value;
		} );

		if ( ! targetOption?.input || targetOption.input.checked ) {
			return;
		}

		targetOption.input.checked = true;
		targetOption.input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	function syncDefaults() {
		document.body.classList.add( 'devhub-checkout--custom-pickup' );

		if ( syncPickupStoreFromNativeSelection() ) {
			return false;
		}

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

		if ( additionalFields[ DELIVERY_FIELD ] === 'pickup' && additionalFields[ PICKUP_FIELD ] ) {
			syncNativePickupSelection( additionalFields[ PICKUP_FIELD ] );
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

	function isCheckoutProcessing() {
		return !! getCheckoutStore()?.isProcessing?.();
	}

	function syncProcessingState( isProcessing ) {
		if ( root ) {
			root.classList.toggle( 'wc-block-components-checkout-step--disabled', isProcessing );
			root.setAttribute( 'aria-disabled', isProcessing ? 'true' : 'false' );
		}

		const orderSummary = document.querySelector( ORDER_SUMMARY_SELECTOR );

		if ( ! orderSummary ) {
			return;
		}

		orderSummary.classList.toggle( 'devhub-checkout-processing', isProcessing );
		orderSummary.setAttribute( 'aria-disabled', isProcessing ? 'true' : 'false' );
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

	function enhanceEmptyCheckoutButton() {
		const button = document.querySelector( EMPTY_CHECKOUT_BUTTON_SELECTOR );

		if ( ! button ) {
			return;
		}

		button.closest( '.wp-block-button' )?.classList.add( 'btn--effect-six' );

		enhanceActionButton(
			button,
			'devhub-empty-checkout-button',
			'Browse store'
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

	function shouldUseCheckoutSidebar() {
		return typeof window.matchMedia !== 'function' || window.matchMedia( DESKTOP_SIDEBAR_MEDIA ).matches;
	}

	function isElementVisible( element ) {
		return !! ( element && ( element.offsetParent !== null || element.getClientRects().length ) );
	}

	function getVisibleOrderSummaryBlock() {
		const blocks = Array.from(
			document.querySelectorAll( '.wp-block-woocommerce-checkout-order-summary-block' )
		);

		return blocks.find( ( block ) => isElementVisible( block ) ) || blocks[ 0 ] || null;
	}

	function syncSidebarRelocationState() {
		if ( ! document.body ) {
			return;
		}

		document.body.classList.toggle(
			SIDEBAR_RELOCATION_CLASS,
			!! document.querySelector( '.wc-block-checkout, .wp-block-woocommerce-checkout' ) && shouldUseCheckoutSidebar()
		);
	}

	function findOrderNoteStep() {
		const candidates = Array.from(
			document.querySelectorAll(
				'.wc-block-components-checkout-step, .wp-block-woocommerce-checkout-order-note-block, .wc-block-checkout__additional-fields'
			)
		);

		return candidates.find( ( candidate ) => {
			if ( ! candidate || candidate === root ) {
				return false;
			}

			const headingText = normalizeText(
				candidate.querySelector( '.wc-block-components-checkout-step__title, .wc-block-components-checkbox__label' )?.textContent || ''
			);
			const textarea = candidate.querySelector( 'textarea' );
			const placeholderText = normalizeText( textarea?.getAttribute( 'placeholder' ) || '' );

			return (
				headingText.includes( 'add a note to your order' ) ||
				placeholderText.includes( 'notes about your order' )
			);
		} ) || null;
	}

	function ensureOrderNotePlaceholder( noteStep ) {
		if ( ! noteStep || ! noteStep.parentElement ) {
			return null;
		}

		let placeholder = document.querySelector( ORDER_NOTE_PLACEHOLDER_SELECTOR );

		if ( placeholder ) {
			return placeholder;
		}

		placeholder = document.createElement( 'div' );
		placeholder.className = 'devhub-checkout-order-note-placeholder';
		placeholder.hidden = true;
		noteStep.parentElement.insertBefore( placeholder, noteStep );

		return placeholder;
	}

	function ensurePaymentPlaceholder( paymentStep ) {
		if ( ! paymentStep || ! paymentStep.parentElement ) {
			return null;
		}

		let placeholder = document.querySelector( PAYMENT_PLACEHOLDER_SELECTOR );

		if ( placeholder ) {
			return placeholder;
		}

		placeholder = document.createElement( 'div' );
		placeholder.className = 'devhub-checkout-payment-placeholder';
		placeholder.hidden = true;
		paymentStep.parentElement.insertBefore( placeholder, paymentStep );

		return placeholder;
	}

	function moveOrderNoteStep() {
		const noteStep = findOrderNoteStep();
		if ( ! noteStep ) {
			return;
		}

		const placeholder = ensureOrderNotePlaceholder( noteStep );
		const orderSummary = getVisibleOrderSummaryBlock();
		const targetParent = orderSummary?.parentElement || null;

		noteStep.classList.add( 'devhub-checkout-order-note-step' );

		if ( orderSummary && targetParent ) {
			if ( noteStep.parentElement !== targetParent || noteStep.previousElementSibling !== orderSummary ) {
				orderSummary.insertAdjacentElement( 'afterend', noteStep );
			}
			return;
		}

		if ( placeholder?.parentElement && noteStep.previousElementSibling !== placeholder ) {
			placeholder.insertAdjacentElement( 'afterend', noteStep );
		}
	}

	function movePaymentStep() {
		const paymentStep = document.querySelector( PAYMENT_STEP_SELECTOR );
		if ( ! paymentStep ) {
			return;
		}

		const placeholder = ensurePaymentPlaceholder( paymentStep );
		const orderSummary = getVisibleOrderSummaryBlock();
		const noteStep = document.querySelector( '.devhub-checkout-order-note-step' );
		const targetParent = orderSummary?.parentElement || null;

		paymentStep.classList.add( 'devhub-checkout-payment-step' );

		if ( orderSummary && targetParent ) {
			const anchor = noteStep || orderSummary;

			if ( anchor && ( paymentStep.parentElement !== targetParent || paymentStep.previousElementSibling !== anchor ) ) {
				anchor.insertAdjacentElement( 'afterend', paymentStep );
			}
			return;
		}

		if ( placeholder?.parentElement && paymentStep.previousElementSibling !== placeholder ) {
			placeholder.insertAdjacentElement( 'afterend', paymentStep );
		}
	}

	function render() {
		syncSidebarRelocationState();

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
		const isProcessing = isCheckoutProcessing();
		const locationMap = getLocationMap();
		const selectedLocation = locationMap[ pickupStore ] || null;
		const signature = JSON.stringify( {
			method,
			pickupStore,
			locationCount: locations.length,
			isProcessing,
		} );

		if ( signature === lastSignature ) {
			syncProcessingState( isProcessing );
			return;
		}

		lastSignature = signature;
		setPrefersCollection( method );
		setValidationState( method, pickupStore );
		syncProcessingState( isProcessing );

		mountNode.innerHTML = `
			<div class="devhub-delivery-method__inner">
				<div class="devhub-delivery-method__header">
					<h2 class="devhub-delivery-method__title">${ escapeHtml( messages.title || 'Your Delivery Method' ) }</h2>
				</div>

				<div class="devhub-delivery-method__options" role="radiogroup" aria-label="${ escapeHtml( messages.title || 'Your Delivery Method' ) }">
					<button type="button" class="devhub-delivery-method__option ${ method === 'pickup' ? 'is-active' : '' } ${ ! locations.length || isProcessing ? 'is-disabled' : '' }" data-method="pickup" aria-pressed="${ method === 'pickup' }" ${ ! locations.length || isProcessing ? 'disabled' : '' }>
						<span class="devhub-delivery-method__option-title">${ escapeHtml( messages.pickupLabel || 'Pick Up at Store' ) }</span>
						<span class="devhub-delivery-method__option-copy">${ escapeHtml( messages.pickupHint || 'Collect from a Hutch service location.' ) }</span>
					</button>

					<button type="button" class="devhub-delivery-method__option ${ method === 'home_delivery' ? 'is-active' : '' } ${ isProcessing ? 'is-disabled' : '' }" data-method="home_delivery" aria-pressed="${ method === 'home_delivery' }" ${ isProcessing ? 'disabled' : '' }>
						<span class="devhub-delivery-method__option-title">${ escapeHtml( messages.deliveryLabel || 'Home Delivery' ) }</span>
						<span class="devhub-delivery-method__option-copy">${ escapeHtml( messages.deliveryHint || 'Delivery via courier to the billing address.' ) }</span>
					</button>
				</div>

				<div class="devhub-delivery-method__pickup ${ method === 'pickup' ? '' : 'is-hidden' }">
					<h3 class="devhub-delivery-method__pickup-title">${ escapeHtml( messages.pickupTitle || 'Pick up at store' ) }</h3>
					<p class="devhub-delivery-method__pickup-copy">${ escapeHtml( messages.pickupSubtitle || 'Select the Hutch location for collection.' ) }</p>

					<div class="devhub-delivery-method__store-list">
						${ ! locations.length ? `<p class="devhub-delivery-method__empty">${ escapeHtml( messages.pickupUnavailable || 'Pickup is currently unavailable.' ) }</p>` : '' }
						${ locations.map( ( location ) => `
							<button type="button" class="devhub-delivery-method__store ${ pickupStore === location.value ? 'is-active' : '' } ${ isProcessing ? 'is-disabled' : '' }" data-store="${ escapeHtml( location.value ) }" aria-pressed="${ pickupStore === location.value }" ${ isProcessing ? 'disabled' : '' }>
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
				if ( button.disabled ) {
					return;
				}

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
				if ( button.disabled ) {
					return;
				}

				const nextStore = button.getAttribute( 'data-store' ) || '';

				patchAdditionalFields( {
					[ PICKUP_FIELD ]: nextStore,
				} );
				syncNativePickupSelection( nextStore );
			} );
		} );

		enhancePlaceOrderButton();
		enhanceCouponButton();
		enhanceEmptyCheckoutButton();
		enhanceCouponInput();
		enhanceContactInput();
		expandAddressLineTwo();
		moveOrderNoteStep();
		movePaymentStep();
	}

	function relabelAddressBlocks() {
		// Shipping-fields block is always visible and shown first → call it "Billing address"
		const shippingTitle = document.querySelector(
			'.wc-block-checkout__shipping-fields .wc-block-components-checkout-step__title'
		);
		if ( shippingTitle && shippingTitle.textContent.trim() !== 'Billing address' ) {
			shippingTitle.textContent = 'Billing address';
		}

		// Billing-address block appears when addresses differ → call it "Shipping address"
		const billingTitle = document.querySelector(
			'.wc-block-checkout__billing-address .wc-block-components-checkout-step__title, ' +
			'.wp-block-woocommerce-checkout-billing-address-block .wc-block-components-checkout-step__title'
		);
		if ( billingTitle && billingTitle.textContent.trim() !== 'Shipping address' ) {
			billingTitle.textContent = 'Shipping address';
		}

		// Change checkbox label from "Use same address for billing" → "Use same address for shipping"
		document
			.querySelectorAll( '.wc-block-checkout__shipping-fields .wc-block-components-checkbox__label' )
			.forEach( ( label ) => {
				if ( /billing/i.test( label.textContent ) ) {
					label.textContent = label.textContent.replace( /billing/gi, 'shipping' );
				}
			} );
	}

	function boot() {
		if ( ! document.querySelector( '.wc-block-checkout, .wp-block-woocommerce-checkout' ) ) {
			return;
		}

		if ( ! window.wp?.data || ! window.wc?.wcBlocksData ) {
			window.setTimeout( boot, 150 );
			return;
		}

		syncSidebarRelocationState();
		render();
		enhancePlaceOrderButton();
		enhanceCouponButton();
		enhanceCouponInput();
		enhanceContactInput();
		expandAddressLineTwo();
		relabelAddressBlocks();
		moveOrderNoteStep();
		movePaymentStep();

		if ( ! hasBoundViewportListener ) {
			hasBoundViewportListener = true;
			window.addEventListener( 'resize', () => {
				syncSidebarRelocationState();
				moveOrderNoteStep();
				movePaymentStep();
			}, { passive: true } );
		}

		if ( unsubscribe ) {
			return;
		}

		unsubscribe = window.wp.data.subscribe( () => {
			render();
			enhancePlaceOrderButton();
			enhanceCouponButton();
			enhanceEmptyCheckoutButton();
			enhanceCouponInput();
			enhanceContactInput();
			expandAddressLineTwo();
			relabelAddressBlocks();
			moveOrderNoteStep();
			movePaymentStep();
		} );
	}

	syncSidebarRelocationState();

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
