<?php

class DirectoryObj
{
  
  public function __construct()
  {
  }
  
  public function load_all_departments()
  {
    $conn = Yii::app()->db;
    $query = "
      SELECT    deptid
      FROM      {{departments}}
      WHERE     1=1
      ORDER BY  deptname ASC;
    ";
    $result = $conn->createCommand($query)->queryAll();
    
    if(!$result or empty($result)) return array();
    
    foreach($result as $row)
      $departments[$row["deptid"]] = new DepartmentObj($row["deptid"]);
    
    return $departments;
  }
  
  public function load_all_contacts()
  {
    $conn = Yii::app()->db;
    $query = "
      SELECT    cid
      FROM      {{contacts}}
      WHERE     1=1
      ORDER BY  cid ASC;
    ";
    $result = $conn->createCommand($query)->queryAll();
    
    if(!$result or empty($result)) return array();
    
    foreach($result as $row)
      $contacts[$row["cid"]] = new ContactObj($row["cid"]);
    
    return $contacts;
  }
  
  public function filter_contacts($filter,$start=0,$length=10,$orderby="",$tag="",$letter="",$dept="")
  {
    $search = explode(" ",$filter);
    $where = "";
    $count = 0;
    $innerjoin = "";
    if($orderby=="") $orderby = "C.lastname ASC, C.firstname ASC";
    if(strlen($filter)>0)
    {
      foreach($search as $f)
      {
        $count++;
        $where .= "(C.fullname LIKE :filter".$count."
        OR        C.tags LIKE :filter".$count.") AND ";
      }
    }
    if($tag!="")
      $where .= " (C.tags LIKE :tags) AND ";
    if($letter != "")
      $where .= " (C.lastname LIKE :letter) AND ";
    if($dept != "")
      $innerjoin .= "
      INNER JOIN  {{contact_departments}} AS CD
      ON          CD.cid = C.cid AND CD.deptid = :department";
    
    $where = substr($where,0,-5);
      
    if($where == "") $where = "1=1";
    
    $conn = Yii::app()->db;
    $query = "
      SELECT      C.cid
      FROM        {{contacts}} AS C
      $innerjoin
      WHERE       $where
      ORDER BY    $orderby
      LIMIT       $start, $length;
    ";
    $command = $conn->createCommand($query);
    if(strlen($filter)>0)
      for($a=1;$a<=count($search);$a++)
        $command->bindValue(":filter".$a,"%".$search[$a-1]."%");
    
    if($tag!="") $command->bindValue(":tags","%".$tag."%");
    if($letter!="") $command->bindValue(":letter",$letter."%");
    if($dept!="") $command->bindParam(":department",$dept);
    
    $result = $command->queryAll();
    
	$query = "
		SELECT		cid
		FROM		{{contact_departments}}
		WHERE		posname LIKE :filter
	";
	$command = $conn->createCommand($query);
	$command->bindValue(":filter","%".$filter."%");
	$result2 = $command->queryAll();
	
	
	$result2_ = array();
	foreach($result2 as $row) {
		$result2_[] = $row["cid"];
	}
	$result2 = $result2_;
	
	$result_ = array();
	foreach($result as $row) {
		$result_[] = $row["cid"];
	}
	$result = $result_;
	if(count($result2) > 0 and count($result)==0) {
		$result = $result2;
	} else {
		$result = array_merge($result,$result2);
	}
	
    if(!$result or empty($result)) return array();
	
    foreach($result as $cid) {
    	$contacts[$cid] = new ContactObj($cid);
    }
    
	usort($contacts,array("DirectoryObj","sort_contacts_by_name"));
	
