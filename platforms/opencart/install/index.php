<?php

$changes = 0;

// Load class required for installation
require('ugrsr.class.php');

// get directory above vqmod dir
$opencart_path = realpath(dirname(__FILE__) .'/../../');

// Create new UGRSR class
$u = new UGRSR($opencart_path);

// Set file searching to off
$u->file_search = false;

// Add both index files to files to include
$u->addFile('index.php');

// Pattern to add vqmod include 
$u->addPattern('~// Startup~', '// VirtualQMOD
require_once(\'./vqmod/vqmod.php\');
$vqmod = new VQMod();

// VQMODDED Startup');

$changes += $u->run();

$u->clearPatterns();
$u->resetFileList();
$u->addFile('admin/index.php');

// Pattern to add vqmod include 
$u->addPattern('~// Startup~', '//VirtualQMOD
require_once(\'../vqmod/vqmod.php\');
$vqmod = new VQMod();

// VQMODDED Startup');


$changes += $u->run();
$u->clearPatterns();

$u->addFile('index.php');

// Pattern to run required files through vqmod
$u->addPattern('/require_once\(DIR_SYSTEM \. \'([^\']+)\'\);/', 'require_once($vqmod->modCheck(DIR_SYSTEM . \'$1\'));');

// Get number of changes during run
$changes += $u->run();

// output result to user
if(!$changes) die('VQMOD ALREADY INSTALLED!');
die('VQMOD HAS BEEN INSTALLED ON YOUR SYSTEM!');