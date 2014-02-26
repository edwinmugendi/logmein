<?php

namespace LogMeIn;

use Guzzle\Http\Client;
use SoapClient;
use DateTime;

/**
 * S# Rescue() Class
 * @author Edwin Mugendi <edwinmugendi@gmail.com>
 * This is a PHP wrapper for some of the LogMeIn API functions
 * */
class Rescue {

//The API link
    private $link;
//The API auth code
    private $authCode;
//Curl guzzle object
    private $guzzleClient;
//Curl guzzle object
    private $guzzleRequest;
//Curl guzzle object
    private $guzzleResponse;

    /**
     * S# __construct() function
     * Constructor
     * */
    public function __construct($email, $pwd) {
        //Load configs
        $configs = json_decode(file_get_contents(__DIR__ . '/configs.json'), true);
        //Set configs
        $this->link = $configs['link'];

        //Intialize the guzzle client
        $this->guzzleClient = new Client($this->link);

        $lastDate = new DateTime($configs['authRequestedAt']);
        $today = new DateTime();
        $dDiff = $lastDate->diff($today);
        if ($dDiff->days > 18) {
            $this->authCode = $this->requestAuthCode($email, $pwd);
        } else {
            $this->authCode = $configs['authCode'];
        }//E# if else statement
    }

//E# __construct() function

    private function object_to_array($obj) {
        $arrObj = is_object($obj) ? get_object_vars($obj) : $obj;
        foreach ($arrObj as $key => $val) {
            $val = (is_array($val) || is_object($val)) ? $this->object_to_array($val) : $val;
            $arr[$key] = $val;
        }
        return $arr;
    }

    /**
     * S# requestAuthCode() function
     * Request Authentication code
     * @link requestAuthCode.aspx
     * @param string $email Your email address
     * @param string $pwd Your password
     * */
    public function requestAuthCode($email, $pwd) {
        //Build endpoint
        $this->guzzleRequest = $this->guzzleClient->get('requestAuthCode.aspx');

        //Set query parameters
        $query = $this->guzzleRequest->getQuery();
        $query->set('email', $email);
        $query->set('pwd', $pwd);

        //Call API
        $this->guzzleResponse = $this->guzzleRequest->send();

        //Get request
        $response = trim($this->guzzleResponse->getBody(true));

        if ($response == 'INVALID') {//Invalid request
            return 'INVALID';
        } else {//Sucessful request
            //Get auth code
            $authCode = substr($response, strpos($response, 'AUTHCODE') + 9);

            //Load configs
            $configs = json_decode(file_get_contents(__DIR__ . '/configs.json'), true);

            //Set auth code and last date to request auth
            $configs['authCode'] = $authCode;
            $configs['authRequestedAt'] = date("Y-m-d");

            //Open and save the configss
            $fp = fopen(__DIR__ . '/configs.json', 'w+');
            fwrite($fp, json_encode($configs));
            fclose($fp);

            //Return auth code
            return $authCode;
        }//E# if else statement
    }

//E# requestAuthCode() function

