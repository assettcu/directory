<?php namespace App\Http\Controllers;

use App\Http\Requests;
use DB;
use App\Models\System\StdLib;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class TagsController extends Controller {

    public function __construct() {

    }

    public function index() {
        $masterlist = $this->load_tag_metadata();
        $list = [];
        foreach($masterlist as $tag => $count) {
            $first = strtoupper(substr($tag, 0, 1));
            if (is_numeric($first)) {
                $list["0-9"][$tag] = $count;
            } else {
                $list[$first][$tag] = $count;
            }
        }

        return view('tags')->with(['masterlist'=>$list]);
    }

    # Not implemented on UI yet
    public function rebuild() {
        $this->load_tag_metadata();
    }

    private function load_tag_metadata() {
        # Return if all the tags have been updated recently
        if(DB::table('tags')
        ->where('date_updated','>',date("Y-m-d H:i:s",strtotime("-1 week")))
        ->count() > 0) {
            return DB::table('tags')
                ->orderBy('tag','asc')
                ->lists('count','tag');
        }

        $results = DB::table('interactions')
            ->lists('tags');
        $masterlist = [];
        foreach($results as $row) {
            $tags = explode(",",$row);
            foreach($tags as $index=>$tag) {
                if(substr($tag,0,1) == " ") {
                    $tag = substr($tag,1);
                }
                $tags[$index] = $tag;
            }
            $masterlist = array_merge($masterlist, $tags);
        }
        $masterlist = array_unique($masterlist);
        foreach($masterlist as $tag) {
            if($tag == "" or $tag === false or empty($tag)) {
                continue;
            }
            $count = DB::table('interactions')
                ->where('tags','LIKE','%'.$tag.'%')
                ->count();
            if(DB::table('tags')
                ->where('tag','=',$tag)
                ->count() > 0) {
                DB::table('tags')
                    ->where('tag','=',$tag)
                    ->update(
                        ['count' => $count, 'date_updated' => date('Y-m-d H:i:s')]
                    );
            } else {
                DB::table('tags')->insert(
                    ['tag' => $tag, 'count' => $count, 'date_updated' => date("Y-m-d H:i:s")]
                );
            }
        }

        $list = DB::table('tags')
            ->orderBy('tag','asc')
            ->lists('count','tag');

        return $list;
    }

}
