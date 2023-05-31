<?php
/**
 * Optimize Loading Separate Core Block Assets in Classic Themes Plugin.
 *
 * @package OptimizeLoadingSeparateCoreBlockAssetsInClassicThemes
 * @author Weston Ruter
 * @link https://github.com/westonruter/wp-script-loading-strategy-tests
 * @license GPL-2.0-or-later
 * @copyright 2023 Google Inc.
 *
 * @wordpress-plugin
 * Plugin Name: Script Loading Strategy Tests
 * Plugin URI: https://github.com/westonruter/wp-script-loading-strategy-tests
 * Description: Demo plugin that puts together various scenarios to test the script loading strategies proposed for WordPress core.
 * Version: 0.1.0
 * Author: Weston Ruter
 * Author URI: https://weston.ruter.net/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 5.6
 * Update URI: false
 */

namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {

	foreach ( [ '', 'async', 'defer' ] as $strategy ) {

		wp_enqueue_script(
			$strategy,
			plugin_dir_url( __FILE__ ) . "{$strategy}.js",
			[],
			false,
			[ 'strategy' => $strategy ]
		);
		wp_add_inline_script(
			$strategy,
			sprintf( 'console.log( %s );', wp_json_encode( "$strategy before standalone!" ) ),
			'before',
			false
		);
		wp_add_inline_script(
			$strategy,
			sprintf( 'console.log( %s );', wp_json_encode( "$strategy after standalone!" ) ),
			'after',
			false
		);
	}

//
//	wp_enqueue_script(
//		'async',
//		plugin_dir_url( __FILE__ ) . '/async.js',
//		[],
//		false,
//		[
//			'strategy' => 'async',
//			'in_footer' => true,
//		]
//	);
//	wp_enqueue_script(
//		'defer',
//		plugin_dir_url( __FILE__ ) . '/defer.js',
//		[],
//		false,
//		[ 'strategy' => 'async' ]
//	);

} );
