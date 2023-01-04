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

//require functions file for compatibility
require 'includes/functions/functions.compatibility.php';

//require class to help with scanning for addon xml's
require 'includes/classes/helper/class.files.php';


if( isset( $extract_files ) && $extract_files ) {
	xml_extract( $plugin_dir, $show_info );
}

ouput_generate( $output_file, $plugin_dir, $show_info );

if( isset( $hash_file ) && $hash_file ) {
	output_hash( $output_file, $show_info );
}

function ouput_generate( $output_file, $plugin_dir, $show_info ) {

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

		if( file_exists( $plugin . '/addon.xml' ) ) {

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
		echo '<br />';
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

//method to run through all folders then extracts latest addon.xml
function xml_extract( $plugin_dir, $show_info ) {

	//set the dir
	$files = new files( $plugin_dir );

	$files->options['recursive']	= false;
	$files->options['list_dir']		= true;
	$files->options['list_files']	= false;

	$plugin_folders = $files->scan();

	//loop through all plugin folders and scan for zip files
	foreach( $plugin_folders as $plugin ) {

		//set the dir
		$zips = new files( $plugin );

		$zips->options['recursive']		= false;
		$zips->options['list_dir']		= true;
		$zips->options['list_files']	= true;
		$zips->options['file_types']	= array( 'zip' );

		//get list of zip files
		$zip_files = $zips->scan();

		//get latest version in folder
		$latest_version = latest_version( $zip_files );

		if( $latest_version ) {
			//get latest version of zip
			$latest_zip = array_search_contains( $latest_version, $zip_files );

			$zip = new ZipArchive;
			$res = $zip->open($zip_files[ $latest_zip ]);
			if ($res === TRUE) {
				if($addonxml = $zip->getFromName( str_replace( $plugin_dir . '/', '', $plugin ) . '/addon.xml')) {
					file_put_contents( $plugin . '/addon.xml' , $addonxml);
					if( $show_info ) {
						echo 'Updating: ', $plugin, '/addon.xml -> ' . $latest_version . '<br />';
					}
				} else {
					if( $show_info ) {
						echo 'Error updating: ', $plugin, '/addon.xml<br />';
					}
				}

				$zip->close();

			} else {
				if( $show_info ) {
					echo 'Zip Error: ', $zip_files[ $latest_zip ], '<br />';
				}
			}

		}
	}

}

//method to get latest zip file
function latest_version( $zip_files ) {

	//turn into array of versions
	$versions = array_map(
		function( $val ){
			$parts = explode('-', $val);
			if( $parts ) {
				return str_replace( '.zip', '', end( $parts ) );
			}
			return array();
		},
		$zip_files
	);

	//sort versions
	if( $versions ) {
		usort( $versions, function($a, $b) {
			if ($a == $b) {
				return 0;
			}
			return version_compare($a, $b, '<') ? 1 : -1;
		});
	}

	//return largest version
	return ( isset( $versions[0] ) ? $versions[0] : false );

}

//function works the same as array_search except is contains instead of a full match
function array_search_contains( $needle, $haystack_array ) {

	if( $needle && $haystack_array ) {
		foreach( $haystack_array as $haystack_id => $haystack ) {
			if( str_contains( $haystack, $needle ) ) {
				return $haystack_id;
			}
		}
	}

	return false;
}

