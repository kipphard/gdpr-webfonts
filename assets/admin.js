/* DSGVO Webfonts – Admin JS */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var form = document.getElementById( 'dwf-scan-form' );
		if ( ! form ) {
			return;
		}

		var btn     = form.querySelector( '.dwf-scan-btn' );
		var running = form.querySelector( '.dwf-scan-running' );

		form.addEventListener( 'submit', function () {
			if ( btn ) {
				btn.disabled = true;
				btn.style.opacity = '0.6';
			}
			if ( running ) {
				running.style.display = 'inline';
			}
		} );
	} );
}() );
