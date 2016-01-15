@extends('layouts.master')

<?php

use App\Models\Objects;

$matchwords = ["Spring 2015 HOCD Seminar", "question"];
$columns = 2;
$chunks = array_chunk($departments, (int)((count($departments)/$columns)+1));

$assett_staff = DB::table('contact_depts')
        ->where("department","=","Arts & Sciences Support of Education Through Technology")
        ->orderBy("fullname","ASC")
        ->groupBy("fullname")
        ->get();

$staffcols = (int)ceil(((count($assett_staff))/4));
$assett_staff = array_chunk($assett_staff, $staffcols);

$column_fields = [
    "contacts"      => DB::select('SHOW COLUMNS FROM contacts'),
    "departments"   => DB::select('SHOW COLUMNS FROM contact_depts'),
    "interactions"  => DB::select('SHOW COLUMNS FROM interactions')
];
?>

@section('content')

    <div class="vertical-spacing-large">
        <div class="pull-right">
            <a href="#" class="btn btn-success btn">
                <span class="glyphicon glyphicon-export"></span> Generate Report
            </a>
            <a href="#" class="btn btn-primary btn">
                <span class="glyphicon glyphicon-floppy-disk"></span> Save Report
            </a>
        </div>
        <h1>Interactions Report for Summer 2015</h1>
    </div>
    <br/><br/>

    <style>
        .panel-heading .accordion-toggle:before {
            /* symbol for "opening" panels */
            font-family: 'Glyphicons Halflings';  /* essential for enabling glyphicon */
            content: "\e114";    /* adjust as needed, taken from bootstrap.css */
            float: right;        /* adjust as needed */
            color: grey;         /* adjust as needed */
        }
        .panel-heading .accordion-toggle.collapsed:before {
            /* symbol for "collapsed" panels */
            content: "\e080";    /* adjust as needed, taken from bootstrap.css */
        }
    </style>

    <div class="panel panel-primary">
        <div class="panel-heading">
            <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#filter-panel" style="color:rgb(102,102,102);text-decoration:none;">
                <h3 class="panel-title">
                    <span class="glyphicon glyphicon-filter"> </span> Filter Interactions
                </h3>
            </a>
        </div>
        <div id="filter-panel" class="panel-collapse collapse in">
            <div class="panel-body">
                <div class="well well-sm">
                    Search by keywords for interactions. Can search by tags, content in interactions, or by contact names.
                </div>
                <div class="input-group vertical-spacing-large">
                    <span class="input-group-addon">Filter Interactions By</span>
                    <input type="text" class="form-control">
                    <span class="input-group-btn">
                      <button class="btn btn-default" type="button">
                          <span class="glyphicon glyphicon-arrow-right"> </span>
                      </button>
                    </span>
                </div>
                <div class="vertical-spacing-huge">
                    <?php foreach($matchwords as $matchword): ?>
                    <div class="btn-group">
                        <a href="#" class="btn btn-default"><?php echo $matchword; ?></a>
                        <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <li><a href="#">Remove this keyword</a></li>
                        </ul>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-primary">
        <div class="panel-heading">
            <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#report-fields-panel" style="color:rgb(102,102,102);text-decoration:none;">
                <h3 class="panel-title">
                    <span class="glyphicon glyphicon-tasks"> </span> Report Fields
                </h3>
            </a>
        </div>
        <div id="report-fields-panel" class="panel-collapse collapse collapsed">
            <div class="panel-body">
                <div class="well well-sm">
                    Select which fields you would like to show up in the report, and how to group them.
                </div>
                <div class="col-md-12">

                </div>
                <?php foreach($column_fields as $table=>$fields): ?>
                <div class="col-md-4">
                    <h3>{{ ucfirst($table) }} Fields</h3>
                    <ul class="list-group table-field-list" style="list-style: none;">
                    <?php foreach($fields as $field): ?>
                        <a href="#" class="list-group-item">
                            <li class="list-group-item-text" style="cursor:pointer;">
                                <input type="checkbox" name="{{$table}}-fields[]" class="{{$table}}-checkbox" value="{{ $field->Field }}" />
                                {{ $field->Field }}
                            </li>
                        </a>
                    <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="panel panel-primary">
        <div class="panel-heading">
            <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#timeline-panel" style="color:rgb(102,102,102);text-decoration:none;">
                <h3 class="panel-title">
                    <span class="glyphicon glyphicon-calendar"> </span> Timeline
                </h3>
            </a>
        </div>
        <div id="timeline-panel" class="panel-collapse collapse">
            <div class="panel-body">
                <div class="well well-sm">
                    The report can include all interactions, or those set between dates.
                </div>
                <div class="form-group">
                    <div class="col-md-4">
                        <div class="input-daterange input-group" id="datepicker">
                            <input type="text" class="input-small form-control" name="date_from" />
                            <span class="input-group-addon">to</span>
                            <input type="text" class="input-small form-control" name="date_to" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-primary">
        <div class="panel-heading">
            <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#assett-staff-panel" style="color:rgb(102,102,102);text-decoration:none;">
                <h3 class="panel-title">
                    <span class="glyphicon glyphicon-transfer"> </span> Interactions from ASSETT Staff
                </h3>
            </a>
        </div>
        <div id="assett-staff-panel" class="panel-collapse collapse collapsed">
            <div class="panel-body">
                <div class="well well-sm">
                    Select which ASSETT staff you want included in the report. Grayed out names are contacts that no longer work for ASSETT.
                </div>
                <div class="col-lg-12 vertical-spacing-medium">
                    <a href="#" class="btn btn-primary btn-sm" id="staff-selectall">Select All</a>
                    <a href="#" class="btn btn-primary btn-sm" id="staff-unselectall">Unselect All</a>
                    <a href="#" class="btn btn-primary btn-sm" id="staff-selectcurrent">Select Current Only</a>
                </div>
                <div class="vertical-spacing-large">
                    <?php foreach($assett_staff as $stafflist): ?>
                    <div class="col-lg-{{(int)12/count($assett_staff)}}">
                        <ul class="list-group staff-list" style="list-style: none;">
                            <?php foreach($stafflist as $cid => $staff): ?>
                            <a href="#" class="list-group-item">
                                <li class="list-group-item-text" style="cursor:pointer;">
                                    <input type="checkbox" name="staff[]" class="staff-checkbox" value="{{ $staff->cid }}" />
                                    <?php if($staff->current == 0): ?>
                                    <span class="staff-old" style="color:#ccc;">
                                        {{ $staff->fullname }}
                                    </span>
                                    <?php else: ?>
                                    <span class="staff-current">
                                        {{ $staff->fullname }}
                                    </span>
                                    <?php endif; ?>
                                </li>
                            </a>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-primary vertical-spacing-huge">
        <div class="panel-heading">
            <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#departments-panel" style="color:rgb(102,102,102);text-decoration:none;">
                <h3 class="panel-title">
                    <span class="glyphicon glyphicon-plus-sign"> </span> Include Departments
                </h3>
            </a>
        </div>
        <div id="departments-panel" class="panel-collapse collapse collapsed">
            <div class="panel-body">
                <div class="well well-sm">
                    The numbers next to each department is the number of interactions each department has total.
                </div>
                <div class="col-lg-12 vertical-spacing-medium">
                    <div class="pull-right">
                        Departments Selected <span class="badge" id="dept-selected-count">0</span>
                    </div>
                    <a href="#" class="btn btn-primary btn-sm" id="dept-selectall">Select All</a>
                    <a href="#" class="btn btn-primary btn-sm" id="dept-unselectall">Unselect All</a>
                    <a href="#" class="btn btn-primary btn-sm" id="dept-interactions-only">Only Departments with Interactions</a><br/>
                </div>
                <?php foreach($chunks as $chunk): ?>
                    <div class="col-lg-{{(int)12/count($chunks)}}">
                        <ul class="list-group departments-list" style="list-style: none;">
                        <?php foreach($chunk as $department): ?>
                            <a href="#" class="list-group-item">
                                <li class="list-group-item-text" style="cursor:pointer;">
                                    <input type="checkbox" name="departments[]" class="department-checkbox" value="{{ $department["name"] }}" />
                                    {{ $department["name"] }}
                                    <span class="badge department-icount pull-right">{{$department["count"]}}</span>
                                </li>
                            </a>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
    <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>

    <link rel="stylesheet" href="{!! URL::asset('public/js/jquery/modules/datepicker/css/bootstrap-datepicker.css'); !!}">
    <script src="{!! URL::asset('public/js/jquery/modules/datepicker/js/bootstrap-datepicker.js'); !!}" ></script>

    <script>
        jQuery(document).ready(function($){
            $(".input-daterange").datepicker({
                startView: 1,
                todayHighlight: true,
                startDate: "01/01/2010",
                endDate: "Today",
                todayBtn: "linked"
            });
            $(document).on('click','.departments-list .list-group-item',function(){
                var checkbox = $(this).find('.department-checkbox')[0];
                checkbox.checked = !($(checkbox).is(":checked"));
                return false;
            });
            $(document).on('click','.staff-list .list-group-item',function(){
                var checkbox = $(this).find('.staff-checkbox')[0];
                checkbox.checked = !($(checkbox).is(":checked"));
                return false;
            });
            $(document).on('change','.department-checkbox',function(){
                return update_icount();
            });
            $(document).on('click','#dept-selectall',function(){
                $('.department-checkbox').each(function(){
                    this.checked = true;
                });
                return update_icount();
            });
            $(document).on('click','#dept-unselectall',function(){
                $('.department-checkbox').each(function(){
                    this.checked = false;
                });
                return update_icount();
            });
            $(document).on('click','#dept-interactions-only',function(){
                $('.departments-list').find('.department-icount').each(function(){
                    if($(this).text() != 0) {
                        $(this).parent().find('input')[0].checked = true;
                    }
                    else {
                        $(this).parent().find('input')[0].checked = false;
                    }
                });
                return update_icount();
            });

            $(document).on('click','#staff-selectall',function(){
                $('.staff-checkbox').each(function(){
                    this.checked = true;
                });
                return false;
            });
            $(document).on('click','#staff-unselectall',function(){
                $('.staff-checkbox').each(function(){
                    this.checked = false;
                });
                return false;
            });
            $(document).on('click','#staff-selectcurrent',function(){
                $('.staff-old').each(function(){
                    $(this).parent().find('input.staff-checkbox')[0].checked = false;
                });
                $('.staff-current').each(function(){
                    $(this).parent().find('input.staff-checkbox')[0].checked = true;
                });
                return false;
            });
            function update_icount() {
                $("#dept-selected-count").text($(".department-checkbox:checked").length);
                return false;
            }

            $("#from-date").datepicker();
            $("#to-date").datepicker();
        });
    </script>

    <?php if(0): ?>
    <table class="table table-striped table-hover ">
        <tbody>
        @foreach ($results as $interactionid)
            <?php
            $unmatched = true;
            $interaction = new \App\Models\Objects\InteractionObj($interactionid);
            foreach($matchwords as $matchword) {
                if(stristr($interaction->notes,$matchword)) {
                    $unmatched = false;
                    $interaction->notes = str_ireplace($matchword,"<span style='color:#09f;'>".$matchword."</span>",$interaction->notes);
                }
                if(stristr($interaction->tags,$matchword)) {
                    $unmatched = false;
                    $interaction->tags = str_ireplace($matchword,"<span style='color:#09f;'>".$matchword."</span>",$interaction->tags);
                }
            }
            if($unmatched) {
                continue;
            }
            ?>
            <tr>
                <td>
                    <div style="color:#e46909;">{{ $interaction->format_date($interaction->meetingdate, "l, F j, Y") }}</div>
                    <div class="vertical-spacing-small">
                        <span class="glyphicon glyphicon-user" style="margin-right:10px;"> </span> {{ $interaction->get_attendees_list() }}
                    </div>
                    <div class="vertical-spacing-small">
                        <span class="glyphicon glyphicon-tags" style="margin-right:10px;"> </span>
                        <span style="color:#959595;font-style:italic;">{!! $interaction->tags !!}</span>
                    </div>
                    <div class="vertical-spacing-small">
                        <span class="glyphicon glyphicon-comment" style="margin-right:10px;"> </span>
                        {!! $interaction->notes !!}...
                    </div>
                    <div class="pull-right">
                        [ <a href="#"><span class="glyphicon glyphicon-edit"> </span> edit</a> ]
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <?php endif; ?>
@stop