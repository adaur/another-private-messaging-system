<?php
/**
 * Copyright (C)2010 adaur
 * Another Private Messaging System v3.0.9
 * Based on work from Vincent Garnier, Connorhd and David 'Chacmool' Djurback
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
    exit;

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);
define('PLUGIN_VERSION', '3.0.9');
define('PLUGIN_URL', 'admin_loader.php?plugin=AP_Private_messaging.php');

// Load language file
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/pms_plugin.php'))
       require PUN_ROOT.'lang/'.$pun_user['language'].'/pms_plugin.php';
else
       require PUN_ROOT.'lang/English/pms_plugin.php';

if (isset($_POST['form_sent']))
{
	$form = array_map('trim', $_POST['form']);
	$allow = array_map('trim', $_POST['allow']);
	$limit = array_map('trim', $_POST['limit']);

	while (list($key, $input) = @each($form))
	{
		// Only update values that have changed
		if ((isset($pun_config['o_'.$key])) || ($pun_config['o_'.$key] == NULL))
		{
			if ($pun_config['o_'.$key] != $input)
			{
				if ($key == 'pms_max_receiver')
					$input = $input+1;
				
				if ($input != '' || is_int($input))
					$value = '\''.$db->escape($input).'\'';
				else
					$value = 'NULL';
	
				$db->query('UPDATE '.$db->prefix.'config SET conf_value='.$value.' WHERE conf_name=\'o_'.$key.'\'') or error('Unable to update the configuration', __FILE__, __LINE__, $db->error());
			}
		}
	}

	while (list($id, $set) = @each($allow))
		$db->query('UPDATE '.$db->prefix.'groups SET g_pm='.intval($set).' WHERE g_id=\''.intval($id).'\'') or error('Unable to change the permissions', __FILE__, __LINE__, $db->error());
	
	while (list($id, $set) = @each($limit))
		$db->query('UPDATE '.$db->prefix.'groups SET g_pm_limit='.intval($set).' WHERE g_id=\''.intval($id).'\'') or error('Unable to change the permissions', __FILE__, __LINE__, $db->error());
	
	// Regenerate the config cache
	require_once PUN_ROOT.'include/cache.php';
	generate_config_cache();

	redirect(PLUGIN_URL, $lang_plugin_pms['Redirection']);
}
else
{
	// Display the admin navigation menu
	generate_admin_menu($plugin);
	
	$pms_max_receiver = $pun_config['o_pms_max_receiver'] - 1;
?>
	<div id="exampleplugin" class="plugin blockform">
		<h2><span><?php echo $lang_plugin_pms['Private Messages'] ?> v<?php echo PLUGIN_VERSION ?></span></h2>
		<div class="box">
			<div class="inbox">
				<p><?php echo $lang_plugin_pms['Description'] ?></p>
			</div>
		</div>
	</div>
	<div class="blockform">
		<h2 class="block2"><span><?php echo $lang_plugin_pms['Settings'] ?></span></h2>
		<div class="box">
			<form method="post" action="<?php echo PLUGIN_URL; ?>">
				<div class="inform">
					<input type="hidden" name="form_sent" value="1" />
					<fieldset>
						<legend><?php echo $lang_plugin_pms['Options'] ?></legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<th scope="row"><?php echo $lang_plugin_pms['Activation'] ?></th>
								<td>
									<input type="radio" name="form[pms_enabled]" value="1"<?php if ($pun_config['o_pms_enabled'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong><?php echo $lang_plugin_pms['Yes'] ?></strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[pms_enabled]" value="0"<?php if ($pun_config['o_pms_enabled'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong><?php echo $lang_plugin_pms['No'] ?></strong>
									<span><?php echo $lang_plugin_pms['Enable or not'] ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php echo $lang_plugin_pms['Notification'] ?></th>
								<td>
									<input type="radio" name="form[pms_notification]" value="1"<?php if ($pun_config['o_pms_notification'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong><?php echo $lang_plugin_pms['Yes'] ?></strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[pms_notification]" value="0"<?php if ($pun_config['o_pms_notification'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong><?php echo $lang_plugin_pms['No'] ?></strong>
									<span><?php echo $lang_plugin_pms['Notify or not'] ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php echo $lang_plugin_pms['Number receivers'] ?></th>
								<td>
									<input type="text" name="form[pms_max_receiver]" size="5" maxlength="5" value="<?php echo $pms_max_receiver ?>" />
									<span><?php echo $lang_plugin_pms['Number max receivers'] ?></span>
								</td>
							</tr>
						</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_plugin_pms['Permissions'] ?></legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
						<p><?php echo $lang_plugin_pms['Permissions information'] ?></p>
							<?php
							$result = $db->query('SELECT g_id, g_title, g_pm, g_pm_limit FROM '.$db->prefix.'groups WHERE g_id !=1 AND g_id !=3 ORDER BY g_id') or error('Unable to find usergroup list', __FILE__, __LINE__, $db->error());
							while ($cur_group = $db->fetch_assoc($result)) :
								if ($pun_user['is_admmod']) :
							?>
							<tr> 
								<th scope="row"><?php echo pun_htmlspecialchars($cur_group['g_title']) ?></th>
								<td>
									<span><?php echo $lang_plugin_pms['Allow group'] ?></span>
									<input type="radio" name="allow[<?php echo $cur_group['g_id'] ?>]" value="1"<?php if ($cur_group['g_pm'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong><?php echo $lang_plugin_pms['Yes'] ?></strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="allow[<?php echo $cur_group['g_id'] ?>]" value="0"<?php if ($cur_group['g_pm'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong><?php echo $lang_plugin_pms['No'] ?></strong>
								</td>
							</tr>
							<tr>
								<th scope="row">&nbsp;</th>
								<td>
									<span><input type="text" name="limit[<?php echo $cur_group['g_id'] ?>]" size="5" maxlength="10" value="<?php echo $cur_group['g_pm_limit'] ?>" /> <?php echo $lang_plugin_pms['Information limit 1'] ?> <em><?php echo pun_htmlspecialchars($cur_group['g_title']) ?></em> <?php echo $lang_plugin_pms['Information limit 2'] ?> 
								</span>
									</td>
							</tr>
							<?php
								endif;
							endwhile;
							?>
							
						</table>
						</div>
					</fieldset>
				</div>
			<p class="submitend"><input type="submit" name="save" value="<?php echo $lang_plugin_pms['Save'] ?>" /></p>
			</form>
		</div>
	</div>

<?php
}
?>