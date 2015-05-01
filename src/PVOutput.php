<?php

namespace PVOutput;

/**
 * Methods to communicate with PVOutput.org
 *
 * @author Ermanno Baschiera <ebaschiera@gmail.com>
 */
class PVOutput {

  private $api_key;
  private $system_id;

  const PVOUTPUT_ADDSTATUS_URL = 'http://pvoutput.org/service/r2/addstatus.jsp';
  const PVOUTPUT_ADDOUTPUT_URL = 'http://pvoutput.org/service/r2/addoutput.jsp';
  const PVOUTPUT_GETOUTPUT_URL = 'http://pvoutput.org/service/r2/getoutput.jsp';

  /**
   * Class constructor with PVOutput parameters init
   * 
   * @param string $system_id
   * @param string $api_key
   */
  public function __construct($system_id = NULL, $api_key = NULL) {
    if (is_null($system_id) || is_null($api_key)) {
      throw new \Exception('Missing PVOutput\'s system_id or api_key');
    }
    $this->system_id = $system_id;
    $this->api_key = $api_key;
  }

  /**
   * Adds a Status to PVOutput.org via the Add Status Service
   * 
   * @param int $production_amount
   * @param int $consumption_amount
   * @param \DateTime $timestamp
   * @return boolean
   * @link http://www.pvoutput.org/help.html#api-addstatus API documentation
   */
  public function addStatus($production_amount = NULL, $consumption_amount = NULL, \DateTime $timestamp = NULL) {

    if (is_null($production_amount) && is_null($consumption_amount)) {
      throw new \Exception('Missing both production and consumption values');
    }

    if (is_null($timestamp)) {
      $timestamp = new \DateTime();
    }

    $date = $timestamp->format('Ymd');
    $time = $timestamp->format('H:i');

    $post_fields = array(
        'd' => $date,
        't' => $time,
    );
    if (!is_null($production_amount)) {
      $post_fields['v2'] = $production_amount;
    }
    if (!is_null($consumption_amount)) {
      $post_fields['v4'] = $consumption_amount;
    }
    
    $response = $this->makeRequest(self::PVOUTPUT_ADDSTATUS_URL, $post_fields);
    if ($response == 'OK 200: Added Status') {
      return TRUE;
    } else {
      throw new \Exception('Failed to send data. Returned message from PVOutput is: ' . $response);
    }
  }

  
  /**
   * Adds a daily Output to PVOutput.org via the Add Output Service
   * 
   * @param \DateTime $date
   * @param int $generated
   * @param int $peak_power
   * @param \DateTime $peak_time
   * @param int $consumption
   * @return boolean true if sending data is ok
   * @throws \Exception
   * @link http://www.pvoutput.org/help.html#api-addoutput API documentation
   */
  public function addOutput(\DateTime $date, $generated = NULL, $peak_power = NULL, 
          \DateTime $peak_time = NULL, $consumption = NULL) {
    
    if (is_null($generated) && is_null($consumption)) {
      throw new \Exception('Missing both generation and consumption values');
    }
    
    $post_fields = array('d' => $date->format('Ymd'));
    
    if (!is_null($generated)) {
      $post_fields['g'] = $generated;
    }
    if (!is_null($peak_power) && !is_null($peak_time)) {
      $post_fields['pp'] = $peak_power;
      $post_fields['pt'] = $peak_time->format('H:i');
    }
    if (!is_null($consumption)) {
      $post_fields['c'] = $consumption;
    }
    $response = $this->makeRequest(self::PVOUTPUT_ADDOUTPUT_URL, $post_fields);
    if ($response == 'OK 200: Added Output' || $response == 'OK 200: Updated Output') {
      return TRUE;
    } else {
      throw new \Exception('Failed to send data. Returned message from PVOutput is: ' . $response);
    }
  }
  
  
  /**
   * Gets a daily Output to PVOutput.org via the Get Output Service
   * 
   * @param \DateTime $from_date
   * @param \DateTime $to_date
   * @param string $aggregate
   * @param int $limit
   * @param int $team_id
   * @param int $system_id
   * @return string
   * @throws \Exception
   */
  public function getOutput(\DateTime $from_date = NULL, \DateTime $to_date = NULL, $aggregate = NULL,
          $limit = NULL, $team_id = NULL, $system_id = NULL) {
    
    $request_fields = array();
    if (!is_null($from_date)) {
      $request_fields['df'] = $from_date->format('Ymd');
    }
    if (!is_null($to_date)) {
      $request_fields['dt'] = $to_date->format('Ymd');
    }
    if (!is_null($aggregate)) {
      if ($aggregate != 'm' && $aggregate != 'y') {
        throw new \Exception('Aggregate value must be \'m\' or \'y\'.');
      }
      $request_fields['a'] = $aggregate;
    }
    if (!is_null($limit)) {
      if (!is_numeric($limit)) {
        throw new \Exception('Limit value must be a number');
      }
      $request_fields['limit'] = $limit;
    }
    if (!is_null($team_id)) {
      if (!is_numeric($team_id)) {
        throw new \Exception('Team id value must be a number');
      }
      $request_fields['tid'] = $team_id;
    }
    if (!is_null($system_id)) {
      if (!is_numeric($system_id)) {
        throw new \Exception('System id value must be a number');
      }
      $request_fields['sid1'] = $system_id;
    }
    
    $response = $this->makeRequest(self::PVOUTPUT_GETOUTPUT_URL, $request_fields, FALSE);
    return $response;
  }
  
  private function makeRequest($url, $request_fields = NULL, $isPost = TRUE) {
    $ch = curl_init();
    $curl_options = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_HTTPHEADER => array(
            'X-Pvoutput-Apikey: ' . $this->api_key,
            'X-Pvoutput-SystemId: ' . $this->system_id,
        ),
        CURLOPT_HEADER => TRUE,
    );
    
    if ($isPost) {
      $curl_options[CURLOPT_POST] = TRUE;
      $curl_options[CURLOPT_POSTFIELDS] = http_build_query($request_fields);
    } else {
      $curl_options[CURLOPT_URL] = $url . '?' .http_build_query($request_fields);
    }
    curl_setopt_array($ch, $curl_options);
    $response = curl_exec($ch);
    list($header, $body) = explode("\r\n\r\n", $response, 2);
    $header_rows = explode("\r\n", $header);
    if ($header_rows[0] != 'HTTP/1.1 200 OK') {
      throw new Exception('Error contacting the web service. Header returned is: ' . $header_rows[0]);
    }
    return $body;
  }

}
