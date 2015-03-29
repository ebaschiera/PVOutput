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
   * Adds a Status to PVOutput.org via the Add Status Service.
   * This method will send instant values (v2 and/or v4 fields according to API specs).
   * 
   * @param int $generation_amount watts (v2 parameter in API)
   * @param int $consumption_amount watts (v4 parameter in API)
   * @param \DateTime $timestamp (d and t parameters in API)
   * @return boolean
   * @link http://www.pvoutput.org/help.html#api-addstatus API documentation
   */
  public function addStatus($generation_amount = NULL, $consumption_amount = NULL, \DateTime $timestamp = NULL) {

    if (is_null($generation_amount) && is_null($consumption_amount)) {
      throw new \Exception('Missing generation and consumption values at the same time. At least one must be provided.');
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
    if (!is_null($generation_amount)) {
      $post_fields['v2'] = $generation_amount;
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
   * @param int $generated watt hours (g parameter in API)
   * @param int $peak_power watts (pp parameter in API)
   * @param \DateTime $peak_time (pt parameter in API)
   * @param int $consumption watt hours (c parameter in API)
   * @return boolean true if sending data is ok
   * @throws \Exception
   * @link http://www.pvoutput.org/help.html#api-addoutput API documentation
   */
  public function addOutput(\DateTime $date = NULL, $generated = NULL, $peak_power = NULL, 
          \DateTime $peak_time = NULL, $consumption = NULL) {
    
    if (is_null($generated) && is_null($consumption)) {
      throw new \Exception('Missing both generation and consumption values');
    }
    
    if (is_null($date)) {
      $date = new \DateTime();
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
  
  private function makeRequest($url, $post_fields) {
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_POSTFIELDS => http_build_query($post_fields),
        CURLOPT_HTTPHEADER => array(
            'X-Pvoutput-Apikey: ' . $this->api_key,
            'X-Pvoutput-SystemId: ' . $this->system_id,
        ),
    ));
    return curl_exec($ch);
  }

}
