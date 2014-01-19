<?php
/***********************************************************************/

// This is the first installation of Another Private Messaging/Topic System

// Some info about your mod.
$mod_title      = 'Another Private Messaging/Topic System';
$mod_version    = '3.0.8';
$release_date   = '2014-01-19';
$author         = 'adaur';
$author_email   = 'adaur.underground@gmail.com';

// Versions of FluxBB this mod was created for. A warning will be displayed, if versions do not match
$fluxbb_versions= array('1.5.4', '1.5.5', '1.5.6');

// Set this to false if you haven't implemented the restore function (see below)
$mod_restore	= true;

// Circumvent maintenance mode
define('PUN_TURN_OFF_MAINT', 1);
define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

// We want the complete error message if the script fails
if (!defined('PUN_DEBUG'))
	define('PUN_DEBUG', 1);

// This following function will be called when the user presses the "Install" button
function install()
{
	global $db, $db_type, $pun_config;

	$schema_messages = array(
			'FIELDS'			=> array(
					'id'				=> array(
							'datatype'			=> 'SERIAL',
							'allow_null'    	=> false
					),
					'shared_id'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					),
					'last_shared_id'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					),
					'last_post'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> true,
							'default'		=> '0'
					),
					'last_post_id'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> true,
							'default'		=> '0'
					),
					'last_poster'	=> array(
							'datatype'			=> 'VARCHAR(255)',
							'allow_null'		=> false,
							'default'		=> '0'
					),
					'owner'		=> array(
							'datatype'			=> 'INTEGER',
							'allow_null'		=> false,
							'default'		=> '0'
					),
					'subject'	=> array(
							'datatype'			=> 'VARCHAR(255)',
							'allow_null'		=> false
					),
					'message'	=> array(
							'datatype'			=> 'MEDIUMTEXT',
							'allow_null'		=> false
					),
					'hide_smilies'	=> array(
							'datatype'		=> 'TINYINT(1)',
							'allow_null'	=> false,
							'default'		=> '0'
					),
					'show_message'	=> array(
							'datatype'		=> 'TINYINT(1)',
							'allow_null'	=> false,
							'default'		=> '0'
					),
					'sender'	=> array(
							'datatype'			=> 'VARCHAR(200)',
							'allow_null'		=> false
					),
					'receiver'	=> array(
							'datatype'			=> 'VARCHAR(200)',
							'allow_null'		=> true
					),
					'sender_id'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'		=> '0'
					),
					'receiver_id'	=> array(
							'datatype'			=> 'VARCHAR(255)',
							'allow_null'		=> true,
							'default'		=> '0'
					),
					'sender_ip'	=> array(
							'datatype'			=> 'VARCHAR(39)',
							'allow_null'		=> true
					),
					'posted'	=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
					),
					'showed'	=> array(
							'datatype'			=> 'TINYINT(1)',
							'allow_null'		=> false,
							'default'		=> '0'
					)
			),
			'PRIMARY KEY'		=> array('id'),
	);
	
	$schema_contacts = array(
			'FIELDS'			=> array(
					'id'				=> array(
							'datatype'			=> 'SERIAL',
							'allow_null'    	=> false
					),
					'user_id'			=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'			=> '0'
					),
					'contact_id'		=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'			=> '0'
					),
					'contact_name'		=> array(
							'datatype'			=> 'VARCHAR(255)',
							'allow_null'		=> false,
					),
					'allow_msg'			=> array(
							'datatype'			=> 'TINYINT(1)',
							'allow_null'		=> false,
							'default'		=> '1'
					)
			),
			'PRIMARY KEY'		=> array('id'),
	);
	
	$schema_sending_lists = array(
			'FIELDS'			=> array(
					'id'				=> array(
							'datatype'			=> 'SERIAL',
							'allow_null'    	=> false
					),
					'user_id'			=> array(
							'datatype'			=> 'INT(10)',
							'allow_null'		=> false,
							'default'			=> '0'
					),
					'array_id'			=> array(
							'datatype'			=> 'VARCHAR(255)',
							'allow_null'		=> false,
					),
					'name'				=> array(
							'datatype'			=> 'VARCHAR(255)',
							'allow_null'		=> false,
					),
					'receivers	'		=> array(
							'datatype'			=> 'VARCHAR(255)',
							'allow_null'		=> false,
					),
			),
			'PRIMARY KEY'		=> array('id'),
	);
	
	$check_installation = $db->query('SELECT 1 FROM '.$db->prefix.'messages');
	if (!$db->num_rows($check_installation)) // There is nothing in the table "messages": perform a full installation
	{
		$db->create_table('messages', $schema_messages) or error('Unable to create table "messages"', __FILE__, __LINE__, $db->error());
		
		$db->create_table('contacts', $schema_contacts) or error('Unable to create table "contacts"', __FILE__, __LINE__, $db->error());
		
		$config = array('o_pms_enabled' => '1', 'o_pms_mess_per_page' => '10', 'o_pms_max_receiver' => '5', 'o_pms_notification' => '1');
		foreach ($config as $conf_name => $conf_value)
		{
			$db->query('INSERT INTO '.$db->prefix."config (conf_name, conf_value) VALUES('$conf_name', $conf_value)")
				or error('Unable to insert into table '.$db->prefix.'config. Please check your configuration and try again', __FILE__, __LINE__, $db->error());
		}
		
		$db->add_field('groups', 'g_pm', 'TINYINT(1)', false, '1', 'g_email_flood') or error('Unable to add column "g_pm" to table "groups"', __FILE__, __LINE__, $db->error());
		
		$db->add_field('groups', 'g_pm_limit', 'INT', false, '20', 'g_pm') or error('Unable to add column "g_pm_limit" to table "groups"', __FILE__, __LINE__, $db->error());
		
		$db->add_field('users', 'use_pm', 'TINYINT(1)', false, '1', 'activate_key') or error('Unable to add column "use_pm" to table "users"', __FILE__, __LINE__, $db->error());
		
		$db->add_field('users', 'notify_pm', 'TINYINT(1)', false, '1', 'use_pm') or error('Unable to add column "notify_pm" to table "users"', __FILE__, __LINE__, $db->error());
		
		$db->add_field('users', 'notify_pm_full', 'TINYINT(1)', false, '0', 'notify_with_post') or error('Unable to add column "num_pms" to table "users"', __FILE__, __LINE__, $db->error());
		
		$db->add_field('users', 'num_pms', 'INT(10) UNSIGNED', false, '0', 'num_posts') or error('Unable to add column "num_pms" to table "users"', __FILE__, __LINE__, $db->error());
	}
	else
	{
		$check_notify = $db->query('SHOW COLUMNS FROM '.$db->prefix.'users LIKE \'notify_pm_full\'');
		if (!$db->num_rows($check_notify))
			$db->add_field('users', 'notify_pm_full', 'TINYINT(1)', false, '0', 'notify_with_post') or error('Unable to add column "num_pms" to table "users"', __FILE__, __LINE__, $db->error());
	}
	
	$check_sending_lists = $db->query('SHOW TABLES LIKE "'.$db->prefix.'sending_lists"');
	
	if (!$db->num_rows($check_sending_lists)) // If the sending_lists table doesn't exist, create it
		$db->create_table('sending_lists', $schema_sending_lists) or error('Unable to create table "sending_lists"', __FILE__, __LINE__, $db->error());
	
	// Regenerate the config cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
        require PUN_ROOT.'include/cache.php';

    generate_config_cache();
}

