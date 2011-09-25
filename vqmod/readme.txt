----------------------------------------
vQmod™ - Virtual File Modification System
----------------------------------------

ABOUT:
=========
 * @author Qphoria <qphoria@gmail.com> & JayGilford <jay@jaygilford.com>
 * @copyright (c) 2010-2011
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @site: http://code.google.com/p/vqmod

"vQmod"(TM) (aka Virtual Quick Mod) is a new innovation in php modification override methods.
Instead of modifying actual files to add custom modifications, source files are parsed "on-the-fly" before the php include() or require() is called.
The source is cloned to a temp file and modifications are made to that temp file, then substituted for the real file in the include path.

=====================================
See website for additional information, usage, and syntax:
http://code.google.com/p/vqmod
=====================================



HISTORY:
============
v2.0 - 2011-SEP-14 - Jay@jaygilford.com
 - Complete Code Rewrite by JayG to be more object based
 - New "log" attribute for <operation error="abort|skip|log"> to skip and log.
 
v1.2.3 - 2011-JUN-21 - Qphoria@gmail.com
 - Fixed index explode issue per JayGs fix

v1.2.2 - 2011-JUN-17 - Qphoria@gmail.com
 - Fixed index and empty source bugs per JNs suggestions

v1.2.1 - 2011-JUN-17 - Qphoria@gmail.com
 - Fixed bug with index not being converted to an array
 - Fixed issue with invalid routes returning true on sourcefile (thanks JN)

v1.2.0 - 2011-JUN-16 - Qphoria@gmail.com
 - Completely revamped logging to only write errors to vqmod.log. No point in writing the rest.
 - Added work around for realpath() on source path returning false on some servers
 - Added optional trim attribute to search tag (defaults to true).
 - Removed is_numeric call on index since index is a comma-delimited field

v1.1.0 - 2011-JUN-01 - Qphoria@gmail.com
 - Fixed another bug with source path on some servers
 - Changed fwrite to file_put_contents for tempfiles
 - Added write delays to tempfiles

v1.0.9 - 2011-MAY-18 - Qphoria@gmail.com
- Fixed bug with source path on some servers
- Updated readme to be clearer
- Added .htaccess to protect xml and log files

v1.0.8 - 2011-JAN-19 - Qphoria@gmail.com
- Fixed bug with a separate vqmod.log file being created in the admin
- Added logging for source files that are referenced but don't exist to help troubleshoot

v1.0.7 - 2011-JAN-18 - Qphoria@gmail.com
- Default directory structure changed to put everything inside the /vqmod/ folder
- xml files are now moved to /vqmod/xml/*.xml
- Redesigned the construct to be simpler
- Construct no longer requires a path or log option
- New init() function handles initialization
- Improved log function to use file_put_contents instead of fopen
- Set logging = true by default

v1.0.6 - 2011-JAN-12 - Qphoria@gmail.com 
- Added RegEx Support
- Added new "all" position to replace entire file
- Added new protected file list option to prevent some files from being modded for security
- Additional performance improvements
- Added divider lines to logging to improve readability between files
- Added version to log print

v1.0.5 - 2011-JAN-03 - Qphoria@gmail.com 
- Fixed bug with search "offset" duplicating the existing line

v1.0.4 - 2010-Dec-30 - Qphoria@gmail.com
- Fixed bug with <search> "index" attribute
- Added "offset" attribute to <search> tag for blind multiline actions
- Added more code checks to ensure xml values are valid
 
v1.0.3 - 2010-Dec-29 - Qphoria@gmail.com
- Overhauled the temp file and debug process to store the modified versions in the vqcache folder
- Added new "index" attribute for <search>
- Added useCache option for reusing cached versions
- Added logging option
- Added top and bottom positions to search for adding at the very top or bottom of a file
- Added XML fileload handler
- Updated the xml field legend
- Added ability to write to the actual source file instead of virtually modding
- Removed old debug mode and replaced with logging
- Improved code performance
- Changed vqdbg to vqcache
- Updated Readme to be more robust

v1.0.2 - 2010-Dec-23 - Qphoria@gmail.com
- Added support for <operation> "error" attribute (skip, abort)
- Added <vqmver> tag to identify the minimum version of VQMod needed for a mod to work

v1.0.1 - 2010-Dec-23 - Qphoria@gmail.com
- Fix for relative path when calling from a subfolder like "admin"

v1.0.0 - 2010-Dec-22 - Qphoria@gmail.com
- Original Version
