<?php

/***
	* files
	*
	* partial class for file management
	* just made for scanning at the moment
	**/


class files {

	//options for the directory scan
	public $options = array(
		'scan_dir'		=> '',
		'ext'					=> array(),
		'recursive'		=> true,
		'list_dir'		=> false,
		'list_files'	=> true,
		'exclude'			=> '',
		'file_types'	=> array(),
	);

	//any errors caught
	public $catch = array(
		'notice'	=> '',
		'warning'	=> '',
		'error'		=> '',
	);

	public $return = array();


	function __construct($dir = null, array $ext = array()) {
		//if there is a directory set it
		if($dir !== null) {
			$this->set_directory($dir);
		}
	}

	//set options
	public function options(array $options_in) {
		$this->options = array_merge($this->options, $options_in);
	}

	//set a scan
	public function scan() {
		if(empty($this->options['scan_dir'])) {
			$this->catch['error'] = 'No scan Directory set.';
		} else {
			return $this->_directory_to_array();
		}
	}

	//return errors
	public function show_error() {
		return $error['error'];
	}

	//set the directory
	public function set_directory($dir) {
		if(!$this->directory_exists($dir)) {
			$this->catch['error'] = 'Scan directory set does not exist.';
			return false;
		} else {
			$this->options['scan_dir'] = $dir;
			return true;
		}
	}

	//directory is classed as file
	public function directory_exists($dir) {
		return (is_dir($dir) && file_exists($dir));
	}


	/***
		* Get an array that represents directory tree
		* @param string $directory     Directory path
		* @param bool $recursive         Include sub directories
		* @param bool $listDirs         Include directories on listing
		* @param bool $listFiles         Include files on listing
		* @param regex $exclude         Exclude paths that matches this regex
		**/

	private function _directory_to_array($directory = null) {

		if($directory === null){ $directory = $this->options['scan_dir']; }

		$arrayItems = array();
		$skipByExclude = false;
		$handle = opendir($directory);

		if ($handle) {
			while (false !== ($file = readdir($handle))) {
				preg_match("/(^(([\.]){1,2})$|(\.(svn|git|md))|(Thumbs\.db|\.DS_STORE))$/iu", $file, $skip);
				if($this->options['exclude']){
					preg_match($this->options['exclude'], $file, $skipByExclude);
				}
				if (!$skip && !$skipByExclude) {
					if (is_dir($directory. DIRECTORY_SEPARATOR . $file)) {
						if($this->options['recursive']) {
							$arrayItems = array_merge($arrayItems, $this->_directory_to_array($directory. DIRECTORY_SEPARATOR . $file));
						}

						if($this->options['list_dir']){
 							$file = $directory . DIRECTORY_SEPARATOR . $file;
							$arrayItems[] = $file;
						}

					} else {
						if($this->options['list_files']){
							if( empty( $this->options['file_types'] ) || in_array( substr( strrchr($file, '.'), 1 ), $this->options['file_types'] ) ) {
								$file = $directory . DIRECTORY_SEPARATOR . $file;
								$arrayItems[] = $file;
							}
						}
					}
				}
			}
			closedir($handle);
		}
		return $arrayItems;
	}


}

