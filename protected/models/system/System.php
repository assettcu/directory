<?php

class System
{
    public  $objects = array();
    
    private $error_flag     = FALSE;
    private $error_msg      = "";
    
    public function __construct($init=TRUE) {
        if($init===TRUE) {
            $this->init();
        }
    }
    
    public function init() {
        return true;
    }
    
    public function install()
    {
        # Does the application need installing? Check if database exists, and can connect
        try {
            # Required fields
            $required = array(
                "db-host",
                "db-name",
                "db-username",
                "db-password",
                "table-prefix"
            );
            
            # Did all the required fields get passed in?
            if(count(array_intersect($required, array_keys($_REQUEST))) != count($required)) {
                throw new Exception("Not all required fields were submitted.");
            }
            
            # Verify the required unempty fields
            foreach($required as $field) {
                # Skip the fields that can be empty
                if($field == "table-prefix" or $field == "db-password") {
                    continue;
                }
                # Check if empty, throw error if they are.
                if(empty($_REQUEST[$field])) {
                    throw new Exception("Field <i>".lookupfieldname($field)."</i> cannot be empty.");
                }
            }

            # Try connecting to the database with the passed in credentials
            try {
                # Setup connection details
                $dsn = 'mysql:host='.$_REQUEST["db-host"].';dbname='.$_REQUEST["db-name"];
                $username = $_REQUEST["db-username"];
                $password = $_REQUEST["db-password"];
                $prefix = $_REQUEST["table-prefix"];
                
                # Make the connection
                $conn = new CDbConnection($dsn, $username, $password);
                $conn->active = true;
                $conn->setActive(true);
            }
            catch(Exception $e) {
                throw new Exception("Could not connect to database. Make sure you have created the database first. Details: ".$e->getMessage());
            }

            # Setup the database params for saving in the extended configuration
            $db_params = array(
                'components'=>array(
                    'db'=>array(
                        'connectionString'  => $dsn,
                        'emulatePrepare'    => true,
                        'username'          => $username,
                        'password'          => $password,
                        'charset'           => 'utf8',
                        'tablePrefix'       => $prefix,
                    ),
                ),
                'params'=>array(
                    'LOCALAPP_SERVER'           => $_SERVER["HTTP_HOST"],
                ),
            );
            
            # Make sure to only overwrite if explicitly asked to
            $config_ext = Yii::app()->basePath."\\config\\main-ext.php";
            if(is_file($config_ext)) {
                throw new Exception("Database configuration already exists. Delete this configuration in order to install this application.");
            }
            
            # Open up the file and write the new configuration.
            $handle = fopen($config_ext,"w");
            fwrite($handle,"<?php return ");
            fwrite($handle,var_export($db_params,true));
            fwrite($handle,"; ?>");
            fclose($handle);
            
            # Make read-only
            chmod($config_ext, 0060);
        } 
        # Catch all the errors and output them as Flashes
        catch(Exception $e) {
            $this->set_error($e->getMessage());
            return false;
        }
        
        # If we made it to here, installation is a success!
        return true;
    }
    
    # Define a couple of local functions first
    # Function to change field name
    private function lookupfieldname($field) {
        switch($field) {
            case "db-host": return "Database Host";
            case "db-name": return "Database Name";
            case "db-username": return "Database Username";
            case "db-password": return "Database Password";
            case "table-prefix": return "Table Prefix";
            default: return $field;
        }
    }
         
    private function set_error($message) {
        $this->error_flag   = TRUE;
        $this->error_msg    = $message;
    }
    
    public function get_error() {
        return $this->error_msg;
    }
    
    public function has_error() {
        return ($this->error_flag === TRUE);
    }
    
}
