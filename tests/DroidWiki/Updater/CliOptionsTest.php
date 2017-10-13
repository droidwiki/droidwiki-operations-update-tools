<?php

namespace DroidWiki\Updater;

use GetOpt\ArgumentException\Missing;
use GetOpt\ArgumentException\Unexpected;
use GetOpt\GetOpt;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CliOptionsTest extends TestCase {
	private $testOptions = [
		'-v',
		'10',
	];

	private $expectedExtensionList = [
		'TestExtension1',
		'TestExtension2',
	];

	/**
	 * @var CliOptions
	 */
	private $initializedCliOptions;

	private $logger;

	public function setUp() {
		$this->logger = $this->createMock( LoggerInterface::class );
		$this->initializedCliOptions = new CliOptions( new GetOpt() );
		$this->initializedCliOptions->initOptions( $this->testOptions );
		$this->initializedCliOptions->setLogger( $this->logger );
	}

	public function testShortGetVersionNumber() {
		$this->assertEquals( '10', $this->initializedCliOptions->getVersionNumber() );
	}

	public function testVersionISrequired() {
		$this->expectException( Missing::class );
		$cliOptions = new CliOptions( new GetOpt() );
		$cliOptions->setLogger( $this->logger );
		$cliOptions->initOptions( [] );
	}

	public function testLongGetVersionNumber() {
		$cliOptions = new CliOptions( new GetOpt() );
		$cliOptions->initOptions( [
			'--version',
			'10',
		] );
		$cliOptions->setLogger( $this->logger );
		$this->assertEquals( '10', $cliOptions->getVersionNumber() );
	}

	public function testUnknownArgument() {
		$this->expectException( Unexpected::class );
		$cliOptions = new CliOptions( new GetOpt() );
		$cliOptions->setLogger( $this->logger );
		$cliOptions->initOptions( [
			'--unknown',
		] );
	}

	public function testIsListUpdateWithOutArgument() {
		$this->assertFalse( $this->initializedCliOptions->isListUpdate() );
	}

	public function testIsListUpdateShort() {
		$cliOptions = new CliOptions( new GetOpt() );
		$cliOptions->setLogger( $this->logger );
		$cliOptions->initOptions( [
			'-l',
			'extensions.list',
			'-v',
			'10',
		] );
		$this->assertTrue( $cliOptions->isListUpdate() );
	}

	public function testIsListUpdateLong() {
		$cliOptions = new CliOptions( new GetOpt() );
		$cliOptions->setLogger( $this->logger );
		$cliOptions->initOptions( [
			'--list',
			'extensions.list',
			'-v',
			'10',
		] );
		$this->assertTrue( $cliOptions->isListUpdate() );
	}

	public function testGetListExceptionIsListUpdateFalse() {
		$this->expectException( NoListUpdateException::class );
		$cliOptions = new CliOptions( new GetOpt() );
		$cliOptions->setLogger( $this->logger );
		$this->assertFalse( $cliOptions->isListUpdate() );
		$cliOptions->getList();
	}

	public function testGetListShort() {
		$cliOptions = new CliOptions( new GetOpt() );
		$cliOptions->setLogger( $this->logger );
		$cliOptions->initOptions( [
			'-l',
			'tests/extensions.list',
			'-v',
			'10',
		] );
		$this->assertEquals( $this->expectedExtensionList, $cliOptions->getList() );
	}

	public function testGetListLong() {
		$cliOptions = new CliOptions( new GetOpt() );
		$cliOptions->setLogger( $this->logger );
		$cliOptions->initOptions( [
			'--list',
			'tests/extensions.list',
			'-v',
			'10',
		] );
		$this->assertEquals( $this->expectedExtensionList, $cliOptions->getList() );
	}

	public function testGetExtensionNameIsListUpdateTrue() {
		$this->expectException( NoSingleExtensionUpdateException::class );
		$cliOptions = new CliOptions( new GetOpt() );
		$cliOptions->setLogger( $this->logger );
		$cliOptions->initOptions( [
			'-l',
			'tests/extensions.list',
			'-v',
			'10',
		] );
		$this->assertTrue( $cliOptions->isListUpdate() );
		$cliOptions->getExtensionName();
	}

	public function testGetExtensionNameIsListUpdateFalse() {
		$cliOptions = new CliOptions( new GetOpt() );
		$cliOptions->setLogger( $this->logger );
		$this->assertFalse( $cliOptions->isListUpdate() );
		$cliOptions->getExtensionName();
	}

	public function testGetExtensionName() {
		$cliOptions = new CliOptions( new GetOpt() );
		$cliOptions->setLogger( $this->logger );
		$cliOptions->initOptions( [
			'TestExtension',
			'-v',
			'10',
		] );
		$this->assertEquals( 'TestExtension', $cliOptions->getExtensionName() );
	}

	public function testGetConfigPathNotSet() {
		$cliOptions = new CliOptions( new GetOpt() );
		$cliOptions->setLogger( $this->logger );
		$cliOptions->initOptions( [
			'TestExtension',
			'-v',
			'10',
		] );
		$this->assertNull(
			$cliOptions->getConfigPath(),
			'If not config option was set, null should be returned for the config path'
		);
	}

	public function testGetConfigPathShortName() {
		$cliOptions = new CliOptions( new GetOpt() );
		$cliOptions->setLogger( $this->logger );
		$cliOptions->setCwd( __DIR__ . '/../..' );
		$cliOptions->initOptions( [
			'TestExtension',
			'-v',
			'10',
			'-c',
			'localConfigPath.json',
		] );
		$this->assertEquals(
			__DIR__ . '/../../localConfigPath.json',
			$cliOptions->getConfigPath(),
			'Setting a config path with the short -c option should return this config path.'
		);
	}

	public function testGetConfigPathLongName() {
		$cliOptions = new CliOptions( new GetOpt() );
		$cliOptions->setLogger( $this->logger );
		$cliOptions->setCwd( __DIR__ . '/../..' );
		$cliOptions->initOptions( [
			'TestExtension',
			'-v',
			'10',
			'--config',
			'localConfigPath.json',
		] );
		$this->assertEquals(
			__DIR__ . '/../../localConfigPath.json',
			$cliOptions->getConfigPath(),
			'Setting a config path with the long -config option should return this config path.'
		);
	}
}
