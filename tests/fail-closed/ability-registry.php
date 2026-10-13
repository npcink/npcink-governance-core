<?php
/** Ability registry test module. @package NpcinkGovernanceCore */

if ( ! function_exists( 'npcink_abilities_toolkit_get_registered' ) ) {
	/**
	 * Ability provider fixture.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	function npcink_abilities_toolkit_get_registered(): array {
		global $npcink_governance_core_fail_closed_abilities;

		if ( is_array( $npcink_governance_core_fail_closed_abilities ?? null ) ) {
			return $npcink_governance_core_fail_closed_abilities;
		}

		$abilities = array(
			'npcink-abilities-toolkit/create-draft' => array(
				'ability_id'        => 'npcink-abilities-toolkit/create-draft',
				'label'             => 'Create Draft',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'edit_posts',
				'required_scopes'   => array( 'post.write' ),
				'input_schema'      => array(
					'type'       => 'object',
					'properties' => array(
						'dry_run'         => array( 'type' => 'boolean', 'default' => true ),
						'commit'          => array( 'type' => 'boolean', 'default' => false ),
						'idempotency_key' => array( 'type' => 'string' ),
					),
				),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/update-post' => array(
				'ability_id'        => 'npcink-abilities-toolkit/update-post',
				'label'             => 'Update Post',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'edit_posts',
				'required_scopes'   => array( 'post.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'post_id' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/patch-post-content' => array(
				'ability_id'        => 'npcink-abilities-toolkit/patch-post-content',
				'label'             => 'Patch Post Content',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'edit_posts',
				'required_scopes'   => array( 'post.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'post_id' => array( 'type' => array( 'integer', 'string' ) ), 'operations' => array( 'type' => 'array' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/update-post-blocks' => array(
				'ability_id'        => 'npcink-abilities-toolkit/update-post-blocks',
				'label'             => 'Update Post Blocks',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'edit_posts',
				'required_scopes'   => array( 'post.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'post_id' => array( 'type' => array( 'integer', 'string' ) ), 'blocks' => array( 'type' => 'array' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/update-template-blocks' => array(
				'ability_id'        => 'npcink-abilities-toolkit/update-template-blocks',
				'label'             => 'Update Template Blocks',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'edit_theme_options',
				'required_scopes'   => array( 'site.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'post_id' => array( 'type' => 'integer' ), 'blocks' => array( 'type' => 'array' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/upsert-template-blocks' => array(
				'ability_id'        => 'npcink-abilities-toolkit/upsert-template-blocks',
				'label'             => 'Upsert Template Blocks',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'edit_theme_options',
				'required_scopes'   => array( 'site.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'slug' => array( 'type' => 'string' ), 'theme' => array( 'type' => 'string' ), 'blocks' => array( 'type' => 'array' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/set-post-terms' => array(
				'ability_id'        => 'npcink-abilities-toolkit/set-post-terms',
				'label'             => 'Set Post Terms',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'edit_posts',
				'required_scopes'   => array( 'taxonomy.manage' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'post_id' => array( 'type' => 'integer' ), 'taxonomy' => array( 'type' => 'string' ), 'term_ids' => array( 'type' => 'array' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/approve-comment' => array(
				'ability_id'        => 'npcink-abilities-toolkit/approve-comment',
				'label'             => 'Approve Comment',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'moderate_comments',
				'required_scopes'   => array( 'comments.manage' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'comment_id' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/trash-post' => array(
				'ability_id'        => 'npcink-abilities-toolkit/trash-post',
				'label'             => 'Trash Post',
				'risk_level'        => 'destructive',
				'requires_approval' => true,
				'capability'        => 'delete_posts',
				'required_scopes'   => array( 'post.delete' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'post_id' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/update-media-details' => array(
				'ability_id'        => 'npcink-abilities-toolkit/update-media-details',
				'label'             => 'Update Media Details',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'attachment_id' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/upload-media-from-url' => array(
				'ability_id'        => 'npcink-abilities-toolkit/upload-media-from-url',
				'label'             => 'Upload Media From URL',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'url' => array( 'type' => 'string' ), 'attach_to_post_id' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/optimize-media-asset' => array(
				'ability_id'        => 'npcink-abilities-toolkit/optimize-media-asset',
				'label'             => 'Optimize Media Asset',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'attachment_id' => array( 'type' => array( 'integer', 'string' ) ), 'target_max_width' => array( 'type' => 'integer' ), 'preferred_format' => array( 'type' => 'string' ), 'quality' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/set-post-featured-image' => array(
				'ability_id'        => 'npcink-abilities-toolkit/set-post-featured-image',
				'label'             => 'Set Post Featured Image',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'edit_posts',
				'required_scopes'   => array( 'media.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'post_id' => array( 'type' => 'integer' ), 'attachment_id' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/adopt-cloud-media-derivative' => array(
				'ability_id'        => 'npcink-abilities-toolkit/adopt-cloud-media-derivative',
				'label'             => 'Adopt Cloud Media Derivative',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.write' ),
				'input_schema'      => array(
					'type'                 => 'object',
					'properties'           => array(
						'attachment_id'       => array( 'type' => 'integer' ),
						'derivative_artifact' => array(
							'type'                 => 'object',
							'properties'           => array(
								'artifact_id'        => array( 'type' => 'string', 'pattern' => '^art_[0-9a-f]{32}$' ),
								'expires_at'         => array( 'type' => 'string', 'format' => 'date-time' ),
								'mime_type'          => array( 'type' => 'string', 'enum' => array( 'image/webp', 'image/avif', 'image/jpeg', 'image/png' ) ),
								'format'             => array( 'type' => 'string', 'enum' => array( 'webp', 'avif', 'jpeg', 'png' ) ),
								'width'              => array( 'type' => 'integer' ),
								'height'             => array( 'type' => 'integer' ),
								'filesize_bytes'     => array( 'type' => 'integer' ),
								'sha256'             => array( 'type' => 'string', 'pattern' => '^[a-f0-9]{64}$' ),
								'suggested_filename' => array( 'type' => 'string' ),
								'filename_basis'     => array( 'type' => 'object' ),
								'processing_warnings' => array( 'type' => 'array' ),
							),
							'required'             => array( 'artifact_id', 'expires_at', 'mime_type', 'format', 'width', 'height', 'filesize_bytes', 'sha256', 'suggested_filename', 'filename_basis', 'processing_warnings' ),
							'additionalProperties' => false,
						),
						'dry_run'              => array( 'type' => 'boolean' ),
						'commit'               => array( 'type' => 'boolean' ),
						'idempotency_key'      => array( 'type' => 'string' ),
					),
					'required'             => array( 'attachment_id', 'derivative_artifact' ),
					'additionalProperties' => false,
				),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/rename-media-file' => array(
				'ability_id'        => 'npcink-abilities-toolkit/rename-media-file',
				'label'             => 'Rename Media File',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'attachment_id' => array( 'type' => 'integer' ), 'target_file_name' => array( 'type' => 'string' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-toolbox/build-article-write-plan' => array(
				'ability_id'        => 'npcink-toolbox/build-article-write-plan',
				'label'             => 'Build Article Write Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'manage_options',
				'required_scopes'   => array( 'cap.toolbox.workflow_suggest' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-toolbox/build-article-batch-write-plan' => array(
				'ability_id'        => 'npcink-toolbox/build-article-batch-write-plan',
				'label'             => 'Build Article Batch Write Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'manage_options',
				'required_scopes'   => array( 'cap.toolbox.workflow_suggest' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-toolbox/build-article-media-batch-write-plan' => array(
				'ability_id'        => 'npcink-toolbox/build-article-media-batch-write-plan',
				'label'             => 'Build Article Media Batch Write Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'manage_options',
				'required_scopes'   => array( 'cap.toolbox.workflow_suggest' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/build-image-candidate-adoption-plan' => array(
				'ability_id'        => 'npcink-abilities-toolkit/build-image-candidate-adoption-plan',
				'label'             => 'Build Image Candidate Adoption Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.read', 'post.read' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-toolbox/build-site-knowledge-review-plan' => array(
				'ability_id'        => 'npcink-toolbox/build-site-knowledge-review-plan',
				'label'             => 'Build Site Knowledge Review Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'manage_options',
				'required_scopes'   => array( 'cap.toolbox.workflow_suggest' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-toolbox/build-nightly-inspection-review-plan' => array(
				'ability_id'        => 'npcink-toolbox/build-nightly-inspection-review-plan',
				'label'             => 'Build Nightly Inspection Review Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'manage_options',
				'required_scopes'   => array( 'cap.toolbox.workflow_suggest' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/build-content-metadata-apply-plan' => array(
				'ability_id'        => 'npcink-abilities-toolkit/build-content-metadata-apply-plan',
				'label'             => 'Build Content Metadata Apply Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'edit_posts',
				'required_scopes'   => array( 'post.read', 'taxonomy.read' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/build-media-alt-apply-plan' => array(
				'ability_id'        => 'npcink-abilities-toolkit/build-media-alt-apply-plan',
				'label'             => 'Build Media ALT Apply Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.read' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/build-media-optimization-plan' => array(
				'ability_id'        => 'npcink-abilities-toolkit/build-media-optimization-plan',
				'label'             => 'Build Media Optimization Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.read' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/build-media-adoption-enhancement-plan' => array(
				'ability_id'        => 'npcink-abilities-toolkit/build-media-adoption-enhancement-plan',
				'label'             => 'Build Media Adoption Enhancement Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.read', 'post.read' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/build-media-rename-plan' => array(
				'ability_id'        => 'npcink-abilities-toolkit/build-media-rename-plan',
				'label'             => 'Build Media Rename Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.read' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/build-pattern-page-plan' => array(
				'ability_id'        => 'npcink-abilities-toolkit/build-pattern-page-plan',
				'label'             => 'Build Pattern Page Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'edit_pages',
				'required_scopes'   => array( 'post.read' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/build-block-theme-site-plan' => array(
				'ability_id'        => 'npcink-abilities-toolkit/build-block-theme-site-plan',
				'label'             => 'Build Block Theme Site Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'edit_theme_options',
				'required_scopes'   => array( 'site.read' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/build-article-block-plan' => array(
				'ability_id'        => 'npcink-abilities-toolkit/build-article-block-plan',
				'label'             => 'Build Article Block Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'edit_posts',
				'required_scopes'   => array( 'post.read' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/read-error-log' => array(
				'ability_id'        => 'npcink-abilities-toolkit/read-error-log',
				'label'             => 'Read Error Log',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'manage_options',
				'required_scopes'   => array( 'logs.read' ),
				'sensitivity'       => 'sensitive',
				'data_classes'      => array( 'logs', 'diagnostics' ),
				'read_authorization' => array(
					'required'       => true,
					'max_rows'       => 50,
					'tail_lines'     => 100,
					'allowed_fields' => array( 'timestamp', 'message', 'severity' ),
					'denied_fields'  => array( 'cookie', 'authorization' ),
				),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
		);

		foreach ( $abilities as &$ability ) {
			$risk = (string) ( $ability['risk_level'] ?? '' );
			$ability['meta'] = is_array( $ability['meta'] ?? null ) ? $ability['meta'] : array();
			$ability['meta']['show_in_rest'] = true;
			$ability['meta']['annotations'] = array(
				'readonly'    => 'read' === $risk,
				'destructive' => 'destructive' === $risk,
			);
			if ( 'read' === $risk && ! array_key_exists( 'sensitivity', $ability ) ) {
				$ability['sensitivity'] = 'internal';
			}
		}
		unset( $ability );

		return $abilities;
	}
}

if ( ! function_exists( 'wp_get_abilities' ) ) {
	/**
	 * WordPress aggregate ability registry fixture.
	 *
	 * @return array<mixed>
	 */
	function wp_get_abilities(): array {
		global $npcink_governance_core_fail_closed_native_abilities;

		if ( is_array( $npcink_governance_core_fail_closed_native_abilities ?? null ) ) {
			return $npcink_governance_core_fail_closed_native_abilities;
		}

		return npcink_abilities_toolkit_get_registered();
	}
}

