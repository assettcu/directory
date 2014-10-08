<?php

/**
 * Department Class
 *
 * @version $Id$
 * @copyright 2011
 */

class DepartmentObj extends FactoryObj
{

	public $do_post_load = false;
	public $deptid = 0;

	public function __construct($deptid=null,$do_post_load=false)
	{
		$this->do_post_load = $do_post_load;
		parent::__construct("deptid","departments",$deptid);
		$this->load();
	}

	public function pre_save()
	{
		if(!isset($this->deptname)) return false;

		$conn = Yii::app()->db;
		$query = "
			SELECT		deptid
			FROM		{{departments}}
			WHERE		deptname = :deptname
			LIMIT		1;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":deptname",$this->deptname);
		$result = $command->queryScalar();

		if($result>0)
		{
			$this->deptid = $result;
		}
	}

	public function run_check()
	{
		if(!$this->is_valid_id())
		{
			if(!isset($this->deptname) or $this->deptname == "")
				return !$this->set_error("Department name cannot be empty.");
		}
		return true;
	}

	public function pre_delete()
	{
		// Need to delete the deptid from all the contacts
	}
	
	public function pre_load()
	{
		if(!$this->is_valid_id() and isset($this->deptname))
		{
			$conn = Yii::app()->db;
			$query = "
				SELECT		deptid
				FROM		{{departments}}
				WHERE		deptname = :deptname
				LIMIT 		1;
			";
			$command = $conn->createCommand($query);
			$command->bindParam(":deptname",$this->deptname);
			$result = $command->queryScalar();
			if(is_numeric($result) and $result>0)
				$this->deptid = $result;
		}
	}

	public function post_load($overload=false)
	{
		$conn = Yii::app()->db;
		$this->dept_positions = array();
		$query = "
			SELECT		*
			FROM			{{department_positions}}
			WHERE			deptid = :deptid
			ORDER BY	posname ASC;
		";
		$command = $conn->createCommand($query);
		$command->bindValue(":deptid",$this->deptid);
		$result = $command->queryAll();
		if($result and !empty($result))
		{
			foreach($result as $row)
			{
				$this->dept_positions[] = $row["posname"];
			}
		}
	}

	public function get_all_positions()
	{
		if(!$this->is_valid_id()) return array();
		$conn = Yii::app()->db;
		$query = "
			SELECT DISTINCT		posid
			FROM							{{dept_positions}}
			WHERE							deptid = :deptid;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":deptid",$this->deptid);
		$result = $command->queryAll();
		$this->dept_positions = array();
		if(!$result or empty($result)) return array();

		foreach($result as $row)
		{
			$this->dept_positions[$row["posid"]] = new PositionObj($row["posid"]);
			if(!$this->dept_positions[$row["posid"]]->loaded)
			{
				$query = "
					DELETE FROM			{{dept_positions}}
					WHERE						deptid = :deptid
					AND							posid = :posid;
				";
				$command = $conn->createCommand($query);
				$command->bindParam(":deptid",$this->deptid);
				$command->bindParam(":posid",$this->posid);
				$command->execute();
				unset($this->dept_positions[$row["posid"]]);
			}
		}

		return $this->dept_positions;
	}
	
	public function load_contacts()
	{
		return $this->get_all_contacts();
	}

	public function get_all_contacts()
	{
		if(!$this->is_valid_id()) return array();
		$conn = Yii::app()->db;
		$query = "
			SELECT		cid,lastname
			FROM		{{contacts}}
			WHERE		departments LIKE :departments
			ORDER BY	lastname ASC;
		";
		$command = $conn->createCommand($query);
		$command->bindValue(":departments","%".$this->deptid."%");
		$result = $command->queryAll();
		if(!$result or empty($result)) return array();
		
		foreach($result as $row)
			$this->contacts[] = new ContactObj($row["cid"]);

		return $this->contacts;
	}

	public function has_position($posid)
	{
		$conn = Yii::app()->db;
		$query = "
			SELECT		COUNT(*)
			FROM			{{dept_positions}}
			WHERE			deptid = :deptid
			AND				posid = :posid;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":deptid",$this->deptid);
		$command->bindParam(":posid",$posid);
		
		return ($command->queryScalar()==1);
	}
	
	public function add_position($posname)
	{
		$conn = Yii::app()->db;
		$query = "
			INSERT INTO			{{department_positions}}
											( deptid, posname )
			VALUES					(:deptid, :posname);
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":deptid",$this->deptid);
		$command->bindParam(":posname",$posname);
		$command->execute();
	}
	
	public function remove_position($posname)
	{
		$conn = Yii::app()->db;
		$query = "
			DELETE FROM			{{department_positions}}
			WHERE						deptid = :deptid
			AND							posname = :posname;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":deptid",$this->deptid);
		$command->bindParam(":posname",$posname);
		$command->execute();
		
		$query = "
			UPDATE					{{contact_departments}}
			SET							posname = ''
			WHERE						deptid = :deptid
			AND							posname = :posname;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":deptid",$this->deptid);
		$command->bindParam(":posname",$posname);
		$command->execute();
	}
	
	public function rename_position($oldname,$newname)
	{
		$conn = Yii::app()->db;
		$query = "
			UPDATE					{{department_positions}}
			SET							posname = :newname
			WHERE						deptid = :deptid
			AND							posname = :posname;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":newname",$newname);
		$command->bindParam(":deptid",$this->deptid);
		$command->bindParam(":posname",$oldname);
		$command->execute();
		
		$query = "
			UPDATE					{{contact_departments}}
			SET							posname = :newname
			WHERE						deptid = :deptid
			AND							posname = :posname;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":newname",$newname);
		$command->bindParam(":deptid",$this->deptid);
		$command->bindParam(":posname",$oldname);
		$command->execute();
	}
	
}


?>