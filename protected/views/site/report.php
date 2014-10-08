<?php
if(!isset($_REQUEST["rptype"])) {
	Yii::app()->user->setFlash("error","Must provide a report type.");
	$this->redirect(Yii::app()->createUrl("index"));
	exit;
}

function sort_by_count($a,$b) 
{
	if(!isset($a["count"],$b["count"])) return 0;
	if($a["count"]==$b["count"]) {
		if($a["obj"] instanceof DepartmentObj and $b["obj"] instanceof DepartmentObj) {
			$dept1 = $a["obj"];
			$dept2 = $b["obj"];
		} else {
			$dept1 = array_slice($a["obj"]->departments,0,1);
			$dept2 = array_slice($b["obj"]->departments,0,1);
			if(!isset($dept1[0])) return 1;
			if(!isset($dept2[0])) return -1;
			$dept1 = $dept1[0];
			$dept2 = $dept2[0];	
		}
		if(!is_object($dept1)) return 1;
		if(!is_object($dept2)) return -1;
		return (strcmp($dept1->deptname,$dept2->deptname));
	}
	return ($a["count"]<$b["count"]) ? 1 : -1;	
}

if($_REQUEST["rptype"]=="overall") {
	$maincontact = new ContactObj($_REQUEST["cid"]);
	if(!$maincontact->loaded) {
		Yii::app()->user->setFlash("error","Could not load reports for user: ".Yii::app()->user->name);
		$this->redirect(Yii::app()->createUrl("index"));
		exit;
	}
	$numinteractions = $maincontact->count_interactions();
	
	$interactions = $maincontact->get_interactions();
	
	$total_attendees = array();
	$total_departments = array();
	foreach($interactions as $int) {
		foreach($int->attendees as $attendee) {
			$contact = new ContactObj($attendee);
			if(!array_key_exists($attendee,$total_attendees)) {
				$contact->load_departments();
				$total_attendees[$attendee] = array("obj"=>$contact,"count"=>1);
			} else {
				$total_attendees[$attendee]["count"]++;
			}
			foreach($contact->departments as $dept) {
				if(!$dept->loaded) continue;
				if(!array_key_exists($dept->deptid,$total_departments)) {
					$total_departments[$dept->deptid] = array("obj"=>$dept,"count"=>1);
				} else {
					$total_departments[$dept->deptid]["count"]++;
				}
			}
		}
	}
	usort($total_attendees,"sort_by_count");
	usort($total_departments,"sort_by_count");
?>
<style>
#interactions-contacts-container {
    width:49%;
    float:left;
}
table.fancy-table {
    width: 100%;
    border-spacing:2px;
}
table.fancy-table thead tr th {
    padding:4px;
    color:#fff;
    background-color:#016cc2;
    font-size:12px;
    font-weight:bold;
}
table.fancy-table tbody tr:hover td {
    background-color:#f7e6dc;
    cursor:default;
}
table.fancy-table tbody tr td {
    padding:3px;
    border:1px solid #ccc;
}
</style>
<h1>Interactions for <?php echo $maincontact->fullname; ?></h1>
<div id="interactions-contacts-container">
	<h3>Interactions per Contact</h3>
	<table class="fancy-table">
		<thead>
			<tr>
				<th width="50px" class="calign">Count</th>
				<th width="150px">Contact</th>
				<th>Department</th>
			</tr>
		</thead>
		<tbody>
			<?php 
			$count = 0;
			foreach($total_attendees as $attendee): 
				$contact = $attendee["obj"];
				$count++;
			?>
			<tr class="<?=($count%2==0)?'odd':'even';?>">
				<td class="calign"><?=$attendee["count"];?></td>
				<td><a href="<?=Yii::app()->createUrl('contact');?>?cid=<?=$contact->cid;?>"><?=$contact->get_name();?></a></td>
				<td><?php 
				    $depts = ""; 
				    foreach($contact->departments as $dept): 
                        if(isset($dept->shortname) and !empty($dept->shortname)) {
                            $depts .= $dept->shortname.",";
                        }
                        else {
                            $depts .= $dept->deptname.",";
                        }
				    endforeach;
				    print substr($depts,0,-1);
				    ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<div style="width:49%;float:right;">
	<h3>Interactions per Department</h3>
	<table class="fancy-table">
		<thead>
			<tr>
				<th width="50px" class="calign">Count</th>
				<th>Department</th>
			</tr>
		</thead>
		<tbody>
			<?php 
			$count = 0;
			foreach($total_departments as $department):
				$dept = $department["obj"];
				$count++;
			?>
			<tr class="<?=($count%2==0)?'odd':'even';?>">
				<td class="calign"><?=$department["count"];?></td>
				<td><?=$dept->deptname;?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<br class="clear" />
