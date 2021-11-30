<?php

//get config
require 'config.php';

//see if we are using a password
if( isset( $password ) && $password ) {
	//make sure correct password is used to run the file
	if( !isset( $_GET[ 'password' ] ) || !$_GET[ 'password' ] || $_GET[ 'password' ] !== $password ) {
		if( $show_info ) {
			echo 'Password is incorrect';
		}
		exit();
	}
}

ouput_generate( $output_file, $plugin_dir, $show_info );

if( isset( $hash_file ) && $hash_file ) {
	output_hash( $output_file, $show_info );
}

function ouput_generate( $output_file, $plugin_dir, $show_info ) {

	//require class to help with scanning for addon xml's
	require 'includes/classes/helper/class.files.php';

	//set the dir
	$files = new files( $plugin_dir );

	$files->options['recursive']	= false;
	$files->options['list_dir']		= true;
	$files->options['list_files']	= false;

	$plugin_folders = $files->scan();

	$fh = fopen( $output_file, 'w+' ) or die( $show_info ? 'can\'t open file' : '' );

	$plugins_stats = array(
		'plugins'		=> 0,
		'included'	=> 0,
	);

	//init xml output
	$xml = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
	<addons>';

	//loop through all plugin folders and get the xml file
	foreach( $plugin_folders as $plugin ) {

		$plugins_stats[ 'plugins' ]++;

		if( file_exists ( $plugin . '/addon.xml' ) ) {

			$plugins_stats[ 'included' ]++;

			$xml_tmp = file_get_contents( $plugin . '/addon.xml' );
			//remove first part of XML - not needed
			$xml_tmp = preg_replace( '/<\?xml(?:\s+)?(?:version="1\.0")?(?:\s+)?(?:encoding="utf-8")?(?:\s+)?(?:standalone="yes")?\?>/i', '', $xml_tmp );
			//remove comments
			$xml_tmp = preg_replace( '/(<!--(?:.+)-->)/i', '', $xml_tmp );
			//minify
			$xml_tmp = preg_replace( '/>\s+</i', '><', $xml_tmp );
			$xml .= trim( $xml_tmp ) . PHP_EOL;

		}

	}

	$xml .= '</addons>';

	//write to output & close
	fwrite( $fh, $xml );
	fclose( $fh );

	if( $show_info ) {
		echo 'Generated: ', $output_file, '<br />';
		echo 'Folders Detected: ', $plugins_stats[ 'plugins' ], '<br />';
		echo 'Plugins Included: ', $plugins_stats[ 'included' ], '<br />';
	}

}

function output_hash( $output_file, $show_info ) {

	$fh = fopen( $output_file . '.md5', 'w+' ) or die( $show_info ? 'can\'t open file' : '' );

	fwrite( $fh, md5_file( $output_file ) );
	fclose( $fh );

	if( $show_info ) {
		echo 'Hash File Generated<br />';
	}

}

