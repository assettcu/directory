<?php
$flashes = new Flashes();
$flashes->render();

$contact = new ContactObj($_REQUEST["cid"]);
if(!$contact->loaded) $this->redirect(Yii::app()->createUrl('index'));

$user = new UserObj(Yii::app()->user->name);

// Box 1
$box = new Widget();
$box->header = StdLib::load_image("email.png","16px","16px")." Contact";
$box->content = "No Content Yet";
$box->styles["container"]["float"] = "none";
$box->styles["header"]["width"] = "494px";
$box->id = "widget-box-contact";

if(!Yii::app()->user->isGuest and $user->loaded and $user->permission >= 3):

$boxGraphs = clone $box;
$boxGraphs->header = StdLib::load_image("chart.png","16px","16px")." Graphs";
ob_start();
?>
<?php if($contact->count_interactions()==0): ?>
<div style="text-align:center;">This contact has no interactions with which to draw pretty graphs.</div>
<?php else: ?>
<div style="margin-bottom:15px;">
	Select a chart: 
	<select id="chart-select">
		<option value="chart1">Interactions per Department</option>
		<option value="chart2">Person Interactions</option>
	</select>
</div>
<div id="chart1" class="chart"></div>
<div id="chart2" class="chart hide"></div>

<script>
jQuery(document).ready(function($){
	
	$("#chart-select").change(function(){
		var $value = $(this).val();
		$("div.chart").hide();
		$("#"+$value).show();
		plot1.replot();
		plot2.replot();
	});
	
	<?php if($contact->count_interactions()!=0): ?>
	var data = <?=$contact->jqplot_load_interactions_by_dept();?>;
  var data2 = <?=$contact->jqplot_load_interactions_by_person();?>;
	var plot1 = jQuery.jqplot ('chart1', [data], 
		{
			title: "Interactions per Department (Top 8)",
			seriesDefaults: {
				// make this a donut chart.
				renderer:$.jqplot.DonutRenderer,
				rendererOptions:{
					// Donut's can be cut into slices like pies.
					sliceMargin: 3,
					showDataLabels: true,
					// By default, data labels show the percentage of the donut/pie.
					// You can show the data 'value' or data 'label' instead.
					dataLabels: 'value',
				},
			},
			legend: { show:true, location: 'e' },
			highlighter: {
				show: true,
			}
		}
	);
	var plot1 = jQuery.jqplot ('chart2', [data2], 
		{
			title: "Persons Interacted with (Top 8)",
			seriesDefaults: {
				// make this a donut chart.
				renderer:$.jqplot.DonutRenderer,
				rendererOptions:{
					// Donut's can be cut into slices like pies.
					sliceMargin: 3,
					showDataLabels: true,
					// By default, data labels show the percentage of the donut/pie.
					// You can show the data 'value' or data 'label' instead.
					dataLabels: 'value',
				},
			},
			legend: { show:true, location: 'e' },
			highlighter: {
				show: true,
			}
		}
	);
	<?php endif; ?>
});
</script>
<style>
	.jqplot-yaxis {
		padding-right:10px;
	}
	.jqplot-xaxis {
		padding-top:10px;
	}
	table.jqplot-table-legend {
		font-size:12px;
		padding:3px;
		max-width:250px;
	}
	td.jqplot-table-legend {
		padding-right:10px;
		max-width:200px;
	}
	span.jqplot-data-label {
		color:#fff;
		font-weight:bold;
	}
	.chart {
		width:100%;
	}
</style>
<?php endif; ?>

<?php
$contents = ob_get_contents();
ob_end_clean();
$boxGraphs->content = $contents;
endif; 

// Box 2: Notes
$boxNotes = clone $box;
$boxNotes->header = StdLib::load_image("notes.png","16px","16px")." Notes <span class='hide save-notify'> &lt; saved &gt;</span>";
ob_start();
?>

<textarea class="notes" style='width:98%;min-height:100px;'><?php echo $contact->load_notes($user->username);?></textarea>

<?php
$contents = ob_get_contents();
ob_end_clean();
$boxNotes->content = $contents;
$boxNotes->styles["content"]["background-color"] = "#fffddc";
$boxNotes->id = "widget-box-notes";


// Box 3: Tags
$boxTags = clone $box;
$atags = explode(",",$contact->tags);
if(count($atags)==1 and $atags[0]=="") $htag = "0";
else $htag = count($atags);
$boxTags->header = StdLib::load_image("tags.png","16px","16px")." Tags for ".$contact->firstname." ".$contact->lastname." (<span id='tag-count'>".$htag."</span>)";
ob_start();
?>
<div class="contact-tags-box">
	<div class="contact-tags">
	<?php
	$tags = explode(",",$contact->tags);
	if(strlen($contact->tags)>0):
		foreach($tags as $tag):
			if(trim($tag)=="") continue;
		?>
			<div class="tag-container" tag="<?=$tag?>"><?=$tag?> <a href="#"><?=StdLib::load_image("remove.png","13px","13px");?></a></div>
		<?php
		endforeach;
	else: ?>
		This contact does not have any tags.
	<?php endif; ?>
	</div>
	<?php if(!Yii::app()->user->isGuest): ?>
	<div class="add-tag-container">
		<a href="#" id="add-tag" style="color:#09f;">Add a Tag</a>
		<a href="#" id="done-tag-adding" class="hide" onclick="$('div.tags').hide();$(this).hide();$('#add-tag').show();" style="color:#09f;">done</a>
	</div>
	<div class="tags hide">
		<input type="text" width="80%" class="txtbox tags" id="new-tag" value="Add tags separated by commas" /> <button>Add Tag</button>
	</div>
	<?php endif; ?>
</div>
<?php
$contents = ob_get_contents();
ob_end_clean();
$boxTags->styles["content"]["padding"] = "10px";
$boxTags->content = $contents;
$boxTags->id = "widget-box-tags";