<h3>Interactions</h3>
<div>
	<table class="fancy-table">
		<thead>
			<tr>
				<th width="50px" class="calign">ID</th>
				<th width="100px">Date</th>
				<th>Notes</th>
				<th width="200px">Attendees</th>
			</tr>
		</thead>
		<tbody>
		<?php $count=0; foreach($interactions as $interaction): $count++; ?>
			<tr class="<?php echo ($count%2==0)?'odd':'even';?>">
				<td class="calign"><?php echo $interaction->interactionid;?></td>
				<td class="calign"><?php echo date("d M, Y",strtotime($interaction->meetingdate));?></td>
				<td>
				    <div style="margin-bottom:4px;border-bottom:1px solid #ccc;padding-bottom:2px;">
				        <?php echo $interaction->notes;?>
				    </div>
				    <div class="tags">
				        <i>Tags:</i> <?php echo $interaction->tags; ?>
				    </div>
				</td>
				<td>
				<?php
					$output = "";
					foreach($interaction->attendees as $contact) {
						$contact = new ContactObj($contact);
						$output .= "<a href=\"".Yii::app()->createUrl('contact')."?cid=".$contact->cid."\">".$contact->firstname." ".$contact->lastname."</a>, ";
					}
					print substr($output,0,-2);
				?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php }
else if($_REQUEST["rptype"]=="custom") {
    $contact = new ContactObj($_REQUEST["cid"]);
    if(isset($_REQUEST["date1"],$_REQUEST["date2"])) {
        Yii::app()->user->setFlash("success","Successfully ran report!");
    }

$flashes = new Flashes;
$flashes->render();
?>
<h2>Run Custom Report</h2>
<div class="hint">Running this report will serve up all the interactions for <span class="lime-green italic"><?php echo $contact->firstname." ".$contact->lastname; ?></span> between the given dates.</div>
<form method="post" action="<?php echo Yii::app()->createUrl('site/CustomReport'); ?>">
    <input type="hidden" name="cid" id="cid" value="<?php echo $_REQUEST["cid"]; ?>" />
    <table class="fancy-table">
        <tbody>
            <tr>
                <th><label for="date1">Starting Date: </label></th>
                <td><input type="text" id="date1" name="date1" value="<?php echo @$_REQUEST["date1"]; ?>" /></td>
            </tr>
            <tr>
                <th><label for="date2">Ending Date: </label></th>
                <td><input type="text" id="date2" name="date2" value="<?php echo @$_REQUEST["date2"]; ?>" /></td>
            </tr>
        </tbody>
    </table>
    <hr style="margin-top:25px;"/>
    
    <div class="button-container">
        <button class="submit">Run Report</button>
        <button class="cancel">Cancel</button>
    </div>
</form>
<script>
jQuery(document).ready(function($){
    $("button.cancel").click(function(){
       window.location = "<?php echo Yii::app()->createUrl('contact'); ?>?cid=<?php echo @$_REQUEST["cid"]; ?>";
       return false; 
    });
    $("#date1").datepicker({
      changeMonth: true,
      changeYear: true,
      dateFormat: 'yy-mm-dd'
    });
    $("#date2").datepicker({
      changeMonth: true,
      changeYear: true,
      dateFormat: 'yy-mm-dd'
    });
});
</script>
<?php
}
