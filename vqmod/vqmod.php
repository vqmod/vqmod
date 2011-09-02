<?php

/**
 * VirtualQMod™ (vQmod™) - http://www.vqmod.com
 * --------------------------------------
 * @author Qphoria <qphoria@gmail.com>
 * @copyright Copyright (c) 2010-2011, Qphoria@gmail.com
 * @version 1.2.3
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 * VirtualQMod is a new innovation in php modification override methods.
 * Instead of modifying actual files to add custom modifications, source files are parsed "on-the-fly" before the php include() or require() is called.
 * The source is cloned to a temp file and modifications are made to that temp file, then substituted for the real file in the include path.
 *
 * This is the first and only known method to allow the ability to test multiple mods without actually changing files.
 * The modifications are stored in their own mod files so there is never a worry about overwriting existing modifications.
 * Multiple mod files can be used together and can modify the same file. Simply drop them into the /vqmod/ folder and go
 *
 * This is an innovation not only for OpenCart but for all php projects.
 * This system can be used for any php script, forum, shopping cart, cms, etc. Any where custom mods are made for reuse.
 * The script is also resilient enough to fail gracefully and restore the original file in the case of an error.
 *
 * After the initial class load, the mod can actually use a script to mod itself into other core files that do the actual includes for other mods.
 *
 * Usage:
 * 1. Include the VirtualQmod class in your index file, before all other require or include commands.
 * 2. Create a new class object called $vqmod.
 *		Example:
 *			//VirtualQMOD
 *			require_once('./vqmod.php', TRUE);
 *			$vqmod = new VQMod('./vqmod');
 *	3. For all places that an include/require is called, wrap the inner file with a the modCheck function
 *		Ex.
 *		require_once('system/startup.php');
 *		changes to:
 *		require_once($vqmod->modCheck('system/startup.php'));
 *	4. Drop a mod file into the /vqmod/ folder and thats it! Reload your site and see it work
 *		- If a mod is not taking effect, it is likely that there was an error and it just defaulted to the original source. Check the "vqmod.log" file for errors.
 *		- All temp modified files are created in the /vqcache/ directory. You can see exactly what changes were made on the fly.
 *		- To remove a mod, just delete it from the /vqmod/ folder or rename the extension .xml to .xml_
 *
 * Mod file Example:
 *  Filename: Free form text (e.g. change_123_to_abc.xml)
 * --------------------------------------------------------
 *    <modification>
 *        <id>Change 123 to ABC</id>
 *        <version>1.0.0</version>
 *        <vqmver>1.0.8</vqmver>
 *        <author>qphoria</author>
 *        <file name="catalog/controller/product/product.php">
 *            <operation error="skip|abort">
 *                <search position="before|after|replace|top|bottom|all" index="false|n,n" offset="n" regex="true|false" trim="true|false"><![CDATA[
 *                $var = '123';
 *                ]]></search>
 *                <add trim="true|false"><![CDATA[
 *                $var = 'ABC';
 *                ]]></add>
 *            </operation>
 *        </file>
 *    </modification>
 *
 * LEGEND:
 * <modification>
 *  - This is the highest level of the file and there can only be one
 * <id>
 *  - This is the name of the mod.
 *  - Format: Free form text. (Informational)
 * <version>
 *  - This is the version of the mod.
 *  - Format: Number and Decimal (1.0.0) (Informational)
 * <vqmver>
 *  - This is the minimum required version of VirtualQMod needed for the script to work.
 *  - Format: Number and Decimal (1.0.0) (Informational)
 * <author>
 *  - This is the author of the mod.
 *  - Format: Free form text (Informational)
 * <file>
 *  - This is the name of the file to modify.
 *  - Requires attribute "name" as relative filename to the location of the vqmod.php file (e.g. catalog/controller/product/product.php).
 *  - There can be multiple file tags in a single xml file. Each file can have its own set of operations
 * <operation>
 *  - This is the wrapper of the actual operation occuring.
 *  - There can be multiple operations to the same <file> tag.
 *  - Optional attribute "error" set to skip|abort
 *      - skip means all other operations will be applied even if one cannot.
 *      - abort means to revert to the original source. (default)
 * <search>
 *  - This is the first required step of the operation.
 *  - Can only search one line!
 *  - Automatically trims whitespace and linebreaks
 *  - One per <operation> tag
 *  - Recommended to use CDATA tags to wrap code.
 *  - Required attribute "position" set to before|after|replace|top|bottom|all.
 *      - replace will replace the data in the <search> tag with the data in the <add> tag. (default)
 *      - before will insert the <add> data before the <search> data
 *      - after will insert the <add> data after the <search> data
 * 		- top will insert the <add> data at the top of the file. The <search> data is ignored.
 * 		- bottom will insert the <add> data at the bottom of the file. The <search> data is ignored.
 * 		- all will completely replace all the code in the file with the <add> data. The <search> data is ignored.
 *  - Optional attribute "offset" to work with the position
 *      - if the search position is before and offset 3 it will put the <add> data before the line, 3 lines above the searched line
 *      - if the search position is after and offset 3 it will put the <add> data after the line, 3 lines below the searched line
 *      - if the search position is replace and offset 3 it will remove the code from the search line and the next 3 lines and replace it with the <add> data
 *      - if the search position is top and offset 3 it will put the code before the line, 3 lines below the top of the file
 *      - if the search position is bottom and offset 3 it will put the code after the line, 3 lines above the bottom of the file
 *  - Optional attribute "index" for specifying which instances of a search tag should be acted on
 *      - If the search string is "echo" and there are 5 echos in the file, but only want to replace the 1st and 3rd, use index="1,3"
 *      - Comma delimited for multiple instances starting with "1"
 *      - Leave out or set to FALSE to replace all instances. (default)
 *  - Optional attribute "regex" for specifying whether or not to search a regex pattern.
 *      - If true, the <search> data should be a valid regex pattern
 *      - Leave out or set to FALSE to use normal string search (default)
 *  - Optional attribute "trim" set to true|false
 *      - true will trim away whitespace and linebreaks. (default)
 *      - leave out or set to false to not trim.
 * <add>
 *  - This is the second required step of the operation.
 *  - Can be multiple lines
 *  - One per operation.
 *  - Location of added data depends on the position attribute of the <search> command.
 *  - Use CDATA tags to wrap code.
 *  - Uses an optional attribute "trim" set to true|false
 *      - true will trim away whitespace and linebreaks.
 *      - leave out or set to false to not trim. (default)
 *
 * <![CDATA[  ]]>
 *  - These are called CDATA tags and they are used by xml to specify that the data between should not be evaluated.
 *
 * --------------------------------------------------------
 *
 * @todo Add sortability based on filename (or master sort control list file?)
 * @todo Add option for inline after/before instead of line by line (replace already does inline)
 * @todo Add default path variable prefix option for platforms like phpbb3 that use variables in path names
 */


