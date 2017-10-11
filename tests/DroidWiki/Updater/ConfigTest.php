<?php

namespace DroidWiki\Updater;

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase {
	private $testConfig = [
		'test' => true,
	];

	/**
	 * @var Config
	 */
	private $initializedConfig;

	public function setUp() {
		$this->initializedConfig = new Config( $this->testConfig );
	}

	public function testArrayConstructor() {
		$config = new Config( $this->testConfig );
		$this->assertTrue( $config->has( 'test' ),
			'Initializing a Config object with an array should contain the config options of that array.' );
	}

	public function testHasNonExisting() {
		$this->assertFalse( $this->initializedConfig->has( 'test2' ),
			'#has() should return false for non-existing configurations.' );
	}

	public function testHasExisting() {
		$this->assertFalse( $this->initializedConfig->has( 'test2' ),
			'#has() should return false for non-existing configurations.' );
	}

	public function testGetExisting() {
		$this->assertTrue( $this->initializedConfig->get( 'test' ),
			'Requesting an existing config option should return it\'s value.' );
	}

	public function testGetNonExisting() {
		$this->expectException( ConfigNotFoundException::class );
		$this->initializedConfig->get( 'nonExisting' );
	}

	public function testGetNonExistingWithDefault() {
		$this->assertEquals( 'defaultValue', $this->initializedConfig->get( 'nonExisting',
			'defaultValue' ),
			'Requesting a non-existing config value with a default value should return ' .
			'the default value.' );
	}

	public function testGetExistingWithDefault() {
		$this->assertTrue( $this->initializedConfig->get( 'test', 'defaultValue' ),
			'Requesting an existing config value with a default value should return ' .
			'the config value.' );
	}
}
