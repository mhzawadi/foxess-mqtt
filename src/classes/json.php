<?php

namespace MHorwood\foxess_mqtt\classes;
use MHorwood\foxess_mqtt\classes\logger;

class json extends logger {

  /**
   * load a file and load to array
   */
  protected function load_from_file($filename, $array = true){
    try {
      $handle = fopen($filename, "r");
      $json = json_decode(fread($handle, filesize($filename)), $array);
      fclose($handle);
      $error = json_last_error();
      if($error === JSON_ERROR_NONE){
        return $json;
      }else{
        throw new Exception('Syntax error, malformed JSON');
      }

    } catch (Exception $e) {
      $this->log('Issues opening file: '.$e->getMessage(), 3);
      return false;
    }

  }

  /**
   * Save a json blob to file
   */
  protected function save_to_file($filename, $json){
    try {
      $fp = fopen($filename, 'w');
      fwrite($fp, json_encode($json, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT));
      fclose($fp);
      sleep(1);
      return true;
    } catch (\Exception $e) {
      $this->log('Issues saving file: '.$e->getMessage(), 3);
      return false;
    }
  }
}
