<?php
/**
 * Local media derivative policy settings.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Media;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores the local WordPress media derivative policy truth.
 */
final class Media_Derivative_Settings {
	public const OPTION_NAME = 'magick_ai_core_media_derivative_settings';

	/**
	 * Registers the option.
	 *
	 * @return void
	 */
	public function register(): void {
		register_setting(
			'magick_ai_core_media_derivative',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => $this->defaults(),
			)
		);
	}

	/**
	 * Returns default policy values.
	 *
	 * @return array<string,mixed>
	 */
	public function defaults(): array {
		return array(
			'enabled'                 => false,
			'target_format'           => 'webp',
			'max_width'               => 1600,
			'quality'                 => 82,
			'watermark_enabled'       => false,
			'watermark_attachment_id' => 0,
			'watermark_position'      => 'bottom_right',
			'watermark_opacity'       => 80,
			'watermark_scale'         => 20,
			'watermark_margin'        => 24,
			'use_cloud_when_available' => true,
		);
	}

	/**
	 * Returns sanitized current settings.
	 *
	 * @return array<string,mixed>
	 */
	public function get_all(): array {
		$value = get_option( self::OPTION_NAME, array() );
		return $this->sanitize( array_merge( $this->defaults(), is_array( $value ) ? $value : array() ) );
	}

	/**
	 * Sanitizes stored settings.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string,mixed>
	 */
	public function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : array();

		$format = sanitize_key( (string) ( $input['target_format'] ?? 'webp' ) );
		if ( ! in_array( $format, $this->allowed_formats(), true ) ) {
			$format = 'webp';
		}

		$position = sanitize_key( (string) ( $input['watermark_position'] ?? 'bottom_right' ) );
		if ( ! in_array( $position, $this->allowed_watermark_positions(), true ) ) {
			$position = 'bottom_right';
		}

		return array(
			'enabled'                 => ! empty( $input['enabled'] ),
			'target_format'           => $format,
			'max_width'               => max( 320, min( 7680, absint( $input['max_width'] ?? 1600 ) ) ),
			'quality'                 => max( 1, min( 100, absint( $input['quality'] ?? 82 ) ) ),
			'watermark_enabled'       => ! empty( $input['watermark_enabled'] ),
			'watermark_attachment_id' => absint( $input['watermark_attachment_id'] ?? 0 ),
			'watermark_position'      => $position,
			'watermark_opacity'       => max( 0, min( 100, absint( $input['watermark_opacity'] ?? 80 ) ) ),
			'watermark_scale'         => max( 1, min( 100, absint( $input['watermark_scale'] ?? 20 ) ) ),
			'watermark_margin'        => max( 0, min( 1000, absint( $input['watermark_margin'] ?? 24 ) ) ),
			'use_cloud_when_available' => ! empty( $input['use_cloud_when_available'] ),
		);
	}

	/**
	 * Builds input for the local media derivative Cloud request ability.
	 *
	 * @param array<string,mixed> $overrides One-run overrides.
	 * @return array<string,mixed>
	 */
	public function ability_input( array $overrides = array() ): array {
		if ( isset( $overrides['preferred_format'] ) && ! isset( $overrides['target_format'] ) ) {
			$overrides['target_format'] = $overrides['preferred_format'];
		}
		if ( isset( $overrides['target_max_width'] ) && ! isset( $overrides['max_width'] ) ) {
			$overrides['max_width'] = $overrides['target_max_width'];
		}

		$settings = $this->sanitize( array_merge( $this->get_all(), $overrides ) );
		$input    = array(
			'preferred_format' => $settings['target_format'],
			'target_max_width' => $settings['max_width'],
			'quality'          => $settings['quality'],
		);

		$attachment_id = absint( $overrides['attachment_id'] ?? 0 );
		if ( $attachment_id > 0 ) {
			$input['attachment_id'] = $attachment_id;
		}

		if ( ! empty( $settings['watermark_enabled'] ) && absint( $settings['watermark_attachment_id'] ) > 0 ) {
			$input['watermark'] = array(
				'type'          => 'image',
				'position'      => $settings['watermark_position'],
				'opacity'       => round( (int) $settings['watermark_opacity'] / 100, 3 ),
				'scale_percent' => $settings['watermark_scale'],
				'margin_px'     => $settings['watermark_margin'],
			);
		}
		if (
			( ! array_key_exists( 'watermark_enabled', $overrides ) || ! empty( $overrides['watermark_enabled'] ) )
			&& is_array( $overrides['watermark'] ?? null )
			&& ! empty( $overrides['watermark'] )
		) {
			$input['watermark'] = $this->sanitize_watermark_plan( $overrides['watermark'], $settings );
		}

		return $input;
	}

	/**
	 * Returns a bounded status summary for other local surfaces.
	 *
	 * @return array<string,mixed>
	 */
	public function summary(): array {
		$settings = $this->get_all();

		return array(
			'enabled'                 => (bool) $settings['enabled'],
			'target_format'           => (string) $settings['target_format'],
			'max_width'               => (int) $settings['max_width'],
			'quality'                 => (int) $settings['quality'],
			'watermark_enabled'       => (bool) $settings['watermark_enabled'],
			'watermark_configured'    => (bool) $settings['watermark_enabled'] && absint( $settings['watermark_attachment_id'] ) > 0,
			'watermark_attachment_id' => absint( $settings['watermark_attachment_id'] ),
			'watermark_position'      => (string) $settings['watermark_position'],
			'watermark_opacity'       => (int) $settings['watermark_opacity'],
			'watermark_scale'         => (int) $settings['watermark_scale'],
			'watermark_margin'        => (int) $settings['watermark_margin'],
			'use_cloud_when_available' => (bool) $settings['use_cloud_when_available'],
			'policy_owner'            => 'magick_ai_core',
			'final_write_owner'       => 'local_wordpress_host',
		);
	}

	/**
	 * Sanitizes a one-run watermark plan without changing stored policy.
	 *
	 * @param array<string,mixed> $watermark Raw watermark input.
	 * @param array<string,mixed> $settings Current sanitized settings.
	 * @return array<string,mixed>
	 */
	private function sanitize_watermark_plan( array $watermark, array $settings ): array {
		$position = sanitize_key( (string) ( $watermark['position'] ?? $settings['watermark_position'] ?? 'bottom_right' ) );
		if ( ! in_array( $position, $this->allowed_watermark_positions(), true ) ) {
			$position = 'bottom_right';
		}

		$opacity = is_numeric( $watermark['opacity'] ?? null )
			? (float) $watermark['opacity']
			: ( (int) ( $settings['watermark_opacity'] ?? 80 ) / 100 );

		return array(
			'type'          => 'image',
			'position'      => $position,
			'opacity'       => round( max( 0, min( 1, $opacity ) ), 3 ),
			'scale_percent' => max( 1, min( 100, absint( $watermark['scale_percent'] ?? $settings['watermark_scale'] ?? 20 ) ) ),
			'margin_px'     => max( 0, min( 1000, absint( $watermark['margin_px'] ?? $settings['watermark_margin'] ?? 24 ) ) ),
		);
	}

	/**
	 * Returns supported target formats.
	 *
	 * @return array<int,string>
	 */
	public function allowed_formats(): array {
		return array( 'webp', 'avif', 'jpeg', 'png', 'original' );
	}

	/**
	 * Returns supported watermark positions.
	 *
	 * @return array<int,string>
	 */
	public function allowed_watermark_positions(): array {
		return array( 'top_left', 'top_right', 'center', 'bottom_left', 'bottom_right' );
	}
}
