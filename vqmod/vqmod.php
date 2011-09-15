<?php

class VQModLog {
	private $_sep;
	private $_vqmod;
	private $_defhash = 'da39a3ee5e6b4b0d3255bfef95601890afd80709';
	private $_logs = array();

	public function __construct(VQMod $vqmod) {
		$this->_vqmod = $vqmod;
		$this->_sep = str_repeat('-', 70);
	}

	public function __destruct() {
		if(empty($this->_logs) || $this->_vqmod->logging == false) {
			return;
		}

		$txt = array();

		$txt[] = str_repeat('-', 10) . ' Date: ' . date('Y-m-d H:i:s') . ' ~ IP : ' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'N/A') . ' ' . str_repeat('-', 10);
		$txt[] = 'REQUEST URI : ' . $_SERVER['REQUEST_URI'];

		foreach($this->_logs as $count => $log) {
			if($log['obj']) {
				$vars = get_object_vars($log['obj']);
				$txt[] = 'MOD DETAILS:';
				foreach($vars as $k => $v) {
					if(is_string($v)) {
						$txt[] = '   ' . str_pad($k, 10, ' ', STR_PAD_RIGHT) . ': ' . $v;
					}
				}

			}

			foreach($log['log'] as $msg) {
				$txt[] = $msg;
			}

			if ($count > count($this->_logs)-1) {
				$txt[] = '';
			}
		}

		$txt[] = $this->_sep;
		$txt[] = str_repeat(PHP_EOL, 2);

		$logPath = $this->_vqmod->path($this->_vqmod->logFilePath, true);
		if(!file_exists($logPath)) {
			$res = file_put_contents($logPath, '');
			if($res === false) {
				die('COULD NOT WRITE TO LOG FILE');
			}
		}

		file_put_contents($logPath, implode(PHP_EOL, $txt), FILE_APPEND);
	}

	public function write($data, VQModObject $obj = NULL) {
		if($obj) {
			$hash = sha1($obj->id);
		} else {
			$hash = $this->_defhash;
		}

		if(empty($this->_logs[$hash])) {
			$this->_logs[$hash] = array(
				'obj' => $obj,
				'log' => array()
			);
		}

		$this->_logs[$hash]['log'][] = $data;

	}
}

class VQModObject {
	public $modFile = '';
	public $id = '';
	public $version = '';
	public $vqmver = '';
	public $author = '';
	public $mods = array();

	private $_vqmod;
	private $_skip = false;

	public function __construct(DOMNode $node, $modFile, VQmod $vqmod) {
		if($node->hasChildNodes()) {
			foreach($node->childNodes as $child) {
				$name = (string) $child->nodeName;
				if(isset($this->$name)) {
					$this->$name = (string) $child->nodeValue;
				}
			}
		}

		$this->modFile = $modFile;
		$this->_vqmod = $vqmod;
		$this->_parseMods($node);
	}

	public function skip() {
		return $this->_skip;
	}