    return $contacts;
  }

  public function sort_contacts_by_name($a,$b){
  	if(!isset($a->lastname,$b->lastname) or $a->lastname == $b->lastname) {
  		return 0;
  	}
	return ($a->lastname > $b->lastname) ? 1 : -1;
  }
  
  public function load_contacts($start=0,$length=10,$orderby="")
  {
    if($orderby=="") $orderby = "C.lastname ASC, C.firstname ASC";
    
    $conn = Yii::app()->db;
    $query = "
      SELECT    C.cid
      FROM      {{contacts}} AS C
      WHERE     1=1
      ORDER BY  $orderby
      LIMIT     $start, $length;
    ";
    $result = $conn->createCommand($query)->queryAll();
    
    if(!$result or empty($result)) return array();
    
    foreach($result as $row)
      $contacts[$row["cid"]] = new ContactObj($row["cid"]);
    
    return $contacts;
    
  }
  
  public function count_contacts($filter="",$tag="",$letter="",$dept="")
  {
    $conn = Yii::app()->db;
    if($filter!="" or $tag!="" or $letter!="" or $dept!="")
    {
      $search = explode(" ",$filter);
      $where = "";
      $count = 0;
      $innerjoin = "";
      if(strlen($filter)!=0)
      {
        foreach($search as $f)
        {
          $count++;
          $where .= "(C.firstname LIKE :filter".$count."
          OR        C.lastname LIKE :filter".$count."
          OR        C.email LIKE :filter".$count."
          OR        C.username LIKE :filter".$count."
          OR        C.tags LIKE :filter".$count.") AND ";
        }
      }
      if($tag!="")
        $where .= " (C.tags LIKE :tags) AND ";
      if($letter != "")
        $where .= " (C.lastname LIKE :letter) AND ";
      if($dept != "")
        $innerjoin .= " AND CD.deptid = :department ";
  
      $where = substr($where,0,-5);
      if($where == "") $where = "1=1";
        
      $query = "
        SELECT      COUNT(C.cid)
        FROM        {{contacts}} AS C
        INNER JOIN  {{contact_departments}} AS CD
        ON          C.cid = CD.cid $innerjoin
        WHERE       $where;
      ";
      $command = $conn->createCommand($query);
      if(strlen($filter)!=0)
        for($a=1;$a<=count($search);$a++)
          $command->bindValue(":filter".$a,"%".$search[$a-1]."%");
        
      if($tag!="") $command->bindValue(":tags","%".$tag."%");
      if($letter!="") $command->bindValue(":letter",$letter."%");
      if($dept!="") $command->bindParam(":department",$dept);
    }
    else
    {
      $query = "
        SELECT    COUNT(*)
        FROM      {{contacts}}
        WHERE     1=1;
      ";
      $command = $conn->createCommand($query);
    }
    return $command->queryScalar();
  }
  
  public function load_contacts_by_name($filter)
  {
    $orderby = "lastname ASC, firstname ASC";
    
    $search = explode(" ",$filter);
    $where = "";
    $count = 0;
    if(strlen($filter)!=0)
    {
      foreach($search as $f)
      {
        $count++;
        $where .= "(firstname LIKE :filter".$count."
        OR        lastname LIKE :filter".$count.") AND ";
      }
    }
    $where = substr($where,0,-5);
    if($where == "") $where = "1=0";
    
    $conn = Yii::app()->db;
    $query = "
      SELECT    cid
      FROM      {{contacts}}
      WHERE     $where
      ORDER BY  $orderby
      LIMIT     0, 10;
    ";
    $command = $conn->createCommand($query);
    if(strlen($filter)!=0)
      for($a=1;$a<=count($search);$a++)
        $command->bindValue(":filter".$a,"%".$search[$a-1]."%");
    $result = $command->queryAll();
    if(!$result or empty($result)) return array();
    
    foreach($result as $row)
      $contacts[$row["cid"]] = new ContactObj($row["cid"]);
    
    return $contacts;
    
  }
  
  public function load_departments_by_name($filter)
  {
    $orderby = "deptname ASC";
    
    $search = explode(" ",$filter);
    $where = "";
    $count = 0;
    if(strlen($filter)!=0)
    {
      foreach($search as $f)
      {
        $count++;
        $where .= "(deptname LIKE :filter".$count.") AND ";
      }
    }
    $where = substr($where,0,-5);
    if($where == "") $where = "1=0";
    
    $conn = Yii::app()->db;
    $query = "
      SELECT    deptid
      FROM      {{departments}}
      WHERE     $where
      ORDER BY  $orderby
      LIMIT     0, 10;
    ";
    $command = $conn->createCommand($query);
    if(strlen($filter)!=0)
      for($a=1;$a<=count($search);$a++)
        $command->bindValue(":filter".$a,"%".$search[$a-1]."%");
    $result = $command->queryAll();
    if(!$result or empty($result)) return array();
    
    foreach($result as $row)
      $depts[$row["deptid"]] = new DepartmentObj($row["deptid"]);
    
    return $depts;
    
  }
  
  public function load_all_tags()
  {
    $conn = Yii::app()->db;
    $query = "
      SELECT    tags
      FROM      {{contacts}}
      WHERE     1=1;
    ";
    $result = $conn->createCommand($query)->queryAll();
    
    if(!$result or empty($result)) return array();
    
    $atags = array();
    foreach($result as $row)
    {
      if(trim($row["tags"])=="") continue;
      $tags = explode(",",$row["tags"]);
      $atags = array_merge($tags,$atags);
    }
    $atags = array_map("trim",$atags);
    $atags = array_unique($atags);
    sort($atags);
    
    return $atags;
  }
  
  public function load_all_depts()
  {
    $conn = Yii::app()->db;
    $query = "
      SELECT    deptid
      FROM      {{departments}}
      WHERE     1=1
      ORDER BY  deptname ASC;
    ";
    $result = $conn->createCommand($query)->queryAll();
    
    if(!$result or empty($result)) return array();
    
    $depts = array();
    foreach($result as $row)
      $depts[$row["deptid"]] = new DepartmentObj($row["deptid"]);
    
    return $depts;
  }
  
  public function load_all_interactions()
  {
    $conn = Yii::app()->db;
    $query = "
      SELECT    interactionid
      FROM      {{interactions}}
      WHERE     1=1
      ORDER BY  interactionid ASC;
    ";
    $result = $conn->createCommand($query)->queryAll();
    
    if(!$result or empty($result)) return array();
    
    $iobjs = array();
    foreach($result as $row)
      $iobjs[$row["interactionid"]] = new InteractionObj($row["interactionid"]);
    
    return $iobjs;
  }
  
}