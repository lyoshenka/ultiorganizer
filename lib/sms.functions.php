<?php

function GetAllSMS(){
	$query = sprintf("SELECT * FROM uo_sms");
	$result = mysql_query($query);
	if (!$result) { die('Invalid query: ' . mysql_error()); }
	
	return $result;
}

function SendSMS($sms) {
	// expects an array containing 'msg','to1','to2' etc.
	$query = sprintf("INSERT INTO uo_sms 
		(msg, to1, to2, to3, to4, to5) 
		VALUES	('%s','%s',",
	mysql_real_escape_string($sms['msg']),
	mysql_real_escape_string($sms['to1']));
	
	if ($sms['to2']=="") {
		$query .= "NULL,";
	} else {
		$query .= mysql_real_escape_string($sms['to2']).",";
	}
	if ($sms['to3']=="") {
		$query .= "NULL,";
	} else {
		$query .= mysql_real_escape_string($sms['to3']).",";
	}
	if ($sms['to4']=="") {
		$query .= "NULL,";
	} else {
		$query .= mysql_real_escape_string($sms['to4']).",";
	}
	if ($sms['to5']=="") {
		$query .= "NULL)";
	} else {
		$query .= mysql_real_escape_string($sms['to5']).")";
	}
	
	$result = mysql_query($query);
	if (!$result) { die('Invalid query: ' . mysql_error()); }
	
		
}
	 
?>