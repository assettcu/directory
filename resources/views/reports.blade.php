@extends('layouts.master')

@section('content')
    <ul class="breadcrumb">
        <li><a href="/">Home</a></li>
        <li class="active">Reports</li>
    </ul>
    <div class="col-lg-6">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">My Reports</h3>
            </div>
            <div class="panel-body">
                <div class="list-group">
                    <a href="{{  url('report/1') }}" class="list-group-item">
                        <h4 class="list-group-item-heading">Interactions Report for Summer 2015</h4>
                        <p class="list-group-item-text">This report will show all the departments we've interacted with since the beginning of the year.</p>
                    </a>
                    <a href="#" class="list-group-item">
                        <h4 class="list-group-item-heading">Test Report 2</h4>
                        <p class="list-group-item-text">This report will show all the contacts we've interacted with since the beginning of the year.</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
@stop