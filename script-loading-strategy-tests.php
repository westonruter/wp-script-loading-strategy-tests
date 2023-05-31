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

add_action(
	'wp_head',
	static function () {
		?>
		<script>
			const scriptEventLog = [];
			document.addEventListener( 'DOMContentLoaded', () => {
				scriptEventLog.push( 'document.DOMContentLoaded' );
			} );
			window.addEventListener( 'load', () => {
				scriptEventLog.push( 'window.load' );

				const ol = document.querySelector( '#script-event-log ol' );
				for ( const entry of scriptEventLog ) {
					const li = document.createElement( 'li' );
					li.textContent = entry;
					ol.appendChild( li );
				}
			} );
		</script>
		<?php
	},
	0
);

add_action(
	'wp_footer',
	static function () {
		?>
		<style>
		#script-event-log {
			margin: 1em;
		}
		</style>
		<div id="script-event-log">
			<h2>Script Event Log</h2>
			<ol></ol>
		</div>
		<?php
	}
);

add_action( 'wp_enqueue_scripts', static function () {
	foreach ( [ 'blocking', 'async', 'defer' ] as $strategy ) {
		$handle = "{$strategy}-head";
		wp_enqueue_script(
			$handle,
			add_query_arg(
				[
					'script_event_log' => "$handle: script",
				],
				plugin_dir_url( __FILE__ ) . 'external.js'
			),
			[]
		);
		if ( 'blocking' !== $strategy ) {
			wp_script_add_data( $handle, 'strategy', $strategy );
		}
		wp_add_inline_script( $handle, sprintf( 'scriptEventLog.push( %s )', wp_json_encode( "{$handle}: before inline" ) ), 'before' );
		wp_add_inline_script( $handle, sprintf( 'scriptEventLog.push( %s )', wp_json_encode( "{$handle}: after inline" ) ), 'after' );
	}
} );
