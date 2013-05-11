<?php
/**
 * Copyright (C)2010-2013 adaur
 * Another Private Messaging System v3.0.5
 * Based on work from Vincent Garnier, Connorhd and David 'Chacmool' Djurback
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';

// No guest here !
if ($pun_user['is_guest'])
	message($lang_common['No permission']);
	
// User enable PM ?
if (!$pun_user['use_pm'] == '1')
	message($lang_common['No permission']);

// Are we allowed to use this ?
if (!$pun_config['o_pms_enabled'] == '1' || $pun_user['g_pm'] == '0')
	message($lang_common['No permission']);

// Load the additionals language files
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/post.php'))
       require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';
else
       require PUN_ROOT.'lang/English/post.php';

$p_destinataire = '';
$p_contact = '';
$p_subject = '';
$p_message = '';

// Clean informations
$r = (isset($_REQUEST['reply']) ? intval($_REQUEST['reply']) : '0');
$q = (isset($_REQUEST['quote']) ? intval($_REQUEST['quote']) : '0');
$edit = isset($_REQUEST['edit']) ? intval($_REQUEST['edit']) : '0';
$tid = isset($_REQUEST['tid']) ? intval($_REQUEST['tid']) : '0';
$mid = isset($_REQUEST['mid']) ? intval($_REQUEST['mid']) : '0';

$errors = array();

if (!empty($r) && !isset($_POST['form_sent'])) // It's a reply
{
	$result = $db->query('SELECT DISTINCT owner, receiver FROM '.$db->prefix.'messages WHERE shared_id='.$r) or error('Unable to get the informations of the message', __FILE__, __LINE__, $db->error());
	
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);
		
	$p_ids = array();
		
	while ($arry_dests = $db->fetch_assoc($result))
	{	
		if ($arry_dests['receiver'] == '0')
			message($lang_common['Bad request']);
			
		$p_ids[] = $arry_dests['owner'];
	}
	
	if (!in_array($pun_user['id'], $p_ids)) // Are we in the array? If not, we add ourselves
		$p_ids[] = $pun_user['id'];
	
	$p_ids = implode(', ', $p_ids);
	
	$result_subject = $db->query('SELECT subject FROM '.$db->prefix.'messages WHERE shared_id='.$r.' AND show_message=1') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());

	if (!$db->num_rows($result_subject))
		message($lang_common['Bad request']);

	$p_subject = $db->result($result_subject);
	
	$result_username = $db->query('SELECT username FROM '.$db->prefix.'users WHERE id IN ('.$p_ids.')') or error('Unable to find the owners of the message', __FILE__, __LINE__, $db->error());
	
	if (!$db->num_rows($result_username))
		message($lang_common['Bad request']);
		
	$p_destinataire = array();
	
	while ($username_result = $db->fetch_assoc($result_username))
	{
		$p_destinataire[] = $username_result['username'];
	}
	
	$p_destinataire = implode(', ', $p_destinataire);
	
	if (!empty($q) && $q > '0') // It's a reply with a quote
	{
		// Get message info
		$result = $db->query('SELECT sender, message FROM '.$db->prefix.'messages WHERE id='.$q.' AND owner='.$pun_user['id']) or error('Unable to find the informations of the message', __FILE__, __LINE__, $db->error());
			
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);
			
		$re_message = $db->fetch_assoc($result);
		
		// Quote the message
		$p_message = '[quote='.$re_message['sender'].']'.$re_message['message'].'[/quote]';
	}
}
if (!empty($edit) && !isset($_POST['form_sent'])) // It's an edit
{
	// Check that $edit looks good
	if ($edit <= '0')
		message($lang_common['Bad request']);
	
	$result = $db->query('SELECT sender_id, message, receiver FROM '.$db->prefix.'messages WHERE id='.$edit) or error('Unable to get the informations of the message', __FILE__, __LINE__, $db->error());
	
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);
		
	$edit_msg = $db->fetch_assoc($result);
	
	// If you're not the owner of this message, why do you want to edit it?
	if ($edit_msg['sender_id'] != $pun_user['id'] && !$pun_user['is_admmod'] || $edit_msg['receiver'] == '0' && !$pun_user['is_admmod'])
		message($lang_common['No permission']);

	// Insert the message
	$p_message = censor_words($edit_msg['message']);
}
if (isset($_POST['form_sent'])) // The post button has been pressed
{
	$hide_smilies = isset($_POST['hide_smilies']) ? '1' : '0';
	
	// Make sure form_user is correct
	if ($_POST['form_user'] != $pun_user['username'])
		message($lang_common['Bad request']);
	
	// Flood protection by Newman
	if (!isset($_SESSION))
		session_start();

	if($_SESSION['last_session_request'] > time() - $pun_user['g_post_flood'])
		$errors[] = $lang_post['Flood start'].' '.$pun_user['g_post_flood'].' '.$lang_post['flood end'];
		
	// Check users boxes
	if ($pun_user['g_pm_limit'] != '0' && !$pun_user['is_admmod'] && $pun_user['num_pms'] >= $pun_user['g_pm_limit'])
		$errors[] = $lang_pms['Sender full'];
	
	// Build receivers list
	$p_destinataire = isset($_POST['p_username']) ? pun_trim($_POST['p_username']) : '';
	$p_contact = isset($_POST['p_contact']) ? pun_trim($_POST['p_contact']) : '';
    $dest_list = explode(', ', $p_destinataire);
	
	if (!in_array($pun_user['username'], $dest_list))
		$dest_list[] = $pun_user['username'];
	
	if ($p_contact != '0')
		$dest_list[] = $p_contact;
	
	$dest_list = array_map('pun_trim', $dest_list);
	$dest_list = array_unique($dest_list);
	
	foreach ($dest_list as $k=>$v)
	{
		if ($v == '') unset($dest_list[$k]);
	}

    if (count($dest_list) < '1' && $edit == '0')
		$errors[] = $lang_pms['Must receiver'];
    elseif (count($dest_list) > $pun_config['o_pms_max_receiver'])
		$errors[] = sprintf($lang_pms['Too many receiver'], $pun_config['o_pms_max_receiver']-1);

	$destinataires = array(); $i = '0';
	$list_ids = array();
	$list_usernames = array();
	foreach ($dest_list as $destinataire)
	{
		// Get receiver infos
		$result_username = $db->query('SELECT u.id, u.username, u.email, u.notify_pm, u.notify_pm_full, u.use_pm, u.num_pms, g.g_id, g.g_pm_limit, c.allow_msg FROM '.$db->prefix.'users AS u INNER JOIN '.$db->prefix.'groups AS g ON u.group_id=g.g_id LEFT JOIN '.$db->prefix.'messages AS pm ON pm.owner=u.id LEFT JOIN '.$db->prefix.'contacts AS c ON (c.user_id=u.id AND c.contact_id='.$pun_user['id'].') WHERE u.id!=1 AND u.username=\''.$db->escape($destinataire).'\' GROUP BY u.username') or error('Unable to get user ID', __FILE__, __LINE__, $db->error());
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
			// Check receivers boxes
			elseif ($destinataires[$i]['g_id'] > PUN_GUEST && $destinataires[$i]['g_pm_limit'] != '0' && $destinataires[$i]['num_pms'] >= $destinataires[$i]['g_pm_limit'])
				$errors[] = sprintf($lang_pms['Dest full'], pun_htmlspecialchars($destinataire));	
			// Are we authorized?
			elseif (!$pun_user['is_admmod'] && $destinataires[$i]['allow_msg'] == '0')
				$errors[] = sprintf($lang_pms['User blocked'], pun_htmlspecialchars($destinataire));
		}
		else
			$errors[] = sprintf($lang_pms['No user'], pun_htmlspecialchars($destinataire));
		$i++;
	}
	// Build IDs' & usernames' list : the end
	$ids_list = implode(', ', $list_ids);
	$usernames_list = implode(', ', $list_usernames);
	
	// Check subject
	$p_subject = pun_trim($_POST['req_subject']);
	
	if ($p_subject == '' && $edit == '0')
		$errors[] = $lang_post['No subject'];
	elseif (pun_strlen($p_subject) > '70')
		$errors[] = $lang_post['Too long subject'];
	elseif ($pun_config['p_subject_all_caps'] == '0' && strtoupper($p_subject) == $p_subject && $pun_user['is_admmod'])
		$p_subject = ucwords(strtolower($p_subject));

	// Clean up message from POST
	$p_message = pun_linebreaks(pun_trim($_POST['req_message']));

	// Check message
	if ($p_message == '')
		$errors[] = $lang_post['No message'];

	// Here we use strlen() not pun_strlen() as we want to limit the post to PUN_MAX_POSTSIZE bytes, not characters
	else if (strlen($p_message) > PUN_MAX_POSTSIZE)
		$errors[] = sprintf($lang_post['Too long message'], forum_number_format(PUN_MAX_POSTSIZE));
	else if ($pun_config['p_message_all_caps'] == '0' && strtoupper($p_message) == $p_message && $pun_user['is_admmod'])
		$p_message = ucwords(strtolower($p_message));

	// Validate BBCode syntax
	if ($pun_config['p_message_bbcode'] == '1')
	{
		require PUN_ROOT.'include/parser.php';
		$p_message = preparse_bbcode($p_message, $errors);
	}	
	// Send message(s)	
	if (empty($errors) && !isset($_POST['preview']))
	{
		$_SESSION['last_session_request'] = $now = time();
		
		if ($pun_config['o_pms_notification'] == '1')
		{
			require_once PUN_ROOT.'include/email.php';
			
			// Load the "new_pm" template
			if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/mail_templates/new_pm.tpl'))
				$mail_tpl = trim(file_get_contents(PUN_ROOT.'lang/'.$pun_user['language'].'/mail_templates/new_pm.tpl'));
			else
				$mail_tpl = trim(file_get_contents(PUN_ROOT.'lang/English/mail_templates/new_pm.tpl'));
				
			// Load the "new_pm_full" template
			if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/mail_templates/new_pm_full.tpl'))
				$mail_tpl_full = trim(file_get_contents(PUN_ROOT.'lang/'.$pun_user['language'].'/mail_templates/new_pm_full.tpl'));
			else
				$mail_tpl_full = trim(file_get_contents(PUN_ROOT.'lang/English/mail_templates/new_pm_full.tpl'));
			
			// The first row contains the subject
			$first_crlf = strpos($mail_tpl, "\n");
			$mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
			$mail_message = trim(substr($mail_tpl, $first_crlf));
	
			$mail_subject = str_replace('<board_title>', $pun_config['o_board_title'], $mail_subject);
			$mail_message = str_replace('<sender>', $pun_user['username'], $mail_message);
			$mail_message = str_replace('<board_mailer>', $pun_config['o_board_title'].' '.$lang_common['Mailer'], $mail_message);
			
			// The first row contains the subject
			$first_crlf_full = strpos($mail_tpl_full, "\n");
			$mail_subject_full = trim(substr($mail_tpl_full, 8, $first_crlf_full-8));
			$mail_message_full = trim(substr($mail_tpl_full, $first_crlf_full));
			
			$cleaned_message = bbcode2email($p_message, -1);
	
			$mail_subject_full = str_replace('<board_title>', $pun_config['o_board_title'], $mail_subject_full);
			$mail_message_full = str_replace('<sender>', $pun_user['username'], $mail_message_full);
			$mail_message_full = str_replace('<message>', $cleaned_message, $mail_message_full);
			$mail_message_full = str_replace('<board_mailer>', $pun_config['o_board_title'].' '.$lang_common['Mailer'], $mail_message_full);
		}
		if (empty($r) && empty($edit)) // It's a new message
		{
			$result_shared = $db->query('SELECT last_shared_id FROM '.$db->prefix.'messages ORDER BY last_shared_id DESC LIMIT 1') or error('Unable to fetch last_shared_id', __FILE__, __LINE__, $db->error());
			if (!$db->num_rows($result_shared))
				$shared_id = '1';
			else
			{
				$shared_result = $db->result($result_shared);
				$shared_id = $shared_result + '1';
			}
				
			foreach ($destinataires as $dest)
			{
				$val_showed = '0';
				
				if ($dest['id'] == $pun_user['id'])
					$val_showed = '1';
				else
					$val_showed = '0';
					
				$db->query('INSERT INTO '.$db->prefix.'messages (shared_id, last_shared_id, owner, subject, message, sender, receiver, sender_id, receiver_id, sender_ip, hide_smilies, posted, show_message, showed) VALUES(\''.$shared_id.'\', \''.$shared_id.'\', \''.$dest['id'].'\', \''.$db->escape($p_subject).'\', \''.$db->escape($p_message).'\', \''.$db->escape($pun_user['username']).'\', \''.$db->escape($usernames_list).'\', \''.$pun_user['id'].'\', \''.$db->escape($ids_list).'\', \''.get_remote_address().'\', \''.$hide_smilies.'\',  \''.$now.'\', \'1\', \''.$val_showed.'\')') or error('Unable to send the message.', __FILE__, __LINE__, $db->error());
				$new_mp = $db->insert_id();
				$db->query('UPDATE '.$db->prefix.'messages SET last_post_id='.$new_mp.', last_post='.$now.', last_poster=\''.$db->escape($pun_user['username']).'\' WHERE shared_id='.$shared_id.' AND show_message=1 AND owner='.$dest['id']) or error('Unable to update the message.', __FILE__, __LINE__, $db->error());
				$db->query('UPDATE '.$db->prefix.'users SET num_pms=num_pms+1 WHERE id='.$dest['id']) or error('Unable to update user', __FILE__, __LINE__, $db->error());
				// E-mail notification
				if ($pun_config['o_pms_notification'] == '1' && $dest['notify_pm'] == '1' && $dest['id'] != $pun_user['id'])
				{
					$mail_message = str_replace('<pm_url>', $pun_config['o_base_url'].'/pms_view.php?tid='.$shared_id.'&mid='.$new_mp.'&box=inbox', $mail_message);
					$mail_message_full = str_replace('<pm_url>', $pun_config['o_base_url'].'/pms_view.php?tid='.$shared_id.'&mid='.$new_mp.'&box=inbox', $mail_message_full);
					
					if ($dest['notify_pm_full'] == '1')
						pun_mail($dest['email'], $mail_subject_full, $mail_message_full);
					else
						pun_mail($dest['email'], $mail_subject, $mail_message);
				}
			}
			$db->query('UPDATE '.$db->prefix.'users SET last_post='.$now.' WHERE id='.$pun_user['id']) or error('Unable to update user', __FILE__, __LINE__, $db->error());
		}
		if (!empty($r)) // It's a reply or a reply with a quote
		{
			// Check that $edit looks good
			if ($r <= '0')
				message($lang_common['Bad request']);
				
			foreach ($destinataires as $dest)
			{
			
				$val_showed = '0';
				
				if ($dest['id'] == $pun_user['id'])
					$val_showed = '1';
				else
					$val_showed = '0';
					
					$db->query('INSERT INTO '.$db->prefix.'messages (shared_id, owner, subject, message, sender, receiver, sender_id, receiver_id, sender_ip, hide_smilies, posted, show_message, showed) VALUES(\''.$r.'\', \''.$dest['id'].'\', \''.$db->escape($p_subject).'\', \''.$db->escape($p_message).'\', \''.$db->escape($pun_user['username']).'\', \''.$db->escape($usernames_list).'\', \''.$pun_user['id'].'\', \''.$db->escape($ids_list).'\', \''.get_remote_address().'\', \''.$hide_smilies.'\', \''.$now.'\', \'0\', \''.$val_showed.'\')') or error('Unable to send the message.', __FILE__, __LINE__, $db->error());
					$new_mp = $db->insert_id();
					$db->query('UPDATE '.$db->prefix.'messages SET last_post_id='.$new_mp.', last_post='.$now.', last_poster=\''.$db->escape($pun_user['username']).'\' WHERE shared_id='.$r.' AND show_message=1 AND owner='.$dest['id']) or error('Unable to update the message.', __FILE__, __LINE__, $db->error());
					if ($dest['id'] != $pun_user['id'])
					{
						$db->query('UPDATE '.$db->prefix.'messages SET showed = 0 WHERE shared_id='.$r.' AND show_message=1 AND owner='.$dest['id']) or error('Unable to update the message.', __FILE__, __LINE__, $db->error());
					}
					// E-mail notification
					if ($pun_config['o_pms_notification'] == '1' && $dest['notify_pm'] == '1' && $dest['id'] != $pun_user['id'])
					{
						$mail_message = str_replace('<pm_url>', $pun_config['o_base_url'].'/pms_view.php?tid='.$r.'&mid='.$new_mp.'&box=inbox', $mail_message);
						$mail_message_full = str_replace('<pm_url>', $pun_config['o_base_url'].'/pms_view.php?tid='.$r.'&mid='.$new_mp.'&box=inbox', $mail_message_full);
						
						if ($dest['notify_pm_full'] == '1')
							pun_mail($dest['email'], $mail_subject_full, $mail_message_full);
						else
							pun_mail($dest['email'], $mail_subject, $mail_message);
					}
			}
			$db->query('UPDATE '.$db->prefix.'users SET last_post='.$now.' WHERE id='.$pun_user['id']) or error('Unable to update user', __FILE__, __LINE__, $db->error());
		}
		if (!empty($edit) && !empty($tid)) // It's an edit
		{
			// Check that $edit looks good
			if ($edit <= '0')
				message($lang_common['Bad request']);
			
			$result = $db->query('SELECT shared_id, owner, message FROM '.$db->prefix.'messages WHERE id='.$edit) or error('Unable to get the informations of the message', __FILE__, __LINE__, $db->error());
			
			if (!$db->num_rows($result))
				message($lang_common['Bad request']);
				
			while($edit_msg = $db->fetch_assoc($result))
			{
				// If you're not the owner of this message, why do you want to edit it?
				if ($edit_msg['owner'] != $pun_user['id'] && !$pun_user['is_admmod'])
					message($lang_common['No permission']);
					
				$message = $edit_msg['message'];
				$shared_id_msg = $edit_msg['shared_id'];
			}
			
			$result_msg = $db->query('SELECT id FROM '.$db->prefix.'messages WHERE message=\''.$db->escape($message).'\' AND shared_id='.$shared_id_msg) or error('Unable to get the informations of the message', __FILE__, __LINE__, $db->error());
			
			if (!$db->num_rows($result_msg))
				message($lang_common['Bad request']);
				
			while($list_ids = $db->fetch_assoc($result_msg))
			{		
				$ids_edit[] = $list_ids['id'];
			}
			
			$ids_edit = implode(',', $ids_edit);
				
			// Finally, edit the message - maybe this query is unsafe?
			$db->query('UPDATE '.$db->prefix.'messages SET message=\''.$db->escape($p_message).'\' WHERE message=\''.$db->escape($message).'\' AND id IN ('.$ids_edit.')') or error('Unable to edit the message', __FILE__, __LINE__, $db->error());
		}
			redirect('pms_inbox.php', $lang_pms['Sent redirect']);
	}
}
else
{
	// To user(s)
	if (isset($_GET['uid']))
	{
		$users_id = explode('-', $_GET['uid']);
		$users_id = array_map('intval', $users_id);
		foreach ($users_id as $k=>$v)
			if ($v <= 0) unset($users_id[$k]);
		
		$arry_dests = array();
		foreach ($users_id as $user_id)
		{
			$result = $db->query('SELECT username FROM '.$db->prefix.'users WHERE id='.$user_id) or error('Unable to find the informations of the message', __FILE__, __LINE__, $db->error());
			
			if (!$db->num_rows($result))
				message($lang_common['Bad request']);
			
			$arry_dests[] = $db->result($result);
		}
			
		$p_destinataire = implode(', ', $arry_dests);
	}
	// From a list
	if (isset($_GET['lid']))
	{
		$id = intval($_GET['lid']);
		
		$arry_dests = array();
		$result = $db->query('SELECT receivers FROM '.$db->prefix.'sending_lists WHERE user_id='.$pun_user['id'].' AND id='.$id) or error('Unable to find the informations of the message', __FILE__, __LINE__, $db->error());
		
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);
		
		$arry_dests = unserialize($db->result($result));
			
		$p_destinataire = implode(', ', $arry_dests);
	}
}

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_pms['Private Messages'], $lang_pms['Send a message']);

$required_fields = array('req_message' => $lang_common['Message']);
$focus_element = array('post');
$focus_element[] = 'req_message';

if ($r == '0' && $q == '0' && $edit == '0')
{
	$required_fields['req_subject'] = $lang_common['Subject'];
	$focus_element[] = 'req_subject';
}

$page_head['jquery'] = '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>';
$page_head['script'] = '<script type="text/javascript" src="include/pms.js"></script>';

define('PUN_ACTIVE_PAGE', 'pm');
require PUN_ROOT.'header.php';
?>
<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="pms_inbox.php"><?php echo $lang_pms['Private Messages'] ?></a></li>
			<li><span>»&#160;</span><?php echo $lang_pms['Send a message'] ?></li>
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
					<li class="isactive"><a href="pms_send.php"><?php echo $lang_pms['Write message'] ?></a></li>
					<li><a href="pms_sending_lists.php"><?php echo $lang_pms['Sending lists'] ?></a></li>
					<li><a href="pms_contacts.php"><?php echo $lang_pms['Contacts'] ?></a></li>
				</ul>
			</div>
		</div>
	</div>
<br />
<?php
// If there are errors, we display them
if (!empty($errors))
{
?>
<div id="posterror" class="block">
	<h2><span><?php echo $lang_post['Post errors'] ?></span></h2>
	<div class="box">
		<div class="inbox error-info">
			<p><?php echo $lang_post['Post errors info'] ?></p>
			<ul class="error-list">
<?php

	foreach ($errors as $cur_error)
		echo "\t\t\t\t".'<li><strong>'.$cur_error.'</strong></li>'."\n";
?>
			</ul>
		</div>
	</div>
</div>

<?php

}
else if (isset($_POST['preview']))
{
	require_once PUN_ROOT.'include/parser.php';
	$preview_message = parse_message($p_message, $hide_smilies);

?>
<div class="blockform">
	<div class="box">
		<div class="inform">
		<fieldset>
			<legend><?php echo $lang_post['Post preview'] ?></legend>
			<div class="infldset txtarea">
				<p><?php echo $preview_message."\n" ?></p>	
			</div>
		</fieldset>
	</div>
</div>
</div>
<br />

<?php

}

$cur_index = 1;

?>
<div class="blockform">
	<div class="box">
	<form method="post" id="post" action="pms_send.php" onsubmit="return process_form(this)">
		<div class="inform">
		<fieldset>
			<legend><?php echo $lang_common['Write message legend'] ?></legend>
			<div class="infldset txtarea">
				<input type="hidden" name="form_sent" value="1" />
				<input type="hidden" name="form_user" value="<?php echo pun_htmlspecialchars($pun_user['username']) ?>" />
				<?php echo (($r != '0') ? '<input type="hidden" name="reply" value="'.$r.'" />' : '') ?>
				<?php echo (($edit != '0') ? '<input type="hidden" name="edit" value="'.$edit.'" />' : '') ?>
				<?php echo (($q != '0') ? '<input type="hidden" name="quote" value="1" />' : '') ?>
				<?php echo (($tid != '0') ? '<input type="hidden" name="tid" value="'.$tid.'" />' : '') ?>
				<?php if ($r == '0' && $q == '0' && $edit == '0') : ?>
				<div id="js_enabled">
				<?php
				$result = $db->query('SELECT * FROM '.$db->prefix.'sending_lists WHERE user_id='.$pun_user['id'].' ORDER BY id DESC') or error('Unable to update the list of the contacts', __FILE__, __LINE__, $db->error());

				if ($db->num_rows($result))
				{
					echo '<div class="conl"><p id="sending_list" style="display: none;"><label>'.$lang_pms['Sending lists'].'<br />';
					echo '<select id="sending_list" name="sending_list">';
						echo '<option value="" selected>'.$lang_pms['Select a list'].'</option>';
							while ($cur_list = $db->fetch_assoc($result))
							{
								$usernames = '';
								$ids_list = unserialize($cur_list['array_id']);
								$usernames_list = unserialize($cur_list['receivers']);
								for($i = 0; $i < count($ids_list); $i++)
								{
									if ($i > 0 && $i < count($ids_list))
											$usernames = $usernames.', ';
									
									$usernames = $usernames.pun_htmlspecialchars($usernames_list[$i]);
								} 
								echo '<option value="'.$usernames.'">'.pun_htmlspecialchars($cur_list['name']).'</option>';
							}
					echo '</select><br />';
					echo '</label></p></div>';
				}
				?>
				</div>
				<noscript><p><?php echo $lang_pms['JS required'] ?></p></noscript>
				<label class="required"><strong><?php echo $lang_pms['Send to'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />
				<input type="text" name="p_username" id="p_username" size="30" value="<?php echo pun_htmlspecialchars($p_destinataire) ?>" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
				<p><?php echo $lang_pms['Send multiple'].($pun_config['o_pms_max_receiver']-1) ?></p>
				<div class="clearer"></div>
				<label class="required"><strong><?php echo $lang_common['Subject'] ?> <span><?php echo $lang_common['Required'] ?></span></strong><br />
				<input class="longinput" type="text" name="req_subject" value="<?php echo ($p_subject != '' ? pun_htmlspecialchars($p_subject) : ''); ?>" size="80" maxlength="255" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
				<?php else : ?>
				<input type="hidden" name="p_username" value="<?php echo pun_htmlspecialchars($p_destinataire) ?>" />
				<input type="hidden" name="req_subject" value="<?php echo pun_htmlspecialchars($p_subject) ?>" />
        		<?php endif; ?>
				<label class="required"><strong><?php echo $lang_common['Message'] ?> <span><?php echo $lang_common['Required'] ?></span></strong>
				<textarea name="req_message" rows="20" cols="95" tabindex="<?php echo $cur_index++ ?>"><?php echo ($p_message != '' ? pun_htmlspecialchars($p_message) : ''); ?></textarea><br /></label>
						<ul class="bblinks">
							<li><span><a href="help.php#bbcode" onclick="window.open(this.href); return false;"><?php echo $lang_common['BBCode'] ?></a> <?php echo ($pun_config['p_message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
							<li><span><a href="help.php#img" onclick="window.open(this.href); return false;"><?php echo $lang_common['img tag'] ?></a> <?php echo ($pun_config['p_message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
							<li><span><a href="help.php#smilies" onclick="window.open(this.href); return false;"><?php echo $lang_common['Smilies'] ?></a> <?php echo ($pun_config['o_smilies'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
						</ul>
					</div>
				</fieldset>
<?php

	if ($pun_config['o_smilies'] == '1')
	{
?>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_common['Options'] ?></legend>
					<div class="infldset">
						<div class="rbox">
							<?php echo '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($cur_index++).'"'.(isset($_POST['hide_smilies']) ? ' checked="checked"' : '').' />'.$lang_post['Hide smilies'].'<br /></label>' ?>
						</div>
					</div>
				</fieldset>
<?php
	}
?>
			</div>
			<p class="buttons"><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="s" /> <input type="submit" name="preview" value="<?php echo $lang_post['Preview'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="p" /> <a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>
</div>
<?php
	require PUN_ROOT.'footer.php';
?>