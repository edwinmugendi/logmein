<?php

namespace LogMeIn;

/**
 * S# Rescue() Class
 * @author Edwin Mugendi <edwinmugendi@gmail.com>
 * This is a PHP wrapper for some of the LogMeIn API functions
 * */
class Rescue {

//The API link
    private $link = 'https://secure.logmeinrescue.com/API';
//The API Email
    private $email;
//The API password
    private $pwd;
//Curl guzzle object
    private $soapClient;
    /**
     * S# __construct() function
     * Constructor
     * */
    public function __construct($email, $pwd) {
        //Set email and pasword
        $this->email = $email;
        $this->pwd = $pwd;
        $this->requestAuthCode($this->email, $this->pwd);
        
         //Initialize a soap client
        $this->soapClient = new \SoapClient($this->link . "/api.asmx?wsdl");
        
        //define parameters
        $loginParams = array(
            'sEmail' => $this->email,
            'sPassword' => $this->pwd
        );
        
        //login
        $loginResult = $this->soapClient->login($loginParams);
    }

//E# __construct() function
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
        //Build the url
        $url = $this->link . '/login.aspx?email=' . $email . '&pwd=' . $pwd;
        // Get cURL resource
        $curl = curl_init();

        // Set curl options
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
        ));

        // Send the request & save response to response
        $response = trim(curl_exec($curl));

        // Close request to clear up some resources
        curl_close($curl);

        if ($response == 'OK') {//Invalid request
            return 'OK';
        } else {//Sucessful request
            die('Failed to login, kindly check your password is correct');
        }//E# if else statement
    }

//E# requestAuthCode() function

    public function getReportV2($beginDate, $endDate, $reportArea, $nodeId, $beginTime = null, $endTime = null, $timeZone = 'UTC', $output = 'TEXT', $delimiter = '|', $nodeRef = "NODE") {
         //Set the report area
        $reportAreaParams = array(
            'eReportArea' => $reportArea
        );

        $setReportAreaResponse = $this->soapClient->setReportArea_v2($reportAreaParams);
        //  var_dump($setReportAreaResponse);
        //Set date ranges
        $reportDateParams = array(
            'dBeginDate' => $beginDate,
            'dEndDate' => $endDate,
        );

        $setReportDateResponse = $this->soapClient->setReportDate_v2($reportDateParams);
        // var_dump($setReportDateResponse);
        //Set time range
        if (!is_null($beginTime) && !is_null($beginTime)) {
            $reportTimeParams = array(
                'bTime' => $beginDate,
                'eTime' => $endDate,
            );
            $setReportTimeResponse = $this->soapClient->setReportTime($reportTimeParams);
        }//E# statement

        if ($output == 'XML') {//XML Output other wise default to TEXT
            //Set time range
            $outputParams = array(
                'eOutput' => 'XML',
            );

            $outputResponse = $this->soapClient->setOutput($outputParams);
        }//E# if statement

        if ($timeZone !== 'UTC' && is_int($timeZone)) {//Use different timezone from UTC
            $setTimezoneParams = array(
                'sTimezone' => $timeZone,
            );
            $setTimezoneResponse = $this->soapClient->setTimezone($setTimezoneParams);
            var_dump($setTimezoneResponse);
        }//E# if statement

        if ($delimiter !== '|') {
            //define parameters
            $delimiterParams = array(
                'sDelimiter' => $delimiter,
            );

            //set the delimiter
            $setDelimiterResponse = $this->soapClient->setDelimiter($delimiterParams);
            var_dump($setDelimiterResponse);
        }//E# if statement
        //Set the node
        $getReportParams = array(
            'iNodeID' => $nodeId,
            'eNodeRef' => $nodeRef,
        );

        //Get report and convert to array
        $apiResponse = $this->soapClient->getReport_v2($getReportParams);

        var_dump($apiResponse);
        
        //Get the api code
        $apiResponseCode = strtoupper(substr($apiResponse->getReport_v2Result, 10));

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
        $this->soapClient = new \SoapClient($this->link . "/api.asmx?wsdl");
        
        //Set parameters
        $params = array(
            'iSessionID' => $sessionId
        );

        if ($chatOrNote == 'chat') {//Get chat
            $apiResponse = $this->soapClient->getChat($params);
        } else if ($chatOrNote == 'note') {//Get note
            $apiResponse = $this->soapClient->getNote($params);
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



 