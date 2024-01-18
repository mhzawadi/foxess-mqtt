<?php

// TODO: Get device list -> return (https://www.foxesscloud.com/public/i18n/en/OpenApiDocument.html#get20device20list0a3ca20id3dget20device20list4303e203ca3e)
// TODO: process_data

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
  public function __construct($config){
    $this->config = $config;
    $this->request = new request($config);
  }

  /**
   * Collect data from Foxess Cloud
   *
   * use curl to collect the latest data from Foxes Cloud
   *
   */
  public function collect_data($foxess_data, $device) {
    $this->log('Collect data from the cloud', 1, 3);
    $deviceSN = $foxess_data['devices'][$device]['deviceSN'];
    $data = '{
        "sn": "'.$deviceSN.'",
        "variables": '.json_encode($foxess_data['devices'][$device]['variable_list']).'
    }';
    $url ='/op/v0/device/real/query';
    $this_curl = $this->request->sign_post($url, $data, $this->config->foxess_lang);
    $return_data = json_decode($this_curl, true);
    if(empty($this_curl) ){
      $this->log('Issue getting error codes', 3, 2);
      return false;
    }elseif($return_data['errno'] > 0 ){
      $this->log($this->config->errno($return_data['errno']), 3, 2);
      return false;
    }else{
      $this->save_to_file('data/'.$deviceSN.'_collected.json', $return_data);
      $collected_data = $return_data;
      $this->log('Data collected', 1, 3);
      return $collected_data;
    }
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
    $this->log('Start of processing the data', 1, 3);
    for( $device = 0; $device < $foxess_data['device_total']; $device++ ){ //loop over devices
      $options_count = count($collected_data[$device]['result'][0]['datas']);
      $deviceSN = $foxess_data['devices'][$device]['deviceSN'];
      for( $i = 0 ; $i < $options_count; $i++ ){ //for each value
        $option = $collected_data[$device]['result'][0]['datas'][$i]['variable'];
        $name = $collected_data[$device]['result'][0]['datas'][$i]['variable'];
        $this->log($name,1,2);
        if(strstr($option, 'Temperature') !== false || strstr($option, 'SoC') !== false
           || strstr($option, 'Volt') !== false || strstr($option, 'Current') !== false ||
           strstr($option, 'Temperation') !== false
          ){
          if($collected_data[$device]['result'] == 'null'){
            $value = 0;
          }else{
            $value = abs($collected_data[$device]['result'][0]['datas'][$i]['value']);
            $this->mqtt->post_mqtt(''.$mqtt_topic.'/'.$deviceSN.'/'.$name, $value);
            $foxess_data['devices'][$device]['variables'][$name] = $value;
            $this->save_to_file('data/foxess_data.json', $foxess_data);
            $this->log('Post '.$value.' of '.$name.' to MQTT', 1, 3);

          }
        }elseif(strstr($option, 'runningState') !== false){
          $data = $collected_data[$device]['result'][0]['datas'][$i];
          switch($data['value']){
            case "165":
              $data['value'] = "fault";
              break;
            case "166":
              $data['value'] = "permanent-fault";
              break;
            case "167":
              $data['value'] = "standby";
              break;
            case "168":
              $data['value'] = "upgrading";
              break;
            case "169":
              $data['value'] = "fct";
              break;
            case "170":
              $data['value'] = "illegal";
              break;
            case "160":
              $data['value'] = "self-test";
              break;
            case "161":
              $data['value'] = "waiting";
              break;
            case "162":
              $data['value'] = "checking";
              break;
            case "163":
              $data['value'] = "on-grid";
              break;
            case "164":
              $data['value'] = "off-grid";
              break;
          }
          $this->mqtt->post_mqtt(''.$mqtt_topic.'/'.$deviceSN.'/'.$name, $data['value']);
          $this->log('Post '.$data['value'].' of '.$name.' to MQTT', 1, 3);
        }else{
          if($collected_data[$device]['result'] == 'null'){
            $value_kw = 0;
            $value_kwh = $total_over_time ? $foxess_data['devices'][$device]['variables'][$i] : 0;
          }else{
            $data = $collected_data[$device]['result'][0]['datas'][$i];
            $name = $collected_data[$device]['result'][0]['datas'][$i]['variable'];
            $value_kw = $data['value'];
            // if(strstr($option, 'generationPower') !== false){ //Returns false or int
            //   $value_kwh = $total_over_time ? $foxess_data['devices'][$device]['generationTotal'] : $foxess_data['devices'][$device]['variables'][$i];
            // }else{
            //   $sum = ($data['value']*0.05);
            //   $value_kwh = $total_over_time ? ($foxess_data['devices'][$device]['variables'][$name] + $sum) : $sum;
            // }
          }
          $this->mqtt->post_mqtt(''.$mqtt_topic.'/'.$deviceSN.'/'.$name, abs(round($value_kw, 2)));
          $this->log('Post '.$value_kw.'kw of '.$name.' to MQTT', 1, 3);

          $foxess_data['devices'][$device]['variables'][$name] = $value_kw;
          $this->save_to_file('data/foxess_data.json', $foxess_data);
          // $this->mqtt->post_mqtt(''.$mqtt_topic.'/'.$deviceSN.'/'.$name.'_kwh', abs(round($value_kwh, 2)));
          // $this->log('Post '.$value_kwh.'kwh of '.$name.' to MQTT', 1, 3);
        }
      }
      $this->log('Data procssed and posted to MQTT', 1, 3);
    } // device
  }
}
