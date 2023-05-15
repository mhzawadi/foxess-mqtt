<?php

namespace MHorwood\foxess_mqtt\classes;

class json {

  /**
   * load a file and load to array
   */
  protected function load_from_file($filename, $array = true){
    try {
      $handle = fopen($filename, "r");
      $json = json_decode(fread($handle, filesize($filename)), $array);
      fclose($handle);
      return $json;
    } catch (Exception $e) {
      echo 'Issues upening file: ', $e->getMessage(), "\n";
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
      echo 'that didnt work';
      return false;
    }
  }
}
