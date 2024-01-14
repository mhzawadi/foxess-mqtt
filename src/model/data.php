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
  public function __construct(){
    try {
      $this->config = new config();
    } catch (Exception $e) {
      $this->log('Missing config: '.  $e->getMessage(), 3, 1);
    }
  }

  /**
   * Collect data from Foxess Cloud
   *
   * use curl to collect the latest data from Foxes Cloud
   *
   */
  public function collect_data($device) {
    $this->log('Collect data from the cloud', 1, 3);
    $deviceSN = $this->foxess_data['devices'][$device]['deviceSN'];
    $data = '{
        "sn": "'.$deviceSN.'",
        "variables": '.json_encode($this->foxess_data['devices'][$device]['variable_list']).'
    }';
    $url ='/op/v0/device/real/query';
    $this_curl = $this->request->sign_post($url, $data, $this->config->foxess_lang);
    $return_data = json_decode(curl_exec($curl), true);
    $this->save_to_file('data/'.$deviceSN.'_collected.json', $return_data);
    $this->collected_data[$device] = $return_data;
    $this->log('Data collected', 1, 3);
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
      $options_count = count($collected_data[$device]['result']);
      $deviceSN = $foxess_data['devices'][$device]['deviceSN'];
      for( $i = 0 ; $i < $options_count; $i++ ){ //for each value
        $option = $collected_data[$device]['result'][$i]['variable'];
        $name = $collected_data[$device]['result'][$i]['variable'];
        $this->log($name,1,2);
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
            $this->log('Post '.$value.' of '.$name.' to MQTT', 1, 3);

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
          $this->log('Post '.$value_kw.'kw of '.$name.' to MQTT', 1, 3);

          $foxess_data['devices'][$device]['variables'][$name] = $value_kwh;
          $this->save_to_file('data/foxess_data.json', $foxess_data);
          $this->mqtt->post_mqtt(''.$mqtt_topic.'/'.$deviceSN.'/'.$name.'_kwh', abs(round($value_kwh, 2)));
          $this->log('Post '.$value_kwh.'kwh of '.$name.' to MQTT', 1, 3);
        }
      }
      $this->log('Data procssed and posted to MQTT', 1, 3);
    } // device
  }
}
