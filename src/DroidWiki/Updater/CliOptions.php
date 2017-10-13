<?php

namespace DroidWiki\Updater;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use GetOpt\ArgumentException\Missing;
use GetOpt\GetOpt;
use GetOpt\Operand;
use GetOpt\Option;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class CliOptions implements LoggerAwareInterface {
	/**
	 * @var GetOpt
	 */
	private $options;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $cwd;

	public function __construct( GetOpt $options ) {
		$this->options = $options;
	}

	public function initOptions( $argvInput = null ) {
		$this->options->addOptions( [
			Option::create( 'v', 'version', GetOpt::REQUIRED_ARGUMENT )
				->setDescription( 'The version number to which this extension should	be updated.' ),
			Option::create( 'l', 'list', GetOpt::OPTIONAL_ARGUMENT )
				->setDescription( 'If set, the extension names will be taken from the specified file and all ' .
					'are updated.' ),
			Option::create( 'c', 'config', GetOpt::OPTIONAL_ARGUMENT )
				->setDescription( 'The location of the config JSON file, relative to the current ' .
					'directory.' ),
		] );
		$this->options->addOperands( [
			Operand::create( 'extension', Operand::OPTIONAL ),
		] );

		if ( $argvInput === null ) {
			$this->options->process();
		} else {
			$this->options->process( $argvInput );
		}

		if ( $this->options->getOption( 'version' ) === null ) {
			$errorMsg = 'The option --version is required.';
			$this->getLogger()->error( $errorMsg );
			$this->getLogger()->info( $this->options->getHelpText() );
			throw new Missing( $errorMsg );
		}
	}

	protected function getLogger() {
		return $this->logger;
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

	public function getVersionNumber() {
		return $this->options->getOption( 'version' );
	}

	public function getList() {
		if ( !$this->isListUpdate() ) {
			throw new NoListUpdateException();
		}

		$listFile =
			file( Constants::INSTALL_PATH . $this->options->getOption( 'list' ),
				FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( $listFile === false ) {
			throw new InvalidArgumentException( 'Error during read of ' .
				$this->options->getOption( 'list' ) );
		}

		return $listFile;
	}

	public function isListUpdate() {
		return $this->options->getOption( 'list' ) !== null;
	}

	public function getExtensionName() {
		if ( $this->isListUpdate() ) {
			throw new NoSingleExtensionUpdateException();
		}

		return $this->options->getOperand( 0 );
	}

	public function getConfigPath() {
		$configOption = $this->options->getOption( 'config' );
		if ( !$configOption ) {
			return null;
		}
		return $this->getCwd() . '/' . $configOption;
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
