<?php

namespace MHorwood\foxess_mqtt\model;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\classes\logger;
use MHorwood\foxess_mqtt\model\mqtt;
use MHorwood\foxess_mqtt\model\request;
use MHorwood\foxess_mqtt\model\config;

class data extends json {

  protected $mqtt;
  protected $request;
  protected $config;
  public function __construct(){
    try {
      $this->config = new config();
    } catch (Exception $e) {
      $this->log('Missing config: '.  $e->getMessage(), 1);
    }
  }

  /**
   * Get the list of devices
   **/
  public function device_list(){
    $this->log('start of device listing', 2);
    $this->request = new request();
    $foxess_data = $this->load_from_file('data/foxess_data.json');
    $data = '{"variables": ["generationPower"]}';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HTTPHEADER, $this->request->get_signature($this->config->foxess_apikey, '/op/v0/device/real/query', 'en') );
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt_array ( $curl , [
      CURLOPT_URL => "https://www.foxesscloud.com/op/v0/device/real/query",
      CURLOPT_RETURNTRANSFER => true
    ] );
    $this_curl = curl_exec($curl);
    print_r($this_curl);
    $return_data = json_decode($this_curl, true);
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
        }else{
          $foxess_data['devices'][$device]['generationTotal'] = $return_data['result']['devices'][$device]['generationTotal'];
          $foxess_data['devices'][$device]['generationToday'] = $return_data['result']['devices'][$device]['generationToday'];
        }
      }
      $this->save_to_file('data/foxess_data.json', $foxess_data);
      $this->log('all done', 3);
      $this->variable_list();
      return true;
    }
  }

  /**
   * get device variables
   *
   * Undocumented function long description
   *
   * @param type var Description
   * @return return true
   */
  public function variable_list(){
    $this->log('start of variable listing', 2);
    $this->login = new login();
    $foxess_data = $this->load_from_file('data/foxess_data.json');
    for( $device = 0; $device < $foxess_data['device_total']; $device++ ){//for each device
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
      curl_setopt_array ( $curl , [
      CURLOPT_URL => "https://www.foxesscloud.com/c/v1/device/variables?deviceID=".$foxess_data['devices'][$device]['deviceID'],
      CURLOPT_RETURNTRANSFER => true
      ] );
      $return_data = json_decode(curl_exec($curl), true);
      if($return_data['errno'] > 0){
        $this->log('error getting variables, file not saved', 3);
        return false;
      }else{
        $this->save_to_file('data/'.$foxess_data['devices'][$device]['deviceSN'].'-variables.json', $return_data);
        $variables = $return_data['result']['variables'];
        $var_count = count($variables);
        $this->log('storing variables', 3);
        $foxess_data['devices'][$device]['variable_list'] = array();
        for( $i = 0 ; $i < $var_count; $i++ ){
          $foxess_data['devices'][$device]['variable_list'][$i] = $variables[$i]['variable'];
          if(!isset($foxess_data['devices'][$device]['variables'][$variables[$i]['variable']])){
            $foxess_data['devices'][$device]['variables'][$variables[$i]['variable']] = 0;
          }
        }
      }
    }
    $this->save_to_file('data/foxess_data.json', $foxess_data);
    $this->log('all done', 3);
    return true;
  }

  /**
   * Process data and pass to MQTT
   *
   * Undocumented function long description
   *
   * @return return type
   */
  public function process_data($mqtt_topic, $foxess_data, $collected_data, $total_over_time)  {
    $this->mqtt  = new mqtt();
    $this->log('Start of processing the data', 3);
    for( $device = 0; $device < $foxess_data['device_total']; $device++ ){ //loop over devices
      $options_count = count($collected_data[$device]['result']);
      $deviceSN = $foxess_data['devices'][$device]['deviceSN'];
      for( $i = 0 ; $i < $options_count; $i++ ){ //for each value
        $option = $collected_data[$device]['result'][$i]['variable'];
        $name = $collected_data[$device]['result'][$i]['variable'];
        $this->log($name);
        if(strstr($option, 'Temperature') !== false || strstr($option, 'SoC') !== false
           || strstr($option, 'Volt') !== false || strstr($option, 'Current') !== false ||
           strstr($option, 'Temperation') !== false
          ){
          if($collected_data[$device]['result'] == 'null'){
            $value = 0;
          }else{
            $data = end($collected_data[$device]['result'][$i]['data']);
            if(is_array($data) && substr($data['time'], 0, 13) == date('Y-m-d H')){
                $value = abs($data['value']);
            }else{
              $value = 0;
            }
            $this->mqtt->post_mqtt(''.$mqtt_topic.'/'.$deviceSN.'/'.$name, $value);
            $foxess_data['devices'][$device]['variables'][$name] = $value;
            $this->save_to_file('data/foxess_data.json', $foxess_data);
            $this->log('Post '.$value.' of '.$name.' to MQTT', 3);

          }
        }else{
          if($collected_data[$device]['result'] == 'null'){
            $value_kw = 0;
            $value_kwh = $total_over_time ? $foxess_data['devices'][$device]['variables'][$i] : 0;
          }else{
            $data = end($collected_data[$device]['result'][$i]['data']);
            $name = $collected_data[$device]['result'][$i]['variable'];
            if(is_array($data) && substr($data['time'], 0, 13) == date('Y-m-d H')){
              $value_kw = $data['value'];
              if(strstr($option, 'generationPower') !== false){ //Returns false or int
                $value_kwh = $total_over_time ? $foxess_data['devices'][$device]['generationTotal'] : $foxess_data['devices'][$device]['variables'][$i];
              }else{
                $sum = ($data['value']*0.05);
                $value_kwh = $total_over_time ? ($foxess_data['devices'][$device]['variables'][$name] + $sum) : $sum;
              }
            }else{
              $value_kw = 0;
              if(strstr($option, 'generationPower') !== false){ //Returns false or int
                $value_kwh = $total_over_time ? $foxess_data['devices'][$device]['generationTotal'] : 0;
              }else{
                $value_kwh = $total_over_time ? $foxess_data['devices'][$device]['variables'][$i] : 0;
              }
            }
          }
          $this->mqtt->post_mqtt(''.$mqtt_topic.'/'.$deviceSN.'/'.$name, abs(round($value_kw, 2)));
          $this->log('Post '.$value_kw.'kw of '.$name.' to MQTT', 3);

          $foxess_data['devices'][$device]['variables'][$name] = $value_kwh;
          $this->save_to_file('data/foxess_data.json', $foxess_data);
          $this->mqtt->post_mqtt(''.$mqtt_topic.'/'.$deviceSN.'/'.$name.'_kwh', abs(round($value_kwh, 2)));
          $this->log('Post '.$value_kwh.'kwh of '.$name.' to MQTT', 3);
        }
      }
      $this->log('Data procssed and posted to MQTT', 3);
    } // device
  }
}
