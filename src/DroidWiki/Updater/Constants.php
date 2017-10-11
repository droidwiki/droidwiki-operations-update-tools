<?php

namespace DroidWiki\Updater;

/**
 * This class is not meant to be instantiated in any way and simply holds constants of
 * values that needs to be available before the Config object is initialized.
 */
final class Constants {
	const INSTALL_PATH = __DIR__ . '/../../../';
	const CONFIG_PATH = self::INSTALL_PATH . 'config.json';

	private function __construct() {
		throw new \Exception();
	}
}
