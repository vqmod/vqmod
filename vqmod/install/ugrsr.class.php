<?php

/**
 * UGRSR
 * 
 * @package Universal Global RegEx Search/Replace
 * @author Qphoria - http://theqdomain.com/ & Jay Gilford - http://jaygilford.com/
 * @copyright Qphoria & Jay Gilford 2011
 * @version 0.2
 * @access public
 * 
 * @information
 * This class will perform mass search and replace actions
 * based on regex pattern matching. It recursively grabs all files
 * below it's given path and applies the specified change(s)
 * 
 * @license
 * Permission is hereby granted, free of charge, to any person to
 * use, copy, modify, distribute, sublicense, and/or sell copies
 * of the Software, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software
 * 
 * @warning
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESSED OR IMPLIED.
 *  
 */
class UGRSR {
	
	public $debug = false;				// Show debug messages switch
	public $flags = 0;					// Flags for the file recursive glob
	public $pattern = '*.php';			// File pattern to match with glob
	public $recursive = true;			// Recursion into subdirectories switch
	public $test_mode = false;			// Test mode only switch
	public $file_search = true;			// Search for files switch
	
	
	private $_regexes = array();		// Array for regex patterns and replaces
	private $_path = '';				// Path to directory to work with
	private $_protected = array();		// Array of protected files
	private $_files = array();			// Array of manually added file locations
	
	/**
	 * UGRSR::__construct()
	 * 
	 * @param string $path
	 * @return null
	 */
	function __construct($path = '') {
		
		// Use current working directory if none given as a parameter
		if(empty($path)) {
			$path = getcwd();
		}
		
		// Apply path to var
		$this->setPath($path);
		
		// Check to make sure the script calling the class is set to be protected
		if(!isset($_SERVER['SCRIPT_FILENAME'])) {
			DIE('SCRIPT FILENAME COULD NOT BE DETERMINED');
		}
		
		// Set default file protections
		$this->resetProtected();
	}
	
	/**
	 * UGRSR::addPattern()
	 * 
	 * @param string $pattern
	 * @param string $replace
	 * @return bool
	 */
	public function addPattern($pattern, $replace) {
		
		// If pattern is empty throw error
		if(empty($pattern)) {
			$this->_dbg('PATTERN EMPTY');
			return false;
		}
		
		// Add regex pattern and replace vars to _regexes array
		$this->_regexes[] = array(
			'pattern'		=> $pattern,
			'replace'		=> $replace
		);
		
		return true;
	}
	
	/**
	 * UGRSR::mergePatterns()
	 * 
	 * @param array $pattern_array
	 * @return bool
	 */
	public function mergePatterns($pattern_array) {
		
		// If the param is not an array throw error
		if(!is_array($pattern_array)) {
			$this->_dbg('PARAM IS NOT AN ARRAY');
			return false;
		}
		
		//Loop through pattern array
		foreach($pattern_array as $data) {
			
			// If pattern or replace keys not set throw error and continue loop
			if(!isset($data['pattern']) || !isset($data['replace'])) {
				$this->_dbg('ARRAY KEYS NOT SET');
				continue;
			}
			
			// Add regex and replace
			$this->addPattern($data['pattern'], $data['replace']);
		}
		
		return true;
	}
	
	/**
	 * UGRSR::clearPatterns()
	 * 
	 * @return null
	 */
	public function clearPatterns() {
		
		// Set regexes var to empty array
		$this->_regexes = array();
	}
	
	/**
	 * UGRSR::addFile()
	 * 
	 * @param string $filename
	 * @param bool 
	 * @return bool
	 */
	public function addFile($filename, $omit_path = false) {
		
		$file = $omit_path ? $filename : $this->_path . $filename;
		
		// If the protection isnt for a file throw an error
		if(!is_file($file)) {
			$this->_dbg('FILE [' . $file . '] IS NOT A FILE');
			return false;
		}
		
		// Get real full path to file
		$real_filename = realpath($file);
		
		// If real path for file can't be found throw error
		if(!$real_filename) {
			$this->_dbg('FILE [' . $file . '] IS NOT A FILE');
			return false;
		}
		
		// Don't add file if it's already in the file list
		if(in_array($real_filename, $this->_files)) {
			$this->_dbg('FILE [' . $file . '] ALREADY IN FILE LIST');
			return false;
		}
		
		// Add filename to file list
		$this->_dbg('FILE [' . $real_filename . '] ADDED TO FILE LIST');
		$this->_files[] = $real_filename;
		
		return true;
	}
	
