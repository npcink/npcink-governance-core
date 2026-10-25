<?php
/**
 * Behavioral coverage for the Core admin asset boundary.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Admin {

	// Keep the asset stubs local to this class's namespace, not all unit tests.
	const NPCINK_GOVERNANCE_CORE_FILE    = __DIR__ . '/../../npcink-governance-core.php';
	const NPCINK_GOVERNANCE_CORE_VERSION = 'unit-test-version';

	/** Records the actual style enqueue arguments. */
	function wp_enqueue_style( ...$args ): void {
		\NpcinkGovernanceCore\Tests\Unit\AdminPageAssetsTest::$styles[] = $args;
	}

	/** Records the actual script enqueue arguments. */
	function wp_enqueue_script( ...$args ): void {
		\NpcinkGovernanceCore\Tests\Unit\AdminPageAssetsTest::$scripts[] = $args;
	}

	/** Resolves a deterministic plugin asset URL without loading WordPress. */
	function plugins_url( string $path, string $plugin ): string {
		\NpcinkGovernanceCore\Tests\Unit\AdminPageAssetsTest::$urls[] = array( $path, $plugin );
		return 'https://example.test/plugins/npcink-governance-core/' . $path;
	}
}

namespace NpcinkGovernanceCore\Tests\Unit {

	use Npcink\GovernanceCore\Admin\Admin_Page;
	use PHPUnit\Framework\TestCase;

	/** Calls the real enqueue method with recorder-backed WordPress functions. */
	final class AdminPageAssetsTest extends TestCase {
		/** @var array<int,array<int,mixed>> */
		public static $styles = array();

		/** @var array<int,array<int,mixed>> */
		public static $scripts = array();

		/** @var array<int,array<int,string>> */
		public static $urls = array();

		/** Resets the enqueue recorder between hook cases. */
		protected function setUp(): void {
			self::$styles  = array();
			self::$scripts = array();
			self::$urls    = array();
		}

		/**
		 * Exercises both Core registrations and pages belonging to other plugins.
		 *
		 * @dataProvider adminHooks
		 * @param string $hook Admin hook suffix.
		 * @param bool   $owns_page Whether Core owns the page.
		 */
		public function test_enqueues_assets_only_on_core_pages( string $hook, bool $owns_page ): void {
			// enqueue_assets has no service dependencies; avoid constructing database services.
			$page = ( new \ReflectionClass( Admin_Page::class ) )->newInstanceWithoutConstructor();
			$page->enqueue_assets( $hook );

			if ( ! $owns_page ) {
				self::assertSame( array(), self::$styles, 'Other pages must not enqueue Core CSS.' );
				self::assertSame( array(), self::$scripts, 'Other pages must not enqueue Core JS.' );
				self::assertSame( array(), self::$urls, 'Other pages must not resolve Core asset URLs.' );
				return;
			}

			self::assertSame(
				array( array( 'npcink-governance-core-admin', 'https://example.test/plugins/npcink-governance-core/assets/admin.css', array(), 'unit-test-version' ) ),
				self::$styles
			);
			self::assertSame(
				array( array( 'npcink-governance-core-admin', 'https://example.test/plugins/npcink-governance-core/assets/admin.js', array(), 'unit-test-version', true ) ),
				self::$scripts
			);
			self::assertSame(
				array(
					array( 'assets/admin.css', \Npcink\GovernanceCore\Admin\NPCINK_GOVERNANCE_CORE_FILE ),
					array( 'assets/admin.js', \Npcink\GovernanceCore\Admin\NPCINK_GOVERNANCE_CORE_FILE ),
				),
				self::$urls
			);
		}

		/** @return array<string,array{string,bool}> */
		public static function adminHooks(): array {
			return array(
				'Core submenu'       => array( 'npcink-ai_page_npcink-governance-core', true ),
				'Core Tools fallback' => array( 'tools_page_npcink-governance-core', true ),
				'Cloud submenu'      => array( 'npcink-ai_page_npcink-cloud-addon', false ),
				'Toolbox submenu'    => array( 'npcink-ai_page_npcink-workflow-toolbox', false ),
				'Npcink parent'      => array( 'toplevel_page_npcink-ai', false ),
				'Other Npcink page'  => array( 'npcink-ai_page_other-plugin', false ),
				'Dashboard'          => array( 'index.php', false ),
			);
		}
	}
}
