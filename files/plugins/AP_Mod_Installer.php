<?php

/**
 * Copyright (C) 2008-2011 FluxBB - Significantly altered by Otomatic
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 ***********************************************************************
 To be managed by Mod Installer, the mods should have the following structure:
 - All files must be in a folder, named with the abbreviated name of the mod.
	 This mod folder must be in "your_forum/plugins/" folder.
	 In the folder of the mod there must be:
 - A configuration file nammed "mod_config.php".
	 The contents of this file is detailed in readme.txt
 - A search, insert or replace strings file nammed "search_insert.php"
 - A folder nammed "lang" with one or more language folder with the same name
	 as for FluxBB languages folders. Each language file must be nammed "mod_admin.php"
	 Necessarily, there must be: plugins/mod_abbr/lang/English/mod_admin.php
	 and the name of language array must be "$lang_plugin_admin"
See readme.txt for more explanations and details
 */

// Make sure no one attempts to run this script "directly"
if (!defined('PUN')) exit;

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);

/*Load the admin installer language file
Must be nammed plugins/mod_installer/lang/your_language/plugin_admin.php */
if(file_exists('plugins/mod_installer/lang/'.$admin_language.'/plugin_admin.php'))
	require 'plugins/mod_installer/lang/'.$admin_language.'/plugin_admin.php';
else 
	require 'plugins/mod_installer/lang/English/plugin_admin.php';
	
// Load mod installer configuration file
require 'plugins/mod_installer/plugin_config.php';

//Open the plugins folder and look for files that match the required criteria.
$plugin_config = array();
$nb_mod = 0;
if ($dh = opendir('plugins')) {
	while (($dir = readdir($dh)) !== false) {
		if(filetype('plugins/'.$dir) == "dir" && $dir != "." && $dir != ".." && $dir != "mod_installer") {
			if($dh1 = opendir('plugins/'.$dir)) {
				while (($file = readdir($dh1)) !== false) {
					if($file == "mod_config.php") {
						require 'plugins/'.$dir.'/mod_config.php';
						// Make sure we are running a Mod Installer version that this mod works with
						if((isset($mod_config['mod_installer']) || isset($mod_config['mod_installer_versions'])) && (file_exists('plugins/'.$dir.'/search_insert.php') && file_exists('plugins/'.$dir.'/lang/English/mod_admin.php'))) {
							$plugin_config['OK'][$nb_mod] = true;
							if(isset($mod_config['mod_installer_versions']) && !in_array($installer_config['version'],$mod_config['mod_installer_versions']))
								$plugin_config['OK'][$nb_mod] = implode(" ",$mod_config['mod_installer_versions']);
							$plugin_config['name'][$nb_mod] = $mod_config['mod_name'];
							$plugin_config['folder'][$nb_mod] = $dir;
							$plugin_config['installed'][$nb_mod] = $mod_config['mod_status'];
							$plugin_config['abbr'][$nb_mod] = $mod_config['mod_abbr'];
							$nb_mod++;
						}
						unset($mod_config);
					}
				}
				closedir($dh1);
			}
		}
	}
	closedir($dh);
}
//Sort mods by name
if($nb_mod > 1)
	array_multisort($plugin_config['name'], SORT_ASC, $plugin_config['folder'], $plugin_config['installed'], $plugin_config['abbr'], $plugin_config['OK']);

//Is there a mod to install or uninstall?
$mod_install = isset($_REQUEST['mod_install']) ? intval($_REQUEST['mod_install']) : 0;
$mod_datadir = isset($_REQUEST['dir']) ? pun_htmlspecialchars($_REQUEST['dir']) : "";
$database_drop_fields = isset($_REQUEST['database_drop_fields']) ? $_REQUEST['database_drop_fields'] : false;
$database_drop_values = array();
$database_drop_values = isset($_REQUEST['database_drop_values']) ? $_REQUEST['database_drop_values'] : false;
if(empty($mod_datadir) || !in_array($mod_datadir, $plugin_config['folder'])) $mod_install = 0;

