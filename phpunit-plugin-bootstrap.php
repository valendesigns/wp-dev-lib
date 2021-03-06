<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( empty( $_tests_dir ) ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/' ) ) {
	trigger_error( 'Unable to locate wordpress-tests-lib', E_USER_ERROR );
}
require_once $_tests_dir . '/includes/functions.php';

$_plugin_dir = dirname( __DIR__ );
foreach ( glob( $_plugin_dir . '/*.php' ) as $_plugin_file_candidate ) {
	// @codingStandardsIgnoreStart
	$_plugin_file_src = file_get_contents( $_plugin_file_candidate );
	// @codingStandardsIgnoreEnd
	if ( preg_match( '/Plugin\s*Name\s*:/', $_plugin_file_src ) ) {
		$_plugin_file = $_plugin_file_candidate;
		break;
	}
}
if ( ! isset( $_plugin_file ) ) {
	trigger_error( 'Unable to locate a file containing a plugin metadata block.', E_USER_ERROR );
}
unset( $_plugin_dir, $_plugin_file_candidate, $_plugin_file_src );

/**
 * Force plugins defined in a constant (supplied by phpunit.xml) to be active at runtime.
 *
 * @filter site_option_active_sitewide_plugins
 * @filter option_active_plugins
 *
 * @param array $active_plugins
 * @return array
 */
function xwp_filter_active_plugins_for_phpunit( $active_plugins ) {
	$forced_active_plugins = array();
	if ( file_exists( WP_CONTENT_DIR . '/themes/vip/plugins/vip-init.php' ) && defined( 'WP_TEST_VIP_QUICKSTART_ACTIVATED_PLUGINS' ) ) {
		$forced_active_plugins = preg_split( '/\s*,\s*/', WP_TEST_VIP_QUICKSTART_ACTIVATED_PLUGINS );
	} else if ( defined( 'WP_TEST_ACTIVATED_PLUGINS' ) ) {
		$forced_active_plugins = preg_split( '/\s*,\s*/', WP_TEST_ACTIVATED_PLUGINS );
	}
	if ( ! empty( $forced_active_plugins ) ) {
		foreach ( $forced_active_plugins as $forced_active_plugin ) {
			$active_plugins[ "$forced_active_plugin" ] = time();
		}
	}
	return $active_plugins;
}
tests_add_filter( 'site_option_active_sitewide_plugins', 'xwp_filter_active_plugins_for_phpunit' );
tests_add_filter( 'option_active_plugins', 'xwp_filter_active_plugins_for_phpunit' );


tests_add_filter( 'muplugins_loaded', function () use ( $_plugin_file ) {
	// Force vip-init.php to be loaded on VIP quickstart
	if ( file_exists( WP_CONTENT_DIR . '/themes/vip/plugins/vip-init.php' ) ) {
		require_once( WP_CONTENT_DIR . '/themes/vip/plugins/vip-init.php' );
	}

	// Load this plugin
	require_once $_plugin_file;
} );

require $_tests_dir . '/includes/bootstrap.php';
