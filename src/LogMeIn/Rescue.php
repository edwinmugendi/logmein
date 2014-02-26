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

    public function getReportV2($beginDate, $endDate, $reportArea, $nodeId, $nodeRef = "NODE") {
        //Initialize a soap client
        $soapclient = new SoapClient($this->link . "/api.asmx?wsdl");

        //Set the report area
        $reportAreaParams = array(
            'eReportArea' => $reportArea,
            'sAuthCode' => $this->authCode
        );

        $setReportAreaResponse = $soapclient->setReportArea($reportAreaParams);

        //Set data ranges
        $reportDateParams = array(
            'sBeginDate' => $beginDate,
            'sEndDate' => $endDate,
            'sAuthCode' => $this->authCode
        );

        $setReportDateResponse = $soapclient->setReportDate($reportDateParams);

        //Set the node
        $getReportParams = array(
            'iNodeID' => $nodeId,
            'eNodeRef' => $nodeRef,
            'sAuthCode' => $this->authCode
        );
        //Get report and convert to array
        $getReportResponse = $this->object_to_array($soapclient->getReport($getReportParams));

        //parse results into an array (NuSOAP stinks at multilevel XML
        $reportData = explode("\n", $getReportResponse['sReport']);

        $report = false;
        foreach ($reportData as $key => $val) {//Loop via report data
            if ($key == 0) {//Header
                $column = explode("|", $val);
            }//E# if statement


            if (trim($val) !== '') {
                $colData = explode("|", trim($val));
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
    }

}

//E# Rescue() Class



 