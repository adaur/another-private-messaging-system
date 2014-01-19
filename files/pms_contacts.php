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
$action = ((isset($_POST['action']) && ($_POST['action'] == 'send' || $_POST['action'] == 'authorize' || $_POST['action'] == 'refuse' || $_POST['action'] == 'delete_multiple')) ? $_POST['action'] : '');


if ($action != '')
{
	// Make sure they got here from the site
	confirm_referrer('pms_contacts.php');
	
	// send a message
	if ($action == 'send')
	{
		if (empty($_POST['selected_contacts']))
			message($lang_pms['Must select contacts']);
			
		$idlist = array_map('trim', $_POST['selected_contacts']);
		$idlist = array_map('intval', $idlist);
		$idlist = implode(',', array_values($idlist));
			
		// Fetch contacts
$result = $db->query('SELECT contact_id FROM '.$db->prefix.'contacts WHERE id IN('.$idlist.') AND user_id='.$pun_user['id']) or error('Unable to update to find the list of the contacts', __FILE__, __LINE__, $db->error());
		
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);
			
		$idlist = array();
		while ($cur_contact = $db->fetch_assoc($result))
			$idlist[] = $cur_contact['contact_id'];
			
		header('Location: pms_send.php?uid='.implode('-', $idlist));
	}
	// authorize multiple contacts
	elseif ($action == 'authorize')
	{
		if (empty($_POST['selected_contacts']))
			message($lang_pms['Must select contacts']);
		
		$idlist = array_map('trim', $_POST['selected_contacts']);
		$idlist = array_map('intval', $idlist);
		$idlist = implode(',', array_values($idlist));
		
		$db->query('UPDATE '.$db->prefix.'contacts SET allow_msg=1 WHERE id IN('.$idlist.') AND user_id='.$pun_user['id']) or error('Unable to update the status of the contacts', __FILE__, __LINE__, $db->error());
		
		redirect('pms_contacts.php', $lang_pms['Multiples status redirect']);
	}
	// refuse multiple contacts
	elseif ($action == 'refuse')
	{
		if (empty($_POST['selected_contacts']))
			message($lang_pms['Must select contacts']);
			
		$idlist = array_map('trim', $_POST['selected_contacts']);
		$idlist = array_map('intval', $idlist);
		$idlist = implode(',', array_values($idlist));
		
		$db->query('UPDATE '.$db->prefix.'contacts SET allow_msg=0 WHERE id IN('.$idlist.') AND user_id='.$pun_user['id']) or error('Unable to update the status of the contacts', __FILE__, __LINE__, $db->error());
		
		redirect('pms_contacts.php', $lang_pms['Multiples status redirect']);
	}
	elseif ($action == 'delete_multiple')
	{
		if (isset($_POST['delete_multiple_comply']))
		{
			$idlist = explode(',', $_POST['contacts']);
			$idlist = array_map('intval', $idlist);
			$idlist = implode(',', array_values($idlist));

			$db->query('DELETE FROM '.$db->prefix.'contacts WHERE id IN('.$idlist.') AND user_id='.$pun_user['id']) or error('Impossible de supprimer les contacts.', __FILE__, __LINE__, $db->error());

			switch ($db_type)
			{
				case 'mysql':
				case 'mysqli':
					$db->query('OPTIMIZE TABLE '.$db->prefix.'contacts') or error('Unable to optimize the database', __FILE__, __LINE__, $db->error());
					break;

				case 'pgsql':
				case 'sqlite':
					$db->query('VACUUM '.$db->prefix.'contacts') or error('Unable to optimize the database', __FILE__, __LINE__, $db->error());
					break;

			}
		}
		else
		{
			if (empty($_POST['selected_contacts']))
				message($lang_pms['Must select contacts']);
			
			$idlist = array_map('trim', $_POST['selected_contacts']);
			$idlist = array_map('intval', $idlist);
			$idlist = implode(',', array_values($idlist));
			
			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_pms['Private Messages'], $lang_pms['Multidelete contacts'], $lang_pms['Contacts']);
			define('PUN_ACTIVE_PAGE', 'pm');
			require PUN_ROOT.'header.php';
	?>
<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="pms_contacts.php"><?php echo $lang_pms['Contacts'] ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_pms['Multidelete contacts'] ?></strong></li>
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
					<li><a href="pms_sending_lists.php"><?php echo $lang_pms['Sending lists'] ?></a></li>
					<li class="isactive"><a href="pms_contacts.php"><?php echo $lang_pms['Contacts'] ?></a></li>
				</ul>
			</div>
		</div>
	</div>
	<br />
	<div class="blockform">
		<div class="box">
			<form method="post" action="pms_contacts.php">
				<input type="hidden" name="action" value="delete_multiple" />
				<input type="hidden" name="contacts" value="<?php echo $idlist ?>" />
				<input type="hidden" name="delete_multiple_comply" value="1" />
				<div class="inform">
				<div class="forminfo">
					<p><?php echo $lang_pms['Delete contacts comply'] ?></p>
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

// Add a contact
if (isset($_POST['add']))
{
	// Make sure they got here from the site
	confirm_referrer('pms_contacts.php');
	
	if (isset($_POST['req_username']))
	{
		$sql_where = 'u.username=\''.$db->escape($_POST['req_username']).'\'';
		$redirect = 'pms_contacts.php';
		$authorized = (isset($_POST['req_refuse']) && intval($_POST['req_refuse']) == 1)  ? 0 : 1;
	}
	else
	{
		$sql_where = 'u.id='.intval($_POST['add']);
		$redirect = 'profile.php?id='.intval($_POST['add']);
		$authorized = 1;
	}
	
	$result = $db->query("SELECT u.id, u.username, g.g_id, g.g_pm, COUNT(c.id) AS allready FROM ".$db->prefix."users AS u INNER JOIN ".$db->prefix."groups AS g ON u.group_id=g.g_id LEFT JOIN ".$db->prefix."contacts AS c ON (c.contact_id=u.id AND c.user_id=".$pun_user['id'].") WHERE u.id!=1 AND ".$sql_where." GROUP BY u.id, g.g_id") or error("Unable to find the informations of the user", __FILE__, __LINE__, $db->error());
	
	if ($contact = $db->fetch_assoc($result))
	{		
		if (!$contact['allready'])
		{
			if ($contact['g_pm'] == '1')
			{
				$result = $db->query('INSERT INTO '.$db->prefix.'contacts (user_id, contact_id, contact_name, allow_msg) VALUES ('.$pun_user['id'].', '.$contact['id'].', \''.$db->escape($contact['username']).'\', '.$authorized.')') or error('Unable to add the contact', __FILE__, __LINE__, $db->error());
				
				redirect($redirect,$lang_pms['Added contact redirect']);
			}
			else
				message($lang_pms['Authorize user']);
		}
		else
			message($lang_pms['User already contact']);
	}
	else
		message($lang_pms['User not exists']);
}

// Delete a contact
if (isset($_GET['delete']))
{
	// Make sure they got here from the site
	confirm_referrer('pms_contacts.php');
	
	$id = intval($_GET['delete']);
	
	$result = $db->query('SELECT user_id FROM '.$db->prefix.'contacts WHERE id='.$id) or error('Unable to find the contact', __FILE__, __LINE__, $db->error());
	
	if ($db->result($result) != $pun_user['id'])
		message($lang_common['Bad request']);

	$result = $db->query('DELETE FROM '.$db->prefix.'contacts WHERE id= '.$id) or error('Unable to delete the contact', __FILE__, __LINE__, $db->error());
	
	redirect('pms_contacts.php',$lang_pms['Deleted contact redirect']);
}

// Switch contact status
if (isset($_GET['switch']))
{
	// Make sure they got here from the site
	confirm_referrer('pms_contacts.php');
	
	$id = intval($_GET['switch']);
	
	$result = $db->query('SELECT user_id FROM '.$db->prefix.'contacts WHERE id='.$id) or error('Unable to find the contact', __FILE__, __LINE__, $db->error());
	
	if ($db->result($result) != $pun_user['id'])
		message($lang_common['Bad request']);

	$result = $db->query('UPDATE '.$db->prefix.'contacts SET allow_msg = 1-allow_msg WHERE id= '.$id) or error('Unable to edit the status of the contact', __FILE__, __LINE__, $db->error());
	
	redirect('pms_contacts.php',$lang_pms['Status redirect']);
}

// Build page
$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_pms['Private Messages'], $lang_pms['Contacts']);

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
					<li><a href="pms_sending_lists.php"><?php echo $lang_pms['Sending lists'] ?></a></li>
					<li class="isactive"><a href="pms_contacts.php"><?php echo $lang_pms['Contacts'] ?></a></li>
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
		<form action="pms_contacts.php" method="post">
		<div class="inform">
			<fieldset>
				<legend><?php echo $lang_pms['Add contact'] ?></legend>
				<div class="infldset">
					<label class="conl"><?php echo $lang_pms['Contact name'] ?><br />
					<input type="text" name="req_username" size="25" maxlength="120" tabindex="1" /><br /><br />
					<input type="checkbox" name="req_refuse" value="1" tabindex="2" /><?php echo $lang_pms['Refuse user'] ?><br />
					</label>
				</div>
			</fieldset>
		</div>
		<p class="buttons"><input type="submit" name="add" value="<?php echo $lang_pms['Add'] ?>" accesskey="s" /></p>
		</form>
	</div>
