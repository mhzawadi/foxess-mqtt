<?php

namespace MHorwood\foxess_mqtt\classes;

class logger {

  /**
   * Log function
   * Type: 1 INFO, 2 WARN, 3 ERROR
   **/
  protected function log($text, $type = 1, $level = 2){

    $log_type = array(
      'DEBUG',
      'INFO',
      'WARN',
      'ERROR'
    );

    if(!defined('log_level')){
      define('log_level', 2);
    }

    if($level == 4){
      echo date('Y-m-d H:i:s').' - ;';
      print_r($text);
      echo ";\n";
    }elseif($level <= constant('log_level')){
      echo date('Y-m-d H:i:s').' ['.$log_type[$type].'] '.$text."\n";
    }
  }

}
