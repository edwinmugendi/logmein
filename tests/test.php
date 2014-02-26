<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use LogMeIn\Rescue;

$rescue = new Rescue('edwinmugendi@gmail.com', 'isabe11A');
/*
  //Request Auth Code
  $authCode = $rescue->requestAuthCode('edwinmugendi@gmail.com', '');

  var_dump($authCode);
 */
/*
  //Repport
  $report = $rescue->getReportV2('02/23/2014', '02/25/2014', 'SESSION', 13038860);
  var_dump($report);

 *  */
//Session 254286942
$chat = $rescue->getChatOrNote('chat',254286942);

var_dump($chat);
?>