</div>
<br />
<form method="post" action="pms_contacts.php">
<div class="blocktable">
	<div class="box">
		<div class="inbox">
			<table cellspacing="0">
			<thead>
				<tr>
					<th><?php echo $lang_pms['Contact name'] ?></th>
					<th><?php echo $lang_pms['Rights contact'] ?></th>
					<th><?php echo $lang_pms['Delete'] ?></th>
					<th><?php echo $lang_pms['Quick message'] ?></th>
					<th class="tcmod"><label style="display: inline; white-space: nowrap;"><?php echo $lang_pms['Select'] ?>&nbsp;<input type="checkbox" id="checkAllButon" value="1" onclick="javascript:checkAll('selected_contacts[]','checkAllButon');" /></label></th>
				</tr>
			</thead>
			<tbody>
<?php
// Fetch contacts
$result = $db->query('SELECT * FROM '.$db->prefix.'contacts WHERE user_id='.$pun_user['id'].' ORDER BY allow_msg DESC, contact_name ASC') or error('Unable to update the list of the contacts', __FILE__, __LINE__, $db->error());

if ($db->num_rows($result))
{
	while ($cur_contact = $db->fetch_assoc($result))
	{
		// authorized or refused
		if ($cur_contact['allow_msg'])
		{
			$status_text = $lang_pms['Authorized messages'].' - <a href="pms_contacts.php?switch='.$cur_contact['id'].'" title="'.sprintf($lang_pms['Refuse from'], pun_htmlspecialchars($cur_contact['contact_name'])).'">'.$lang_pms['Refuse'].'</a>';
			$status_class = '';
		}
		else {
			$status_text = $lang_pms['Refused messages'].' - <a href="pms_contacts.php?switch='.$cur_contact['id'].'" title="'.sprintf($lang_pms['Authorize from'], pun_htmlspecialchars($cur_contact['contact_name'])).'">'.$lang_pms['Authorize'].'</a>';
			$status_class =  ' class="iclosed"';
		}
?>
	<tr<?php echo $status_class ?>>
	<?php
		if ($pun_user['g_view_users'] == '1')
			echo '<td><a href="profile.php?id='.$cur_contact['contact_id'].'">'.pun_htmlspecialchars($cur_contact['contact_name']).'</a></td>';
		else
			echo '<td>'.pun_htmlspecialchars($cur_contact['contact_name']).'</td>';
	?>
		<td><?php echo $status_text; ?></td>
		<td><a href="pms_contacts.php?delete=<?php echo $cur_contact['id']?>" title="<?php printf($lang_pms['Delete x'], pun_htmlspecialchars($cur_contact['contact_name'])) ?>" onclick="return window.confirm('<?php echo $lang_pms['Delete contact confirm'] ?>')"><?php echo $lang_pms['Delete'] ?></a></td>
		<td><a href="pms_send.php?uid=<?php echo $cur_contact['contact_id']?>" title="<?php printf($lang_pms['Quick message x'], pun_htmlspecialchars($cur_contact['contact_name'])) ?>"><?php echo $lang_pms['Quick message'] ?></a></td>
		<td class="tcmod"><input type="checkbox" name="selected_contacts[]" value="<?php echo $cur_contact['id']; ?>" /></td>
	</tr>
<?php
	}
}
else
	echo "\t".'<tr><td class="puncon1" colspan="5">'.$lang_pms['No contacts'].'</td></tr>'."\n";
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
					<option value="send"><?php echo $lang_pms['Quick message'] ?></option>
					<option value="authorize"><?php echo $lang_pms['Authorize'] ?></option>
					<option value="refuse"><?php echo $lang_pms['Refuse'] ?></option>
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