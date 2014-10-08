<?php

/**
 * Position Class
 *
 * @version $Id$
 * @copyright 2011
 */


class PositionObj extends FactoryObj
{

	public $posid = 0;

	public function __construct($posid=null)
	{
		parent::__construct("posid","positions",$posid);
		$this->load();
	}

	public function pre_load()
	{
		if(!$this->is_valid_id() and isset($this->deptid))
		{
			$conn = Yii::app()->db;
			$query = "
				SELECT			posid
				FROM			{{dept_positions}}
				WHERE			posid = (SELECT posid FROM {{positions}} WHERE posname = '".$this->posname."' LIMIT 1)
				AND				deptid = '".$this->deptid."'
				LIMIT			1;
			";
			$command = $conn->createCommand($query);
			$result = $command->queryScalar();
			
			if(!$result or $result==0) return;
			
			$this->posid = $result["posid"];
		}
	}

	public function pre_save()
	{
		if(!$this->is_valid_id())
		{
			$conn = Yii::app()->db;
			$query = "
				SELECT		posid
				FROM		{{positions}}
				WHERE		posname = :posname
				LIMIT		1;
			";
			$command = $conn->createCommand($query);
			$command->bindParam(":posname",$this->posname);
			$result = $command->queryScalar();

			if($result>0)
				$this->posid = $result;
		}
	}

	public function count_members()
	{
		if(!$this->is_valid_id()) return 0;
		$conn = Yii::app()->db;
		$query = "
			SELECT		COUNT(*)
			FROM		{{contacts}}
			WHERE		posid LIKE :posid;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":posid","%".$this->posid."%");
		return $command->queryScalar();
	}

	public function get_members($deptid=null)
	{
		if(!$this->is_valid_id()) return array();
		if(is_null($deptid)) $deptid = @$this->deptid;
		$conn = Yii::app()->db;
		if(is_null($deptid))
		{
			$query = "
				SELECT DISTINCT		c.cid
				FROM							{{contacts}} as c, {{positions}} as p
				WHERE							c.positions LIKE :positions
				ORDER BY					c.archive ASC, p.posname ASC;
			";
			$command = $conn->createCommand($query);
			$command->bindValue(":positions","%".$this->posid."%");
		}
		else
		{
			$query = "
				SELECT DISTINCT		c.cid
				FROM							{{contacts}} as c, {{positions}} as p
				WHERE							c.positions LIKE :positions
				AND								c.departments LIKE :departments
				ORDER BY					c.archive ASC, p.posname ASC;
			";
			$command = $conn->createCommand($query);
			$command->bindValue(":positions","%".$this->posid."%");
			$command->bindValue(":departments","%".$deptid."%");
		}

		$result = $command->queryAll();
		if(!$result or empty($result)) return array();
		$this->contacts = array();
		foreach($result as $row)
			$this->contacts[] = new ContactObj($row["cid"]);

		return $this->contacts;
	}

	public function run_check()
	{
		if(!isset($this->posname) or $this->posname=="")
			return !$this->set_error("Position cannot be empty!");

		return true;
	}

	public function pre_delete()
	{
		if(!$this->is_valid_id()) return false;
		$conn = Yii::app()->db;
		$query = "
			SELECT		cid
			FROM		{{contacts}}
			WHERE		positions LIKE :positions
		";
		$command = $conn->createCommand($query);
		$command->bindValue(":positions","%".$this->posid."%");
		$result = $command->queryAll();
		if(!$result or empty($result)) return false;
		foreach($result as $row)
		{
			$contact = new ContactObj($row["cid"]);
			$contact->remove_position($this->posid);
			$contact->save();
		}
		return true;
	}

}

?>