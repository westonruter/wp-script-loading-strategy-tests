<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {
	enqueue_test_script( 'defer-dependency-with-blocking-following-dependency', 'defer', [] );
	enqueue_test_script( 'blocking-dependency-with-defer-preceding-dependency', 'blocking', [] );
	enqueue_test_script( 'defer-dependent-of-blocking-and-defer-dependencies', 'defer', [ 'defer-dependency-with-blocking-following-dependency', 'blocking-dependency-with-defer-preceding-dependency' ] );
} );

// Snapshot of output below:
?>
defer-dependency-with-blocking-following-dependency: before inline
blocking-dependency-with-defer-preceding-dependency: before inline
blocking-dependency-with-defer-preceding-dependency: script
blocking-dependency-with-defer-preceding-dependency: after inline
defer-dependency-with-blocking-following-dependency: script
defer-dependency-with-blocking-following-dependency: after inline
defer-dependent-of-blocking-and-defer-dependencies: before inline
defer-dependent-of-blocking-and-defer-dependencies: script
defer-dependent-of-blocking-and-defer-dependencies: after inline
