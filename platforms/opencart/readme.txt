----------------------
vQmod for OpenCart 
http://vQmod.com
----------------------

Supported Versions
===================
vQmod works on both OpenCart 1.4.x and 1.5.x

How to install
===================
1. Download the latest version that has "opencart" in the title from 
   http://code.google.com/p/vqmod
2. Using FTP, upload the "vqmod" folder from the zip to the root of your opencart store.
3. Be sure the vqmod folder and the vqmod/vqcache folders are writable (either 755 or 777).
   Also be sure index.php and admin/index.php are writable. 
   If not sure which you need, first try 755. 
   If you get errors about permissions, then try 777.
4. Goto http://www.yoursite.com/vqmod/install 
5. You should get a success message. If not, check permissions above and try again
6. Load your store homepage and verify it works.
7. Using FTP, verify that there are new "vq" files in the "vqmod/vqcache" folder.
8. If yes, then you are ready to start downloading or creating vQmod scripts, otherwise ask for assistance.
Done!

DO NOT DELETE THE INSTALL FOLDER!
YOU MUST RUN THE INSTALLER EVERY TIME YOU UPGRADE OPENCART!!
THERE IS NO DANGER OF RE-RUNNING THE INSTALLER!


About vQmod Scripts
===================
vQmod scripts are simple xml files that you upload into the vqmod/xml folder. 
If you want to disable a mod, simply remove the script from this folder. 
After installing a new mod, test it out and verify that no errors are being written to the vqmod/vqmod.log file. 
If you see errors in that file, be sure you contact the script author for assistance.

The OpenCart development community has made hundreds of vQmod mod scripts in the free and commercial vqmod forums
Almost all developers use vQmod for their other larger modifications that require core alterations.

Free vQmods: http://forum.opencart.com/viewforum.php?f=131
Commercial vQmods: http://forum.opencart.com/viewforum.php?f=165