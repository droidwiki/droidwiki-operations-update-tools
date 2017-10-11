<?php

namespace DroidWiki;

use DroidWiki\Updater\CliOptions;
use DroidWiki\Updater\Config;
use GitWrapper\GitWorkingCopy;
use GitWrapper\GitWrapper;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class Updater implements LoggerAwareInterface {
	const UPSTREAM_REMOTE_NAME = 'upstream';
	const COMMIT_MSG_TEMPLATE = 'Update %s to %s

Forward to %s';

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @var CliOptions
	 */
	private $cliOptions;

	/**
	 * @var GitWrapper
	 */
	private $gitWrapper;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var bool
	 */
	private $needsPush = true;

	/**
	 * @var string
	 */
	private $extensionName;

	/**
	 * @var string
	 */
	private $cwd;

	public function __construct( Config $config, CliOptions $options, GitWrapper $gitWrapper ) {
		$this->config = $config;
		$this->cliOptions = $options;
		$this->gitWrapper = $gitWrapper;
	}

	public function run() {
		if ( $this->cliOptions->isListUpdate() ) {
			$this->getLogger()->debug( 'Got list of extension to update...' );
			$this->updateList();
		} else {
			$this->getLogger()->debug( 'Got one extension to update...' );
			$this->update( $this->cliOptions->getExtensionName() );
		}
	}

	private function updateList() {
		$list = $this->cliOptions->getList();

		foreach ( $list as $extName ) {
			$this->update( $extName );
		}
	}

	private function update( $extName ) {
		$this->needsPush = true;
		$this->extensionName = $extName;

		$this->getLogger()->info( '---Start updating extension {name}...+++',
			[ 'name' => $extName ] );

		$git = $this->getGitFromExtensionName();
		$git->checkout( 'master' );
		$this->fetchUpdateVersion( $git );

		$this->mergeAndCommitChanges( $git );

		if ( $this->needsPush ) {
			$this->getLogger()->info( 'Pushing changes...' );
			$git->push();
		}
	}

	private function getGitFromExtensionName() {
		$fsLocation = $this->getCwd() . '/' . $this->extensionName;
		if ( !is_dir( $fsLocation ) ) {
			$this->getLogger()->info( 'Extension directory does not exist, cloning...' );
			return $this->cloneExtension( $fsLocation );
		}

		$git = $this->gitWrapper->workingCopy( $fsLocation );
		if ( !$git->isCloned() ) {
			$this->getLogger()->info( 'Extension isn\'t cloned already, cloning...' );
			return $this->cloneExtension( $fsLocation );
		}
		$this->getLogger()->info( 'Extension is already cloned...' );
		$this->resetAndPull( $git );
		$this->addUpdateRemote( $git );

		return $git;
	}

	private function cloneExtension( $fsLocation ) {
		if ( !$this->config->has( Config::GIT_PUSH_REMOTE ) ) {
			throw new \UnexpectedValueException( 'Extension, which should be updated, is '.
				'not cloned already, but the remote to push to is not configured.' );
		}
		$gitWorkingDir = $this->gitWrapper->cloneRepository(
			sprintf( $this->config->get( Config::GIT_PUSH_REMOTE ), $this->extensionName ),
			$fsLocation
		);
		$this->addUpdateRemote( $gitWorkingDir );

		return $gitWorkingDir;
	}

	private function resetAndPull( GitWorkingCopy $git ) {
		if ( $git->hasChanges() ) {
			$this->getLogger()->info( 'Git clone has changes, resetting...' );
			$git->reset( [ 'hard' => true ] );
		}
		$this->getLogger()->info( 'Checking out master and pull from origin/master...' );
		$git->checkout( 'master' )->pull( 'origin', 'master' );
	}

	/**
	 * Sets a logger instance on the object
	 *
	 * @param LoggerInterface $logger
	 * @return null
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	protected function getLogger() {
		return $this->logger;
	}

	private function addUpdateRemote( GitWorkingCopy $gitWorkingDir ) {
		if ( !$this->config->has( Config::GIT_UPDATE_REMOTE ) ) {
			throw new \UnexpectedValueException( 'Extension, which should be updated, is '.
				'not cloned already, but the remote to update from is not configured.' );
		}
		$gitWorkingDir->getOutput();
		$remotes = $gitWorkingDir->getRemotes();
		$expectedRemoteUrl = sprintf(
			$this->config->get( Config::GIT_UPDATE_REMOTE ),
			$this->extensionName
		);
		if ( $gitWorkingDir->hasRemote( self::UPSTREAM_REMOTE_NAME ) ) {
			if ( $remotes[self::UPSTREAM_REMOTE_NAME]['fetch'] === $expectedRemoteUrl ) {
				return;
			}
			$gitWorkingDir->removeRemote( self::UPSTREAM_REMOTE_NAME );
		}

		$this->getLogger()->info(
			'Update remote does not exist or has wrong URL, adding/updating with URL {url}',
			[ 'url' => $expectedRemoteUrl ] );
		$gitWorkingDir->addRemote( self::UPSTREAM_REMOTE_NAME, $expectedRemoteUrl );
	}

	private function fetchUpdateVersion( GitWorkingCopy $git ) {
		$versionRefSpec = $this->getVersionRefSpec();

		$this->getLogger()->info( 'Fetching remote ref-spec {refSpec}',
			[ 'refSpec' => $versionRefSpec ] );
		$git->fetch( self::UPSTREAM_REMOTE_NAME, $versionRefSpec );
	}

	private function getVersionRefSpec() {
		$versionRefSpec = '';
		if ( $this->config->has( 'version.prefix' ) ) {
			$versionRefSpec .= $this->config->get( 'version.prefix' );
		}

		$versionRefSpec .= $this->cliOptions->getVersionNumber();

		return $versionRefSpec;
	}

	private function mergeAndCommitChanges( GitWorkingCopy $git ) {
		$refSpec = self::UPSTREAM_REMOTE_NAME . '/' . $this->getVersionRefSpec();
		$this->getLogger()
			->info( 'Merging changes from {refSpec} to master with strategy recursive=theirs...',
				[ 'refSpec' => $refSpec ] );
		$git->merge( $refSpec, [
			's' => 'recursive',
			'X' => 'theirs',
			'squash' => true,
		] );
		if ( !$git->hasChanges() ) {
			$this->needsPush = false;
			$this->getLogger()->info( 'Nothing to commit, skipping...' );
			return;
		}
		$this->getLogger()->info( 'Commiting changes...' );
		$sha1OfRefSpec = $git->getWrapper()->git( 'rev-parse ' . $refSpec, $git->getDirectory() );
		$git->commit( sprintf(
			self::COMMIT_MSG_TEMPLATE,
			$this->extensionName,
			$this->getVersionRefSpec(),
			$sha1OfRefSpec
		) );
	}

	private function getCwd() {
		if ( $this->cwd !== null ) {
			return $this->cwd;
		}
		return getcwd();
	}

	public function setCwd( $cwd ) {
		$this->cwd = $cwd;
	}
}
