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
	 * Constructor.
	 *
	 * @param Ability_Registry_Adapter $abilities Ability adapter.
	 * @param Proposal_Repository      $proposals Proposal repository.
	 * @param Audit_Log_Repository     $audit Audit repository.
	 */
	public function __construct( Ability_Registry_Adapter $abilities, Proposal_Repository $proposals, Audit_Log_Repository $audit ) {
		$this->abilities = $abilities;
		$this->proposals = $proposals;
		$this->audit     = $audit;
	}

	/**
	 * Registers admin menu.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
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

		$summary = $this->abilities->summary();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Magick AI Core', 'magick-ai-core' ); ?></h1>
			<p><?php echo esc_html__( 'AI operation governance for WordPress abilities.', 'magick-ai-core' ); ?></p>

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
		</div>
		<?php
	}
}