// Box 5: Reports
$reportBox = clone $box;
$reportBox->header = StdLib::load_image("paste.png","16px","16px")." Reports";
$reportBox->id = "widget-box-paste";
$reportBox->styles["content"]["min-height"] = "20px";
ob_start();
?>
<?php if(!Yii::app()->user->isGuest): ?>
<div class="bio-container" style="position:relative;">
	<div class="bio-filter" style="white-space: nowrap; overflow: hidden; max-width: inherit;">
	    <ul>
	        <li><a href="<?=Yii::app()->createUrl('site/report');?>?rptype=overall&cid=<?=$contact->cid;?>">Overall Report</a></li>
	        <li><a href="<?php echo Yii::app()->createUrl('site/report'); ?>?rptype=custom&cid=<?php echo $contact->cid; ?>">Custom Report</a></li>
	    </ul>
	</div>
</div>
<?php endif; ?>
<?php
$contents = ob_get_contents();
ob_end_clean();
$reportBox->content = $contents;


// Box 4: Biography
$boxBio = clone $box;
$boxBio->header = StdLib::load_image("book.png","16px","16px")." Biography";
$boxBio->id = "widget-box-bio";
$boxBio->styles["content"]["min-height"] = "20px";
ob_start();
?>
<div class="bio-container" style="position:relative;">
	<div class="bio-filter" style="white-space: nowrap; overflow: hidden; max-width: inherit;">
		<?=$contact->bio;?>
	</div>
	<?php if(!Yii::app()->user->isGuest): ?>
	<textarea class="hide bio-text" style="width:90%;min-height:150px;resize: none;"><?=$contact->bio?></textarea>
	<div style="position:absolute;top:2px;right:5px;">
		<a href="#" class="bio-edit" style="color:#09f;">edit</a>
		<a href="#" class="bio-edit-done hide" style="color:#09f;">done</a>
	</div>
	<?php endif; ?>
</div>
<?php
$contents = ob_get_contents();
ob_end_clean();
$boxBio->content = $contents;

// Box 5: Employment
$boxEmploy = clone $box;
$boxEmploy->header = StdLib::load_image("employment.png","16px","16px")." Employment";
$boxEmploy->styles["header"]["width"] = "474px";
$boxEmploy->id = "widget-box-employ";


if(0):
// Box 6: Additional Info
$boxInfo = clone $box;
$boxInfo->header = StdLib::load_image("information.png","16px","16px")." Additional Information";
$boxInfo->styles["header"]["width"] = "474px";
$boxInfo->id = "widget-box-info";
endif;

// Box 7: Quick Add Interactions
$boxInteraction = clone $box;
$boxInteraction->header = StdLib::load_image("comment_edit.png","16px","16px")." Log an Interaction";
$boxInteraction->styles["header"]["width"] = "474px";
$boxInteraction->id = "interaction-quickadd";
ob_start();
?>
<style>
#interaction-feed .widget-box-content {
	
}
</style>

<div class="add-interaction-container">
	<div>
		<div style="float:right;cursor:pointer;">
			<input type="text" name="i-date" id="i-date" class="hide" value="<?=date("Y-m-d");?>" />
			<span id="i-date-select"><?=StdLib::load_image('notes.png','16px','16px');?> <span class="date"><?=date("d M Y");?></span></span>
		</div>
	</div>
    <div style="margin-bottom:5px;">
        Who's involved:
        <input type="text" id="i-attendees" class="i-attendees" name="i-attendees" />
    </div>
    <div style="margin-bottom:5px;">
        Tags: <input type="text" id="i-tags" name="i-tags" class="tags" style="width:445px;padding:5px;" />
    </div>
	<div style="margin-bottom:5px;">
		Notes about the Interaction: <span class="hide required">Cannot be empty!</span>
		<textarea id="i-notes" style="min-height:150px;resize:none;width:451px;font-family:Verdana;font-size:11px;"></textarea>
	</div>
	<div id="i-advanced-options" class="hide">
		Advanced Options have not been initialized.
	</div>
	<div>
		<a href="#" id="i-advanced-link" style="color:#09f;float:left;">[+] advanced options</a>
		&nbsp;
		<a href="#" id="i-save" style="color:#09f;float:right;">save interaction</a>
	</div>
</div>

<script>
jQuery(document).ready(function($){
	$("#i-advanced-link").click(function(){
		if($("#i-advanced-options").is(":hidden"))
		{
			$("#i-advanced-options").stop().show('blind');
			$("#i-advanced-link").html("[-] hide options");
		} else {
			$("#i-advanced-options").stop().hide('blind');
			$("#i-advanced-link").html("[+] advanced options");
		}
	});
});
</script>

<?php
$contents = ob_get_contents();
ob_end_clean();
$boxInteraction->content = $contents;

