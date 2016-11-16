<?php

namespace Reprint\Helper;

class Display
{

  public $elements = array();

  public function __construct($params) {
    if(isset($params['elements'])) {
      $this->elements = $params['elements'];
    }
  }

  public function element($name) {
    return (array_key_exists($name, $this->elements)) ? $this->elements[$name] : true;
  }

}