final class VQMod {
	private $vqversion = '1.2.3';						// Current version for logging
    private $filesToMod = array();						// Keeps a list of all the files that have mods available at construct
    private $modsByFile = array();						// Keeps an array of which mods apply to which files to improve performance
    private $doNotMod = array();						// Keeps an array of which files should never be modded
    private $fileCache = array();						// Keeps a relationship from real file to temp file to avoid reloading same class
    private $cwd = '';									// default working directory of this script
    private $virtualMode = TRUE;						// If you want to modify the actual source files, set to FALSE. NOT RECOMMENDED!!!
    private $init = TRUE;								// Triggers the log to reset
	private $logFilePath = './vqmod';				// Log file path. default: './vqmod/logs'
	private $logFileName = 'vqmod.log';					// Log file name. default: 'vqmod.log'

    public $useCache = FALSE;							// Whether or not to use previously cached versions of the source files
	public $vqCachePath = './vqmod/vqcache';			// The vqmod cache path
	public $protectedFilelist = './vqmod/vqprotect.txt';// Protected Files list path. default: './vqmod/vqprotect.txt'
    public $logging = TRUE;								// enable/disable logging


    /**
     * Scan all mod.xml files to get a list of ALL files that will be modded
     * This is designed to improve performance by having a quick list of which files actually need modding
     * And skip over files that aren't in the array as they have no changes to scan for.
     */
    public function __construct($path = './vqmod', $logging = true) {

		if ($logging === false) { $this->logging = false; }

		$this->cwd = str_replace("\\", "/", dirname(dirname(realpath(__FILE__))));

		$this->init = true;

		$files = array();

        $xmlmods = glob($this->cwd . '/vqmod/xml/*.xml');

        // Read all .xml files and get all <file> entries to reduce scanning of files during modding
        foreach ($xmlmods as $mod) {
            $files[$mod] = $this->getFilesToMod($mod);
        }

        foreach($files as $key => $sources) {
            foreach($sources as $source) {
                $source = str_replace("\\", "/", $this->cwd . '/' . $source);
                $this->filesToMod[] = $source;
                $this->modsByFile[$source][] = $key;
            }
        }

        $this->filesToMod = $this->super_unique($this->filesToMod);

	}

