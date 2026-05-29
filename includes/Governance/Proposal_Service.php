<?php
/**
 * Proposal service.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Governance;

use MagickAI\Core\Audit\Audit_Log_Repository;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates proposal records and audit events.
 */
final class Proposal_Service {
	/**
	 * Proposal repository.
	 *
	 * @var Proposal_Repository
	 */
	private $proposals;

	/**
	 * Audit repository.
	 *
	 * @var Audit_Log_Repository
	 */
	private $audit;

	/**
	 * Constructor.
	 *
	 * @param Proposal_Repository  $proposals Proposal repository.
	 * @param Audit_Log_Repository $audit Audit repository.
	 */
	public function __construct( Proposal_Repository $proposals, Audit_Log_Repository $audit ) {
		$this->proposals = $proposals;
		$this->audit     = $audit;
	}

	/**
	 * Creates a proposal.
	 *
	 * @param array<string,mixed> $payload Payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create( array $payload ) {
		$ability_id = sanitize_text_field( (string) ( $payload['ability_id'] ?? '' ) );
		if ( '' === $ability_id || false === strpos( $ability_id, '/' ) ) {
			return new WP_Error(
				'magick_ai_core_invalid_ability_id',
				__( 'A namespaced ability_id is required.', 'magick-ai-core' ),
				array( 'status' => 400 )
			);
		}

		$proposal = $this->proposals->create(
			array(
				'ability_id' => $ability_id,
				'title'      => $payload['title'] ?? '',
				'summary'    => $payload['summary'] ?? '',
				'input'      => is_array( $payload['input'] ?? null ) ? $payload['input'] : array(),
				'preview'    => is_array( $payload['preview'] ?? null ) ? $payload['preview'] : array(),
				'caller'     => is_array( $payload['caller'] ?? null ) ? $payload['caller'] : array(),
			)
		);

		$this->audit->record(
			'proposal.created',
			array(
				'ability_id' => $ability_id,
				'status'     => $proposal['status'],
			),
			(string) $proposal['proposal_id']
		);

		return $proposal;
	}

	/**
	 * Approves a proposal without executing commit.
	 *
	 * @param string              $proposal_id Proposal id.
	 * @param array<string,mixed> $metadata Decision metadata.
	 * @return array<string,mixed>|WP_Error
	 */
	public function approve( string $proposal_id, array $metadata = array() ) {
		return $this->transition( $proposal_id, 'approved', 'proposal.approved', $metadata );
	}

	/**
	 * Rejects a proposal.
	 *
	 * @param string              $proposal_id Proposal id.
	 * @param array<string,mixed> $metadata Decision metadata.
	 * @return array<string,mixed>|WP_Error
	 */
	public function reject( string $proposal_id, array $metadata = array() ) {
		return $this->transition( $proposal_id, 'rejected', 'proposal.rejected', $metadata );
	}

	/**
	 * Transitions a proposal status and records audit.
	 *
	 * @param string              $proposal_id Proposal id.
	 * @param string              $status New status.
	 * @param string              $event_name Audit event.
	 * @param array<string,mixed> $metadata Decision metadata.
	 * @return array<string,mixed>|WP_Error
	 */
	private function transition( string $proposal_id, string $status, string $event_name, array $metadata ) {
		$proposal_id = sanitize_text_field( $proposal_id );
		$existing    = $this->proposals->find( $proposal_id );

		if ( null === $existing ) {
			return new WP_Error(
				'magick_ai_core_proposal_not_found',
				__( 'Proposal was not found.', 'magick-ai-core' ),
				array( 'status' => 404 )
			);
		}

		if ( 'pending' !== (string) ( $existing['status'] ?? '' ) ) {
			return new WP_Error(
				'magick_ai_core_proposal_already_decided',
				__( 'Only pending proposals can be approved or rejected.', 'magick-ai-core' ),
				array( 'status' => 409 )
			);
		}

		$proposal = $this->proposals->update_status( $proposal_id, $status );
		if ( null === $proposal ) {
			return new WP_Error(
				'magick_ai_core_proposal_transition_failed',
				__( 'Proposal status could not be updated.', 'magick-ai-core' ),
				array( 'status' => 500 )
			);
		}

		$this->audit->record(
			$event_name,
			array_merge(
				array(
					'ability_id' => $proposal['ability_id'],
					'status'     => $proposal['status'],
				),
				$metadata
			),
			$proposal_id
		);

		return $proposal;
	}
}
