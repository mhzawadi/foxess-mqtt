<?php

namespace MHorwood\foxess_mqtt\controller;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\classes\logger;
use MHorwood\foxess_mqtt\model\data;
use MHorwood\foxess_mqtt\model\mqtt;
use MHorwood\foxess_mqtt\model\login;
use MHorwood\foxess_mqtt\model\config;

class foxess_data extends json {

  protected $foxess_data;
  protected $collected_data;
  protected $login;
  protected $mqtt;
  protected $data;
  protected $config;

  public function __construct(){
    $this->login = new login();
    $this->data  = new data();
    $this->mqtt  = new mqtt();
    try {
      $this->config = new config();
      $this->config->get_error_codes();
    } catch (Exception $e) {
      $this->log('Missing config: '.$e->getMessage(), 1);
    }
    $this->foxess_data = $this->load_from_file('data/foxess_data.json');

    $check_login = false;
    while($check_login === false){
      $check_login = $this->login->check_login($this->foxess_data['token']);
      if($check_login === false){
        $this->foxess_data['token'] = $this->login->login();
      }
    }
    if($check_login !== true){
      exit($check_login);
    }else{
      $this->save_to_file('data/foxess_data.json', $this->foxess_data);
    }
    if( $this->data->device_list() === true ){
      $this->foxess_data = $this->load_from_file('data/foxess_data.json');
    }else{
      $this->log("issues getting devices", 2);
    }
    if($this->foxess_data['setup'] < time()){
      $this->foxess_data['setup'] = $this->mqtt->setup_mqtt($this->foxess_data);
    }

    for( $device = 0; $device < $this->foxess_data['device_total']; $device++ ){//for each device
      $this->collect_data($device);
    }

    $this->data->process_data($this->foxess_data, $this->collected_data);
    $this->log("Work complete", 2);
  }

  /**
   * Collect data from Foxess Cloud
   *
   * use curl to collect the latest data from Foxes Cloud
   *
   */
  protected function collect_data($device) {
    $this->log('Collect data from the cloud', 3);
    $deviceSN = $this->foxess_data['devices'][$device]['deviceSN'];
    $data = '{
        "deviceID": "'.$this->foxess_data['devices'][$device]['deviceID'].'",
        "variables": '.json_encode($this->foxess_data['variables']).',
        "timespan": "hour",
        "beginDate": {
            "year": '.date("Y").',
            "month": '.date("n").',
            "day": '.date("j").',
            "hour": '.date("G").',
            "minute": 0,
            "second": 0
        }
    }';
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
        "token: ".$this->foxess_data['token'],
        "Content-Type: application/json"
      )
    );
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt_array ( $curl , [
    CURLOPT_URL => "https://www.foxesscloud.com/c/v0/device/history/raw",
    CURLOPT_RETURNTRANSFER => true
    ] );
    $return_data = json_decode(curl_exec($curl), true);
    $this->save_to_file('data/'.$deviceSN.'_collected.json', $return_data);
    $this->collected_data[$device] = $return_data;
    $this->log('Data collected', 3);
  }
}
