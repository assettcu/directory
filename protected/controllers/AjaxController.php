<?php

class AjaxController extends Controller 
{
    
    public function actionFBLookup() 
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
        
        if($info["count"] == 0) {
            return print json_encode(array());
        }
        
        return print json_encode(array($request["attribute"] => @$info[0][$request["attribute"]][0]));
    }
    
}
