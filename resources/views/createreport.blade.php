@extends('layouts.master')

@section('content')
    <h1>Create Report</h1>
    <div class="input-group col-lg-6 vertical-spacing-large">
        <input type="text" class="form-control" placeholder="Report Name..." />
        <span class="input-group-btn">
          <button class="btn btn-default" type="button">
              <span class="glyphicon glyphicon-arrow-right"> </span>
          </button>
        </span>
    </div>
@stop