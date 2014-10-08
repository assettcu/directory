<?php

/**
 * Factory Class
 *
 * @version $Id$
 * @copyright 2011
 */

class FactoryObj
{

	private $error_msg = "";
	public $loaded = false;

	public function __construct($uniqueid,$table,$id=null)
	{
		$this->uniqueid = $uniqueid;
		$this->table = $table;
		$this->{$this->uniqueid} = $id;
		$this->load();
	}

	public function is_valid_id()
	{
		return (isset($this->{$this->uniqueid}) and !is_null($this->{$this->uniqueid}) and $this->{$this->uniqueid}!="" and (!is_numeric($this->{$this->uniqueid}) or $this->{$this->uniqueid}>0));
	}

	protected function pre_save()
	{
		/** This function is meant to be overloaded **/
	}

	protected function post_save()
	{
		/** This function is meant to be overloaded **/
	}

	protected function pre_load()
	{
		/** This function is meant to be overloaded **/
	}

	protected function post_load()
	{
		/** This function is meant to be overloaded **/
	}

	public function load()
	{
		$this->pre_load();
		$conn = Yii::app()->db;
		if(!$this->is_valid_id()) return false;
		$query = "
			SELECT		*
			FROM		{{".$this->table."}}
			WHERE		".$this->uniqueid." = :".$this->uniqueid.";
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":".$this->uniqueid,$this->{$this->uniqueid});

		$result = $command->queryRow();
		if(!$result or count($result)==0)
		{
			$this->post_load();
			return false;
		}

		// Loop through each field and load it into the contact object
		foreach($result as $index=>$val) {
			$this->$index = $val;
		}
		$this->loaded = true;
		$this->post_load();
		return true;
	}

	public function save()
	{
		$this->pre_save();

		$vars = get_object_vars($this);

		// Remove the variables which are not database vars
		foreach($vars as $var=>$val) {
            if(!$this->is_column($var)) {
                unset($vars[$var]);
            }
		}

		if($this->run_check()) {
			$transaction = Yii::app()->db->beginTransaction();
			// Course id was set so we need to update the database
			if($this->is_valid_id() and $this->exists()) {
				$set_fields = "";

				foreach($vars as $var=>$val)
					$set_fields .= "{$var} = :{$var},";

				$set_fields = substr($set_fields,0,-1);
				$query = "
					UPDATE		{{".$this->table."}}
					SET			{$set_fields}
					WHERE		`".$this->uniqueid."` = :".$this->uniqueid.";
				";
				$command = Yii::app()->db->createCommand($query);
				$command->bindParam(":".$this->uniqueid,$this->uniqueid);

			}
			else {
				$field_names = "";
				$field_values = "";

				foreach($vars as $var=>$val) {
					if($var==$this->uniqueid and (is_null($val) or empty($val))) continue;
					$field_names .= "{$var},";
					$field_values .= ":{$var},";
				}
				$field_names = substr($field_names,0,-1);
				$field_values = substr($field_values,0,-1);
				$query = "
					INSERT INTO		{{".$this->table."}}
					(	{$field_names} 	)
					VALUES
					(	{$field_values}	);
				";
				$command = Yii::app()->db->createCommand($query);
			}
			# Loop through and bind the parameters
			foreach($vars as $var=>$val) {
				if($var==$this->uniqueid and (is_null($val) or empty($val))) continue;
				$command->bindParam(":{$var}",$this->$var);
			}
			
            
			try {
				$command->execute();
				if(!$this->is_valid_id()) $this->{$this->uniqueid} = Yii::app()->db->getLastInsertId();
				$transaction->commit();
			}
			catch(Exception $e) {
				$transaction->rollBack();
				$this->set_error($e);
				return false;
			}
            
			$this->post_save();
			return true;
		} 
		else {
		    return false;
        }
	}

	public function run_check()
	{
		return true;
	}

	public function exists()
	{
		$result = Yii::app()->db->createCommand()
            ->select("COUNT(*)")
            ->from($this->table)
            ->where($this->uniqueid." = :".$this->uniqueid, array(":".$this->uniqueid => $this->{$this->uniqueid}))
            ->queryScalar();
		return ((integer)$result!=0);
	}

	public function is_column($column)
	{
		$conn = Yii::app()->db;
		$query = "
			SHOW COLUMNS FROM {{".$this->table."}};
		";
		$result = $conn->createCommand($query)->queryAll();
		if(!$result) return false;
		foreach($result as $col)
		{
			if($col["Field"]==$column) return true;
		}
		return false;
	}

	public function set_error($msg)
	{
		$this->error_msg = $msg;
		return true;
	}

	public function pre_delete()
	{

	}

	public function delete()
	{
		$this->pre_delete();

		if(!$this->is_valid_id()) return false;
		$conn = Yii::app()->db;
		$query = "
			DELETE FROM		{{".$this->table."}}
			WHERE		".$this->uniqueid." = :".$this->uniqueid.";
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":".$this->uniqueid,$this->{$this->uniqueid});
		return $command->execute();
	}

	public function get_error()
	{
		return $this->error_msg;
	}
}


?>