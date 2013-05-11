<?php
if (file_exists(PUN_ROOT.'style/'.$pun_user['style'].'/pms.css'))
	echo '<link rel="stylesheet" type="text/css" href="style/'.$pun_user['style'].'/pms.css" />';
else
	echo '<link rel="stylesheet" type="text/css" href="style/imports/pms.css" />';
?>