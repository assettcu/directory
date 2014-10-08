<?php

class Slack
{
    # Tokens are the hooks tokens to post to the various Slack chats
    public static $tokens = array(
        "#general"  => "L1WCITk5A7NvFkDhQjbpWPGo",      # General Chat Token
        "#awesome"  => "eRBVhwyxQALiY7IMR1lGqlMv"       # Server stuff
    );
    
    public static $botname = "Places Bot";
    public static $icon_emoji = "";
    
    /**
     * Send Message
     * 
     * Send a message to the board.
     * 
     * @param string $message Sends this message to the board.
     * @param string $board The board to send the message to.
     */
    public static function send_msg($message,$board)
    {
        $token = @Slack::$tokens[$board];
        if($token == "") {
            return false;
        }
        
        # Trying something with Slack API
        return StdLib::post(
            "https://assett.slack.com/services/hooks/incoming-webhook?token=".$token,
            array(
                "payload" => array(
                    "text"          => $message,
                    "username"      => Slack::$botname,
                ),
            )
        );
    }
    
}
