<?php
include_once 'lib/database.php';
include_once 'lib/pool.functions.php';
include_once 'lib/reservation.functions.php';
include_once 'lib/location.functions.php';
include_once 'lib/common.functions.php';
include_once 'lib/team.functions.php';
include_once 'lib/game.functions.php';
include_once 'lib/reservation.functions.php';

$LAYOUT_ID = POOLGAMES;

$poolId = $_GET["Pool"];
$season = $_GET["Season"];
$rounds = 1;
$title = utf8entities(U_(PoolSeriesName($poolId)).", ". U_(PoolName($poolId))).": "._("Games");
$info = PoolInfo($poolId);
$usepseudoteams = PseudoTeamsOnly($poolId);
$generatedgames = array();
$nomutual=0;
$html = "";

//common page
pageTopHeadOpen($title);
?>
<script type="text/javascript">
<!--
function setId(id) 
	{
	var input = document.getElementById("hiddenDeleteId");
	input.value = id;
	}
//-->
</script>
<?php
pageTopHeadClose($title);
leftMenu($LAYOUT_ID);
contentStart();

	
//process itself on submit
if(!empty($_POST['remove_x']))
	{
	$id = $_POST['hiddenDeleteId'];
	$ok = true;
		
	//run some test to for safe deletion
	$goals = GameAllGoals($id);
	if(mysql_num_rows($goals))
		{
		$html .= "<p class='warning'>"._("Game has")." ".mysql_num_rows($goals)." "._("goals").". "._("Goals must be removed before removing the team").".</p>";
		$ok = false;
		}	
	if($ok)
		DeleteGame($id);
}elseif(!empty($_POST['swap_x']))
	{
	$id = $_POST['hiddenDeleteId'];
	$goals = GameAllGoals($id);
	if(!mysql_num_rows($goals)){
	  GameChangeHome($id);
	}		
}elseif(!empty($_POST['removemoved']))
	{
	$id = $_POST['hiddenDeleteId'];
	DeleteMovedGame($id, $poolId);
}elseif(!empty($_POST['fakegenerate'])){
	if(!empty($_POST['rounds'])){
		$rounds = $_POST['rounds'];
	}
	$nomutual = isset($_POST["nomutual"]);
	$homeresp = isset($_POST["homeresp"]);
	$fakegames="";
	$generatedgames = GenerateGames($poolId,$rounds,false,$nomutual,$homeresp);
	
	if($info['type']==1){
		foreach($generatedgames as $game){
			if($usepseudoteams){
				$fakegames .= "<p>".TeamPseudoName($game['home'])." - ".TeamPseudoName($game['away'])."</p>";
			}else{
				$fakegames .= "<p>".TeamName($game['home'])." - ".TeamName($game['away'])."</p>";
			}
		}
	}elseif($info['type']==2){
		$generatedpools = GeneratePlayoffPools($poolId, false);
		$fakegames .= "<p><b>".$info['name']."</b></p>";
		foreach($generatedgames as $game){
			if($usepseudoteams){
				$fakegames .= "<p>".TeamPseudoName($game['home'])." - ".TeamPseudoName($game['away'])."</p>";
			}else{
				$fakegames .= "<p>".TeamName($game['home'])." - ".TeamName($game['away'])."</p>";
			}
		}
		foreach($generatedpools as $gpool){
			$fakegames .= "<p><b>".$gpool['name']."</b></p>";
			$generatedgames = GenerateGames($poolId,$rounds,false);
			$fakegames .= "<p>".count($generatedgames)." "._("games").". "._("Previous round winner vs. winners and losers vs. losers")."</p>";
//			debugVar($gpool);
			if($gpool['specialmoves']) { $fakegames .= "<p>playoff layout with moves found, using special moves.</p>"; }
		}
	}elseif($info['type']==3){
		// Swiss-draw: 
		if($generatedgames[0]==false) {
			$fakegames .= "<p>The number of teams in a Swiss-draw pool should be even. Please add or remove a team.</p>";
		}else{
			$generatedpools = GenerateSwissdrawPools($poolId,$rounds,false);
			$fakegames .= "<p><b>".$info['name']."</b></p>";
			foreach($generatedgames as $game){
				if($usepseudoteams){
					$fakegames .= "<p>".TeamPseudoName($game['home'])." - ".TeamPseudoName($game['away'])."</p>";
				}else{
					$fakegames .= "<p>".TeamName($game['home'])." - ".TeamName($game['away'])."</p>";
				}
			}
			if ($rounds>2) {
				$generatedgames = GenerateGames($poolId,$rounds,false);
				$fakegames .="<p><b> and ".($rounds-1)." extra Swissdraw pools with ".count($generatedgames)." games each</b></p>";
			}elseif($rounds=2){
				$generatedgames = GenerateGames($poolId,$rounds,false);
				$fakegames .="<p><b> and ".($rounds-1)." extra Swissdraw pool with ".count($generatedgames)." games each</b></p>";
			}
		}
	}elseif($info['type']==4){
	  foreach($generatedgames as $game){
			if($usepseudoteams){
				$fakegames .= "<p>".TeamPseudoName($game['home'])." - ".TeamPseudoName($game['away'])."</p>";
			}else{
				$fakegames .= "<p>".TeamName($game['home'])." - ".TeamName($game['away'])."</p>";
			}
		}
	}	
}elseif(!empty($_POST['generate'])){
	if(!empty($_POST['rounds'])){
		$rounds = $_POST['rounds'];
	}
	$homeresp = isset($_POST["homeresp"]);
	$nomutual = isset($_POST["nomutual"]);
	$generatedgames=GenerateGames($poolId,$rounds,true,$nomutual, $homeresp);
	
	//in case of playoff pool create all pools and games for playoffs
	if($info['type']==2){
		//generate pools needed to solve standings
		$generatedpools = GeneratePlayoffPools($poolId, true);
	
		//generate games into generated pools
		foreach($generatedpools as $gpool){
			//echo "<p>Generate games for ".$gpool['pool_id']."</p>";
			GenerateGames($gpool['pool_id'],$rounds,true);
		}
	}elseif($info['type']==3){ //in case of Swissdraw, create pools and moves
		if($generatedgames[0]==false) {
			echo "<p>The number of teams in a Swiss-draw pool should be even. Please add or remove a team.</p>";
		}else{		
			//generate pools (with games) and moves 
			$generatedpools = GenerateSwissdrawPools($poolId, $rounds, true);
		}
	}
}elseif(!empty($_POST['addnew'])){
	$home = $_POST['newhome'];
	$away = $_POST['newaway'];
	$homeresp = isset($_POST["homeresp"]);
	PoolAddGame($poolId,$home,$away,$usepseudoteams, $homeresp);
}
	
