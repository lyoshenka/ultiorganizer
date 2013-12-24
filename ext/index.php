<?php

include_once 'lib/season.functions.php';
include_once 'lib/common.functions.php';
include_once 'lib/series.functions.php';
include_once 'lib/team.functions.php';
include_once 'lib/country.functions.php';
include_once 'lib/configuration.functions.php';

$LAYOUT_ID = EXTINDEX;
$title = _("Ultiorganizer links");
//common page
pageTop($title, false);
leftMenu($LAYOUT_ID);
contentStart();

$seltournament="";
$selpool="";
$selseries="";
$selteam="";
$selcountry="";
$season="";
$lastown='http://';

$baseurl = GetURLBase();
$defaultcss="default";
if(CUSTOMIZATIONS){
$defaultcss=CUSTOMIZATIONS;
}

$styles = array(urlencode("$baseurl/ext/$defaultcss.css"),urlencode("$baseurl/ext/black.css"),urlencode("$baseurl/ext/noborder.css"));
$stylenames = array(_("default"),_("black and white"),_("no borders"));

if(!empty($_POST['update']))
	{
	$selstyle = $_POST['ownstyle'];
	if(empty($selstyle) || strlen($selstyle)<8)
		$selstyle = $_POST['style'];
	else
		$selstyle = urlencode($_POST['ownstyle']);
	
	if(isset($_POST['tournamentname']))
		$seltournament = $_POST['tournamentname'];
	if(isset($_POST['poolid']))
		$selpool = $_POST['poolid'];
	if(isset($_POST['seriesid']))
		$selseries = $_POST['seriesid'];
	if(isset($_POST['teamid']))		
		$selteam = $_POST['teamid'];
	if(isset($_POST['season']))
		$season = $_POST['season'];
	if(isset($_POST['ownstyle']))
		$lastown = $_POST['ownstyle'];
	if(isset($_POST['country']))
		$selcountry = $_POST['country'];
	}	
//content
if(empty($season))
	$season = CurrentSeason();

echo "<h1>"._("Embedding Ultiorganizer into other pages")."</h1>";

echo "<p>"._("You can embed the following statistics from ultiorganizer directly into your own pages").".</p>\n";
echo "<form method='post' action='?view=ext/index'>\n";

echo "<h2>"._("Select")."</h2>";

//season selection
$selector = "<p>"._("Select event").":	<select class='dropdown' name='season'>\n";

$seasons = Seasons();
		
while($row = mysql_fetch_assoc($seasons))
	{
	
	if($row['season_id'] == $season)
		$selector .= "<option class='dropdown' selected='selected' value='". $row['season_id'] . "'>". utf8entities($row['name']) ."</option>";
	else
		$selector .= "<option class='dropdown' value='". $row['season_id'] . "'>". utf8entities($row['name']) ."</option>";
	}

$selector .= "</select></p>\n";

if(!empty($season)){
$selector .= "<p>"._("Select grouping").":	<select class='dropdown' name='tournamentname'>\n";

$tours = SeasonReservationgroups($season);

foreach($tours as $row)
	{
	if(empty($seltournament))
		$seltournament=$row['reservationgroup'];
		
	if($row['reservationgroup'] == $seltournament)
		$selector .= "<option class='dropdown' selected='selected' value='". $row['reservationgroup'] . "'>". $row['reservationgroup'] ."</option>";
	else
		$selector .= "<option class='dropdown' value='". $row['reservationgroup'] . "'>". $row['reservationgroup'] ."</option>";
	}
	
$selector .="</select></p>\n";
}
$series = array();
if(!empty($season)){
	$selector .= "<p>"._("Select division").":	<select class='dropdown' name='seriesid'>\n";
	
	$series = SeasonSeries($season, true);
			
	foreach($series as $row)
		{
		if(empty($selseries))
			$selseries=$row['series_id'];
			
		if($row['series_id'] == $selseries)
			$selector .= "<option class='dropdown' selected='selected' value='". $row['series_id'] . "'>". utf8entities($row['name']) ."</option>";
		else
			$selector .= "<option class='dropdown' value='". $row['series_id'] . "'>". utf8entities($row['name']) ."</option>";
		}
	$selector .= "</select></p>\n";
	
	
	$selector .= "<p>"._("Select country").":\n";
	$selector .= CountryDropListWithValues("country","country",$selcountry);
	$selector .= "</p>\n";
}
if(!empty($selseries)){
	$selector .= "<p>"._("Select pool").": <select class='dropdown' name='poolid'>\n";
	$pools = SeriesPools($selseries, true);

	foreach($pools as $row)
		{
		if(empty($selpool))
			$selpool=$row['pool_id'];
			
		if($row['pool_id'] == $selpool)
			$selector .= "<option class='dropdown' selected='selected' value='". $row['pool_id'] . "'>". utf8entities($row['name']) ."</option>";
		else
			$selector .= "<option class='dropdown' value='". $row['pool_id'] . "'>". utf8entities($row['name']) ."</option>";
		}

	$selector .= "</select></p>\n";
}
if(!empty($selseries)){
	$selector .= "<p>"._("Select team").": <select class='dropdown' name='teamid'>\n";

	$teams = SeriesTeams($selseries);

	foreach($teams as $row)
		{
		if(empty($selteam))
			$selteam=$row['team_id'];
		
		if($row['team_id'] == $selteam)
			$selector .= "<option class='dropdown' selected='selected' value='". $row['team_id'] . "'>". utf8entities($row['name']) ."</option>";
		else
			$selector .= "<option class='dropdown' value='". $row['team_id'] . "'>". utf8entities($row['name']) ."</option>";
		}
	$selector .= "</select></p>\n";
}

