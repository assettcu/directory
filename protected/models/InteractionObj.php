<?php


class InteractionObj extends FactoryObj
{
	public function __construct($interactionid=null)
	{
		parent::__construct("interactionid","interactions",$interactionid);
	}

	// Method to run after before saving
	public function pre_save()
	{
		if(!isset($this->date_created)) $this->date_created = date("Y-m-d H:i:s");
		if(isset($this->tags)){
			$tags = explode(",",$this->tags);
			$taglist = array();
			foreach($tags as $tag)
			{
				$tag = trim($tag);
				if(!in_array($tag,$taglist))
					$taglist[] = $tag;
			}
			$this->tags = implode(", ",$taglist);
		}
		if(!isset($this->tags) or $this->tags=="")
			$this->tags = "untagged";
			
		// Convert Departments Array to string for database/class conversion
		if(isset($this->attendees) and is_array($this->attendees))
		{
			$this->attendees = array_values($this->attendees);
			$this->attendees = json_encode($this->attendees);
		}
	}

	// Method to run after succesfully saving
	public function post_save()
	{
		// Convert Departments String to Array for database/class conversion
		if(isset($this->attendees) and is_string($this->attendees))
			$this->attendees = json_decode($this->attendees);
	}
	
	public function post_load()
	{
		// Empty relationships? array it!
		if(is_null($this->attendees))
			$this->attendees = array();
		
		// Convert Departments Array to string for database/class conversion
		if(isset($this->attendees) and is_string($this->attendees))
			$this->attendees = (array)json_decode($this->attendees);
	}
	
	public function get_attendees_list()
	{
		$attendees = $this->get_all_attendees();
		if(empty($attendees)) return "";
		$list = array();
		foreach($attendees as $attendee)
			$list[] = $attendee->cid;
		return implode(",",$list);
	}

	public function format_date($date="",$format="Y-m-d H:i:s")
	{
		if($date=="") $date = $this->meetingdate;
		if($date=="") return $date;
		
		return date($format,strtotime($date));
	}
	
	public function add_attendee($contact)
	{
		return $this->save_attendee($contact);
	}

	public function save_attendee($contact)
	{
		if(!$this->is_valid_id() or $this->attendee_exists($contact)) return false;
		if($contact instanceof ContactObj) $cid = $contact->cid;
		else $cid = $contact;

		$conn = Yii::app()->db;
		$query = "
			INSERT INTO		{{attendees}}
			VALUES
			( :interactionid, :cid );
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":interactionid",$this->interactionid);
		$command->bindParam(":cid",$cid);
		$command->execute();
		return true;
	}

	public function attendee_exists($contact)
	{
		if(!$this->is_valid_id()) return false;
		if($contact instanceof ContactObj) $cid = $contact->cid;
		else $cid = $contact;

		$conn = Yii::app()->db;
		$query = "
			SELECT		COUNT(*) as attendee_count
			FROM		{{attendees}}
			WHERE		interactionid = :interactionid
			AND			cid = :cid;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":interactionid",$this->interactionid);
		$command->bindParam(":cid",$cid);
		$result = $command->queryScalar();
		return ($result==1);
	}

	public function remove_all_attendees()
	{
		$attendees = $this->get_all_attendees();
		if(empty($attendees)) return true;

		foreach($attendees as $attendee)
			$this->remove_attendee($attendee);

		return true;
	}

	public function get_all_attendees()
	{
		$this->contacts = array();
		if(!$this->is_valid_id()) return false;
		$attendees = array();
		$conn = Yii::app()->db;
		$query = "
			SELECT DISTINCT		A.cid,C.lastname
			FROM							{{attendees}} as A, {{contacts}} as C
			WHERE							A.interactionid = :interactionid
			AND								C.cid = A.cid
			ORDER BY					C.lastname;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":interactionid",$this->interactionid);
		$result = $command->queryAll();
		if(!$result) return $attendees;
		foreach($result as $row)
		{
			if($row["cid"]==0) $this->remove_attendee(0);
			else
				$attendees[] = new ContactObj($row["cid"]);
		}
		$this->contacts = $attendees;
		return $attendees;
	}

	public function remove_attendee($contact)
	{
		if(!$this->is_valid_id()) return false;
		if($contact instanceof ContactObj) $cid = $contact->cid;
		else $cid = $contact;
		$conn = Yii::app()->db;
		$query = "
			DELETE FROM		{{attendees}}
			WHERE			`interactionid` = :interactionid
			AND				`cid` = :cid;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":interactionid",$this->interactionid);
		$command->bindParam(":cid",$cid);
		$command->execute();
		return true;
	}

	public function run_check()
	{
		if(!isset($this->meetingdate) or $this->meetingdate == "")
		{
			$this->set_error("Meeting date must be set. You can't not have a meeting date. Silly.");
			return false;
		}
		if(!isset($this->notes) or strip_tags($this->notes)=="")
		{
			$this->set_error("Notes cannot be empty. Something must have happened at the interaction. Jot that down!");
			return false;
		}
		return true;
	}

	public function get_formatted_tags()
	{
		if(!isset($this->tags) or empty($this->tags)) return "";

		$tags_output = "";
		$tags = explode(",",$this->tags);
		foreach($tags as $tag)
		{
			if(trim($tag)=="") continue;
			$tags_output .= "<a href='#{$tag}' class='filter-tag'>{$tag}</a>, ";
		}

		$tags_output = substr($tags_output,0,-2);
		return $tags_output;
	}

	public function get_linked_attendees()
	{
		switch($this->type)
		{
			case "workshop":
				$format = ":l| :f";
			break;
			case "conversation":
			case "interaction":
			default:	$format = ":f :l";
		}
		
		$this->get_all_attendees();
		$text = "";
		
		if(isset($this->contacts) and !empty($this->contacts))
		{
			foreach($this->contacts as $contact)
			{
				$text .= $contact->get_linked_name($format) . ",";
			}
			$text = substr($text,0,-1);
		}
		
		if($this->type=="interaction")
			return str_replace(",","<br/>",$text);
		if($this->type=="workshop")
			return str_replace("|",",",str_replace(",","<br/>",$text));
	}
	
	public function get_attendees_count()
	{
		if(!isset($this->contacts)) $this->get_all_attendees();
		return count($this->contacts);
	}
	
	public function load_contact_objects()
	{
		if(!isset($this->attendees) or count($this->attendees)==0) return false;
		
		$this->contacts = array();
		
		foreach($this->attendees as $cid)
			$this->contacts[$cid] = new ContactObj($cid);
			
		return $this->contacts;
	}
	
}

?>