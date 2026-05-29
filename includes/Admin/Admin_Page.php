<?php
/**
 * Minimal admin page.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Admin;

use MagickAI\Core\Audit\Audit_Log_Repository;
use MagickAI\Core\Capabilities\Ability_Registry_Adapter;
use MagickAI\Core\Governance\Proposal_Repository;
use MagickAI\Core\Governance\Proposal_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a compact governance overview.
 */
final class Admin_Page {
	/**
	 * Ability adapter.
	 *
	 * @var Ability_Registry_Adapter
	 */
	private $abilities;

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
	 * Proposal service.
	 *
	 * @var Proposal_Service
	 */
	private $service;

	/**
	 * Constructor.
	 *
	 * @param Ability_Registry_Adapter $abilities Ability adapter.
	 * @param Proposal_Repository      $proposals Proposal repository.
	 * @param Audit_Log_Repository     $audit Audit repository.
	 * @param Proposal_Service         $service Proposal service.
	 */
	public function __construct( Ability_Registry_Adapter $abilities, Proposal_Repository $proposals, Audit_Log_Repository $audit, Proposal_Service $service ) {
		$this->abilities = $abilities;
		$this->proposals = $proposals;
		$this->audit     = $audit;
		$this->service   = $service;
	}

	/**
	 * Registers admin menu.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_post_magick_ai_core_approve_proposal', array( $this, 'handle_approve' ) );
		add_action( 'admin_post_magick_ai_core_reject_proposal', array( $this, 'handle_reject' ) );
	}

	/**
	 * Adds menu page.
	 *
	 * @return void
	 */
	public function add_menu(): void {
		add_management_page(
			__( 'Magick AI Core', 'magick-ai-core' ),
			__( 'Magick AI Core', 'magick-ai-core' ),
			'manage_options',
			'magick-ai-core',
			array( $this, 'render' )
		);
	}

	/**
	 * Renders admin page.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'magick-ai-core' ) );
		}

		$summary       = $this->abilities->summary();
		$pending       = $this->proposals->list_recent( 20, 'pending' );
		$selected_id   = isset( $_GET['proposal_id'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['proposal_id'] ) ) : '';
		$selected      = '' !== $selected_id ? $this->proposals->find( $selected_id ) : null;
		$message       = isset( $_GET['magick_ai_core_message'] ) ? sanitize_key( wp_unslash( (string) $_GET['magick_ai_core_message'] ) ) : '';
		$error         = isset( $_GET['magick_ai_core_error'] ) ? sanitize_key( wp_unslash( (string) $_GET['magick_ai_core_error'] ) ) : '';
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Magick AI Core', 'magick-ai-core' ); ?></h1>
			<p><?php echo esc_html__( 'AI operation governance for WordPress abilities.', 'magick-ai-core' ); ?></p>

			<?php if ( '' !== $message ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( $this->message_text( $message ) ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $error ) : ?>
				<div class="notice notice-error is-dismissible">
					<p><?php echo esc_html( $this->message_text( $error ) ); ?></p>
				</div>
			<?php endif; ?>

			<table class="widefat striped" style="max-width: 760px;">
				<tbody>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Ability source', 'magick-ai-core' ); ?></th>
						<td><?php echo esc_html( $summary['source'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Available abilities', 'magick-ai-core' ); ?></th>
						<td><?php echo esc_html( (string) $summary['count'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Proposals', 'magick-ai-core' ); ?></th>
						<td><?php echo esc_html( (string) $this->proposals->count() ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Audit events', 'magick-ai-core' ); ?></th>
						<td><?php echo esc_html( (string) $this->audit->count() ); ?></td>
					</tr>
				</tbody>
			</table>

			<h2><?php echo esc_html__( 'Pending Proposals', 'magick-ai-core' ); ?></h2>
			<table class="widefat striped" style="max-width: 1100px;">
				<thead>
					<tr>
						<th scope="col"><?php echo esc_html__( 'Title', 'magick-ai-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Ability', 'magick-ai-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Created', 'magick-ai-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Action', 'magick-ai-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $pending ) ) : ?>
						<tr>
							<td colspan="4"><?php echo esc_html__( 'No pending proposals.', 'magick-ai-core' ); ?></td>
						</tr>
					<?php endif; ?>
					<?php foreach ( $pending as $proposal ) : ?>
						<tr>
							<td><?php echo esc_html( (string) ( $proposal['title'] ?: $proposal['proposal_id'] ) ); ?></td>
							<td><code><?php echo esc_html( (string) $proposal['ability_id'] ); ?></code></td>
							<td><?php echo esc_html( (string) $proposal['created_at'] ); ?></td>
							<td>
								<a class="button" href="<?php echo esc_url( $this->detail_url( (string) $proposal['proposal_id'] ) ); ?>">
									<?php echo esc_html__( 'Review', 'magick-ai-core' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( null !== $selected ) : ?>
				<?php $this->render_proposal_detail( $selected ); ?>
			<?php elseif ( '' !== $selected_id ) : ?>
				<div class="notice notice-warning">
					<p><?php echo esc_html__( 'Selected proposal was not found.', 'magick-ai-core' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handles approval form submission.
	 *
	 * @return void
	 */
	public function handle_approve(): void {
		$this->handle_decision( 'approve' );
	}