	private function init() {

		if (!$this->init) {
			return;
		}

		$this->vqCachePath = str_replace("./", "", $this->vqCachePath);
		//$this->vqCachePath = $this->cwd . '/' . str_replace("./", "", $this->vqCachePath);
		if (!file_exists($this->cwd . '/' . $this->vqCachePath)) {
        	if (!mkdir($this->cwd . '/' . $this->vqCachePath)) {
				die('VQMOD: Could not create ' . $this->vqCachePath . ' directory. Ensure the vqmod directory is writable or create manually');
			}
		}

		// Check for do not mod list
		$this->protectedFilelist = str_replace("./", "", $this->protectedFilelist);
		if (file_exists($this->cwd . '/' . $this->protectedFilelist)) {
			if ($this->doNotMod = file($this->cwd . '/' . $this->protectedFilelist)) {
				foreach($this->doNotMod as $k => $dnmpath) {
					$this->doNotMod[$k] = $this->cwd . '/' . $dnmpath;
				}
			}
		}

		if ($this->logging && $this->logFilePath) {

			date_default_timezone_set('UTC');

			//$this->logFilePath = $this->cwd . '/' . $this->logFilePath;
			$this->logFilePath = str_replace("./", "", $this->logFilePath);
			//$this->logFilePath = ($this->logFilePath . substr(str_replace('/', '_', str_replace(array('?','&'), '+', $_SERVER['REQUEST_URI'])), 0, 80));

			if (!file_exists($this->cwd . '/' . $this->logFilePath)) {
				if (!mkdir($this->cwd . '/' . $this->logFilePath)) {
					die('VQMOD: Could not create ' . $this->logFilePath . ' directory. Ensure the vqmod directory is writable or create manually');
				}
			}

		} else {
			$this->logging = FALSE;
		}

	    $this->init = false;

	}


    // This will check the filesToMod array to see if this file needs to bother searching for changes
    public function hasMods($sourcefile) {
        return in_array($sourcefile, $this->filesToMod);
    }

