<?php
/**
 * Copyright (C)2010-2014 adaur
 * Another Private Messaging System v3.0.8
 * Based on work from Vincent Garnier, Connorhd and David 'Chacmool' Djurback
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/parser.php';

// No guest here !
if ($pun_user['is_guest'])
	message($lang_common['No permission']);
	
// User enable PM ?
if (!$pun_user['use_pm'] == '1')
	message($lang_common['No permission']);

// Are we allowed to use this ?
if (!$pun_config['o_pms_enabled'] =='1' || $pun_user['g_pm'] == '0')
	message($lang_common['No permission']);

// Load the additionals language files
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/topic.php'))
       require PUN_ROOT.'lang/'.$pun_user['language'].'/topic.php';
else
       require PUN_ROOT.'lang/English/topic.php';

if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/delete.php'))
       require PUN_ROOT.'lang/'.$pun_user['language'].'/delete.php';
else
       require PUN_ROOT.'lang/English/delete.php';

// Get the message's and topic's id
$mid = isset($_REQUEST['mid']) ? intval($_REQUEST['mid']) : '0';
$tid = isset($_REQUEST['tid']) ? intval($_REQUEST['tid']) : '0';
$pid = isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : '0';

$delete_all = '0';

$topic_msg = isset($_REQUEST['all_topic']) ? intval($_REQUEST['all_topic']) : '0';
$delete_all = isset($_POST['delete_all']) ? '1' : '0';

if ($pid)
{
	$result = $db->query('SELECT shared_id FROM '.$db->prefix.'messages WHERE id='.$mid) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	$id = $db->result($result);

	// Determine on what page the post is located (depending on $pun_user['disp_posts'])
	$result = $db->query('SELECT id FROM '.$db->prefix.'messages WHERE shared_id='.$id.' AND owner='.$pun_user['id'].' ORDER BY posted') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	$num_posts = $db->num_rows($result);

	for ($i = 0; $i < $num_posts; ++$i)
	{
		$cur_id = $db->result($result, $i);
		if ($cur_id == $pid)
			break;
	}
	++$i; // we started at 0

	$_REQUEST['p'] = ceil($i / $pun_user['disp_posts']);
}

// Replace num_replies' feature by a query :-)
$result = $db->query('SELECT COUNT(*) FROM '.$db->prefix.'messages WHERE shared_id='.$tid.' AND owner='.$pun_user['id']) or error('Unable to count the messages', __FILE__, __LINE__, $db->error());
list($num_replies) = $db->fetch_row($result);

// Determine the post offset (based on $_GET['p'])
$num_pages = ceil($num_replies / $pun_user['disp_posts']);

// Page ?
$page = (!isset($_REQUEST['p']) || $_REQUEST['p'] <= '1') ? '1' : intval($_REQUEST['p']);
$start_from = $pun_user['disp_posts'] * ($page - 1);
	
// Check that $mid looks good
if ($mid <= 0)
	message($lang_common['Bad request']);

// Action ?
$action = ((isset($_REQUEST['action']) && ($_REQUEST['action'] == 'delete')) ? $_REQUEST['action'] : '');

	// Delete a single message or a full topic
	if ($action == 'delete')
	{
		// Make sure they got here from the site
		confirm_referrer('pms_view.php');
		
		if (isset($_POST['delete_comply']))
		{
			if ($topic_msg > '1' || $topic_msg < '0')
				message($lang_common['Bad request']);
			
			if ($topic_msg == '0')
			{
				if ($pun_user['is_admmod'])
				{
					if ($delete_all == '1')
					{
						$result_msg = $db->query('SELECT message FROM '.$db->prefix.'messages WHERE id='.$mid) or error('Unable to get the informations of the message', __FILE__, __LINE__, $db->error());
				
						if (!$db->num_rows($result_msg))
							message($lang_common['Bad request']);
							
						$delete_msg = $db->fetch_assoc($result_msg);
							
						// To devs: maybe this query is unsafe? Maybe you know how to secure it? I'm open to your suggestions ;) !
						$result_ids = $db->query('SELECT id FROM '.$db->prefix.'messages WHERE message=\''.$db->escape($delete_msg).'\'') or error('Unable to get the informations of the message', __FILE__, __LINE__, $db->error());
						
						if (!$db->num_rows($result_ids))
							message($lang_common['Bad request']);
						
						$ids_msg[] = $db->result($result_ids);
						
						// Finally, delete the messages!
						$db->query('DELETE FROM '.$db->prefix.'messages WHERE id IN ('.$ids_msg.')') or error('Unable to delete the message', __FILE__, __LINE__, $db->error());
					}
					else
						$db->query('DELETE FROM '.$db->prefix.'messages WHERE id='.$mid) or error('Unable to delete the message', __FILE__, __LINE__, $db->error());
				}
				else
				{
					$result = $db->query('SELECT owner FROM '.$db->prefix.'messages WHERE id='.$mid) or error('Unable to delete the message', __FILE__, __LINE__, $db->error());
					$owner = $db->result($result);
					
					if($owner != $pun_user['id']) // Double check : hackers are everywhere =)
						message($lang_common['No permission']);
						
					$db->query('DELETE FROM '.$db->prefix.'messages WHERE id='.$mid) or error('Unable to delete the message', __FILE__, __LINE__, $db->error());
				}
			}
			else
			{
				if ($pun_user['is_admmod'])
				{
					if ($delete_all == '1')
					{
						$result_ids = $db->query('SELECT DISTINCT owner FROM '.$db->prefix.'messages WHERE shared_id='.$tid) or error('Unable to get the informations of the message', __FILE__, __LINE__, $db->error());
						
						if (!$db->num_rows($result_ids))
							message($lang_common['Bad request']);
						
						while ($user_ids = $db->fetch_assoc($result_ids))
						{
							$ids_users[] = $user_ids['owner'];
						}
						
						$ids_users = implode(',', $ids_users);
						
						$db->query('UPDATE '.$db->prefix.'users SET num_pms=num_pms-1 WHERE id IN('.$ids_users.')') or error('Unable to update user', __FILE__, __LINE__, $db->error());
						$db->query('DELETE FROM '.$db->prefix.'messages WHERE shared_id='.$tid) or error('Unable to delete the message', __FILE__, __LINE__, $db->error());
					}
					else
					{
						$db->query('DELETE FROM '.$db->prefix.'messages WHERE shared_id='.$tid.' AND owner='.$pun_user['id']) or error('Unable to delete the message', __FILE__, __LINE__, $db->error());
						$db->query('UPDATE '.$db->prefix.'messages SET receiver=REPLACE(receiver,\''.$db->escape($pun_user['username']).'\',\''.$db->escape($pun_user['username'].' Deleted').'\') WHERE receiver LIKE \'%'.$db->escape($pun_user['username']).'%\' AND shared_id='.$tid) or error('Unable to update private messages', __FILE__, __LINE__, $db->error());
						$db->query('UPDATE '.$db->prefix.'users SET num_pms=num_pms-1 WHERE id='.$pun_user['id']) or error('Unable to update user', __FILE__, __LINE__, $db->error());
					}
				}
				else
				{
					$result = $db->query('SELECT owner FROM '.$db->prefix.'messages WHERE id='.$mid) or error('Unable to delete the message', __FILE__, __LINE__, $db->error());
					$owner = $db->result($result);
					
					if($owner != $pun_user['id']) // Double check : hackers are everywhere =)
						message($lang_common['No permission']);
						
					$db->query('DELETE FROM '.$db->prefix.'messages WHERE id='.$mid) or error('Unable to delete the message', __FILE__, __LINE__, $db->error());
					$db->query('UPDATE '.$db->prefix.'users SET num_pms=num_pms-1 WHERE id='.$pun_user['id']) or error('Unable to update user', __FILE__, __LINE__, $db->error());
				}
			}
			
			// Redirect
			redirect('pms_inbox.php', $lang_pms['Del redirect']);
		}
		else
		{
			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_pms['Delete message']);
			
			define('PUN_ACTIVE_PAGE', 'pm');
			require PUN_ROOT.'header.php';
			
			// If you're not the owner of the message, you can't delete it.
			$result = $db->query('SELECT owner, show_message, posted, sender, message, hide_smilies FROM '.$db->prefix.'messages WHERE id='.$mid) or error('Unable to delete the message', __FILE__, __LINE__, $db->error());
			$cur_delete = $db->fetch_assoc($result);
			
			if($cur_delete['owner'] != $pun_user['id'] && !$pun_user['is_admmod'])
				message($lang_common['No permission']);

			$cur_delete['message'] = parse_message($cur_delete['message'], $cur_delete['hide_smilies']);
		?>
<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="pms_inbox.php"><?php echo $lang_pms['Private Messages'] ?></a></li>
			<li><span>»&#160;</span><a href="pms_inbox.php"><?php echo $lang_pms['Inbox'] ?></a></li>
			<li><span>»&#160;</span><?php echo $lang_pms['Delete message'] ?></li>
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
					<li class="isactive"><a href="pms_inbox.php"><?php echo $lang_pms['Inbox'] ?></a></li>
					<li><a href="pms_send.php"><?php echo $lang_pms['Write message'] ?></a></li>
					<li><a href="pms_sending_lists.php"><?php echo $lang_pms['Sending lists'] ?></a></li>
					<li><a href="pms_contacts.php"><?php echo $lang_pms['Contacts'] ?></a></li>
				</ul>
			</div>
		</div>
	</div>
<br />
<div class="blockform">
	<div class="box">
		<form action="pms_view.php" method="post">
					<input type="hidden" name="action" value="delete" />
					<input type="hidden" name="mid" value="<?php echo $mid ?>" />
					<input type="hidden" name="tid" value="<?php echo $tid ?>" />
					<input type="hidden" name="delete_comply" value="1" />
					<input type="hidden" name="all_topic" value="<?php echo $cur_delete['show_message'] ?>" />
			<div class="inform">
				<div class="forminfo">
					<h3><span><?php printf($cur_delete['show_message'] ? $lang_delete['Topic by'] : $lang_delete['Reply by'], '<strong>'.pun_htmlspecialchars($cur_delete['sender']).'</strong>', format_time($cur_delete['posted'])) ?></span></h3>
					<p><?php echo ($cur_delete['show_message']) ? '<strong>'.$lang_delete['Topic warning'].'<br /></strong>'.$lang_pms['Topic warning info'].'' : '<strong>'.$lang_delete['Warning'].'</strong>' ?><br /><?php echo $lang_delete['Delete info'] ?></p>
					<?php if ($pun_user['is_admmod']) : ?>
					<label><input type="checkbox" name="delete_all" value="1" /><?php echo $lang_pms['Delete for everybody'] ?></label>
					<?php endif; ?>
				</div>
			</div>
			<p class="buttons"><input type="submit" name="delete" value="<?php echo $lang_pms['Delete'] ?>" /> <a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>

<div id="postreview">
	<div class="blockpost">
		<div class="box">
			<div class="inbox">
				<div class="postbody">
					<div class="postleft">
						<dl>
							<dt><strong><?php echo pun_htmlspecialchars($cur_delete['sender']) ?></strong></dt>
							<dd><span><?php echo format_time($cur_delete['posted']) ?></span></dd>
						</dl>
					</div>
					<div class="postright">
						<div class="postmsg">
							<?php echo $cur_delete['message']."\n" ?>
						</div>
					</div>
				</div>
				<div class="clearer"></div>
			</div>
		</div>
	</div>
</div>
</div>
		
		<?php
			require PUN_ROOT.'footer.php';
		}
	}
	
// Start building page
	
$result_receivers = $db->query('SELECT DISTINCT receiver, owner, sender_id FROM '.$db->prefix.'messages WHERE shared_id='.$tid) or error('Unable to get the informations of the message', __FILE__, __LINE__, $db->error());

if (!$db->num_rows($result_receivers))
		message($lang_common['Bad request']);
		
$owner = array();
		
while ($receiver = $db->fetch_assoc($result_receivers))
{	
	$r_usernames = $receiver['receiver'];
	$owner[] = $receiver['owner'];
	$uid = $receiver['sender_id'];
}

$r_usernames = str_replace('Deleted', $lang_pms['Deleted'], $r_usernames);

$result = $db->query('SELECT subject FROM '.$db->prefix.'messages WHERE shared_id='.$tid.' AND show_message=1') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());

if (!$db->num_rows($result))
	message($lang_common['Bad request']);

$p_subject = $db->result($result);

$messageh2 = pun_htmlspecialchars($p_subject).' '.$lang_pms['With'].' '.pun_htmlspecialchars($r_usernames);

$quickpost = false;
	if ($pun_config['o_quickpost'] == '1')
	{
		$required_fields = array('req_message' => $lang_common['Message']);
		$quickpost = true;
	}

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_pms['Private Messages'], $lang_pms['View']);

define('PUN_ACTIVE_PAGE', 'pm');
require PUN_ROOT.'header.php';

if(!in_array($pun_user['id'], $owner) && !$pun_user['is_admmod'])
	message($lang_common['No permission']);
	
	$post_count = '0'; // Keep track of post numbers
	
	if ($num_new_pm > '0')
		$db->query('UPDATE '.$db->prefix.'messages SET showed=1 WHERE shared_id='.$tid.' AND show_message=1 AND owner='.$pun_user['id']) or error('Unable to update the status of the message', __FILE__, __LINE__, $db->error());

$result = $db->query('SELECT m.id AS mid, m.shared_id, m.subject, m.sender_ip, m.message, m.hide_smilies, m.posted, m.showed, m.sender, m.sender_id, u.id, u.group_id AS g_id, g.g_user_title, u.username, u.registered, u.email, u.title, u.url, u.location, u.email_setting, u.num_posts, u.admin_note, u.signature, u.use_pm, o.user_id AS is_online FROM '.$db->prefix.'messages AS m, '.$db->prefix.'users AS u LEFT JOIN '.$db->prefix.'online AS o ON (o.user_id=u.id AND o.idle=0) LEFT JOIN '.$db->prefix.'groups AS g ON (u.group_id=g.g_id) WHERE u.id=m.sender_id AND m.shared_id='.$tid.' AND m.owner='.$pun_user['id'].' ORDER BY m.posted LIMIT '.$start_from.','.$pun_user['disp_posts']) or error('Unable to get the message and the informations of the user', __FILE__, __LINE__, $db->error());

if (!$db->num_rows($result))
	message($lang_common['Bad request']);
	
$reply_link = '<a href="pms_send.php?reply='.$tid.'">'.$lang_pms['Reply'].'</a>';
?>
<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="pms_inbox.php"><?php echo $lang_pms['Private Messages'] ?></a></li>
			<li><span>»&#160;</span><a href="pms_inbox.php"><?php echo $lang_pms['Inbox'] ?></a></li>
			<li><span>»&#160;</span><a href="#block"><?php echo $lang_pms['View'] ?></a></li>
			<li><span>»&#160;</span><?php echo $messageh2 ?></li>
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
					<li class="isactive"><a href="pms_inbox.php"><?php echo $lang_pms['Inbox'] ?></a></li>
					<li><a href="pms_send.php"><?php echo $lang_pms['Write message'] ?></a></li>
					<li><a href="pms_sending_lists.php"><?php echo $lang_pms['Sending lists'] ?></a></li>
					<li><a href="pms_contacts.php"><?php echo $lang_pms['Contacts'] ?></a></li>
				</ul>
			</div>
		</div>
	</div>
	<div class="block" id="block">
		<div class="pagepost">
			<p class="pagelink conl"><span class="pages-label"><?php echo $lang_common['Pages'].' '.paginate($num_pages, $page, 'pms_view.php?tid='.$tid.'&amp;mid='.$mid)  ?></span></p>	
			<p class="postlink actions conr"><?php echo $reply_link ?></p>
		</div>

<?php
while ($cur_post = $db->fetch_assoc($result))
{	
	$post_count++;
	$user_avatar = '';
	$user_info = array();
	$user_contacts = array();
	$post_actions = array();
	$is_online = '';
	$signature = '';
	
	// If the poster is a registered user
	if ($cur_post['id'])
	{
		if ($pun_user['g_view_users'] == '1')
			$username = '<a href="profile.php?id='.$cur_post['sender_id'].'">'.pun_htmlspecialchars($cur_post['sender']).'</a>';
		else
			$username = pun_htmlspecialchars($cur_post['sender']);
			
		$user_title = get_title($cur_post);

		if ($pun_config['o_censoring'] == '1')
			$user_title = censor_words($user_title);

		// Format the online indicator
		$is_online = ($cur_post['is_online'] == $cur_post['sender_id']) ? '<strong>'.$lang_topic['Online'].'</strong>' : '<span>'.$lang_topic['Offline'].'</span>';

		if ($pun_config['o_avatars'] == '1' && $pun_user['show_avatars'] != '0')
		{
			if (isset($user_avatar_cache[$cur_post['sender_id']]))
				$user_avatar = $user_avatar_cache[$cur_post['sender_id']];
			else
				$user_avatar = $user_avatar_cache[$cur_post['sender_id']] = generate_avatar_markup($cur_post['sender_id']);
		}

		// We only show location, register date, post count and the contact links if "Show user info" is enabled
		if ($pun_config['o_show_user_info'] == '1')
		{
			if ($cur_post['location'] != '')
			{
				if ($pun_config['o_censoring'] == '1')
					$cur_post['location'] = censor_words($cur_post['location']);

				$user_info[] = '<dd><span>'.$lang_topic['From'].' '.pun_htmlspecialchars($cur_post['location']).'</span></dd>';
			}

			$user_info[] = '<dd><span>'.$lang_topic['Registered'].' '.format_time($cur_post['registered'], true).'</span></dd>';

			if ($pun_config['o_show_post_count'] == '1' || $pun_user['is_admmod'])
				$user_info[] = '<dd><span>'.$lang_topic['Posts'].' '.forum_number_format($cur_post['num_posts']).'</span></dd>';

			// Now let's deal with the contact links (Email and URL)
			if ((($cur_post['email_setting'] == '0' && !$pun_user['is_guest']) || $pun_user['is_admmod']) && $pun_user['g_send_email'] == '1')
				$user_contacts[] = '<span class="email"><a href="mailto:'.$cur_post['email'].'">'.$lang_common['Email'].'</a></span>';
			else if ($cur_post['email_setting'] == '1' && !$pun_user['is_guest'] && $pun_user['g_send_email'] == '1')
				$user_contacts[] = '<span class="email"><a href="misc.php?email='.$cur_post['sender_id'].'">'.$lang_common['Email'].'</a></span>';
				
			if ($pun_config['o_pms_enabled'] == '1' && !$pun_user['is_guest'] && $pun_user['g_pm'] == '1' && $pun_user['use_pm'] == '1' && $cur_post['use_pm'] == '1')
			{
				$pid = isset($cur_post['sender_id']) ? $cur_post['sender_id'] : $cur_post['sender_id'];
				$user_contacts[] = '<span class="email"><a href="pms_send.php?uid='.$pid.'">'.$lang_pms['PM'].'</a></span>';
			}

			if ($cur_post['url'] != '')
				$user_contacts[] = '<span class="website"><a href="'.pun_htmlspecialchars($cur_post['url']).'">'.$lang_topic['Website'].'</a></span>';
				
		}

		if ($pun_user['is_admmod'])
		{
			$user_info[] = '<dd><span><a href="moderate.php?get_host='.$cur_post['sender_ip'].'" title="'.$cur_post['sender_ip'].'">'.$lang_topic['IP address logged'].'</a></span></dd>';

			if ($cur_post['admin_note'] != '')
				$user_info[] = '<dd><span>'.$lang_topic['Note'].' <strong>'.pun_htmlspecialchars($cur_post['admin_note']).'</strong></span></dd>';
		}
	}
	// If the poster is a guest (or a user that has been deleted)
	else
	{
		$username = pun_htmlspecialchars($cur_post['username']);
		$user_title = get_title($cur_post);

		if ($pun_user['is_admmod'])
			$user_info[] = '<dd><span><a href="moderate.php?get_host='.$cur_post['sender_id'].'" title="'.$cur_post['sender_ip'].'">'.$lang_topic['IP address logged'].'</a></span></dd>';

		if ($pun_config['o_show_user_info'] == '1' && $cur_post['poster_email'] != '' && !$pun_user['is_guest'] && $pun_user['g_send_email'] == '1')
			$user_contacts[] = '<span class="email"><a href="mailto:'.$cur_post['poster_email'].'">'.$lang_common['Email'].'</a></span>';
	}
	
	$username_quickreply = pun_htmlspecialchars($cur_post['username']);

		// Generation post action array (reply, delete etc.)
		if ($pun_user['id'] == $cur_post['sender_id'] || $pun_user['is_admmod'])
		{
			$post_actions[] = '<li class="postdelete"><span><a href="pms_view.php?action=delete&amp;mid='.$cur_post['mid'].'&amp;tid='.$cur_post['shared_id'].'">'.$lang_topic['Delete'].'</a></span></li>';
			$post_actions[] = '<li class="postedit"><span><a href="pms_send.php?edit='.$cur_post['mid'].'&amp;tid='.$cur_post['shared_id'].'">'.$lang_topic['Edit'].'</a></span></li>';
		}
		$post_actions[] = '<li class="postquote"><span><a href="pms_send.php?reply='.$cur_post['shared_id'].'&amp;quote='.$cur_post['mid'].'">'.$lang_topic['Quote'].'</a></span></li>';

	// Perform the main parsing of the message (BBCode, smilies, censor words etc)
	$cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

	// Do signature parsing/caching
	if ($pun_config['o_signatures'] == '1' && $cur_post['signature'] != '' && $pun_user['show_sig'] != '0')
	{
		if (isset($signature_cache[$cur_post['id']]))
			$signature = $signature_cache[$cur_post['id']];
		else
		{
			$signature = parse_signature($cur_post['signature']);
			$signature_cache[$cur_post['id']] = $signature;
		}
	}
?>

<div id="p<?php echo $cur_post['mid'] ?>" class="blockpost<?php echo ($post_count % 2 == 0) ? ' roweven' : ' rowodd'; if ($post_count == 1) echo ' blockpost1'; ?>">
	<h2><span><span class="conr">#<?php echo ($start_from + $post_count) ?> </span><a href="pms_view.php?tid=<?php echo $tid.'&amp;mid='.$cur_post['mid'].'&amp;pid='.$cur_post['mid'].'#p'.$cur_post['mid'] ?>"><?php echo format_time($cur_post['posted']) ?></a></span></h2>
	<div class="box">
		<div class="inbox">
			<div class="postbody">
				<div class="postleft">
					<dl>
						<dt><strong><?php echo $username ?></strong></dt>
						<dd class="usertitle"><strong><?php echo $user_title ?></strong></dd>
<?php if ($user_avatar != '') echo "\t\t\t\t\t\t".'<dd class="postavatar">'.$user_avatar.'</dd>'."\n"; ?>
<?php if (count($user_info)) echo "\t\t\t\t\t\t".implode("\n\t\t\t\t\t\t", $user_info)."\n"; ?>
<?php if (count($user_contacts)) echo "\t\t\t\t\t\t".'<dd class="usercontacts">'.implode(' ', $user_contacts).'</dd>'."\n"; ?>
					</dl>
				</div>
				<div class="postright">
					<div class="postmsg">
						<?php echo $cur_post['message']."\n" ?>
					</div>
<?php if ($signature != '') echo "\t\t\t\t\t".'<div class="postsignature postmsg"><hr />'.$signature.'</div>'."\n"; ?>
				</div>
			</div>
		</div>
	<div class="inbox">
			<div class="postfoot clearb">
				<div class="postfootleft"><?php if ($cur_post['sender_id'] > '1' || $cur_post['id'] > '1') echo '<p>'.$is_online.'</p>'; ?></div>
<?php if (count($post_actions)) echo "\t\t\t\t".'<div class="postfootright">'."\n\t\t\t\t\t".'<ul>'."\n\t\t\t\t\t\t".implode("\n\t\t\t\t\t\t", $post_actions)."\n\t\t\t\t\t".'</ul>'."\n\t\t\t\t".'</div>'."\n" ?>
			</div>
		</div>
	</div>
</div>
<?php	
}
?>
		<div class="pagepost">
			<p class="pagelink conl"><span class="pages-label"><?php echo $lang_common['Pages'].' '.paginate($num_pages, $page, 'pms_view.php?tid='.$tid.'&amp;mid='.$mid)  ?></span></p>	
			<p class="postlink actions conr"><?php echo $reply_link ?></p>
			<div class="clearer"></div>
		</div>
</div>
<?php
// Display quick post if enabled
if (!empty($reply_link) && $quickpost)
{
?>
<div id="quickpost" class="blockform">
	<h2><span><?php echo $lang_topic['Quick post'] ?></span></h2>
	<div class="box">
		<form method="post" id="post" action="pms_send.php?reply=<?php echo $tid ?>" onsubmit="return process_form(this)">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_common['Write message legend'] ?></legend>
					<div class="infldset txtarea">
						<input type="hidden" name="reply" value="<?php echo $tid ?>" />
						<input type="hidden" name="form_sent" value="1" />
						<input type="hidden" name="form_user" value="<?php echo pun_htmlspecialchars($pun_user['username']) ?>" />
						<input type="hidden" name="req_subject" value="<?php echo pun_htmlspecialchars($p_subject) ?>" />
						<input type="hidden" name="p_username" value="<?php echo pun_htmlspecialchars($r_usernames) ?>" />
						<label><textarea name="req_message" rows="7" cols="75" tabindex="1"></textarea></label>
						<ul class="bblinks">
							<li><span><a href="help.php#bbcode" onclick="window.open(this.href); return false;"><?php echo $lang_common['BBCode'] ?></a> <?php echo ($pun_config['p_message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
							<li><span><a href="help.php#img" onclick="window.open(this.href); return false;"><?php echo $lang_common['img tag'] ?></a> <?php echo ($pun_config['p_message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
							<li><span><a href="help.php#smilies" onclick="window.open(this.href); return false;"><?php echo $lang_common['Smilies'] ?></a> <?php echo ($pun_config['o_smilies'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></span></li>
						</ul>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="submit" tabindex="2" value="<?php echo $lang_common['Submit'] ?>" accesskey="s" /><input type="submit" name="preview" tabindex="2" value="<?php echo $lang_topic['Preview'] ?>" accesskey="s" /></p>
		</form>
	</div>
</div>
<?php
}
?>
	<div class="clearer"></div>
</div>
<?php
require PUN_ROOT.'footer.php';
