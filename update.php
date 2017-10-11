<?php

/**
 * Main entry point for the updater script. The script may take
 * the following parameters:
 * ...
 */
require_once __DIR__ . '/src/RuntimeStart.php';

$container->get( 'updater' )->run();
