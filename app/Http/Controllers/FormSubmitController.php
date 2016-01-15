<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class FormSubmitController extends Controller {

    public function SaveInteraction()
    {
        var_dump($_REQUEST); die();
    }

}
