/* global ddwcacAdminObj */
import './admin.less';

const rowOf = field => field && field.closest( 'tr' );

// Hide rows whose inputs carry a class, depending on a controlling field.
const bindToggle = ( control, update ) => {
	if ( control ) {
		control.addEventListener( 'change', update );
		// select2 fires jQuery change events only.
		if ( window.jQuery ) {
			window.jQuery( control ).on( 'change', update );
		}
		update();
	}
};

document.addEventListener( 'DOMContentLoaded', () => {
	// Google: show only the fields for the chosen version.
	const version = document.getElementById( 'ddwcac-google-captcha-version' );
	bindToggle( version, () => {
		document.querySelectorAll( '.ddwcac-google-v2-enable' ).forEach( field => {
			rowOf( field ).hidden = 'v2_checkbox' !== version.value;
		} );
		document.querySelectorAll( '.ddwcac-google-v3-enable' ).forEach( field => {
			rowOf( field ).hidden = 'v3' !== version.value;
		} );
	} );

	// Wizard: only ask for the keys of the chosen provider.
	const provider = document.getElementById( 'ddwcac-wizard-provider' );
	bindToggle( provider, () => {
		[ 'google', 'cloudflare', 'h' ].forEach( type => {
			document.querySelectorAll( '.ddwcac-wizard-keys-' + type ).forEach( field => {
				rowOf( field ).hidden = provider.value !== type;
			} );
		} );
	} );

	// Settings that only matter while their switch is on.
	const dependents = {
		_ddwcac_honeypot_enabled: [ 'honeypot_min_time' ],
	};
	Object.keys( dependents ).forEach( name => {
		const toggle = document.querySelector( 'input[type="checkbox"][name="' + name + '"]' );
		bindToggle( toggle, () => {
			dependents[ name ].forEach( key => {
				const row = rowOf( document.getElementById( 'ddwcac-' + key.replace( /_/g, '-' ) ) );
				if ( row ) {
					row.hidden = ! toggle.checked;
				}
			} );
		} );
	} );

	// Live preview: send what the widget produced to the server and show the provider's verdict.
	document.addEventListener( 'click', event => {
		const button = event.target.closest( '.ddwcac-preview-test' );
		if ( ! button ) {
			return;
		}
		const preview = button.closest( '.ddwcac-preview' );
		const result = preview.querySelector( '.ddwcac-preview-result' );
		const body = new URLSearchParams( {
			action: 'ddwcac_test_captcha',
			nonce: ddwcacAdminObj.nonce,
			type: preview.getAttribute( 'data-type' ),
		} );
		preview.querySelectorAll( '.ddwcac-preview-stage input, .ddwcac-preview-stage textarea' ).forEach( field => {
			if ( field.name ) {
				body.append( field.name, field.value );
			}
		} );

		button.disabled = true;
		result.className = 'ddwcac-preview-result';
		result.textContent = ddwcacAdminObj.i18n.testing;

		window
			.fetch( ddwcacAdminObj.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' } )
			.then( response => response.json() )
			.then( response => {
				result.textContent = response.data && response.data.message ? response.data.message : '';
				result.classList.add( response.success ? 'is-success' : 'is-error' );
			} )
			.catch( () => {
				result.classList.add( 'is-error' );
			} )
			.finally( () => {
				button.disabled = false;
			} );
	} );

	document.addEventListener( 'click', event => {
		const clear = event.target.closest( '.ddwcac-clear-logs' );
		// eslint-disable-next-line no-alert
		if ( clear && ! window.confirm( ddwcacAdminObj.i18n.confirmClear ) ) {
			event.preventDefault();
		}
	} );
} );

// Integrations: each card saves on change, then the badge, filter bucket and header counts follow.
document.addEventListener( 'DOMContentLoaded', () => {
	const root = document.querySelector( '.ddwcac-int' );
	if ( ! root ) {
		return;
	}

	const save = card => {
		const toggle = card.querySelector( '.ddwcac-int-toggle' );
		const body   = new URLSearchParams( {
			action: 'ddwcac_save_integration',
			nonce: ddwcacAdminObj.integrationNonce,
			key: card.dataset.key,
			enabled: toggle.checked ? 'yes' : 'no',
		} );
		card.querySelectorAll( '.ddwcac-int-form:checked' ).forEach( input => body.append( 'forms[]', input.value ) );
		card.classList.add( 'is-saving' );

		fetch( ddwcacAdminObj.ajaxUrl, { method: 'POST', credentials: 'same-origin', body } )
			.then( response => response.json() )
			.then( json => {
				if ( ! json.success ) {
					throw new Error();
				}
				const data  = json.data;
				const badge = card.querySelector( '.ddwcac-int-state .ddwcac-status' );
				toggle.checked = data.enabled;
				card.querySelectorAll( '.ddwcac-int-form' ).forEach( input => {
					input.checked = data.selected.includes( input.value );
				} );
				badge.className   = 'ddwcac-status ddwcac-status-' + data.status[ 0 ];
				badge.textContent = data.status[ 1 ];
				card.className    = card.className.replace( /\bis-(protected|waiting|missing)\b/, 'is-' + data.filter );
				card.dataset.filter = data.filter;
				Object.keys( data.counts ).forEach( key => {
					const el = root.querySelector( '[data-count="' + key + '"]' );
					if ( el ) {
						el.textContent = data.counts[ key ];
					}
				} );
			} )
			.catch( () => window.alert( ddwcacAdminObj.i18n.saveFailed ) ) // eslint-disable-line no-alert
			.finally( () => card.classList.remove( 'is-saving' ) );
	};

	root.addEventListener( 'change', event => {
		const card = event.target.closest( '.ddwcac-int-card' );
		if ( ! card ) {
			return;
		}
		if ( event.target.classList.contains( 'ddwcac-int-form' ) ) {
			// Ticking a form switches the plugin on; unticking the last one switches it off.
			card.querySelector( '.ddwcac-int-toggle' ).checked = !! card.querySelector( '.ddwcac-int-form:checked' );
		} else if ( event.target.classList.contains( 'ddwcac-int-toggle' ) && ! event.target.checked ) {
			card.querySelectorAll( '.ddwcac-int-form' ).forEach( input => {
				input.checked = false;
			} );
		}
		save( card );
	} );

	// Filter buttons and search.
	let filter   = 'all';
	const search = root.querySelector( '.ddwcac-int-search' );
	const apply  = () => {
		const term = search.value.trim().toLowerCase();
		let shown  = 0;
		root.querySelectorAll( '.ddwcac-int-card' ).forEach( card => {
			card.hidden = ! ( ( 'all' === filter || card.dataset.filter === filter ) && card.dataset.name.includes( term ) );
			shown += card.hidden ? 0 : 1;
		} );
		root.querySelectorAll( '.ddwcac-int-group' ).forEach( group => {
			group.hidden = ! group.querySelector( '.ddwcac-int-card:not([hidden])' );
		} );
		root.querySelector( '.ddwcac-int-empty' ).hidden = shown > 0;
	};
	const segments = root.querySelectorAll( '.ddwcac-int-filter' );
	segments.forEach( link => link.addEventListener( 'click', event => {
		event.preventDefault();
		segments.forEach( other => other.classList.toggle( 'current', other === link ) );
		filter = link.dataset.filter;
		apply();
	} ) );
	search.addEventListener( 'input', apply );
} );
