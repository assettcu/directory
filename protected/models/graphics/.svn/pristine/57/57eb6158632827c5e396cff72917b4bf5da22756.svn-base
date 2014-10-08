<?php

class WidgetGrid extends Graphics
{
  
  public $numrows = 0;
  public $numcols = 0;
  public $fixed = false;
  public $data = array();
  public $classes = array();
  public $styles = array();
  public $ids = array();
  
  public function __construct($cols=0,$rows=0,$fixed=false)
  {
    parent::__construct();
    $this->numcols = $cols;
    $this->numrows = $rows;
    $this->fixed = $fixed;
  }
  
  public function add_data($data,$col,$row)
  {
    $this->data[$row][$col] = $data;
    if($this->fixed) return;
    
    if($row>=$this->numrows) $this->numrows = $row+1;
    if($col>=$this->numcols) $this->numcols = $col+1;
  }
  
  public function add_row($data,$row)
  {
    if(!is_array($data)) return false;
    if(!$this->fixed and $row>=$this->numrows) $this->numrows = $row+1;
    if(!$this->fixed and $col>=$this->numcols) $this->numcols = $col+1;
    
    for($cols=0;$cols<$this->numcols;$cols++)
    {
      if(isset($data[$cols])) $this->data[$row][$cols] = $data[$cols];
    }
  }
  
  public function add_col($data,$col)
  {
    if(!is_array($data)) return false;
    if($col>$this->numcols) return false;
    
    for($rows=0;$rows<$this->numrows;$rows++)
    {
      if(isset($data[$rows])) $this->data[$rows][$col] = $data[$rows];
    }
  }
  
  public function add_grid($data)
  {
    // Data[row][col]
    if(empty($data)) return false;
    if(!is_array($data)) return false;
    
    $first_row = array_shift(array_values($data));
    if(empty($first_row)) return false;
    
    if(count($data)>$this->numrows) $this->numrows = count($data);
    if(count($first_row)>$this->numcols) $this->numcols = count($first_row);
    
  }
  
  public function get_html()
  {
    ob_start();
    ?>
    <table id="<?=$this->ids["table"]?>" class="<?=trim($this->render_table_class());?>" style="<?=$this->render_table_style();?>">
      <tbody>
        <?php for($rows=0;$rows<$this->numrows;$rows++): ?>
        <tr class="<?=trim($this->render_row_class($rows));?>" style="<?=$this->render_row_style($rows);?>">
        <?php for($cols=0;$cols<$this->numcols;$cols++): ?>
          <td class="<?=trim($this->render_cell_class($rows,$cols));?>" style="<?=$this->render_col_style($cols);?><?=$this->render_cell_style($rows,$cols);?>"><?=$this->data[$rows][$cols];?></td>
        <?php endfor; ?>
        </tr>
        <?php endfor; ?>
      </tbody>
    </table>
    <?php
    $contents = ob_get_contents();
    ob_end_clean();
    
    return $contents;
  }
  
  public function add_styles($styles)
  {
    $this->styles = array_merge($styles,$this->styles);
  }
  
  public function add_classes($classes)
  {
    $this->classes = array_merge($classes,$this->classes);
  }
  
  public function render_table_style()
  {
    $render = "";
    if(isset($this->styles["table"]))
    {
      foreach($this->styles["table"] as $style=>$value)
        $render .= $style.":".$value.";";
    }
    return $render;
  }
  
  public function render_row_style($row)
  {
    $render = "";
    if(isset($this->styles["rows"]))
    {
      foreach($this->styles["rows"] as $style=>$value)
        $render .= $style.":".$value.";";
    }
    if(isset($this->styles["row"][$row]))
    {
      foreach($this->styles["row"][$row] as $style=>$value)
        $render .= $style.":".$value.";";
    }
    
    return $render;
  }
  
  public function render_col_style($col)
  {
    $render = "";
    if(isset($this->styles["cols"]))
    {
      foreach($this->styles["cols"] as $style=>$value)
        $render .= $style.":".$value.";";
    }
    if(isset($this->styles["col"][$col]))
    {
      foreach($this->styles["col"][$col] as $style=>$value)
        $render .= $style.":".$value.";";
    }
    
    return $render;
  }
  
  public function render_cell_style($row,$col)
  {
    $render = "";
    if(isset($this->styles["cells"]))
    {
      foreach($this->styles["cells"] as $style=>$value)
        $render .= $style.":".$value.";";
    }
    if(isset($this->styles["cell"][$row][$col]))
    {
      foreach($this->styles["cell"][$row][$col] as $style=>$value)
        $render .= $style.":".$value.";";
    }
    
    return $render;
  }
  
  public function render_table_class()
  {
    $render = "";
    if(isset($this->classes["table"]))
    {
      foreach($this->classes["table"] as $class)
        $render .= " ".$class." ";
    }
    return $render;
  }
  
  public function render_row_class($row)
  {
    $render = "";
    if(isset($this->classes["rows"]))
    {
      foreach($this->classes["rows"] as $class)
        $render .= " ".$class." ";
    }
    if(isset($this->classes["row"][$row]))
    {
      foreach($this->classes["row"][$row] as $class)
        $render .= " ".$class." ";
    }
    
    return $render;
  }
  
  public function render_cell_class($row,$col)
  {
    $render = "";
    if(isset($this->classes["cells"]))
    {
      foreach($this->classes["cells"] as $class)
        $render .= " ".$class." ";
    }
    if(isset($this->classes["cell"][$row][$col]))
    {
      foreach($this->classes["cell"][$row][$col] as $class)
        $render .= " ".$class." ";
    }
    
    return $render;
  }
}