    public function modCheck($sourcefile) {

		if ($sourcefile == '') {
			return '';
		}

        // if the path is relative, replace with full path
		$realsourcefile = realpath($sourcefile);
		if ($realsourcefile) {
			$sourcefile = $realsourcefile;
		}
        //$sourcefile = str_replace('./', dirname($_SERVER['SCRIPT_FILENAME']) . '/', $sourcefile);
		$sourcefile = str_replace(array("\\\\", "\\", "//"), "/", $sourcefile);


        // Check if this source has any mods to execute
        if (!$this->hasMods($sourcefile)) {
            return $sourcefile;
        }

        // If first action, run the init first
        if ($this->init) {
			$this->init();
		}

        // Check if the source is in the doNotModify list
		if (in_array($sourcefile, $this->doNotMod)) {
			$this->log("SOURCEFILE: [$sourcefile] FOUND IN THE PROTECTED FILE LIST ($this->protectedFilelist). SKIPPING\r\n");
			return $sourcefile;
		}

        // Create tempfile
        $tempfilepath = $this->cwd;
        $tempfilename = 'vq-' . trim(str_replace(array("/"), "_", str_replace($tempfilepath, '', $sourcefile)), '_');
        $tempfile = $tempfilepath . '/' . $this->vqCachePath . '/' . $tempfilename;

		// Use cached versions of the previously modded sources if exist and $useCache is true
		//if ($this->virtualMode && ($this->useCache || ((microtime(true) - $this->startTime) > 2)) && file_exists($tempfile)) {
		if ($this->virtualMode && ($this->useCache && file_exists($tempfile))) {
			$msg  = ("SOURCEFILE:  $sourcefile\r\n");
			$msg .= ("USING CACHED VERSION: $tempfile\r\n");
			$this->log($msg);
		    return $tempfile;
		}

        // Start the new Data with the old data as a base
        $newData = file_get_contents($sourcefile);

        // Loop through each mod.xml that references this sourcefile
        foreach($this->modsByFile[$sourcefile] as $modfile) {

        	$modfile = realpath($modfile);

            // Load the XML and parse it for the necessary data
            $dom = new DOMDocument('1.0', 'UTF-8');
            if (!$result = $dom->load($modfile)) {
                return $sourcefile;
            }
            $modification = $dom->getElementsByTagName('modification')->item(0);
            $id = $modification->getElementsByTagName('id')->item(0)->nodeValue;
            $version = $modification->getElementsByTagName('version')->item(0)->nodeValue;
            $author = $modification->getElementsByTagName('author')->item(0)->nodeValue;
            $files = $modification->getElementsByTagName('file');

            // Loop through all <file> tags that match this sourcefile
            foreach ($files as $file) {

				$filename = $file->getAttribute('name');
				$filename = $tempfilepath . '/' . $filename;
				if (!file_exists($filename)) { continue; }
				if (($filename) != $sourcefile) { continue; }
				if (in_array($sourcefile, array_keys($this->fileCache))){
					//$newData = file_get_contents($this->fileCache[$sourcefile]);
					return $this->fileCache[$sourcefile];
				}

				$operations = $file->getElementsByTagName('operation');
				foreach ($operations as $operation) {
					$error  	= $operation->getAttribute('error');
					$search 	= $operation->getElementsByTagName('search')->item(0)->nodeValue;
					$position 	= $operation->getElementsByTagName('search')->item(0)->getAttribute('position');
					$offset 	= $operation->getElementsByTagName('search')->item(0)->getAttribute('offset');
					$index  	= $operation->getElementsByTagName('search')->item(0)->getAttribute('index');
					$regex  	= $operation->getElementsByTagName('search')->item(0)->getAttribute('regex');
					$exists  	= $operation->getElementsByTagName('search')->item(0)->getAttribute('exists');
					$strim 		= $operation->getElementsByTagName('search')->item(0)->getAttribute('trim');
					$add 		= $operation->getElementsByTagName('add')->item(0)->nodeValue;
					$atrim 		= $operation->getElementsByTagName('add')->item(0)->getAttribute('trim');
					if (strtolower($strim) != "false") { $search = trim($search); }
					if ($atrim) { $add = trim($add); }

					$msg  = ("----------------------" . date('Ymd-His') . "-------------------------\r\n");
					$msg .= ("SOURCEFILE:  $sourcefile\r\n");
					$msg .= ("MODFILE:     " . str_replace(array("\\\\", "\\"), '/', $modfile) . "\r\n");
					$msg .= ("SEARCH: 	   $search\r\n");

					// Test regex pattern for validity
					if ($regex && !$this->testRegEx($search, "test string")) {
						if ($error == 'skip') { // Log it but leave current $newData as it is
							$msg .= "OPERATION SKIPPED: INVALID REGEX PATTERN!\r\n";
							$msg .= ("--------------------------------------------------------------\r\n\r\n\r\n");
							$this->log($msg);
					    } else { //Abort all changes and return original sourcefile
							$msg .= "OPERATION FAILED (ABORTED): INVALID REGEX PATTERN!\r\n";
							$msg .= ("--------------------------------------------------------------\r\n\r\n\r\n");
							$this->log($msg);
							$msg = "";
					        return $sourcefile;
					    }
					}

					// Apply the mod
					$tempData = $this->applyMod($newData, $search, $add, $position, $offset, $index, $regex, $exists); // recursive in case there are 2 <file> tags with same file

					// If there was an error, skip the mod and use original file
					if ($tempData === FALSE || $tempData == NULL) {
					    if ($error == 'skip') { // Log it but leave current $newData as it is
							//$msg .= "OPERATION SKIPPED: NO MATCH FOR SEARCH!\r\n";
							//$msg .= ("--------------------------------------------------------------\r\n\r\n");
					        //$this->log($msg);
					    } else { //Abort all changes and return original sourcefile
							$msg .= "OPERATION FAILED (ABORTED): NO MATCH FOR SEARCH!\r\n";
							$msg .= ("--------------------------------------------------------------\r\n\r\n");
							$this->log($msg);
					        return $sourcefile;
					    }
					} else {
					    $newData = $tempData;
					}
				}

            }
        }

        // If not in virtualMode, write to actual source file
		if (!$this->virtualMode) {
			$this->log("VIRTUALMODE IS SET TO FALSE! WRITING TO ACTUAL SOURCE! THIS CANNOT BE UNDONE!");
        	$tempfile = $sourcefile;
        	rename($modfile, ($modfile . '_NOVIRT'));
        	$this->log("RENAMED modfile to " . ($modfile . '_NOVIRT') . " to avoid double processing");
		}

		// Write newData to tempfile and return it
		//$handle = fopen($tempfile, "w");
        //fwrite($handle, $newData);
        //fclose($handle);
		$start = microtime(true);
		while (!file_put_contents($tempfile, $newData)) {
			$this->log("---DELAYED WRITE---");
			if ((microtime(true) - $start) > 1) {
				$this->log("FAILED: COULD NOT WRITE TEMPFILE AFTER 1 SEC: $tempfile");
				return $sourcefile;
			}
		}

        // Avoid reloading same class as different file by keeping a relationship file cache
        if (in_array($sourcefile, array_keys($this->fileCache))){
            $tempfile = $this->fileCache[$sourcefile];
        } else {
            $this->fileCache[$sourcefile] = $tempfile;
        }

        return $tempfile;

    }

