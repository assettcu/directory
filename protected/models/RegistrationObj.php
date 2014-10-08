<?php

/**
 * Registration Class
 *
 * @version $Id$
 * @copyright 2011
 */

class RegistrationObj extends FactoryObj
{

	public function __construct($username=null)
	{
		parent::__construct("username","d_registration",$username);
	}
}

?>
