<?php

class Graphics {
  
  public function __construct()
  {
    
  }
  
  public function set_style_defaults()
  {
    $this->styles = array();
  }

  public function set_class_defaults()
  {
    $this->classes = array();
  }
  
  public function render()
  {
    print $this->get_html();
    return;
  }
  
  public function get_html()
  {
    // This should be overwritten
  }
  
  public function render_styles($type)
  {
    $return = "";
    foreach($this->styles[$type] as $key=>$value)
      $return .= $key.":".$value.";";
    
    return $return;
  }
  
  public function render_classes($type)
  {
    return implode(" ",$this->classes[$type]);
  }
  
}