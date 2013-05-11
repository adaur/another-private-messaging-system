<?php
if (file_exists(PUN_ROOT.'lang/'.$pun_user['language'].'/pms.php'))
       require PUN_ROOT.'lang/'.$pun_user['language'].'/pms.php';
else
       require PUN_ROOT.'lang/English/pms.php';
?>