    private function getFilesToMod($modfile) {
    	$modfile = realpath($modfile);
        if (file_exists($modfile)) {
            set_error_handler(array(&$this, 'HandleXmlError'));
            try {
                $dom = new DOMDocument('1.0', 'UTF-8');
                $dom->load($modfile);
            } catch (Exception $e) {
                $this->log('Caught exception: ' .  $e->getMessage());
                return array();
            }
            restore_error_handler();
            $modification = $dom->getElementsByTagName('modification')->item(0);
            $files = $modification->getElementsByTagName('file');
            $filepaths = array();
            foreach ($files as $file) {
                $filepaths[] = $file->getAttribute('name');
            }
            return $filepaths;
        }
    }

    private function applyMod(&$data, $search, $add, $position = 'replace', $offset = 0, $index = FALSE, $regex = FALSE, $exists = 'skip') {
        // Check if the mod already exists...
        //if ($exists == 'skip') { $exists = trim($add); }
        //if ($this->searchDataForString($data, $match)) { //$msg = "File: '$file' already has modification" . "<br/>"; }

		if ($index && is_string($index)) {
            $index = explode(',', $index);
            if(!count($index))
            $index = false;
        }
		
		$positions = array('before','after','replace','top','bottom','all');

		if (!$position || !in_array($position, $positions)) {
			return $data;
		}

		if (!is_numeric($offset)) {
			$offset = 0;
		}

		if ($position == 'top') {
			if ($offset) {
				$filedata = explode("\n", trim($data));
               	$existing_line = ($filedata[0 + $offset]);
                $filedata[0 + $offset] = ($add . $existing_line);
                $data = implode("\n", $filedata);
                return $data;
			} else {
				return ($add . $data);
			}
		} elseif ($position == 'bottom') {
			if ($offset) {
				$filedata = explode("\n", trim($data));
				$existing_line = ($filedata[count($filedata) - 1 - $offset]);
                $filedata[count($filedata) - 1 - $offset] = ($existing_line . $add);
                $data = implode("\n", $filedata);
                return $data;
			} else {
				return ($data . $add);
			}
		} elseif ($position == 'all') {
			return $add;
		}

	

        // If not, then add it if the search exists...
        if ($firstlinematch = $this->searchDataForString($data, $search, $regex)) {

            $filedata = explode("\n", $data);

            $count = 0;

            foreach ($filedata as $linenum => $line) {

            	// Since we already have the first line match from the initial search, skip ahead to improve performace
            	if ($linenum < $firstlinematch-1) { continue; }

                $line = rtrim($line, "\r\n") . PHP_EOL;
                if($regex) {
                	$pos = preg_match($search, $line);
				} else {
                	$pos = strpos($line, $search);
				}
                if ($pos !== false) {
                	$count++;
	                if ($index && is_array($index)) {
						if (!in_array($count, $index)) { continue; }
					} else if ($index && ($index != $count)) {
						continue;
					}
                    if ($position == 'before') { // before
                        //$existing_line = (!$offset) ? $filedata[$linenum] : '';
                        $existing_line = ($filedata[$linenum - $offset]);
                        $filedata[$linenum - $offset] = ($add . "\n" . $existing_line);
                    } elseif ($position == 'after') { //after
                        //$existing_line = (!$offset) ? $filedata[$linenum + $offset] : '';
                        $existing_line = ($filedata[$linenum + $offset]);
                        $filedata[$linenum + $offset] = ($existing_line . "\n" . $add);
                    } elseif ($position == 'replace') { //replace
                        $existing_line = "";
                        if ($offset) {
							for ($i=0; $i<=$offset; $i++) {
								$filedata[$linenum + $i] = '';
							}
							$filedata[$linenum] = $add;
						} elseif ($regex) {
							$filedata[$linenum] = preg_replace($search, $add, $filedata[$linenum]);
						} else {
                        	$filedata[$linenum] = str_replace($search, $add, $filedata[$linenum]);
						}
                    }
                }
            }
            return implode("\n", $filedata);
        }

    }

