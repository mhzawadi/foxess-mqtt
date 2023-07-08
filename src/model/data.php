<?php

namespace MHorwood\foxess_mqtt\model;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\classes\logger;
use MHorwood\foxess_mqtt\model\mqtt;
use MHorwood\foxess_mqtt\model\login;

class data extends json {

  protected $mqtt;
  protected $login;

  /**
   * Get the list of devices
   **/
  public function device_list(){
    $this->log('start of device listing', 2);
    $this->login = new login();
    $foxess_data = $this->load_from_file('data/foxess_data.json');
    $data = '{
        "pageSize": 10,
        "currentPage": 1,
        "total": 0,
        "condition": {
            "queryDate": {
                "begin": 0,
                "end": 0
            }
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
        "token: ".$foxess_data['token'],
        "Content-Type: application/json"
      )
    );
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt_array ( $curl , [
    CURLOPT_URL => "https://www.foxesscloud.com/generic/v0/device/list",
    CURLOPT_RETURNTRANSFER => true
    ] );
    $return_data = json_decode(curl_exec($curl), true);
    if($return_data['errno'] > 0){
      return false;
    }else{
      $this->save_to_file('data/devices.json', $return_data);

      $this->log('storing devices', 3);
      $foxess_data['device_total'] = $return_data['result']['total'];
      for( $device = 0; $device < $return_data['result']['total']; $device++ ){
        if(!is_array($foxess_data['devices'][$device])){
          $foxess_data['devices'][$device] = $return_data['result']['devices'][$device];
          $foxess_data['devices'][$device]['variables'] = $foxess_data['result'];
        }
      }
      $this->save_to_file('data/foxess_data.json', $foxess_data);
      $this->log('all done', 3);
      return true;
    }
  }

  /**
   * Process data and pass to MQTT
   *
   * Undocumented function long description
   *
   * @return return type
   */
  public function process_data($foxess_data, $collected_data)  {
    $this->mqtt  = new mqtt();
    $this->log('Start of processing the data', 3);
    for( $device = 0; $device < $foxess_data['device_total']; $device++ ){ //loop over devices
      $options_count = count($collected_data[$device]['result']);
      $deviceSN = $foxess_data['devices'][$device]['deviceSN'];
      for( $i = 0 ; $i < $options_count; $i++ ){ //for each value
        $option = $collected_data[$device]['result'][$i]['variable'];
        $name = $collected_data[$device]['result'][$i]['variable'];
        if(strstr($option, 'Temperature') !== false || strstr($option, 'SoC') !== false
           || strstr($option, 'Volt') !== false || strstr($option, 'Current') !== false ||
           strstr($option, 'Temperation') !== false
          ){
          $this->log($name);
          if($collected_data[$device]['result'] == 'null'){
            $value = 0;
          }else{
            $data = end($collected_data[$device]['result'][$i]['data']);
            if(is_array($data) && substr($data['time'], 0, 13) == date('Y-m-d H')){
                $value = abs($data['value']);
            }else{
              $value = 0;
            }
            $this->mqtt->post_mqtt('foxesscloud/'.$deviceSN.'/'.$name, $value);
            $foxess_data['devices'][$device]['variables'][$name] = $value;
            $this->save_to_file('data/foxess_data.json', $foxess_data);
            $this->log('Post '.$value.' of '.$name.' to MQTT', 3);

          }
        }else{
          if($collected_data[$device]['result'] == 'null'){
            $value_kw = 0;
            $value_kwh = $foxess_data['devices'][$device]['variables'][$i];
          }else{
            $data = end($collected_data[$device]['result'][$i]['data']);
            $name = $collected_data[$device]['result'][$i]['variable'];
            if(is_array($data) && substr($data['time'], 0, 13) == date('Y-m-d H')){
              $value_kw = $data['value'];
              if(strstr($option, 'generationPower') !== false){ //Returns false or int
                $value_kwh = $foxess_data['devices'][$device]['generationToday'];
              }else{
                $sum = ($data['value']*0.05);
                $value_kwh = ($foxess_data['devices'][$device]['variables'][$name] + $sum);
              }
            }else{
              $value_kw = 0;
              if(strstr($option, 'generationPower') !== false){ //Returns false or int
                $value_kwh = $foxess_data['devices'][$device]['generationToday'];
              }else{
                $value_kwh = $foxess_data['devices'][$device]['variables'][$name];
              }
            }
          }
          $this->mqtt->post_mqtt('foxesscloud/'.$deviceSN.'/'.$name, abs(round($value_kw, 2)));
          $this->log('Post '.$value_kw.'kw of '.$name.' to MQTT', 3);

          $foxess_data['devices'][$device]['variables'][$name] = $value_kwh;
          $this->save_to_file('data/foxess_data.json', $foxess_data);
          $this->mqtt->post_mqtt('foxesscloud/'.$deviceSN.'/'.$name.'_kwh', abs(round($value_kwh, 2)));
          $this->log('Post '.$value_kwh.'kwh of '.$name.' to MQTT', 3);
        }
      }
      $this->log('Data procssed and posted to MQTT', 3);
    } // device
  }
}
