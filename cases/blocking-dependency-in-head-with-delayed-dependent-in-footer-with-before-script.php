<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {
	enqueue_test_script( 'blocking-dependency-in-head-for-delayed-footer-dependent-with-before-inline-script', 'blocking', [] );
	enqueue_test_script( 'deferred-dependent-in-footer-with-before-inline-script-for-blocking-head-dependency', 'defer', [ 'blocking-dependency-in-head-for-delayed-footer-dependent-with-before-inline-script' ], true );
	add_test_inline_script( 'deferred-dependent-in-footer-with-before-inline-script-for-blocking-head-dependency', 'before' );
} );

// Snapshot of output below:
?>
blocking-dependency-in-head-for-delayed-footer-dependent-with-before-inline-script: script
deferred-dependent-in-footer-with-before-inline-script-for-blocking-head-dependency: before inline
deferred-dependent-in-footer-with-before-inline-script-for-blocking-head-dependency: script