$html .= "<form method='post' action='?view=admin/poolgames&amp;Season=$season&amp;Pool=$poolId'>";

if(CanGenerateGames($poolId)){
	$html .= "<h2>"._("Creation of pool games")."</h2>\n";

if($info['type']=="1"){
	$html .= "<p>"._("Round Robin -type of pool")."</p>\n";
	$html .= "<p>"._("Game rounds").": <input class='input' size='2' name='rounds' value='$rounds'/></p>\n";
	$html .= "<p>"._("Home team has rights to edit game score sheet").":<input class='input' type='checkbox' name='homeresp'";
	if (isRespTeamHomeTeam()) {
		$html .= "checked='checked'";
	}
	$html .="/></p>";

	if($info['mvgames']==2){
		$html .= "<p>"._("Do not generate mutual games for teams moved from same pool").":<input class='input' type='checkbox' name='nomutual'";
		if ($nomutual) {
			$html .= "checked='checked'";
		}
		$html .="/></p>";
	}
	
}elseif($info['type']=="2"){
	$html .= "<p>"._("Playoff -type of pool")."</p>\n";
	$html .= "<p>"._("best")." <input class='input' size='2' name='rounds' value='$rounds'/> "._("matches")."</p>\n";
	$html .= "<p>"._("Home team has rights to edit game score sheet").":<input class='input' type='checkbox' name='homeresp'";
	if (isRespTeamHomeTeam()) {
		$html .= "checked='checked'";
	}
	$html .="/></p>";
	
}elseif($info['type']=="3"){
	$html .= "<p>"._("Swissdraw pool")."</p>\n";
	$html .= "<p>"._("with")." <input class='input' size='2' name='rounds' value='$rounds'/> "._("rounds")."</p>\n";
	$html .= "<p>"._("Home team has rights to edit game score sheet").":<input class='input' type='checkbox' name='homeresp'";
	if (isRespTeamHomeTeam()) {
		$html .= "checked='checked'";
	}
	$html .="/></p>";	
	
}elseif($info['type']=="4"){
	$html .= "<p>"._("Crossmatch -type of pool")."</p>\n";
	$html .= "<p>"._("best")." <input class='input' size='2' name='rounds' value='$rounds'/> "._("matches")."</p>\n";
	$html .= "<p>"._("Home team has rights to edit game score sheet").":<input class='input' type='checkbox' name='homeresp'";
	if (isRespTeamHomeTeam()) {
		$html .= "checked='checked'";
	}
	$html .="/></p>";	
	
}


$html .= "<p><input type='submit' name='fakegenerate' value='"._("Show games")."'/>";
$html .= "<input type='submit' name='generate' value='"._("Generate all games")."'/></p>";
}else{
$html .= "<p><a href='?view=admin/reservations&amp;Season=$season'>"._("Scheduling and Reservation management")."</a></p>";
}
if(!empty($fakegames)){
	$html .= "<h2>"._("Games to generate")."</h2>\n";
	$html .= $fakegames;
}

