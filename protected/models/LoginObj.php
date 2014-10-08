<?php


class LoginObj extends FactoryObj
{

	public function __construct($loginid=null)
	{
		parent::__construct("loginid","login",$loginid);
	}

	public function create_session_id()
	{
		$this->sessionid = StandardObj::generate_random_string(32);
	}

	public function login($username="")
	{
		// If the username is not set, then use the parameter username
		if(!isset($this->username) or $this->username="") $this->username = $username;
		// If the username is still not set, then use the Yii username
		if($this->username=="") $this->username = Yii::app()->user->name;
		// If the Yii username was set and it was empty, we have no one to login!
		if($this->username=="") return false;

		// Save the date that the user logged in
		$this->dateloggedin = date("Y-m-d H:i:s");
		return $this->save();
	}

	public function find_login_session()
	{
	}

	public function logout($username)
	{
		// If the username is not set, then use the parameter username
		if(!isset($this->username) or $this->username="") $this->username = $username;
		// If the username is still not set, then use the Yii username
		if($this->username=="") $this->username = Yii::app()->user->name;
		// If the Yii username was set and it was empty, we have no one to login!
		if($this->username=="") return false;

		// Load the most recent login
		$this->load_recent_login();
		if(!$this->is_valid_id()) return false;

		// Save the date that the user logged in
		$this->dateloggedout = date("Y-m-d H:i:s");
		$this->save();
	}

	public function load_recent_login()
	{
		if(!isset($this->username)) return false;
		$conn = Yii::app()->db;
		$query = "
			SELECT		loginid
			FROM		{{login}}
			WHERE		username = :username
			ORDER BY	dateloggedin DESC
			LIMIT		1;
		";
		$command = $conn->createCommand($query);
		$command->bindParam(":username",$this->username);
		$this->loginid = $command->queryScalar();
		$this->load();
	}

}

?>