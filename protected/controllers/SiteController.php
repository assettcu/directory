<?php

class SiteController extends Controller
{
	/**
	 * Declares class-based actions.
	 */
	public function actions()
	{
		return array(
			// captcha action renders the CAPTCHA image displayed on the contact page
			'captcha'=>array(
				'class'=>'CCaptchaAction',
				'backColor'=>0xFFFFFF,
			),
			// page action renders "static" pages stored under 'protected/views/site/pages'
			// They can be accessed via: index.php?r=site/page&view=FileName
			'page'=>array(
				'class'=>'CViewAction',
			),
		);
	}
	
	public function actionDepartments()
	{
		$this->render("departments");
	}
	
	public function actionReport()
	{
		$this->render("report");
	}
	
	public function actionRunonce()
	{
		$this->noGuest();
		$this->render("runonce");
	}

	public function actionChat()
	{
		$this->render("chat");
	}
    
    public function actionNewDepartment()
    {
        $this->noGuest();
        if(isset($_REQUEST["deptname"])) {
            $dept = new DepartmentObj();
            $dept->deptname     = $_REQUEST["deptname"];
            $dept->shortname    = $_REQUEST["shortname"];
            $dept->abbr         = $_REQUEST["abbr"];
            $dept->college      = $_REQUEST["college"];
            $dept->website      = $_REQUEST["website"];
            
            if($dept->save()) {
                Yii::app()->user->setFlash("success","Successfully added new department!");
                $this->redirect("dept?deptid=".$dept->deptid);
            }
            else {
                Yii::app()->user->setFlash("error","Could not save department.\n".$dept->get_error());
            }
        }
        
        $this->render("newdepartment");
    }
    
	/**
	 * This is the default 'index' action that is invoked
	 * when an action is not explicitly requested by users.
	 */
	public function actionIndex()
	{
        $this->noGuest();
        
        $COREUSER = new UserObj(Yii::app()->user->name);
        $COREUSER->get_contact();
        
        $params["COREUSER"] = $COREUSER;
        
		$this->render('index',$params);
	}

