<?php
/**
 * Image Object
 * 
 * This class pulls rows from the Image table. It correlates images with their properties. It also logs who
 * uploaded the images as well as when they were uploaded and their order.
 * 
 * @author      Ryan Carney-Mogan
 * @category    Core_Classes
 * @version     1.0.1
 * @copyright   Copyright (c) 2013 University of Colorado Boulder (http://colorado.edu)
 * 
 * @database    cuproperty
 * @table       images
 * @schema      
 * 		imageid				(int 255)			Image Identifying number (PK, Not Null, Auto-increments)
 *      propertyid			(int 255)			Property ID associated with image (Not Null)
 * 		location			(varchar 255)		Location of the file (Not Null)
 * 		sorder				(int 100)			Order of the image according to other images of the same property (Not Null)
 * 		date_uploaded		(datetime)			Date and Time of image upload (Not Null)
 * 
 */
 
class ImageObj extends FactoryObj
{
    
    public function __construct($imageid=null)
    {
        parent::__construct("imageid","images",$imageid);
    }
    
	/*
	 * Pre-Delete
	 * 
	 * Overloaded function to call before deletion. Should delete the image file itself before
	 * removing the row from the table in the database.
	 */
    public function pre_delete()
    {
        if($this->loaded and is_file(getcwd()."/".$this->location)) {
            # unlink(getcwd()."/".$this->location);
        }
    }
}
