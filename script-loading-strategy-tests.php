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

/**
 * Enqueue test script with before/after inline scripts.
 *
 * @param string   $handle    Dependency handle to enqueue.
 * @param string   $strategy  Strategy to use for dependency.
 * @param string[] $deps      Dependencies for the script.
 * @param bool     $in_footer Whether to print the script in the footer.
 */
function enqueue_test_script( $handle, $strategy, $deps = [], $in_footer = false ) {
	wp_enqueue_script(
		$handle,
		add_query_arg(
			[
				'script_event_log' => "$handle: script",
			],
			plugin_dir_url( __FILE__ ) . 'external.js'
		),
		$deps
	);
	if ( 'blocking' !== $strategy ) {
		wp_script_add_data( $handle, 'strategy', $strategy );
	}
	wp_add_inline_script( $handle, sprintf( 'scriptEventLog.push( %s )', wp_json_encode( "{$handle}: before inline" ) ), 'before' );
	wp_add_inline_script( $handle, sprintf( 'scriptEventLog.push( %s )', wp_json_encode( "{$handle}: after inline" ) ), 'after' );
}

/**
 * Checks whether a test is requested.
 *
 * @param string $test_id Test ID.
 * @return bool Whether test requested.
 */
function is_test_requested( $test_id ) {
	return ! isset( $_GET[ $test_id ] ) || rest_sanitize_boolean( $_GET[ $test_id ] );
}

const TEST_ASYNC_WITH_ASYNC_DEPENDENCIES = 'async-with-async-dependencies';
add_action( 'wp_enqueue_scripts', static function () {
	if ( is_test_requested( TEST_ASYNC_WITH_ASYNC_DEPENDENCIES ) ) {
		enqueue_test_script( 'async-no-dependency', 'async', [] );
		enqueue_test_script( 'async-one-async-dependency', 'async', [ 'async-no-dependency' ] );
		enqueue_test_script( 'async-two-async-dependencies', 'async', [ 'async-no-dependency', 'async-one-async-dependency' ] );
	}
} );

const TEST_BLOCKING_WITH_ASYNC_DEPENDENCY = 'blocking-with-async-dependency';
add_action( 'wp_enqueue_scripts', static function () {
	if ( is_test_requested( TEST_BLOCKING_WITH_ASYNC_DEPENDENCY ) ) {
		enqueue_test_script( 'blocking-not-async-without-dependency', 'blocking', [] );
		enqueue_test_script( 'async-with-blocking-dependency', 'async', [ 'blocking-not-async-without-dependency' ] );
	}
} );

const TEST_ASYNC_WITH_BLOCKING_DEPENDENCY = 'async-with-blocking-dependency';
add_action( 'wp_enqueue_scripts', static function () {
	if ( is_test_requested( TEST_ASYNC_WITH_BLOCKING_DEPENDENCY ) ) {
		enqueue_test_script( 'async-with-blocking-dependent', 'async', [] );
		enqueue_test_script( 'blocking-dependent-of-async', 'blocking', [ 'async-with-blocking-dependent' ] );
	}
} );

const TEST_ASYNC_WITH_DEFER_DEPENDENT = 'async-with-defer-dependent';
add_action( 'wp_enqueue_scripts', static function () {
	if ( is_test_requested( TEST_ASYNC_WITH_DEFER_DEPENDENT ) ) {
		enqueue_test_script( 'async-with-defer-dependent', 'async', [] );
		enqueue_test_script( 'defer-dependent-of-async', 'defer', [ 'async-with-defer-dependent' ] );
	}
} );

const TESTS = [
	TEST_ASYNC_WITH_ASYNC_DEPENDENCIES,
	TEST_BLOCKING_WITH_ASYNC_DEPENDENCY,
	TEST_ASYNC_WITH_BLOCKING_DEPENDENCY,
	TEST_ASYNC_WITH_DEFER_DEPENDENT,
];

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
			<h2>Script Loading Strategy Tests</h2>
			<nav>
				<ul>
				<?php foreach ( TESTS as $test ) : ?>
					<li>
						<?php echo esc_html( $test ); ?>:
						<a href="<?php echo esc_attr( add_query_arg( $test, wp_json_encode( ! is_test_requested( $test ) ) ) . '#script-event-log' ); ?>" title="<?php echo esc_attr( ! is_test_requested( $test ) ? 'disable' : 'enable' ); ?>">
							<?php echo is_test_requested( $test ) ? 'enabled' : 'disabled'; ?>
						</a>
					</li>
				<?php endforeach; ?>
				</ul>
			</nav>
			Test Results:
			<ol></ol>
		</div>
		<?php
	}
);
