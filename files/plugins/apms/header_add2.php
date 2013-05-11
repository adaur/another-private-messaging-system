<?php
$num_new_pm = 0;
if ($pun_config['o_pms_enabled'] == '1' && $pun_user['g_pm'] == '1' && $pun_user['use_pm'] == '1')
{
	// Check for new messages
	$result_messages = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'messages WHERE showed=0 AND show_message=1 AND owner='.$pun_user['id']) or error('Unable to check the availibility of new messages', __FILE__, __LINE__, $db->error());
	$num_new_pm = $db->result($result_messages);
	
	if ($num_new_pm > 0)
		$links[] = '<li id="navpm"'.((PUN_ACTIVE_PAGE == 'pm') ? ' class="isactive"' : '').'><a href="pms_inbox.php">('.$num_new_pm.') '.$lang_pms['PM'].'</a></li>';	
	else
		$links[] = '<li id="navpm"'.((PUN_ACTIVE_PAGE == 'pm') ? ' class="isactive"' : '').'><a href="pms_inbox.php">'.$lang_pms['PM'].'</a></li>';	
}
?>