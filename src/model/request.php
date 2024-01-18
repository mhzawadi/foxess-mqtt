<?php

namespace MHorwood\foxess_mqtt\model;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\classes\logger;
use MHorwood\foxess_mqtt\model\config;

class request extends json {

  protected $config;
  public function __construct($config){
    $this->config = $config;
  }

  /**
   * Build signature
   *
   *
   * @param string url The path on the URL
   * @param string lang what language are you
   * @return return array
   */
  protected function get_signature($url, $lang='en')
  {
    $token = $this->config->foxess_apikey;
    $timestamp = floor(microtime(true) * 1000);
    $signature = $url.'\r\n'.$token.'\r\n'.$timestamp;

    $result = array(
        'token: '.$token,
        'lang: '.$lang,
        'Timestamp: '.$timestamp,
        'Signature: '.md5(utf8_encode($signature)),
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
        'Content-Type: application/json'
    );
    return $result;
  }

  /**
   * curl POST with signed headers
   *
   * Undocumented function long description
   *
   * @param type var Description
   * @return return type
   */
  public function sign_post($path, $data, $lang) {
    $headers = $this->get_signature($path, $lang);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt_array ( $curl , [
      CURLOPT_URL => "https://www.foxesscloud.com$path",
      CURLOPT_RETURNTRANSFER => true
    ] );
    $this_curl = curl_exec($curl);
    return $this_curl;
  }

  /**
   * curl GET with signed headers
   *
   * Undocumented function long description
   *
   * @param string path Description
   * @param array params Description
   * @return return type
   */
  public function sign_get($path, $params = array(), $lang = 'en') {
    $headers = $this->get_signature($path, $lang);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );
    curl_setopt_array ( $curl , [
      CURLOPT_URL => "https://www.foxesscloud.com$path?".http_build_query($params),
      CURLOPT_RETURNTRANSFER => true
    ] );
    $this_curl = curl_exec($curl);
    return $this_curl;
  }
}
