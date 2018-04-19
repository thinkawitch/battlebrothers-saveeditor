<?php
// Edit Battle brothers saved game.
// Tested on game version 1.1.0.6 GOG
// https://steamcommunity.com/sharedfiles/filedetails/?id=598903989

$loadFromFile = './444.sav';
$brothersCount = 14;
$saveToFile = './444-2.sav';

$selfFile = basename(__FILE__);
$options = getopt('h', ['help', 'list', 'set-stats', 'brother:', 'stats:', 'set-action-points', 'points:']);

if (isset($options['h']) || isset($options['help'])) {
    echo <<<EOD
Available commands:

List brothers stats
    php $selfFile --list
    
Set brother stats
    php $selfFile --set-stats --brother 1 --stats "100 70 120 95 95 75 75 122"
Stats are space separated values of: hitpoints resolve fatigue melee.skill range.skill melee.defence range.defence initiative

Set brother action points
    php $selfFile --set-action-points --brother 1 --points 15

EOD;
    return;
}

$doCommand = 'list';
$brotherNum = null;
$brotherSetStats = null;
$brotherSetActionPoints = null;

// detect command
if (isset($options['set-stats'])) {
    processOptionBrother();
    if (isset($options['stats'])) {
        $stats = explode(' ', $options['stats']);
        if (count($stats) == 8) {
            $brotherSetStats = $stats;
        }
    }
    if (!$brotherNum || !$brotherSetStats) {
        echo "Missing or incorrect arguments, run \"php $selfFile --help\" for details.".PHP_EOL;
        return;
    }
    $doCommand = 'setBrotherStats';

} else if (isset($options['set-action-points'])) {
    processOptionBrother();
    if (isset($options['points'])) {
        $brotherSetActionPoints = intval($options['points']);
    }
    if (!$brotherNum || !$brotherSetActionPoints) {
        echo "Missing or incorrect arguments, run \"php $selfFile --help\" for details.".PHP_EOL;
        return;
    }
    $doCommand = 'setBrotherActionPoints';
}

echo 'Will try to do command: '.$doCommand.PHP_EOL;




// load saved game

$h = fopen($loadFromFile, 'rb');
$content = stream_get_contents($h);
fclose($h);

$contentLen = strlen($content);

echo 'load from file: '.$loadFromFile.PHP_EOL;
echo 'file length: '.$contentLen.PHP_EOL;
echo 'brothersCount: '.$brothersCount.PHP_EOL;


// find brothers
$wordHuman = 'human';
$wordHumanLen = strlen($wordHuman);
$lastPos = 0;
$positions = [];
while (($lastPos = strpos($content, $wordHuman, $lastPos)) !== false) {
    $positions[] = $lastPos;
    if (count($positions) == $brothersCount) break; // our brothers are first, but there are other humans too
    $lastPos = $lastPos + $wordHumanLen;
}

echo "brothers found: ".print_r(count($positions), true).PHP_EOL.PHP_EOL;

/*
    stats:
    - hitpoints
    - resolve
    - fatigue
    - melee skill
    - ranged skill
    - melee defence
    - ranged defence
    - initiative
 */
$statsNames = [
    'hitpoints',
    'resolve',
    'fatigue',
    'm.skill',
    'r.skill',
    'm.defence',
    'r.defence',
    'initiative'
];


$bc = 0;
foreach ($positions as $pos) {
    $bc++;
    $statsLineOffset = $pos + $wordHumanLen + 6;
    $statsLine = substr($content, $statsLineOffset, 15);
    $brotherStats = [];
    $brotherAP = 0;
    //action points
    $c = substr($content, $statsLineOffset-1, 1);
    $brotherAP = ord($c);
    //stats
    for ($i=0; $i<16; $i+=2) {
        $c = substr($statsLine, $i);
        $val = ord($c);
        $brotherStats[] = $val;
    }
    if ($doCommand == 'list') {
        echo 'brother '.$bc.PHP_EOL;
        echo 'action points:'.$brotherAP.PHP_EOL;
        echo bin2hex($statsLine).PHP_EOL;
        echo getInlineBrotherStats($brotherStats);
        echo PHP_EOL.PHP_EOL;
    }
    if ($bc == $brotherNum && $doCommand == 'setBrotherStats') {
        echo 'brother '.$bc.', will change stats'.PHP_EOL;
        $sk = 0;
        $newStatsLine = '';
        foreach ($brotherSetStats as $sv) {
            echo $statsNames[$sk].': '.$sv.PHP_EOL;
            $newStatsLine .= chr($sv).chr(0);
            $sk++;
        }
        $newStatsLine = substr($newStatsLine, 0, 15);
        echo bin2hex($newStatsLine).PHP_EOL;
        $content = substr_replace($content, $newStatsLine, $statsLineOffset, 15);
    }
    if ($bc == $brotherNum && $doCommand == 'setBrotherActionPoints') {
        echo 'brother '.$bc.', will change action points to '.$brotherSetActionPoints.PHP_EOL;
        $content = substr_replace($content, chr($brotherSetActionPoints), $pos + $wordHumanLen + 5, 1);
    }
}


if (in_array($doCommand , ['setBrotherStats', 'setBrotherActionPoints'])) {
    echo 'Save to file: '.$saveToFile.PHP_EOL;
    saveToFile($saveToFile, $content);
    echo 'Done.'.PHP_EOL;
}


// --- end  ---, support functions

function processOptionBrother() {
    global $brotherNum, $options, $brothersCount;
    if (isset($options['brother'])) {
        $brotherNum = intval($options['brother']);
        if ($brotherNum > $brothersCount) {
            $brotherNum = null;
            echo 'Your brother number is higher than total brothers count! You may need to edit $brothersCount value.'.PHP_EOL;
        }
    }
}


function getInlineBrotherStats($stats) {
    global $statsNames;
    $line = [];
    $k = 0;
    foreach ($statsNames as $name) {
        $line[] = $name.': '.$stats[$k];
        $k++;
    }
    return implode(', ', $line);
}

function saveToFile($filename, $data) {
    $h = fopen($filename, 'wb+');
    if ($h) {
        fwrite($h, $data);
        fclose($h);
    }
}