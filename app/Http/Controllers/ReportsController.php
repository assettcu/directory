<?php namespace App\Http\Controllers;

use App\Models\System\StdLib;
use DB;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class ReportsController extends Controller {

    public function index() {
        return view('reports');
    }

    public function report($reportid)
    {
        $results = DB::table('interactions')
            ->where("meetingdate", ">", date("Y-m-d H:i:s", strtotime("-1 year")))
            ->orderBy("meetingdate", "DESC")
            ->lists("interactionid");
        $departments = DB::table('departments')
            ->orderBy("deptname")
            ->select('deptname','icount','icount_updated')
            ->get();
        foreach ($departments as $index => $department) {
            if(strtotime($department->icount_updated) < strtotime("-1 days")) {
                $people = DB::select("
                    SELECT      cid
                    FROM        contact_depts
                    WHERE       contact_depts.department = ?;
                ", [$department->deptname]);
                $interaction_count = 0;
                foreach ($people as $person) {
                    $dbcount = DB::select("
                        SELECT      COUNT(*) as counter
                        FROM        interaction_attendees
                        WHERE       cid = ?
                    ", [$person->cid]);
                    $interaction_count += $dbcount[0]->counter;
                }

                # Update the department's icount values
                DB::table('departments')
                    ->where('deptname',$department->deptname)
                    ->update(['icount'=>$interaction_count,'icount_updated'=>date("Y-m-d H:i:s")]);

                $departments[$index] = ["count"=>$interaction_count, "name"=>$department->deptname];
            }
            else {
                $departments[$index] = ["count"=>$department->icount,"name"=>$department->deptname];
            }
        }
        return view('report')->with(["results" => $results, "departments" => $departments]);
    }

}