	/**
	 * UGRSR::resetFileList()
	 * 
	 * @return true
	 */
	public function resetFileList() {
		// Clear file list
		$this->_files = array();
		
		$this->_dbg('FILE LIST RESET');
		
		return true;
	}
	
	/**
	 * UGRSR::addProtected()
	 * 
	 * @param string $filename
	 * @return bool
	 */
	public function addProtected($filename) {
		
		// If the protection isnt for a file throw an error
		if(!is_file($filename)) {
			$this->_dbg('FILE [' . $filename . '] IS NOT A FILE');
			return false;
		}
		
		// Get real full path to file
		$real_filename = realpath($filename);
		
		// If real path for file can't be found throw error
		if(!$real_filename) {
			$this->_dbg('FILE [' . $filename . '] IS NOT A FILE');
			return false;
		}
		
		// Add filename to protected list
		$this->_dbg('FILE [' . $filename . '] ADDED TO PROTECTED LIST');
		$this->_protected[] = $real_filename;
		
		return true;
	}
	
	/**
	 * UGRSR::resetProtected()
	 * 
	 * @return true
	 */
	public function resetProtected() {
		// Clear protected list
		$this->_protected = array();
		
		$this->_dbg('PROTECTED FILES RESET');
		//Add this class to protected list
		$this->_protected[] = realpath(__FILE__);
		
		// Add script that called the class to protected list
		$this->_protected[] = realpath($_SERVER['SCRIPT_FILENAME']);
		
		return true;
	}
	
	/**
	 * UGRSR::setPath()
	 * 
	 * @param string $path
	 * @return bool
	 */
	public function setPath($path) {
		
		// Get full real path to given path
		$realpath = realpath($path);
		
		// If path can't be found or isn't a directory throw an error
		if(!$realpath || !is_dir($realpath)) {
			$this->_dbg('INVALID PATH [' . $realpath . ']');
			return false;
		}
		
		// Add trailing slash to path name
		$realpath .= '/';
		
		// Set path to new value
		$this->_dbg('NEW PATH SET [' . $realpath . ']');
		$this->_path = $realpath;
		
		return true;
	}
	
	/**
	 * UGRSR::run()
	 * 
	 * @return bool
	 */
	public function run() {
		
		// If regexes array is empty throw an error
		if(empty($this->_regexes)) {
			$this->_dbg('REGEX LIST IS EMPTY');
			return false;
		}
		
		$this->_dbg('STARTING RUN');
		$this->_dbg();
		
		// Set files to list of manually added files
		$files = $this->_files;

		// Check if file searching is enabled
		if($this->file_search) {
			$this->_dbg('GETTING FILE LIST');
			
			// Get a list of files under defined path
			$found = array_merge($this->_rglob());
	
			$this->_dbg(count($found) . ' FILES FOUND');
			$this->_dbg();
			
			// merge list of files with manually added file list
			$files = array_merge($files, $found);
		}
		
		// Check files found or throw error and return
		if(count($files) == 0) {
			$this->_dbg('NO FILES TO BE PROCESSED');
			return false;
		}
		
		$this->_dbg('STARTING FILE PROCESSING');
		
		// Var for total regex matches throughout files
		$global_change_count = 0;
		
		// Var to hold 
		$global_write_count = 0;
		
		// Var to hold number of bytes saved
		$bytes_saved = 0;
		
		// Loop through files one at a time
		foreach($files as $filename) {
			
			// Var for total regex matches in current file
			$file_change_count = 0;
			
			// Load file contents
			$content = $original_content = file_get_contents($filename);
			
			// If content couldn't be loaded throw error
			if($content === FALSE) {
				$this->_dbg('COULD NOT OPEN [' . $filename . ']');
				continue;
			}
			
			// If file length is 0 throw error
			if(strlen($content) == 0) {
				$this->_dbg('EMPTY FILE SKIPPED [' . $filename . ']');
				continue;
			}
			
			// Loop through _regexes array applying changes to content
			foreach($this->_regexes as $regex) {
				
				// Var for total regex matches for individual pattern
				$change_count = 0;
				
				// Try replacing content
				$content = preg_replace($regex['pattern'], $regex['replace'], $content, -1, $change_count);
				
				// If regex operation fails throw error and abort all operations
				if($content === NULL) {
					$this->_dbg('REGEX PATTERN ERROR <strong>' . $regex['pattern'] . '</strong>');
					$this->_dbg('ABORTING ALL OPERATIONS');
					break 2;
				}
				
				// Add individual pattern change count to file change count
				$file_change_count += $change_count;
				$this->_dbg('REGEX <strong>' . $regex['pattern'] . '</strong> FOUND ' . ($change_count ? $change_count : 'NO') . ' MATCHES IN [' . $filename . ']');
				
			}
			
			// If not in test mode and content has changed attempt to write back to file			
			if($content !== $original_content && !$this->test_mode) {
				$this->_dbg('ATTEMPTING TO WRITE TO FILE [' . $filename . ']');
				
				// If file isn't writeable throw error
				if(!is_writeable($filename)) {
					$this->_dbg('CANNOT WRITE TO [' . $filename . ']');
				} else {
					
					// Write file data back to file and show result
					$result = file_put_contents($filename, $content);
					if($result) {
						$this->_dbg('SUCCESSFULLY WROTE ' . $result . ' BYTES  TO [' . $filename . ']');
						$global_write_count++;
					} else {
						$this->_dbg('WRITE OPERATION FAILED IN [' . $filename . ']');
					}
				}
			}
			
			// Add byte difference to $bytes_saved
			$bytes_saved += (strlen($original_content) - strlen($content));
			
			// Add total file changes count to global file changes count 
			$global_change_count += $file_change_count;
			
			$this->_dbg('TOTAL NUMBER OF FILE CHANGES: ' . $file_change_count);
			$this->_dbg();
		}
		
		$this->_dbg();
		$this->_dbg('FINISHED FILE PROCESSING');
		$this->_dbg('TOTAL CHANGES APPLIED ACROSS ALL FILES: <strong>' . $global_change_count . '</strong>');
		$this->_dbg('TOTAL BYTES SAVED ACROSS ALL FILES: <strong>' . $bytes_saved . '</strong>');
		$this->_dbg();
		
		$this->_dbg('FINISHED RUN');
		$this->_dbg();
		
		// Pass back the number of changes and writes matched
		return array(
			'changes' => $global_change_count,
			'writes' => $global_write_count
		);
	}
	
