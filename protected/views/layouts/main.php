<?php
// Theme name from Jquery UI themes
$theme = "bluebird";
if(!Yii::app()->user->isGuest) {
    $COREUSER = new UserObj(Yii::app()->user->name);
    $COREUSER->get_contact();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="language" content="en" />
	
	<link rel="icon" type="image/png" href="<?=Yii::app()->request->baseUrl;?>/images/person.png" />
		
	<!-- blueprint CSS framework -->
	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/screen.css" media="screen, projection" />
	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/print.css" media="print" />
	<!--[if lt IE 8]>
	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/ie.css" media="screen, projection" />
	<![endif]-->

	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/main.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/table.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/form.css" />

	<title><?php echo CHtml::encode($this->pageTitle); ?></title>
	
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.0/jquery.min.js" type="text/javascript"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js" type="text/javascript"></script>

	<script src="<?php echo WEB_LIBRARY_PATH; ?>jquery/modules/jqplot/jquery.jqplot.js" type="text/javascript"></script>
	<script src="<?php echo WEB_LIBRARY_PATH; ?>jquery/modules/jqplot/plugins/jqplot.pieRenderer.js" type="text/javascript"></script>
	<script src="<?php echo WEB_LIBRARY_PATH; ?>jquery/modules/jqplot/plugins/jqplot.donutRenderer.js" type="text/javascript"></script>
	<script src="<?php echo WEB_LIBRARY_PATH; ?>jquery/modules/jqplot/plugins/jqplot.dateAxisRenderer.js" type="text/javascript"></script>
	<script src="<?php echo WEB_LIBRARY_PATH; ?>jquery/modules/jqplot/plugins/jqplot.categoryAxisRenderer.min.js" type="text/javascript"></script>
	<script src="<?php echo WEB_LIBRARY_PATH; ?>jquery/modules/jqplot/plugins/jqplot.pointLabels.min.js" type="text/javascript"></script>
	
	<script src="<?php echo WEB_LIBRARY_PATH; ?>jquery/modules/cookie/jquery.cookie.js" type="text/javascript"></script>
	<script src="<?php echo WEB_LIBRARY_PATH; ?>jquery/modules/tokeninput/src/jquery.tokeninput.js" type="text/javascript"></script>
	<link rel="stylesheet" href="<?php echo WEB_LIBRARY_PATH; ?>jquery/modules/tokeninput/styles/token-input-facebook.css" type="text/css" />
	<link rel="stylesheet" href="<?php echo WEB_LIBRARY_PATH; ?>jquery/modules/jqplot/jquery.jqplot.min.css" type="text/css" />
	<link rel="stylesheet" href="<?php echo WEB_LIBRARY_PATH; ?>jquery/themes/<?=$theme?>/jquery-ui.css" type="text/css" />

	<script>
	// Button Script for all buttons
	jQuery(document).ready(function($){
		$("button").button();
		// This gets all the messages for this user
	});
	</script>
</head>

<body>

<div class="container" id="page">

	<div id="header">
		<div id="logo" style="position:relative;">
			<div id="logo-image" style="position:absolute;top:5px;left:15px;">
				<?=StdLib::load_image('person.png',"48px","48px");?>
			</div>
			<div id="logo-text">
				<?php echo CHtml::encode(Yii::app()->name); ?>
			</div>
			<div id="mainmenu">
				<?php if(Yii::app()->user->isGuest): ?>
				<a href="<?=Yii::app()->createUrl('login')?>">Login</a>
				<?php else: ?>
				<a href="<?=Yii::app()->createUrl('logout')?>">Logout (<?=Yii::app()->user->name?>)</a>
				<a href="<?=Yii::app()->createUrl('contact');?>?cid=<?php echo @$COREUSER->contact->cid; ?>">My Profile</a>
				<a href="<?=Yii::app()->createUrl('departments')?>">Departments</a>
				<?php endif; ?>
				<a href="<?=Yii::app()->baseUrl;?>">Home</a>
			</div>
		</div>

	</div><!-- header -->
	
	<?php echo $content; ?>

	<div class="clear"></div>

    <div id="footer">
        <a id="assettlogo" href="http://assett.colorado.edu"></a>
        <div class="info">
            Copyright &copy; <?php echo date('Y'); ?> by the University of Colorado Boulder.<br/>
            Developed by the <a href="http://assett.colorado.edu">ASSETT program</a><br/>
        </div>
    </div><!-- footer -->

</div><!-- page -->

</body>
</html>