	public function actionContact()
	{
		$this->render('contact');
	}
	
	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
	    if($error=Yii::app()->errorHandler->error)
	    {
	    	if(Yii::app()->request->isAjaxRequest)
	    		echo $error['message'];
	    	else
	        	$this->render('error', $error);
	    }
	}

	public function actionDept()
	{
		if(!isset($_REQUEST["deptid"]))
		{
			$this->redirect(Yii::app()->createUrl('index'));
			exit;
		}
		
		$this->render("dept");
	}
	
	public function actionCustomReport()
    {
        if(!isset($_REQUEST["cid"],$_REQUEST["date1"],$_REQUEST["date2"])) {
            Yii::app()->user->setFlash("error","Invalid parameters sent.");
            $this->redirect(Yii::app()->baseUrl);
            exit;
        }
        $contact = new ContactObj($_REQUEST["cid"]);
        $date1 = $_REQUEST["date1"];
        $date2 = $_REQUEST["date2"];
        
        if(empty($date1) or empty($date2)) {
            return false;
        }
        
        $result = Yii::app()->db->createCommand()
            ->select("interactionid")
            ->from("interactions")
            ->where(
                "attendees LIKE :attendees AND meetingdate > :startdate AND meetingdate < :enddate", 
                array(
                    ":attendees"    => "%\"".$contact->cid."\"%",
                    ":startdate"    => $date1,
                    ":enddate"      => $date2
                )
            )
            ->order("meetingdate DESC")
            ->queryAll();
        
        $headers = false;
        
        foreach($result as $row) {
            $interaction = new InteractionObj($row["interactionid"]);
            $contacts = $interaction->get_all_attendees();
            $attendees = array();
            $depts = array();
            foreach($interaction->attendees as $attendee) {
                if($attendee == $contact->cid) continue;
                $attendee = new ContactObj($attendee);
                foreach($attendee->departments as $dept) {
                    $depts[] = ($dept->shortname != null) ? $dept->shortname : $dept->deptname;
                }
                $attendees[] = $attendee->firstname." ".$attendee->lastname;
            }
            if(!$headers) {
                $output[] = array(
                    "interactionid" => "ID",
                    "meetingdate"   => "Meeting Date",
                    "createdby"     => "Created By",
                    "attendees"     => "Attendees",
                    "departments"   => "Departments",
                    "notes"         => "Notes",
                );
                $headers = true;
            }
            $output[] = array(
                "interactionid"     => $interaction->interactionid,
                "meetingdate"       => StdLib::format_date($interaction->meetingdate,"normal-notime"),
                "createdby"         => $interaction->username,
                "attendees"         => implode(", ",$attendees),
                "departments"       => implode("; ",array_unique($depts)),
                "notes"             => $interaction->notes
            );
        }
            
        $datetime = time();
        $targetDir = ROOT.'/tmp/'.Yii::app()->user->name."/";
        if(!is_dir($targetDir)) {
            mkdir($targetDir);
        }
        $targetFile = $targetDir.'export-'.$datetime.'.csv';
        
        $fp = fopen($targetFile,'w');
        foreach($output as $fields) {
            fputcsv($fp,$fields);
        }
        fclose($fp);
        
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/force-download');
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename='.basename($targetFile));
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($targetFile));
        ob_clean();
        ob_flush();
        flush();
        readfile($targetFile);
        unlink($targetFile);
        exit;
    }
	/** AJAX FUNCTIONS BELOW **/
	
	public function action_load_selected_contacts_emails()
	{
		$cids = $_REQUEST["cids"];
		$cids = explode(",",$cids);
		$conn = Yii::app()->db;
		$WHERE = "";
		if(empty($cids)) return print "";
		
		foreach($cids as $cid)
		{
			$WHERE .= "cid = '$cid' OR ";
		}
		$WHERE = substr($WHERE,0,-3);
		$query = "
			SELECT			email
			FROM			{{contacts}}
			WHERE			$WHERE
			ORDER BY		email ASC;
		";
		$result = $conn->createCommand($query)->queryAll();
		if(!$result or empty($result)) return print "";
		$return = "";
		foreach($result as $row)
		{
			if($row["email"]=="") continue;
			$return .= $row["email"]."; ";
		}
		$return = substr($return,0,-2);
		
		return print $return;
	}
	
	public function action_ajax_filter_tags()
	{
		$filter = $_REQUEST["term"];
		$Directory = new DirectoryObj();
		$tags = $Directory->load_all_tags();
		
		$return = array();
		foreach($tags as $tag)
		{
			if(stristr($tag,$filter))
				$return[] = array("id"=>$tag,"label"=>$tag,"value"=>$tag);
		}
		
		print json_encode($return);
		return;
	}
	
	public function action_load_filter_tags()
	{
		$Directory = new DirectoryObj();
		$tags = $Directory->load_all_tags();
		
		ob_start();
		?>
			<option value="">Any Tag</option>
		<?php
		foreach($tags as $tag)
		{
			if($tag=="") continue;
		?>
			<option value="<?=$tag?>"><?=$tag?></option>
		<?php
		}
		$contents = ob_get_contents();
		ob_end_clean();
		
		print $contents;
	}
	
	public function action_load_department_tags()
	{
		$Directory = new DirectoryObj();
		$departments = $Directory->load_all_depts();
		
		ob_start();
		?>
			<option value="">Any department</option>
		<?php
		foreach($departments as $dept)
		{
		?>
			<option value="<?=$dept->deptid?>"><?=$dept->deptname?></option>
		<?php
		}
		$contents = ob_get_contents();
		ob_end_clean();
		
		print $contents;
		
	}
	
	public function action_load_dept_contacts()
	{
		$dept = new DepartmentObj($_REQUEST["deptid"]);
		$contacts = $dept->load_contacts();
		
		ob_start();
		foreach($contacts as $contact)
		{
			$box = new WidgetBox();
			$box->header = $contact->lastname.", ".$contact->firstname;
			$box->content = "";
			$box->render();
		}
		$content = ob_get_contents();
		ob_end_clean();
		
		print $content;
		return;
	}
	
	public function action_update_contact_name()
	{
		$this->noGuest();
		$contact = new ContactObj($_REQUEST["cid"]);
		if(!$contact->loaded) return print "Contact could not be found.";
		
		$oldname = $contact->firstname . " " .$contact->lastname;
		$newname = $_REQUEST["firstname"] . " " . $_REQUEST["lastname"];
		
		if($oldname == $newname) return print 1;
		
		$contact->firstname = $_REQUEST["firstname"];
		$contact->lastname = $_REQUEST["lastname"];
		$log = new LogObj;
		if(!$contact->save())
		{
			$log->type = "error";
			$log->log_message = "Attempting to update contact(".$contact->cid.") name from \"$oldname\" to \"$newname\".\n";
			$log->log_message .= $contact->get_error();
			if(!$log->save()) die($log->get_error());
			return print $log->log_message;
		}
		$log->type = "update";
		$log->log_message = "Successfully updated contact (".$contact->cid.") name from \"$oldname\" to \"$newname\".";
		$log->save();
		return print 1;
	}
	
	public function action_add_contact_name()
	{
		$this->noGuest();
		$contact = new ContactObj();
		
		$newname = $_REQUEST["firstname"] . " " . $_REQUEST["lastname"];
		
		$contact->firstname = $_REQUEST["firstname"];
		$contact->lastname = $_REQUEST["lastname"];
		$log = new LogObj;
		$contact->load();
		if($contact->loaded) return print $contact->cid;
		if(!$contact->save())
		{
			$log->type = "error";
			$log->log_message = "Attempting to create new contact \"$newname\".\n";
			$log->log_message .= $contact->get_error();
			if(!$log->save()) die($log->get_error());
			return print $log->log_message;
		}
		$log->type = "insert";
		$log->log_message = "Successfully created new contact \"$newname\".";
		$log->save();
		return print 1;
	}
	
	public function action_add_department_name()
	{
		$this->noGuest();
		$dept = new DepartmentObj();
		
		$newname = $_REQUEST["deptname"];
		$dept->deptname = $newname;

		$log = new LogObj;
		if(!$dept->save())
		{
            var_dump($dept->get_error());
		}
		$log->type = "insert";
		$log->log_message = "Successfully created new department \"$newname\".";
		$log->save();
		return print $dept->deptid;
	}
	
	public function action_delete_contact()
	{
		$this->noGuest();
		$contact = new ContactObj($_REQUEST["cid"]);
		if(!$contact->loaded) return print "Contact could not be found.";
		
		$name = $contact->firstname . " " .$contact->lastname;
		
		$log = new LogObj;
		if(!$contact->delete())
		{
			$log->type = "error";
			$log->log_message = "Attempting to delete contact(".$contact->cid.") \"$name\".\n";
			$log->log_message .= $contact->get_error();
			if(!$log->save()) die($log->get_error());
			return print $log->log_message;
		}
		$log->type = "delete";
		$log->log_message = "Successfully deleted contact (".$contact->cid.") \"$name\".";
		$log->save();
		return print 1;
	}
	
	public function action_save_notes()
	{
		if(Yii::app()->user->isGuest) return print "You need ot be logged in to save notes.";
		$contact = new ContactObj($_REQUEST["cid"]);
		if(!$contact->loaded) return print "Contact could not be found.";
		
		$user = new UserObj(Yii::app()->user->name);
		if(!$user->loaded) return print "You need to be logged in to save notes.";
		
		
		$oldnotes = $contact->load_notes($user->username);
		$newnotes = $_REQUEST["notes"];
		
		# Save the notes
		if(!$contact->save_notes($user->username,$newnotes))
		{
			$log = new LogObj;
			$log->type = "error";
			$log->log_message = "Attempting to update contact(".$contact->cid.") notes from \"$oldnotes\" to \"$newnotes\".\n";
			$log->log_message .= $contact->get_error();
			if(!$log->save()) die($log->get_error());
			return print $log->log_message;
		}
		return print 1;
	}
	
	public function action_save_widget_order()
	{
		$this->noGuest();
		$order = $_REQUEST["order"];
		$type = $_REQUEST["type"];
		$user = new UserObj(Yii::app()->user->name);
		if(!$user->loaded) return print "Could not find user with name '".Yii::app()->user->name."'.";
		
		$user->preferences->{"contact"}->$type = explode(",",$order);
		if(!$user->save())
		{
			$log = new LogObj;
			$log->type = "error";
			$log->log_message = "Attempting to update contact(".$contact->cid.") preferences: widget order ('type'=>$type , 'order'=>$order).\n";
			$log->log_message .= $contact->get_error();
			if(!$log->save()) die($log->get_error());
			return print $log->log_message;
		}
		return print 1;
	}
	
	public function action_save_contact_bio()
	{
		$this->noGuest();
		$contact = new ContactObj($_REQUEST["cid"]);
		if(!$contact->loaded) return print "Contact could not be found.";
		
		$contact->bio = $_REQUEST["bio"];
		$name = $contact->firstname . " " .$contact->lastname;
		$log = new LogObj;
		if(!$contact->save())
		{
			$log->type = "error";
			$log->log_message = "Attempting to update bio of contact (".$contact->cid.") \"$name\" bio.\n";
			$log->log_message .= $contact->get_error();
			if(!$log->save()) die($log->get_error());
			return print $log->log_message;
		}
		$log->type = "update";
		$log->log_message = "Successfully updated bio of contact (".$contact->cid.") \"$name\".";
		$log->save();
		return print 1;
	}
	
	public function action_save_contact()
	{
		// Must be an administrator or the actual user saving their information
		if(!$this->is_level(3) and Yii::app()->user->cid != $_REQUEST["cid"])
			return print "You do not have permission to edit this contact.";
		
		$contact = new ContactObj($_REQUEST["cid"]);
		if(!$contact->loaded) return print "Contact could not be found.";
		
		$name = $contact->firstname . " " .$contact->lastname;
		foreach($_REQUEST as $item=>$value)
		{
			$contact->{$item} = $value;
		}
		$log = new LogObj;
		if(!$contact->save())
		{
			$log->type = "error";
			$log->log_message = "Attempting to update information of contact (".$contact->cid.") \"$name\" bio.\n";
			$log->log_message .= $contact->get_error();
			if(!$log->save()) die($log->get_error());
			return print $log->log_message;
		}
		$name = $contact->firstname . " " .$contact->lastname;
		$log->type = "update";
		$log->log_message = "Successfully updated information of contact (".$contact->cid.") \"$name\".";
		$log->save();
		
		if(isset($contact->username) and $contact->username != "")
		{
			$user = new UserObj($contact->username);
			if(isset($_REQUEST["permission"]))
			{
				$permission = $_REQUEST["permission"];
				if($permission > @Yii::app()->user->userobj->permission)
					$permission = @Yii::app()->user->userobj->permission;
				$user->permission = $permission;
				$user->email = $user->username."@colorado.edu";
				$user->active = @$_REQUEST["makeuser"];
				if(!$user->save())
					return print $user->get_error();
			}
		}
		
		return print 1;
	}
	
	public function action_add_contact_tag()
	{
		$this->noGuest();
		$contact = new ContactObj($_REQUEST["cid"]);
		if(!$contact->loaded) return print "Contact could not be found.";
		
		$tags = explode(",",$contact->tags);
		for($a=0;$a<count($tags);$a++)
		{
			$trimmed = trim($tags[$a]);
			if($trimmed=="") {
				unset($tags[$a]);
				continue;
			}
			$tags[$a] = $trimmed;
		}
		$tags = array_flip($tags);
		$atags = explode(",",$_REQUEST["tags"]);
		for($a=0;$a<count($atags);$a++)
		{
			$trimmed = trim($atags[$a]);
			if($trimmed=="") {
				unset($atags[$a]);
				continue;
			}
			$atags[$a] = $trimmed;
		}
		$atags = array_flip($atags);
		$tags = array_keys(array_merge($tags,$atags));
		$contact->tags = implode(",",$tags);
		if(substr($contact->tags,0,1)==",") $contact->tags = substr($contact->tags,1);
		if(!$contact->save())
		{
			$name = $contact->firstname . " " .$contact->lastname;
			$log = new LogObj;
			$log->type = "error";
			$log->log_message = "Attempting to update tags of contact (".$contact->cid.") \"$name\" bio.\n";
			$log->log_message .= $contact->get_error();
			if(!$log->save()) die($log->get_error());
			return print $log->log_message;
		}
		return print json_encode(array("tags"=>explode(",",$contact->tags)));
	}
	
	public function action_remove_contact_tag()
	{
		$this->noGuest();
		$contact = new ContactObj($_REQUEST["cid"]);
		if(!$contact->loaded) return print "Contact could not be found.";
		
		$tags = array_flip(explode(",",$contact->tags));
		unset($tags[$_REQUEST["tag"]]);
		$contact->tags = implode(",",array_keys($tags));
		if(!$contact->save())
		{
			$name = $contact->firstname . " " .$contact->lastname;
			$log = new LogObj;
			$log->type = "error";
			$log->log_message = "Attempting to update tags of contact (".$contact->cid.") \"$name\" bio.\n";
			$log->log_message .= $contact->get_error();
			if(!$log->save()) die($log->get_error());
			return print $log->log_message;
		}
		return print 1;
	}
	
	public function action_load_quick_contacts()
	{
		$q = $_REQUEST["q"];
		$Directory = new DirectoryObj();
		$contacts = $Directory->load_contacts_by_name($q);
		
		$return = array();
		foreach($contacts as $contact)
			$return[] = array("id"=>$contact->cid,"name"=>$contact->firstname." ".$contact->lastname);
		
		return print json_encode($return);
	}
	
	public function action_load_quick_departments()
	{
		$q = $_REQUEST["q"];
		$Directory = new DirectoryObj();
		$depts = $Directory->load_departments_by_name($q);
		
		$return = array();
		foreach($depts as $dept)
			$return[] = array("id"=>$dept->deptid,"name"=>$dept->deptname);
		
		return print json_encode($return);
	}
	
	public function action_get_positions_from_department()
	{
		$deptid = $_REQUEST["deptid"];
		
		$dept = new DepartmentObj($deptid,true);
		if(empty($dept->dept_positions)) return print json_encode(array());
		
		$return = array();
		foreach($dept->dept_positions as $pos)
		{
			if(!in_array($pos,$return))
				$return[] = array("posname"=>$pos);
		}
		
		return print json_encode($return);
	}
	
	public function action_remove_position_from_contact()
	{
		$posname = $_REQUEST["posname"];
		$deptid = $_REQUEST["deptid"];
		$cid = $_REQUEST["cid"];
		
		$contact = new ContactObj($cid);
		$contact->remove_position($deptid,$posname);
		
		return print 1;
	}
	
	public function action_add_position_to_contact()
	{	
		$posname = $_REQUEST["posname"];
		$deptid = $_REQUEST["deptid"];
		$cid = $_REQUEST["cid"];
		
		$contact = new ContactObj($cid);
		if(!$contact->add_position($deptid,$posname))
			return print 0;
		
		return print 1;
	}
	
	public function action_add_position_to_department()
	{
		$posname = $_REQUEST["posname"];
		$deptid = $_REQUEST["deptid"];
		
		$department = new DepartmentObj($deptid);
		if(!$department->loaded)
		{
			return print "Department cannot be found!";
		}
		$department->add_position($posname);
		
		return print 1;
	}
	
	public function action_edit_position_to_department()
	{
		$posname = $_REQUEST["posname"];
		$oldname = $_REQUEST["oldname"];
		$deptid = $_REQUEST["deptid"];
		
		$department = new DepartmentObj($deptid);
		if(!$department->loaded)
		{
			return print "Department cannot be found!";
		}
		$department->rename_position($oldname,$posname);
		
		return print 1;
		
	}
	
	public function action_bulk_delete_positions()
	{
		$positions = $_REQUEST["positions"];
		$positions = explode("|||",$positions);
		$deptid = $_REQUEST["deptid"];
		
		$department = new DepartmentObj($deptid);
		if(!$department->loaded) return false;
		foreach($positions as $pos)
		{
			$department->remove_position($pos);
		}
		return print 1;
	}
	
	public function action_delete_department()
	{
		$deptid = $_REQUEST["deptid"];
		
		$department = new DepartmentObj($deptid);
		if(!$department->loaded)
		{
			return print "Department cannot be found!";
		}
		$department->delete();
		return print 1;
	}
	
	public function action_remove_contact_department()
	{
		$deptid = $_REQUEST["deptid"];
		$cid = $_REQUEST["cid"];
		
		$contact = new ContactObj($cid);
		if(!$contact->remove_department($deptid))
			return print 0;
		
		return print 1;
		
	}
	
	public function action_load_interaction()
	{
		$interactionid = $_REQUEST["interactionid"];
		$interaction = new InteractionObj($interactionid);
		
		$return = array();
		$return["notes"] = $interaction->notes;
		$attendees = $interaction->load_contact_objects();
		$return["date"] = StdLib::format_date($interaction->date_created,"d M Y");
		
		if(count($attendees)>0)
		{
			foreach($attendees as $attendee)
			{
				$return["attendees"][] = array("cid"=>$attendee->cid,"name"=>$attendee->firstname." ".$attendee->lastname);
			}
		}
		
		$return["tags"] = $interaction->tags;
		
		return print json_encode($return);
	}
	
	public function action_delete_interaction()
	{
		$interactionid = $_REQUEST["interactionid"];
		$interaction = new InteractionObj($interactionid);
		$interaction->delete();
        Yii::app()->user->setFlash("success","Successfully deleted interaction.");
		return print 1;
	}
	
	public function action_save_quick_interaction()
	{
		$this->noGuest();
		$user = new UserObj(Yii::app()->user->name);
		$interaction = new InteractionObj(@$_REQUEST["interactionid"]);
		$interaction->meetingdate = date("Y-m-d",strtotime($_REQUEST['d']));
		$interaction->duration = "9001";
		$interaction->notes = $_REQUEST["notes"];
		$interaction->tags = $_REQUEST["tags"];
		$interaction->attendees = explode(",",$_REQUEST["a"]);
		$interaction->username = $user->username;
		
		$log = new LogObj;
		if(!$interaction->save())
		{
            Yii::app()->user->setFlash("error","Cannot save interaction. ".$interaction->get_error());
			$log->type = "error";
			$log->log_message = "Attempting to save interaction for ".$interaction->username.".\n";
			$log->log_message .= $contact->get_error();
			if(!$log->save()) die($log->get_error());
			return print $log->log_message;
		}
		$log->type = "insert";
		$log->log_message = "Successfully saved new interaction (".$interaction->interactionid.")";
		$log->save();
        
        Yii::app()->user->setFlash("success",$log->log_message);
        
		return print 1;
	}
	
	public function action_load_contacts_table()
	{
		$page = $_REQUEST["page"];
		$pagelength = isset($_REQUEST["pl"])?$_REQUEST["pl"]:10;
		$sort = $_REQUEST["sort"];
		$query = $_REQUEST["f"];
		$tag = $_REQUEST["tag"];
		$letter = $_REQUEST["letter"];
		$dept = $_REQUEST["dept"];
		$Directory = new DirectoryObj();
		
		$start = ($page-1)*$pagelength;
		$finish = $start + $pagelength;
		
		if(isset($sort)){
			switch($sort){
				case "name": 		$orderby = "C.lastname ASC, C.firstname ASC"; break;
				case "name_r":	$orderby = "C.lastname DESC, C.firstname DESC"; break;
				default: $orderby = "";
			}
		}
		
		if($query!="" or $tag !="" or $letter!="" or $dept!="")
		{
			$contacts = $Directory->filter_contacts($query,$start>=0?$start:0,$pagelength,$orderby,$tag,$letter,$dept);
			$contact_count = $Directory->count_contacts($query,$tag,$letter,$dept);
		} else {
			$contacts = $Directory->load_contacts($start>=0?$start:0,$pagelength,$orderby);
			$contact_count = $Directory->count_contacts();
		}
		if($finish > $contact_count) $finish = $contact_count;
		ob_start();
		?>
			<div class="contacts-table">
			<table cellpadding="0" cellspacing="0">
				<thead>
					<tr>
						<th class="first check"></th>
						<th class="name<?=($sort=="name" or $sort=="name_r")?" current":"";?>">
							<a href="#" sort="name<?=($sort=="name")?"_r":""?>" class="sort<?=($sort=="name" or $sort=="name_r")?" current":"";?><?=($sort=="name")?" descending":" ascending"?>" title="Contact name">Name</a>
						</th>
						<th class="department">
							<a href="#" sort="department" title="Department name">Department</a>
						</th>
						<th class="position">
							<a href="#" sort="position" title="Position name">Position</a>
						</th>
						<th class="email">
							<a href="#" sort="email" title="Email">Email</a>
						</th>
						<th class="last edit"></th>
					</tr>
				</thead>
				<tbody>
					<?php if(count($contacts)>0): ?>
					<?php foreach($contacts as $contact): if(!$contact->loaded) continue; ?>
					<tr cid="<?=$contact->cid?>" ctype="person">
						<td class="check"><input class="contact_checked" type="checkbox" value="<?=$contact->cid;?>" /></td>
						<td class="name" cid="<?=$contact->cid;?>">
							<div class="box">
								<a href="<?=Yii::app()->createUrl('contact');?>?cid=<?=$contact->cid;?>" class="view-contact" title="<?=@$contact->tags?>"><?=@$contact->lastname.", ".@$contact->firstname;?></a>
								<?php if(!Yii::app()->user->isGuest):?><a href="#" title="Edit" class="se edit-name"></a><?php endif; ?>
							</div>
						</td>
						<td class="department">
							<div class="box">
								<a href="<?=Yii::app()->createUrl('dept');?>?deptid=<?=@$contact->get_primary_department()->deptid?>"><?=@$contact->get_primary_department()->deptname;?></a>
							</div>
						</td>
						<td class="position">
							<div class="box">
								<?=@$contact->get_primary_position()->posname;?>
							</div>
						</td>
						<td class="email">
							<div class="box">
								<?=strtolower($contact->email);?>
							</div>
						</td>
						<td class="edit">
							<a class="delete" href="#delete" title="Delete this person">Delete</a>
						</td>
					</tr>
					<?php endforeach; ?>
					<?php else: ?>
					<tr>
						<td colspan="5">
							<div class="empty-table">
								No contacts found. Click <a href="<?=Yii::app()->createUrl('index');?>">here</a> to view all contacts.
							</div>
						</td>
					</tr>
					
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
		<?php
		$contents = ob_get_contents();
		ob_end_clean();
		
		
		ob_start();
		?>
			<?php if($start==0): ?>
				<span class="disabled first_page">First</span>
				<span class="disabled prev_page">Prev</span>
			<?php else: ?>
				<a href="#" class="first_page">First</a>
				<a href="#" class="prev_page">Prev</a>
			<?php endif; ?>
			
			<span class="counts current"><?=$start?>-<?=$finish?> of <?=$contact_count;?></span>
			
			<?php if($finish==$contact_count): ?>
				<span class="disabled next_page">Next</span>
				<span class="disabled last_page">Last</span>
			<?php else: ?>
				<a href="#" class="next_page">Next</a>
				<a href="#" class="last_page">Last</a>
			<?php endif; ?>
		
		<?php
		$paging = ob_get_contents();
		ob_end_clean();
		
		$return = array();
		$return["contents"] = $contents;
		$return["paging"] = $paging;
		$return["start"] = $start;
		$return["finish"] = $finish;
		$return["total"] = $contact_count;
		$return["ended"] = ($finish==$contact_count);
		
		print json_encode($return);
		return;
	}
	
	/**
	 * Displays the login page
	 */
	public function actionLogin()
	{
		if(!Yii::app()->user->isGuest)
			Yii::app()->user->logout();
		$this->makeSSL();
			$model = new LoginForm;
		$redirect = (isset($_REQUEST["redirect"])) ? $_REQUEST["redirect"] : "index";
		$error = "";

		// collect user input data
		if (isset($_POST['username']) and isset($_POST["password"])) {
				$model->username = $_POST["username"];
				$model->password = $_POST["password"];
				// validate user input and redirect to the previous page if valid
				if ($model->validate() && $model->login())
						$this->redirect($redirect);
				else
				{
					# Get Error from the login model
					$error = $model->getError('account');
					
					# Log the error in the DB
					$log = new LogObj();
					$log->ipaddress = $_SERVER["SERVER_ADDR"];
					$log->username = "Guest";
					$log->log_message = "Attempted to log in as '".$_POST["username"]."'. Returned error: ".$error;
					$log->type = "login";
					$log->date_logged = date("Y-m-d H:i:s");
					$log->save();
				}
		}
		// display the login form
		$this->render('login', array('model' => $model,"error"=>$error));
	}

	/**
	 * Logs out the current user and redirect to homepage.
	 */
	public function actionLogout()
	{
		Yii::app()->user->logout();
		$this->redirect(Yii::app()->homeUrl);
	}
	

	private function makeSSL()
	{
		if($_SERVER['SERVER_PORT'] != 443) {
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
			exit();
		}
	}

	private function makeNonSSL()
	{
		if($_SERVER['SERVER_PORT'] == 443) {
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
			exit();
		}
	}
	
	private function noGuest()
	{
		if(Yii::app()->user->isGuest)
		{
			$this->redirect(Yii::app()->createUrl('login')."?redirect=http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		}
	}

	private function adminOnly()
	{
		if(!isset(Yii::app()->user->permission) or (Yii::app()->user->isGuest and Yii::app()->user->permission!="admin"))
		{
			$this->redirect(Yii::app()->createUrl('login')."?redirect=http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		}
	}

	private function managerOnly()
	{
		if(!isset(Yii::app()->user->permission) or (Yii::app()->user->permission=="basic"))
		{
			$this->redirect(Yii::app()->createUrl('login')."?redirect=http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		}
	}

	private function basicOnly()
	{
		if(!isset(Yii::app()->user->permission) or (Yii::app()->user->isGuest and Yii::app()->user->permission!="admin" and Yii::app()->user->permission!="manager" and Yii::app()->user->permission!="basic"))
		{
			$this->redirect(Yii::app()->createUrl('login')."?redirect=http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		}
	}
	
	private function is_level($level)
	{
		return (isset(Yii::app()->user->userobj) and Yii::app()->user->userobj->permission>=$level);
	}
	
	
}