// Box 8: Interaction Feed
$boxIfeed = clone $box;
$boxIfeed->header = StdLib::load_image("comment.png","16px","16px")." Interaction Feed";
$boxIfeed->styles["header"]["width"] = "474px";
$boxIfeed->id = "interaction-feed";
$boxIfeed->styles["content"]["padding"] = "0px";
$interactions = $contact->get_interactions();
ob_start();
?>
<style>
.feed {
	height:300px;
	border-bottom-right-radius:10px;
	border-top:0;
	overflow: hidden;
	clear:both;
	min-height:250px;
	max-height:1500px;
	position:relative;
	margin-right:-2px;
	padding-bottom:12px;
}
#ifeed-container {
	width:478px;
	border-left:1px solid #ccc;
	border-bottom:1px solid #ccc;
	height:100%;
	z-index:0;
	background-color:#fff;
	border-right:1px solid #ccc;
	position:relative;
}
#ifeed-container .scroll {
	position:relative;
	overflow:auto;
	height:100%;
}
#ifeed-container .item {
	clear:both;
	padding:6px;
	border-bottom:1px solid #DBDBDB;
}
#ifeed-container .news_summary {
	color: #666;
	padding: 2px 0;
}
#ifeed-container .news_summary .icon_col {
	float:left;
	width:25px;
	overflow:hidden;
	padding-top:4px;
	padding:3px;
	cursor:pointer;
}
#ifeed-container .news_summary .ifeed-title {
	font-weight:normal;
	margin-left:28px;
	color:#666;
	font-size:11px;
	padding-bottom:2px;
	cursor:pointer;
}
#ifeed-container .date_bucket {
	background: url("<?=Yii::app()->baseUrl;?>/images/gradient_x.png") repeat-x 0 0;
	width:auto;
	height:14px;
	padding:6px 0 2px 4px;
	font-size:10px;
	color:#777;
}
#ifeed-container .item .news_summary .ifeed-source {
	margin-left:28px;
	font-size:10px;
	color:#ADADAD;
}
#ifeed-container .item .news_summary a {
	color: #759DC5;
}
#ifeed-container .item .news_summary .actions_strip {
	margin-top:-18px;
	visibility:hidden;
	margin-right:0;
	background-color:#fff;
	z-index:10000;
	clear:both;
	padding-top:5px;
	float:right;
	height:20px;
}
#ifeed-container .item .news_summary .actions_strip a {
	display:block;
	float:left;
	padding-right:10px;
}
.icon {
	font-size:10px;
	height:16px;
	padding-top:1px;
	padding-left:21px;
	color:#0074B0;
	overflow: hidden;
	text-transform:uppercase;
	text-align:left;
	white-space: nowrap;
	display:block;
	background-repeat:no-repeat;
	background-position:0 0;
	background-image: url("<?=Yii::app()->baseUrl;?>/images/mini_icons.png");
}
.icon.flag_news_button {
	background-position:0px -4104px;
}
.icon.share_news_button {
	background-position:0px -4464px;
}
.icon.post_facebook {
	background-position:0px -2232px;
}
.icon.post_twitter {
	background-position:0px -3024px;
}
span.clickformore {
	font-size:10px;
	color:#09f;
	white-space:nowrap;
}
span.clickforless {
	font-size:10px;
	color:#f09;
	white-space:nowrap;
}
</style>
<div class="feed">
	<div id="ifeed-container">
		<div class="scroll">
		<?php if(count($interactions)==0):?>
			<div class="date_bucket" style="text-align:center;padding-top:10px;font-size:12px;">
				There are no interactions to report.
			</div>
		<?php else: ?>
			<?php $flag = 0; foreach($interactions as $interaction): ?>
				<?php if($flag <= 0 and strtotime($interaction->meetingdate)>strtotime("-1 week")): $flag = 1; ?>
					<div class="date_bucket">Last 7 Days</div>
				<?php elseif($flag <= 1 and strtotime($interaction->meetingdate)<strtotime("-1 week") and strtotime($interaction->meetingdate)>strtotime("-3 months")): $flag = 2; ?>
					<div class="date_bucket">Last 3 Months</div>
				<?php elseif($flag <= 2 and strtotime($interaction->meetingdate)<strtotime("-3 months") and strtotime($interaction->meetingdate)>strtotime("-1 year")): $flag = 3; ?>
					<div class="date_bucket">Past Year</div>
				<?php elseif($flag <= 3 and strtotime($interaction->meetingdate)<strtotime("-1 year")): $flag = 4; ?>
					<div class="date_bucket">Old</div>
				<?php endif; ?>
				<?php
				$notes = strip_tags($interaction->notes);
				$ext = "";
				if(strlen($notes)>120){ $notes = substr($notes,0,120); $ext = substr(strip_tags($interaction->notes),120); }
				?>
				<div class="item">
					<div class="news_summary">
						<div class="icon_col">
							<?=StdLib::load_image("Newsfeed RSS.png","20px","20px");?>
						</div>
						<div class="ifeed-title"><?=$notes?><?php if(strlen(strip_tags($interaction->notes))>120): ?><span class="more">... <span class="clickformore">[click for more]</span></span><span class="ifeed-title-full hide"><?=$ext?> <span class="clickforless">[click to hide]</span></span><?php endif; ?></div>
						<div class="ifeed-source">
						<?php
							$output = '';
							foreach($interaction->attendees as $cid)
							{
								$acontact = new ContactObj($cid);
								if(!$acontact->loaded) continue;
								$output .= '<a href="'.Yii::app()->createUrl("contact").'?cid='.$acontact->cid.'">'.$acontact->firstname.' '.$acontact->lastname.'</a>, ';
							}
							print substr($output,0,-2);
						?>
						<br/><em><?=StdLib::format_date($interaction->meetingdate,"nice-notime");?></em>
						<a href="#" class="iedit-link" interactionid="<?=$interaction->interactionid;?>">[edit]</a> <a href="#" class="idelete-link" interactionid="<?=$interaction->interactionid;?>">[delete]</a>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
		</div>
	</div>
</div>
<?php
$contents = ob_get_contents();
ob_end_clean();
$boxIfeed->content = $contents;

