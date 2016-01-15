<?php namespace App\Http\Controllers;

use App\Http\Requests;
use DB;
use App\Models\Objects\ContactObj;
use App\Models\Objects\InteractionObj;
use App\Models\System\StdLib;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class PeopleController extends Controller {

	//

    public function __construct() {

    }

    public function index() {

        $results = DB::table('contacts')
            ->orderBy('contacts.lastname','asc')
            ->limit(10)
            ->get();

        $totalcount = DB::table('contacts')
            ->count();

        foreach($results as $index=>$contact) {
            $icount = DB::table('interaction_attendees')
                ->select(DB::raw('count(*) as icount'))
                ->where('cid', '=', $contact->cid)
                ->groupBy('interactionid')
                ->get();

            $results[$index]->count = (count($icount) > 0) ? $icount[0]->icount : 0;
        }

        return view('people')->with(['people'=>$results,'totalcount'=>$totalcount]);
    }

    public function show($id) {

        $contactobj = new ContactObj($id);

        $results = DB::table('interaction_attendees')
            ->select('interaction_attendees.interactionid AS ID')
            ->join('interactions','interactions.interactionid','=','interaction_attendees.interactionid')
            ->where('cid','=',$contactobj->cid)
            ->groupBy('interaction_attendees.interactionid')
            ->orderBy('interactions.meetingdate','desc')
            ->lists("ID");

        $info = [
            "fullname"      => $contactobj->fullname,
            "positions"     => $contactobj->get_renderable("positions"),
            "departments"   => $contactobj->get_renderable("departments"),
            "email"         => $contactobj->email,
            "phone"         => $contactobj->phone,
            "username"      => $contactobj->username,
            "shortbio"      => $contactobj->shortbio
        ];

        $interactions = [];
        foreach($results as $id) {
            $interactions[] = new InteractionObj($id);
        }
        $info["interactions"] = $interactions;

        return view('person')->with(["info"=>$info,"contact"=>$contactobj]);
    }

}
