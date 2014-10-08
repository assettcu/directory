<?php


class ContactObj extends FactoryObj
{

	public $username;
	public $departments = array();
	public $positions = array();
	public $relationships = array();

	public function __construct($cid=null)
	{
		parent::__construct("cid","contacts",$cid);
	}

	// Method to run before loading
	public function pre_load()
	{
		// Find the contact by username, if possible
		if(!$this->is_valid_id() and isset($this->username) and $this->username != "")
		{
			$conn = Yii::app()->db;
			$query = "
				SELECT		`cid`
				FROM		{{contacts}}
				WHERE		`username` = :username;
			";
			$command = $conn->createCommand($query);
			$command->bindParam(":username",$this->username);
			$result = $command->queryScalar();
			if(!$result or empty($result)) return false;

			$this->cid = $result;
		}

        // Find the username by first name and last name, if possible
        if(isset($this->fullname) and !$this->is_valid_id())
        {
            $conn = Yii::app()->db;
            $query = "
                SELECT      cid
                FROM        {{contacts}}
                WHERE       fullname = :fullname;
            ";
            $command = $conn->createCommand($query);
            $command->bindParam(":fullname",$this->fullname);
            $this->cid = $command->queryScalar();
        }
        
		// Find the username by first name and last name, if possible
		if(isset($this->firstname,$this->lastname) and !$this->is_valid_id())
		{
			$conn = Yii::app()->db;
			$query = "
				SELECT		cid
				FROM		{{contacts}}
				WHERE		firstname = :firstname
				AND			lastname = :lastname;
			";
			$command = $conn->createCommand($query);
			$command->bindParam(":firstname",$this->firstname);
			$command->bindParam(":lastname",$this->lastname);
			$this->cid = $command->queryScalar();
		}
		
		// Find the username by last name, as a last resort
		if(isset($this->lastname) and !isset($this->firstname) and !$this->is_valid_id())
		{
			$conn = Yii::app()->db;
			$query = "
				SELECT		COUNT(*) AS unique_contact, cid
				FROM			{{contacts}}
				WHERE			lastname = :lastname;
			";
			$command = $conn->createCommand($query);
			$command->bindParam(":lastname",$this->lastname);
			$result = $command->queryRow();
			if($result["unique_contact"]==1)
			{
				$query = "
					SELECT		cid
					FROM		{{contacts}}
					WHERE		lastname = :lastname;
				";
				$command = $conn->createCommand($query);
				$command->bindParam(":lastname",$this->lastname);
				$this->cid = $command->queryScalar();
			}
		}
	}

	// Method to run after succesfully loading
	public function post_load()
	{
		if(!$this->loaded) return false;

		$this->load_departments();
		
		if($this->username!="")
		{
			$this->userobj = new UserObj($this->username);
			if(!$this->userobj->loaded) $this->userobj->permission = 0;
		}
	}

	// Return's an array
	public function load_departments()
	{
		if(!isset($this->cid)) return array();
		$this->departments = array();
		
		$conn = Yii::app()->db;
		$query = "
			SELECT			*, D.deptname
			FROM				{{contact_departments}} AS CD, {{departments}} AS D
			WHERE				CD.cid = :cid
			AND					D.deptid = CD.deptid
			ORDER BY		D.deptname ASC, CD.posname ASC;
		";
		
		$command = $conn->createCommand($query);
		$command->bindParam(":cid",$this->cid);
		$result = $command->queryAll();
		if(!$result or empty($result)) return array();
		
		foreach($result as $row)
		{
			$posobj = new stdClass;
			$posobj->posname = $row["posname"];
			$posobj->startdate = $row["startdate"];
			$posobj->enddate = $row["enddate"];
			$posobj->primary = $row["primary"];
			$posobj->current = $row["current"];
			$posobj->miscinfo = $row["miscinfo"];
			
			if(!array_key_exists($row["deptid"],$this->departments))
			{
				$dept = new DepartmentObj($row["deptid"]);
				$dept->positions[] = $posobj;
				$this->departments[$row["deptid"]] = $dept;
			}
			else
			{
				$this->departments[$row["deptid"]]->positions[] = $posobj;
			}
		}
		return $this->departments;
	}
	