?>
<div class="cpanel-left">
	<div class="contact-profile" style="position:relative;">
		<?php if(!Yii::app()->user->isGuest and (Yii::app()->user->userobj->permission > 2 or Yii::app()->user->userobj->username == $contact->username)): ?>
		<div class="edit-contact" style="position:absolute;top:-10px;right:15px;background-color:#fff;width:40px;text-align: center;font-size:13px;">
			<a href="#" id="edit-contact-link">edit</a>
		</div>
		<?php endif; ?>
		<table>
			<tr>
				<td class="tvalign profile-pic"><?=StdLib::load_profile_image($contact->cid."-".$contact->lastname,"125px","125px");?></td>
				<td class="tvalign">
					<div class="profile-info-box">
						<div class="contact-name">
							<?=$contact->firstname." ".$contact->lastname?>
						</div>
						<?php
						foreach($contact->departments as $dept):
							if(!$dept->loaded) continue;
						?>
						<div class="contact-department">
							<div class="department-details">
								<div class="img-src-box"><?=StdLib::load_image("building-home.png","18px","18px");?></div>
								<a href="#"><?=$dept->deptname;?></a>
							</div>
							<?php
							$output = "";
							if(!empty($dept->positions)){
								foreach($dept->positions as $pos)
								{
									if(isset($pos->posname) and $pos->posname!="")
										$output .= $pos->posname.", ";
								}
							}
							if($output != ""):
								$output = substr($output,0,-2);
							?>
							<div class="department-positions">
								<?=StdLib::load_image("user-icon.png","16px","16px");?>
								<?=$output?>
							</div>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
						<div class="contact-details">
							<?php if(strlen($contact->username)>0): ?>
							<div class="cdetail username">
								<div class="img-src-box" style="margin-top:0px;cursor: pointer;" title="Select username"><?=StdLib::load_image("user.png","18px","18px");?></div>
								<span><?=$contact->username?></span>
							</div>
							<?php endif; ?>
							<?php if(isset(Yii::app()->user->userobj) and Yii::app()->user->userobj->permission>3): ?>
							<div class="cdetail permission">
								<div class="img-src-box" style="margin-top:0px;"><?=StdLib::load_image("perm.jpg","18px","18px");?></div>
								<span><?=$contact->text_permission_level();?></span>
							</div>
							<?php endif; ?>
							<?php if(strlen($contact->email)>0): ?>
							<div class="cdetail email">
								<div class="img-src-box" style="margin-top:0px;cursor: pointer;" title="Select email address"><?=StdLib::load_image("envelope_gold.png","18px","18px");?></div>
								<span><?=$contact->email?></span>
							</div>
							<?php endif; ?>
							<?php if(strlen($contact->phone)>0): ?>
							<div class="cdetail phone">
								<div class="img-src-box" style="margin-top:0px;cursor: pointer;" title="Select phone text"><?=StdLib::load_image("phone-blue.png","18px","18px");?></div>
								<span><?=$contact->phone?></span>
							</div>
							<?php endif; ?>
							<?php if(isset(Yii::app()->user->userobj) and Yii::app()->user->userobj->permission>3): ?>
								<?php if(strlen($contact->phone2)>0): ?>
								<div class="cdetail phone">
									<div class="img-src-box" style="margin-top:0px;cursor: pointer;" title="Select alternate phone text"><?=StdLib::load_image("phone.png","18px","18px");?></div>
									<span><?=$contact->phone2?></span>
								</div>
								<?php endif; ?>
							<?php endif; ?>
							<?php if(strlen($contact->googlephone)>0): ?>
							<div class="cdetail phone">
								<div class="img-src-box" style="margin-top:0px;cursor: pointer;" title="Select google phone text"><?=StdLib::load_image("Google.png","18px","18px");?></div>
								<span><?=$contact->googlephone?></span>
							</div>
							<?php endif; ?>
						</div>
					</div>
				</td>
			</tr>
		</table>

	</div>
	
	<ul class="content-list" id="contact-left-pane">
		<?php if(!Yii::app()->user->isGuest): ?>
			<?php if(isset(Yii::app()->user->userobj) and Yii::app()->user->userobj->permission >= 2): ?>
			<li id="widget-iquickadd"><?=$boxInteraction->render();?></li>
			<?php endif; ?>
			<?php if(isset(Yii::app()->user->userobj) and Yii::app()->user->userobj->permission >= 2): ?>
			<li id="widget-ifeed"><?=$boxIfeed->render();?></li>
			<?php endif; ?>
		<?php endif; ?>
	</ul>

	
</div>
<div class="cpanel-right">
<ul class="content-list" id="contact-right-pane">
    <?php if(!Yii::app()->user->isGuest and $user->loaded and $user->permission >= 3): ?>
    <li id="widget-igraphs"><?=$boxGraphs->render();?></li>
    <?php endif; ?>
    <?php if(!Yii::app()->user->isGuest): ?>
    <li id="widget-report"><?=$reportBox->render();?></li>
    <?php endif; ?>
	<?php if(!Yii::app()->user->isGuest): ?>
		<li id="widget-notes"><?=$boxNotes->render();?></li>
	<?php endif; ?>
	<li id="widget-tags"><?=$boxTags->render();?></li>
	<li id="widget-bio"><?=$boxBio->render();?></li>
</ul>
</div>

<style>
	table#edit-contact-table tr td label {
		font-weight:bold;
	}
	table#edit-contact-table tr td > label {
		margin-right:25px;
	}
	table#edit-contact-table tr td input,
	table#edit-contact-table tr td select{
		border:1px solid #8496BA;
		font-family: Verdana;
		font-size:12px;
		padding:3px;
	}
	table#edit-contact-table tr td select option:hover {
		background-color: #8496BA;
		color:#fff;
		font-weight:bold;
	}
	.required {
		color:#a00;
	}
	div.widget-header {
		font-size:13px;
		margin-bottom:10px;
		margin-top:3px;
	}
	.list-item-dept {
		font-weight:bold;
		color:#a00;
	}
	div.token-input-dropdown-facebook {
		z-index:10000;
	}
</style>

<?php if(isset(Yii::app()->user->userobj) and (Yii::app()->user->userobj->permission > 2 or Yii::app()->user->userobj->username == $contact->username)): ?>
<script>
jQuery(document).ready(function($){
	$("#permission-change").change(function(){
		$("#perm-level-num").html($(this).val());
	});
});
</script>

