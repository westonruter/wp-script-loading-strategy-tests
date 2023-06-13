<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {
	enqueue_test_script_with_inline_scripts( 'blocking-dependency-with-defer-following-dependency', 'blocking', [] );
	enqueue_test_script_with_inline_scripts( 'defer-dependency-with-blocking-preceding-dependency', 'defer', [] );
	enqueue_test_script_with_inline_scripts( 'defer-dependent-of-blocking-and-defer-dependencies', 'defer', [ 'blocking-dependency-with-defer-following-dependency', 'defer-dependency-with-blocking-preceding-dependency' ] );
} );

// Snapshot of output below:
?>
blocking-dependency-with-defer-following-dependency: before inline
blocking-dependency-with-defer-following-dependency: script
blocking-dependency-with-defer-following-dependency: after inline
defer-dependency-with-blocking-preceding-dependency: before inline
defer-dependency-with-blocking-preceding-dependency: script
defer-dependency-with-blocking-preceding-dependency: after inline
defer-dependent-of-blocking-and-defer-dependencies: before inline
defer-dependent-of-blocking-and-defer-dependencies: script
defer-dependent-of-blocking-and-defer-dependencies: after inline
