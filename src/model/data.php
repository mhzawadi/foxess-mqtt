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
    foreach($collected_data as $device){ //for each device
      print_r($device);
      // $options_count = count($collected_data['result']);
      // for( $i = 0 ; $i < $options_count; $i++ ){ //for each value
      //   $option = $collected_data['result'][$i]['variable'];
      //   $name = $collected_data['result'][$i]['variable'];
      //   if(strstr($option, 'Temperature') !== false || strstr($option, 'SoC') !== false
      //      || strstr($option, 'Volt') !== false || strstr($option, 'Current') !== false ||
      //      strstr($option, 'Temperation') !== false
      //     ){
      //     $this->log($name);
      //     if($collected_data['result'] == 'null'){
      //       $value = 0;
      //     }else{
      //       $data = end($collected_data['result'][$i]['data']);
      //       if(is_array($data) && substr($data['time'], 0, 13) == date('Y-m-d H')){
      //           $value = abs($data['value']);
      //       }else{
      //         $value = 0;
      //       }
      //       $this->mqtt->post_mqtt('foxesscloud/'.$name, $value);
      //       $foxess_data['result'][$name] = $value;
      //       $this->save_to_file('data/foxess_data.json', $foxess_data);
      //       $this->log('Post '.$value.' of '.$name.' to MQTT', 3);
      //
      //     }
      //   }else{
      //     if($collected_data['result'] == 'null'){
      //       $value_kw = 0;
      //       $value_kwh = $foxess_data['result'][$i];
      //     }else{
      //       $data = end($collected_data['result'][$i]['data']);
      //       $name = $collected_data['result'][$i]['variable'];
      //       if(is_array($data) && substr($data['time'], 0, 13) == date('Y-m-d H')){
      //         $value_kw = abs(round($data['value'], 2, PHP_ROUND_HALF_DOWN));
      //         $sum = round(($data['value']*0.08), 2, PHP_ROUND_HALF_DOWN);
      //         $value_kwh = abs(round(($foxess_data['result'][$name] + $sum), 2, PHP_ROUND_HALF_DOWN));
      //       }else{
      //         $value_kw = 0;
      //         $value_kwh = abs($foxess_data['result'][$name]);
      //       }
      //     }
      //     $this->mqtt->post_mqtt('foxesscloud/'.$name, $value_kw);
      //     $this->log('Post '.$value_kw.'kw of '.$name.' to MQTT', 3);
      //
      //     $foxess_data['result'][$name] = $value_kwh;
      //     $this->save_to_file('data/foxess_data.json', $foxess_data);
      //     $this->mqtt->post_mqtt('foxesscloud/'.$name.'_kwh', $value_kwh);
      //     $this->log('Post '.$value_kwh.'kwh of '.$name.' to MQTT', 3);
      //   }
      // }
      // $this->log('Data procssed and posted to MQTT', 3);
    } // device
  }
}