<div id="edit-contact-dialog" title="<?=$contact->firstname." ".$contact->lastname;?> | Edit Contact">
	<div class="ui-state-highlight ui-corner-all hide cedit-success" style="padding:5px;"></div>
	<div style="width:50%;float:left;">
		<div class="widget-header">Edit contact information: </div>
		<form id="cedit-form">
			<table id="edit-contact-table">
				<tr>
					<td><label for="firstname">First Name <span class="required">*</span></label></td>
					<td><input type="text" name="firstname" class="txtbox" value="<?=$contact->firstname?>" style="width:150px;"/></td>
				</tr>
				<tr>
					<td><label for="lastname">Last Name <span class="required">*</span></label></td>
					<td><input type="text" name="lastname" class="txtbox" value="<?=$contact->lastname?>" style="width:200px;" /></td>
				</tr>
				<tr>
					<td><label for="username">Username</label></td>
					<td><input type="text" name="username" class="txtbox" value="<?=$contact->username?>" style="width:100px;" maxlength="8" /></td>
				</tr>
				<?php
				$permlevel = isset($contact->userobj->permission)?$contact->userobj->permission:0;
				if($user->loaded and $user->permission >= 3 and $user->permission >= (isset($contact->userobj->permission)?$contact->userobj->permission:0)) : ?>
				<tr>
					<td><label for="username">Make active user?</label></td>
					<td><input type="radio" name="makeuser" value="1" <?php if(@$contact->useroobj->loaded and $contact->userobj->active==1): ?>checked="checked"<?php endif; ?> /> yes <input type="radio" name="makeuser" value="0" <?php if(!isset($contact->userobj) or @$contact->useroobj->loaded and $contact->userobj->active==0): ?>checked="checked"<?php endif; ?> /> no</td>
				</tr>
				<tr>
					<td><label for="username">Permission Level</label></td>
					<td>
						<select name="permission" id="permission-change" style="width:200px;">
							<option value="0" <?php if($permlevel==0): ?>selected="selected"<?php endif; ?>>Guest</option>
							<option value="1" <?php if($permlevel): ?>selected="selected"<?php endif; ?>>Registered User</option>
							<option value="2" <?php if($permlevel): ?>selected="selected"<?php endif; ?>>Manager</option>
							<option value="3" <?php if($permlevel): ?>selected="selected"<?php endif; ?>>ASSETT Manager</option>
							<option value="4" <?php if($permlevel): ?>selected="selected"<?php endif; ?>>Administrator</option>
							<?php if($user->loaded and $user->permission == 10): ?>
							<option value="10" <?php if($permlevel): ?>selected="selected"<?php endif; ?>>Super Administrator</option>
							<?php endif; ?>
						</select>
						<span id="perm-level-num"><?=$permlevel?></span>
					</td>
				</tr>
				<?php endif; ?>
				<tr>
					<td><label for="email">Email</label></td>
					<td><input type="text" name="email" class="txtbox" value="<?=$contact->email?>" style="width:230px;" /></td>
				</tr>
				<tr>
					<td><label for="phone">Work Phone</label></td>
					<td><input type="text" name="phone" class="txtbox" value="<?=$contact->phone?>" style="width:150px;" /></td>
				</tr>
				<tr>
					<td><label for="phone2">Personal Phone</label></td>
					<td><input type="text" name="phone2" class="txtbox" value="<?=$contact->phone2?>" style="width:150px;" /></td>
				</tr>
				<tr>
					<td><label for="phone2">Google Phone</label></td>
					<td><input type="text" name="googlephone" class="txtbox" value="<?=$contact->googlephone?>" style="width:150px;" /></td>
				</tr>
			</table>
		</form>
	</div>
	<div style="width:50%;float:right;">
		<div class="widget-header">Select department contact belongs with: </div>
		<input type="text" id="cedit-dept" name="cedit-dept" />
		
		<div class="widget-header">Add the contact position(s):</div>
		<div class="cedit-pos" style="margin-bottom:10px;">
			<?php if(!empty($contact->departments)): ?>
			<?php
			foreach($contact->departments as $dept):
				if(!$dept->loaded) continue;
				
			?>
				<div class="list-item" deptid="<?=$dept->deptid;?>">
					<div class="list-item-dept" deptid="<?=$dept->deptid;?>"><?=$dept->deptname;?></div>
					<?php
						$positions = $contact->get_positions_by_dept($dept->deptid);
						foreach($positions as $pos):
							if($pos["posname"]=="") continue;
					?>
						<div style="margin-left:15px;" posname="<?=$pos["posname"]?>" deptid="<?=$dept->deptid?>"><?=$pos["posname"];?> <a href="#" id="contact-remove-position">[x]</a></div>
						<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<select id="positions"><option class="empty" value=""></option></select>
		<a href="#" style="text-decoration: none;" id="contact-add-position">[+] Add</a>
	</div>
</div>
<?php endif; ?>

<?php if(isset(Yii::app()->user->userobj) and Yii::app()->user->userobj->permission > 2): ?>
<div id="iedit-dialog" title="Edit Interaction">
	<input type="hidden" name="iedit-id" id="iedit-id" value="" />
	<div class="edit-interaction-container">
		<div>
			<div style="float:right;cursor:pointer;">
				<input type="text" name="iedit-date" id="iedit-date" class="hide" value="" />
				<span id="iedit-date-select"><?=StdLib::load_image('notes.png','16px','16px');?> <span class="date"></span></span>
			</div>
		</div>
		<div style="margin-bottom:5px;">
			Notes about the Interaction: <span class="hide required">Cannot be empty!</span>
			<textarea id="iedit-notes" style="min-height:100px;resize:none;width:451px;font-family:Verdana,sans-serif;font-size:11px;letter-spacing:0px;"></textarea>
		</div>
		<div style="margin-bottom:5px;">
			Who's involved:
			<input type="text" id="iedit-attendees" class="iedit-attendees" name="iedit-attendees" />
		</div>
		<div style="margin-bottom:5px;">
			Tags: <input type="text" id="iedit-tags" name="iedit-tags" class="tags" style="width:445px;padding:5px;font-family:Verdana,sans-serif;font-size:11px;letter-spacing:0px;" />
		</div>
	</div>
</div>
<?php endif; ?>

<?php if(isset(Yii::app()->user->userobj) and Yii::app()->user->userobj->permission > 2): ?>
<div id="idelete-dialog" title="Delete Interaction?">
	<input id="idelete-id" type="hidden" />
	<p>Are you sure you wish to delete this intereaction?</p>
</div>
<?php endif; ?>