$selector .= "<p>"._("Select style").": <select class='dropdown' name='style'>\n";

if(empty($selstyle))
	$selstyle=$styles[0];
		
for($i=0;$i<count($styles);$i++) 
	{
	if($selstyle == $styles[$i])
		$selector .=  "<option class='dropdown' selected='selected' value='$styles[$i]'>$stylenames[$i]</option>";
	else
		$selector .=  "<option class='dropdown' value='$styles[$i]'>$stylenames[$i]</option>";
	}	

$selector .=  "</select><br/>"._("or a link to a style definition of your own").":\n";
$selector .= "<input class='input' size='50' name='ownstyle' value='$lastown'/></p>";

	
$selector .= "<p><input class='button' type='submit' name='update' value='"._("Select and Update")."' /></p>\n";
echo $selector;

echo "<h2>"._("RSS")."</h2>\n";
echo "<ul class='feed-list'>";
echo "<li><a href='$baseurl/ext/rss.php?feed=all'>"
		._("Ultimate results")."</a></li>";
if(count($series)){
	echo "<li><a href='$baseurl/ext/rss.php?feed=gameresults&amp;id1=$season'>"
		._("Ultimate results").": ".utf8entities(SeasonName($season)) ."</a></li>";
		foreach($series as $ser){
			echo "<li><a href='$baseurl/ext/rss.php?feed=gameresults&amp;id1=$season&amp;id2=".$ser['series_id']."'>"
				._("Ultimate results").": ".utf8entities(SeasonName($season)) ." ".utf8entities(U_($ser['name']))."</a></li>";
		}
}
echo "</ul>\n";

if(IsTwitterEnabled()){
	echo "<h2>"._("Twitter")."</h2>\n";
	if(count($series)){
		echo "<ul class='twitter-list'>";
		$savedurl = GetUrl("season",$season,"result_twitter");
		if($savedurl){
			echo "<li><a href='".$savedurl['url']."'>"
				._("Ultimate results").": ". utf8entities(SeasonName($season)) ."</a></li>";
		}
	
		foreach($series as $ser){
			$savedurl = GetUrl("series",$ser['series_id'],"result_twitter");
			if($savedurl){
				echo "<li><a href='".$ser['series_id']."'>"
					._("Ultimate results").": ".utf8entities(SeasonName($season)) ." ".utf8entities(U_($ser['name']))."</a></li>";
			}
		}
		echo "</ul>\n";
	}
}
if(!empty($season)){
	echo "<h2>"._("Score counter")."</h2>\n";
		echo "<p class='highlight' ><code>
			&lt;object data='$baseurl/ext/scorecounter.php?Season=$season&amp;Numbers=6' <br/>
			type='text/html' width='100px' height='60px'&gt;&lt;/object&gt;
			</code></p>\n";
			
		echo "<p><object data='$baseurl/ext/scorecounter.php?Season=$season&amp;Numbers=6' type='text/html' width='100px' height='60px'></object></p>\n";
	
	echo "<h2>"._("All pools")."</h2>\n";
		echo "<p class='highlight' ><code>
			&lt;object data='$baseurl/ext/eventpools.php?Season=$season&amp;Style=$selstyle' <br/>
			type='text/html' width='600px' height='400px'&gt;&lt;/object&gt;
			</code></p>\n";
			
	echo "<p><object data='$baseurl/ext/eventpools.php?Season=$season&amp;Style=$selstyle' type='text/html' width='600px' height='400px'></object></p>\n";
}
	
