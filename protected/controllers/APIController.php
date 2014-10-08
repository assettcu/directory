<?php

header('Access-Control-Allow-Origin: *');

class APIController extends Controller
{
    public function actionDeptNames()
    {
        $return = array();
        $conn = Yii::app()->db;
        $query = "
            SELECT      deptname
            FROM        {{departments}}
            WHERE       deptname LIKE :deptname
            ORDER BY    deptname ASC;
        ";
        $command = $conn->createCommand($query);
        $command->bindValue(":deptname","%".$_REQUEST["term"]."%");
        $result = $command->queryAll();
        foreach($result as $row) {
            $return[] = $row["deptname"];
        }
        
        return print json_encode($return);
    }
    
    public function actionDeptName()
    {
        $conn = Yii::app()->db;
        $query = "
            SELECT      deptname
            FROM        {{departments}}
            WHERE       abbr = :abbr
            ORDER BY    deptname ASC
            LIMIT       1;
        ";
        $command = $conn->createCommand($query);
        $command->bindValue(":abbr",$_REQUEST["code"]);
        $return = $command->queryScalar();
        
        return print $return;
    }
    
    public function actionDeptCodes()
    {
        $return = array();
        $conn = Yii::app()->db;
        $query = "
            SELECT      abbr
            FROM        {{departments}}
            WHERE       deptname LIKE :deptname
            ORDER BY    deptname ASC;
        ";
        $command = $conn->createCommand($query);
        $command->bindValue(":deptname","%".$_REQUEST["term"]."%");
        $return = $command->queryAll();
        foreach($result as $row) {
            $return[] = $row["abbr"];
        }
        
        return print json_encode($return);
    }
	
	public function actionGetDeptContact()
	{
		$conn = Yii::app()->db;
		$site = urldecode($_REQUEST["site"]);
		$query = "
			SELECT 		contacts.fullname, contacts.email, contacts.phone, departments.deptname 
			FROM 		contacts, contact_departments, departments 
			WHERE 		contact_departments.deptid = (SELECT deptid FROM departments WHERE website = :website) 
			AND 		contact_departments.posname LIKE \"%program%assistant%\" 
			AND 		contacts.cid = contact_departments.cid 
			AND 		contact_departments.primary = 1 
			AND 		contact_departments.current = 1
			AND			departments.website = :website;
		";
        $command = $conn->createCommand($query);
        $command->bindValue(":website",$site);
        $return = $command->queryRow();
		
		return print json_encode($return);
	}
	
	public function actionLDAP()
	{
        $rest = new RestServer();
        $request = RestUtils::processRequest();
        $required = array("q","attribute");
        $keys = array_keys($request);
        if(count(array_intersect($required, $keys)) != count($required)) {
            return RestUtils::sendResponse(308);
        }
        
        # The Directory we're connecting with is the Active Directory for the Campus 
        # (not to be confused with this application's name)
        $ldap = new ADAuth("directory");
        $ldap->bind_anon();
        $info = $ldap->lookup_user($request["q"]);
        
        if($info["count"] == 0) {
            return print json_encode(array());
        }
        
        return print json_encode(array($request["attribute"] => @$info[0][$request["attribute"]][0]));
	}
    
    public function actionLDAPAll() 
    {
        $rest = new RestServer();
        $request = RestUtils::processRequest();
        $required = array("q");
        $keys = array_keys($request);
        if(count(array_intersect($required, $keys)) != count($required)) {
            return RestUtils::sendResponse(308);
        }
        
        # The Directory we're connecting with is the Active Directory for the Campus 
        # (not to be confused with this application's name)
        $ldap = new ADAuth("directory");
        $ldap->bind_anon();
        $info = $ldap->lookup_user($request["q"]);
        
        return print json_encode($info);
    }
	
	public function actionAllDepartments()
	{
        $rest = new RestServer();
        $request = RestUtils::processRequest();
		$query = "
			SELECT      id as deptcode,
						label as deptname
			FROM        {{ascore_departments}}
			ORDER BY    id ASC;
		";
        $return = array();
        $conn = Yii::app()->db;
        $result = $conn->createCommand($query)->queryAll();
        foreach($result as $row) {
            $return[] = array("deptcode"=>$row["deptcode"],"deptname"=>$row["deptname"]);
        }
        
        return print json_encode($return);
	}
	
	public function actionDepartmentName()
	{
        $rest = new RestServer();
        $request = RestUtils::processRequest();
		if($this->has_required($request,array('deptcode'))) {
			extract($request);
		}
		else {
			return RestUtils::sendResponse(308);
		}
		
        $return = array();
		$deptname = Yii::app()->db->createCommand()
			->select('label as deptname')
			->from('ascore_departments')
			->where('id = :id',array(':id'=>$deptcode))
			->order('id ASC')
			->queryScalar();
        
        return print json_encode($deptname);
	}
	
	private function has_required($request, $required)
	{
        $keys = array_keys($request);
        return (count(array_intersect($required, $keys)) == count($required));
	}
}
