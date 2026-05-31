<?php
/**
 * Proposal service.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Governance;

use MagickAI\Core\Audit\Audit_Log_Repository;
use MagickAI\Core\Capabilities\Ability_Registry_Adapter;
use MagickAI\Core\Security\Request_Context;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates proposal records and audit events.
 */
final class Proposal_Service {
	const PENDING_TTL_SECONDS = 86400;

	/**
	 * Proposal repository.
	 *
	 * @var Proposal_Repository
	 */
	private $proposals;

	/**
	 * Ability adapter.
	 *
	 * @var Ability_Registry_Adapter
	 */
	private $abilities;

	/**
	 * Audit repository.
	 *
	 * @var Audit_Log_Repository
	 */
	private $audit;

	/**
	 * Constructor.
	 *
	 * @param Proposal_Repository      $proposals Proposal repository.
	 * @param Ability_Registry_Adapter $abilities Ability adapter.
	 * @param Audit_Log_Repository     $audit Audit repository.
	 */
	public function __construct(
		Proposal_Repository $proposals,
		Ability_Registry_Adapter $abilities,
		Audit_Log_Repository $audit
	) {
		$this->proposals = $proposals;
		$this->abilities = $abilities;
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

		if ( null === $this->abilities->find( $ability_id ) ) {
			return new WP_Error(
				'magick_ai_core_ability_not_available',
				__( 'Proposal target ability is not available.', 'magick-ai-core' ),
				array( 'status' => 404 )
			);
		}

		$caller = is_array( $payload['caller'] ?? null ) ? $payload['caller'] : array();
		if ( Request_Context::is_app() ) {
			$caller['auth'] = Request_Context::audit_metadata();
		}

		$proposal = $this->proposals->create(
			array(
				'ability_id' => $ability_id,
				'title'      => $payload['title'] ?? '',
				'summary'    => $payload['summary'] ?? '',
				'input'      => is_array( $payload['input'] ?? null ) ? $payload['input'] : array(),
				'preview'    => is_array( $payload['preview'] ?? null ) ? $payload['preview'] : array(),
				'caller'     => $caller,
			)
		);

		if ( is_wp_error( $proposal ) ) {
			return $proposal;
		}

		$event_id = $this->audit->record(
			'proposal.created',
			array(
				'ability_id' => $ability_id,
				'status'     => $proposal['status'],
			),
			(string) $proposal['proposal_id']
		);

		if ( '' === $event_id ) {
			$this->proposals->delete_by_proposal_id( (string) $proposal['proposal_id'] );
			return $this->audit_failed_error( 'magick_ai_core_proposal_audit_failed' );
		}

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
	 * Archives an expired proposal.
	 *
	 * @param string              $proposal_id Proposal id.
	 * @param array<string,mixed> $metadata Archive metadata.
	 * @return array<string,mixed>|WP_Error
	 */
	public function archive( string $proposal_id, array $metadata = array() ) {
		$proposal_id = sanitize_text_field( $proposal_id );
		$existing    = $this->proposals->find( $proposal_id );

		if ( null === $existing ) {
			return $this->not_found_error();
		}

		if ( Proposal_Repository::STATUS_EXPIRED !== (string) ( $existing['status'] ?? '' ) ) {
			return new WP_Error(
				'magick_ai_core_proposal_archive_not_allowed',
				__( 'Only expired proposals can be archived.', 'magick-ai-core' ),
				array( 'status' => 409 )
			);
		}

		$proposal = $this->proposals->update_status( $proposal_id, Proposal_Repository::STATUS_ARCHIVED );
		if ( null === $proposal ) {
			return $this->transition_failed_error();
		}

		$event_id = $this->audit->record(
			'proposal.archived',
			array_merge(
				array(
					'ability_id'       => $proposal['ability_id'],
					'status'           => $proposal['status'],
					'previous_status'  => Proposal_Repository::STATUS_EXPIRED,
					'archive_terminal' => true,
				),
				$metadata
			),
			$proposal_id
		);

		if ( '' === $event_id ) {
			$this->proposals->update_status( $proposal_id, Proposal_Repository::STATUS_EXPIRED );
			return $this->audit_failed_error( 'magick_ai_core_proposal_archive_audit_failed' );
		}

		return $proposal;
	}

	/**
	 * Reopens an expired or archived proposal into pending review.
	 *
	 * @param string              $proposal_id Proposal id.
	 * @param array<string,mixed> $metadata Reopen metadata.
	 * @return array<string,mixed>|WP_Error
	 */
	public function reopen( string $proposal_id, array $metadata = array() ) {
		$proposal_id = sanitize_text_field( $proposal_id );
		$existing    = $this->proposals->find( $proposal_id );

		if ( null === $existing ) {
			return $this->not_found_error();
		}

		$previous_status = (string) ( $existing['status'] ?? '' );
		if ( ! in_array( $previous_status, array( Proposal_Repository::STATUS_EXPIRED, Proposal_Repository::STATUS_ARCHIVED ), true ) ) {
			return new WP_Error(
				'magick_ai_core_proposal_reopen_not_allowed',
				__( 'Only expired or archived proposals can be reopened.', 'magick-ai-core' ),
				array( 'status' => 409 )
			);
		}

		$proposal = $this->proposals->reopen( $proposal_id );
		if ( null === $proposal ) {
			return $this->transition_failed_error();
		}

		$event_id = $this->audit->record(
			'proposal.reopened',
			array_merge(
				array(
					'ability_id'      => $proposal['ability_id'],
					'status'          => $proposal['status'],
					'previous_status' => $previous_status,
				),
				$metadata
			),
			$proposal_id
		);

		if ( '' === $event_id ) {
			$this->proposals->update_status( $proposal_id, $previous_status );
			return $this->audit_failed_error( 'magick_ai_core_proposal_reopen_audit_failed' );
		}

		return $proposal;
	}

	/**
	 * Expires stale pending proposals.
	 *
	 * @param int $limit Maximum proposals to expire.
	 * @return int Expired proposal count.
	 */
	public function expire_stale_pending( int $limit = 100 ): int {
		$stale = $this->proposals->list_stale_pending( self::PENDING_TTL_SECONDS, $limit );
		$count = 0;

		foreach ( $stale as $proposal ) {
			if ( $this->expire_one( $proposal, 'ttl_elapsed' ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Returns whether a proposal is pending and past the TTL.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return bool
	 */
	public function is_stale_pending( array $proposal ): bool {
		if ( Proposal_Repository::STATUS_PENDING !== (string) ( $proposal['status'] ?? '' ) ) {
			return false;
		}

		$created_at = strtotime( (string) ( $proposal['created_at'] ?? '' ) );
		return false !== $created_at && $created_at < ( time() - self::PENDING_TTL_SECONDS );
	}

	/**
	 * Returns the pending proposal TTL in seconds.
	 *
	 * @return int
	 */
	public function pending_ttl_seconds(): int {
		return self::PENDING_TTL_SECONDS;
	}

	/**
	 * Records proposal list access.
	 *
	 * @param int $count Returned row count.
	 * @return void
	 */
	public function record_listed( int $count ): void {
		$this->audit->record(
			'proposal.listed',
			array(
				'count' => max( 0, $count ),
			)
		);
	}

	/**
	 * Records proposal detail access.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return void
	 */
	public function record_viewed( array $proposal ): void {
		$this->audit->record(
			'proposal.viewed',
			array(
				'ability_id' => (string) ( $proposal['ability_id'] ?? '' ),
				'status'     => (string) ( $proposal['status'] ?? '' ),
			),
			(string) ( $proposal['proposal_id'] ?? '' )
		);
	}

	/**
	 * Returns proposal audit timeline.
	 *
	 * @param string $proposal_id Proposal id.
	 * @return array<int,array<string,mixed>>
	 */
	public function audit_timeline( string $proposal_id ): array {
		return $this->audit->list_filtered(
			array(
				'proposal_id' => sanitize_text_field( $proposal_id ),
				'limit'       => 50,
				'order'       => 'asc',
			)
		);
	}

	/**
	 * Returns the shared not-found error.
	 *
	 * @return WP_Error
	 */
	public function not_found_error(): WP_Error {
		return new WP_Error(
			'magick_ai_core_proposal_not_found',
			__( 'Proposal was not found.', 'magick-ai-core' ),
			array( 'status' => 404 )
		);
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
			return $this->not_found_error();
		}

		if ( $this->is_stale_pending( $existing ) ) {
			$this->expire_one( $existing, 'decision_attempt_after_ttl' );
			return new WP_Error(
				'magick_ai_core_proposal_expired',
				__( 'Proposal expired before a decision was made.', 'magick-ai-core' ),
				array( 'status' => 409 )
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
			return $this->transition_failed_error();
		}

		$event_id = $this->audit->record(
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

		if ( '' === $event_id ) {
			$this->proposals->update_status( $proposal_id, (string) $existing['status'] );
			return $this->audit_failed_error( 'magick_ai_core_proposal_decision_audit_failed' );
		}

		return $proposal;
	}

	/**
	 * Expires one pending proposal and records audit.
	 *
	 * @param array<string,mixed> $proposal Existing proposal row.
	 * @param string              $reason Expiration reason.
	 * @return bool Whether expiration succeeded.
	 */
	private function expire_one( array $proposal, string $reason ): bool {
		$proposal_id = sanitize_text_field( (string) ( $proposal['proposal_id'] ?? '' ) );
		if ( '' === $proposal_id || Proposal_Repository::STATUS_PENDING !== (string) ( $proposal['status'] ?? '' ) ) {
			return false;
		}

		$expired = $this->proposals->update_status( $proposal_id, Proposal_Repository::STATUS_EXPIRED );
		if ( null === $expired ) {
			return false;
		}

		$event_id = $this->audit->record(
			'proposal.expired',
			array(
				'ability_id'             => (string) $expired['ability_id'],
				'status'                 => (string) $expired['status'],
				'previous_status'        => Proposal_Repository::STATUS_PENDING,
				'expired_after_seconds'  => self::PENDING_TTL_SECONDS,
				'expiration_reason'      => sanitize_key( $reason ),
			),
			$proposal_id
		);

		if ( '' === $event_id ) {
			$this->proposals->update_status( $proposal_id, Proposal_Repository::STATUS_PENDING );
			return false;
		}

		return true;
	}

	/**
	 * Returns transition failed error.
	 *
	 * @return WP_Error
	 */
	private function transition_failed_error(): WP_Error {
		return new WP_Error(
			'magick_ai_core_proposal_transition_failed',
			__( 'Proposal status could not be updated.', 'magick-ai-core' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Returns audit persistence error.
	 *
	 * @param string $code Stable error code.
	 * @return WP_Error
	 */
	private function audit_failed_error( string $code ): WP_Error {
		return new WP_Error(
			$code,
			__( 'Proposal lifecycle could not be audited.', 'magick-ai-core' ),
			array( 'status' => 500 )
		);
	}
}
