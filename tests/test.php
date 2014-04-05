<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use LogMeIn\Rescue;

$rescue = new Rescue('YOUR LOGMEIN EMAIL', 'YOUR LOGMEIN PASSWORD');

//Report
$report = $rescue->getReportV2('2014-04-01T07:00:00', '2014-04-01T08:30:20', 'SESSION', 'YOUR LOGMEIN NODE', null, null, 'UTC', 'TEXT', '|');
var_dump($report);


//Get chat
$chat = $rescue->getChatOrNote('chat', 'YOUR LOGMEIN SESSION ID');