	// Method to run before saving
	public function pre_save()
	{
		// Remove sporadic spaces and commas from the tag list
		if(isset($this->tags) and !empty($this->tags))
		{
			$this->tags = trim($this->tags);
			if(substr($this->tags,-1,1)==",") $this->tags = substr($this->tags,0,-1);
		}
		// If there are no tags, then tag it as "untagged"
		else
			$this->tags = "";

		// Format phone before saving
		if(isset($this->phone))
			$this->phone = $this->get_formatted_phone($this->phone);
		if(isset($this->phone2))
			$this->phone2 = $this->get_formatted_phone($this->phone2);
		
		if(isset($this->firstname,$this->lastname) and $this->firstname!="" and $this->lastname!="") {
			if(isset($this->middlename) and $this->middlename != "") {
				$this->fullname = $this->firstname." ".$this->middlename." ".$this->lastname;
			} else {
				$this->fullname = $this->firstname." ".$this->lastname;
			}
		} else if(isset($this->fullname) and $this->fullname != "") {
			$name = explode(" ",$this->fullname);
			if(count($name)==2) {
				$this->firstname = $name[0];
				$this->lastname = $name[1];
			} else if(count($name)==3) {
				$this->firstname = $name[0];
				$this->middlename = $name[1];
				$this->lastname = $name[2];
			} else {
				$this->firstname = array_shift($name);
				$this->lastname = array_pop($name);
				$this->middlename = implode(" ",$name);
			}
		} else {
			return false;
		}
		
		if(!isset($this->cid) and isset($this->fullname)) {
			$conn = Yii::app()->db;
			$query = "
				SELECT		cid
				FROM		{{contacts}}
				WHERE		fullname = :fullname;
			";
			$command = $conn->createCommand($query);
			$command->bindParam(":fullname",$this->fullname);
			$this->cid = $command->queryScalar();
		}
	}

	// Method to run after succesfully saving
	public function post_save()
	{
		if(isset($this->cid))
		{
			if(isset($this->departments) and !empty($this->departments))
			{
				foreach($this->departments as $dept)
				{
					if(!empty($dept->positions))
					{
						foreach($dept->positions as $posname)
						{
							$this->add_position($dept->deptid,$posname);
						}
					}
				}
			}
		}
	}

	public function get_departments_list()
	{
		if(empty($this->departments)) return "";
		$return = "";
		foreach($this->departments as $deptid)
		{
			$temp = new DepartmentObj($deptid);
			if($temp->loaded)
				$return .= "<a href='".Yii::app()->createUrl('site/dept',array("id"=>$deptid))."' class='dept-link'>".$temp->deptname."</a>, ";
		
		}
		$return = substr($return,0,-2);
		return $return;
	}

	public function get_positions_list()
	{
		if(empty($this->positions)) return "";
		$return = "";
		foreach($this->positions as $posid)
		{
			$temp = new PositionObj($posid);
			if($temp->loaded)
				$return .= "<a href='#' class='position-link'>".$temp->posname."</a>, ";
		}
		$return = substr($return,0,-2);
		return $return;
	}

	public function add_department($deptid)
	{
		if(!in_array($deptid,$this->departments))
			$this->departments[] = $deptid;
		return true;
	}

