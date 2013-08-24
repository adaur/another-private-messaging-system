<?php
if ($pun_config['o_pms_enabled'] == '1' && !$pun_user['is_guest'] && $pun_user['g_pm'] == '1' && $pun_user['use_pm'] == '1' && $user['use_pm'] == '1' && $user['g_pm'] == '1')
    {
        $pm_send_field = '<a href="pms_send.php?uid='.$id.'">'.$lang_pms['Quick message'].'</a>';
        $user_personal[] = '<dt>'.$lang_pms['PM'].'</dt>';
        $user_personal[] = '<dd><span class="email">'.$pm_send_field.'</span></dd>';

        $pm_add_field = '<a href="pms_contacts.php?add='.$id.'">'.$lang_pms['Add to contacts'].'</a>';
        $user_personal[] = '<dt>'.$lang_pms['Contacts'].'</dt>';
        $user_personal[] = '<dd>'.$pm_add_field.'</span></dd>';
    }
?>
