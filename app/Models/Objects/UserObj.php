<?php namespace app\Models\Objects;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use app\Models\System\FactoryObj;

class UserObj extends FactoryObj implements AuthenticatableContract {

    use Authenticatable;

    public function __construct($id=null)
    {
        parent::__construct("id","users",$id);
    }

}