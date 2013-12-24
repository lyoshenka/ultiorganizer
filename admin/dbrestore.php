<?php
include_once 'menufunctions.php';
include_once 'lib/club.functions.php';
include_once 'lib/reservation.functions.php';
$html = "";
if (isset($_POST['restore']) && isSuperAdmin()){
	if(is_uploaded_file($_FILES['restorefile']['tmp_name'])) {
		
		$templine = '';
		$lines = file($_FILES['restorefile']['tmp_name']);
		set_time_limit(300);
		
		foreach ($lines as $line){
			// Skip it if it's a comment
			if (substr($line, 0, 2) == '--' || $line == '')
				continue;
 
			$templine .= $line;
			if (substr(trim($line), -1, 1) == ';'){
				mysql_query($templine) or $html .= "<p>".$templine.": ". mysql_error() ."</p>";
				$templine = '';
			}
		}
		unlink($_FILES['restorefile']['tmp_name']);
		unset($_SESSION['dbversion']);
		$html .= "<p>"._("Restore")."</p>";
	}
	
	//disable facebook and twitter updates after restore to avoid false postings 
	//(f.ex. if restored database is used for testing purpose)
	$settings = array();

	$setting = array();
	$setting['name']="FacebookEnabled";
	$setting['value']="false";
	$settings[] = $setting;
	$setting['name']="TwitterEnabled";
	$setting['value']="false";
	$settings[] = $setting;
	
	SetServerConf($settings);
}

//common page
$title = _("Database backup");
$LAYOUT_ID = DBRESTORE;
pageTopHeadOpen($title);
include 'script/common.js.inc';
pageTopHeadClose($title, false);
leftMenu($LAYOUT_ID);
contentStart();
if(isSuperAdmin()){
	ini_set("post_max_size", "30M");
	ini_set("upload_max_filesize", "30M");
	ini_set("memory_limit", -1 );

	$html .= "<form method='post' enctype='multipart/form-data' action='?view=admin/dbrestore'>\n";
	
	$html .= "<p><span class='profileheader'>"._("Select backup to restore").": </span></p>\n";
	
	$html .= "<p><input class='input' type='file' size='80' name='restorefile'/>";
	$html .= "<input type='hidden' name='MAX_FILE_SIZE' value='100000000'/></p>";
	$html .= "<p><input class='button' type='submit' name='restore' value='"._("Restore")."'/>";	
	$html .= "<input class='button' type='button' name='takaisin'  value='"._("Return")."' onclick=\"window.location.href='?view=admin/dbadmin'\"/></p>";
	$html .= "</form>";

}else{
	$html .= "<p>"._("User credentials does not match")."</p>\n";
}
echo $html;

contentEnd();
pageEnd();
?>