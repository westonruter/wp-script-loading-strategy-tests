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

const DISABLE_DELAYED_STRATEGIES_QUERY_ARG = 'disable-delayed-strategies';

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
 * Register test script.
 *
 * @param string   $handle    Dependency handle to enqueue.
 * @param string   $strategy  Strategy to use for dependency.
 * @param string[] $deps      Dependencies for the script.
 * @param bool     $in_footer Whether to print the script in the footer.
 */
function register_test_script( $handle, $strategy, $deps = [], $in_footer = false ) {
	wp_register_script(
		$handle,
		add_query_arg(
			[
				'script_event_log' => "$handle: script",
			],
			plugin_dir_url( __FILE__ ) . 'external.js'
		),
		$deps,
		null,
		$in_footer
	);
	if ( 'blocking' !== $strategy && ! are_delayed_strategies_disabled() ) {
		wp_script_add_data( $handle, 'strategy', $strategy );
	}
}

/**
 * Enqueue test script.
 *
 * @param string   $handle    Dependency handle to enqueue.
 * @param string   $strategy  Strategy to use for dependency.
 * @param string[] $deps      Dependencies for the script.
 * @param bool     $in_footer Whether to print the script in the footer.
 */
function enqueue_test_script( $handle, $strategy, $deps = [], $in_footer = false ) {
	register_test_script( $handle, $strategy, $deps, $in_footer );
	wp_enqueue_script( $handle );
}

/**
 * Enqueue test script with before/after inline scripts.
 *
 * @param string   $handle    Dependency handle to enqueue.
 * @param string   $strategy  Strategy to use for dependency.
 * @param string[] $deps      Dependencies for the script.
 * @param bool     $in_footer Whether to print the script in the footer.
 */
function enqueue_test_script_with_inline_scripts( $handle, $strategy, $deps = [], $in_footer = false ) {
	enqueue_test_script( $handle, $strategy, $deps, $in_footer );
	add_test_inline_script( $handle, 'before' );
	add_test_inline_script( $handle, 'after' );
}

/**
 * Adds test inline script.
 *
 * @param string $handle   Dependency handle to enqueue.
 * @param string $position Position.
 */
function add_test_inline_script( $handle, $position ) {
	wp_add_inline_script( $handle, sprintf( 'scriptEventLog.push( %s )', wp_json_encode( "{$handle}: {$position} inline" ) ), $position );
}

/**
 * Checks whether a test is enabled.
 *
 * @param string $test_id Test ID.
 * @return bool Whether test requested.
 */
function is_test_enabled( $test_id ) {
	// All enabled by default.
	if ( empty( $_GET[ TEST_CASE_QUERY_ARG ] ) ) {
		return true;
	}
	return (
		isset( $_GET[ TEST_CASE_QUERY_ARG ][ $test_id ] )
		&&
		rest_sanitize_boolean( $_GET[ TEST_CASE_QUERY_ARG ][ $test_id ] )
	);
}

/**
 * Checks whether delayed strategies are disabled.
 *
 * @return bool Whether disabled.
 */
function are_delayed_strategies_disabled() {
	return isset( $_GET[ DISABLE_DELAYED_STRATEGIES_QUERY_ARG ] ) && rest_sanitize_boolean( $_GET[ DISABLE_DELAYED_STRATEGIES_QUERY_ARG ] );
}

/**
 * Get enabled tests.
 *
 * @return string[] Test IDs.
 */
function get_enabled_tests() {
	return array_values(
		array_filter(
			array_keys( get_test_cases() ),
			static function ( $test_id ) {
				return is_test_enabled( $test_id );
			}
		)
	);
}

/**
 * Checks if another test is enabled.
 *
 * @param string $test_id Test ID.
 * @return bool Whether another test was requested.
 */
