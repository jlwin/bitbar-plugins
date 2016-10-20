#!/usr/bin/env php
<?php
// <bitbar.title>RescueTime Productivity</bitbar.title>
// <bitbar.version>v1.0</bitbar.version>
// <bitbar.author>Jean-Luc Winkler</bitbar.author>
// <bitbar.author.github>jlwin</bitbar.author.github>
// <bitbar.desc>Display RescueTime Productivity of last 30 minutes in Progress Bar and Productivity Pulse of the current day broken down by Productivity Level in the Dropdown.</bitbar.desc>
// <bitbar.image>https://jlwin.co/rescuetime-bitbar-2016-10-20.png</bitbar.image>
// <bitbar.dependencies>php >= 5</bitbar.dependencies>


// ##### SETTINGS #####
// paste your RescueTime API key here:
$key = "enter-your-api-key-here";



// ##### CODE #####
// no need to make changes below this point, except of code improvements ;-)

// ### fetch current productivity pulse that displays the productivity level of the last 30 minutes
$urlPulse30Min = "https://www.rescuetime.com/anapi/current_productivity_pulse.json?key=" . $key;

function fetchPulseCurrent($url){
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$json = json_decode(curl_exec($ch));
	curl_close($ch);
	return $json;
};

$jsonPulse30Min = fetchPulseCurrent($urlPulse30Min);
$pulse30Min = $jsonPulse30Min->pulse;
// api reports "null" if less than 15 minutes are logged for today
$pulse30Min = ($jsonPulse30Min->pulse != null ? $jsonPulse30Min->pulse : "-");
$color = $jsonPulse30Min->color;

echo $pulse30Min . "|color=" . $color . "\n";
echo "---\n";


// ### fetch today's productivity pulse
$urlPulseToday = "https://www.rescuetime.com/anapi/data?key=" . $key . "&perspective=interval&restrict_kind=productivity&resolution_time=day";

function fetchPulseToday($url){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$output = curl_exec($ch);
	// api returns an html header, json string needs to be extracted by the following regex pattern
	$pattern = "/\{(?:[^{}]|(?R))*\}/";
	preg_match_all($pattern, $output, $matches);
	$json = json_decode($matches[0][0]);
	return $json;
}

$jsonPulseToday = fetchPulseToday($urlPulseToday);
$pulseTodayLevelTimeSpent = array();

foreach ($jsonPulseToday->rows as $rows) {
	$pulseTodayLevelTimeSpent[$rows[3]] = $rows[1];
}
krsort($pulseTodayLevelTimeSpent);

// calculate all time logged for today
$timeSpentTotal = 0;
foreach ($pulseTodayLevelTimeSpent as $key => $value) {
	$timeSpentTotal = $timeSpentTotal + $value;
};

// calculate percentage of time spent for each productivity category
$pulseTodayLevel = array();
foreach ($pulseTodayLevelTimeSpent as $key => $value) {
	$progressPercentage = $value / $timeSpentTotal * 100;
	$pulseTodayLevel[$key] = $progressPercentage;
};

// culculate and display today's productivity pulse
$pulseTodayScore = 0;
foreach ($pulseTodayLevel as $key => $value) {
	$scorePart = 0;
	$pulseCalculationPercentage = array(
		"2" => 1, 
		"1" => 0.75, 
		"0" => 0.5, 
		"-1" => 0.25, 
		"-2" => 0);
	$scorePart = $pulseCalculationPercentage[$key] * $value;
	$pulseTodayScore = $pulseTodayScore + $scorePart;
}
echo "Productivity Pulse: " . floor($pulseTodayScore) . "| color=#000 \n";

// display amoount of time spent on each productivity level
$productivityLevelNames = array(
	"2" => array("very productive", "#2F78BD"), 
	"1" => array("productive", "#395B96"), 
	"0" => array("neutral", "#655568"), 
	"-1" => array("unproductive", "#92343B"), 
	"-2" => array("very unproductive", "#C5392F"));
foreach ($pulseTodayLevel as $key => $value) {
	echo $productivityLevelNames[$key][0] . ": " . round($value) . "% | color=" . $productivityLevelNames[$key][1] . "\n";
};

// display link to go to RescueTime Dashboard
echo "Go to Dashboard | href=https://www.rescuetime.com/dashboard?src=bitbar";


