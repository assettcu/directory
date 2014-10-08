<?php
/**
 * User Object
 * 
 * User object extends factory object. This class uses proprietary password hashing (notes can be found below).
 * Although there is AD Authentication procedures, users not found in the Active Directory can be added to the database.
 * 
 * **** NOTE ****
 * The CU Property database User table does not have a password field and therefore 
 * would not utilize the password hashing functions. However, it is in the code in case the application 
 * specifications change and the database field is added. Add this field to the schema if you desire to 
 * add a local password field:
 * 
 * 		password		(varchar 255)				Encrypted password field (Can be null)
 * 
 * @author      Ryan Carney-Mogan
 * @category    Core_Classes
 * @version     1.0.2
 * @copyright   Copyright (c) 2013 University of Colorado Boulder (http://colorado.edu)
 * 
 * @database    cuproperty
 * @table       users
 * @schema      
 *      username      	(varchar 50)                Identikey username (PK, Not Null)
 *      email		    (varchar 255)               User email (Not Null)
 *      name			(varchar 255)              	User name (Not Null)
 *      permission	    (int 10)               		Level of the user permissions (Not Null)
 *      active		    (tinyint 1)                	Whether user is active or not (0 or 1 usually) (Not Null)
 *      attempts        (tinyint 1)   				Attempts to login (Not Null)
 *      last_login     	(datetime)                  Last Login date
 * 		preferences		(text)						Metadata field for application specific preferences (usually not used)
 * 
 */
 
  
/*
 * Password hashing with PBKDF2.
 * Author: havoc AT defuse.ca
 * www: https://defuse.ca/php-pbkdf2.htm
 */

// These constants may be changed without breaking existing hashes.
define("PBKDF2_HASH_ALGORITHM", "sha256");
define("PBKDF2_ITERATIONS", 1000);
define("PBKDF2_SALT_BYTES", 24);
define("PBKDF2_HASH_BYTES", 24);

define("HASH_SECTIONS", 4);
define("HASH_ALGORITHM_INDEX", 0);
define("HASH_ITERATION_INDEX", 1);
define("HASH_SALT_INDEX", 2);
define("HASH_PBKDF2_INDEX", 3);

class UserObj extends FactoryObj
{

    public $contact = null;

	public function __construct($userid=null)
	{
		parent::__construct("username","users",$userid);
	}

	public function login()
	{
		if(!$this->loaded) return false;
		$login = new LoginObj();
		$login->login($this->username);
	}

    public function get_contact()
    {
        if(!$this->loaded) {
            return false;
        }
        $this->contact = new ContactObj();
        $this->contact->username = $this->username;
        $this->contact->load();
        
        return $this->contact;
    }

	function create_hash($password)
	{
	    // format: algorithm:iterations:salt:hash
	    $salt = base64_encode(mcrypt_create_iv(PBKDF2_SALT_BYTES, MCRYPT_DEV_URANDOM));
	    return PBKDF2_HASH_ALGORITHM . ":" . PBKDF2_ITERATIONS . ":" .  $salt . ":" .
	        base64_encode($this->pbkdf2(
	            PBKDF2_HASH_ALGORITHM,
	            $password,
	            $salt,
	            PBKDF2_ITERATIONS,
	            PBKDF2_HASH_BYTES,
	            true
	        ));
	}
	
	function validate_password($password, $good_hash)
	{
	    $params = explode(":", $good_hash);
	    if(count($params) < HASH_SECTIONS)
	       return false;
	    $pbkdf2 = base64_decode($params[HASH_PBKDF2_INDEX]);
	    return $this->slow_equals(
	        $pbkdf2,
	        $this->pbkdf2(
	            $params[HASH_ALGORITHM_INDEX],
	            $password,
	            $params[HASH_SALT_INDEX],
	            (int)$params[HASH_ITERATION_INDEX],
	            strlen($pbkdf2),
	            true
	        )
	    );
	}
	
	// Compares two strings $a and $b in length-constant time.
	function slow_equals($a, $b)
	{
	    $diff = strlen($a) ^ strlen($b);
	    for($i = 0; $i < strlen($a) && $i < strlen($b); $i++)
	    {
	        $diff |= ord($a[$i]) ^ ord($b[$i]);
	    }
	    return $diff === 0;
	}
	
	/*
	 * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
	 * $algorithm - The hash algorithm to use. Recommended: SHA256
	 * $password - The password.
	 * $salt - A salt that is unique to the password.
	 * $count - Iteration count. Higher is better, but slower. Recommended: At least 1000.
	 * $key_length - The length of the derived key in bytes.
	 * $raw_output - If true, the key is returned in raw binary format. Hex encoded otherwise.
	 * Returns: A $key_length-byte key derived from the password and salt.
	 *
	 * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
	 *
	 * This implementation of PBKDF2 was originally created by https://defuse.ca
	 * With improvements by http://www.variations-of-shadow.com
	 */
	function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
	{
	    $algorithm = strtolower($algorithm);
	    if(!in_array($algorithm, hash_algos(), true))
	        die('PBKDF2 ERROR: Invalid hash algorithm.');
	    if($count <= 0 || $key_length <= 0)
	        die('PBKDF2 ERROR: Invalid parameters.');
	
	    $hash_length = strlen(hash($algorithm, "", true));
	    $block_count = ceil($key_length / $hash_length);
	
	    $output = "";
	    for($i = 1; $i <= $block_count; $i++) {
	        // $i encoded as 4 bytes, big endian.
	        $last = $salt . pack("N", $i);
	        // first iteration
	        $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
	        // perform the other $count - 1 iterations
	        for ($j = 1; $j < $count; $j++) {
	            $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
	        }
	        $output .= $xorsum;
	    }
	
	    if($raw_output)
	        return substr($output, 0, $key_length);
	    else
	        return bin2hex(substr($output, 0, $key_length));
	}
}

?>