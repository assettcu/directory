@if(Session::has('flash_message'))
    @foreach(session('flash_message') as $key => $value)
        <div class="alert alert-{{ $key }} alert-dismissable" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            {{ $value }}
        </div>
    @endforeach
@endif