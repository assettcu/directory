<?php
/**
 * Login page
 */
$this->pageTitle=Yii::app()->name . ' - Login';

$imager = new Imager(LOCAL_IMAGE_LIBRARY."lock.png");
$imager->width = "16px";
$imager->height = "16px";
$imager->attributes["title"] = "This password is passed through 256-bit encryption for authentication.";

$flashes = new Flashes();
$flashes->render();
?>
<style>
div.authen-container {
    margin:auto;
    width:480px;
    border:1px solid #ccc;
    padding:8px;
}
div.authen-title {
    padding:5px;
    margin-bottom:12px;
}
form#login-form div.input-container input {
    font-family: Verdana, Geneva, sans-serif;
    font-size:12px;
    padding:5px;
    letter-spacing:1px;
    margin-bottom:5px;
    width: 300px;
    margin-left:80px;
}
form#login-form div.input-container .required {
    font-weight:bold;
    color:#f00;
}
form#login-form div.input-container label {
    font-weight:bold;
    display:block;
    font-size:11px;
    margin-left:80px;
}
form#login-form div.input-container #submit {
    cursor:pointer;
}
#submit.disabled {
    background-color:#fff;
    color:#ccc;
    cursor:default;
}
form#login-form div.input-container input#submit {
    width:120px;
    margin-left:0px;
}
</style>
<h1>Login</h1>

<div class="ui-widget-content" style="padding:6px;font-size:13px;margin-bottom:10px;">Please fill out the following form with your identikey username and password:</div>

<div class="authen-container ui-widget-content ui-corner-all">
    <div class="authen-title ui-widget-header ui-corner-all">Authentication Needed</div>
    <form method="post" id="login-form">
        <div class="input-container">
            <label>CU Identikey Username <span class="required">*</span></label>
            <input type="text" name="username" />
        </div>
        <div class="input-container">
            <label>CU Identikey Password <span class="required">*</span></label>
            <input type="password" name="password" /> <?=$imager->render();?>
        </div>
        <div class="input-container calign submit">
            <input type="submit" id="submit" value="Login" />
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function(){
    $("#submit").button();
    $("#submit").click(function(){
        $(this).removeClass("ui-state-hover");
        $(this).addClass("disabled");
        $(this).prop("value","Logging in...");
        $("#login-form").submit();
        return true;
    });
});
</script>