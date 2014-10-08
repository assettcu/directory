<?php
/**
 * Flashes Class, uses the Yii framework's "user flash" to send messages across pages.
 * 
 * This simplifies the flash messaging by just rendering any messages in the flash queue. The queue
 * is a part of the Yii framework. It uses the Jquery UI CSS classes to display the messages. Further
 * CSS changes can be made in css/main.css using the class ".flash".
 * 
 * @author      Ryan Carney-Mogan
 * @category    Core_Classes
 * @version     1.0.1
 * @copyright   Copyright (c) 2013 University of Colorado Boulder (http://colorado.edu)
 * 
 */
class Flashes {
	
    public $buffer = "";
    
    /*
     * Constructor sets up the local flashes variable
     */
	public function __construct() 
	{
		$this->flashes = Yii::app()->user->getFlashes();
	}
	
    /**
     * Render
     * 
     * Renders the HTML output for each of the flash messages.
     * 
     */
	public function render() 
	{
		ob_start();
		$flashes = $this->flashes;
		if(!empty($flashes)) {
			foreach($flashes as $key=>$message) {
				$icon = $this->get_icon($key);
				switch($key) {
					case "success": echo '<div class="ui-state-highlight ui-corner-all flash">'.$icon.$message.'</div>'; break;
					case "error": echo '<div class="ui-state-error ui-corner-all flash">'.$icon.$message.'</div>'; break;
					default: echo '<div class="ui-state-highlight ui-corner-all flash">'.$icon.$message.'</div>'; break;
				}
				
			}
		}
		$this->buffer .= ob_get_contents();
		ob_end_clean();
		
		echo $this->buffer;
	}
	
    /**
     * Get Icon
     * 
     * Returns the HTML for the icon that depends on the status of the message ("error","success","warning").
     * 
     * @param   (string)    $type   Type of message icon needed ("error","success","warning")
     * @return  (string)
     */
	public function get_icon($type)
	{
		$icon = "<div class='message-icon'>";
		switch($type) {
			case "success": 	$icon .= StdLib::load_image("check-64","16px","16px"); 			break;
			case "error": 		$icon .= StdLib::load_image("attention","16px","16px"); 		break;
			default: 			$icon .= StdLib::load_image("flag_mark_blue","16px","16px"); 	break;
		}
		if($icon!="") {
			$icon .= "</div>";
		}
		
		return $icon;
	}
	
	public static function create_flash($type,$messages="")
    {
        if(empty($messages)) {
            return;
        }
        if(is_string($messages)) {
           Yii::app()->user->setFlash($type,$messages);
        }
        else if(is_array($messages)) {
            foreach($messages as $key=>$message) {
                if(is_array($message)) {
                    $message = implode("<br/>",$message);
                }
                $message = (StdLib::is_programmer()) ? $key.": ".$message : $message;
                Yii::app()->user->setFlash($type,$message);
            }
        }
    }
}
