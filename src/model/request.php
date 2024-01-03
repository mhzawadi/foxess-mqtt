<?php

namespace MHorwood\foxess_mqtt\model;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\classes\logger;
use MHorwood\foxess_mqtt\model\config;

class request extends json {

  protected $config;
  public function __construct(){
    try {
      $this->config = new config();
    } catch (Exception $e) {
      $this->log('Missing config: '.  $e->getMessage(), 1);
    }
  }

  /**
   * undocumented function summary
   *
   * Undocumented function long description
   *
   * @param type var Description
   * @return return type
   */
  public function get_signature($token, $url, $lang='en')
  {
    $timestamp = floor(microtime(true) * 1000);
    $signature = $url.'\r\n'.$token.'\r\n'.$timestamp;
    $this->log($signature, 4);

    $result = array(
        'token: '.$token,
        'lang: '.$lang,
        'timestamp: '.$timestamp,
        'signature: '.md5(utf8_encode($signature)),
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
        'Content-Type: application/json'
    );
    $this->log($result, 4);
    return $result;
  }

}
