<?php
/**
 * Copyright (C)2010-2014 adaur
 * Another Private Messaging System v3.0.9
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

// Page ?
$page = (!isset($_REQUEST['p']) || $_REQUEST['p'] <= '1') ? '1' : intval($_REQUEST['p']);

// Action ?
$action = ((isset($_REQUEST['action']) && ($_REQUEST['action'] == 'delete_multiple' || $_REQUEST['action'] == 'markread' || $_REQUEST['action'] == 'markunread')) ? $_REQUEST['action'] : '');


if ($action != '')
{	
	// Make sure they got here from the site
	confirm_referrer('pms_inbox.php');
	
	// Mark as read multiple posts
	if ($action == 'markread')
	{
		if (empty($_POST['selected_messages']))
			message($lang_pms['Must select']);
			
		$idlist = array_values($_POST['selected_messages']);
		$idlist = array_map('intval', $idlist);
		$idlist = implode(',', array_values($idlist));
		
		$db->query('UPDATE '.$db->prefix.'messages SET showed=1 WHERE shared_id IN ('.$idlist.') AND owner=\''.$pun_user['id'].'\' AND show_message=1') or error('Unable to update the status of the messages', __FILE__, __LINE__, $db->error());
		redirect('pms_inbox.php', $lang_pms['Read redirect']);
	}
	// Mark as unread multiple posts
	elseif ($action == 'markunread')
	{
		if (empty($_POST['selected_messages']))
			message($lang_pms['Must select']);
			
		$idlist = array_values($_POST['selected_messages']);
		$idlist = array_map('intval', $idlist);
		$idlist = implode(',', array_values($idlist));
		
		$db->query('UPDATE '.$db->prefix.'messages SET showed=0 WHERE shared_id IN ('.$idlist.') AND owner=\''.$pun_user['id'].'\' AND show_message=1') or error('Unable to update the status of the messages', __FILE__, __LINE__, $db->error());
		redirect('pms_inbox.php', $lang_pms['Unread redirect']);
	}
	// Delete multiple posts
	elseif ($action == 'delete_multiple')
	{
		if (isset($_POST['delete_multiple_comply']))
		{
			$idlist = explode(',', $_POST['messages']);
			$idlist = array_map('intval', $idlist);
			$idlist = implode(',', array_values($idlist));
			$number = explode(',', $_POST['messages']);
			$number = array_map('intval', $number);

			$db->query('DELETE FROM '.$db->prefix.'messages WHERE shared_id IN ('.$idlist.') AND owner=\''.$pun_user['id'].'\'') or error('Unable to delete the messages', __FILE__, __LINE__, $db->error());
			$db->query('UPDATE '.$db->prefix.'users SET num_pms=num_pms-'.count($number).' WHERE id='.$pun_user['id']) or error('Unable to update user', __FILE__, __LINE__, $db->error());
		}
		else
		{
			if (empty($_POST['selected_messages']))
				message($lang_pms['Must select']);
			
			$idlist = array_values($_POST['selected_messages']);
			$idlist = array_map('intval', $idlist);
			$idlist = implode(',', array_values($idlist));
			
			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_pms['Multidelete'], $lang_pms['Private Messages']);
			define('PUN_ACTIVE_PAGE', 'pm');
			require PUN_ROOT.'header.php';
			
			// If you're not the owner of the message, you can't delete it.
			$result = $db->query('SELECT DISTINCT owner FROM '.$db->prefix.'messages WHERE shared_id IN ('.$idlist.')') or error('Unable to delete the message', __FILE__, __LINE__, $db->error());
			$owner = array();
			while ($cur_mess_delete = $db->fetch_assoc($result))
				$owner[] = $cur_mess_delete['owner'];
			
			if(!in_array($pun_user['id'], $owner) && !$pun_user['is_admmod'])
				message($lang_common['No permission']);
	?>
<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="pms_inbox.php"><?php echo $lang_pms['Private Messages'] ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_pms['Multidelete'] ?></strong></li>
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
			<form method="post" action="pms_inbox.php">
				<input type="hidden" name="action" value="delete_multiple" />
				<input type="hidden" name="messages" value="<?php echo $idlist ?>" />
				<input type="hidden" name="delete_multiple_comply" value="1" />
				<div class="inform">
				<div class="forminfo">
					<p><?php echo $lang_pms['Delete messages comply'] ?></p>
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

// Get message count for this box
$result = $db->query("SELECT COUNT(*) FROM ".$db->prefix."messages WHERE show_message=1 AND owner='".$pun_user['id']."'") or error("Unable to count the messages", __FILE__, __LINE__, $db->error());
list($num_messages) = $db->fetch_row($result);

// What page are we on ?
$num_pages = ceil($num_messages/$pun_config['o_pms_mess_per_page']);
if ($page > $num_pages) $page = 1;
$start_from = intval($pun_config['o_pms_mess_per_page'])*($page-1);
$limit = $start_from.','.$pun_config['o_pms_mess_per_page'];


// Start building page
$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_pms['Private Messages'], $lang_pms['Inbox']);

define('PUN_ACTIVE_PAGE', 'pm');
require PUN_ROOT.'header.php';
?>
<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="pms_inbox.php"><?php echo $lang_pms['Private Messages'] ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_pms['Inbox'] ?></strong></li>
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
	<div class="block">
		<div class="pagepost">
			<p class="pagelink conl"><span class="pages-label"><?php echo $lang_common['Pages'].' '.paginate($num_pages, $page, 'pms_inbox.php?') ?></span></p>
			<p class="postlink"><span><a href="pms_send.php"><?php echo $lang_pms['Write message'] ?></a></span></p>
		</div>
	<form method="post" action="pms_inbox.php">
	<fieldset>
	<input type="hidden" name="box" value="0" />
	<div id="vf" class="blocktable">
		<div class="box">
				<div class="inbox">
			<table cellspacing="0">
			<thead>
				<tr>
					<th class="tcl"><?php echo $lang_pms['Messages'] ?></th>
					<th class="tc2"><?php echo $lang_pms['Sender'] ?></th>
					<th class="tc2"><?php echo $lang_pms['Receiver'] ?></th>
					<th class="tcr"><?php echo $lang_common['Last post'] ?></th>
					<th class="tcmod"><label style="display: inline; white-space: nowrap;"><?php echo $lang_pms['Select'] ?> <input type="checkbox" id="checkAllButon" value="1" onclick="checkAll('selected_messages[]','checkAllButon');" /></label></th>
				</tr>
			</thead>
			<tbody>
<?php
// Fetch messages
$result = $db->query("SELECT * FROM ".$db->prefix."messages WHERE show_message=1 AND owner='".$pun_user['id']."' ORDER BY last_post DESC LIMIT ".$limit) or error("Unable to find the list of the pms.", __FILE__, __LINE__, $db->error()); 

// If there are messages in this folder.
if ($db->num_rows($result))
{
	while ($cur_mess = $db->fetch_assoc($result))
	{
		$item_status = 'roweven';
		if ($cur_mess['showed'] == '0')
		{
			$item_status .= ' inew';
			$icon_type = 'icon icon-new';
			$subject = '<a href="pms_view.php?tid='.$cur_mess['shared_id'].'&amp;mid='.$cur_mess['id'].'">'.
					   '<strong>'.pun_htmlspecialchars($cur_mess['subject']).'</strong>'.
					   '</a>';
		}
		else
		{
			$icon_type = 'icon';
			$subject = '<a href="pms_view.php?tid='.$cur_mess['shared_id'].'&amp;mid='.$cur_mess['id'].'">'.
					   pun_htmlspecialchars($cur_mess['subject']).
					   '</a>';
		}
		
		$last_post = '<a href="pms_view.php?tid='.$cur_mess['shared_id'].'&amp;mid='.$cur_mess['id'].'&amp;pid='.$cur_mess['last_post_id'].'#p'.$cur_mess['last_post_id'].'">'.format_time($cur_mess['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_mess['last_poster']).'</span>';
?>
	<tr class="<?php echo $item_status ?>">
		<td class="tcl">
				<div class="<?php echo $icon_type ?>"></div>
				<div class="tclcon"><?php echo $subject ?></div>
		</td>
		<td class="tc2">
		<?php
		if ($pun_user['g_view_users'] == '1')
			echo '<a href="profile.php?id='.$cur_mess['sender_id'].'">'.pun_htmlspecialchars($cur_mess['sender']).'</a>';
		else
			echo pun_htmlspecialchars($cur_mess['sender']);
		?>
		</td>
		<td class="tc2">
		<?php
			if ($pun_user['g_view_users'] == '1')
			{
				$ids_list = explode(', ', $cur_mess['receiver_id']);
				$sender_list = explode(', ', $cur_mess['receiver']);
				$sender_list = str_replace('Deleted', $lang_pms['Deleted'], $sender_list);
				
				for($i = '0'; $i < count($ids_list); $i++){
				echo '<a href="profile.php?id='.$ids_list[$i].'">'.pun_htmlspecialchars($sender_list[$i]).'</a>';
				
				if($ids_list[$i][count($ids_list[$i])-'1'])
					echo'<br />';
				} 
			}
			else
				echo pun_htmlspecialchars($cur_mess['receiver']);
		?>
		</td>
		<td class="tcr"><?php echo $last_post ?></td>
		<td class="tcmod"><input type="checkbox" name="selected_messages[]" value="<?php echo $cur_mess['shared_id'] ?>" /></td>
	</tr>
<?php
	}
}
else
	echo "\t".'<tr><td class="puncon1" colspan="4">'.$lang_pms['No messages'].'</td></tr>'."\n";
?>
			</tbody>
			</table>
		</div>
	</div>
	</div>
<div class="pagepost">
				<p class="pagelink conl"><span class="pages-label"><?php echo $lang_common['Pages'].' '.paginate($num_pages, $page, 'pms_inbox.php?') ?></span></p>
				<p class="postlink conr">
					<label style="display:inline">
					<?php echo $lang_pms['For select'] ?> 
					<select name="action">
						<option value="markread"><?php echo $lang_pms['Mark as read select'] ?></option>
						<option value="markunread"><?php echo $lang_pms['Mark as unread select'] ?></option>
						<option value="delete_multiple"><?php echo $lang_pms['Delete'] ?></option>
					</select>
					<input type="submit" value="<?php echo $lang_pms['OK'] ?>" />
					</label>
				</p>
</div>
	<div class="clearer"></div>
</fieldset>
</form>
</div>
</div>

<?php
require PUN_ROOT.'footer.php';