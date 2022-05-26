-----------------------------------------
vQmod™ - Virtual File Modification System
-----------------------------------------

ABOUT:
======
 * @author Qphoria <qphoria@gmail.com> & Jay Gilford <jay@jaygilford.com>
 * @copyright (c) 2010-2022
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @site: https://vqmod.com

"vQmod"(TM) (aka Virtual Quick Mod) is an innovation modification override method for php.
Instead of modifying actual files to add custom modifications, source files are parsed
"on-the-fly" before the php include() or require() is called.
The source is cloned to a temp file and modifications are made to that temp file, then
substituted for the real file in the include path. The original files are never altered.

==========================================================
See website for additional information, install, usage, and syntax:
https://vqmod.com
==========================================================