<?php

class Functions {
    
    static public function get_colleges()
    {
        $results = Yii::app()->db->createCommand()
            ->select("collegeid")
            ->from("colleges")
            ->order("name ASC")
            ->queryAll();
        
        if(!$results or empty($results)) {
            return array();
        }
        foreach($results as $row) {
            $colleges[] = new CollegeObj($row["collegeid"]);
        }
        
        return $colleges;
    }
    
}