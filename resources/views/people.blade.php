@extends('layouts.master')

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>

<link rel="stylesheet" href="{{ URL::asset('public/js/jquery/modules/datatables/css/jquery.datatables.min.css') }}">
<script type="text/javascript" src="{{ URL::asset('public/js/jquery/modules/datatables/jquery.datatables.min.js') }}"></script>
<script type="text/javascript">
    jQuery(document).ready(function($){
        $('#people-table').DataTable({
            "pagingType": "full_numbers",
            "ajax": "{{ url('ajax/people_table') }}",
            "processing": true,
            "serverSide": true,
            "stateSave": true,
            "searchDelay": 500,

            "stateSaveCallback": function (settings, data) {
                // Send an Ajax request to the server with the state object
                $.ajax({
                    "url": "{{ url('ajax/people_table_save_state') }}",
                    "data": data
                });
            },
            "stateLoadCallback": function (settings) {
                var o;
                // Send an Ajax request to the server with the state object
                $.ajax({
                    "url": "{{ url('ajax/people_table_load_state') }}",
                    "async": false,
                    "dataType": "json",
                    "success" : function(json) {
                        o = json;
                    }
                });

                return o;
            }
        });
    });
</script>


@section('content')
    <ul class="breadcrumb">
        <li><a href="/">Home</a></li>
        <li class="active">People</li>
    </ul>
    <table id="people-table" class="table table-striped table-hover ">
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Email</th>
                <th>Title</th>
                <th>Department</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
@stop