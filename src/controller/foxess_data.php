<?php

// Public API - https://www.foxesscloud.com/public/i18n/en/OpenApiDocument.html

// TODO: Get config and check for API KEY [x]
// TODO: Get device list (https://www.foxesscloud.com/public/i18n/en/OpenApiDocument.html#get20device20list0a3ca20id3dget20device20list4303e203ca3e)
// TODO: Get device real-time data (https://www.foxesscloud.com/public/i18n/en/OpenApiDocument.html#get20device20real-time20data0a3ca20id3dget20device20real-time20data4303e203ca3e)
// TODO: process_data to load to MQTT

namespace MHorwood\foxess_mqtt\controller;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\classes\logger;
use MHorwood\foxess_mqtt\model\data;
use MHorwood\foxess_mqtt\model\device;
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
    try {
      $this->config = new config();
    } catch (Exception $e) {
      $this->log('Missing config: '.$e->getMessage(), 3, 1);
      exit(1);
    }
    $this->data  = new data($this->config);
    $this->device  = new device($this->config);
    $this->mqtt  = new mqtt($this->config);
    $errors = $this->device->get_error_codes();
    if ($errors){
      $this->log('Issue with comms to FoxESS cloud', 3, 1);
      exit(1);
    }
    $this->foxess_data = $this->load_from_file('data/foxess_data.json');

    if($this->foxess_data['setup'] < time()){
      $this->log('Update MQTT and device list', 1, 2);
      if( $this->device->list() === true ){
        $this->foxess_data['setup'] = $this->mqtt->setup_mqtt($this->foxess_data);
        $this->save_to_file('data/foxess_data.json', $foxess_data);
      }else{
        $this->log('Issues getting devices', 3, 2);
      }
    }

    for( $device = 0; $device < $this->foxess_data['device_total']; $device++ ){//for each device
      $this->collected_data[$device] = $this->data->collect_data($this->foxess_data, $device);
    }

    $this->data->process_data($this->config->mqtt_topic, $this->foxess_data, $this->collected_data, $this->config->total_over_time);
    $this->log("Work complete", 1, 2);
  }

}
