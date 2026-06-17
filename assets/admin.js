( function () {
	document.addEventListener( 'click', function ( event ) {
		var toggle = event.target.closest( '.npcink-governance-core-row-details-toggle' );
		var target;
		var expanded;

		if ( ! toggle ) {
			return;
		}

		target = document.getElementById( toggle.getAttribute( 'data-npcink-details-target' ) || '' );
		if ( ! target ) {
			return;
		}

		expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';
		toggle.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
		toggle.textContent = expanded
			? toggle.getAttribute( 'data-show-label' )
			: toggle.getAttribute( 'data-hide-label' );
		target.hidden = expanded;
	} );
}() );
