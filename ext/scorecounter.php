<?php
include_once 'localization.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='fi' lang='fi'>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Pragma" content="no-cache"/>
<meta http-equiv="Expires" content="-1"/>
<?php
include_once '../lib/common.functions.php';
include_once '../lib/season.functions.php';
include_once '../lib/series.functions.php';
include_once '../lib/team.functions.php';
include_once '../lib/timetable.functions.php';

echo "<title>"._("Ultiorganizer Score Counter")."</title>";
?>
</head>
<body>
<?php 

if(!empty($_GET["Season"])){
	$season = $_GET["Season"];
}
$lenght = 6;
if(!empty($_GET["Numbers"])){
	$lenght = intval($_GET["Numbers"]);
}
echo "<table><tr>";
$query = "SELECT (SUM(game.homescore) + SUM(game.visitorscore)) AS scores FROM
		uo_game game
		LEFT JOIN uo_pool pool ON(pool.pool_id=game.pool)
		LEFT JOIN uo_series ser ON(pool.series=ser.series_id)";

if(!empty($season)){
	$query .= sprintf("WHERE ser.season='%s'",
		mysql_real_escape_string($season));
}
		
$scores = DBQueryToValue($query);

$chars = str_split($scores);
for($i=count($chars);$i<$lenght;$i++){
echo "<td>0</td>";
}
foreach($chars as $char){
	echo "<td class='center' style='width:10px'>$char</td>";
}

echo "</tr></table>";


CloseConnection();
?>
</body>
</html>
