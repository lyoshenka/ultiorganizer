<?php
include_once 'localization.php';
include_once '../lib/game.functions.php';

$season = $_GET['Season'];
$encoding = 'UTF-8';
$separator = ',';

if(!empty($_GET['Enc'])){
	$encoding = $_GET['Enc'];
}
if(!empty($_GET['Sep'])){
	$separator = $_GET['Sep'];
}

$data = ResultsToCsv($season,$separator);
$data = mb_convert_encoding($data, $encoding, 'UTF-8'); 
CloseConnection();
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Length: " . strlen($data));
header("Content-type: text/x-csv");
header("Content-Disposition: attachment; filename=results.csv");
echo $data;

?>
