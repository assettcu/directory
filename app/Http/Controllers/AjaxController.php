<?php namespace App\Http\Controllers;

use DB;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\System\StdLib;

use Illuminate\Http\Request;

class AjaxController extends Controller {

    /*
     * List of all the contacts matching the names.
     * @return array
     */
    public function SearchNames()
    {
        $name = $_REQUEST["q"];
        $results = DB::table('contacts')
            ->where('fullname', 'like', '%'.$name.'%')
            ->limit(10)
            ->get();
        $output = [];
        foreach($results as $row){
            $output[] = ["id" => "".$row->cid."", "name" => $row->fullname];
        }
        return json_encode($output);
    }

    public function PeopleTable()
    {
        if(isset($_GET["order"][0]["column"])) {
            switch ($_GET["order"][0]["column"]) {
                case 0:
                    $column = "lastname";
                    break;
                case 1:
                    $column = "email";
                    break;
                case 2:
                    $column = "title";
                    break;
                case 3:
                    $column = "department";
                    break;
                default:
                    $column = "lastname";
                    break;
            }
            $order = $_GET["order"][0]["dir"];
        }
        else {
            $column = "lastname";
            $order = "asc";
        }

        $query = DB::table('contacts')
            ->orderBy($column,$order)
            ->orderBy("lastname","asc");

        if(isset($_GET["search"]["value"]) and !empty($_GET["search"]["value"])) {
            $term = "%".str_replace(" ","%",$_GET["search"]["value"])."%";
            $query->where("lastname","LIKE",$term)
                ->orWhere("firstname","LIKE",$term)
                ->orWhere("email","LIKE",$term)
                ->orWhere("department","LIKE",$term)
                ->orWhere("title","LIKE",$term);
        }

        # Query for the information
        $filteredCount = count($query->get());
        $results = $query->skip($_GET["start"])
            ->limit($_GET["length"])
            ->get();

        $totalcount = DB::table('contacts')
            ->count();

        $return = array();
        foreach($results as $index=>$contact) {
            $return[] = [
                "<a href='people/".$results[$index]->cid."'>".$results[$index]->fullname."</a>",
                "<a href='mailto:".$results[$index]->email."'>".$results[$index]->email."</a>",
                $results[$index]->title,
                $results[$index]->department
            ];
        }

        (int)$data["draw"] = isset($_GET["draw"]) ? $_GET["draw"] : 0;
        $data["recordsTotal"] = (int)$totalcount;
        $data["recordsFiltered"] = (integer)$filteredCount;
        $data["data"] = $return;


        return $data;
    }

    public function PeopleTableSaveState() {
        session_start();
        $_SESSION["People_Table_Save_State"] = $_GET;
    }

    public function PeopleTableLoadState() {
        session_start();
        return (isset($_SESSION["People_Table_Save_State"])) ? json_encode($_SESSION["People_Table_Save_State"]) : null;
    }
}
