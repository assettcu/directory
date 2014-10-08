<?php

ini_set("display_errors",1);
ini_set("error_reporting",E_ALL);
set_time_limit(0);

function get_interactions() {
    $tlc1 = new ContactObj();
    $tlc1->fullname = "Amanda McAndrew";
    $tlc1->load();
    
    $tlc2 = new ContactObj();
    $tlc2->fullname = "Nigora Azimova";
    $tlc2->load();
    
    $tlc3 = new ContactObj();
    $tlc3->fullname = "Jacie Moriyama";
    $tlc3->load();
    
    $tlcs[] = $tlc1;
    $tlcs[] = $tlc2;
    $tlcs[] = $tlc3;
    
    $return = array();
    foreach($tlcs as $tlc) {
        $interactions = $tlc->get_interactions();
        foreach($interactions as $interaction) {
            if(strtotime($interaction->meetingdate) < strtotime("January 1st, 2014")) {
                continue;
            }
            $attendees = array();
            $departments = array();
            foreach($interaction->attendees as $contact) {
                if($contact == $tlc->cid) {
                    continue;
                }
                $result = Yii::app()->db->createCommand()
                    ->select("C.fullname, D.deptname")
                    ->from("contacts as C, departments AS D")
                    ->where("D.deptid = (SELECT deptid FROM contact_departments WHERE cid = :cid LIMIT 1) AND C.cid = :cid", array(":cid"=>$contact))
                    ->queryRow();
                if(!$result or empty($result)) {
                    continue;
                }
                $attendees[] = $result["fullname"];
                $departments[] = $result["deptname"];
            }
            
            $return[] = array(
                $interaction->interactionid,
                $tlc->fullname,
                implode(", ",array_unique($attendees)),
                implode(", ",array_unique($departments)),
                date_format(new DateTime($interaction->meetingdate), "M d, Y")
            );
        }
    }
    
    return $return;
}

function get_dept_interactions() {
    $tlc1 = new ContactObj();
    $tlc1->fullname = "Amanda McAndrew";
    $tlc1->load();
    
    $tlc2 = new ContactObj();
    $tlc2->fullname = "Nigora Azimova";
    $tlc2->load();
    
    $tlc3 = new ContactObj();
    $tlc3->fullname = "Jacie Moriyama";
    $tlc3->load();
    
    $tlcs[] = $tlc1;
    $tlcs[] = $tlc2;
    $tlcs[] = $tlc3;
    
    $interactions = $tlc1->get_interactions();
    $interactions = array_merge($interactions, $tlc2->get_interactions());
    $interactions = array_merge($interactions, $tlc3->get_interactions());
    
    $depts = array();
    $conn = Yii::app()->db;
    
    foreach($interactions as $interaction) {
        if(strtotime($interaction->meetingdate) < strtotime("January 1st, 2014")) {
            continue;
        }
        $original_attendees = $interaction->attendees;
        foreach($tlcs as $tlc) {
            if($key = array_search($tlc->cid,$interaction->attendees) !== false) {
                unset($interaction->attendees[$key]);
            }
        }
        foreach($interaction->attendees as $attendee) {
            $query = "
                SELECT      B.deptname, B.deptid
                FROM        contact_departments as A,
                            departments as B
                WHERE       A.cid = :cid
                AND         B.deptid = A.deptid;
            ";
            $command = $conn->createCommand($query);
            $command->bindParam(":cid",$attendee);
            $result = $command->queryAll();
            
            foreach($result as $row) {
                if(!array_key_exists($row["deptid"], $depts)) {
                    $depts[$row["deptid"]] = array(
                        "deptid"            => $row["deptid"],
                        "deptname"          => $row["deptname"],
                        "interactions"      => 1,
                        $tlc1->fullname     => 0,
                        $tlc2->fullname     => 0,
                        $tlc3->fullname     => 0,
                    );
                    $query = "
                        SELECT      COUNT(*) as count
                        FROM        contact_departments
                        WHERE       deptid = :deptid;
                    ";
                    $command = $conn->createCommand($query);
                    $command->bindParam(":deptid",$row["deptid"]);
                    $depts[$row["deptid"]]["num_contacts"] = $command->queryScalar();
                }
                else {
                    $depts[$row["deptid"]]["interactions"]++;
                }
                
                # Add interaction count for each TLC
                foreach($tlcs as $tlc) {
                    if(in_array($tlc->cid,$original_attendees)) {
                        $depts[$row["deptid"]][$tlc->fullname]++;
                    }
                }
            }
        }
    }
    
    return $depts;
}

