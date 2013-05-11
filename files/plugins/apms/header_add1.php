<?php
	if ($pun_user['g_pm'] == '1' && $pun_user['use_pm'] == '1' && $pun_config['o_pms_enabled'] == '1')
	{
		// Boxes status
		$pm_boxes_full = ($pun_user['num_pms'] >= $pun_user['g_pm_limit']) ? true : false;
		$pm_boxes_empty = ($pun_user['num_pms'] <= '0') ? true : false;
		if ($pun_user['g_pm_limit'] != '0' && !$pun_user['is_admmod'])
		{	
			if ($pm_boxes_empty)
				$page_statusinfo[] = '<li><span>'.$lang_pms['Empty boxes'].'</span></li>';
			elseif ($pm_boxes_full)
				$page_statusinfo[] = '<li><span><a href="pms_inbox.php"><strong>'.$lang_pms['Full boxes'].'</strong></a></span></li>';
			else
			{
				$per_cent_box = ceil($pun_user['num_pms'] / $pun_user['g_pm_limit'] * '100');	
				$page_statusinfo[] = '<li><span>'.sprintf($lang_pms['Full to'],$per_cent_box.'%').'</span> <div id="mp_bar_ext"><div id="mp_bar_int" style="width:'.$per_cent_box.'px;"><!-- --></div></div></li>';
			}
		}
		
		if ($num_new_pm > 0)
			$page_statusinfo[] = '<li><span><a href="pms_inbox.php"><strong>'.($num_new_pm == '1' ? $lang_pms['New message'] : sprintf($lang_pms['New messages'],$num_new_pm)).'</strong></a></span></li>';		
	}