// This following function will be called when the user presses the "Restore" button (only if $mod_restore is true (see above))
function restore()
{
	global $db, $db_type, $pun_config;

	$db->drop_table('messages') or error('Unable to drop table "messages"', __FILE__, __LINE__, $db->error());
	
	$db->drop_table('contacts') or error('Unable to drop table "contacts"', __FILE__, __LINE__, $db->error());
	
	$db->drop_table('sending_lists') or error('Unable to drop table "sending_lists"', __FILE__, __LINE__, $db->error());
	
	$db->drop_field('groups', 'g_pm') or error('Unable to drop column "g_pm" from table "groups"', __FILE__, __LINE__, $db->error());
	
	$db->drop_field('groups', 'g_pm_limit') or error('Unable to drop column "g_pm_limit" from table "groups"', __FILE__, __LINE__, $db->error());
	
	$db->drop_field('users', 'use_pm') or error('Unable to drop column "use_pm" from table "users"', __FILE__, __LINE__, $db->error());
	
	$db->drop_field('users', 'notify_pm') or error('Unable to drop column "notify_pm" from table "users"', __FILE__, __LINE__, $db->error());
	
	$db->drop_field('users', 'num_pms') or error('Unable to drop column "num_pms" from table "users"', __FILE__, __LINE__, $db->error());
	
	$db->drop_field('users', 'notify_pm_full') or error('Unable to drop column "num_pms" from table "users"', __FILE__, __LINE__, $db->error());
	
	$db->query('DELETE FROM '.$db->prefix.'config WHERE conf_name=\'o_pms_enabled\'') or error('Unable to delete "o_pms_enabled" from "config"', __FILE__, __LINE__, $db->error());
        
    $db->query('DELETE FROM '.$db->prefix.'config WHERE conf_name=\'o_pms_mess_per_page\'') or error('Unable to delete "o_pms_mess_per_page" from "config"', __FILE__, __LINE__, $db->error());
        
    $db->query('DELETE FROM '.$db->prefix.'config WHERE conf_name=\'o_pms_max_receiver\'') or error('Unable to delete "o_pms_max_receiver" from "config"', __FILE__, __LINE__, $db->error());
        
    $db->query('DELETE FROM '.$db->prefix.'config WHERE conf_name=\'o_pms_notification\'') or error('Unable to delete "o_pms_notification" from "config"', __FILE__, __LINE__, $db->error());
	
	// Regenerate the config cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
        require PUN_ROOT.'include/cache.php';

    generate_config_cache();
}

