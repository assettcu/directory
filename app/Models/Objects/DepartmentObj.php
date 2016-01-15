<?php
/**
 * Created by PhpStorm.
 * User: carneymo
 * Date: 5/21/2015
 * Time: 11:14 AM
 */

namespace app\Models\Objects;


class DepartmentObj extends FactoryObj {

    public function __construct($deptid=null) {
        parent::__construct("deptid","departments",$deptid);
    }

}