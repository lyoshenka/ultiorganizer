<?php 
function logo() {
	return "";
}

function pageHeader() {
	global $styles_prefix;
	global $include_prefix;
	if (!isset($styles_prefix)) {
		$styles_prefix = $include_prefix;
	}
	return "<a href='http://www.wfdf.org' class='header_text'><img class='header_logo' style='width:550px;height:50px' src='".$styles_prefix."cust/wfdf/logo.jpg' alt='WFDF'/></a><br/>\n";
}

?>