( function () {
	function updateBulkActions() {
		var disclosure = document.querySelector( '[data-npcink-bulk-disclosure]' );
		var checkboxes = Array.prototype.slice.call( document.querySelectorAll( 'input[name="proposal_ids[]"]' ) );
		var selected = checkboxes.filter( function ( checkbox ) {
			return checkbox.checked;
		} );
		var count = selected.length;
		var countLabel;
		var help;
		var selectedLabel;

		if ( ! disclosure ) {
			return;
		}

		countLabel = disclosure.querySelector( '[data-npcink-bulk-count-label]' );
		help = disclosure.querySelector( '[data-npcink-bulk-help]' );
		disclosure.hidden = count === 0;
		disclosure.open = count > 0;

		if ( countLabel ) {
			selectedLabel = countLabel.getAttribute( 'data-selected-label' ) || '%d selected';
			countLabel.textContent = count > 0
				? selectedLabel.replace( '%d', count )
				: countLabel.getAttribute( 'data-default-label' );
		}

		if ( help ) {
			help.textContent = count > 0
				? help.getAttribute( 'data-selected-help' )
				: help.getAttribute( 'data-default-help' );
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var toggle = event.target.closest( '.npcink-governance-core-row-details-toggle' );
		var bulkClear = event.target.closest( '[data-npcink-bulk-clear]' );
		var target;
		var expanded;

		if ( bulkClear ) {
			Array.prototype.forEach.call( document.querySelectorAll( 'input[name="proposal_ids[]"]' ), function ( checkbox ) {
				checkbox.checked = false;
			} );
			updateBulkActions();
			return;
		}

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

	document.addEventListener( 'change', function ( event ) {
		if ( event.target.matches( 'input[name="proposal_ids[]"]' ) ) {
			updateBulkActions();
		}
	} );

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', updateBulkActions );
	} else {
		updateBulkActions();
	}
}() );
