<?php

namespace DroidWiki;

use DroidWiki\Updater\CliOptions;
use DroidWiki\Updater\Config;
use GitWrapper\GitWorkingCopy;
use GitWrapper\GitWrapper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UpdaterTest extends TestCase {

	private $logger;
	private $config;

	public function setUp() {
		$this->logger = $this->createMock( LoggerInterface::class );
		$this->config = $this->createMock( Config::class );
		$this->config->method( 'has' )->withAnyParameters()->willReturn( true );
		$this->config->method( 'get' )
			->withAnyParameters()
			->willReturn( 'testValue/' );
	}

	public function testRunNonExistingClones() {
		$cliOptions = $this->createMock( CliOptions::class );
		$cliOptions->method( 'isListUpdate' )->willReturn( false );
		$cliOptions->method( 'getExtensionName' )->willReturn( 'TestNonExisting' );

		$gitWrapper = $this->createMock( GitWrapper::class );
		$workingCopy = $this->createMock( GitWorkingCopy::class );
		$gitWrapper->method( 'cloneRepository' )->willReturn( $workingCopy );
		$gitWrapper->expects( $this->once() )->method( 'cloneRepository' );

		$updater = new Updater( $this->config, $cliOptions, $gitWrapper );
		$updater->setLogger( $this->logger );
		$updater->run();
	}

	public function testRunExistingDoesNotClone() {
		$cliOptions = $this->createMock( CliOptions::class );
		$cliOptions->method( 'isListUpdate' )->willReturn( false );
		$cliOptions->method( 'getExtensionName' )->willReturn( 'TestExisting' );

		$gitWrapper = $this->createMock( GitWrapper::class );
		$workingCopy = $this->createMock( GitWorkingCopy::class );
		$workingCopy->method( 'isCloned' )->willReturn( true );
		$gitWrapper->method( 'cloneRepository' )->willReturn( $workingCopy );
		$workingCopy->method( 'checkout' )->willReturn( $workingCopy );
		$gitWrapper->method( 'workingCopy' )->willReturn( $workingCopy );
		$gitWrapper->expects( $this->never() )->method( 'cloneRepository' );

		$updater = new Updater( $this->config, $cliOptions, $gitWrapper );
		$updater->setLogger( $this->logger );
		$updater->setCwd( __DIR__ );
		$updater->run();
	}

	public function testRunResetWhenChanges() {
		$cliOptions = $this->createMock( CliOptions::class );
		$cliOptions->method( 'isListUpdate' )->willReturn( false );
		$cliOptions->method( 'getExtensionName' )->willReturn( 'TestExisting' );

		$gitWrapper = $this->createMock( GitWrapper::class );
		$workingCopy = $this->createMock( GitWorkingCopy::class );
		$workingCopy->method( 'isCloned' )->willReturn( true );
		$gitWrapper->method( 'cloneRepository' )->willReturn( $workingCopy );
		$workingCopy->method( 'checkout' )->willReturn( $workingCopy );
		$workingCopy->method( 'hasChanges' )->willReturn( true );
		$workingCopy->method( 'reset' )->willReturn( true );
		$workingCopy->method( 'getWrapper' )->willReturn( $gitWrapper );
		$gitWrapper->method( 'workingCopy' )->willReturn( $workingCopy );

		$workingCopy->expects( $this->once() )->method( 'reset' );

		$updater = new Updater( $this->config, $cliOptions, $gitWrapper );
		$updater->setLogger( $this->logger );
		$updater->setCwd( __DIR__ );
		$updater->run();
	}

	public function testRunAddsRemote() {
		$cliOptions = $this->createMock( CliOptions::class );
		$cliOptions->method( 'isListUpdate' )->willReturn( false );
		$cliOptions->method( 'getExtensionName' )->willReturn( 'TestExisting' );

		$gitWrapper = $this->createMock( GitWrapper::class );
		$workingCopy = $this->createMock( GitWorkingCopy::class );
		$workingCopy->method( 'isCloned' )->willReturn( true );
		$gitWrapper->method( 'cloneRepository' )->willReturn( $workingCopy );
		$workingCopy->method( 'checkout' )->willReturn( $workingCopy );
		$gitWrapper->method( 'workingCopy' )->willReturn( $workingCopy );

		$workingCopy->expects( $this->once() )->method( 'addRemote' );

		$updater = new Updater( $this->config, $cliOptions, $gitWrapper );
		$updater->setLogger( $this->logger );
		$updater->run();
	}

	public function testRunUpdatesRemote() {
		$cliOptions = $this->createMock( CliOptions::class );
		$cliOptions->method( 'isListUpdate' )->willReturn( false );
		$cliOptions->method( 'getExtensionName' )->willReturn( 'TestExisting' );

		$gitWrapper = $this->createMock( GitWrapper::class );
		$workingCopy = $this->createMock( GitWorkingCopy::class );
		$workingCopy->method( 'isCloned' )->willReturn( true );
		$gitWrapper->method( 'cloneRepository' )->willReturn( $workingCopy );
		$workingCopy->method( 'checkout' )->willReturn( $workingCopy );
		$workingCopy->method( 'hasRemote' )->willReturn( true );
		$workingCopy->method( 'getRemotes' )->willReturn( [
			Updater::UPSTREAM_REMOTE_NAME => [
				'fetch' => 'test'
			]
		] );
		$gitWrapper->method( 'workingCopy' )->willReturn( $workingCopy );

		$workingCopy->expects( $this->once() )->method( 'removeRemote' );
		$workingCopy->expects( $this->once() )->method( 'addRemote' );

		$updater = new Updater( $this->config, $cliOptions, $gitWrapper );
		$updater->setLogger( $this->logger );
		$updater->run();
	}

	public function testRunNoAddRemoveRemote() {
		$cliOptions = $this->createMock( CliOptions::class );
		$cliOptions->method( 'isListUpdate' )->willReturn( false );
		$cliOptions->method( 'getExtensionName' )->willReturn( 'TestExisting' );

		$gitWrapper = $this->createMock( GitWrapper::class );
		$workingCopy = $this->createMock( GitWorkingCopy::class );
		$workingCopy->method( 'isCloned' )->willReturn( true );
		$gitWrapper->method( 'cloneRepository' )->willReturn( $workingCopy );
		$workingCopy->method( 'checkout' )->willReturn( $workingCopy );
		$workingCopy->method( 'hasRemote' )->willReturn( true );
		$workingCopy->method( 'getRemotes' )->willReturn( [
			Updater::UPSTREAM_REMOTE_NAME => [
				'fetch' => 'testValue/'
			]
		] );
		$gitWrapper->method( 'workingCopy' )->willReturn( $workingCopy );

		$workingCopy->expects( $this->never() )->method( 'removeRemote' );
		$workingCopy->expects( $this->never() )->method( 'addRemote' );

		$updater = new Updater( $this->config, $cliOptions, $gitWrapper );
		$updater->setLogger( $this->logger );
		$updater->run();
	}

	public function testRunTestSingle() {
		$cliOptions = $this->createMock( CliOptions::class );
		$cliOptions->method( 'isListUpdate' )->willReturn( false );
		$cliOptions->method( 'getExtensionName' )->willReturn( 'TestExisting' );
		$cliOptions->method( 'getVersionNumber' )->willReturn( '1.3.0-wmf.1' );

		$gitWrapper = $this->createMock( GitWrapper::class );
		$workingCopy = $this->createMock( GitWorkingCopy::class );
		$workingCopy->method( 'isCloned' )->willReturn( true );
		$gitWrapper->method( 'cloneRepository' )->willReturn( $workingCopy );
		$workingCopy->method( 'checkout' )->willReturn( $workingCopy );
		$workingCopy->method( 'getWrapper' )->willReturn( $gitWrapper );
		$workingCopy->method( 'hasChanges' )->willReturn( true );
		$gitWrapper->method( 'workingCopy' )->willReturn( $workingCopy );

		$workingCopy->expects( $this->once() )->method( 'pull' );
		$workingCopy->expects( $this->exactly( 2 ) )->method( 'checkout' )->with( 'master' );
		$workingCopy->expects( $this->once() )->method( 'commit' );
		$workingCopy->expects( $this->once() )->method( 'merge' )->with(
			'upstream/testValue/1.3.0-wmf.1',
			[
				's' => 'recursive',
				'X' => 'theirs',
				'squash' => true,
			]
		);
		$workingCopy->expects( $this->once() )->method( 'fetch' )->with(
			'upstream', 'testValue/1.3.0-wmf.1' );

		$updater = new Updater( $this->config, $cliOptions, $gitWrapper );
		$updater->setLogger( $this->logger );
		$updater->setCwd( __DIR__ );
		$updater->run();
	}

	public function testRunNoChangesNoCommit() {
		$cliOptions = $this->createMock( CliOptions::class );
		$cliOptions->method( 'isListUpdate' )->willReturn( false );
		$cliOptions->method( 'getExtensionName' )->willReturn( 'TestExisting' );
		$cliOptions->method( 'getVersionNumber' )->willReturn( '1.3.0-wmf.1' );

		$gitWrapper = $this->createMock( GitWrapper::class );
		$workingCopy = $this->createMock( GitWorkingCopy::class );
		$workingCopy->method( 'isCloned' )->willReturn( true );
		$gitWrapper->method( 'cloneRepository' )->willReturn( $workingCopy );
		$workingCopy->method( 'checkout' )->willReturn( $workingCopy );
		$workingCopy->method( 'getWrapper' )->willReturn( $gitWrapper );
		$workingCopy->method( 'hasChanges' )->willReturn( false );
		$gitWrapper->method( 'workingCopy' )->willReturn( $workingCopy );

		$workingCopy->expects( $this->once() )->method( 'pull' );
		$workingCopy->expects( $this->exactly( 2 ) )->method( 'checkout' )->with( 'master' );
		$workingCopy->expects( $this->never() )->method( 'commit' );
		$workingCopy->expects( $this->once() )->method( 'merge' )->with(
			'upstream/testValue/1.3.0-wmf.1',
			[
				's' => 'recursive',
				'X' => 'theirs',
				'squash' => true,
			]
		);
		$workingCopy->expects( $this->once() )->method( 'fetch' )->with(
			'upstream', 'testValue/1.3.0-wmf.1' );

		$updater = new Updater( $this->config, $cliOptions, $gitWrapper );
		$updater->setLogger( $this->logger );
		$updater->setCwd( __DIR__ );
		$updater->run();
	}
}
