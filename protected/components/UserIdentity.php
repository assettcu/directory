<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */

define ("ERROR_INVALID_CREDENTIALS",    1);
define ("ERROR_MAX_ATTEMPTS",           2);
define ("ERROR_AUTH_GROUP_INVALID",     3);

class UserIdentity extends CUserIdentity
{
    public function authenticate()
    {
        $this->errorCode=self::ERROR_NONE;

        $authenticated = false;
        $username = $this->username;
        $password = $this->password;
        $user = null;
        
        try
        {
            Yii::app()->db;
            $dbload = true;
        } catch (Exception $e) {
            # If Connection doesn't exist
            $dbload = false;
        }
        
        # Check if user exists or is locked out
        if($dbload) {
            $user = new UserObj($username);
            if($user->loaded and isset($user->active,$user->attempts) and ($user->active==0 or $user->attempts>5))
            {
                $this->errorCode=ERROR_MAX_ATTEMPTS;
                return !$this->errorCode;
            }
        }
        
        # The new Authentication System
        $adauth = new ADAuth("adcontroller");
        
        # Authenticate!
        if($adauth->authenticate($username, $password)){
            
            # !Important! User groups and their permission levels
            $valid_groups = array(
                "ASSETT-Programming"=>10,
                "ASSETT-Admins"=>10,
                "ASSETT-TTAs"=>3,
                "ASSETT-Core"=>3,
                "ASSETT-Staff"=>3,
                "ASSETT-ATCs"=>3,
                "ASSETT-Design"=>3,
            );
            
            # Empty for now
            $info = $adauth->lookup_user();
                
            # Iterate through groups and assign user to appropriate groups
            foreach($valid_groups as $group=>$permlevel) {
                if($adauth->is_member($group)) {
                    // Update only if membership changed or new user
                    if($dbload === true and !is_null($user) and (!$user->loaded or ($user->loaded and $user->member != $group))) {
                        $user->permission = $permlevel;
                        $user->member = $group;
                    } else if($dbload===false and (!isset($permission) or $permlevel > $permission)) {
                        $permission = $permlevel;
                        $belongsto  = $group;
                    }
                    break;
                }
            }
            
            if($dbload===false) {
                if(!isset($permission)) {
                    $this->errorCode = ERROR_AUTH_GROUP_INVALID;
                    return !$this->errorCode;
                }
                Yii::app()->user->setState("group",$belongsto);
                Yii::app()->user->setState("permission",$permission);
            } else {
            
                if(is_null($user->permission) and !$user->loaded) {
                    $user->permission = 1;
                }
                
                $user->email = $info[0]["mail"][0];
                $user->name = $info[0]["displayname"][0];
                
                if($user->permission==0) {
                    $this->errorCode = ERROR_AUTH_GROUP_INVALID;
                }
                    
                if(!$this->errorCode) {
                    $user->last_login = date("Y-m-d H:i:s");
                    $user->attempts = 0;
                    $user->save();
                    $user->load();
                }
                
                # Switch to the directory and lookup user's CU affiliation (student/staff/faculty)
                $adauth->change_controller("directory");
                $info = $adauth->lookup_user();
                $user->roles = $this->parse_roles($info[0]["edupersonaffiliation"]);
                
                # Save and reload
                $user->save();
                $user->load();
                
                $contact = new ContactObj();
                $contact->username = $user->username;
                $contact->load();
                
                if($contact->loaded)
                {
                    Yii::app()->user->setState("cid",$contact->cid);
                    Yii::app()->user->setState("userobj",$user);
                }
            }

        } else {
            if($dbload === true and $user->loaded)
            {
                $user->attempts++;
                $user->save();
            }
            $this->errorCode=ERROR_INVALID_CREDENTIALS;
        }
        
        return !$this->errorCode;
    }

    /*
     * Takes "edupersonaffiliation" field values from the Directory.
     * Parses whether the person is student/staff/faculty.
     * A person may be none or many.
     * 
     */
    private function parse_roles($roles) {
        if(!is_array($roles) or empty($roles)) {
            return "";
        }
        $return = array();
        foreach($roles as $role) {
            $role = trim(strtolower($role));
            switch($role) {
                case "student": 
                    if(!in_array("student",$return)) $return[] = "student"; 
                break;
                case "employee":
                case "staff":
                case "officer/professional":
                    if(!in_array("staff",$return)) $return[] = "staff";
                break;
                case "faculty":
                    if(!in_array("faculty",$return)) $return[] = "faculty";
                break;
            }
        }
        
        return implode(",",$return);
    }
}