#!/usr/bin/php -d memory_limit=2048M
<?php

error_reporting( E_ALL );

/**
 * directories
 */
$root   = dirname( dirname( __FILE__ ) );
$vendor = $root . '/lib';

/**
 * read options
 */
$options = getopt( 'd:c:' );
if ( ! isset( $options['c'] ) || ! is_string( $options['c'] ) ) {
	$options['c'] = 'default';
}

if ( ! isset( $options['d'] ) || empty( $options['d'] ) ) {
	echo 'please add directory to read';
	echo PHP_EOL;
	echo 'run.sh -d <dir_to_read>';
	echo PHP_EOL;
	exit;

}
if ( ! is_dir( $options['d'] ) ) {
	printf( '%s - is not a directory or is unredable', $options['d'] );
	echo PHP_EOL;
	exit;
}

/**
 * configuration
 */
$config_file = sprintf( '%s/etc/%s.json', $root, $options['c'] );
if ( ! is_file( $config_file ) || ! is_readable( $config_file ) ) {
	die( sprintf( 'File "%s" does not exists or is not readable!', $config_file ) );
}
$config = json_decode( file_get_contents( $config_file ) );

/**
 * common
 */
include_once $vendor . '/functions.php';
include_once $vendor . '/iworks/wxr.php';
include_once $vendor . '/iworks/db.php';

/**
 * service
 */

include_once $vendor . '/iworks/' . $config->service . '.php';

foreach ( $config->runners as $runner => $state ) {
	if ( false == $state ) {
		continue;
	}
	print $config->service . '::' . $runner . PHP_EOL;
	include_once $vendor . '/iworks/' . $config->service . '/' . $runner . '.php';
	$class = sprintf( 'iworks_%s_%s', $config->service, $runner );
	$doc   = new $class();
	$doc->generate( $config->mode );
}
