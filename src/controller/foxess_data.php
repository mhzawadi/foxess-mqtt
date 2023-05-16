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
    } catch (Exception $e) {
      $this->log('Missing config: '.$e->getMessage());
    }

    $this->log('Start of work');
    # load the json data from file
    $this->foxess_data = $this->load_from_file('data/foxess_data.json');
    if($this->foxess_data['setup'] < time()){
      $this->foxess_data['setup'] = $this->mqtt->setup_mqtt($this->foxess_data);
    }
    $data_run = 2;
    while($data_run > 1){
      $data_run = $this->collect_data();
    }

    if($data_run === 0){
      $this->data->process_data($this->foxess_data, $this->collected_data);
    }

    $this->log("Work complete");
  }



  /**
   * Collect data from Foxess Cloud
   *
   * use curl to collect the latest data from Foxes Cloud
   *
   */
  protected function collect_data() {
    $this->log('Collect data from the cloud');
    $data = '{
        "deviceID": "'.$this->config->device_id.'",
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
    $this->save_to_file('data/collected.json', $return_data);
    if(is_null($return_data) === false){
      if($return_data['errno'] == 40401){
        $this->log('Too many logins');
        return 1;
      }elseif($return_data['errno'] == 41809 ||
              $return_data['errno'] == 41808){
        $this->log('we need to login again');
        if($this->foxess_data['token'] = $this->login->login()){
          $this->log('login complte');
          return 2;
        }else{
          $this->log('login error');
          return 1;
        }
      }elseif($return_data['errno'] > 0){
        $this->log('We have an error getting data, we have logged in fine');
        $this->log('Error: '.$return_data['errno']);
        return 1;
      }else{
        $this->log('We have the data, ready to process');
      }
    }else{
      $this->log('We have an error getting data, the file is empty');
      return 1;
    }
    $this->collected_data = $return_data;
    $this->log('Data collected');
    return 0;
  }
}
