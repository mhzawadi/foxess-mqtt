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
  public function process_data($foxess_data, $collected_data)  {
    $this->mqtt  = new mqtt();
    echo 'Start of processing the data'."\n";
    $options_count = count($collected_data['result']);
    for( $i = 0 ; $i < $options_count; $i++ ){
      $option = $collected_data['result'][$i]['variable'];
      if($collected_data['result'] == 'null'){
        $value_kw = 0;
        $value_kwh = $foxess_data['result'][$i];
      }else{
        $data = end($collected_data['result'][$i]['data']);
        $name = $collected_data['result'][$i]['variable'];
        if(substr($data['time'], 0, 13) == date('Y-m-d H')){
          $value_kw = round($data['value'], 2, PHP_ROUND_HALF_DOWN);
          $value_kwh = round(($foxess_data['result'][$name] + $data['value']), 2, PHP_ROUND_HALF_DOWN);
        }else{
          $value_kw = 0;
          $value_kwh = $foxess_data['result'][$name];
        }
      }
      $this->mqtt->post_mqtt('foxesscloud/'.$name, $value_kw);

      $foxess_data['result'][$name] = $value_kwh;
      $this->save_to_file('data/foxess_data.json', $foxess_data);
      $this->mqtt->post_mqtt('foxesscloud/'.$name.'_kwh', $value_kwh);
      echo 'Posted data to MQTT'."\n";
    }
    echo 'Data procssed and posted to MQTT'."\n";
  }
}
