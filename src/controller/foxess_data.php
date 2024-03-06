<?php

// Public API - https://www.foxesscloud.com/public/i18n/en/OpenApiDocument.html

namespace MHorwood\foxess_mqtt\controller;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\classes\logger;
use MHorwood\foxess_mqtt\model\data;
use MHorwood\foxess_mqtt\model\device;
use MHorwood\foxess_mqtt\model\mqtt;
use MHorwood\foxess_mqtt\model\login;
use MHorwood\foxess_mqtt\model\config;
use MHorwood\foxess_mqtt\model\mhredis;

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
      $this->log('Missing config: '.$e->getMessage(), 3);
      exit(1);
    }
    $this->data   = new data($this->config);
    $this->device = new device($this->config);
    $this->mqtt   = new mqtt($this->config);
    $this->redis  = new mhredis($this->config);
    $this->foxess_data = $this->redis->get('foxess_data');

    if( $this->foxess_data['setup'] < time() ){
      $this->log('Update MQTT and device list', 1);
      if( $this->device->list() === true ){
        $this->foxess_data = $this->redis->get('foxess_data');
        $this->foxess_data['setup'] = $this->config->timestamp();
        $this->redis->set('foxess_data', $this->foxess_data);
      }else{
        $this->log('Issues getting devices', 3);
      }
    }

    for( $device = 0; $device < $this->foxess_data['device_total']; $device++ ){ //for each device
      $this->collected_data[$device] = $this->data->collect_data($this->foxess_data, $device);
    }

    $this->data->process_data($this->config->mqtt_topic, $this->foxess_data, $this->collected_data, $this->config->total_over_time);
    $this->log("Work complete", 1);
  }

}