	/**
	 * Handles rejection form submission.
	 *
	 * @return void
	 */
	public function handle_reject(): void {
		$this->handle_decision( 'reject' );
	}

	/**
	 * Handles approval or rejection.
	 *
	 * @param string $decision Decision.
	 * @return void
	 */
	private function handle_decision( string $decision ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to update proposals.', 'magick-ai-core' ) );
		}

		$proposal_id = isset( $_POST['proposal_id'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['proposal_id'] ) ) : '';
		check_admin_referer( 'magick_ai_core_decide_proposal_' . $proposal_id );

		$note   = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['note'] ) ) : '';
		$result = 'approve' === $decision ? $this->service->approve( $proposal_id, array( 'note' => $note ) ) : $this->service->reject( $proposal_id, array( 'note' => $note ) );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( $this->admin_url( array( 'magick_ai_core_error' => $result->get_error_code() ) ) );
			exit;
		}

		wp_safe_redirect( $this->admin_url( array( 'magick_ai_core_message' => 'approve' === $decision ? 'approved' : 'rejected' ) ) );
		exit;
	}

	/**
	 * Renders proposal detail.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return void
	 */
	private function render_proposal_detail( array $proposal ): void {
		$proposal_id = (string) $proposal['proposal_id'];
		?>
		<h2><?php echo esc_html__( 'Proposal Detail', 'magick-ai-core' ); ?></h2>
		<table class="widefat striped" style="max-width: 1100px;">
			<tbody>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Proposal ID', 'magick-ai-core' ); ?></th>
					<td><code><?php echo esc_html( $proposal_id ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Status', 'magick-ai-core' ); ?></th>
					<td><?php echo esc_html( (string) $proposal['status'] ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Ability', 'magick-ai-core' ); ?></th>
					<td><code><?php echo esc_html( (string) $proposal['ability_id'] ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Summary', 'magick-ai-core' ); ?></th>
					<td><?php echo esc_html( (string) $proposal['summary'] ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Input', 'magick-ai-core' ); ?></th>
					<td><pre><?php echo esc_html( (string) wp_json_encode( $proposal['input'], JSON_PRETTY_PRINT ) ); ?></pre></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Preview', 'magick-ai-core' ); ?></th>
					<td><pre><?php echo esc_html( (string) wp_json_encode( $proposal['preview'], JSON_PRETTY_PRINT ) ); ?></pre></td>
				</tr>
			</tbody>
		</table>

		<?php if ( 'pending' === (string) $proposal['status'] ) : ?>
			<h3><?php echo esc_html__( 'Decision', 'magick-ai-core' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width: 760px;">
				<input type="hidden" name="proposal_id" value="<?php echo esc_attr( $proposal_id ); ?>" />
				<?php wp_nonce_field( 'magick_ai_core_decide_proposal_' . $proposal_id ); ?>
				<p>
					<label for="magick-ai-core-note"><?php echo esc_html__( 'Decision note', 'magick-ai-core' ); ?></label><br />
					<textarea id="magick-ai-core-note" name="note" rows="3" class="large-text"></textarea>
				</p>
				<p>
					<button type="submit" class="button button-primary" name="action" value="magick_ai_core_approve_proposal">
						<?php echo esc_html__( 'Approve', 'magick-ai-core' ); ?>
					</button>
					<button type="submit" class="button" name="action" value="magick_ai_core_reject_proposal">
						<?php echo esc_html__( 'Reject', 'magick-ai-core' ); ?>
					</button>
				</p>
			</form>
		<?php endif; ?>
		<?php
	}

	/**
	 * Returns detail URL.
	 *
	 * @param string $proposal_id Proposal id.
	 * @return string
	 */
	private function detail_url( string $proposal_id ): string {
		return $this->admin_url( array( 'proposal_id' => $proposal_id ) );
	}

	/**
	 * Returns admin page URL.
	 *
	 * @param array<string,string> $args Query args.
	 * @return string
	 */
	private function admin_url( array $args = array() ): string {
		return add_query_arg( array_merge( array( 'page' => 'magick-ai-core' ), $args ), admin_url( 'tools.php' ) );
	}

	/**
	 * Returns user-facing message text.
	 *
	 * @param string $code Message code.
	 * @return string
	 */
	private function message_text( string $code ): string {
		$messages = array(
			'approved'                                      => __( 'Proposal approved.', 'magick-ai-core' ),
			'rejected'                                      => __( 'Proposal rejected.', 'magick-ai-core' ),
			'magick_ai_core_proposal_not_found'             => __( 'Proposal was not found.', 'magick-ai-core' ),
			'magick_ai_core_proposal_already_decided'       => __( 'Only pending proposals can be approved or rejected.', 'magick-ai-core' ),
			'magick_ai_core_proposal_transition_failed'     => __( 'Proposal status could not be updated.', 'magick-ai-core' ),
		);

		return (string) ( $messages[ $code ] ?? __( 'Proposal action could not be completed.', 'magick-ai-core' ) );
	}
}