	private function log($message = 'unknown', $filename = false) {
		if (!$filename) { $filename = $this->logFileName; }
		if ($this->logging) {
			$message = explode("\r\n", $message);
			foreach ($message as $k => $msg) {
				//if (!$msg) { continue; }
				//$message[$k] = (date('Y-m-d G:i:s') .  substr((string)microtime(), 1, 8) . ' - ' . ($msg) . "\r\n");
				$message[$k] = "$msg\r\n";
			}
			file_put_contents($this->cwd . '/' . $this->logFilePath . '/' . $filename, implode("", $message), FILE_APPEND);
		}
	}

    private function searchDataForString($data, $string, $regex = false) {

   		// Disable php warnings for invalid regex patterns so that the vqmod.log can catch it
	    $old_error = error_reporting(0);

        // open file to an array
        $fileLines = explode("\n", $data);

        // loop through lines and look for search term
        $lineNumber = 1;
        foreach($fileLines as $line) {
        	if ($regex) {
        		$matches = array();
				$searchCount = preg_match($string, $line, $matches); //matches used only for debugging
			} else {
				$searchCount = substr_count($line, $string);
			}
            if($searchCount > 0) {
            	error_reporting($old_error);  // Set error reporting to old level
                return $lineNumber;
            }
            $lineNumber++;
        }
    }

    private function super_unique($array) {
        $result = array_map("unserialize", array_unique(array_map("serialize", $array)));

        foreach ($result as $key => $value) {
            if ( is_array($value) ) {
                $result[$key] = $this->super_unique($value);
            }
        }

        return $result;
    }

    function HandleXmlError($errno, $errstr, $errfile, $errline) {
        if ($errno==E_WARNING && (substr_count($errstr,"DOMDocument::load()")>0)) {
            throw new DOMException($errstr);
        } else {
            return false;
        }
    }

	private function testRegEx($pattern, $subject) {
		$old_error = error_reporting(0); // Turn off error reporting
		$valid = @preg_match($pattern, $subject);
		if ($valid === false) {
			return False;
		}
		error_reporting($old_error);  // Set error reporting to old level
		return True;
	}

    function XmlLoader($strXml) {
        set_error_handler(array(&$this, 'HandleXmlError'));
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXml($strXml);
        restore_error_handler();
        return $dom;
    }

