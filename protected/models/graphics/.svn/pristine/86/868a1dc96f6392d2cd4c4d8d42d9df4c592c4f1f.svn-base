<?php

class Widget extends Graphics
{
  
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
    $this->styles["container"]["width"] = "100%";
    $this->styles["container"]["float"] = "left";
    $this->styles["container"]["padding"] = "0px";
    $this->styles["header"]["padding"] = "3px";
    $this->styles["header"]["width"] = "100%";
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
    <div class="widget">
      <div class="ui-widget-header ui-corner-top <?=$this->render_classes("header");?>" style="<?=$this->render_styles("header");?>">
        <?=$this->header?>
        <div class="drag-me"></div>
      </div>
      <div id="<?=$this->id?>" class="ui-widget-content <?=$this->render_classes("container");?>" style="<?=$this->render_styles("container");?>">
        <div class=" <?=$this->render_classes("content");?>" style="<?=$this->render_styles("content");?>"><?=$this->content?></div>
      </div>
    </div>
    <?php
    $contents = ob_get_contents();
    ob_end_clean();
    
    return $contents;
  }
}