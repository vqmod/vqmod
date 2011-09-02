THIS DOCUMENT INCLUDES STEPS ON USING THE AUTOINSTALLER (RECOMMENDED!)

THIS VERSION OF VQMOD WILL WORK WITH BOTH
OPENCART 1.4.x AND 1.5.x

FOLLOW THIS GUIDE TO INSTALL VQMOD ON OPENCART
VQMOD HAS BEEN TESTED WITH OPENCART 1.4.x AND 1.5.x

BE SURE TO VISIT http://code.google.com/p/vqmod FOR GENERAL VQMOD USAGE INFORMATION

THIS IS THE ONLY STEP YOU NEED TO DO MANUAL CHANGES FOR
KEEP IN MIND THAT WHEN YOU UPGRADE OPENCART THAT THE INDEX FILES CHANGE
YOU WILL NEED TO MAKE THESE CHANGES AGAIN FOR EACH UPGRADE
=======================================================

=======================================================
=======================================================
================ VQMOD AUTO INSTALLER =================
============== OpenCart v1.4.x & 1.5.x ================
=======================================================
=======================================================

1. Download the main vQmod class first

2. Upload the "vqmod" folder to the opencart root (same place as your index.php file)

3. Set the following folders to be writable:
	/vqmod
	/vqmod/vqcache

	* Some hosts require 0755 and some require 0777
	* If you get file write errors then you likely need 0777
	* If you get Internal Server Error 500 then you likely need 0755
	* If it looks like vQmod isn't creating vqcache files or vqmod.log file, check the perms.


4. Upload the "install" and "xml" folder from the opencart platform zip into the "vqmod" folder

5. Browse to: 
	
	http://www.yourstore.com/vqmod/install
		or, if using subdirectory:
	http://www.yoursite.com/shop/vqmod/install
		
6. You should see a success message. That's it!


DO NOT DELETE THE INSTALL FOLDER!
YOU MUST RUN THE INSTALLER EVERY TIME YOU UPGRADE OPENCART!!
THERE IS NO DANGER OF RE-RUNNING THE INSTALLER!

