<?php
		$db->query('UPDATE '.$db->prefix.'messages SET sender=\''.$db->escape($form['username']).'\' WHERE sender=\''.$db->escape($old_username).'\'') or error('Unable to update private messages', __FILE__, __LINE__, $db->error());
		$db->query('UPDATE '.$db->prefix.'messages SET last_poster=\''.$db->escape($form['username']).'\' WHERE last_poster=\''.$db->escape($old_username).'\'') or error('Unable to update private messages', __FILE__, __LINE__, $db->error());
		$db->query('UPDATE '.$db->prefix.'contacts SET contact_name=\''.$db->escape($form['username']).'\' WHERE contact_name=\''.$db->escape($old_username).'\'') or error('Unable to update contacts', __FILE__, __LINE__, $db->error());
		$db->query('UPDATE '.$db->prefix.'messages SET receiver=REPLACE(receiver,\''.$db->escape($old_username).'\',\''.$db->escape($form['username']).'\') WHERE receiver LIKE \'%'.$db->escape($old_username).'%\'') or error('Unable to update private messages', __FILE__, __LINE__, $db->error());
?>