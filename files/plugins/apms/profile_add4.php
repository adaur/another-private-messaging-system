<?php
if ($user['use_pm'] == '1' && $user['g_pm'] == '1')
				$email_field .= '<p><a href="pms_send.php?uid='.$id.'">'.$lang_pms['Quick message'].'</a> - <a href="pms_contacts.php?add='.$id.'">'.$lang_pms['Add to contacts'].'</a></p>'."\n";
?>
