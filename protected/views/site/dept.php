<?php
if(!is_numeric($_REQUEST["deptid"]))
{
  header("Location: ".Yii::app()->createUrl('index')."?msg=".$_REQUEST["deptid"]);
  exit;
}
$department = new DepartmentObj($_REQUEST["deptid"]);
$positions = $department->dept_positions;
?>

<style>
  table tr:hover {
    background-color:#def;
  }
</style>
<div class="success ui-state-highlight <?=(isset($_REQUEST["success"]) and $_REQUEST["success"]==1)?"":"hide";?>" style="padding:5px;">
  Successfully saved position!
</div>
<div class="buttons">
  <a class="right custom-button mini" href="#" id="delete_department">
    <em>Delete Department</em>
    <span></span>
  </a>
  <a class="right custom-button mini" href="#" id="add_position">
    <em>Add Position</em>
    <span></span>
  </a>
</div>
<h1><?=$department->deptname;?></h1>

<div style="font-size:15px;font-weight:bold;border-bottom:1px solid #eff;margin-bottom:4px;">Positions</div>
<div class="row" id="nav-bottom">
  <div class="select">
    <label>Select:</label>
    <a class="action" href="#" id="select_all_button" title="Select all the positions on this page">These</a>
    <a class="action" href="#" id="select_none_button" title="Clear the selection">None</a>
  </div>
  <div class="bulk disabled_bulk">
    <label>Actions:</label>
    <a class="action" href="#" id="bulk_delete" title="Remove position from department">Delete</a>
  </div>
</div>
<br class="clear" />
<div>
  <table style="width:500px;">
  <?php $count=0; foreach($positions as $pos): $count++; ?>
  <tr class="<?=($count%2==0)?"odd":"even";?>">
    <td style="width:25px;"><input type="checkbox" value="<?=$pos;?>" /></td>
    <td class="posname"><?=$pos;?></td>
    <td><a href="#" class="edit_position">edit</a></td>
  </tr>
  <?php endforeach; ?>
  </table>
</div>

<div id="add_position_dialog" title="Add Position to department <?=$department->deptname;?>" style="font-size:13px;">
  <div class="error hide"></div>
  <label>Position Name</label><br/>
  <input type="text" name="positionname" id="positionname" style="width:300px;" />
</div>

<div id="edit_position_dialog" title="Edit Position" style="font-size:13px;">
  <div class="error hide"></div>
  <label>Position Name</label><br/>
  <input type="hidden" name="oldname" id="oldname" />
  <input type="text" name="edit_positionname" id="edit_positionname" style="width:300px;" />
</div>

<div id="delete_department_dialog" title="Delete <?=$department->deptname;?>" style="font-size:13px;">
  Are you sure you wish to delete department <span class="deptname" style="font-weight:bold;color:#09f;"><?=$department->deptname;?></span>?
</div>

<script>
jQuery(document).ready(function($){
  $("#delete_department").click(function(){
    $("#delete_department_dialog").dialog("open");
  });
  
  $("#add_position").click(function(){
    $("#add_position_dialog").dialog("open");
  });
  
  $(".edit_position").click(function(){
    var posname = $(this).parent().parent().find("td.posname").text();
    $("#edit_positionname").val(posname);
    $("#oldname").val(posname);
    $("#edit_position_dialog").dialog("open");
    return false;
  });
  
  $("#add_position_dialog").dialog({
    "autoOpen":   false,
    "modal":      true,
    "width":      400,
    "height":     180,
    "resizable":  false,
    "buttons":    {
      "Cancel":   function(){
        $("#add_position_dialog").dialog("close");
        return false;
      },
      "Add Position": function(){
        if($("#positionname").val()=="")
        {
          $(".error").html("Position name cannot be empty.").show('blind');
          return false;
        }
        $.ajax({
          "url":      "<?=Yii::app()->createUrl('_add_position_to_department');?>",
          "data":     "posname="+escape($("#positionname").val())+"&deptid=<?=$department->deptid;?>",
          "success":  function(data){
            if(data!=1)
            {
              $(".error").html(data).show('blind');
              return false;
            } else {
              window.location = "<?=Yii::app()->createUrl('dept');?>?deptid=<?=$department->deptid;?>&success=1";
              $("#add_position_dialog").dialog("close");
              return false;
            }
          }
        });
        return false;
      }
    }
  });
  
  $("#edit_position_dialog").dialog({
    "autoOpen":   false,
    "modal":      true,
    "width":      400,
    "height":     180,
    "resizable":  false,
    "buttons":    {
      "Cancel":   function(){
        $("#edit_position_dialog").dialog("close");
        return false;
      },
      "Save Position": function(){
        if($("#edit_positionname").val()=="")
        {
          $(".error").html("Position name cannot be empty.").show('blind');
          return false;
        }
        $.ajax({
          "url":      "<?=Yii::app()->createUrl('_edit_position_to_department');?>",
          "data":     "oldname="+escape($("#oldname").val())+"&posname="+escape($("#edit_positionname").val())+"&deptid=<?=$department->deptid;?>",
          "success":  function(data){
            if(data!=1)
            {
              $(".error").html(data).show('blind');
              return false;
            } else {
              window.location = "<?=Yii::app()->createUrl('dept');?>?deptid=<?=$department->deptid;?>&success=1";
              $("#edit_position_dialog").dialog("close");
              return false;
            }
          }
        });
        return false;
      }
    }
  });
  
	$("#select_all_button").click(function(){
		$("input:checkbox").attr("checked","checked");
		$("#nav-bottom .bulk.disabled_bulk").removeClass("disabled_bulk");
		return false;
	});
	$("#select_none_button").click(function(){
		$("input:checkbox").removeAttr("checked");
		$("#nav-bottom .bulk").addClass("disabled_bulk");
		return false;
	});
  
  $("input:checkbox").change(function(){
    if($("input:checkbox").is(":checked"))
    {
      $("#nav-bottom .bulk.disabled_bulk").removeClass("disabled_bulk");
    } else {
  		$("#nav-bottom .bulk").addClass("disabled_bulk");
    }
  });
  
	
	$("#bulk_delete").click(function(){
		var positions = "";
		$.each($("input:checkbox:checked"),function(index,value){
			positions = positions + $(value).val() + "|||";
		});
		positions = positions.substring(0,positions.length-3);
		$.ajax({
			"url":		"<?=Yii::app()->createUrl('_bulk_delete_positions');?>",
			"data":		"deptid=<?=$department->deptid;?>&positions="+positions,
			"success":	function(data)
			{
        //window.location.reload();
			}
		});
		return false;
	});
  
  $("#delete_department_dialog").dialog({
    "autoOpen":   false,
    "modal":      true,
    "width":      400,
    "height":     130,
    "resizable":  false,
    "buttons":    {
      "Cancel":   function(){
        $("#delete_department_dialog").dialog("close");
        return false;
      },
      "Delete Department": function(){
        $.ajax({
          "url":      "<?=Yii::app()->createUrl('_delete_department');?>",
          "data":     "deptid=<?=$department->deptid;?>",
          "success":  function(data){
            $("#delete_department_dialog").dialog("close");
            window.location = "<?=Yii::app()->baseUrl;?>";
            return false;
          }
        });
      }
    }
  });
});
</script>