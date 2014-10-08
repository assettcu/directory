<?php


class RelationshipObj extends FactoryObj
{

	public function __construct($relid=null)
	{
		parent::__construct("relid","dept_positions",$relid);
		$this->load();
	}
  
  public function pre_load()
  {
    if(!$this->is_valid_id() and isset($this->posid) and isset($this->deptid))
    {
      $conn = Yii::app()->db;
      $query = "
        SELECT    relid
        FROM      {{dept_positions}}
        WHERE     deptid = :deptid
        AND       posid = :posid
        LIMIT     1;
      ";
      $command = $conn->createCommand($query);
      $command->bindParam(":deptid",$this->deptid);
      $command->bindParam(":posid",$this->posid);
      $result = $command->queryScalar();
      
      if(!$result or empty($result)) return;
      
      $this->relid = $result;
    }
  }
  
  public function get_members()
  {
		$this->contacts = array();
    
    $conn = Yii::app()->db;
    $query = "
      SELECT        		cid
      FROM							{{contacts}}
      WHERE							relationships LIKE :relationships
      ORDER BY					lastname ASC;
    ";
    $command = $conn->createCommand($query);
    $command->bindValue(":relationships","%\"".$this->relid."\"%");
		$result = $command->queryAll();
    
		if(!$result or empty($result)) return array();
    
		foreach($result as $row)
			$this->contacts[] = new ContactObj($row["cid"]);
    
		return $this->contacts;
  }
  
}