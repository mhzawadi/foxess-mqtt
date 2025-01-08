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
  protected $error_codes;
  public function __construct($config){
    $this->config = $config;
    $this->request = new request($config);
    $this->mqtt  = new mqtt($config);
    $this->redis   = new mhredis($config);
    $this->error_codes = $this->redis->get('error_codes');
  }

  /**
   * Collect data from Foxess Cloud
   *
   * use curl to collect the latest data from Foxes Cloud
   *
   */
  public function collect_data($foxess_data, $device) {
    $this->log('Collect data from the cloud', 1);
    $deviceSN = $foxess_data['devices'][$device]['deviceSN'];
    $data = '{
        "sn": "'.$deviceSN.'",
        "variables": '.json_encode($foxess_data['devices'][$device]['variable_list']).'
    }';
    $url ='/op/v0/device/real/query';
    $this_curl = $this->request->sign_post($url, $data, $this->config->foxess_lang);
    if($this->request->getinfo() === false){
      return false;
    }
    $return_data = json_decode($this_curl, true);
    if(empty($this_curl) ){
      $this->log('Issue getting data', 3);
      return false;
    }elseif($return_data['errno'] > 0 ){
      $this->log($this->error_codes[$return_data['errno']].'; code: '.$return_data['errno'], 3);
      return false;
    }else{
      $this->redis->set($deviceSN.'_collected', $return_data);
      $collected_data = $return_data;
      $this->log('Data collected', 1);
      return $collected_data;
    }
  }

  public function update_mqtt($foxess_data, $collected_data){
    for( $device = 0; $device < $foxess_data['device_total']; $device++ ){ //loop over devices
      $options_count = count($collected_data[$device]['result'][0]['datas']);
      for( $i = 0 ; $i < $options_count; $i++ ){ //for each value
        $name = $collected_data[$device]['result'][0]['datas'][$i]['variable'];
        if(isset($collected_data[$device]['result'][0]['datas'][$i]['unit'])){
          $unit = $collected_data[$device]['result'][0]['datas'][$i]['unit'];
        }else{
          $unit = false;
        }
        if($unit !== false){
          switch($name){
            case 'feedinPower':
              $this->mqtt->setup_mqtt($foxess_data['devices'][$device]['deviceSN'], $foxess_data['devices'][$device]['deviceType'], $name.'_kwh', 'kWh');
              if(!isset($foxess_data['devices'][$device]['variables'][$name.'_kwh'])){
                $foxess_data['devices'][$device]['variables'][$name.'_kwh'] = 0;
              }
              break;
            case 'gridConsumptionPower':
              $this->mqtt->setup_mqtt($foxess_data['devices'][$device]['deviceSN'], $foxess_data['devices'][$device]['deviceType'], $name.'_kwh', 'kWh');
              if(!isset($foxess_data['devices'][$device]['variables'][$name.'_kwh'])){
                $foxess_data['devices'][$device]['variables'][$name.'_kwh'] = 0;
              }
              break;
            }
            $this->mqtt->setup_mqtt($foxess_data['devices'][$device]['deviceSN'], $foxess_data['devices'][$device]['deviceType'], $name, $unit);
        }else{
          $this->mqtt->setup_mqtt($foxess_data['devices'][$device]['deviceSN'], $foxess_data['devices'][$device]['deviceType'], $name, null);
        }
        if(!isset($foxess_data['devices'][$device]['variables'][$name])){
          $foxess_data['devices'][$device]['variables'][$name] = 0;
        }
      }
    }
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
    $this->log('Start of processing the data', 1);
    for( $device = 0; $device < $foxess_data['device_total']; $device++ ){ //loop over devices
      if(empty($collected_data[$device])){ // Did we get any data
        $this->log('Data from the cloud is empty', 3);
      }else{ // Yes we did
        $options_count = count($collected_data[$device]['result'][0]['datas']);
        $deviceSN = $foxess_data['devices'][$device]['deviceSN'];
        for( $i = 0 ; $i < $options_count; $i++ ){ //for each value
          $option = $collected_data[$device]['result'][0]['datas'][$i]['variable'];
          $name   = $collected_data[$device]['result'][0]['datas'][$i]['variable'];
          $this->log('Value name: '.$name, 1);
          if(strstr($option, 'Temperature') !== false || strstr($option, 'SoC') !== false
             || strstr($option, 'Volt') !== false || strstr($option, 'Current') !== false ||
             strstr($option, 'Temperation') !== false
            ){ // list of non-KW/KWh
            if($collected_data[$device]['result'] == 'null'){
              $value = 0;
            }else{
              if(isset($collected_data[$device]['result'][0]['datas'][$i]['value'])){
                $value = abs($collected_data[$device]['result'][0]['datas'][$i]['value']);
              }else{
                $value = 0;
              }
              $this->mqtt->post_mqtt(''.$mqtt_topic.'/'.$deviceSN.'/'.$name, $value);
              $foxess_data['devices'][$device]['variables'][$name] = $value;
              $this->redis->set('foxess_data', $foxess_data);
              $this->log('Post '.$value.' of '.$name.' to MQTT', 1);

            }
          }elseif(strstr($option, 'runningState') !== false){ // only runningState
            $data = $collected_data[$device]['result'][0]['datas'][$i];
            $this->mqtt->post_mqtt(''.$mqtt_topic.'/'.$deviceSN.'/'.$name, $data['value']);
            $this->log('Post '.$data['value'].' of '.$name.' to MQTT', 1);
          }elseif(array_key_exists('unit', $collected_data[$device]['result'][0]['datas'][$i]) === false){ // Text values
            $data = $collected_data[$device]['result'][0]['datas'][$i];
            $this->mqtt->post_mqtt(''.$mqtt_topic.'/'.$deviceSN.'/'.$name, $data['value']);
            $this->log('Post '.$data['value'].' of '.$name.' to MQTT', 1);
          }else{ // KW/KWh
            if($collected_data[$device]['result'] == 'null'){
              $value_kw = 0;
              $value_kwh = $total_over_time ? $foxess_data['devices'][$device]['variables'][$i] : 0;
            }else{
              $data = $collected_data[$device]['result'][0]['datas'][$i];
              $name = $collected_data[$device]['result'][0]['datas'][$i]['variable'];
              if(isset($data['value'])){
                $value_kw = $data['value'];
              }else{
                $value_kw = 0;
              }
              switch($option){ // if we have an export or import
                case 'feedinPower': // export
                  $sum = ($data['value']*0.01);// convert to KWh/min
                  $over_time = ($sum+$foxess_data['devices'][$device]['variables'][$name.'_kwh']);
                  $foxess_data['devices'][$device]['variables'][$name.'_kwh'] = $over_time;
                  $this->mqtt->post_mqtt(''.$mqtt_topic.'/'.$deviceSN.'/'.$name.'_kwh', abs(round($over_time, 2)));
                  $this->log('Post '.$over_time.'KWh of '.$name.' to MQTT', 1);
                  break;
                case 'gridConsumptionPower': // import
                  $sum = ($data['value']*0.01);// convert to KWh/min
                  $over_time = ($sum+$foxess_data['devices'][$device]['variables'][$name.'_kwh']);
                  $foxess_data['devices'][$device]['variables'][$name.'_kwh'] = $over_time;
                  $this->mqtt->post_mqtt(''.$mqtt_topic.'/'.$deviceSN.'/'.$name.'_kwh', abs(round($over_time, 2)));
                  $this->log('Post '.$over_time.'KWh of '.$name.' to MQTT', 1);
                  break;
              }
            }
            $this->mqtt->post_mqtt(''.$mqtt_topic.'/'.$deviceSN.'/'.$name, abs(round($value_kw, 2)));
            $this->log('Post '.$value_kw.'kw of '.$name.' to MQTT', 1);
            $foxess_data['devices'][$device]['variables'][$name] = $value_kw;
            $this->redis->set('foxess_data', $foxess_data);
          }
        }
        $this->log('Data procssed and posted to MQTT', 1);
      } // check for data end
    } // device end
  }
}
