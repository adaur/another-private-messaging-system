/*
 * Copyright (C)2010-2013 adaur
 * Another Private Messaging System v3.0.5
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
//<![CDATA[
$("#js_enabled").show();
$(document).ready(function(){
$('#sending_list').show();

$('select[name=sending_list]').change(function(){ 
	var message_rap = $('select[name=sending_list]').val();
	$('input#p_username').val(message_rap);
});
});
//]]>