function is_another_test_enabled( $test_id ) {
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
					$test_snapshot = preg_split( '/\n+/', $test_snapshot_raw );
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
				const latestTestSnapshotResultIndex = {};
				const enabledTests = [];
				Array.prototype.push.apply( enabledTests, <?php echo wp_json_encode( get_enabled_tests() ); ?> );

				const seenSnapshotEntries = new Set();

				const ol = document.querySelector( '#script-event-log ol' );
				for ( const entry of scriptEventLog ) {
					const li = document.createElement( 'li' );
					ol.appendChild( li );

					// Include DOMContentLoaded and window load events in the log, but prevent them from being copied since they are not part of the snapshot.
					if ( entry === domReadyResultValue || entry === windowLoadResultValue ) {
						li.inert = true;
						li.textContent = `${entry}`;
						const emoji = document.createElement( 'span' );
						emoji.className = 'emoji';
						emoji.textContent = '⏲ ';
						li.prepend( emoji );
						continue;
					}

					let matchedCount = 0;
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
						resultSpan.className = 'emoji';
						resultSpan.textContent = ( pass ? '✅' : '❌' );
						li.prepend( document.createTextNode( ' ' ) );
						li.prepend( resultSpan );

						const caseId = document.createElement( 'code' );
						caseId.inert = true;
						caseId.className = 'test-id';
						caseId.textContent = testId;
						li.append( caseId );
						matchedCount++;
					}

					if ( matchedCount !== 1 || seenSnapshotEntries.has( entry ) ) {
						const resultSpan = document.createElement( 'span' );
						resultSpan.inert = true;
						resultSpan.className = 'emoji';
						resultSpan.textContent = '⚠ ';
						li.prepend( resultSpan );

						const warning = document.createElement( 'em' );
						warning.className = 'warning';
						warning.inert = true;
						li.append( warning );
						if ( matchedCount === 0 ) {
							warning.textContent = ' Warning! Entry not contained in snapshot!';
						} else if ( matchedCount > 1 ) {
							warning.textContent = ' Warning! Entry contained in multiple snapshots!';
						} else {
							warning.textContent = ' Warning! Duplicate snapshot entry encountered!';
						}
					}

					seenSnapshotEntries.add( entry );
				}

				for ( const enabledTestId of enabledTests ) {
					for ( const snapshotEntry of testSnapshots[ enabledTestId ] ) {
						if ( -1 === scriptEventLog.indexOf( snapshotEntry ) ) {
							const li = document.createElement( 'li' );
							li.className = 'error';
							li.inert = true;
							li.textContent = ` Error: Missing '${snapshotEntry}' snapshot entry for test '${enabledTestId}'`;
							const emoji = document.createElement( 'span' );
							emoji.className = 'emoji';
							emoji.textContent = '⚠ ';
							li.prepend( emoji );
							ol.appendChild(li);
						}
					}
				}

				const nextAloneLink = document.getElementById( 'script-loading-strategy-next-alone-link' );
				if ( nextAloneLink ) {
					nextAloneLink.focus();
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
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Noto+Color+Emoji">
		<style>
			#script-event-log {
				margin: 1em;
			}
			#script-event-log .warning {
				color: orange;
			}
			#script-event-log .error {
				color: red;
			}
			#script-event-log .emoji {
				font-family: "Noto Color Emoji";
			}
			#script-event-log .test-id {
				font-size: small;
				padding: 3px;
				border-radius: 6px;
				margin-left: 2ex;
				color: #fff;
				background: #999;
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
						echo ( $is_enabled ? '<span class="emoji">🟩</span>' : '<span class="emoji">⬜</span>' ) . ' ';
						echo esc_html( $test_id );
						echo ': ';
						$href = add_query_arg( get_test_case_query_arg( $test_id ), wp_json_encode( ! $is_enabled ) ) . '#' . CONTAINER_ELEMENT_ID;
						?>
						<a
							href="<?php echo esc_attr( esc_url( $href ) ); ?>"
						><?php echo $is_enabled ? 'disable' : 'enable'; ?></a>

						<?php if ( ! $is_enabled || is_another_test_enabled( $test_id ) ): ?>
							<?php
							$href  = add_query_arg( get_test_case_query_arg( $test_id ), 'true', remove_query_arg( TEST_CASE_QUERY_ARG ) ) . '#' . CONTAINER_ELEMENT_ID;
							$label = $is_enabled ? 'enable alone' : 'alone';
							?>
							(<a href="<?php echo esc_attr( esc_url( $href ) ); ?>" title="Enable test case in isolation from others (useful for grabbing snapshot)"><?php echo esc_html( $label ); ?></a>)
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
				</ul>
			</nav>

			<p>
				<?php if ( isset( $_GET[ TEST_CASE_QUERY_ARG ] ) ) : ?>
					<?php
					$all_tests     = array_keys( get_test_cases() );
					$enabled_tests = get_enabled_tests();
					if ( count( $enabled_tests ) === 1 ) {
						$index = array_search( $enabled_tests[0], $all_tests );
						if ( $index < count( $all_tests ) - 1 ) {
							$next_alone_test = $all_tests[ $index + 1 ];
							printf(
								'<a href="%s" id="script-loading-strategy-next-alone-link">Next alone: %s</a>',
								esc_url(
									add_query_arg(
										[
											get_test_case_query_arg( $next_alone_test ) => 'true',
										],
										remove_query_arg( TEST_CASE_QUERY_ARG )
									) . '#' . CONTAINER_ELEMENT_ID
								),
								$next_alone_test
							);
							echo ' | ';
						}
					}
					?>
					<?php if ( count( $enabled_tests ) === 1 ) : ?>
					<?php endif; ?>
					<a href="<?php echo esc_attr( esc_url( remove_query_arg( TEST_CASE_QUERY_ARG ) . '#' . CONTAINER_ELEMENT_ID ) ); ?>">Enable all</a>
					|
				<?php endif; ?>
				<a href="<?php echo esc_url( add_query_arg( DISABLE_DELAYED_STRATEGIES_QUERY_ARG, wp_json_encode( ! are_delayed_strategies_disabled() ) ) . '#' . CONTAINER_ELEMENT_ID ); ?>"><?php echo are_delayed_strategies_disabled() ? 'Enable delayed strategies' : 'Disable delayed strategies'; ?></a>
			</p>

			Test Results:
			<ol></ol>
		</div>
		<?php
	}
);