$mutualgames=array();

//if mutual games moved, mark games played between teams moved from same pool
if($info['mvgames']==2){
	$allgames = PoolGames($info['pool_id']);
	foreach($allgames as $game){
		$gameinfo = GameInfo($game['game_id']);
		if(!empty($gameinfo['hometeam']) && !empty($gameinfo['visitorteam'])){
				$homepool = PoolGetFromPoolByTeamId($info['pool_id'],$gameinfo['hometeam']);
				$awaypool = PoolGetFromPoolByTeamId($info['pool_id'],$gameinfo['visitorteam']);
		}else{
			$homepool = PoolGetFromPoolBySchedulingId($gameinfo['scheduling_name_home']);
			$awaypool = PoolGetFromPoolBySchedulingId($gameinfo['scheduling_name_visitor']);
		}
		if($homepool==$awaypool){
			$mutualgames[] = $game['game_id'];
		}
	}

}

$reservations = SeasonReservations($season);
$tour = "";
foreach($reservations as $res){
	$games = PoolGames($poolId, $res['id']);
	$location = LocationInfo($res['location']);
	if(count($games)){
		if($tour != $res['reservationgroup']){
			$html .= "<h2>".utf8entities($res['reservationgroup'])."</h2>";
			$tour = $res['reservationgroup'];
		}
		$html .= "<table border='0' cellpadding='4px' width='400px'>\n";
		$html .= "<tr><th colspan='4'>".utf8entities($location['name'])." ";
		$html .= " ". DefWeekDateFormat($res['starttime']) ." ". DefHourFormat($res['starttime'])."-";
		$html .= DefHourFormat($res['endtime']) ."</th>";
		$html .= "<th colspan='5' class='right'><a href='?view=admin/schedule&amp;Reservations=".$res['id']."'>"._("Add games")."</a></th>";	
		$html .= "</tr>";
		
		foreach($games as $row)	{
			if(in_array($row['game_id'],$mutualgames)){
				$html .= "<tr class='highlight'>";
			}else{
				$html .= "<tr>";
			}

			$html .= "<td style='width:10%'>".DefHourFormat($row['time']) ."</td>";
			if($usepseudoteams){
				$html .= "<td style='width:30%'>".utf8entities($row['phometeamname'])."</td>";
				$html .= "<td>-</td>";
				$html .= "<td style='width:30%'>". utf8entities($row['pvisitorteamname']) ."</td>";
			}else{
				$html .= "<td style='width:30%'>".utf8entities($row['hometeamname'])."</td>";
				$html .= "<td>-</td>";
				$html .= "<td style='width:30%'>". utf8entities($row['visitorteamname']) ."</td>";
			}
			$html .= "<td class='center'><a href='?view=admin/editgame&amp;Season=$season&amp;Game=".$row['game_id']."'>"._("edit")."</a></td>";
			$html .= "<td style='width:5%'>". intval($row['homescore']) ."</td><td style='width:2%'>-</td><td style='width:5%'>". intval($row['visitorscore']) ."</td>";
			$html .= "<td class='center'><input class='deletebutton' type='image' src='images/swap.png' alt='<->' name='swap' value='"._("X")."' onclick=\"setId(".$row['game_id'].");\"/></td>";
			$html .= "<td class='center'><input class='deletebutton' type='image' src='images/remove.png' alt='X' name='remove' value='"._("X")."' onclick=\"setId(".$row['game_id'].");\"/></td>";		
			$html .= "</tr>\n";	
			}
		
		$html .= "</table>";
		}
	}


