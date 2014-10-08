<?php

$flashes = new Flashes;
$flashes->render();
?>

<style>
    
table#post-form-table tr th {
    vertical-align: top;
}
table#post-form-table tr th div {
    padding:5px;
    border:2px solid #ccc;
    background-color:#f0f0f0;
}
table#post-form-table tr td {
    padding-left:15px;
}
table#post-form-table tr td textarea {
    font-family: Verdana, Geneva, sans-serif;
}
table#post-form-table tr th div.highlight {
    border:2px solid #ffcc33;
    color:#ffcc33;
    background-color:#fffcf0;
    font-weight:bold;
}
table#post-form-table tr th div.error {
    border:2px solid #CC0000;
    background-color:#fff0f0;
    color:#CC0000;
}

input.longtext {
    width: 300px;
}
input.shorttext {
    width: 200px;
}
input.abbr {
    width: 75px;
}
input.reallylongtext {
    width:400px;
}
</style>

<h1>Add New Department</h1>


<?php 
    $colleges = Functions::get_colleges();
?>
<form method="post">
    <table id="post-form-table">
        <tr>
            <th><div>Department/Program Name</div></th>
            <td>
                <input type="text" name="deptname" class="reallylongtext" value="<?php echo @$_REQUEST["deptname"]; ?>" />
            </td>
        </tr>
        <tr>
            <th><div>Department/Program Short Name</div></th>
            <td>
                <input type="text" name="shortname" class="longtext" value="<?php echo @$_REQUEST["shortname"]; ?>" />
            </td>
        </tr>
        <tr>
            <th><div>Department/Program Abbreviation</div></th>
            <td>
                <input type="text" name="abbr" class="abbr" value="<?php echo @$_REQUEST["abbr"]; ?>" />
            </td>
        </tr>
        <tr>
            <th><div>Belongs to College</div></th>
            <td>
                <select name="college">
                    <?php foreach($colleges as $college): ?>
                        <option value="<?php echo $college->collegeid; ?>" <?php if(@$_REQUEST["college"]==$college->collegeid) echo "selected='selected'"; ?>><?php echo $college->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><div>Website</div></th>
            <td>
                <input type="text" name="website" class="reallylongtext" value="<?php echo @$_REQUEST["website"]; ?>" />
            </td>
        </tr>
        <tr>
            <td class="lalign">
                <div style="margin-top:25px;">
                    <button id="cancel">Cancel</button> 
                    <button id="save">Save Department</button>
                </div>
            </td>
            <td></td>
        </tr>
    </table>
</form>

<script>
jQuery(document).ready(function($){
   $("#cancel").click(function(){
       window.location = "<?php echo Yii::app()->createUrl('departments'); ?>";
       return false;
   });
});
</script>