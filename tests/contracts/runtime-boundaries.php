<?php
/** Runtime boundaries test module. @package NpcinkGovernanceCore */

npcink_governance_core_assert( false !== strpos( $core_operability, 'article workflow summary' ), 'Core governance operability documents article workflow summary.' );
npcink_governance_core_assert( false !== strpos( $core_operability, 'final write ability' ), 'Core governance operability documents article final write ability.' );
npcink_governance_core_assert( false !== strpos( $core_operability, 'batch action details' ), 'Core governance operability documents batch action detail review.' );
npcink_governance_core_assert( false !== strpos( $core_operability, 'audit lifecycle summary' ), 'Core governance operability documents visible lifecycle evidence.' );

$forbidden_runtime_terms = array(
	'Agent Gateway',
	'MCP runtime',
	'Prompt Center',
	'batch_article_governance',
	'batch_media_optimize',
	'workflow/content_tag_completion',
	'workflow/comment-moderation',
);

$forbidden_runtime_code_markers = array(
	"register_rest_route( 'npcink-governance-core/v1', '/execute",
	"register_rest_route( 'npcink-governance-core/v1', '/proxy-execute",
	"register_rest_route( 'npcink-governance-core/v1', '/jobs",
	"register_rest_route( 'npcink-governance-core/v1', '/tasks",
	"register_rest_route( 'npcink-governance-core/v1', '/runs",
	'provider_credentials',
	'provider_api_key',
	'model_router',
	'model_routing',
	'prompt_preset',
	'workflow_runtime',
	'task_queue',
	'workflow_queue',
	'batch_execution_console',
	'operator_runtime_console',
	'agent_gateway',
	'mcp_server',
);

$forbidden_runtime_name_pattern = '/(^|_)(executor|job_runner|queue|scheduler|worker|workflow_runtime|mcp_server|agent_gateway)($|_)/i';

foreach ( npcink_governance_core_project_files( $root ) as $file ) {
	$relative = str_replace( $root . '/', '', $file );
	$contents = npcink_governance_core_read( $file );

	if ( ! preg_match( '/^(includes|npcink-governance-core\.php)/', $relative ) ) {
		continue;
	}

	foreach ( $forbidden_runtime_terms as $term ) {
		npcink_governance_core_assert( false === strpos( $contents, $term ), $relative . ' must not reintroduce old Core runtime term: ' . $term );
	}

	foreach ( $forbidden_runtime_code_markers as $marker ) {
		npcink_governance_core_assert( false === strpos( $contents, $marker ), $relative . ' must not reintroduce Core runtime ownership marker: ' . $marker );
	}

	$basename = strtolower( pathinfo( $relative, PATHINFO_FILENAME ) );
	npcink_governance_core_assert( 1 !== preg_match( $forbidden_runtime_name_pattern, $basename ), $relative . ' must not introduce a runtime-shaped file name.' );

	if ( preg_match_all( '/\b(?:final\s+)?(?:class|interface|trait)\s+([A-Za-z_][A-Za-z0-9_]*)/', $contents, $matches ) ) {
		foreach ( $matches[1] as $symbol_name ) {
			npcink_governance_core_assert( 1 !== preg_match( $forbidden_runtime_name_pattern, $symbol_name ), $relative . ' must not introduce a runtime-shaped PHP symbol: ' . $symbol_name );
		}
	}
}

