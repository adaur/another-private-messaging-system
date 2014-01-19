<?php
/**
 * Copyright (C)2010-2014 adaur
 * Another Private Messaging System v3.0.8
 * Based on work from Vincent Garnier, Connorhd and David 'Chacmool' Djurback
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';

// No guest here !
if ($pun_user['is_guest'])
	message($lang_common['No permission']);
	
// User enable PM ?
if (!$pun_user['use_pm'] == '1')
	message($lang_common['No permission']);

// Are we allowed to use this ?
if (!$pun_config['o_pms_enabled'] =='1' || $pun_user['g_pm'] == '0')
	message($lang_common['No permission']);

// Action ?
$action = ((isset($_POST['action']) && ($_POST['action'] == 'delete_multiple')) ? $_POST['action'] : '');


if ($action != '')
{
	if ($action == 'delete_multiple')
	{
		if (isset($_POST['delete_multiple_comply']))
		{
			$idlist = explode(',', $_POST['selected_lists']);
			$idlist = array_map('intval', $idlist);
			$idlist = implode(',', array_values($idlist));

			$db->query('DELETE FROM '.$db->prefix.'sending_lists WHERE id IN('.$idlist.') AND user_id='.$pun_user['id']) or error('Unable to delete sending lists', __FILE__, __LINE__, $db->error());
		}
		else
		{
			if (empty($_POST['selected_lists']))
				message($lang_pms['Must select lists']);
			
			$idlist = array_map('trim', $_POST['selected_lists']);
			$idlist = array_map('intval', $idlist);
			$idlist = implode(',', array_values($idlist));
			
			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_pms['Private Messages'], $lang_pms['Multidelete lists'], $lang_pms['Sending lists']);
			define('PUN_ACTIVE_PAGE', 'pm');
			require PUN_ROOT.'header.php';
	?>
<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="pms_sending_lists.php"><?php echo $lang_pms['Sending lists'] ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_pms['Multidelete lists'] ?></strong></li>
		</ul>
		<div class="pagepost"></div>
		<div class="clearer"></div>
	</div>
</div>
<div class="block2col">
	<div class="blockmenu">
		<h2><span><?php echo $lang_pms['PM Menu'] ?></span></h2>
		<div class="box">
			<div class="inbox">
				<ul>
					<li><a href="pms_inbox.php"><?php echo $lang_pms['Inbox'] ?></a></li>
					<li><a href="pms_send.php"><?php echo $lang_pms['Write message'] ?></a></li>
					<li class="isactive"><a href="pms_sending_lists.php"><?php echo $lang_pms['Sending lists'] ?></a></li>
					<li><a href="pms_contacts.php"><?php echo $lang_pms['Contacts'] ?></a></li>
				</ul>
			</div>
		</div>
	</div>
	<br />
	<div class="blockform">
		<div class="box">
			<form method="post" action="">
				<input type="hidden" name="action" value="delete_multiple" />
				<input type="hidden" name="selected_lists" value="<?php echo $idlist ?>" />
				<input type="hidden" name="delete_multiple_comply" value="1" />
				<div class="inform">
				<div class="forminfo">
					<p><?php echo $lang_pms['Delete lists comply'] ?></p>
				</div>
			</div>
			<p class="buttons"><input type="submit" name="delete" value="<?php echo $lang_pms['Delete'] ?>" /> <a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>
</div>

	<?php
			require PUN_ROOT.'footer.php';
		}
	}
}

// Add a list
if (isset($_POST['form_sent']))
{
	// Make sure they got here from the site
	confirm_referrer('pms_sending_lists.php');
	
	// Build list
	$list_name = pun_trim($_POST['list_name']);
	$p_destinataire = isset($_POST['req_username']) ? pun_trim($_POST['req_username']) : '';
    $dest_list = explode(', ', $p_destinataire);
	
	$dest_list = array_map('pun_trim', $dest_list);
	$dest_list = array_unique($dest_list);
	
	if (in_array($pun_user['username'], $dest_list))
		message('yourself');
	
	foreach ($dest_list as $k=>$v)
	{
		if ($v == '') unset($dest_list[$k]);
	}

    if (count($dest_list) > $pun_config['o_pms_max_receiver'])
		$errors[] = sprintf($lang_pms['Too many receiver'], $pun_config['o_pms_max_receiver']-1);

	$destinataires = array();
	$i = 0;
	$list_ids = array();
	$list_usernames = array();
	foreach ($dest_list as $destinataire)
	{
		// Get receiver infos
		$result_username = $db->query('SELECT u.id, u.username, u.email, u.notify_pm, u.notify_pm_full, u.use_pm, u.num_pms, g.g_id, g.g_pm_limit FROM '.$db->prefix.'users AS u INNER JOIN '.$db->prefix.'groups AS g ON u.group_id=g.g_id LEFT JOIN '.$db->prefix.'messages AS pm ON pm.owner=u.id WHERE u.id!=1 AND u.username=\''.$db->escape($destinataire).'\' GROUP BY u.username') or error('Unable to get user ID', __FILE__, __LINE__, $db->error());
		// List users infos
		if ($destinataires[$i] = $db->fetch_assoc($result_username))
		{
			// Begin to build the IDs' list - Thanks to Yacodo!
			$list_ids[] = $destinataires[$i]['id'];
			// Begin to build usernames' list
			$list_usernames[] = $destinataires[$i]['username'];
			// Receivers enable PM ?
			if (!$destinataires[$i]['use_pm'] == '1')
				$errors[] = sprintf($lang_pms['User disable PM'], pun_htmlspecialchars($destinataire));			
		}
		else
			$errors[] = sprintf($lang_pms['No user'], pun_htmlspecialchars($destinataire));
		$i++;
	}
	
	$ids_serialized = serialize($list_ids);
	$usernames_serialized = serialize($list_usernames);
	
	$db->query('INSERT INTO '.$db->prefix.'sending_lists (user_id, name, receivers, array_id) VALUES ('.$pun_user['id'].', \''.$db->escape($list_name).'\', \''.$db->escape($usernames_serialized).'\', \''.$db->escape($ids_serialized).'\')') or error('Unable to add the list', __FILE__, __LINE__, $db->error());
}

// Delete a list
if (isset($_GET['delete']))
{
	$id = intval($_GET['delete']);
	
	$result = $db->query('SELECT user_id FROM '.$db->prefix.'sending_lists WHERE id='.$id) or error('Unable to find the list', __FILE__, __LINE__, $db->error());
	
	if ($db->result($result) != $pun_user['id'])
		message($lang_common['Bad request']);

	$result = $db->query('DELETE FROM '.$db->prefix.'sending_lists WHERE id= '.$id) or error('Unable to delete the list', __FILE__, __LINE__, $db->error());
	
	redirect('pms_sending_lists.php', $lang_pms['Deleted list redirect']);
}

// Build page
$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_pms['Private Messages'], $lang_pms['Sending lists']);

define('PUN_ACTIVE_PAGE', 'pm');
require PUN_ROOT.'header.php';
?>
<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="pms_inbox.php"><?php echo $lang_pms['Private Messages'] ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_pms['Contacts'] ?></strong></li>
		</ul>
		<div class="pagepost"></div>
		<div class="clearer"></div>
	</div>
</div>
<div class="block2col">
	<div class="blockmenu">
		<h2><span><?php echo $lang_pms['PM Menu'] ?></span></h2>
		<div class="box">
			<div class="inbox">
				<ul>
					<li><a href="pms_inbox.php"><?php echo $lang_pms['Inbox'] ?></a></li>
					<li><a href="pms_send.php"><?php echo $lang_pms['Write message'] ?></a></li>
					<li class="isactive"><a href="pms_sending_lists.php"><?php echo $lang_pms['Sending lists'] ?></a></li>
					<li><a href="pms_contacts.php"><?php echo $lang_pms['Contacts'] ?></a></li>
				</ul>
			</div>
		</div>
	</div>
<script type="text/javascript">
/* <![CDATA[ */
function checkAll(checkWhat,command){
    var inputs = document.getElementsByTagName('input');
   
    for(index = 0; index < inputs.length; index++){
        if(inputs[index].name == checkWhat){
            inputs[index].checked=document.getElementById(command).checked;
        }
    }
}
/* ]]> */
</script>
<br />
<div class="blockform">
	<div class="box">
		<form action="" method="post">
		<div class="inform">
			<fieldset>
				<legend><?php echo $lang_pms['Add a list'] ?></legend>
				<div class="infldset">
					<label class="conl"><?php echo $lang_pms['List name'] ?><br />
					<input type="text" name="list_name" size="25" maxlength="255" tabindex="1" /></label><br />
					<label class="con"><?php echo $lang_pms['List usernames comma'] ?><br />
					<textarea name="req_username" rows="1" cols="50" tabindex="1"></textarea><br />
					</label>
				</div>
			</fieldset>
		</div>
		<p class="buttons"><input type="hidden" name="form_sent" value="1" /><input type="submit" name="add" value="<?php echo $lang_pms['Add'] ?>" accesskey="s" /></p>
		</form>
	</div>