	public function remove_department($deptid)
	{
		$conn = Yii::app()->db;
		$query = "
			DELETE FROM			{{contact_departments}}
			WHERE						deptid = :deptid
			AND							cid = :cid;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":deptid",$deptid);
		$command->bindParam(":cid",$this->cid);
		return $command->execute();
	}

	public function has_department($deptid)
	{
		return in_array($deptid,$this->departments);
	}

	// Format the phone number to a custom format
	public function get_formatted_phone($phone,$format = "(xxx) xxx-xxxx")
	{
		$phone_pre = preg_replace("/[^0-9]*/","",$phone);
		if(strlen($phone_pre)!=10 and strlen($phone_pre)!=11) return $phone;
		$phone_arr = str_split($phone_pre);
		$phone = $format;
		if(count($phone_arr)==11)
		{
			$phone = "+".$phone_arr[0]." ".$phone;
			array_pop($phone_arr);
		}
		foreach($phone_arr as $char)
			$phone = substr_replace($phone,$char,strpos($phone,"x"),1);
		return $phone;
	}

	public function holds_position($deptid)
	{
		if(empty($this->positions)) return false;
		$dept = new DepartmentObj($deptid);
		foreach($this->positions as $posid)
		{
			if($dept->has_position($posid)) return true;
		}
		return false;
	}

	public function find_contact_by_username($username="")
	{
		if($username=="") $username = $this->username;
		if(is_null($this->username)) return false;

		$conn = Yii::app()->db;
		$query = "
			SELECT		`cid`
			FROM		{{contacts}}
			WHERE		`username` = :username;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":username",$username);
		$result = $command->queryRow();
		if(!$result or empty($result)) return false;

		$this->cid = $result["cid"];
		$this->load();
		return true;
	}

	public function login()
	{
		$login = new LoginObj();
		return $login->login($this->username);
	}

	public function print_name()
	{
		if(!(isset($this->firstname,$this->lastname) and strlen($this->firstname)>0 and strlen($this->lastname)>0))
			return print "(no name #".$this->cid.")";
		return print $this->firstname." ".$this->lastname;
	}

	public function get_name($format = "first")
	{
		if(!isset($this->firstname) or $this->firstname=="")
			return "{Unknown}";
		if(strlen($this->lastname)==0)
			return $this->firstname;
		if($format == "first")
			return $this->firstname." ".$this->lastname;

		return $this->lastname.", ".$this->firstname;
	}

	public function parse_name($name,$format="first (m) last")
	{
		$name = trim($name);
		if($name == "") return false;
		if($format=="first (m) last")
		{
			$temp = explode(" ",$name);
			$firstname = $temp[0];
			$lastname = $temp[count($temp)-1];
			unset($temp[0]);
			$this->firstname = $firstname;
			$this->lastname = $lastname;
		}
	}

	public function get_linked_name($format=":f :l")
	{
		$link = '<a class="contact-link" href="'.Yii::app()->createUrl('site/contact').'?id='.$this->cid.'">:contact_name</a>';
		
		$name = $format;
		$name = str_replace(":f",$this->firstname,$name);
		$name = str_replace(":l",$this->lastname,$name);
		
		return str_replace(":contact_name",$name,$link);
	}

	public function get_positions()
	{
		if(!isset($this->positions)) return "";
		$return = "";
		foreach($this->positions as $posid)
		{
			$position = new PositionObj($posid);
			if($position->loaded)
				$return .= $position->posname.", ";
		}
		$return = substr($return,0,-2);
		return $return;
	}

	public function get_departments()
	{
		if(!isset($this->departments)) return "";
		$return = "";
		foreach($this->departments as $deptid)
		{
			$department = new DepartmentObj($deptid);
			if($department->loaded)
				$return .= $department->deptname.", ";
		}
		$return = substr($return,0,-2);
		return $return;
	}

	public function get_departments_objects()
	{
		if(!isset($this->departments)) return "(not found)";
		foreach($this->departments as $deptid)
			$this->department_objects[] = new DepartmentObj($deptid);
		return $this->department_objects;
	}

	public function get_position_obj()
	{
		if(!isset($this->posid)) return "(not found)";
		$position = new PositionObj($this->posid);
		return $position;
	}
	
	public function run_check()
	{
		if($this->firstname=="")
			return !$this->set_error("First name must be set.");
		if($this->lastname == "")
			return !$this->set_error("Last name must be set.");

		if(isset($this->firstname,$this->lastname) and !$this->is_valid_id())
		{
			$conn = Yii::app()->db;
			$query = "
				SELECT		COUNT(*)
				FROM		{{contacts}}
				WHERE		firstname = :firstname
				AND			lastname = :lastname;
			";
			$command = $conn->createCommand($query);
			$command->bindParam(":firstname",$this->firstname);
			$command->bindParam(":lastname",$this->lastname);
			$result = $command->queryScalar();
			if($result==1)
				return !$this->set_error("This person's name already exists.");
		}
		if(!isset($this->email) or empty($this->email)) $this->email = "";
		return true;
	}
	
	public function count_interactions()
	{
		return $this->get_interaction_count();
	}

	public function get_interaction_count()
	{
		if(!$this->is_valid_id()) return -1;
		$conn = Yii::app()->db;
		$query = "
			SELECT		COUNT(*) as interaction_count
			FROM		{{attendees}}
			WHERE		cid = :cid;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":cid",$this->cid);
		$this->interaction_count = $command->queryScalar();

		return $this->interaction_count;
	}
	
	public function get_interaction_count_between_dates($startdate,$enddate)
	{
		if(!$this->is_valid_id()) return -1;
		$conn = Yii::app()->db;
		$query = "
			SELECT		COUNT(*) as interaction_count
			FROM		{{interactions}}
			WHERE		attendees LIKE :cid
			AND			meetingdate > :startdate
			AND			meetingdate < :enddate
		";
		$command = $conn->createCommand($query);
		$command->bindValue(":cid","%\"".$this->cid."\"%");
		$command->bindValue(":startdate",$startdate);
		$command->bindValue(":enddate",$enddate);
		$this->interaction_count = $command->queryScalar();

		return $this->interaction_count;
	}
	
	public function get_recent_interaction_count()
	{
		if(!isset($this->interactions)) $this->get_interactions();
		if(!isset($this->interactions) or count($this->interactions)==0)
		{
			$this->recent_interaction_count=0;
			return 0;
		}
		
		$interactions_set = array();
		foreach($this->interactions as $interaction)
		{
			if(!$interaction->loaded)
			{
				$this->remove_attendee_spot($interaction->interactionid);
				continue;
			}
			if(strtotime($interaction->meetingdate) > strtotime("-1 month"))
			{
				$interactions_set[] = $interaction;
			}
		}
		
		$this->recent_interaction_count = count($interactions_set);
		
		return $this->recent_interaction_count;
	}
	
	public function involvement()
	{
		if(!isset($this->recent_interaction_count)) $this->get_recent_interaction_count();
		if(!isset($this->recent_interaction_count)) return 0;
		
		if($this->recent_interaction_count<3 and $this->recent_interaction_count>0)
		{
			return 1;
		}
		else if($this->recent_interaction_count>=3 and $this->recent_interaction_count<10)
		{
			return 2;
		}
		else if($this->recent_interaction_count>10)
		{
			return 3;
		}
		else
		{
			return 0;
		}
		
		return 0;
	}

	private function remove_attendee_spot($interactionid)
	{
		$conn = Yii::app()->db;
		$query = "
			DELETE FROM			{{attendees}}
			WHERE						interactionid = :interactionid
			AND							cid = :cid;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":interactionid",$interactionid);
		$command->bindParam(":cid",$this->cid);
		$command->execute();
	}
	
	public function get_interactions()
	{
		if(!$this->is_valid_id()) return -1;
		$conn = Yii::app()->db;
		$query = "
			SELECT		interactionid
			FROM			{{interactions}}
			WHERE			attendees LIKE :attendees;
		";
		$command = $conn->createCommand($query);
		$command->bindValue(":attendees","%".$this->cid."%");
		$result = $command->queryAll();
		if(!$result or empty($result)) return array();

		$this->interactions = array();
		foreach($result as $row)
			$this->interactions[] = new InteractionObj($row["interactionid"]);

		usort($this->interactions,array("ContactObj","sort_interactions_by_meetingdate"));

		return $this->interactions;
	}

	static function sort_interactions_by_meetingdate($a, $b)
	{
		if(!isset($a->meetingdate)) return -1;
		if(!isset($b->meetingdate)) return +1;
        if ($a->meetingdate == $b->meetingdate) {
					return ($a->date_created > $b->date_created) ? -1 : +1;
        }
        return ($a->meetingdate > $b->meetingdate) ? -1 : +1;

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
	
	public function get_tag_list()
	{
		if(!isset($this->tags) or empty($this->tags)) return array();

		$taglist = array();
		$tags = explode(",",$this->tags);
		foreach($tags as $tag)
		{
			$tag = trim($tag);
			if($tag=="") continue;
			$taglist[] = $tag;
		}
		
		return $taglist;
	}
	
	public function get_primary_department()
	{
		$flag = true; $retnull = new stdClass;
		if(empty($this->departments)) return $retnull;
		
		foreach($this->departments as $dept)
		{
			if($flag)
			{
				$flag = false;
				$retnull = $dept;
			}
			if(empty($dept->positions)) continue;
			foreach($dept->positions as $pos)
			{
				if($pos->primary == 1) return $dept;
			}
		}
		
		return $retnull;
	}
	
	public function get_primary_position()
	{
		$flag = true; $retnull = new stdClass;
		if(empty($this->departments)) return $retnull;
		
		foreach($this->departments as $dept)
		{
			if(empty($dept->positions)) continue;
			foreach($dept->positions as $pos)
			{
				if($flag)
				{
					$flag = false;
					$retnull = $pos;
					$retnull_dept = $dept;
				}
				if($pos->primary == 1) return $pos;
			}
		}
		
		$this->set_primary_position($retnull_dept,$pos);
		
		return $retnull;
	}
	
	public function set_primary_position($dept,$pos)
	{
		if(!$this->has_position($dept->deptid,$pos)) return;
		
		$conn = Yii::app()->db;
		$query = "
			UPDATE			{{contact_departments}}
			SET 				`primary` = 1
			WHERE				cid = :cid
			AND					deptid = :deptid
			AND					posname = :posname;
		";
		
		$command = $conn->createCommand($query);
		$command->bindParam(":cid",$this->cid);
		$command->bindParam(":deptid",$dept->deptid);
		$command->bindParam(":posname",$pos->posname);
		$command->execute();
	}
	
	public function load_notes($username)
	{
		$notesobj = new NotesObj();
		$notesobj->username = $username;
		$notesobj->cid = $this->cid;
		$notesobj->load();
		if(!$notesobj->loaded) return "";
		
		return $notesobj->notes;
	}
	
	public function save_notes($username,$notes)
	{
		$notesobj = new NotesObj();
		$notesobj->username = $username;
		$notesobj->cid = $this->cid;
		$notesobj->notes = $notes;
		return $notesobj->save();
	}
	
	public function has_position($deptid,$posname)
	{
		if(is_object($posname) and isset($posname->posname))
			$posname = $posname->posname;
		
		$conn = Yii::app()->db;
		$query = "
			SELECT		COUNT(*) as has_position
			FROM			{{contact_departments}}
			WHERE			cid = :cid
			AND				deptid = :deptid
			AND				posname = :posname
			LIMIT			1;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":cid",$this->cid);
		$command->bindParam(":deptid",$deptid);
		$command->bindParam(":posname",$posname);
		
		return ($command->queryScalar()==1);
		
	}
	
	public function add_position($deptid,$posname)
	{
		if(is_object($posname) and isset($posname->posname))
			$posname = $posname->posname;
		if($this->has_position($deptid,$posname)) return false;
		if($this->has_position($deptid,""))
		{
			$query = "
				UPDATE			{{contact_departments}}
				SET 				posname = :posname
				WHERE				cid = :cid
				AND					deptid = :deptid
				AND					posname = '';
			";
		} else {
			$query = "
				INSERT INTO			{{contact_departments}}
				( `cid`, `deptid`, `posname` )
				VALUES
				( :cid, :deptid, :posname );
			";
		}
		$conn = Yii::app()->db;
		$command = $conn->createCommand($query);
		$command->bindParam(":cid",$this->cid);
		$command->bindParam(":deptid",$deptid);
		$command->bindParam(":posname",$posname);
		$command->execute();
		
		return true;
	}
	
	public function remove_position($deptid,$posname)
	{
		$conn = Yii::app()->db;
		$query = "
			DELETE FROM			{{contact_departments}}
			WHERE						cid = :cid
			AND							deptid = :deptid
			AND							posname = :posname;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":cid",$this->cid);
		$command->bindParam(":deptid",$deptid);
		$command->bindParam(":posname",$posname);
		$command->execute();
		
		return true;
	}
	
	public function get_positions_by_dept($deptid)
	{
		$conn = Yii::app()->db;
		$query = "
			SELECT		posname
			FROM			{{contact_departments}}
			WHERE			cid = :cid
			AND				deptid = :deptid
			ORDER BY	posname ASC;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":cid",$this->cid);
		$command->bindParam(":deptid",$deptid);
		$result = $command->queryAll();
		
		return $result;
	}
	
	public function text_permission_level()
	{
		if(isset($this->userobj->permission))
		{
			switch($this->userobj->permission)
			{
				case 0: return "Guest";
				case 1: return "Registered User";
				case 2: return "Manager";
				case 3: return "Administrator";
				case 10: return "Super Administrator";
				default: return "Unknown [".$this->userobj->permission."]";
			}
		}
		
		return "Guest";
	}
	
	public function load_interaction_count_by_dates($type)
	{
		if($type!="JSON") return "";
		
		if(isset($this->interactions))
		{
			foreach($this->interactions as $interaction)
			{
				$hash = date("Y-m-d",strtotime($interaction->meetingdate));
				if(!isset($count[$hash])) $count[$hash] = 0;
				$count[$hash] = $count[$hash] + 1;
			}
			if(!empty($count))
			{
				foreach($count as $hash=>$count)
				{
					$return[] = array($hash,$count);
				}
			} else return "";
			return json_encode($return);
		} else return "";
	}
	
	public function jqplot_load_interactions_by_person()
	{
		
		if(!isset($this->interactions)) $this->get_interactions();
		
		$ret = array();
		if(empty($this->interactions))
		{
			$ret[] = array("No Contacts",0);
			return json_encode($ret);
		}
		foreach($this->interactions as $interaction)
		{
			$attendees = $interaction->attendees;
			foreach($attendees as $attendee)
			{
				if($attendee==$this->cid) continue;
				$contact = new ContactObj($attendee);
				if(!$contact->loaded) continue;
				$fullname = $contact->firstname." ".$contact->lastname;
				if(!isset($return[$fullname])) $return[$fullname] = 0;
				$return[$fullname] = $return[$fullname] + 1;
			}
		}
		
		if(empty($return) or is_null($return))
		{
			$ret[] = array("No Contacts",0);
			return json_encode($ret);
		}
		
		arsort($return);
		$keys = array_keys($return);
		$values = array_values($return);
		$keys = array_splice($keys,0,7);
		$values = array_splice($values,0,7);
		$return = array_combine($keys,$values);
		ksort($return);
		if(!empty($return))
		{
			foreach($return as $item=>$count)
			{
				$ret[] = array($item,$count);
			}
		} else {
			$ret[] = array("No Contacts",0);
		}
		return json_encode($ret);
	}
	
	public function jqplot_load_interactions_by_dept()
	{
		
		if(!isset($this->interactions)) $this->get_interactions();
		if(empty($this->interactions))
		{
			$ret[] = array("No Departments",0);
			return json_encode($ret);
		}
		
		$conn = Yii::app()->db;
		$query = "
			SELECT		D.deptname
			FROM			{{departments}} AS D, {{contact_departments}} AS CD
			WHERE			CD.cid = :cid
			AND				D.deptid = CD.deptid
			LIMIT			1;
		";
		$command = $conn->createCommand($query);
		$ret = array();
		foreach($this->interactions as $interaction)
		{
			$attendees = $interaction->attendees;
			$round_dept = array();
			foreach($attendees as $attendee)
			{
				if($attendee==$this->cid) continue;
				$command->bindParam(":cid",$attendee);
				$deptname = $command->queryScalar();
				if(!$deptname) continue;
				if(!isset($return[$deptname])) $return[$deptname] = 0;
				if(!in_array($deptname,$round_dept))
				{
					$return[$deptname] = $return[$deptname] + 1;
					$round_dept[] = $deptname;
				}
			}
		}
		
		if(empty($return) or is_null($return))
		{
			$ret[] = array("No Departments",0);
			return json_encode($ret);
		}		
		arsort($return);
		$keys = array_keys($return);
		$values = array_values($return);
		$keys = array_splice($keys,0,7);
		$values = array_splice($values,0,7);
		$return = array_combine($keys,$values);
		ksort($return);
		if(!empty($return))
		{
			foreach($return as $item=>$count)
			{
				$ret[] = array($item,$count);
			}
		} else {
			$ret[] = array("No Departments",0);
		}
		return json_encode($ret);
	}
	
}

?>