$games = PoolGamesNotScheduled($poolId);
if(count($games)){
	$html .= "<h2>"._("No schedule")."</h2>\n";
	$html .= "<table border='0' cellpadding='4px' width='400px'>\n";

	foreach($games as $row){
		if(in_array($row['game_id'],$mutualgames)){
			$html .= "<tr class='highlight'>";
		}else{
			$html .= "<tr>";
		}
		if($row['hometeam']){
			$html .= "<td style='width:30%'>".utf8entities($row['hometeamname'])."</td>";
		}else{
			$html .= "<td style='width:30%'>".utf8entities(U_($row['phometeamname']))."</td>";
		}
		
		$html .= "<td>-</td>";
		if($row['visitorteam']){
			$html .= "<td style='width:30%'>". utf8entities($row['visitorteamname']) ."</td>";
		}else{
			$html .= "<td style='width:30%'>". utf8entities(U_($row['pvisitorteamname'])) ."</td>";
		}
		$html .= "<td class='center'><a href='?view=admin/editgame&amp;Season=$season&amp;Game=".$row['game_id']."'>"._("edit")."</a></td>";
		$html .= "<td class='center'><input class='deletebutton' type='image' src='images/swap.png' alt='<->' name='swap' value='"._("X")."' onclick=\"setId(".$row['game_id'].");\"/></td>";
		$html .= "<td class='center'><input class='deletebutton' type='image' src='images/remove.png' alt='X' name='remove' value='"._("X")."' onclick=\"setId(".$row['game_id'].");\"/></td>";		
		$html .= "</tr>\n";	
	}
	$html .= "</table>";
}

$games = PoolMovedGames($poolId);
if(count($games)){
	$html .= "<h2>"._("Moved games")."</h2>\n";
	$html .= "<table border='0' cellpadding='2px' width='400px'>\n";
	foreach($games as $row){
		$html .= "<tr>";
		$html .= "<td style='width:30%'>".utf8entities($row['hometeamname'])."</td>";
		$html .= "<td>-</td>";
		$html .= "<td style='width:30%'>". utf8entities($row['visitorteamname']) ."</td>";
		$html .= "<td style='width:5%'>". intval($row['homescore']) ."</td><td style='width:2%'>-</td><td style='width:5%'>". intval($row['visitorscore']) ."</td>";
		$html .= "<td class='center'><a href='?view=admin/editgame&amp;Season=$season&amp;Game=".$row['game_id']."'>"._("edit")."</a></td>";
		$html .= "<td class='center'><input class='deletebutton' type='image' src='images/swap.png' alt='<->' name='swap' value='"._("X")."' onclick=\"setId(".$row['game_id'].");\"/></td>";
		$html .= "<td class='center'><input class='deletebutton' type='image' src='images/remove.png' alt='X' name='removemoved' value='"._("X")."' onclick=\"setId(".$row['game_id'].");\"/></td>";		
		$html .= "</tr>\n";	
		}
	$html .= "</table>";
	}

if(!$info['played']){
	$html .= "<h2>"._("Creation of single game")."</h2>\n";
	$html .= "<p>"._("Home team has rights to edit game score sheet").":<input class='input' type='checkbox' name='homeresp'";
	if (isRespTeamHomeTeam()) {
		$html .= "checked='checked'";
	}
	$html .="/></p>";	
	
	$html .= "<table border='0' cellpadding='4px' width='400px'>\n";
	$html .= "<tr>";
	$html .= "<td style='width:30%'><select class='dropdown' style='width:100%' name='newhome'>";
	$pseudoteams = false;
	$teams = PoolTeams($poolId);
	if(count($teams)==0){
		$teams = PoolSchedulingTeams($poolId);
		$pseudoteams = true;
	}
	$teamlist = "";
	foreach($teams as $row){
		if($pseudoteams){
			$teamlist .= "<option class='dropdown' value='". $row['scheduling_id'] . "'>". $row['name'] ."</option>";
		}else{
			$teamlist .= "<option class='dropdown' value='". $row['team_id'] . "'>". $row['name'] ."</option>";
		}
		}
	$html .= $teamlist;
	$html .= "</select></td>";
	$html .= "<td>-</td>";
	$html .= "<td style='width:30%'><select class='dropdown' style='width:100%' name='newaway'>";
	$html .= $teamlist;
	$html .= "</select></td>";
	$html .= "<td class='center'><input class='button' type='submit' value='"._("Create")."' name='addnew'/></td>";		
	$html .= "</tr>\n";
	$html .= "</table>";
}


//stores id to delete
$html .= "<p><input type='hidden' id='hiddenDeleteId' name='hiddenDeleteId'/></p>";
$html .= "</form>\n";

echo $html;
contentEnd();
pageEnd();
?>