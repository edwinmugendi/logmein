<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use LogMeIn\Rescue;

$rescue = new Rescue('edwin.sapama@gmail.com', 'Computer1');
/*
  //Request Auth Code
  $authCode = $rescue->requestAuthCode('edwin.sapama@gmail.com', 'Computer1');

  var_dump($authCode);
 * 
 */
 

  //Repport
  $report = $rescue->getReportV2('2014-02-20T07:00:00', '2014-02-26T12:30:20', 'SESSION', 7426179,null,null,'UTC','TEXT','|');
  var_dump($report);
/*
/*
//Session 254286942
$chat = $rescue->getChatOrNote('chat',254286942);

var_dump($chat);

 * 
 */