if($mod_install) { // Structure for install/uninstall a mod or create install file
	
	define('CONFIG_FILE', 'plugins/'.$mod_datadir.'/mod_config.php');
	
	/* Retrieving configuration values */
	require CONFIG_FILE;
	
	//File for search, insert and replace strings must be nammed : search_insert.php
	require 'plugins/'.$mod_datadir.'/search_insert.php';
	$list_files = array();
	$list_base = array();
	// Do not modify the order below, otherwise some mods cannot be installed
	// 1st files_to_insert - 2nd files_to_add - 3rd files_to_replace - 4th files_to_move
	if(isset($files_to_insert)) $list_files[] = "files_to_insert";
	if(isset($files_to_add)) $list_files[] = "files_to_add";
	if(isset($files_to_replace)) $list_files[] = "files_to_replace";
	if(isset($files_to_move)) {
		$list_files[] = "files_to_move";
		$move_start = "//modif oto - mod ".$mod_config['mod_name']." - Beginning of the block moved\n";
		$move_end = "//modif oto - mod ".$mod_config['mod_name']." - End of the block moved\n";
	}
	//Database to modify
	if(isset($fields_to_add)) $list_tables[] = "fields_to_add";
	if(isset($config_to_insert)) $list_tables[] = "config_to_insert";
	
	/*Load the admin language file
		Must be named plugins/mod_folder/lang/your_language/mod_admin.php */
	if(file_exists('plugins/'.$mod_datadir.'/lang/'.$admin_language.'/mod_admin.php'))
		require 'plugins/'.$mod_datadir.'/lang/'.$admin_language.'/mod_admin.php';
	else 
		require 'plugins/'.$mod_datadir.'/lang/English/mod_admin.php';
	
	// Make sure we are running a FluxBB version that this mod works with
	$version_warning = !in_array($pun_config['o_cur_version'], $mod_config['fluxbb_versions']);
	
	/* **************************** */
	/* Functions used by the plugin */
	/* **************************** */
	$temp_status = array();
	$mod_plugin_status = array();
	
	function update_config_file($status) {
		global $mod_config;
		$mod_config['mod_status'] = $status;
		$fp = fopen(CONFIG_FILE, 'wb');
		fwrite($fp, '<?php'."\n\n".'$mod_config = '.var_export($mod_config, true).';'."\n\n".'?>');
		fclose($fp);
	}
	
	// Return true if only one file is not writable
	function files_not_writable() {
		global $mod_plugin_status, $list_files, $files_to_insert, $files_to_replace, $files_to_add, $files_to_move, $lang_installer_admin;
		$not_writable = false;
		if(!is_writable(CONFIG_FILE)) {
			$mod_plugin_status[] = '<span style="color: red; font-weight: bold;">'.sprintf($lang_installer_admin['Not writable'],CONFIG_FILE).'</span>';
			$not_writable = true;
		}
		foreach($list_files as $file_name) {
			foreach($$file_name as $file_value) {
				if(!is_writable($file_value)) {
					$mod_plugin_status[] = '<span style="color: red; font-weight: bold;">'.sprintf($lang_installer_admin['Not writable'],$file_value).'</span>';
					$not_writable = true;
				}
			}
		}
		return $not_writable;
	}
	
	// Return true if one string to be replaced is not found in files or found more than one time
	function string_not_found($mode="install") {
		global $mod_plugin_status, $list_files, $files_to_insert, $files_to_replace, $files_to_add, $files_to_move, $lang_installer_admin, $insert_file, $search_file, $insert_replace_file, $search_replace_file, $search_add_file, $insert_add_file, $move_get_start, $move_to_start, $move_get_end, $move_to_end;
		if($mode == "install") {
			$type_file = "search_";
			$type_move = "move_get";
		}
		elseif($mode == "uninstall") {
			$type_file = "insert_";
			$type_move = "move_to";
		}
		else exit("Wrong value ($mode) for parameter type - Must be 'install' or 'uninstall'");
		$not_found = false;
		foreach($list_files as $file_name) {
			$type = $type_file;
			if($file_name == "files_to_insert") $type .= "file";
			elseif($file_name == "files_to_replace") $type .= "replace_file";
			elseif($file_name == "files_to_add") $type .= "add_file";
			foreach($$file_name as $file_value) {
				list($name_file,$ext_file) = explode('.',$file_value);
				if($file_name == "files_to_move") {
					$type = $type_move; //Only one move per file
					//move_to_start and move_to_end MUST be two consecutive lines
					$move_to[$name_file][0] = $move_to_start[$name_file][0].$move_to_end[$name_file][0];
					//For install, verify presence of "move from" reference lines (get_start and get_end)
					//and that "move to" reference lines are consecutives.
					$move_get[$name_file] = array_merge($move_get_start[$name_file], $move_get_end[$name_file],$move_to[$name_file]);
					//For uninstall, verify presence of "move from" and "move to" reference lines.
					$move_to[$name_file] = array_merge($move_to_start[$name_file], $move_to_end[$name_file],$move_get_start[$name_file], $move_get_end[$name_file]);
				}
				$file_content = file_get_contents($file_value);
				$nb_test = 1;
				//Look for presence of strings to be replaced in files: Each only once
				foreach(${$type}[$name_file] as $file_string) {
					$count_found = substr_count($file_content,$file_string);
					if($count_found == 0) {
						$mod_plugin_status[]='<span style="color:#FF8C00; font-weight: bold;">'.sprintf($lang_installer_admin['Not correct '.$mode],$file_value,$nb_test-1).'</span> ($'.$type.')';
						$not_found = true;
					}
					elseif($count_found > 1) {
						$mod_plugin_status[]='<span style="color:#FF8C00; font-weight: bold;">'.sprintf($lang_installer_admin['Not correct count'],$file_value,$nb_test-1,$count_found).'</span> ($'.$type.')';
						$not_found = true;
					}
					$nb_test++;
				}
			}
		}
		return $not_found;
	}
	// Write file with content
	function write_file($file, $content) {
		$fp = fopen ($file, 'wb');
		fwrite ($fp, $content);
		fclose ($fp);
	}
	/* End of functions */
	
	// Already installed once ?
	if ($mod_config['date_install'] != 0) {
		$first_install = false;
		$mod_install_date = date($lang_installer_admin['Date format'], $mod_config['date_install']);
	}
	else {
		$first_install = true;
		$mod_install_date = "";
	}
	
	//Even if the state is "not installed" or "installed", look if the mod is installed or not.
	$already_installed = $uninstall_fail = $install_fail = $no_install_file = true;
	$nb_test_all = $nb_false = $nb_true = 0;
	foreach($list_files as $file_name) {
		$type = "insert_";
		if($file_name == "files_to_insert") $type .= "file";
		elseif($file_name == "files_to_replace") $type .= "replace_file";
		elseif($file_name == "files_to_add") $type .= "add_file";
		elseif($file_name == "files_to_move") break;
		foreach($$file_name as $file_value) {
			$correct_file = $correct_count = true;
			$nb_test = 0;
			list($name_file,$ext_file) = explode('.',$file_value);
			$file_content = file_get_contents($file_value);
			$index_max = count(${$type}[$name_file]) - 1;
			foreach(${$type}[$name_file] as $file_string) {
				$nb_test++;
				$nb_test_all++;
				$count_found = substr_count($file_content,$file_string);
				if($count_found == 0) {
					$temp_status[] = '<span style="color:#FF8C00; font-weight: bold;">'.sprintf($lang_installer_admin['Plugin wrong installation'],$file_value,$nb_test-1,$index_max).'</span> ($'.$type.')';
					$correct_file = $already_installed = false;
					$nb_false++;
				}
				elseif($count_found > 1) {
					$temp_status[] = '<span style="color:#FF8C00; font-weight: bold;">'.sprintf($lang_installer_admin['Plugin wrong count'],$file_value,$nb_test-1,$index_max,$count_found).'</span> ($'.$type.')';
					$correct_file = $correct_count = false;
					$nb_false++;
				}
				else $nb_true++;
			}
			if($correct_file) $temp_status[] = '<span style="color: green;">'.sprintf($lang_installer_admin['Plugin in action'],$mod_config['mod_abbr'],$file_value).'</span>';
			unset($file_content); 
		}
	}
	
	if ($mod_config['mod_status'] == 0) { //Theoretically not installed.
		$mod_plugin_status[] = '<span style="color:green; font-weight: bold;">'.sprintf($lang_installer_admin['Not installed'],$mod_config['mod_abbr']).'</span>';
		if($nb_true == 0) {
			$mod_plugin_status[] = '<span style="color: red; font-weight: bold;">'.sprintf($lang_installer_admin['Plugin removed'],$mod_config['mod_abbr']).'</span>';
			$install_fail = false;
		 }
		else {
			foreach($temp_status as $value) $mod_plugin_status[] = $value;
			if($nb_true == $nb_test_all) {
				$mod_plugin_status[] = '<span style="color: green; font-weight: bold;">'.sprintf($lang_installer_admin['Install OK'],$mod_config['mod_abbr']).'</span>';
				$uninstall_fail = false;
				update_config_file(1);
			}
		}
	}
	else { //Theoretically installed.
		$mod_plugin_status[] = '<span style="color:green; font-weight: bold;">'.sprintf($lang_installer_admin['Installed'],$mod_config['mod_abbr']).'</span>';
		foreach($temp_status as $value) $mod_plugin_status[] = $value;
		if($nb_true == $nb_test_all) {
			$mod_plugin_status[] = '<span style="color: green; font-weight: bold;">'.sprintf($lang_installer_admin['Install OK'],$mod_config['mod_abbr']).'</span>';
			$uninstall_fail = false;
		}
		elseif($nb_false == $nb_test_all && $correct_count) $mod_plugin_status[] = '<span style="color: green; font-weight: bold;">'.$lang_installer_admin['May install'].'</span>';
	}
	/* ---------------------------------------------- */
	/* If the create install file button is validated */
	if (isset($_POST['readme'])) {
		$step = 1;
		$readme_file = 'plugins/'.$mod_datadir.'/'.$mod_config['mod_abbr'].'_install.txt';
		$fp_read = @fopen($readme_file, 'wb');
		if($fp_read !== false) {
			fwrite($fp_read, "#--- MOD ".$mod_config['mod_name']." (".$mod_config['mod_abbr'].")\n");
			fwrite($fp_read, "Version: ".$mod_config['version']."\nRelease date: ".$mod_config['release_date']."\n");
			fwrite($fp_read, strip_tags($lang_plugin_admin['Explanation'])."\n\n");
			fwrite($fp_read, str_pad("[ MANUAL INSTALLATION PROCEDURE ]",78,"=",STR_PAD_BOTH)."\n\n");
			fwrite($fp_read, "Note: There may be several times the opening and saving the same file.\nThis is due to changes order that are made by type and not by files.\nAre performed, in order: Insert before, Add after, Replace then Move\n\n");
			fwrite($fp_read, "#----[ FOLLOW THE FOLLOWING STEPS TO MAKE THE CHANGES TO FILES ]----\n\n");
			//is there database modifications ?
			if(!empty($list_tables)) {
				fwrite($fp_read,str_repeat("*",78)."\n");
			  fwrite($fp_read, str_pad("[ DATABASE MODIFICATIONS ]",78,"=",STR_PAD_BOTH)."\n\n");
			  foreach($list_tables as $base_name) {
			  	foreach($$base_name as $table_value) {
			  		if($base_name == "fields_to_add") {
			  			for($i=0;$i<count($add_field_name[$table_value]);$i++) {
			  				fwrite($fp_read, "ALTER TABLE ".$table_value." ADD ".$add_field_name[$table_value][$i]." ".$add_field_type[$table_value][$i].($add_allow_null[$table_value][$i] ? " NULL" : " NOT NULL").(!is_null($add_default_value[$table_value][$i]) ? " DEFAULT '".$add_default_value[$table_value][$i]."'" : "")."\n");
			  			}
			  			fwrite($fp_read, "\n");
			  		}
			  		elseif($base_name == "config_to_insert") {
							$sql = "INSERT INTO config (conf_name, conf_value)\n\t\tVALUES ";
							for($i = 0;$i < count($values[$table_value]);$i = $i + 2) {
								$sql .= "('".$values[$table_value][$i]."', '".$values[$table_value][$i+1]."'),\n\t\t\t\t\t";
								}
							$sql = substr($sql,0,-7);
			  			fwrite($fp_read, $sql."\n\n");
			  		}
			  	}
			  }
			  fwrite($fp_read, str_pad("[ END OF DATABASE MODIFICATIONS ]",78,"=",STR_PAD_BOTH)."\n");
				fwrite($fp_read,str_repeat("*",78)."\n\n");
			}
			foreach($list_files as $file_name) {
				foreach($$file_name as $file_value) {
					if(!isset($last_file_value) || $file_value != $last_file_value) {
						if(isset($last_file_value)) {
							fwrite($fp_read,str_repeat("*",78)."\n");
							fwrite($fp_read,"#-------[ ".$step." SAVE FILE ".$last_file_value." ]\n");
							$step++;
							fwrite($fp_read,str_repeat("*",78)."\n\n");
						}
						fwrite($fp_read, str_pad("#---[ ".$step." OPEN ]",78,"-")."\n\n    ".$file_value."\n\n");
						$step++;
						$last_file_value = $file_value;
					}
					list($name_file,$ext_file) = explode('.',$file_value);
					if($file_name == "files_to_insert") {
						//Inserting the code before an existing line.
						for($i=0;$i<count($insert_file[$name_file]);$i++) {
							fwrite($fp_read, str_pad("#--[ ".$step." FIND ] Info: \$search_file[".$name_file."][".$i."]",78,"-")."\n\n".$search_file[$name_file][$i]."\n\n");
							$step++;
							fwrite($fp_read, str_pad("#--[ ".$step." INSERT BEFORE ] Info: \$insert_file[".$name_file."][".$i."]",78,"-")."\n\n".$insert_file[$name_file][$i]."\n\n");
							$step++;
						}
					}
					elseif($file_name == "files_to_replace") {
						//Replacing an existing code by another one.
						for($i=0;$i<count($insert_replace_file[$name_file]);$i++) {
							fwrite($fp_read, str_pad("#--[ ".$step." FIND ] Info: \$search_replace_file[".$name_file."][".$i."]",78,"-")."\n\n".$search_replace_file[$name_file][$i]."\n\n");
							$step++;
							fwrite($fp_read, str_pad("#--[ ".$step." REPLACE BY ] Info: \$insert_replace_file[".$name_file."][".$i."]",78,"-")."\n\n".$insert_replace_file[$name_file][$i]."\n\n");
							$step++;
						}
					}
					elseif($file_name == "files_to_add") {
						//Inserting the code before an existing line.
						for($i=0;$i<count($insert_add_file[$name_file]);$i++) {
							fwrite($fp_read, str_pad("#--[ ".$step." FIND ] Info: \$search_add_file[".$name_file."][".$i."]",78,"-")."\n\n".$search_add_file[$name_file][$i]."\n\n");
							$step++;
							fwrite($fp_read, str_pad("#--[ ".$step." ADD AFTER ] Info: \$insert_add_file[".$name_file."][".$i."]",78,"-")."\n\n".$insert_add_file[$name_file][$i]."\n\n");
							$step++;
						}
					}
					elseif($file_name == "files_to_move") {
						//Move code between two lines to another location
						for($i=0;$i<count($move_get_start[$name_file]);$i++) {
							fwrite($fp_read, str_pad("#--[ ".$step." MOVE ALL CODE BETWEEN]",78,"-")."\n\n".$move_get_start[$name_file][$i]."\t\t\tAND\n".$move_get_end[$name_file][$i]."\n\n");
							$step++;
							fwrite($fp_read, str_pad("#--[ ".$step." TO BETWEEN ]",78,"-")."\n\n".$move_to_start[$name_file][$i]."\t\t\tAND\n".$move_to_end[$name_file][$i]."\n\n");
							$step++;
						}
					}
				}
			}
			//For the latest opened file
			fwrite($fp_read,str_repeat("*",78)."\n");
			fwrite($fp_read,"#-------[ ".$step." SAVE FILE ".$file_value." ]\n");
			$step++;
			fwrite($fp_read,str_repeat("*",78)."\n\n");
			
			fwrite($fp_read, str_pad("[ END OF INSTALLATION PROCEDURE ]",78,"=",STR_PAD_BOTH)."\n\n");
			fwrite($fp_read, "This file was created automatically by the plugin Mod Installer\nWritten by Otomatic - fluxbb.fr\n");
			fclose($fp_read);
			$mod_plugin_status[] = sprintf($lang_installer_admin['Readme file'], $readme_file);
			$no_install_file = false;
		}
		else $mod_plugin_status[] = '<span style="color: red; font-weight: bold;">'.sprintf($lang_installer_admin['Not writable'],$readme_file).'</span>';
	}
	
	/* ------------------------------------------------------------------- */
	/* If the Install button is validated or the plugin is newly installed */
	elseif (isset($_POST['install']) && !$already_installed) {
		unset($mod_plugin_status);
		$mod_plugin_status = array();
		$install_fail = files_not_writable() || string_not_found("install");
		
		if(!$install_fail) {
			//is there database modifications to do?
			if(!empty($list_tables)) {
			  foreach($list_tables as $base_name) {
			  	foreach($$base_name as $table_value) {//$table_value is name of table for modifications
			  		if($base_name == "fields_to_add") {
			  			for($i=0;$i<count($add_field_name[$table_value]);$i++) {
			  				//If the field already exist there is no error.
			  				$db->add_field($table_value, $add_field_name[$table_value][$i], $add_field_type[$table_value][$i], $add_allow_null[$table_value][$i], $add_default_value[$table_value][$i]) or error('Unable to add column '.$add_field_name[$table_value][$i].' to table '.$table_value, __FILE__, __LINE__, $db->error());;
			  			}
			  		}
			  		elseif($base_name == "config_to_insert") {
			  			$db->query('BEGIN');
							for($i = 0;$i < count($values[$table_value]);$i = $i + 2) {
								$sql = "SELECT conf_value FROM ".$db->prefix.$table_value." WHERE conf_name = '".$db->escape($values[$table_value][$i])."'";
								$result = $db->query($sql) or error('Unable to SELECT values INTO '.$table_value, __FILE__, __LINE__, $db->error());
								if($db->num_rows($result) == 0 ) $sql = "INSERT INTO ".$db->prefix.$table_value." (conf_name, conf_value) VALUES ('".$db->escape($values[$table_value][$i])."', '".$db->escape($values[$table_value][$i+1])."')";
								else $sql = "UPDATE ".$db->prefix.$table_value." SET conf_value = '".$db->escape($values[$table_value][$i+1])."' WHERE conf_name = '".$db->escape($values[$table_value][$i])."'";
								$db->query($sql) or error('Unable to INSERT or UPDATE values INTO '.$table_value, __FILE__, __LINE__, $db->error());
							}
							$db->query('COMMIT');
			  		}
			  	}
			  }
			}
			//End of database modifications - Begin files modifications
			foreach($list_files as $file_name) {
				foreach($$file_name as $file_value) {
					list($name_file,$ext_file) = explode('.',$file_value);
					$file_content = file_get_contents($file_value);
					$searching = array();
					$replacement = array();
					if($file_name == "files_to_insert") {
						//Inserting the code before an existing line.
						for($i=0;$i<count($insert_file[$name_file]);$i++) {
							$searching[$i] = $search_file[$name_file][$i];
							$replacement[$i] = $insert_file[$name_file][$i].$search_file[$name_file][$i];
						}
					}
					elseif($file_name == "files_to_add") {
						//Adding the code after an existing line.
						for($i=0;$i<count($insert_add_file[$name_file]);$i++) {
							$searching[$i] = $search_add_file[$name_file][$i];
							$replacement[$i] = $search_add_file[$name_file][$i].$insert_add_file[$name_file][$i];
						}
					}
					elseif($file_name == "files_to_replace") {
						//Replacing an existing code by another one.
						for($i=0;$i<count($insert_replace_file[$name_file]);$i++) {
							$searching[$i] = $search_replace_file[$name_file][$i];
							$replacement[$i] = $insert_replace_file[$name_file][$i];
						}
					}
					elseif($file_name == "files_to_move") {
						// Move code between two lines to another location
						for($i=0;$i<count($move_get_start[$name_file]);$i++) {
							$pos_start = strpos($file_content, $move_get_start[$name_file][$i]) + strlen($move_get_start[$name_file][$i]);
	  					$pos_end = strpos($file_content, $move_get_end[$name_file][$i]);
							$move_string = substr($file_content, $pos_start, $pos_end - $pos_start);
	          	
							$searching[] = $move_get_start[$name_file][$i].$move_string.$move_get_end[$name_file][$i];
							$replacement[] = $move_get_start[$name_file][$i].$move_get_end[$name_file][$i];
							$searching[] = $move_to_start[$name_file][$i].$move_to_end[$name_file][$i];
							$replacement[] = $move_to_start[$name_file][$i].$move_start.$move_string.$move_end.$move_to_end[$name_file][$i];
						}
					}
					write_file($file_value, str_replace($searching, $replacement, $file_content));
					$mod_plugin_status[] = '<span style="color: green;">'.sprintf($lang_installer_admin['Plugin in action'],$mod_config['mod_abbr'],$file_value).'</span>';
					unset($file_content, $searching, $replacement);
				}
			}
	
			//Execute install script if exist
			if(file_exists('plugins/'.$mod_datadir.'/update_install.php'))
				require('plugins/'.$mod_datadir.'/update_install.php');
	
			// Updating config
			if ($first_install) {
				$mod_config['date_install'] = time();
				$mod_install_date = date($lang_installer_admin['Date format'], $mod_config['date_install']);
			}
			update_config_file(1);
			$already_installed = true;
			$mod_plugin_status[] = '<span style="color: green; font-weight: bold;">'.sprintf($lang_installer_admin['Install OK'],$mod_config['mod_abbr']).'</span>';
		}
	}
	/* ------------------------------------ */
	/* If the Uninstall button is validated */
	elseif (isset($_POST['remove']) && $already_installed){
		unset($mod_plugin_status);
		$mod_plugin_status = array();
		$uninstall_fail = files_not_writable() || string_not_found("uninstall");
		
		if(!$uninstall_fail) {
			//is there database modifications to do?
			if(!empty($list_tables) && ($database_drop_fields || $database_drop_values)) {
			  foreach($list_tables as $base_name) {
			  	foreach($$base_name as $table_value) {//$table_value is name of table for modifications
			  		if($base_name == "fields_to_add" && $database_drop_fields[$table_value]) {
			  			for($i=0;$i<count($add_field_name[$table_value]);$i++) {
			  				//Drop fields
			  				$db->drop_field($table_value, $add_field_name[$table_value][$i]) or error('Unable to drop column '.$add_field_name[$table_value][$i].' to table '.$table_value, __FILE__, __LINE__, $db->error());;
			  			}
			  		}
			  		elseif($base_name == "config_to_insert" && $database_drop_values) {
							for($i = 0;$i < count($values[$table_value]);$i = $i + 2) {
								$sql = "DELETE FROM ".$db->prefix.$table_value." WHERE conf_name = '".$db->escape($values[$table_value][$i])."'";
								$db->query($sql) or error('Unable to DELETE values FROM '.$table_value, __FILE__, __LINE__, $db->error());
							}
			  		}
			  	}
			  }
			}
			//To uninstall, we proceed in reverse order of installation.
			$list_files = array_reverse($list_files);
			foreach($list_files as $file_name) {
				foreach($$file_name as $file_value) {
					list($name_file,$ext_file) = explode('.',$file_value);
					$searching = array();
					$replacement = array();
					$file_content = file_get_contents($file_value);
					if($file_name == "files_to_insert") {
						//Replace inserting code by nothing.
						$insert_file[$name_file] = array_reverse($insert_file[$name_file]);
						for($i=0;$i<count($insert_file[$name_file]);$i++) {
							$replacement[$i] = '';
							$searching[$i] = $insert_file[$name_file][$i];
						}
					}
					elseif($file_name == "files_to_add") {
						//Replace adding code by nothing.
						$insert_add_file[$name_file] = array_reverse($insert_add_file[$name_file]);
						for($i=0;$i<count($insert_add_file[$name_file]);$i++) {
							$replacement[$i] = '';
							$searching[$i] = $insert_add_file[$name_file][$i];
						}
					}
					elseif($file_name == "files_to_replace") {
						//Replace replaced strings by old ones.
						$search_replace_file[$name_file] = array_reverse($search_replace_file[$name_file]);
						$insert_replace_file[$name_file] = array_reverse($insert_replace_file[$name_file]);
						for($i=0;$i<count($insert_replace_file[$name_file]);$i++) {
							$replacement[$i] = $search_replace_file[$name_file][$i];
							$searching[$i] = $insert_replace_file[$name_file][$i];
						}
					}
					elseif($file_name == "files_to_move") {
						// Move code between two lines to another location
						for($i=0;$i<count($move_get_start[$name_file]);$i++) {
							$pos_start = strpos($file_content, $move_to_start[$name_file][$i]) + strlen($move_to_start[$name_file][$i].$move_start);
	  					$pos_end = strpos($file_content, ($move_end.$move_to_end[$name_file][$i]));
							$move_string = substr($file_content, $pos_start, $pos_end - $pos_start);
							
							$searching[] = $move_to_start[$name_file][$i].$move_start.$move_string.$move_end.$move_to_end[$name_file][$i];
							$replacement[] = $move_to_start[$name_file][$i].$move_to_end[$name_file][$i];
							$searching[] = $move_get_start[$name_file][$i].$move_get_end[$name_file][$i];
							$replacement[] = $move_get_start[$name_file][$i].$move_string.$move_get_end[$name_file][$i];
						}
					}
					write_file($file_value, str_replace($searching, $replacement, $file_content));
					unset($file_content, $searching, $replacement);
				}
			}
			
			//Execute uninstall script if exist
			if(file_exists('plugins/'.$mod_datadir.'/update_uninstall.php'))
				require('plugins/'.$mod_datadir.'/update_uninstall.php');
	
			update_config_file(0);
			// New status message
			unset($mod_plugin_status);
			$already_installed = false;
			$mod_plugin_status[] = '<span style="color: red; font-weight: bold;">'.sprintf($lang_installer_admin['Plugin removed'],$mod_config['mod_abbr']).'</span>';
		}
	}
	
	// Display the admin navigation menu
		generate_admin_menu($plugin);
	
	?>
		<div id="plugin_mod" class="plugin blockform">
			<h2><span><?php echo $mod_config['mod_name'].' ('.$mod_config['mod_abbr'].')' ?></span></h2>
			<div class="box">
				<div class="inbox">
					<p><?php echo nl2br($lang_plugin_admin['Explanation']) ?></p>
				</div>
			</div>
	
			<h2 class="block2"><span><?php echo $lang_installer_admin['Form title'] ?></span></h2>
			<div class="box">
				<form id="plugin_mod_form" method="post" action="<?php echo pun_htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
					<div class="inform">
						<fieldset>
							<legend><?php echo $lang_installer_admin['Legend text'] ?></legend>
							<div class="infldset">
							<ul>
								<li><?php echo sprintf($lang_installer_admin['Mod version'],$mod_config['version'],$mod_config['release_date'],$mod_config['author']) ?></li>
								<?php	if(!empty($mod_install_date))	echo "<li>".sprintf($lang_installer_admin['Installation date'],$mod_install_date)."</li>\n"; ?>
								<li><?php echo $lang_installer_admin['Plugin status']."<br />" ?> <?php foreach($mod_plugin_status as $value) echo $value."<br />" ?></li>
							</ul>
	<?php if($version_warning) echo "<p style='color:#00f'>".sprintf($lang_installer_admin['Version warning'],$pun_config['o_cur_version'],pun_htmlspecialchars(implode(', ', $mod_config['fluxbb_versions'])))."</p>\n"; ?>
							<div>
							<?php
							if($already_installed) {
								if(!$uninstall_fail) {
									//Database modified ?
									if(!empty($list_tables)) {
										echo "<ul style='margin:0 0 10px 20px;'>\n";
										echo "<li><strong>".$lang_installer_admin['Database mod']."</strong></li>\n";
										echo "<li>".$lang_installer_admin['Database uninstall']."</li>\n";	
										foreach($list_tables as $base_name) {
					  					foreach($$base_name as $table_value) {
					  						if($base_name == "fields_to_add") {
					  							$drop_fields = array();
					  							for($i=0;$i<count($add_field_name[$table_value]);$i++) {
					  								$drop_fields[] = $add_field_name[$table_value][$i];
					  							}
					  							echo "<li><label><input type='checkbox' name='database_drop_fields[".$table_value."]' value='true' />&nbsp;".sprintf($lang_installer_admin['Database fields'],implode(", ",$drop_fields),$table_value)."</label></li>";
					  						}
									  		elseif($base_name == "config_to_insert") {
									  			$delete_values = array();
													for($i = 0;$i < count($values[$table_value]);$i = $i + 2) {
														$delete_values[] = $values[$table_value][$i];
													}
													echo "<li><label><input type='checkbox' name='database_drop_values' value='true' />&nbsp;".sprintf($lang_installer_admin['Database values'],implode(", ",$delete_values),$table_value)."</label></li>";
									  		}
					  					}
										}
										echo "</ul>\n";
									}
									echo "<input type=\"submit\" name=\"remove\" value=\"".$lang_installer_admin['Remove']."\" />\n";
								}
							}
							else {
								if(!$install_fail) echo "<input type='submit' name='install' value='".$lang_installer_admin['Install']."' />\n";
							}
							if($no_install_file) echo "&nbsp;<input type='submit' name='readme' value=\"".$lang_installer_admin['Readme']."\" />\n";
							//Return link to Mod Installer without install parameters
							$link_search = array('&amp;dir='.$mod_datadir,'&amp;mod_install=1');
							$link_replace = '';
							$link = str_replace($link_search, $link_replace, pun_htmlspecialchars($_SERVER['REQUEST_URI']));
							?>
							&nbsp;<input type="button" name="return" value="<?php echo $lang_installer_admin['Return'] ?>" onclick="self.location='<?php echo $link ?>'"/>
							<input type="hidden" name="mod_install" value="1" /></div>
							</div>
						 </fieldset>
					</div>
				</form>
			</div>
	
		</div>
	<?php
} //End of structure for Install or Uninstall a mod or create install file
else { // Structure for Mod Installer
	// Display the admin navigation menu
	generate_admin_menu($plugin);
	//Does the plugin compatible with the version of FluxBB?
	$plugin_version_warning = !in_array($pun_config['o_cur_version'], $installer_config['fluxbb_versions']);

?>
	<div id="plugin_mod" class="plugin blockform">
		<h2><span><?php echo $installer_config['plugin_name'].' ('.$installer_config['plugin_abbr'].')' ?></span></h2>
		<div class="box">
			<div class="inbox">
				<p><?php echo $lang_installer_admin['Explanation'] ?></p>
			</div>
		</div>
		
		<h2 class="block2"><span><?php echo $lang_installer_admin['Form title'] ?></span></h2>
		<div class="box">
			<form id="plugin_mod_form" method="post" action="#">
				<div class="inform">
					<?php if($plugin_version_warning) echo "<p style='color:#00f'>".sprintf($lang_installer_admin['Plugin warning'],$pun_config['o_cur_version'],pun_htmlspecialchars(implode(', ', $installer_config['fluxbb_versions'])))."</p>\n"; ?>
					<fieldset>
						<legend><?php echo $lang_installer_admin['Plugin legend text'] ?></legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<?php
							if($nb_mod > 0) {
								echo "<tr><th style='width:60%;'>".$lang_installer_admin['Mod name']."</th><th>".$lang_installer_admin['Status']."</th><th>".$lang_installer_admin['Action']."</th></tr>";
								for($i = 0;$i < $nb_mod;$i++) {
								echo "<tr><td>".$plugin_config['name'][$i]." (".$plugin_config['abbr'][$i].")</td>";
								if($plugin_config['OK'][$i] === true) echo "<td>".($plugin_config['installed'][$i] ? $lang_installer_admin['Mod installed'] : "<em style='color:red;'>".$lang_installer_admin['Mod not installed']."</em>")."</td><td><a href='".pun_htmlspecialchars($_SERVER['REQUEST_URI'])."&amp;mod_install=1&amp;dir=".$plugin_config['folder'][$i]."'>".$lang_installer_admin['Change']."</a>";
								else echo "<td colspan='2'>MODINST ->".$plugin_config['OK'][$i];
								echo "</td></tr>\n";
								}
							}
							else echo "<tr><th colspan='3'>".$lang_installer_admin['No mod found']."</th></tr>\n";
							?>
						</table>
						 <p><?php echo sprintf('MODINST '.$lang_installer_admin['Plugin version'],$installer_config['version'],$installer_config['release_date'],$installer_config['author']) ?></p>
					 </div>
					</fieldset>
				</div>
			</form>
		</div>

	</div>
<?php

} //End of structure for Mod Installer
// Note that the script just ends here. The footer will be included by admin_loader.php