	public function applyMod(&$data, $filename) {
		$tmp = $data;

		if(empty($this->mods[$filename]) || $this->_skip) {
			return;
		}

		foreach($this->mods[$filename] as $mod) {
			$indexCount = 0;
			$tmp = $this->_explodeData($tmp);
			$lineMax = count($tmp) - 1;

			switch($mod['search']->position) {
				case 'top':
				$tmp[$mod['search']->offset] =  $mod['add']->getContent() . $tmp[$mod['search']->offset];
				break;

				case 'bottom':
				$offset = $lineMax - $mod['search']->offset;
				if($offset < 0){
					$tmp[-1] = $mod['add']->getContent();
				} else {
					$tmp[$offset] += $mod['add']->getContent();
				}
				break;

				case 'all':
				$tmp = array($mod['add']->getContent());
				break;

				default:

				$changed = false;
				foreach($tmp as $lineNum => $line) {
					if($mod['search']->regex == 'true') {
						$pos = @preg_match($mod['search']->getContent(), $line);
						if($pos === false) {
							// Regex is invalid. need to handle error here
							if($mod['error'] == 'log' || $mod['error'] == 'abort' ) {
								$this->_vqmod->log->write('INVALID REGEX ERROR - ' . $mod['search']->getContent(), $this);
							}
							continue 2;
						}
					} else {
						$pos = strpos($line, $mod['search']->getContent());
					}

					if($pos !== false) {
						$indexCount++;
						$changed = true;

						if(!$mod['search']->indexes() || ($mod['search']->indexes() && in_array($indexCount, $mod['search']->indexes()))) {

							switch($mod['search']->position) {
								case 'before':
								$offset = ($lineNum - $mod['search']->offset < 0) ? -1 : $lineNum - $mod['search']->offset;
								$tmp[$offset] = empty($tmp[$offset]) ? $mod['add']->getContent() : $mod['add']->getContent() . "\n" . $tmp[$offset];
								break;

								case 'after':
								$offset = ($lineNum + $mod['search']->offset > $lineMax) ? $lineMax : $lineNum + $mod['search']->offset;
								$tmp[$offset] = $tmp[$offset] . "\n" . $mod['add']->getContent();
								break;

								default:
								if(!empty($mod['search']->offset)) {
									for($i = 1; $i <= $mod['search']->offset; $i++) {
										if(isset($tmp[$lineNum + $i])) {
											$tmp[$lineNum + $i] = '';
										}
									}
								}

								if($mod['search']->regex == 'true') {
									$tmp[$lineNum] = preg_replace($mod['search']->getContent(), $mod['add']->getContent(), $line);
								} else {
									$tmp[$lineNum] = str_replace($mod['search']->getContent(), $mod['add']->getContent(), $line);
								}
								break;
							}
						}
					}
				}

				if(!$changed) {
					// Log mod as invalid
					$skip = ($mod['error'] == 'skip' || $mod['error'] == 'log') ? ' (SKIPPED)' : ' (ABORTING MOD)';

					if($mod['error'] == 'log' || $mod['error'] == 'abort') {
						$this->_vqmod->log->write('SEARCH NOT FOUND' . $skip . ': ' . $mod['search']->getContent(), $this);
					}

					if($mod['error'] == 'abort') {
						$this->_skip = true;
						return;
					}

				}

				break;
			}
			ksort($tmp);
			$tmp = $this->_implodeData($tmp);
		}

		$data = $tmp;
	}

	private function _parseMods(DOMNode $node){
		$files = $node->getElementsByTagName('file');

		foreach($files as $file) {
			$fileToMod = $file->getAttribute('name');
			$fullPath = $this->_vqmod->path($fileToMod);
			$this->_vqmod->addFileToMod($fileToMod, $fullPath);

			$operations = $file->getElementsByTagName('operation');

			foreach($operations as $operation) {

				$error = ($operation->hasAttribute('error')) ? $operation->getAttribute('error') : 'abort';
				
				$this->mods[$fullPath][] = array(
					'search' 		=> new VQSearchNode($operation->getElementsByTagName('search')->item(0)),
					'add' 			=> new VQAddNode($operation->getElementsByTagName('add')->item(0)),
					'error'		 	=> $error
				);
			}
		}
	}

	private function _explodeData($data) {
		return explode("\n", $data);
	}

	private function _implodeData($data) {
		return implode("\n", $data);
	}
}

class VQNode {
	public $trim = 'false';

	private $_content = '';

	public function  __construct(DOMNode $node) {
		$this->_content = $node->nodeValue;

		if($node->hasAttributes()) {
			foreach($node->attributes as $attr) {
				$name = $attr->nodeName;
				if(isset($this->$name)) {
					$this->$name = $attr->nodeValue;
				}
			}
		}
	}

	public function getContent() {
		$content = ($this->trim == 'true') ? trim($this->_content) : $this->_content;
		return $content;
	}
}

class VQSearchNode extends VQNode {
	public $position = 'replace';
	public $offset = 0;
	public $index = 'false';
	public $regex = 'false';
	public $trim = 'true';

	public function indexes() {
		if($this->index == 'false') {
			return false;
		}
		$tmp = explode(',', $this->index);
		foreach($tmp as $k => $v) {
			if(!is_int($v)) {
				unset($k);
			}
		}
		$tmp = array_unique($tmp);
		return empty($tmp) ? false : $tmp;
	}
}

class VQAddNode extends VQNode {
}

