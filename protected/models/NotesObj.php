<?php


class NotesObj extends FactoryObj
{

	public function __construct($notesid=null)
	{
		parent::__construct("notesid","notes",$notesid);
	}

  public function pre_load()
  {
    if(!$this->is_valid_id() and isset($this->cid,$this->username))
    {
      $conn = Yii::app()->db;
      $query = "
        SELECT     notesid
        FROM      {{notes}}
        WHERE     username = :username
        AND       cid = :cid;
      ";
      $command = $conn->createCommand($query);
      $command->bindParam(":username",$this->username);
      $command->bindParam(":cid",$this->cid);
      $this->notesid = $command->queryScalar();
    }
  }
  
	public function pre_save()
	{
    if(!$this->is_valid_id() and isset($this->cid,$this->username))
    {
      $conn = Yii::app()->db;
      $query = "
        SELECT     notesid
        FROM      {{notes}}
        WHERE     username = :username
        AND       cid = :cid;
      ";
      $command = $conn->createCommand($query);
      $command->bindParam(":username",$this->username);
      $command->bindParam(":cid",$this->cid);
      $this->notesid = $command->queryScalar();
    }
	}
	
	public function post_save()
	{
		// Convert String to Array for database/class conversion
		if(isset($this->preferences) and is_string($this->preferences))
			$this->preferences = json_decode($this->preferences);
	}
	
	public function post_load()
	{
		// Convert String to Array for database/class conversion
		if(isset($this->preferences) and is_string($this->preferences))
			$this->preferences = json_decode($this->preferences);
	}
	
}

?>