<?php if(isset(Yii::app()->user->userobj) and Yii::app()->user->userobj->permission > 2): ?>
<script>
var savenotes;
var DeptTokenInput;
var IAttendeesTokenInput;
var IEditAttendeesTokenInput;
var DeptListLoaded = false;
jQuery(window).load(function(){
	if(parseAnchor("edit")=="1")
	{
		$("#edit-contact-dialog").dialog("open");
		if(parseAnchor("saved")=="1")
		{
			$("#edit-contact-dialog div.cedit-success").html("Successfully saved contact information.").show("slide","","slow").delay(3000).hide("slide","","slow");
		}
	}
});
jQuery(document).ready(function(){
	
	$("div.ifeed-title").click(function(){
		if($(this).find("span.more").is(":visible"))
		{
			$(this).find("span.more").stop().hide('fade',{},300,function(){
				$(this).parent().find("span.ifeed-title-full").stop().show('fade').css('display','inline');
			});
		} else {
			$(this).find("span.ifeed-title-full").stop().hide('fade',{},300,function(){
				$(this).parent().find("span.more").stop().show("fade").css('display','inline');
			});
		}
	});
	
	$("#contact-remove-position").live('click',function(){
		var $robj = $(this).parent();
		var posname = $(this).parent().attr('posname');
		var deptid = $(this).parent().attr('deptid');
		
		$.ajax({
			"url":			"<?=Yii::app()->createUrl('_remove_position_from_contact');?>",
			"data":			"cid=<?=$contact->cid;?>&posname="+posname+"&deptid="+deptid,
			"success":	function(){
				$robj.hide('fade',{},'fast',function(){$robj.remove();});
			}
		});
	});
	
	$("#contact-add-position").live('click',function(){
		var $robj = $(this).parent();
		var posname = $("#positions").val();
		if(posname=="") return false;
		var deptid = $('#positions :selected').parent().attr('deptid');
		
		$.ajax({
			"url":			"<?=Yii::app()->createUrl('_add_position_to_contact');?>",
			"data":			"cid=<?=$contact->cid;?>&posname="+posname+"&deptid="+deptid,
			"success":	function(data){
				if(data==1)
				{
					var output = '<div style="margin-left:15px;font-weight:normal;color:#333;" posname="'+posname+'" deptid="'+deptid+'">'+posname+' <a href="#" id="contact-remove-position">[x]</a></div>';
					$(".cedit-pos div.list-item-dept[deptid='"+deptid+"']").append(output);
				}
			}
		});
		
	});
	
	$(".idelete-link").live('click',function(){
		$("#idelete-id").val($(this).attr("interactionid"));
		$("#idelete-dialog").dialog("open");
		return false;
	});
	
	$("#idelete-dialog").dialog({
		"autoOpen":			false,
		"width":				400,
		"height":				150,
		"modal":				true,
		"resizable":		false,
		buttons:				{
			"Cancel":			function(){
				$("#idelete-dialog").dialog("close");
			},
			"Delete Interaction":	function(){
				$.ajax({
					"url":		"<?=Yii::app()->createUrl('_delete_interaction');?>",
					"data":		"interactionid="+$("#idelete-id").val(),
					"success":		function(){
						// HERRDWERR SUCCESSFERRR
					}
				});
				$("#idelete-dialog").dialog("close");
				window.location.reload();
			}
		}
	});
	
	$('#idelete-dialog').bind('dialogclose', function(event) {
		$("#idelete-id").val("");
	});
	
	$(".iedit-link").click(function(){
		IEditAttendeesTokenInput.tokenInput("clear");
		$("#iedit-id").val($(this).attr("interactionid"));
		$.ajax({
			"url":			"<?=Yii::app()->createUrl('_load_interaction');?>",
			"data":			"interactionid="+$(this).attr("interactionid"),
			"dataType":	"JSON",
			"success":	function(data){
				$("#iedit-notes").html(data.notes);
				$("#iedit-tags").val(data.tags);
				$("#iedit-date").val(data.date);
				$("#iedit-date-select span.date").text(data.date);
				$.each(data.attendees,function(key,value){
					IEditAttendeesTokenInput.tokenInput("add",{id: data.attendees[key].cid, name: data.attendees[key].name });
				});
			}
		});
		$("#iedit-dialog").dialog("open");
		return false;
	});
	
	$("#iedit-dialog").dialog({
		"autoOpen":		false,
		"modal":			true,
		"width":			475,
		"resizable":	false,
		"buttons":		{
			"Cancel":		function()
			{
				$("#iedit-dialog").dialog("close");
			},
			"Save Interaction": function()
			{
				$.ajax({
					url:				"<?=Yii::app()->createUrl('_save_quick_interaction');?>",
					data:				"cid=<?=$contact->cid;?>&interactionid="+$("#iedit-id").val()+"&d="+$("#iedit-date").val()+"&notes="+escape($("#iedit-notes").val())+"&a="+$("#iedit-attendees").val()+"&tags="+$("#iedit-tags").val(),
					success:		function(){
						window.location.reload();
					}
				});
				$("#iedit-dialog").dialog("close");
			}
		}
	});
	
	// Interaction - Autocomplete for tags
	function split( val ) {
		return val.split( /,\s*/ );
	}
	function extractLast( term ) {
		return split( term ).pop();
	}

	$("#edit-contact-link").click(function(){
		$("#edit-contact-dialog").dialog("open");
		return false;
	});
	
	// Departments - autocomplete
	DeptTokenInput = $("#cedit-dept").tokenInput("<?=Yii::app()->createUrl('_load_quick_departments');?>", {
			theme: "facebook",
			hintText: "Start typing to look up departments",
			noResultsText: "There are no departments by that name",
			searchingText: "Searching departments...",
			preventDuplicates: true,
			zindex: "11000",
			onAdd: function(item){
				var flag = false;
				$(".cedit-pos .list-item-dept").each(function(){
					if($(this).text()==item.name) flag = true;
				});
				if(!flag){
					$(".cedit-pos").append('<div class="list-item" deptid="'+item.id+'"><div class="list-item-dept" deptid="'+item.id+'">'+item.name+'</div></div>');
					$.ajax({
						"url":			"<?=Yii::app()->createUrl('_add_position_to_contact');?>",
						"data":			"cid=<?=$contact->cid;?>&posname=&deptid="+item.id,
						"success":	function(data){
						}
					});
				}
				var append_ = "<optgroup deptid='"+item.id+"' label='"+item.name+"'>";
				$.ajax({
					"url":	"<?=Yii::app()->createUrl('site/_get_positions_from_department')?>",
					"data": "deptid="+item.id,
					"dataType": 'json',
					"success":	function(data)
					{
						var items = '';
						$.each(data,function(key,value){
							items = items + '<option value="'+value.posname+'">'+value.posname+'</option>';
						});
						append_ = append_ + items + "</optgroup>";
						$("#positions").append(append_);
					}
				});
			},
			onDelete: function(item){
				$.ajax({
					"url":	"<?=Yii::app()->createUrl('site/_remove_contact_department')?>",
					"data": "deptid="+item.id+"&cid=<?=$contact->cid;?>",
					"dataType": 'json',
					"success":	function()
					{
						$(".cedit-pos div.list-item[deptid='"+item.id+"']").hide('fade',{},"fast",function(){$(this).remove();});
					}
				});
			}
	});
	
	$("#edit-contact-dialog").dialog({
		"autoOpen":		false,
		"modal":			true,
		"width":			830,
		"height":			450,
		"resizable":	false,
		"open":				function(){
			updateAnchor("edit",1);
			if(!DeptListLoaded)
			{
			<?php
			foreach($contact->departments as $dept):
				if(!$dept->loaded) continue;
			?>
			DeptTokenInput.tokenInput("add",{id: "<?=$dept->deptid?>", name: "<?=$dept->deptname;?>"});
			<?php endforeach; ?>
			DeptListLoaded = true;
			}
		},
		"buttons":		{
			"Close":		function()
			{
				$("#edit-contact-dialog").dialog("close");
			},
			"Save Contact":		function()
			{
				var formdata = $("#cedit-form").serialize();
				console.log(formdata);
				$.ajax({
					"url":			"<?=Yii::app()->createUrl('_save_contact')?>",
					"data":			formdata+"&cid=<?=$contact->cid;?>",
					"success":	function(data)
					{
						updateAnchor("edit",1);
						updateAnchor("saved",1);
						window.location.reload();
					}
				});
			}
		},
		close:			function(){
			removeAnchor("edit");
			removeAnchor("saved");
		}
	});
	
	$( ".tags" )
	// don't navigate away from the field on tab when selecting an item
	.bind( "keydown", function( event ) {
		if ( event.keyCode === $.ui.keyCode.TAB &&
				$( this ).data( "autocomplete" ).menu.active ) {
			event.preventDefault();
		}
	})
	.autocomplete({
		source: function( request, response ) {
			$.getJSON( "<?=Yii::app()->createUrl('_ajax_filter_tags');?>", {
				term: extractLast( request.term )
			}, response );
		},
		search: function() {
			// custom minLength
			var term = extractLast( this.value );
			if ( term.length < 2 ) {
				return false;
			}
		},
		focus: function() {
			// prevent value inserted on focus
			return false;
		},
		select: function( event, ui ) {
			var terms = split( this.value );
			// remove the current input
			terms.pop();
			// add the selected item
			terms.push( ui.item.value );
			// add placeholder to get the comma-and-space at the end
			terms.push( "" );
			this.value = terms.join( ", " );
			return false;
		}
	});
	
	// Interaction - names autocomplete
	IAttendeesTokenInput = $("#i-attendees").tokenInput("<?=Yii::app()->createUrl('_load_quick_contacts');?>", {
			theme: "facebook",
			hintText: "Start typing to look up contacts",
			noResultsText: "No one found by that name",
			searchingText: "Searching contacts...",
			preventDuplicates: true,
			zindex: "1005",
	}).tokenInput("add",{id: "<?=$contact->cid?>", name: "<?=$contact->firstname." ".$contact->lastname;?>"});
	
	// Interaction - names autocomplete
	IEditAttendeesTokenInput = $("#iedit-attendees").tokenInput("<?=Yii::app()->createUrl('_load_quick_contacts');?>", {
			theme: "facebook",
			hintText: "Start typing to look up contacts",
			noResultsText: "No one found by that name",
			searchingText: "Searching contacts...",
			preventDuplicates: true,
			zindex: "1005",
	});
	
	// Interaction - date calendar
	$("#i-date").datepicker({
		showWeek: true,
		firstDay: 1,
		dateFormat: "d M yy",
		onSelect:		function(dateText, inst) {
			$("#i-date-select span.date").text(dateText);
		}
	});
	
	$("#i-date-select").click(function(){
		$("#i-date").datepicker("show");
	});
	
	// Interaction - date calendar
	$("#iedit-date").datepicker({
		showWeek: true,
		firstDay: 1,
		dateFormat: "d M yy",
		onSelect:		function(dateText, inst) {
			$("#iedit-date-select span.date").text(dateText);
		}
	});
	
	$("#iedit-date-select").click(function(){
		$("#iedit-date").datepicker("show");
	});
	
	// Interaction - submit new interaction
	$("#i-save").click(function(){
		$.ajax({
			url:				"<?=Yii::app()->createUrl('_save_quick_interaction');?>",
			data:				"cid=<?=$contact->cid;?>&d="+$("#i-date").val()+"&notes="+escape($("#i-notes").val())+"&a="+$("#i-attendees").val()+"&tags="+$("#i-tags").val(),
			success:		function(){
				window.location.reload();
			}
		});
	});
	
	
	$("div.add-tag-container a#add-tag").click(function(){
		$('div.tags').show();
		$(this).hide();
		$('#done-tag-adding').show();
	});
	
	$("div.tag-container img").fadeTo(0,.5);
	
	$("a.bio-edit").click(function(){
		$("a.bio-edit").hide();
		$("a.bio-edit-done").show();
		$("div.bio-filter").hide('fade','fast',function(){
			$("textarea.bio-text").show('blind');
		});
	});
	
	$("a.bio-edit-done").click(function(){
		$.ajax({
			"url":			"<?=Yii::app()->createUrl('_save_contact_bio');?>",
			"data":			"cid=<?=$contact->cid;?>&bio="+escape($("textarea.bio-text").val()),
			"success":	function(){
				$("textarea.bio-text").hide('fade','fast',function(){
					$("div.bio-filter").html($("textarea.bio-text").val()).show('fade');
					$("a.bio-edit").show();
					$("a.bio-edit-done").hide();
				});
			}
		});
	});
	
	$("div.tags input").focus(function(){
		if($(this).val()=="Add tags separated by commas")
		{
			$(this).val("");
		}
		$(this).css("border","1px solid #069").css("color","#333");
	}).blur(function(){
		if($(this).val()=="")
		{
			$(this).val("Add tags separated by commas");
		}
		$(this).css("border","1px solid #aaa").css("color","#aaa");
	});
	
	$("div.tags button").click(function(){
		$tag = $("div.tags input").val();
		if($tag=="" || $tag == "Add tags separated by commas") return false;
		$.ajax({
			"url":			"<?=Yii::app()->createUrl('_add_contact_tag');?>",
			"data":			"tags="+$tag+"&cid=<?=$contact->cid;?>",
			"dataType": "JSON",
			"success":	function(data){
				if(data.tags.length>0)
				{
					var $tagbox = "";
					$.each(data.tags, function(key,value){
						if(value!="")
						{
							$tagbox += '<div class=\"tag-container\">'+value+' <a href=\"#\"><?=StdLib::load_image("remove.png","13px","13px");?></a></div>';
						}
					});
					console.log($tagbox);
					$("div.contact-tags").html($tagbox);
					$("span#tag-count").text(data.tags.length);
				}
				$("div.tags input").val("").focus();
				$("div.tag-container img").fadeTo(100,.5);
				return true;
			}
		});
		return true;
	});
	
	$("div.tag-container a").live('click',function(){
		var $tag = $(this).parent().attr('tag');
		$(this).parent().remove();
		var tagcount = $("#tag-count").text();
		tagcount = parseInt(tagcount) - 1;
		$("#tag-count").text(tagcount);
		$.ajax({
			"url":				"<?=Yii::app()->createUrl('_remove_contact_tag');?>",
			"data":				"cid=<?=$contact->cid;?>&tag="+escape($tag)
		});
	});
	
	$("div.tag-container img").live('hover',function(e){
		if(e.type=="mouseenter"){
				$(this).fadeTo(100,1);
		}
		if(e.type=="mouseleave"){
				$(this).fadeTo(100,.5);
		}
	});
	
	$("a[href='#']").live('click',function(){
		return false;
	});
	
	$(".img-src-box").click(function(){
		if($(this).parent().find('span').length==1)
			selectText($(this).parent().find('span')[0]);
	});
	
	$(".content-list").sortable({
		placeholder: "ui-state-highlight",
		handle: ".drag-me",
		axis: "y",
		update:		function(event, ui){
			var $order = $(this).sortable('toArray').toString();
			$.ajax({
				"url":		"<?=Yii::app()->createUrl('_save_widget_order');?>",
				"data":		"type="+$(this).attr('id')+"&order="+$order
			});
		}
	});
	$("textarea.notes").keyup(function(){
		var $notes = $(this).val();
		typewatch(function () {
			// executed only 1500 ms after the last keyup event.
			$.ajax({
				"url":			"<?=Yii::app()->createUrl('_save_notes');?>",
				"data":			"cid=<?=$contact->cid;?>&notes="+escape($notes),
				"success":	function(){
					$('span.save-notify').fadeIn(500).delay(1000).fadeOut(500);
				}
			});
		}, 1500);
	});
});

