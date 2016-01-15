<!DOCTYPE html>
<html lang="en">
	<head>
		<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
		<link href="{!! URL::asset('public/css/bootstrap.min.css') !!}" rel="stylesheet">
        <link href="{!! URL::asset('public/css/main.css') !!}" rel="stylesheet">

        <title>Directory</title>
	</head>
    <body>
        <header>
            <div class="container">
                <div id="banner"></div>
                <nav class="navbar navbar-default">
                    <div class="container-fluid">
                        <!-- Brand and toggle get grouped for better mobile display -->
                        <div class="navbar-header">
                            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                                <span class="sr-only">Toggle navigation</span>
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>
                            </button>
                            <a class="navbar-brand" href="{{ $app->make('url')->to('/') }}">
                                <img src="{{ URL::asset('public/images/Users-icon.png') }}" width="35px" style="display:inline-block;margin-top:-8px;" /> Directory
                            </a>
                        </div>

                        <!-- Collect the nav links, forms, and other content for toggling -->
                        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                            <ul class="nav navbar-nav">
                                <li><a href="{{ $app->make('url')->to('/') }}">Home <span class="sr-only">(current)</span></a></li>
                                <li><a href="{{ url('reports') }}">Reports</a></li>
                                <li><a href="{{ url('tags') }}">Tags</a></li>
                                <li><a href="{{ url('people') }}">People</a></li>
                                <li><a href="#">Departments</a></li>
                            </ul>
                            <form class="navbar-form navbar-right" role="search">
                                <div class="form-group">
                                    <input type="text" class="form-control" placeholder="Search Interactions, People, or Tags" style="width:400px;">
                                </div>
                                <button type="submit" class="btn btn-default">Submit</button>
                            </form>
                        </div><!-- /.navbar-collapse -->
                    </div><!-- /.container-fluid -->
                </nav>
            </div>
        </header>
        <div class="container">
            @if (!Request::is("auth/login"))
                @if (Auth::check())
                    <div class="text-right" style="margin-top:-7px;margin-bottom:15px;">Welcome, {{ Auth::user()->name }}! (<a href="logout">logout</a>)</div>
                @else
                    <div class="text-right">Hello, Guest. <a href="auth/login">login</a></div>
                @endif
            @endif
            @include('partials.flash')
            @yield('content')
        </div>

        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
    </body>
</html>