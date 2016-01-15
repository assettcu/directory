@extends('layouts.master')

@section('content')
    <div class="content">
        <div class="row top-bottom-padding-3">
            <div class="col-md-6 col-md-offset-3">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Directory Login</h3>
                    </div>
                    <div class="panel-body">
                        <form method="post">
                            <div id="credentialsBody" class="col-md-8 col-md-offset-2">
                                <div class="form-group">
                                    <label for="identikeyInput">Identikey</label>
                                    <input type="text" class="form-control" name="username" id="username" placeholder="Identikey">
                                </div>
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input type="password" class="form-control" name="password" id="password" placeholder="Password">
                                </div>
                                <button id="loadMailboxesButton" class="btn-block btn btn-primary">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop