<?php namespace App\Http\Controllers;

use Auth;
use DB;
use App\Models\Objects\ContactObj;
use App\Models\System\StdLib;
use App\Models\Objects\InteractionObj;

class WelcomeController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| Welcome Controller
	|--------------------------------------------------------------------------
	|
	| This controller renders the "marketing page" for the application and
	| is configured to only allow guests. Like most of the other sample
	| controllers, you are free to modify or remove it as you desire.
	|
	*/

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
        $this->makeSSL();
	}

	/**
	 * Show the application welcome screen to the user.
	 *
	 * @return Response
	 */
	public function index()
	{
        if(Auth::check()) {
            $contactobj = ContactObj::find_contact_by_username(Auth::user()->id);
            $AuthContact = $contactobj;

            $results = DB::table('interaction_attendees')
                ->select('interaction_attendees.interactionid AS ID')
                ->join('interactions','interactions.interactionid','=','interaction_attendees.interactionid')
                ->where('cid','=',$AuthContact->cid)
                ->groupBy('interaction_attendees.interactionid')
                ->orderBy('interactions.meetingdate','desc')
                ->lists("ID");

            $info = [
                "fullname"      => $AuthContact->fullname,
                "positions"     => $AuthContact->get_renderable("positions"),
                "departments"   => $AuthContact->get_renderable("departments"),
                "email"         => $AuthContact->email,
                "phone"         => $AuthContact->phone,
                "username"      => $AuthContact->username,
                "shortbio"      => $AuthContact->shortbio
            ];

            $interactions = [];
            foreach($results as $id) {
                $interactions[] = new InteractionObj($id);
            }
            $info["interactions"] = $interactions;

            return view('welcome')->with(["info"=>$info,"AuthContact"=>$AuthContact]);
        }
	}

    private function makeSSL()
    {
        if(!isset($_SERVER["HTTPS"]) or $_SERVER["HTTPS"] != "on")
        {
            header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
            exit();
        }
    }

}
