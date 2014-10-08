<?php

class LogObj extends FactoryObj
{
	public function __construct($logid=null)
	{
		parent::__construct("logid","logs",$logid);
	}
  
  public function pre_save()
  {
    if(!$this->is_valid_id())
    {
      $this->username = Yii::app()->user->name;
      $this->date_logged = date("Y-m-d H:i:s");
    }
  }
  
}