</div>
<br />
<form method="post" action="">
<div class="blocktable">
	<div class="box">
		<div class="inbox">
			<table cellspacing="0">
			<thead>
				<tr>
					<th><?php echo $lang_pms['List name'] ?></th>
					<th><?php echo $lang_pms['List usernames'] ?></th>
					<th><?php echo $lang_pms['Delete'] ?></th>
					<th><?php echo $lang_pms['Quick message'] ?></th>
					<th class="tcmod"><label style="display: inline; white-space: nowrap;"><?php echo $lang_pms['Select'] ?>&nbsp;<input type="checkbox" id="checkAllButon" value="1" onclick="javascript:checkAll('selected_lists[]','checkAllButon');" /></label></th>
				</tr>
			</thead>
			<tbody>
<?php
// Fetch lists
$result = $db->query('SELECT * FROM '.$db->prefix.'sending_lists WHERE user_id='.$pun_user['id'].' ORDER BY id DESC') or error('Unable to update the list of the lists', __FILE__, __LINE__, $db->error());

if ($db->num_rows($result))
{
	while ($cur_list = $db->fetch_assoc($result))
	{
		$usernames = '';
		$ids_list = unserialize($cur_list['array_id']);
		$usernames_list = unserialize($cur_list['receivers']);
		for($i = 0; $i < count($ids_list); $i++){
			if ($i > 0 && $i < count($ids_list))
					$usernames = $usernames.', ';
			$usernames = $usernames.'<a href="profile.php?id='.$ids_list[$i].'">'.pun_htmlspecialchars($usernames_list[$i]).'</a>';
		} 
?>
	<tr>
		<td><?php echo pun_htmlspecialchars($cur_list['name']) ?></td>
		<td><?php echo $usernames ?></td>
		<td><a href="pms_sending_lists.php?delete=<?php echo $cur_list['id'] ?>" title="<?php $usernames ?>" onclick="return window.confirm('<?php echo $lang_pms['Delete list confirm'] ?>')"><?php echo $lang_pms['Delete this list'] ?></a></td>
		<td><a href="pms_send.php?lid=<?php echo $cur_list['id'] ?>" title="<?php echo $lang_pms['Quick message'] ?>"><?php echo $lang_pms['Quick message'] ?></a></td>
		<td class="tcmod"><input type="checkbox" name="selected_lists[]" value="<?php echo $cur_list['id'] ?>" /></td>
	</tr>
<?php
	}
}
else
	echo "\t".'<tr><td class="puncon1" colspan="5">'.$lang_pms['No sending lists'].'</td></tr>'."\n";
?>
			</tbody>
			</table>
		</div>
	</div>
</div>

<div class="linksb">
	<div class="contacts crumbsplus">
		<div class="pagepost">
		<div style="text-align: right;">
				<p class="conr" style="width:auto">
				<label style="display:inline">
				<?php echo $lang_pms['For select'] ?> 
				<select name="action">
					<option value="delete_multiple"><?php echo $lang_pms['Delete'] ?></option>
				</select>
				</label> 
				<input type="submit" value="<?php echo $lang_pms['OK'] ?>" />
				</p>
			</div>
		</div>
	</div>
	<div class="clearer"></div>
</div>
</form>
</div>

<?php
	require PUN_ROOT.'footer.php';
?>