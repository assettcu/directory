<?php

class WidgetBox extends Graphics {

  public $header = "";
  public $content = "";
  public $id = "";
  public $styles = "";
  public $classes = "";
  public $header_set = false;

  public function __construct()
  {
    parent::__construct();
    $this->header_set = true;
    $this->set_style_defaults();
    $this->set_class_defaults();
  }
  
  public function set_style_defaults()
  {
    $this->styles["container"]["width"] = "auto";
    $this->styles["container"]["float"] = "left";
    $this->styles["container"]["margin-right"] = "10px";
    $this->styles["container"]["padding"] = "3px";
    $this->styles["header"]["padding"] = "3px";
    $this->styles["content"]["padding"] = "10px";
  }
  
  public function set_class_defaults()
  {
    $this->classes["container"][] = "widget-box-container";
    $this->classes["header"][] = "widget-box-header";
    $this->classes["content"][] = "widget-box-content";
  }
  
  public function render()
  {
    print $this->get_html();
    return;
  }
  
  public function get_html()
  {
    ob_start();
    ?>
    <div id="<?=$this->id?>" class="ui-widget-content ui-corner-all <?=$this->render_classes("container");?>" style="<?=$this->render_styles("container");?>">
      <?php if($this->header_set): ?>
      <div class="ui-widget-header ui-corner-all <?=$this->render_classes("header");?>" style="<?=$this->render_styles("header");?>"><?=$this->header?></div>
      <?php endif; ?>
      <div class=" <?=$this->render_classes("content");?>" style="<?=$this->render_styles("content");?>"><?=$this->content?></div>
    </div>
    <?php
    $contents = ob_get_contents();
    ob_end_clean();
    
    return $contents;
  }
  
}

?>