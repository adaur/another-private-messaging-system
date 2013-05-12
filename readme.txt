##
##
##        Mod title:  Another Private Messaging-Topic System
##
##      Mod version:  3.0.5
##  Works on FluxBB:  1.5.0, 1.5.1, 1.5.2, 1.5.3
##     Release date:  2013-05-11
##           Author:  adaur (adaur.underground@gmail.com)
##      FluxBB repo:  https://fluxbb.org/resources/mods/another-private-messaging-system
##      GitHub repo:  https://github.com/adaur/another-private-messaging-system
##  English support:  https://fluxbb.org/forums/viewtopic.php?id=4315
##   French support:  http://fluxbb.fr/forums/viewtopic.php?id=10824
##		  
##         Thanks to: jojaba (1 click installation)
##         			  Otomatic (Mod Installer)
##         			  vin100, Connorhd, David Djurbäck (old PM messaging style)
##                    All testers from FluxBB.org/fr :)
##
##      Description:  Private Messaging System for FluxBB.
##					  Messages are displayed as topics.
##
##   Affected files:  include/common.php
##                    include/functions.php
##                    header.php
##                    profile.php
##                    viewtopic.php
##
##       Affects DB:  New tables:
##                       'messages'
##                       'contacts'
##                    New options:
##                       'o_pms_enabled'
##                       'o_pms_mess_per_page'
##                       'o_pms_max_receiver'
##                       'o_pms_notification'
##                    New users option:
##                       'notify_pm'
##                       'notify_pm_full'
##						 'num_pms'
##                    New groups permissions:
##                       'g_pm'
##                       'g_pm_limit'
##
##
##       DISCLAIMER:  Please note that "mods" are not officially supported by
##                    FluxBB. Installation of this modification is done at your
##                    own risk. Backup your forum database and any and all
##                    applicable files before proceeding.
##
##


#-------------------------------------------------------------------------------
#
#---------[ UPGRADE ]-----------------------------------------------------------


To update from previous version of Another Private Messaging-Topic System (before 3.0.0),
please delete all modifications in your PHP files, then install this one.

If you have 3.0.0/3.0.1/3.0.2/3.0.3/3.0.4 installed, uninstall it with Mod Installer,
replace your old files by the new ones and launch the installation via Mod Installer.
PLEASE run the install_mod (it is modified to keep the "messages" table untouched).


#-------------------------------------------------------------------------------
#
#---------[ 1. UPLOAD ]---------------------------------------------------------
#

Upload all the files from the folder /files to your FTP.


#
#---------[ 2. RUN ]------------------------------------------------------------
#

install_mod.php


#
#---------[ 3. DELETE ]---------------------------------------------------------
#

install_mod.php

#
#---------[ 4. GO TO ]----------------------------------------------
#

Go to the plugin administration page and launch Mod Installer.
Click on 'Change' then 'Install the Mod'.

That's it! Enjoy your mod installed in 1 minute :)
