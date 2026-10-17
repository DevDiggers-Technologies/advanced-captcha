/* global jQuery, ddwcacFrontObject, grecaptcha, turnstile, hcaptcha */
import './front.less';

( function ( $ ) {
	'use strict';

	const config = ddwcacFrontObject.ddwcac_configuration;
	const WRAPPER = '.ddwcac-captcha-wrapper';
	const V3_ACTION = 'ddwcac_validate_v3';

	const submitButtons = form => $( form ).find( 'button[type="submit"], button:not([type]), input[type="submit"]' );

	// Keep the submit button locked until the widget is solved, when the admin asked for it.
	const lockSubmit = ( element, enabled ) => {
		if ( ! enabled ) {
			return;
		}
		const form = $( element ).closest( 'form' );
		submitButtons( form ).prop( 'disabled', true );
	};

	const unlockSubmit = ( element, enabled ) => {
		if ( enabled ) {
			submitButtons( $( element ).closest( 'form' ) ).prop( 'disabled', false );
		}
	};

	/* Google reCAPTCHA v2 (checkbox and invisible) */

	const renderReCaptchaV2 = element => {
		if ( element.hasAttribute( 'data-widget-id' ) && element.childElementCount ) {
			return;
		}

		const version = element.getAttribute( 'data-version' );
		const params = {
			sitekey: element.getAttribute( 'data-sitekey' ),
			size: element.getAttribute( 'data-size' ),
			theme: element.getAttribute( 'data-theme' ),
		};

		if ( 'v2_checkbox' === version ) {
			const disable = !! config.google_disable_submit;
			lockSubmit( element, disable );
			params.callback = () => unlockSubmit( element, disable );
			params[ 'expired-callback' ] = () => lockSubmit( element, disable );
			element.setAttribute( 'data-widget-id', grecaptcha.render( element, params ) );
			return;
		}

		if ( 'v2_invisible' !== version ) {
			return;
		}

		// Invisible: hold the submit, run the challenge, then submit for real with the token in place.
		const form = element.closest( 'form' );
		if ( ! form ) {
			return;
		}

		let pendingSubmitter = null;
		params.callback = () => {
			if ( pendingSubmitter && pendingSubmitter.click ) {
				pendingSubmitter.click();
			} else if ( form.requestSubmit ) {
				form.requestSubmit();
			} else {
				form.submit();
			}
		};
		params[ 'expired-callback' ] = () => grecaptcha.reset( widgetId );

		const widgetId = grecaptcha.render( element, params );
		element.setAttribute( 'data-widget-id', widgetId );

		form.addEventListener(
			'submit',
			event => {
				const response = form.querySelector( '.g-recaptcha-response' );
				if ( response && response.value ) {
					return;
				}
				event.preventDefault();
				event.stopImmediatePropagation();
				pendingSubmitter = event.submitter || null;
				grecaptcha.execute( widgetId );
			},
			true
		);
	};

	/* Google reCAPTCHA v3: no widget, a fresh token in a hidden field of every protected form */

	const ensureV3Field = element => {
		let field = element.querySelector( 'textarea[name="g-recaptcha-response"]' );
		if ( ! field ) {
			field = document.createElement( 'textarea' );
			field.name = 'g-recaptcha-response';
			field.className = 'g-recaptcha-response';
			field.hidden = true;
			element.appendChild( field );
		}
		return field;
	};

	const refreshV3Tokens = () => {
		const wrappers = document.querySelectorAll( WRAPPER );
		if ( ! wrappers.length || 'undefined' === typeof grecaptcha || ! grecaptcha.execute ) {
			return;
		}
		grecaptcha.ready( () => {
			grecaptcha.execute( config.google_site_key, { action: V3_ACTION } ).then( token => {
				document.querySelectorAll( WRAPPER ).forEach( wrapper => {
					ensureV3Field( wrapper ).value = token;
				} );
			} );
		} );
	};

	/* Cloudflare Turnstile */

	const renderTurnstile = element => {
		if ( element.hasAttribute( 'data-widget-id' ) ) {
			turnstile.remove( element.getAttribute( 'data-widget-id' ) );
		}
		const disable = !! config.cloudflare_disable_submit;
		lockSubmit( element, disable );

		const widgetId = turnstile.render( element, {
			sitekey: config.cloudflare_site_key,
			theme: config.cloudflare_captcha_theme,
			language: element.getAttribute( 'data-language' ) || 'auto',
			callback: () => unlockSubmit( element, disable ),
			'expired-callback': () => lockSubmit( element, disable ),
		} );
		element.setAttribute( 'data-widget-id', widgetId );
	};

	/* hCaptcha */

	const renderHCaptcha = element => {
		if ( element.hasAttribute( 'data-widget-id' ) ) {
			hcaptcha.remove( element.getAttribute( 'data-widget-id' ) );
		}
		const disable = !! config.h_disable_submit;
		lockSubmit( element, disable );

		const widgetId = hcaptcha.render( element, {
			sitekey: config.h_site_key,
			theme: config.h_captcha_theme,
			size: config.h_captcha_size,
			callback: () => unlockSubmit( element, disable ),
			'expired-callback': () => lockSubmit( element, disable ),
		} );
		element.setAttribute( 'data-widget-id', widgetId );
	};

	/* Built-in CAPTCHA: fetch a new challenge on demand, and replace stale ones from cached pages */

	const BUILTIN_STALE = 20 * 60 * 1000;

	const refreshBuiltin = element => {
		const body = new URLSearchParams( { action: 'ddwcac_builtin_challenge' } );
		return window
			.fetch( ddwcacFrontObject.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' } )
			.then( response => response.json() )
			.then( result => {
				if ( ! result || ! result.success ) {
					return;
				}
				element.querySelector( '.ddwcac-builtin-image' ).innerHTML = result.data.image;
				element.querySelector( '.ddwcac-builtin-token' ).value = result.data.token;
				element.querySelector( '.ddwcac-builtin-answer' ).value = '';
				element.setAttribute( 'data-issued', String( Math.floor( Date.now() / 1000 ) ) );
			} )
			.catch( () => {} );
	};

	const renderBuiltin = element => {
		const issued = parseInt( element.getAttribute( 'data-issued' ), 10 ) * 1000;
		if ( issued && Date.now() - issued > BUILTIN_STALE ) {
			refreshBuiltin( element );
		}
	};

	$( document ).on( 'click', '.ddwcac-builtin-refresh', function () {
		refreshBuiltin( this.closest( '.ddwcac-builtin' ) );
	} );

	/* Shared */

	const render = element => {
		try {
			if ( element.classList.contains( 'ddwcac-builtin' ) ) {
				renderBuiltin( element );
			} else if ( 'google' === config.captcha_type ) {
				if ( 'v3' === config.google_captcha_version ) {
					refreshV3Tokens();
				} else if ( 'undefined' !== typeof grecaptcha && grecaptcha.render ) {
					renderReCaptchaV2( element );
				}
			} else if ( 'cloudflare' === config.captcha_type && 'undefined' !== typeof turnstile ) {
				renderTurnstile( element );
			} else if ( 'h' === config.captcha_type && 'undefined' !== typeof hcaptcha ) {
				renderHCaptcha( element );
			}
		} catch ( error ) {
			// A widget that fails to render must never break the rest of the page.
		}
	};

	const reset = element => {
		const widgetId = element.getAttribute( 'data-widget-id' );
		try {
			if ( element.classList.contains( 'ddwcac-builtin' ) ) {
				refreshBuiltin( element );
			} else if ( 'google' === config.captcha_type ) {
				if ( 'v3' === config.google_captcha_version ) {
					refreshV3Tokens();
				} else if ( null !== widgetId ) {
					grecaptcha.reset( widgetId );
				}
			} else if ( 'cloudflare' === config.captcha_type && null !== widgetId ) {
				turnstile.reset( widgetId );
			} else if ( 'h' === config.captcha_type && null !== widgetId ) {
				hcaptcha.reset( widgetId );
			}
		} catch ( error ) {
			render( element );
		}
	};

	const renderAll = () => document.querySelectorAll( WRAPPER ).forEach( render );

	// Provider onload callbacks named in the script URLs.
	window.ddwcacRecaptchaCallback = renderAll;
	window.ddwcacOnloadTurnstileCallback = renderAll;
	window.ddwcacOnloadHCaptcha = renderAll;

	/* WooCommerce classic checkout: the fragment refresh replaces the widget markup */

	const checkoutCaptcha = () => document.querySelector( 'form.woocommerce-checkout ' + WRAPPER );

	$( document.body ).on( 'updated_checkout', () => {
		const element = checkoutCaptcha();
		if ( element && ( ! element.childElementCount || 'v3' === config.google_captcha_version ) ) {
			element.removeAttribute( 'data-widget-id' );
			render( element );
		}
	} );

	$( document.body ).on( 'checkout_error', () => {
		const element = checkoutCaptcha();
		if ( element ) {
			reset( element );
		}
	} );

	/* WooCommerce block checkout: inject the widget and pass its token to the Store API */

	// Everything the server-side check reads, sent along with the Store API checkout request.
	const BLOCK_FIELDS = [ 'g-recaptcha-response', 'cf-turnstile-response', 'h-captcha-response', 'ddwcac_builtin_token', 'ddwcac_builtin_answer', 'ddwcac_hp', 'ddwcac_ts' ];

	const blockTargets = {
		before_payment_methods: [ '.wp-block-woocommerce-checkout-payment-block', 'before' ],
		before_billing_details: [ '.wp-block-woocommerce-checkout-contact-information-block', 'before' ],
		after_billing_details: [ '.wp-block-woocommerce-checkout-billing-address-block, .wp-block-woocommerce-checkout-shipping-address-block', 'after' ],
		before_place_order: [ '.wc-block-checkout__actions', 'before' ],
		after_place_order: [ '.wc-block-checkout__actions', 'after' ],
	};

	const renderBlocksCaptcha = () => {
		const checkout = document.querySelector( '.wc-block-checkout' );
		if ( ! checkout || checkout.querySelector( WRAPPER ) || ! ddwcacFrontObject.captcha_html ) {
			return;
		}

		const forms = config.woocommerce_forms || [];
		const isGuest = ! document.body.classList.contains( 'logged-in' );
		if ( ! forms.includes( 'users_checkout' ) && ! ( forms.includes( 'guest_checkout' ) && isGuest ) ) {
			return;
		}

		const [ selector, method ] = blockTargets[ config.woocommerce_checkout_form_position ] || blockTargets.before_place_order;
		const target = checkout.querySelector( selector ) || checkout.querySelector( '.wc-block-checkout__main' );
		if ( ! target ) {
			return;
		}

		const holder = document.createElement( 'div' );
		holder.className = 'ddwcac-blocks-captcha';
		holder.innerHTML = ddwcacFrontObject.captcha_html;
		target[ target.matches( '.wc-block-checkout__main' ) ? 'append' : method ]( holder );

		const element = holder.querySelector( WRAPPER );
		if ( element ) {
			render( element );
		}
	};

	if ( 'function' === typeof window.MutationObserver && document.body.classList.contains( 'woocommerce-checkout' ) ) {
		// Blocks render after load and re-render on step changes; observe instead of polling.
		new MutationObserver( renderBlocksCaptcha ).observe( document.body, { childList: true, subtree: true } );
	}

	if ( 'function' === typeof window.fetch ) {
		const originalFetch = window.fetch;

		window.fetch = async function ( resource, options ) {
			const url = 'string' === typeof resource ? resource : ( resource && resource.url ) || '';
			const isCheckout = -1 !== url.indexOf( '/wc/store/v1/checkout' ) && options && 'POST' === String( options.method ).toUpperCase();

			if ( isCheckout && 'string' === typeof options.body ) {
				try {
					const body = JSON.parse( options.body );
					BLOCK_FIELDS.forEach( name => {
						const field = document.querySelector( '.wc-block-checkout [name="' + name + '"]' );
						if ( field ) {
							body[ name ] = field.value;
						}
					} );
					options = Object.assign( {}, options, { body: JSON.stringify( body ) } );
				} catch ( error ) {
					// Not a JSON body; send it untouched.
				}
			}

			const response = await originalFetch.call( this, resource, options );

			if ( isCheckout && ! response.ok ) {
				const element = document.querySelector( '.wc-block-checkout ' + WRAPPER );
				if ( element ) {
					reset( element );
				}
			}

			return response;
		};
	}

	$( () => {
		// v3 tokens live two minutes: issue one on load and renew it before it expires.
		if ( 'google' === config.captcha_type && 'v3' === config.google_captcha_version ) {
			const start = setInterval( () => {
				if ( 'undefined' !== typeof grecaptcha && grecaptcha.execute ) {
					clearInterval( start );
					refreshV3Tokens();
					setInterval( refreshV3Tokens, 90000 );
				}
			}, 300 );
			setTimeout( () => clearInterval( start ), 15000 );
		}

		renderBlocksCaptcha();

		// Built-in challenges have no provider script to call back, so draw them now. Hosted widgets are
		// drawn too when their provider script is already on the page: an admin screen loaded in place
		// keeps the script from an earlier visit, so its onload callback never fires again.
		renderAll();

		// Contact Form 7 resets the form after each submission; tokens are single use.
		document.addEventListener( 'wpcf7submit', event => {
			event.target.querySelectorAll( WRAPPER ).forEach( reset );
		} );

		// AJAX form plugins (WPForms, Fluent Forms, Elementor, Forminator, Formidable) keep the page,
		// so a used or rejected token must be replaced before the next attempt.
		$( document ).ajaxComplete( ( event, xhr, settings ) => {
			const data = settings && 'string' === typeof settings.data ? settings.data : '';
			if ( -1 === data.indexOf( 'ddwcac_' ) && -1 === data.indexOf( 'captcha-response' ) && -1 === data.indexOf( 'turnstile-response' ) ) {
				return;
			}
			document.querySelectorAll( 'form ' + WRAPPER ).forEach( reset );
		} );
	} );
} )( jQuery );