	/**
	 * UGRSR::_dbg()
	 * 
	 * @param string $message
	 * @return NULL;
	 */
	private function _dbg($message = '') {
		
		// If in debug mode show output
		if($this->debug) {
			
			// Set mode type
			$mode = $this->test_mode ? 'TEST MODE' : 'LIVE MODE';
			
			// If there's a message echo that otherwise echo some whitespace for formatting
			if(!empty($message)) {
				echo $mode . ': *** ' . $message . " ***<br />\r\n";
			} else {
				echo str_repeat("<br />\r\n", 2);
			}
		}
	}
	
	/**
	 * UGRSR::_rglob()
	 * 
	 * @param string $path
	 * @return array
	 */
	private function _rglob($path = NULL) {
		
		// If the path isn't supplied use the one stored in _path
		if($path === NULL) $path = $this->_path;
		$this->_dbg('SEARCHING PATH [' . $path .']');
		
		// Get list of files under current directory
		$files = glob($path . $this->pattern, $this->flags);
		
		// Loop through all files
		foreach($files as $key => &$file) {
			// Flag to allow file to be kept in file array
			$remove_file = false;
			
			// Get full path of file
			$realfile = realpath($file);
			
			// Report if file path can't be resolved
			if($realfile === FALSE) {
				$this->_dbg('REAL PATH OF FILE [' . $file . '] COULD NOT BE RESOLVED');
				$remove_file = true;
			}
			
			// Report if file path is in the protected list
			if($realfile && in_array($realfile, $this->_protected)) {
				$this->_dbg('PROTECTED FILE [' . $realfile . '] REMOVED FROM FILES LIST');
				$remove_file = true;
			}
			
			// Report if file path is in the protected list
			if($realfile && in_array($realfile, $this->_files)) {
				$this->_dbg('FILE [' . $realfile . '] SKIPPED. ALREADY IN FILE LIST');
				$remove_file = true;
			}
			
			// Report if write access cannot be granted for file
			if($realfile && !is_writeable($realfile)) {
				$this->_dbg('FILE [' . $file . '] SKIPPED AS CANNOT WRITE TO IT');
				$remove_file = true;
			}
			
			// Remove from file list if any issues
			if($remove_file) {
				unset($files[$key]);
			}
		}
		
		// If recursion is set get files in subdirectories
		if($this->recursive) {
		
			// Get list of directories under current path
			$paths = glob($path . '*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
			
			// Loop through subdirectories and merge files into directory list
			foreach($paths as $p) {
				$files = array_merge($files, $this->_rglob($p));
			}
		}
		
		// Pass file array back		
		return $files;
	}
}