<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use LogMeIn\Rescue;

$rescue = new Rescue('edwin.sapama@gmail.com', 'Computer1');
/*
  //Request Auth Code
  $authCode = $rescue->requestAuthCode('edwin.sapama@gmail.com', '');

  var_dump($authCode);
 */

  //Repport
  $report = $rescue->getReportV2('2014-04-01T07:00:00', '2014-04-01T08:30:20', 'SESSION', 7426179,null,null,'UTC','TEXT','|');
  var_dump($report);

/*
//Session 254286942
$chat = $rescue->getChatOrNote('chat',254286942);

var_dump($chat);

 * 
 */