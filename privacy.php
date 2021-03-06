<?php
$html = "";
global $include_prefix;
$print=0;
if(!empty($_GET["Print"])) {
	$print = intval($_GET["Print"]);
}
//common page
$title = _("Privacy Policy");
$LAYOUT_ID = PRIVACY;
pageTop($title, $print);
leftMenu($LAYOUT_ID, $print);
contentStart();

$htmlfile = 'locale/'.getSessionLocale().'/LC_MESSAGES/privacy.html';

if (is_file('cust/'.CUSTOMIZATIONS.'/'.$htmlfile)) {
  $html .= file_get_contents('cust/'.CUSTOMIZATIONS.'/'. $htmlfile);
}else{
  $html .= file_get_contents($htmlfile);
}


$backurl = utf8entities($_SERVER['HTTP_REFERER']);

$querystring = $_SERVER['QUERY_STRING'];
$querystring = preg_replace("/&Print=[0-1]/","",$querystring);
if($print){
	$html .= "<hr/><div style='text-align:right'><a href='?".utf8entities($querystring)."'>"._("Return")."</a></div>";
}else{
	$html .= "<hr/><div style='text-align:left;float:left;clear:left;'><a href='".utf8entities($backurl)."'>"._("Return")."</a></div>";
	$html .= "<div style='text-align:right'><a href='?".utf8entities($querystring)."&amp;Print=1'>"._("Printable version")."</a></div>";
	
}
echo $html;

contentEnd();
pageEnd();
?>