<?php
if ($pun_config['o_pms_enabled'] == '1' && !$pun_user['is_guest'] && $pun_user['g_pm'] == '1' && $pun_user['use_pm'] == '1' && $cur_post['use_pm'] == '1')
			{
				$pid = isset($cur_post['poster_id']) ? $cur_post['poster_id'] : $cur_post['id'];
				$user_contacts[] = '<span class="email"><a href="pms_send.php?uid='.$pid.'">'.$lang_pms['PM'].'</a></span>';
			}
?>