// Make sure we are running a FluxBB version that this mod works with
$version_warning = !in_array($pun_config['o_cur_version'], $fluxbb_versions);

$style = (isset($pun_user)) ? $pun_user['style'] : $pun_config['o_default_style'];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo pun_htmlspecialchars($mod_title) ?> installation</title>
<link rel="stylesheet" type="text/css" href="style/<?php echo $style.'.css' ?>" />
</head>
<body>

<div id="punwrap">
<div id="puninstall" class="pun" style="margin: 10% 20% auto 20%">

<?php

if (isset($_POST['form_sent']))
{
	if (isset($_POST['install']))
	{
		// Run the install function (defined above)
		install();

?>
<div class="block">
	<h2><span>Installation successful</span></h2>
	<div class="box">
		<div class="inbox">
			<p>Your database has been successfully prepared for <?php echo pun_htmlspecialchars($mod_title) ?>. See readme.txt for further instructions.</p>
		</div>
	</div>
</div>
<?php

	}
	else
	{
		// Run the restore function (defined above)
		restore();

?>
<div class="block">
	<h2><span>Restore successful</span></h2>
	<div class="box">
		<div class="inbox">
			<p>Your database has been successfully restored.</p>
		</div>
	</div>
</div>
<?php

	}
}
else
{

?>
<div class="blockform">
	<h2><span>Mod installation</span></h2>
	<div class="box">
		<form method="post" action="<?php echo pun_htmlspecialchars($_SERVER['PHP_SELF']) ?>">
			<div><input type="hidden" name="form_sent" value="1" /></div>
			<div class="inform">
				<p>This script will update your database to work with the following modification:</p>
				<p><strong>Mod title:</strong> <?php echo pun_htmlspecialchars($mod_title.' '.$mod_version) ?></p>
				<p><strong>Author:</strong> <?php echo pun_htmlspecialchars($author) ?> (<a href="mailto:<?php echo pun_htmlspecialchars($author_email) ?>"><?php echo pun_htmlspecialchars($author_email) ?></a>)</p>
				<p><strong>Disclaimer:</strong> Mods are not officially supported by FluxBB. Mods generally can't be uninstalled without running SQL queries manually against the database. Make backups of all data you deem necessary before installing.</p>
<?php if ($mod_restore): ?>
				<p>If you've previously installed this mod and would like to uninstall it, you can click the Restore button below to restore the database.</p>
<?php endif; ?>
<?php if ($version_warning): ?>
				<p style="color: #a00"><strong>Warning:</strong> The mod you are about to install was not made specifically to support your current version of FluxBB (<?php echo $pun_config['o_cur_version']; ?>). This mod supports FluxBB versions: <?php echo pun_htmlspecialchars(implode(', ', $fluxbb_versions)); ?>. If you are uncertain about installing the mod due to this potential version conflict, contact the mod author.</p>
<?php endif; ?>
			</div>
			<p class="buttons"><input type="submit" name="install" value="Install" /><?php if ($mod_restore): ?><input type="submit" name="restore" value="Restore" /><?php endif; ?></p>
		</form>
	</div>
</div>
<?php

}

?>

</div>
</div>

</body>
</html>