    function XmlFileLoader($strFile) {
        set_error_handler(array(&$this, 'HandleXmlError'));
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->load($strFile);
        restore_error_handler();
        return $dom;
    }
}

 /**
 * History
 * --------------------------------------
 * v1.2.3 - 2011-JUN-21 - Qphoria@gmail.com
 * - Fixed index explode issue per JayGs fix
 *
 * v1.2.2 - 2011-JUN-17 - Qphoria@gmail.com
 * - Fixed index and empty source bugs per JNs suggestions
 *
 * v1.2.1 - 2011-JUN-17 - Qphoria@gmail.com
 * - Fixed bug with index not being converted to an array
 * - Fixed issue with invalid routes returning true on sourcefile (thanks JN)
 * - Bypass logging for "Skipped" errors (for now)
 *
 * v1.2.0 - 2011-JUN-16 - Qphoria@gmail.com
 * - Completely revamped logging to only write errors to vqmod.log. No point in writing the rest.
 * - Added work around for realpath() on source path returning false on some servers
 * - Added optional trim attribute to search tag (defaults to true).
 * - Removed is_numeric call on index since index is a comma-delimted field
 *
 * v1.1.0 - 2011-JUN-01 - Qphoria@gmail.com
 * - Fixed another bug with source path on some servers
 * - Changed fwrite to file_put_contents for tempfiles
 * - Added write delays to tempfiles
 * - useCache by default if less than 1 second since last page load
 *
 * v1.0.9 - 2011-MAY-18 - Qphoria@gmail.com
 * - Fixed bug with source path on some servers
 * - Updated readme to be clearer
 * - Added .htaccess to protect xml and log files
 *
 * v1.0.8 - 2011-JAN-19 - Qphoria@gmail.com
 * - Fixed bug with a separate vqmod.log file being created in the admin
 * - Added logging for source files that are referenced but don't exist to help troubleshoot
 *
 * v1.0.7 - 2011-JAN-18 - Qphoria@gmail.com
 * - Default directory structure changed to put everything inside the /vqmod/ folder
 * - xml files are now moved to /vqmod/xml/*.xml
 * - Redesigned the construct to be simpler
 * - Construct no longer requires a path or log option
 * - New init() function handles initialization
 * - Improved log function to use file_put_contents instead of fopen
 * - Set logging = true by default
 *
 * v1.0.6 - 2011-JAN-12 - Qphoria@gmail.com
 * - Added RegEx Support
 * - Added new "all" position to replace entire file
 * - Added new protected file list option to prevent some files from being modded for security
 * - Additional performance improvements
 * - Added divider lines to logging to improve readability between files
 * - Added version to log print
 *
 * v1.0.5 - 2011-JAN-03 - Qphoria@gmail.com
 * - Fixed bug with search "offset" duplicating the existing line
 *
 * v1.0.4 - 2010-DEC-30 - Qphoria@gmail.com
 * - Fixed bug with search "index" attribute
 * - Added "offset" attribute to <search> tag for blind multiline actions
 * - Added more code checks to ensure xml values are valid
 *
 * v1.0.3 - 2010-DEC-29 - Qphoria@gmail.com
 * - Overhauled the temp file and debug process to store the modified versions in the vqcache folder
 * - Added new "index" attribute for <search>
 * - Added useCache option for reusing cached versions
 * - Added logging option
 * - Added top and bottom positions to search for adding at the very top or bottom of a file
 * - Added XML fileload handler
 * - Updated the xml field legend
 * - Added ability to write to the actual source file instead of virtually modding
 * - Removed old debug mode and replaced with logging
 * - Improved code performance
 *
 * v1.0.2 - 2010-DEC-23 - Qphoria@gmail.com
 * - Added support for <operation> "error" attribute (skip, abort)
 * - Added <vqmver> tag to identify the minimum version of VQMod needed for a mod to work
 *
 * v1.0.1 - 2010-DEC-23 - Qphoria@gmail.com
 * - Bug fix for relative path in subdirectory
 *
 * v1.0.0 - 2010-DEC-22 - Qphoria@gmail.com
 * - Original release
 *
 */
?>