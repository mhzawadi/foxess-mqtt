<?php

namespace MHorwood\foxess_mqtt\model;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\classes\logger;
use MHorwood\foxess_mqtt\model\config;

class login extends json {

  protected $config;
  public function __construct(){
    try {
      $this->config = new config();
    } catch (Exception $e) {
      echo 'Missing config: ',  $e->getMessage(), "\n";
    }
  }

  /**
   * Login to Foxess Cloud
   *
   * Undocumented function long description
   *
   * @param type var Description
   * @return return type
   */
  public function login() {
    $this->log('start of login');
    $data = '{
        "user": "'.$this->config->foxess_username.'",
        "password": "'.$this->config->foxess_password.'"
    }';
    set_time_limit(0);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HTTPHEADER,
      array(
        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.134 Safari/537.36 OPR/89.0.4447.83',
        'Accept: application/json, text/plain, */*',
        'lang: en',
        'sec-ch-ua-platform: macOS',
        'Sec-Fetch-Site: same-origin',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Dest: empty',
        'Referer: https://www.foxesscloud.com/login?redirect=/',
        'Accept-Language: en-US;q=0.9,en;q=0.8,de;q=0.7,nl;q=0.6',
        'Connection: keep-alive',
        'X-Requested-With: XMLHttpRequest',
        'token: '
      )
    );
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt_array ( $curl , [
    CURLOPT_URL => "https://www.foxesscloud.com/c/v0/user/login",
    CURLOPT_RETURNTRANSFER => true
    ] );
    $return_data = json_decode(curl_exec($curl), true);
    curl_close($curl);
    if($return_data['errno'] > 0){
      return false;
    }else{
      return $return_data['result']['token'];
    }
  }
}
