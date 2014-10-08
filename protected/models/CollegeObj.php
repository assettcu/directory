<?php

class CollegeObj extends FactoryObj
{

	public function __construct($collegeid=null)
	{
		parent::__construct("collegeid","colleges",$collegeid);
		$this->load();
	}

}