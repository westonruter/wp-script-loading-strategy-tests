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

const TEST_CASE_QUERY_ARG = 'test-case';

/**
 * Gets test case files.
 *
 * @return string[] Test cases with keys being slugs and values being file paths.
 */
function get_test_case_files() {
	static $files = null;
	if ( null === $files ) {
		$files = [];
		foreach( glob( __DIR__ . '/cases/*.php' ) as $file ) {
			$slug = basename( $file, '.php' );
			$files[ $slug ] = $file;
		}
	}
	return $files;
}

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
	return (
		! isset( $_GET[ TEST_CASE_QUERY_ARG ][ $test_id ] )
		||
		rest_sanitize_boolean( $_GET[ TEST_CASE_QUERY_ARG ][ $test_id ] )
	);
}

/**
 * Checks if another test was requested.
 *
 * @param string $test_id Test ID.
 * @return bool Whether another test was requested.
 */
function is_another_test_requested( $test_id ) {
	foreach ( array_diff( array_keys( get_test_case_files() ), [ $test_id ] ) as $other_test_id ) {
		if ( is_test_requested( $other_test_id ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Gets the query arg name for a given test.
 *
 * @param string $test_id Test ID.
 * @return string Query arg key.
 */
function get_test_case_query_arg( $test_id ) {
	return sprintf( '%s[%s]', TEST_CASE_QUERY_ARG, $test_id );
}

add_action(
	'init',
	static function () {
		foreach ( get_test_case_files() as $test_slug => $test_file ) {
			if ( is_test_requested( $test_slug ) ) {
				require $test_file;
			}
		}
	}
);

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
		$test_cases = array_keys( get_test_case_files() );

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
				<?php foreach ( $test_cases as $test_id ) : ?>
					<?php
					$is_enabled = is_test_requested( $test_id );
					?>
					<li>
						<?php echo esc_html( $test_id ); ?>:
						<a
							href="<?php echo esc_attr( add_query_arg( get_test_case_query_arg( $test_id ), wp_json_encode( ! $is_enabled ) ) . '#script-event-log' ); ?>"
							title="<?php echo esc_attr( ! $is_enabled ? 'enable' : 'disable' ); ?>"
						><?php echo $is_enabled ? 'enabled' : 'disabled'; ?></a>

						<?php if ( ! $is_enabled || is_another_test_requested( $test_id ) ): ?>
							<?php
							$args = [];
							foreach ( array_diff( $test_cases, [ $test_id ] ) as $other_test_id ) {
								$args[ get_test_case_query_arg( $other_test_id ) ] = 'false';
							}
							$args[ get_test_case_query_arg( $test_id ) ] = 'true';
							?>
							(<a href="<?php echo esc_attr( add_query_arg( $args ) ); ?>'#script-event-log" title="Enable test case in isolation from others">only</a>)
						<?php endif; ?>
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
