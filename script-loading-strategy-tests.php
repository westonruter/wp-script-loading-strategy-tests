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

const CONTAINER_ELEMENT_ID = 'script-event-log';

/**
 * Gets test cases.
 *
 * @return string[] Test cases with keys being slugs and values being file paths.
 */
function get_test_cases() {
	static $files = null;
	if ( null === $files ) {
		$files = [];
		foreach( glob( __DIR__ . '/cases/*.php' ) as $file ) {
			$id = basename( $file, '.php' );
			$files[ $id ] = $file;
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
 * Checks whether a test is enabled.
 *
 * @param string $test_id Test ID.
 * @return bool Whether test requested.
 */
function is_test_enabled( $test_id ) {
	return (
		! isset( $_GET[ TEST_CASE_QUERY_ARG ][ $test_id ] )
		||
		rest_sanitize_boolean( $_GET[ TEST_CASE_QUERY_ARG ][ $test_id ] )
	);
}

/**
 * Checks if another test is enabled.
 *
 * @param string $test_id Test ID.
 * @return bool Whether another test was requested.
 */
function is_another_test_requested( $test_id ) {
	foreach ( array_diff( array_keys( get_test_cases() ), [ $test_id ] ) as $other_test_id ) {
		if ( is_test_enabled( $other_test_id ) ) {
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
	'wp_head',
	static function () {
		$test_snapshots = [];
		foreach ( get_test_cases() as $test_id => $test_file ) {
			if ( is_test_enabled( $test_id ) ) {
				ob_start();
				require $test_file;
				$test_snapshot_raw = trim( ob_get_clean() );
				if ( empty( $test_snapshot_raw ) ) {
					$test_snapshot = null;
				} else {
					$test_snapshot = preg_split( '/\n/', $test_snapshot_raw );
				}
				$test_snapshots[ $test_id ] = $test_snapshot;
				unset( $test_snapshot );
			}
		}

		?>
		<script>
			const scriptEventLog = [];
			const windowLoadResultValue = 'window.load';
			const domReadyResultValue = 'document.DOMContentLoaded';

			document.addEventListener( 'DOMContentLoaded', () => {
				scriptEventLog.push( domReadyResultValue );
			} );
			window.addEventListener( 'load', () => {
				scriptEventLog.push( windowLoadResultValue );

				const testSnapshots = {};
				Object.assign( testSnapshots, <?php echo wp_json_encode( $test_snapshots ) ?> );
				console.log(testSnapshots)
				const latestTestSnapshotResultIndex = {};

				const seenSnapshotEntries = new Set();

				const ol = document.querySelector( '#script-event-log ol' );
				for ( const entry of scriptEventLog ) {
					const li = document.createElement( 'li' );
					ol.appendChild( li );

					// Include DOMContentLoaded and window load events in the log, but prevent them from being copied since they are not part of the snapshot.
					if ( entry === domReadyResultValue || entry === windowLoadResultValue ) {
						li.inert = true;
						li.textContent = `⏲ ${entry}`;
						continue;
					}

					li.textContent = entry;
					for ( const [ testId, testSnapshot ] of Object.entries( testSnapshots ) ) {
						const index = testSnapshot.indexOf( entry );
						if ( index === -1 ) {
							continue;
						}

						let pass = false;
						if ( testId in latestTestSnapshotResultIndex ) {
							// Verify that this entry is the next entry in the snapshot for this test.
							pass = index === latestTestSnapshotResultIndex[ testId ] + 1;
						} else if ( index === 0 ) {
							// If this is the first time we've encountered an entry from this test's snapshot and it's in the first position, we're good.
							pass = true;
						}
						latestTestSnapshotResultIndex[ testId ] = index;

						const resultSpan = document.createElement( 'span' );
						resultSpan.inert = true;
						resultSpan.textContent = ( pass ? '✅' : '❌' ) + ' ';
						li.prepend( resultSpan );
					}

					// Add edge-case check.
					if ( seenSnapshotEntries.has( entry ) ) {
						const warning = document.createElement( 'em' );
						warning.className = 'warning';
						warning.inert = true;
						warning.textContent = ' Warning! Duplicate snapshot entry encountered!';
						li.append( warning );
					}
					seenSnapshotEntries.add( entry );
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
		$test_ids = array_keys( get_test_cases() );

		?>
		<style>
			#script-event-log {
				margin: 1em;
			}
			#script-event-log .warning {
				color: red;
			}
		</style>
		<div id="<?php echo esc_attr( CONTAINER_ELEMENT_ID ); ?>">
			<h2>Script Loading Strategy Tests</h2>
			<nav>
				<ul>
				<?php foreach ( $test_ids as $test_id ) : ?>
					<?php
					$is_enabled = is_test_enabled( $test_id );
					?>
					<li>
						<?php
						echo ( $is_enabled ? '🟩' : '🟥' ) . ' ';
						echo esc_html( $test_id );
						echo ': ';
						$href = add_query_arg( get_test_case_query_arg( $test_id ), wp_json_encode( ! $is_enabled ) ) . '#' . CONTAINER_ELEMENT_ID;
						?>
						<a
							href="<?php echo esc_attr( esc_url( $href ) ); ?>"
						><?php echo $is_enabled ? 'disable' : 'enable'; ?></a>

						<?php if ( ! $is_enabled || is_another_test_requested( $test_id ) ): ?>
							<?php
							$args = [];
							foreach ( array_diff( $test_ids, [ $test_id ] ) as $other_test_id ) {
								$args[ get_test_case_query_arg( $other_test_id ) ] = 'false';
							}
							$args[ get_test_case_query_arg( $test_id ) ] = 'true';
							$href = add_query_arg( $args ) . '#' . CONTAINER_ELEMENT_ID;

							$label = $is_enabled ? 'enable alone' : 'alone';
							?>
							(<a href="<?php echo esc_attr( esc_url( $href ) ); ?>" title="Enable test case in isolation from others (useful for grabbing snapshot)"><?php echo esc_html( $label ); ?></a>)
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
