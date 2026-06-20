<?php
/**
 * Historical governance record cleanup.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Governance;

use Npcink\GovernanceCore\Audit\Audit_Log_Repository;
use Npcink\GovernanceCore\Security\App_Key_Repository;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cleans old historical Core records under the configured retention policy.
 */
final class History_Cleanup_Service {
	const OPTION_HISTORY_RETENTION_DAYS = 'npcink_governance_core_history_retention_days';
	const DEFAULT_HISTORY_RETENTION_DAYS = 90;
	const HISTORY_RETENTION_DISABLED_DAYS = 0;
	const CLEANUP_BATCH_LIMIT = 200;

	/**
	 * Proposal repository.
	 *
	 * @var Proposal_Repository
	 */
	private $proposals;

	/**
	 * App key repository.
	 *
	 * @var App_Key_Repository
	 */
	private $apps;

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
	 * @param App_Key_Repository   $apps App key repository.
	 * @param Audit_Log_Repository $audit Audit repository.
	 */
	public function __construct( Proposal_Repository $proposals, App_Key_Repository $apps, Audit_Log_Repository $audit ) {
		$this->proposals = $proposals;
		$this->apps      = $apps;
		$this->audit     = $audit;
	}

	/**
	 * Returns bounded retention day options.
	 *
	 * @return array<int,string>
	 */
	public static function retention_day_options(): array {
		return array(
			90                                      => __( '90 days', 'npcink-governance-core' ),
			180                                     => __( '180 days', 'npcink-governance-core' ),
			365                                     => __( '365 days', 'npcink-governance-core' ),
			self::HISTORY_RETENTION_DISABLED_DAYS => __( 'Do not automatically delete', 'npcink-governance-core' ),
		);
	}

	/**
	 * Sanitizes retention days.
	 *
	 * @param string|int $days Raw retention days.
	 * @return int
	 */
	public static function sanitize_retention_days( $days ): int {
		$days    = absint( $days );
		$allowed = array_keys( self::retention_day_options() );

		return in_array( $days, $allowed, true ) ? $days : self::DEFAULT_HISTORY_RETENTION_DAYS;
	}

	/**
	 * Returns the stored retention policy.
	 *
	 * @return int
	 */
	public static function stored_retention_days(): int {
		$days = get_option( self::OPTION_HISTORY_RETENTION_DAYS, self::DEFAULT_HISTORY_RETENTION_DAYS );

		return self::sanitize_retention_days( is_scalar( $days ) ? $days : self::DEFAULT_HISTORY_RETENTION_DAYS );
	}

	/**
	 * Runs one bounded cleanup pass.
	 *
	 * @param string $source Trigger source.
	 * @return array<string,mixed>|WP_Error
	 */
	public function run( string $source = 'manual' ) {
		$retention_days = self::stored_retention_days();
		$source         = sanitize_key( $source );

		if ( self::HISTORY_RETENTION_DISABLED_DAYS === $retention_days ) {
			return array(
				'skipped'                => true,
				'source'                 => $source,
				'history_retention_days' => $retention_days,
				'deleted_proposals'      => 0,
				'deleted_app_keys'       => 0,
				'deleted_audit_events'   => 0,
			);
		}

		$cutoff             = gmdate( 'Y-m-d H:i:s', time() - ( $retention_days * DAY_IN_SECONDS ) );
		$planned_proposals  = $this->proposals->count_historical_before( $cutoff );
		$planned_app_keys   = $this->apps->count_revoked_before( $cutoff );
		$planned_audit_events = $this->audit->count_access_events_before( $cutoff );
		$requested_event_id = $this->audit->record(
			'core.history_cleanup_requested',
			array(
				'source'                 => $source,
				'history_retention_days' => $retention_days,
				'cutoff_utc'             => $cutoff,
				'planned_proposals'      => $planned_proposals,
				'planned_app_keys'       => $planned_app_keys,
				'planned_audit_events'   => $planned_audit_events,
				'batch_limit'            => self::CLEANUP_BATCH_LIMIT,
				'commit_execution'       => false,
				'core_execution'         => false,
			)
		);

		if ( '' === $requested_event_id ) {
			return new WP_Error(
				'npcink_governance_core_history_cleanup_audit_failed',
				__( 'History cleanup could not be audited.', 'npcink-governance-core' ),
				array( 'status' => 500 )
			);
		}

		$deleted_proposals = $this->proposals->delete_historical_before( $cutoff, self::CLEANUP_BATCH_LIMIT );
		$deleted_app_keys  = $this->apps->delete_revoked_before( $cutoff, self::CLEANUP_BATCH_LIMIT );
		$deleted_audit_events = $this->audit->delete_access_events_before( $cutoff, self::CLEANUP_BATCH_LIMIT );
		if ( null === $deleted_proposals || null === $deleted_app_keys || null === $deleted_audit_events ) {
			$this->audit->record(
				'core.history_cleanup_failed',
				array(
					'source'                 => $source,
					'history_retention_days' => $retention_days,
					'cutoff_utc'             => $cutoff,
					'requested_event_id'     => $requested_event_id,
					'deleted_proposals'      => null === $deleted_proposals ? 0 : $deleted_proposals,
					'deleted_app_keys'       => null === $deleted_app_keys ? 0 : $deleted_app_keys,
					'deleted_audit_events'   => null === $deleted_audit_events ? 0 : $deleted_audit_events,
					'commit_execution'       => false,
					'core_execution'         => false,
				)
			);

			return new WP_Error(
				'npcink_governance_core_history_cleanup_failed',
				__( 'History cleanup could not delete all selected records.', 'npcink-governance-core' ),
				array( 'status' => 500 )
			);
		}

		$completed_event_id = $this->audit->record(
			'core.history_cleanup_completed',
			array(
				'source'                 => $source,
				'history_retention_days' => $retention_days,
				'cutoff_utc'             => $cutoff,
				'requested_event_id'     => $requested_event_id,
				'deleted_proposals'      => $deleted_proposals,
				'deleted_app_keys'       => $deleted_app_keys,
				'deleted_audit_events'   => $deleted_audit_events,
				'batch_limit'            => self::CLEANUP_BATCH_LIMIT,
				'commit_execution'       => false,
				'core_execution'         => false,
			)
		);

		if ( '' === $completed_event_id ) {
			return new WP_Error(
				'npcink_governance_core_history_cleanup_completion_audit_failed',
				__( 'History cleanup completed but the completion audit could not be stored.', 'npcink-governance-core' ),
				array( 'status' => 500 )
			);
		}

		return array(
			'skipped'                => false,
			'source'                 => $source,
			'history_retention_days' => $retention_days,
			'cutoff_utc'             => $cutoff,
			'deleted_proposals'      => $deleted_proposals,
			'deleted_app_keys'       => $deleted_app_keys,
			'deleted_audit_events'   => $deleted_audit_events,
			'requested_event_id'     => $requested_event_id,
			'completed_event_id'     => $completed_event_id,
		);
	}
}
