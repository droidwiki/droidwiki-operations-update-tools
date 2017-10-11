<?php

/**
 * A file wrapping the startup of the updater script, which src to
 * require necessary files, like the composer autoloader, and default configuration
 * options.
 */

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

require dirname( __DIR__ ) . '/vendor/autoload.php';

$container = new ContainerBuilder();
$loader = new XmlFileLoader( $container, new FileLocator( __DIR__ ) );
$loader->load( 'services.xml' );
