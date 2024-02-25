<?php
namespace MHorwood\foxess_mqtt\model;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\classes\logger;

class mhredis extends json {

  protected $config;
  protected $redis;
  public $foxess_data;

  /**
   * undocumented function summary
   *
   * Undocumented function long description
   *
   * @param type var Description
   * @return return type
   */
  public function __construct($config) {
    $this->config = $config;
    try{
      $this->redis = new \Redis();
      //Connecting to Redis
      $this->redis->connect($this->config->redis_server, $this->config->redis_port);
      $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_JSON);
      // $redis->auth('password');

      if(!$this->redis->exists('foxess_data')){
        $this->log('Build default config', 1);
        $this->redis->set('foxess_data', $this->load_from_file('template/foxess_data.json'));
      }
    }catch (Exception $e) {
      $this->log('Missing config: '.  $e->getMessage(), 3);
    }
  }

  /**
   * undocumented function summary
   *
   * Undocumented function long description
   *
   * @param type var Description
   * @return return type
   */
  public function set($key, $value='') {
    try{
      return $this->redis->set($key, $value);
    }catch (Exception $e) {
      $this->log('Missing config: '.  $e->getMessage(), 3);
    }
  }

  /**
   * undocumented function summary
   *
   * Undocumented function long description
   *
   * @param type var Description
   * @return return type
   */
  public function get($key) {
    try{
      if($this->redis->exists($key)){
        return $this->redis->get($key);
      }else{
        $this->log('Missing Key: '.  $key, 3);
      }
    }catch (Exception $e) {
      $this->log($e->getMessage(), 3);
    }
  }
}
