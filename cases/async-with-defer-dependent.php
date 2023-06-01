<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {
	enqueue_test_script( 'async-with-defer-dependent', 'async', [] );
	enqueue_test_script( 'defer-dependent-of-async', 'defer', [ 'async-with-defer-dependent' ] );
} );

// Snapshot of output below:
?>
async-with-defer-dependent: before inline
async-with-defer-dependent: script
async-with-defer-dependent: after inline
defer-dependent-of-async: before inline
defer-dependent-of-async: script
defer-dependent-of-async: after inline
document.DOMContentLoaded
window.load
