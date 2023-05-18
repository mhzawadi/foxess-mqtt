<?php

namespace MHorwood\foxess_mqtt\classes;

class logger {

  /**
   * Log function
   **/
  protected function log($text, $level = 1){
    echo date('Y-m-d H:i:s').' - '.$text."\n";
  }

}
