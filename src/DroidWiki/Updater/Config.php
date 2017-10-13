<?php

namespace DroidWiki\Updater;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class Config {
	const GIT_UPDATE_REMOTE = 'git.update.remote';
	const GIT_PUSH_REMOTE = 'git.push.remote';
	const VERSION_PREFIX = 'version.prefix';

	/**
	 * @var FileResource
	 */
	private $locationFileReference;

	/**
	 * @var array
	 */
	private $jsonConfig;

	public function __construct( CliOptions $cliOptions, $locationOrConfiguration = null ) {
		if ( is_array( $locationOrConfiguration ) ) {
			$this->jsonConfig = $locationOrConfiguration;
			return;
		}

		if ( $cliOptions->getConfigPath() !== null ) {
			$this->locationFileReference = new FileResource( $cliOptions->getConfigPath() );
		} elseif ( $locationOrConfiguration !== null ) {
			$this->locationFileReference = new FileResource( $locationOrConfiguration );
		} else {
			throw new \InvalidArgumentException( 'No configuration file specified' );
		}
		$this->load();
	}

	private function load() {
		$this->jsonConfig = json_decode( file_get_contents( $this->locationFileReference ), true );
		if ( $this->jsonConfig === null ) {
			throw new InvalidArgumentException(
				sprintf( 'The file "%s" is empty or not valid json.',
					$this->locationFileReference->getResource() )
			);
		}
	}

	/**
	 * @param $key
	 * @return bool
	 */
	public function has( $key ) {
		return isset( $this->jsonConfig[ $key ] );
	}

	public function get( $key, $default = null ) {
		if ( $this->has( $key ) ) {
			return $this->jsonConfig[ $key ];
		}
		if ( func_num_args() === 1 ) {
			throw new ConfigNotFoundException( sprintf( 'The configuration "%s" does not exist.',
				$key ) );
		}
		return $default;
	}
}