if(!empty($seltournament)){
	echo "<h2>"._("All games in selected grouping")."</h2>\n";

	echo "<p class='highlight' ><code>
		&lt;object data='$baseurl/ext/tournament.php?Tournament=$seltournament&amp;Season=$season&amp;Style=$selstyle' <br/>
		type='text/html' width='600px' height='300px'&gt;&lt;/object&gt;
		</code></p>\n";
		
	echo "<p><object data='$baseurl/ext/tournament.php?Tournament=$seltournament&amp;Season=$season&amp;Style=$selstyle' type='text/html' width='600px' height='300px'></object></p>\n";
}

if(!empty($selpool)){
	echo "<h2>"._("Selected pool standings and scoreboard")."</h2>";

	echo "<p class='highlight' ><code>
		&lt;object data='$baseurl/ext/poolstatus.php?Pool=$selpool&amp;Season=$season&amp;Style=$selstyle' <br/>
		type='text/html' width='500px' height='200px'&gt;&lt;/object&gt;
		</code></p>\n";
		
	echo "<p><object data='$baseurl/ext/poolstatus.php?Pool=$selpool&amp;Season=$season&amp;Style=$selstyle' type='text/html' width='500px' height='200px'></object></p>\n";

	echo "<p class='highlight' ><code>
		&lt;object data='$baseurl/ext/poolscoreboard.php?Pool=$selpool&amp;Season=$season&amp;Style=$selstyle' <br/>
		type='text/html' width='300px' height='200px'&gt;&lt;/object&gt;
		</code></p>\n";
		
	echo "<p><object data='$baseurl/ext/poolscoreboard.php?Pool=$selpool&amp;Season=$season&amp;Style=$selstyle' type='text/html' width='400px' height='200px'></object></p>\n";
}
if(!empty($selcountry)){
echo "<h2>"._("Selected country's teams pool standings and game result")."</h2>\n";
	echo "<p class='highlight' ><code>
		&lt;object data='$baseurl/ext/countrystatus.php?Country=$selcountry&amp;Season=$season&amp;Style=$selstyle' <br/>
		type='text/html' width='500px' height='300px'&gt;&lt;/object&gt;
		</code></p>\n";
echo "<p><object data='$baseurl/ext/countrystatus.php?Country=$selcountry&amp;Season=$season&amp;Style=$selstyle' type='text/html' width='500px' height='300px'></object></p>\n";
}

if(!empty($selteam)){
	echo "<h2>"._("Selected team's games and scoreaboard")."</h2>\n";

	echo "<p class='highlight' ><code>
		&lt;object data='$baseurl/ext/teamplayed.php?Team=$selteam&amp;Season=$season&amp;Style=$selstyle' <br/>
		type='text/html' width='400px' height='300px'&gt;&lt;/object&gt;
		</code></p>\n";
		
	echo "<p><object data='$baseurl/ext/teamplayed.php?Team=$selteam&amp;Season=$season&amp;Style=$selstyle' type='text/html' width='400px' height='300px'></object></p>\n";

	echo "<p class='highlight' ><code>
		&lt;object data='$baseurl/ext/teampcoming.php?Team=$selteam&amp;Season=$season&amp;Style=$selstyle' <br/>
		type='text/html' width='400px' height='300px'&gt;&lt;/object&gt;
		</code></p>\n";
		
	echo "<p><object data='$baseurl/ext/teamcoming.php?Team=$selteam&amp;Season=$season&amp;Style=$selstyle' type='text/html' width='400px' height='300px'></object></p>\n";

	echo "<p class='highlight' ><code>
		&lt;object data='$baseurl/ext/teamscoreboard.php?Team=$selteam&amp;Season=$season&amp;Style=$selstyle' <br/>
		type='text/html' width='300px' height='200px'&gt;&lt;/object&gt;
		</code></p>\n";
		
	echo "<p><object data='$baseurl/ext/teamscoreboard.php?Team=$selteam&amp;Season=$season&amp;Style=$selstyle' type='text/html' width='300px' height='200px'></object></p>\n";}
echo "</form>\n";

contentEnd();
pageEnd();
?>
