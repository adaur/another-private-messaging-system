<?php

/* ***** Another Private Messaging - Topic System by adaur 3.0.8 - APMS ***** */

/* Files to be modified by inserting strings into */
$files_to_insert = array('header.php', 'profile.php');
$files_to_add = array('include/common.php', 'header.php', 'viewtopic.php', 'profile.php');
$files_to_replace = array('include/functions.php');

/* String to be searched and string to be inserted after */
/* ****** Each insert value must be terminated by \n ****** */

$search_add_file['include/common'] = array(
  "maintenance_message();",
);
$insert_add_file['include/common'] = array(
 "\n\nrequire PUN_ROOT.'plugins/apms/common_add1.php';\n",
);

$search_add_file['header'] = array(
  "\$links[] = '<li id=\"navprofile\"'.((PUN_ACTIVE_PAGE == 'profile') ? ' class=\"isactive\"' : '').'><a href=\"profile.php?id='.\$pun_user['id'].'\">'.\$lang_common['Profile'].'</a></li>';",
);
$insert_add_file['header'] = array(
 "\n\n\trequire PUN_ROOT.'plugins/apms/header_add2.php';\n",
);

$search_add_file['profile'] = array(
  "'auto_notify'			=> isset(\$_POST['form']['auto_notify']) ? '1' : '0',",
  "u.notify_with_post, ",
  "u.last_visit, ",
  "\t\t\t\$email_field = '<label class=\"required\"><strong>'.\$lang_common['Email'].' <span>'.\$lang_common['Required'].'</span></strong><br /><input type=\"text\" name=\"req_email\" value=\"'.pun_htmlspecialchars(\$user['email']).'\" size=\"40\" maxlength=\"80\" /><br /></label><p><span class=\"email\"><a href=\"misc.php?email='.\$id.'\">'.\$lang_common['Send email'].'</a></span></p>'.\"\\n\";",
  "\$db->query('UPDATE '.\$db->prefix.'online SET ident=\''.\$db->escape(\$form['username']).'\' WHERE ident=\''.\$db->escape(\$old_username).'\'') or error('Unable to update online list', __FILE__, __LINE__, \$db->error());",
);
$insert_add_file['profile'] = array(
 "\n\t\t\t\t'use_pm'\t\t\t\t=> isset(\$_POST['form']['use_pm']) ? '1' : '0',\n\n\t\t\t\t'notify_pm'	\t\t\t=> isset(\$_POST['form']['notify_pm']) ? '1' : '0',\n\n\t\t\t\t'notify_pm_full'	\t\t\t=> isset(\$_POST['form']['notify_pm_full']) ? '1' : '0',",
 "u.notify_pm, u.notify_pm_full, u.use_pm, ",
 "g.g_pm, ",
 "\n\n\t\t\trequire PUN_ROOT.'plugins/apms/profile_add4.php';\n",
 "\n\t\trequire PUN_ROOT.'plugins/apms/profile_add6.php';\n",
);

$search_add_file['viewtopic'] = array(
  "u.email_setting, ",
  "g.g_user_title, ",
  "\t\t\t\t\$user_contacts[] = '<span class=\"email\"><a href=\"misc.php?email='.\$cur_post['poster_id'].'\">'.\$lang_common['Email'].'</a></span>';",
);
$insert_add_file['viewtopic'] = array(
 "u.use_pm, ",
 "g.g_pm, ",
  "\n\n\t\t\trequire PUN_ROOT.'plugins/apms/viewtopic_add1.php';\n",
);

/* String to be searched and string to be inserted before */
/* ****** Each insert value must be terminated by \n ****** */

$search_file['header'] = array(
  "\tif (\$pun_user['g_read_board'] == '1' && \$pun_user['g_search'] == '1')",
  "if (!empty(\$page_head))",
);
$insert_file['header'] = array(
 "\trequire PUN_ROOT.'plugins/apms/header_add1.php';\n\n",
 "require PUN_ROOT.'plugins/apms/header_add3.php';\n\n",
);

$search_file['profile'] = array(
  "\t\t// Delete user avatar",
  "\t\$user_messaging = array();",
  "<p class=\"buttons\"><input type=\"submit\" name=\"update\" value=\"<?php echo \$lang_common['Submit'] ?>\" /> <?php echo \$lang_profile['Instructions'] ?></p>\n\t\t\t</form>\n\t\t</div>\n\t</div>\n<?php\n\n\t}\n\telse if (\$section == 'admin')",
);
$insert_file['profile'] = array(
 "\t\trequire PUN_ROOT.'plugins/apms/profile_add1.php';\n\n",
 "\trequire PUN_ROOT.'plugins/apms/profile_add3.php';\n\n",
 "<?php require PUN_ROOT.'plugins/apms/profile_add5.php'; ?>\n\n",
);

/* String to be searched and string to be replaced in files */

$search_replace_file['include/functions'] = array(
	"global \$db, \$lang_common, \$pun_config, \$pun_start, \$tpl_main, \$pun_user;",
);
$insert_replace_file['include/functions'] = array(
	"global \$db, \$lang_common, \$lang_pms, \$pun_config, \$pun_start, \$tpl_main, \$pun_user;",
);
