<?php

namespace MHorwood\foxess_mqtt\classes;

class logger {

  /**
   * Log function
   **/
  protected function log($text, $level = 2){

    if(!defined('log_level')){
      define('log_level', 2);
    }

    if($level == 4){
      echo date('Y-m-d H:i:s').' - ';
      print_r($text);
      echo "\n";
    }elseif($level <= constant('log_level')){
      echo date('Y-m-d H:i:s').' - '.$text."\n";
    }
  }

}
