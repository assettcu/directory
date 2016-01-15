<?php
namespace App\Models\Objects;

use DB;
use App\Models\System\FactoryObj;

class ContactObj extends FactoryObj
{

    public $userobj;
    public $username;
    public $departments = [];
    public $interaction_count = null;

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
            $this->username = strtolower(trim($this->username));
            $this->cid = DB::table($this->table)
                ->where('username', $this->username)
                ->pluck('cid');
        }

        // Find the username by first name and last name, if possible
        if(isset($this->fullname) and !$this->is_valid_id())
        {
            $this->cid = DB::table($this->table)
                ->where('fullname', $this->fullname)
                ->pluck('cid');
        }

        // Find the username by first name and last name, if possible
        if(isset($this->firstname,$this->lastname) and !$this->is_valid_id())
        {
            $this->cid = DB::table($this->table)
                ->where('firstname',$this->firstname)
                ->where('lastname',$this->lastname)
                ->pluck('cid');
        }

        if(isset($this->email) and !$this->is_valid_id())
        {
            $this->cid = DB::table($this->table)
                ->where('email',$this->email)
                ->pluck('cid');
        }
    }

    // Method to run after succesfully loading
    public function post_load()
    {
        if(!$this->loaded) return false;

        $this->load_departments();
        $this->interaction_count = $this->get_interaction_count();

        if($this->username!="") {
            $this->userobj = new UserObj($this->username);
            if(!$this->userobj->loaded) {
                $this->userobj->permission = 0;
            }
        }
    }

    // Return's an array
    public function load_departments()
    {
        if(!isset($this->cid)) {
            return [];
        }
        $this->departments = [];

        return DB::table('contact_departments')
            ->where('cid',$this->cid)
            ->get();
    }

    // Method to run before saving
    public function pre_save()
    {
        // Remove sporadic spaces and commas from the tag list
        if(isset($this->tags) and !empty($this->tags)) {
            $this->tags = trim($this->tags);
            if(substr($this->tags,-1,1)==",") $this->tags = substr($this->tags,0,-1);
        }
        // If there are no tags, then tag it as "untagged"
        else
            $this->tags = "";

        // Format phone before saving
        if(isset($this->phone)) {
            $this->phone = $this->get_formatted_phone($this->phone);
        }
        if(isset($this->phone2)) {
            $this->phone2 = $this->get_formatted_phone($this->phone2);
        }

        # Format the names
        if(isset($this->firstname,$this->lastname) and $this->firstname != "" and $this->lastname != "") {
            if(isset($this->middlename) and $this->middlename != "") {
                $this->fullname = $this->firstname." ".$this->middlename." ".$this->lastname;
            } else {
                $this->fullname = $this->firstname." ".$this->lastname;
            }
        }
        else if(isset($this->fullname) and $this->fullname != "") {
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
        }
        else {
            return false;
        }

        # If Contact ID not set, see if user with same full name exists
        if(!isset($this->cid) and isset($this->fullname)) {
            $this->cid = DB::table($this->table)
                ->where('fullname',$this->fullname)
                ->pluck('cid');
        }
    }

    // Method to run after successfully saving
    public function post_save()
    {

    }

    public static function get_fullname($cid) {
        return DB::table('contacts')
            ->where('cid', $cid)
            ->pluck('fullname');
    }

    public function add_association($department, $position)
    {
        # See if this unique set of information exists already
        if(DB::table('contact_depts')
            ->where('cid',$this->cid)
            ->where('department',$department)
            ->where('position',$position)
            ->count()) {

            # Technically we didn't add it, but it already exists, so pretend everything went through as normal
            return true;
        }

        # Insert association into the table
        DB::table('contact_depts')
            ->insert(
                [
                    'cid'           => $this->cid,
                    'fullname'      => $this->fullname,
                    'department'    => $department,
                    'position'      => $position
                ]
            );
    }

    public function remove_association($department, $position="")
    {
        # Remove the association with the specific position within a department
        if($position != "") {
            DB::table('contact_depts')
                ->where('cid', $this->cid)
                ->where('department', $department)
                ->where('position', $position)
                ->delete();
        }
        # Remove the association with the department entirely
        else {
            DB::table('contact_depts')
                ->where('cid', $this->cid)
                ->where('department', $department)
                ->delete();
        }
    }

    public function has_association($department, $position="")
    {
        if($position != "") {
            return DB::table('contact_depts')
                ->where('cid', $this->cid)
                ->where('department', $department)
                ->where('position', $position)
                ->count() >= 1;
        }
        else {
            return DB::table('contact_depts')
                ->where('cid', $this->cid)
                ->where('department', $department)
                ->count() >= 1;
        }
        return false;
    }

    public function get_associations()
    {
        return DB::table('contact_depts')
            ->where('cid', $this->cid)
            ->get();
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

    public static function find_contact_by_username($username)
    {
        $cid = DB::table('contacts')
            ->where('username',$username)
            ->pluck('cid');

        return new ContactObj($cid);
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

    public function get_departments()
    {
        return DB::table('contact_depts')
            ->where('cid',$this->cid)
            ->list('department');
    }

    public function run_check()
    {
        if($this->firstname=="") {
            return !$this->set_error("First name must be set.");
        }
        if($this->lastname == "") {
            return !$this->set_error("Last name must be set.");
        }

        if(isset($this->firstname,$this->lastname) and !$this->is_valid_id())
        {
            $exists = DB::table($this->table)
                ->where("firstname", $this->firstname)
                ->where("lastname", $this->lastname)
                ->count();
            if($exists==1) {
                return !$this->set_error("This person's name already exists.");
            }
        }
        if(!isset($this->email) or empty($this->email)) {
            $this->email = "";
        }
        return true;
    }

    public function count_interactions()
    {
        return $this->get_interaction_count();
    }

    public function get_interaction_count()
    {
        return DB::table('interaction_attendees')
            ->where('cid', $this->cid)
            ->count();
    }

    public function get_interaction_count_between_dates($startdate,$enddate)
    {
        return DB::table('interaction_attendees AS ia')
            ->join('interactions as i','i.interactionid','=','ia.interactionid')
            ->where('ia.cid', $this->cid)
            ->where('i.meetingdate', '>', $startdate)
            ->where('i.meetingdate', '<', $enddate)
            ->count();
    }

    public function get_interactions($fromdate=null,$todate=null)
    {
        $list_of_ids = DB::table('interactions')
            ->where('meetingdate', '>', $fromdate)
            ->where('meetingdate', '<', $todate)
            ->orderBy('meetingdate','DESC')
            ->list('interactionid');

        foreach($list_of_ids as $id) {
            $interactions[] = new InteractionObj($id);
        }

        return $interactions;
    }

    public function get_renderable($data_to_render) {
        if(method_exists($this,"get_renderable_".$data_to_render)) {
            return $this->{"get_renderable_".$data_to_render}();
        }
    }

    public function get_renderable_positions() {
        $output = [];
        foreach($this->get_associations() as $association) {
            if(!in_array($association->position, $output)) {
                $output[] = $association->position;
            }
        }
        return implode(", ",$output);
    }

    public function get_renderable_departments() {
        $output = [];
        foreach($this->get_associations() as $association) {
            if(!in_array($association->department, $output)) {
                $output[] = $association->department;
            }
        }
        return implode(", ",$output);
    }
}

?>