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
	const FALLBACK_DELIVERY_STEP_SELECTOR = '.devhub-checkout-delivery-fallback';
	const SIDEBAR_RELOCATION_CLASS = 'devhub-checkout--sidebar-relocation';
	const EMPTY_CHECKOUT_BUTTON_SELECTOR = '.wc-block-checkout-empty .wp-block-button__link';
	const COUPON_BUTTON_SELECTOR = '.wp-block-woocommerce-checkout-order-summary-coupon-form-block .wc-block-components-totals-coupon__button';
	const COUPON_INPUT_SELECTOR = '.wp-block-woocommerce-checkout-order-summary-coupon-form-block .wc-block-components-totals-coupon__input input';
	const COUPON_INPUT_LABEL_SELECTOR = '.wp-block-woocommerce-checkout-order-summary-coupon-form-block .wc-block-components-totals-coupon__input label';
	const CONTACT_EMAIL_INPUT_SELECTOR = '.wc-block-checkout__contact-fields .wc-block-components-text-input input[type="email"]';
	const CONTACT_EMAIL_LABEL_SELECTOR = '.wc-block-checkout__contact-fields .wc-block-components-text-input label';
	const ADDRESS_LINE_2_TOGGLE_SELECTOR = '.wc-block-components-address-form__address_2-toggle';
	const NATIVE_DELIVERY_STEP_SELECTOR = '.wc-block-checkout__shipping-method, #shipping-method';
	const NATIVE_DELIVERY_OPTION_SELECTOR = `${ NATIVE_DELIVERY_STEP_SELECTOR } .wc-block-components-radio-control__option`;
	const NATIVE_DELIVERY_CARD_SELECTOR = `${ NATIVE_DELIVERY_STEP_SELECTOR } .wc-block-checkout__shipping-method-option`;
	const NATIVE_PICKUP_STEP_SELECTOR = '.wc-block-checkout__pickup-options';
	const NATIVE_PICKUP_OPTION_SELECTOR = '.wc-block-checkout__pickup-options .wc-block-components-radio-control__option';
	const NATIVE_PICKUP_INPUT_SELECTOR = '.wc-block-checkout__pickup-options input[type="radio"]';
	const DESKTOP_SIDEBAR_MEDIA = '(min-width: 782px)';

	const state = {};

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

	function getNativeDeliveryOptions() {
		const cardOptions = Array.from( document.querySelectorAll( NATIVE_DELIVERY_CARD_SELECTOR ) );

		if ( cardOptions.length ) {
			return cardOptions.map( ( option ) => ( {
				option,
				input: null,
				text: normalizeText( option.textContent ),
				selected:
					option.classList.contains( 'wc-block-checkout__shipping-method-option--selected' ) ||
					option.getAttribute( 'aria-checked' ) === 'true' ||
					option.getAttribute( 'aria-pressed' ) === 'true',
			} ) );
		}

		return Array.from( document.querySelectorAll( NATIVE_DELIVERY_OPTION_SELECTOR ) ).map( ( option ) => ( {
			option,
			input: option.querySelector( 'input[type="radio"]' ),
			text: normalizeText( option.textContent ),
			selected: !! option.querySelector( 'input[type="radio"]:checked' ),
		} ) );
	}

	function getMethodFromNativeOption( option ) {
		const text = normalizeText( option?.text );

		if ( ! text ) {
			return '';
		}

		if ( text.includes( 'pickup' ) || text.includes( 'collect' ) ) {
			return 'pickup';
		}

		if ( text.includes( 'ship' ) || text.includes( 'delivery' ) || text.includes( 'home' ) ) {
			return 'home_delivery';
		}

		return '';
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

	function syncNativeDeliverySelection( method ) {
		const targetOption = getNativeDeliveryOptions().find(
			( option ) => getMethodFromNativeOption( option ) === method
		);

		if ( ! targetOption?.input || targetOption.input.checked ) {
			return;
		}

		targetOption.input.checked = true;
		targetOption.input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	function getSelectedNativeDeliveryMethod() {
		const selectedOption = getNativeDeliveryOptions().find(
			( option ) => option.input?.checked || option.selected
		);

		return selectedOption ? getMethodFromNativeOption( selectedOption ) : '';
	}

	function getOrderSummaryDeliveryLabel( method, pickupStore ) {
		if ( method === 'pickup' ) {
			const selectedLocation = getLocationMap()[ pickupStore ] || null;

			if ( selectedLocation?.name ) {
				return `Pickup (${ selectedLocation.name })`;
			}

			return 'Store Pickup';
		}

		return 'Home Delivery';
	}

	function syncOrderSummaryDeliveryLabel( method, pickupStore ) {
		if ( document.querySelector( NATIVE_DELIVERY_STEP_SELECTOR ) ) {
			return;
		}

		const orderSummary = document.querySelector( ORDER_SUMMARY_SELECTOR );

		if ( ! orderSummary ) {
			return;
		}

		const targetLabel = getOrderSummaryDeliveryLabel( method, pickupStore );
		const candidates = orderSummary.querySelectorAll(
			'.wc-block-components-totals-item__label, .wc-block-components-totals-shipping__via'
		);

		Array.from( candidates ).forEach( ( candidate ) => {
			const text = normalizeText( candidate.textContent );

			if (
				! text ||
				( ! text.includes( 'pickup' ) &&
					! text.includes( 'shipping' ) &&
					! text.includes( 'delivery' ) &&
					! text.includes( 'ship' ) &&
					! text.includes( 'collect' ) )
			) {
				return;
			}

			candidate.textContent = targetLabel;
		} );
	}

	function ensureFallbackDeliveryStep() {
		const contactStep = document.querySelector( '.wc-block-checkout__contact-fields' );

		if ( ! contactStep ) {
			return null;
		}

		let fallbackStep = document.querySelector( FALLBACK_DELIVERY_STEP_SELECTOR );

		if ( fallbackStep ) {
			return fallbackStep;
		}

		fallbackStep = document.createElement( 'section' );
		fallbackStep.className = 'wc-block-components-checkout-step devhub-checkout-delivery-fallback';
		contactStep.insertAdjacentElement( 'afterend', fallbackStep );

		return fallbackStep;
	}

	function renderFallbackDeliveryStep( method, isProcessing ) {
		const nativeStepExists = !! document.querySelector( NATIVE_DELIVERY_STEP_SELECTOR );
		const fallbackStep = document.querySelector( FALLBACK_DELIVERY_STEP_SELECTOR ) || ensureFallbackDeliveryStep();

		if ( ! fallbackStep ) {
			return;
		}

		if ( nativeStepExists ) {
			fallbackStep.hidden = true;
			fallbackStep.innerHTML = '';
			return;
		}

		fallbackStep.hidden = false;
		fallbackStep.innerHTML = `
			<div class="wc-block-components-checkout-step__container">
				<div class="wc-block-components-checkout-step__heading">
					<h2 class="wc-block-components-checkout-step__title">Delivery</h2>
				</div>
				<div class="wc-block-components-radio-control wc-block-components-radio-control--highlight-checked devhub-checkout-delivery-fallback__options">
					<label class="wc-block-components-radio-control__option ${ method === 'home_delivery' ? 'wc-block-components-radio-control__option--checked-option-highlighted' : '' }">
						<input type="radio" name="devhub-fallback-delivery" value="home_delivery" ${ method === 'home_delivery' ? 'checked' : '' } ${ isProcessing ? 'disabled' : '' } />
						<span class="wc-block-components-radio-control__label-group devhub-checkout-delivery-fallback__label-group">
							<span class="devhub-checkout-delivery-fallback__icon" aria-hidden="true">
								<i class="fas fa-house"></i>
							</span>
							<span class="wc-block-components-radio-control__label">Home Delivery</span>
						</span>
					</label>
					<label class="wc-block-components-radio-control__option ${ method === 'pickup' ? 'wc-block-components-radio-control__option--checked-option-highlighted' : '' }">
						<input type="radio" name="devhub-fallback-delivery" value="pickup" ${ method === 'pickup' ? 'checked' : '' } ${ isProcessing || ! locations.length ? 'disabled' : '' } />
						<span class="wc-block-components-radio-control__label-group devhub-checkout-delivery-fallback__label-group">
							<span class="devhub-checkout-delivery-fallback__icon" aria-hidden="true">
								<i class="fas fa-truck"></i>
							</span>
							<span class="wc-block-components-radio-control__label">Store Pickup</span>
						</span>
					</label>
				</div>
			</div>
		`;

		fallbackStep.querySelectorAll( 'input[name="devhub-fallback-delivery"]' ).forEach( ( input ) => {
			input.addEventListener( 'change', () => {
				if ( ! input.checked ) {
					return;
				}

				const nextMethod = input.value;

				if ( ! isValidMethod( nextMethod ) || getAdditionalFields()[ DELIVERY_FIELD ] === nextMethod ) {
					return;
				}

				setPrefersCollection( nextMethod );
				patchAdditionalFields( {
					[ DELIVERY_FIELD ]: nextMethod,
					[ PICKUP_FIELD ]: nextMethod === 'pickup' ? getAdditionalFields()[ PICKUP_FIELD ] || '' : '',
				} );
			} );
		} );
	}

	function bindNativeDeliveryListeners() {
		getNativeDeliveryOptions().forEach( ( option ) => {
			if ( option.input ) {
				if ( option.input.dataset.devhubDeliveryBound === 'true' ) {
					return;
				}

				option.input.dataset.devhubDeliveryBound = 'true';
				option.input.addEventListener( 'change', () => {
					if ( ! option.input?.checked ) {
						return;
					}

					const nextMethod = getMethodFromNativeOption( option );

					if ( ! isValidMethod( nextMethod ) || getAdditionalFields()[ DELIVERY_FIELD ] === nextMethod ) {
						return;
					}

					patchAdditionalFields( {
						[ DELIVERY_FIELD ]: nextMethod,
						[ PICKUP_FIELD ]: nextMethod === 'pickup' ? getAdditionalFields()[ PICKUP_FIELD ] || '' : '',
					} );
				} );
				return;
			}

			if ( ! option.option || option.option.dataset.devhubDeliveryBound === 'true' ) {
				return;
			}

			option.option.dataset.devhubDeliveryBound = 'true';
			option.option.addEventListener( 'click', () => {
				const nextMethod = getMethodFromNativeOption( option );

				if ( ! isValidMethod( nextMethod ) ) {
					return;
				}

				window.requestAnimationFrame( () => {
					const currentFields = getAdditionalFields();

					if ( currentFields[ DELIVERY_FIELD ] === nextMethod && ( nextMethod === 'pickup' || ! currentFields[ PICKUP_FIELD ] ) ) {
						return;
					}

					patchAdditionalFields( {
						[ DELIVERY_FIELD ]: nextMethod,
						[ PICKUP_FIELD ]: nextMethod === 'pickup' ? currentFields[ PICKUP_FIELD ] || '' : '',
					} );
				} );
			} );
		} );
	}

	function bindNativePickupListeners() {
		getNativePickupOptions().forEach( ( option ) => {
			if ( ! option.input || option.input.dataset.devhubPickupBound === 'true' ) {
				return;
			}

			option.input.dataset.devhubPickupBound = 'true';
			option.input.addEventListener( 'change', () => {
				if ( ! option.input?.checked ) {
					return;
				}

				const matchedLocation = findLocationByNativeText( option.text );
				const currentFields = getAdditionalFields();
				const nextPatch = {
					[ DELIVERY_FIELD ]: 'pickup',
				};

				if ( matchedLocation && currentFields[ PICKUP_FIELD ] !== matchedLocation.value ) {
					nextPatch[ PICKUP_FIELD ] = matchedLocation.value;
				}

				if (
					currentFields[ DELIVERY_FIELD ] === nextPatch[ DELIVERY_FIELD ] &&
					!( PICKUP_FIELD in nextPatch )
				) {
					return;
				}

				patchAdditionalFields( nextPatch );
			} );
		} );
	}

	function syncDefaults() {
		const additionalFields = getAdditionalFields();
		const patch = {};
		const currentMethod = additionalFields[ DELIVERY_FIELD ];
		const nativeStepExists = !! document.querySelector( NATIVE_DELIVERY_STEP_SELECTOR );

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

		const nativeMethod = getSelectedNativeDeliveryMethod();

		if ( nativeStepExists ) {
			if ( isValidMethod( nativeMethod ) && nativeMethod !== additionalFields[ DELIVERY_FIELD ] ) {
				patchAdditionalFields( {
					[ DELIVERY_FIELD ]: nativeMethod,
					[ PICKUP_FIELD ]: nativeMethod === 'pickup' ? additionalFields[ PICKUP_FIELD ] || '' : '',
				} );
				return false;
			}
		} else {
			setPrefersCollection( additionalFields[ DELIVERY_FIELD ] );
			syncNativeDeliverySelection( additionalFields[ DELIVERY_FIELD ] );
		}

		if ( additionalFields[ DELIVERY_FIELD ] === 'pickup' && additionalFields[ PICKUP_FIELD ] ) {
			syncNativePickupSelection( additionalFields[ PICKUP_FIELD ] );
		}

		if ( syncPickupStoreFromNativeSelection() ) {
			return false;
		}

		return true;
	}

	function isCheckoutProcessing() {
		return !! getCheckoutStore()?.isProcessing?.();
	}

	function syncProcessingState( isProcessing ) {
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
			if ( ! candidate ) {
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

		const additionalFields = getAdditionalFields();
		const method = isValidMethod( additionalFields[ DELIVERY_FIELD ] ) ? additionalFields[ DELIVERY_FIELD ] : 'home_delivery';
		const pickupStore = additionalFields[ PICKUP_FIELD ] || '';
		const isProcessing = isCheckoutProcessing();

		bindNativeDeliveryListeners();
		bindNativePickupListeners();

		const signature = JSON.stringify( {
			method,
			pickupStore,
			locationCount: locations.length,
			isProcessing,
		} );

		if ( signature === lastSignature ) {
			syncProcessingState( isProcessing );
			syncOrderSummaryDeliveryLabel( method, pickupStore );
			syncBillingTitleForPickup( method );
			return;
		}

		lastSignature = signature;
		setValidationState( method, pickupStore );
		syncProcessingState( isProcessing );
		syncOrderSummaryDeliveryLabel( method, pickupStore );
		syncBillingTitleForPickup( method );
		renderFallbackDeliveryStep( method, isProcessing );

		enhancePlaceOrderButton();
		enhanceCouponButton();
		enhanceEmptyCheckoutButton();
		enhanceCouponInput();
		enhanceContactInput();
		expandAddressLineTwo();
		moveOrderNoteStep();
		movePaymentStep();
	}

	function syncBillingTitleForPickup( method ) {
		const billingTitle = document.querySelector(
			'.wc-block-checkout__billing-address .wc-block-components-checkout-step__title, ' +
			'.wp-block-woocommerce-checkout-billing-address-block .wc-block-components-checkout-step__title'
		);

		if ( ! billingTitle ) {
			return;
		}

		const targetTitle = method === 'pickup' ? 'Billing address' : 'Shipping address';

		if ( billingTitle.textContent.trim() !== targetTitle ) {
			billingTitle.textContent = targetTitle;
		}
	}

	function relabelAddressBlocks() {
		const additionalFields = getAdditionalFields();
		const nativeMethod = getSelectedNativeDeliveryMethod();
		const method = isValidMethod( nativeMethod )
			? nativeMethod
			: ( isValidMethod( additionalFields[ DELIVERY_FIELD ] ) ? additionalFields[ DELIVERY_FIELD ] : 'home_delivery' );
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
		if ( billingTitle ) {
			const targetTitle = method === 'pickup' ? 'Billing address' : 'Shipping address';
			if ( billingTitle.textContent.trim() !== targetTitle ) {
				billingTitle.textContent = targetTitle;
			}
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