final class VQMod {
	public $useCache = false;
	public $logFilePath = 'vqmod/vqmod.log';
	public $vqCachePath = 'vqmod/vqcache/';
	public $protectedFilelist = 'vqmod/vqprotect.txt';
	public $logging = true;
	public $log;

	private $_vqversion = '2.0';
	private $_modFileList = array();
	private $_mods = array();
	private $_filesToMod = array();
	private $_cwd = '';
	private $_doNotMod = array();
	private $_virtualMode = true;

	public function __construct($path = false, $logging = true) {
		if(!class_exists('DOMDocument')) {
			die('ERROR - YOU NEED DOMDocument INSTALLED TO USE VQMod');
		}

		if(!$path){
			$path = dirname(dirname(__FILE__));
		}
		$this->_setCwd($path);

		$this->logging = (bool) $logging;
		$this->log = new VQModLog($this);

		$this->_getMods();
	}

	public function modCheck($sourceFile) {

		if(!preg_match('%^([a-z]:)?[\\\\/]%i', $sourceFile)) {
			$sourcePath = $this->path($sourceFile);
		} else {
			$sourcePath = realpath($sourceFile);
		}

		if(!$sourcePath) {
			return $sourceFile;
		}

		if(!empty($this->_filesToMod[$sourcePath])) {
			$fileInfo = $this->_filesToMod[$sourcePath];

			if($fileInfo['modded'] || ($this->useCache && file_exists($fileInfo['cacheFile'])) ) {
				return $fileInfo['cacheFile'];
			}

			$fileHash = sha1_file($sourcePath);
			$fileData = file_get_contents($sourcePath);

			foreach($this->_mods as $mod) {
				if(!$mod->skip()) {
					$mod->applyMod($fileData, $sourcePath);
				}
			}

			if(sha1($fileData) != $fileHash) {
				$writePath = $this->_virtualMode ?  $fileInfo['cacheFile'] : $sourcePath;
				if(!file_exists($writePath) || is_writable($writePath)) {
					file_put_contents($writePath, $fileData);
					$fileInfo['modded'] = true;
				}
				return $writePath;
			}
		}
		return $sourcePath;
	}

	public function path($path, $skip_real = false) {
		$tmp = $this->_cwd . $path;
		$realpath = $skip_real ? $tmp : realpath($tmp);
		if(!$realpath) {
			die('COULDNT RESOLVE REAL PATH [' . $this->_cwd . $path . ']');
		}
		if(is_dir($realpath)) {
			$realpath = rtrim($realpath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		}
		return $realpath;
	}

	public function addFileToMod($path, $fullPath = false) {
		if(!$fullPath) {
			$fullPath = $this->path($path);
		}
		if(empty($this->_filesToMod[$fullPath])) {
			$this->_filesToMod[$fullPath] = array(
				'modded' => false,
				'cacheFile' => $this->_cacheName($path),
			);
			return true;
		}
		return false;
	}

	private function _getMods() {

		$this->_modFileList = glob($this->path('vqmod/xml/') . '*.xml');

		if($this->_modFileList) {
			$this->_parseMods();
		} else {
			$this->log->write('NO MODS IN USE');
			// No mods, needs logging if applicable
		}
	}

	private function _parseMods() {

		$dom = new DOMDocument('1.0', 'UTF-8');
		foreach($this->_modFileList as $modFileKey => $modFile) {
			if(file_exists($modFile)) {
				if(@$dom->load($modFile)) {
					$mod = $dom->getElementsByTagName('modification')->item(0);
					$this->_mods[] = new VQModObject($mod, $modFile, $this);
				} else {
					//Dom couldn't load XML error handling
					$this->log->write('DOM UNABLE TO LOAD: ' . $modFile);
				}
			} else {
				// File doesn't exist error handling
				$this->log->write('FILE NOT FOUND: ' . $modFile);
			}
		}
	}

	private function _cacheName($file) {
		return $this->path($this->vqCachePath) . 'vq2-' . preg_replace('~[/\\\\]+~', '_', $file);
	}

	private function _setCwd($path) {
		$realpath = realpath($path);
		if(!$realpath) {
			die('COULDNT RESOLVE CWD REALPATH');
		}
		$this->_cwd = rtrim($realpath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	}
}