var typewatch = (function(){
  var timer = 0;
  return function(callback, ms){
    clearTimeout (timer);
    timer = setTimeout(callback, ms);
  }  
})();

function selectText(element) {
	var doc = document;
	var text = element;

	if (doc.body.createTextRange) { // ms
		var range = doc.body.createTextRange();
		range.moveToElementText(text);
		range.select();
	} else if (window.getSelection) { // moz, opera, webkit
		var selection = window.getSelection();            
		var range = doc.createRange();
		range.selectNodeContents(text);
		selection.removeAllRanges();
		selection.addRange(range);
	}
}
function parseAnchor(akey)
{
	var hash = location.hash.substring(1);
	if(hash=="") return "";
	var $params = hash.split("&");
	var $return = "";
	$.each($params,function(key,value){
		var key = value.split("=")[0];
		var value = value.split("=")[1];
		if(key==akey) $return = value;
	});
	return unescape($return);
}
function updateAnchor(akey,avalue)
{
	var hash = location.hash.substring(1);
	var $params = hash.split("&");
	var updated = false;
	var hashstring = "";
	if(hash.length!=0)
	{
		$.each($params,function(key,value){
			var key = value.split("=")[0];
			var value = value.split("=")[1];
			if(key==akey)
			{
				updated = true;
				hashstring = hashstring + akey + "=" + avalue + "&";
			} else {
				hashstring = hashstring + key + "=" + value + "&";
			}
		});
	}
	if(!updated) hashstring = hashstring + akey + "=" + avalue;
	else hashstring = hashstring.substring(0,hashstring.length-1);
	window.location.hash = hashstring;
}
function removeAnchor(akey)
{
	var hash = location.hash.substring(1);
	var $params = hash.split("&");
	var updated = false;
	var hashstring = "";
	if(hash.length!=0)
	{
		$.each($params,function(key,value){
			var key = value.split("=")[0];
			var value = value.split("=")[1];
			if(key!=akey)
			{
				hashstring = hashstring + key + "=" + value + "&";
			}
		});
	}
	hashstring = hashstring.substring(0,hashstring.length-1);
	window.location.hash = hashstring;
}
</script>
<?php else: ?>
<script>
jQuery(document).ready(function($){
	$("div.tag-container a img").fadeTo('fast',0.1).parent().css("cursor","default");
	
	$(".img-src-box").click(function(){
		if($(this).parent().find('span').length==1)
			selectText($(this).parent().find('span')[0]);
	});
});

function selectText(element) {
	var doc = document;
	var text = element;

	if (doc.body.createTextRange) { // ms
		var range = doc.body.createTextRange();
		range.moveToElementText(text);
		range.select();
	} else if (window.getSelection) { // moz, opera, webkit
		var selection = window.getSelection();            
		var range = doc.createRange();
		range.selectNodeContents(text);
		selection.removeAllRanges();
		selection.addRange(range);
	}
}
</script>
<?php endif; ?>