<?php namespace App\Models\Objects;

use DB;
use App\Models\System\FactoryObj;
use App\Models\Objects\ContactObj;

class InteractionObj extends FactoryObj {

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
            foreach($tags as $tag) {
                $tag = trim($tag);
                if(!in_array($tag,$taglist)) {
                    $taglist[] = $tag;
                }
            }
            $this->tags = implode(", ",$taglist);
        }
        if(!isset($this->tags) or $this->tags=="") {
            $this->tags = "untagged";
        }

        if((!isset($this->username) or empty($this->username)) and !Yii::app()->user->isGuest) {
            $this->username = Yii::app()->user->name;
        }
        $this->meetingdate = date("Y-m-d H:i:s",strtotime($this->meetingdate));
        $this->date_modified = date("Y-m-d H:i:s");

        // Convert Departments Array to string for database/class conversion
        if(isset($this->attendees) and is_array($this->attendees)) {
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

        if(!empty($this->attendees)) {
            foreach($this->attendees as $cid) {
                if(!$this->attendee_exists($cid)) {
                    $this->add_attendee($cid);
                }
            }
        }
    }

    public function post_load()
    {
        // Empty relationships? array it!
        if(is_null($this->attendees)) {
            $this->attendees = [];
        }

        // Convert Departments Array to string for database/class conversion
        if(isset($this->attendees) and is_string($this->attendees) and !empty($this->attendees) and $this->attendees != '[""]') {
            $this->attendees = (array)json_decode($this->attendees);
        }
        else if(empty($this->attendees) or $this->attendees == '[""]') {
            $this->attendees = [];
        }
    }

    public function get_attendees_list()
    {
        $list_of_names = DB::table('interaction_attendees')
            ->where('interactionid',$this->interactionid)
            ->lists('fullname');
        return implode(', ',$list_of_names);
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
        if(!$this->is_valid_id() or $this->attendee_exists($contact)) {
            return false;
        }
        $cid = ($contact instanceof ContactObj) ? $contact->cid : $contact;

        DB::table('interaction_attendees')
            ->insert(
                [
                    "interactionid" => $this->interactionid,
                    "cid"           => $cid,
                    "fullname"      => ContactObj::get_fullname($cid)
                ]
            );
        return true;
    }

    public function remove_attendee($contact)
    {
        if(!$this->is_valid_id() or $this->attendee_exists($contact)) {
            return false;
        }
        $cid = ($contact instanceof ContactObj) ? $contact->cid : $contact;

        DB::table('interaction_contacts')
            ->where('interactionid', $this->interactionid)
            ->where('cid', $cid)
            ->delete();
    }

    public function attendee_exists($contact)
    {
        if(!$this->is_valid_id()) {
            return false;
        }
        $cid = ($contact instanceof ContactObj) ? $contact->cid : $contact;

        return DB::table('interaction_attendees')
                ->where('interactionid', $this->interactionid)
                ->where('cid', $cid)
                ->count() >= 1;
    }

    public function remove_all_attendees()
    {
        $attendees = $this->get_all_attendees();
        if(empty($attendees)) return true;

        foreach($attendees as $attendee) {
            $this->remove_attendee($attendee);
        }

        return true;
    }

    public function get_all_attendees()
    {
        return DB::table('interaction_attendees')
            ->where('interactionid', $this->interactionid)
            ->lists('cid');
    }

    public function run_check()
    {
        if(!isset($this->meetingdate) or $this->meetingdate == "") {
            return !$this->set_error("Meeting date must be set. You can't not have a meeting date.");
        }

        if(!isset($this->attendees) or empty($this->attendees)) {
            return !$this->set_error("There must be someone that attended this interaction.");
        }

        if(!isset($this->notes) or strip_tags($this->notes)=="") {
            return !$this->set_error("Notes cannot be empty. Something must have happened at the interaction.");
        }

        return true;
    }

    public function get_attendees_count()
    {
        return DB::table('interaction_attendees')
            ->where('interactionid', $this->interactionid)
            ->count();
    }

    public function get_attendees_objects()
    {
        $cids = $this->get_all_attendees();
        $contacts = [];
        foreach($cids as $cid) {
            $contacts[] = new ContactObj($cid);
        }
        return $contacts;
    }

}