<?php

namespace MHorwood\foxess_mqtt\model;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\model\mqtt;

class data extends json {

  protected $mqtt;

  /**
   * Process data and pass to MQTT
   *
   * Undocumented function long description
   *
   * @return return type
   */
  protected function process_data($foxess_data, $collected_data)  {
    $this->mqtt  = new mqtt();
    echo 'Start of processing the data'."\n";
    $options_count = count($collected_data['result']);
    print_r($options_count);
    for( $i = 0 ; $i < $options_count; $i++ ){
      $option = $collected_data['result'][$i]['variable'];
      if($collected_data['result'] == 'null'){
        $value_kw = 0;
        $value_kwh = $foxess_data['result'][$i];
      }else{
        $value_data = count($collected_data['result'][$i]['data']);
        $data = $collected_data['result'][$i][$i]['data'][$value_data];
        if($data['time'] == date('Y-m-d H')){
          $value_kw = $data['value'];
          $value_kwh = round(float($foxess_data['result'][$i] + $data['value']), 2);
        }else{
          $value_kw = 0;
          $value_kwh = $foxess_data['result'][$i];
        }
      }
      $this->mqtt->post_mqtt('foxesscloud/'.$collected_data['result'][$i]['variable'], $value_kw);

      $foxess_data['result'][$data_name] = $value_kwh;
      $this->save_to_file('data/foxess_data.json', $foxess_data);
      $this->mqtt->post_mqtt('foxesscloud/'.$data_name.'_kwh', $value_kwh);
    }
    echo 'Data procssed and posted to MQTT'."\n";
  }
}