    public function getReportV2($beginDate, $endDate, $reportArea, $nodeId, $beginTime = null, $endTime = null, $timeZone = 'UTC', $output = 'TEXT', $delimiter = '|', $nodeRef = "NODE") {
        //Initialize a soap client
        $soapClient = new SoapClient($this->link . "/api.asmx?wsdl");

        //Set the report area
        $reportAreaParams = array(
            'eReportArea' => $reportArea,
            'sAuthCode' => $this->authCode
        );

        $setReportAreaResponse = $soapClient->setReportArea($reportAreaParams);

        //Set date ranges
        $reportDateParams = array(
            'sBeginDate' => $beginDate,
            'sEndDate' => $endDate,
            'sAuthCode' => $this->authCode
        );

        $setReportDateResponse = $soapClient->setReportDate($reportDateParams);

        //Set time range
        if (!is_null($beginTime) && !is_null($beginTime)) {
            $reportTimeParams = array(
                'bTime' => $beginDate,
                'eTime' => $endDate,
                'sAuthCode' => $this->authCode
            );
            $setReportTimeResponse = $soapClient->setReportTime($reportTimeParams);
        }//E# statement

        if ($output == 'XML') {//XML Output other wise default to TEXT
            //Set time range
            $outputParams = array(
                'eOutput' => 'XML',
                'sAuthCode' => $this->authCode
            );

            $outputResponse = $soapClient->setOutput($outputParams);
        }//E# if statement

        if ($timeZone !== 'UTC' && is_int($timeZone)) {//Use different timezone from UTC
            $setTimezoneParams = array(
                'sTimezone' => $timeZone,
                'sAuthCode' => $this->authCode
            );
            $setTimezoneResponse = $soapClient->setTimezone($setTimezoneParams);
        }//E# if statement


        if ($delimiter !== '|') {
            //define parameters
            $delimiterParams = array(
                'sDelimiter' => $delimiter,
                'sAuthCode' => $this->authCode
            );

            //set the delimiter
            $setDelimiterResponse = $soapClient->setDelimiter($delimiterParams);
        }//E# if statement
        //Set the node
        $getReportParams = array(
            'iNodeID' => $nodeId,
            'eNodeRef' => $nodeRef,
            'sAuthCode' => $this->authCode
        );

        //Get report and convert to array
        $apiResponse = $soapClient->getReport($getReportParams);

        //Get the api code
        $apiResponseCode = strtoupper(substr($apiResponse->getReportResult, 10));

        if ($apiResponseCode == 'OK') {
            //Get the api response as array
            $apiResponseArray = $this->object_to_array($apiResponse);
            //Parse results into an array
            $reportData = explode("\n", $apiResponseArray['sReport']);

            $report = '';
            foreach ($reportData as $key => $val) {//Loop via report data
                if ($key == 0) {//Header
                    $column = explode($delimiter, $val);
                    array_pop($column);
                }//E# if statement

                if (($trimmedVal = trim($val)) !== '') {
                    $colData = explode($delimiter, trim($trimmedVal));
                    array_pop($colData);
                    foreach ($colData as $ckey => $val) {
                        if (empty($column[$ckey])) {
                            $column[$ckey] = $ckey;
                        } else {
                            $column[$ckey] = str_replace(" ", "", $column[$ckey]);
                        }//E# if else statment
                        $report[$key][$column[$ckey]] = $val;
                    }//E# foreach statement
                }//E# if statement
            }//E# foreach statement

            return $report;
        } else {
            return $apiResponse;
        }
    }

//E# getReportV2() function

    /**
     * S# getChatOrNote() function
     * Get chat log or technicians note of a session
     * @param string $chatOrNote Chat or note
     * @param int $sessionId The session id
     * @return string Chat log / Log if successful, or code as specified in the official API
     * @link https://secure.logmeinrescue.com/welcome/webhelp/RescueAPI/API/API_Rescue_getNote.html Get Note API
     * @link https://secure.logmeinrescue.com/welcome/webhelp/RescueAPI/API/API_Rescue_getChat.html Get Chat API
     */
    public function getChatOrNote($chatOrNote, $sessionId) {
        //Initialize a soap client
        $soapClient = new SoapClient($this->link . "/api.asmx?wsdl");

        //Set parameters
        $params = array(
            'iSessionID' => $sessionId,
            'sAuthCode' => $this->authCode
        );

        if ($chatOrNote == 'chat') {//Get chat
            $apiResponse = $soapClient->getChat($params);
        } else if ($chatOrNote == 'note') {//Get note
            $apiResponse = $soapClient->getNote($params);
        } else {//ERROR
            return 'ERROR';
        }//E# if else statement
        //Set the api result property
        $chatOrNoteResult = $chatOrNote == 'chat' ? 'getChatResult' : 'getNoteResult';

        //Get the api code
        $apiResponseCode = strtoupper(substr($apiResponse->$chatOrNoteResult, 8));

        if ($apiResponseCode == 'OK') {//OK
            $log = $chatOrNote == 'chat' ? $apiResponse->sChatLog : $apiResponse->sNote;

            return array(
                'status' => 1,
                'log' => $log
            );
        } else {//ERROR
            return $apiResponseCode;
        }//E# if else statement
    }

//E# getChatOrNote() function
}

//E# Rescue() Class



 