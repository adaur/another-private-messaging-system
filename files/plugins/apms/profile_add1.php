<?php
		$db->query('DELETE FROM '.$db->prefix.'messages WHERE owner='.$id) or error('Unable to delete user\'s messages', __FILE__, __LINE__, $db->error());
		$db->query('DELETE FROM '.$db->prefix.'messages WHERE sender_id='.$id) or error('Unable to delete user\'s messages', __FILE__, __LINE__, $db->error());
		$db->query('DELETE FROM '.$db->prefix.'contacts WHERE user_id='.$id) or error('Unable to delete user\'s contacts', __FILE__, __LINE__, $db->error());
		$db->query('DELETE FROM '.$db->prefix.'contacts WHERE contact_id='.$id) or error('Unable to delete user\'s contacts', __FILE__, __LINE__, $db->error());
?>