function get_all_tags()
{
    $tlc1 = new ContactObj();
    $tlc1->fullname = "Amanda McAndrew";
    $tlc1->load();
    
    $tlc2 = new ContactObj();
    $tlc2->fullname = "Nigora Azimova";
    $tlc2->load();
    
    $tlc3 = new ContactObj();
    $tlc3->fullname = "Jacie Moriyama";
    $tlc3->load();
    
    $tlc3 = new ContactObj();
    $tlc3->fullname = "Jacie Moriyama";
    $tlc3->load();
    
    $tlcs[] = $tlc1;
    $tlcs[] = $tlc2;
    $tlcs[] = $tlc3;
    
    $interactions = $tlc1->get_interactions();
    $interactions = array_merge($interactions, $tlc2->get_interactions());
    $interactions = array_merge($interactions, $tlc3->get_interactions());
    
    $depts = array();
    $conn = Yii::app()->db;
    
    foreach($interactions as $index=>$interaction) {
        if(strtotime($interaction->meetingdate) < strtotime("January 1st, 2014")) {
            unset($interactions[$index]);
        }
        foreach(explode(", ",$interaction->tags) as $tag) {
            $tags[] = $tag;
        }
    }
    
    $tags = array_unique($tags);

    foreach($tags as $tag) {
        $query = "
            SELECT      attendees
            FROM        interactions
            WHERE       tags LIKE :tag
        ";
        $command = $conn->createCommand($query);
        $command->bindValue(":tag","%".$tag."%");
        $result = $command->queryAll();
        
        $names = array();
        $depts = array();
        $rowinfo = array();
        foreach($result as $row) {
            $attendees = json_decode($row["attendees"]);
            
            # Remove the TLCs
            foreach($tlcs as $tlc) {
                if($key = array_search($tlc->cid,$attendees) !== false) {
                    unset($attendees[$key]);
                }
            }
            foreach($attendees as $attendee) {
                $query = "
                    SELECT      B.deptname, A.fullname
                    FROM        contacts as A, departments as B
                    WHERE       A.cid = :cid
                    AND         B.deptid = (SELECT deptid FROM contact_departments WHERE cid = :cid LIMIT 1);
                ";
                $command = $conn->createCommand($query);
                $command->bindParam(":cid",$attendee);
                $result = $command->queryRow();
                if(!in_array($result["fullname"],$names)){
                    if($result["fullname"] != "") {
                        $names[] = $result["fullname"];
                        $rowinfo[] = $result["fullname"];
                    }
                    if($result["deptname"] != "") {
                        $rowinfo[] = $result["deptname"];
                        $depts[] = $result["deptname"];
                    }
                }
            }
        }
        $output[$tag] = array(
            "tag"           => $tag,
            "numcontacts"   => count($names),
            "numdepts"      => count($depts),
        );
        $output[$tag] = array_merge($output[$tag],$rowinfo);
    }
    
    return $output;
}

function get_contacts() {
    $directory = new DirectoryObj();
    $contacts = $directory->load_all_contacts();
    $output = array();
    foreach($contacts as $contact) {
        $departments = array();
        foreach($contact->departments as $dept) {
            $departments[] = $dept->deptname;
        }
        $output[] = array(
            $contact->cid,
            $contact->fullname,
            $contact->firstname,
            $contact->middlename,
            $contact->lastname,
            implode("; ",$departments),
            $contact->email,
            $contact->phone,
            $contact->phone2,
            $contact->tags,
        );
    }
    return $output;
}

# $output = get_interactions();
# $output = get_dept_interactions();
# $output = get_all_tags();
# $output = get_contacts();


// Settings
$datetime = time();
$targetDir = ROOT.'/tmp/'.Yii::app()->user->name."/";
if(!is_dir($targetDir)) {
    mkdir($targetDir);
}
$targetFile = $targetDir.'export-'.$datetime.'.csv';

$fp = fopen($targetFile,'w');
foreach($output as $fields) {
    fputcsv($fp,$fields);
}
fclose($fp);


header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Content-Type: application/force-download');
header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename='.basename($targetFile));
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($targetFile));
ob_clean();
ob_flush();
flush();
readfile($targetFile);
unlink($targetFile);
exit;
?>