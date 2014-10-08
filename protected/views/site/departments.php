<?php
$directory = new DirectoryObj();
$departments = $directory->load_all_departments();

$flashes = new Flashes;
$flashes->render();
?>
<style>
ul {
  list-style: none;
}
ul li {
  font-size:13px;
  padding-bottom:5px;
}
div.menu {
    margin-bottom:25px;
}
</style>
<div class="menu">
    <a href="<?php echo Yii::app()->createUrl('newdepartment'); ?>">
        <span class="flash"><?php echo StdLib::load_image("plus","16px"); ?></span> Add New Department
    </a>
</div>

<ul>
<?php foreach($departments as $dept): ?>
  <li><a href="<?=Yii::app()->createUrl('dept');?>?deptid=<?=$dept->deptid;?>"><?=$dept->deptname;?></a></li>
<?php endforeach; ?>
</ul>