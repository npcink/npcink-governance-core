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
		var toggleAll = document.querySelector( '[data-npcink-bulk-toggle-all]' );
		var applyButton = document.querySelector( '[data-npcink-bulk-apply]' );

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

		if ( toggleAll ) {
			toggleAll.checked = count > 0 && count === checkboxes.length;
			toggleAll.indeterminate = count > 0 && count < checkboxes.length;
		}

		if ( applyButton ) {
			applyButton.disabled = count === 0;
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var toggle = event.target.closest( '.npcink-governance-core-row-details-toggle' );
		var bulkClear = event.target.closest( '[data-npcink-bulk-clear]' );
		var bulkApply = event.target.closest( '[data-npcink-bulk-apply]' );
		var bulkSelect;
		var disclosure;
		var target;
		var expanded;

		if ( bulkApply ) {
			bulkSelect = document.querySelector( '[data-npcink-bulk-select]' );
			disclosure = document.querySelector( '[data-npcink-bulk-disclosure]' );
			if ( bulkSelect && bulkSelect.value === 'reject' && disclosure && ! disclosure.hidden ) {
				disclosure.open = true;
				disclosure.scrollIntoView( { block: 'nearest' } );
			}
			return;
		}

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
		if ( event.target.matches( '[data-npcink-bulk-toggle-all]' ) ) {
			Array.prototype.forEach.call( document.querySelectorAll( 'input[name="proposal_ids[]"]' ), function ( checkbox ) {
				checkbox.checked = event.target.checked;
			} );
			updateBulkActions();
			return;
		}

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
