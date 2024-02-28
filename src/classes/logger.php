<?php

namespace MHorwood\foxess_mqtt\classes;

class Exception extends \Exception {}

class logger {

  /**
   * Log function
   * Type: 0 debug, 1 INFO, 2 WARN, 3 ERROR
   **/
  protected function log($text, $type = 1, $var = false){

    $log_type = array(
      'DEBUG',
      'INFO',
      'WARN',
      'ERROR'
    );

    if(!defined('log_level')){
      define('log_level', 2);
    }

    if($type == 0 && $var === true){
      echo date('Y-m-d H:i:s').' - ;';
      print_r($text);
      echo ";\n";
    }elseif($type >= constant('log_level')){
      echo date('Y-m-d H:i:s').' ['.$log_type[$type].'] '.$text."\n";
    }
  }

}
