@extends('layouts.master')

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>

<script type="text/javascript" src="{{ URL::asset('public/js/jquery/modules/tokeninput/src/jquery.tokeninput.js') }}"></script>
<link rel="stylesheet" href="{{ URL::asset('public/js/jquery/modules/tokeninput/styles/token-input.css') }}" type="text/css" />
<link rel="stylesheet" href="{{ URL::asset('public/js/jquery/modules/tokeninput/styles/token-input-facebook.css') }}" type="text/css" />

<script>
    jQuery(document).ready(function($){
        $("#attendees").tokenInput("{{ url('ajax/search_names') }}", {
            theme: "facebook",
            preventDuplicates: true
        });
        $( "#meetingdate" ).datepicker( );
        $(document).on('click',"#add-me-to-interaction",function(){
            $("#attendees").tokenInput('add', {id: {{ $AuthContact->cid }}, name : "{{ $AuthContact->fullname }}" });
        });
    });
</script>

@section('content')
    <div class="col-lg-6">
        <div class="col-lg-12">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title"><span class="glyphicon glyphicon-user"></span> Profile Information</h3>
                </div>
                <div class="panel-body">
                    <div style="font-size:18px;">{{ $info["fullname"] }}</div>
                    @if (!empty($info["positions"]))
                        <div style="font-style:italic;margin-bottom:10px;">
                            {{ $info["positions"] }}
                        </div>
                    @endif
                    @if (!empty($info["departments"]))
                        <div class="vertical-spacing-medium profile-department">
                            <span class="glyphicon glyphicon-home" style="padding-right:5px;"></span>
                            {{ $info["departments"] }}
                        </div>
                    @endif
                    @if (!empty($info["email"]))
                        <div class="vertical-spacing-small profile-description">
                            <span class="glyphicon glyphicon-send" style="padding-right:5px;"></span>
                            <a href="#">{{ $info["email"] }}</a>
                        </div>
                    @endif
                    @if (!empty($info["phone"]))
                        <div class="vertical-spacing-small profile-description">
                            <span class="glyphicon glyphicon-phone" style="padding-right:5px;"></span>
                            {{ $info["phone"] }}
                        </div>
                    @endif
                    @if (!empty($info["username"]))
                        <div class="vertical-spacing-small profile-description">
                            <span class="glyphicon glyphicon-user" style="padding-right:5px;"></span>
                            {{ $info["username"] }}
                        </div>
                    @endif
                    @if (!empty($info["shortbio"]))
                        <div class="vertical-spacing-large profile-description">
                            <h4>Short Bio</h4>
                            <blockquote>{{ $info["shortbio"] }}</blockquote>
                        </div>
                    @endif
                    <div class="vertical-spacing-large profile-description">
                        <h4>Total Interactions</h4>
                        {{ $AuthContact->count_interactions() }} Interactions
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12 pull-left">
        </div>
    </div>
        <div class="col-lg-6">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"><span class="glyphicon glyphicon-plus"></span> Add an Interaction</h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="control-label" for="meetingdate">Meeting Date</label>
                        <input type="text" class="form-control" id="meetingdate" />
                    </div>
                    <div class="form-group">
                        <label class="control-label" for="attendees">Who's involved (<a href="#" id="add-me-to-interaction">me</a>)</label>
                        <input type="text" class="form-control" id="attendees" />
                    </div>
                    <div class="form-group">
                        <label class="control-label" for="category">Category</label>
                        <select class="form-control" id="category">
                            <option></option>
                            <option>Course Improvements</option>
                            <option>Relationship/Community</option>
                            <option>Teaching &amp; Learning Development</option>
                            <option>Technology Training</option>
                            <option>Web Support</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="textArea" class="control-label">Notes about Interaction</label>
                        <textarea class="form-control" rows="5" id="description"></textarea>
                        <span class="help-block">The more descriptive the notes are, the more accurate the reports that can be generated.</span>
                    </div>
                    <div class="form-group">
                        <label class="control-label" for="tags">Tags</label>
                        <input type="text" class="form-control" id="tags" />
                    </div>
                    <div class="form-group text-right">
                        <input type="submit" class="btn btn-primary" value="Save Interaction" />
                    </div>
                </div>
            </div>
        </div>
    <div class="col-lg-12">
        <div class="panel panel-success">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <span class="glyphicon glyphicon-user"></span>
                    <span class="glyphicon glyphicon-transfer"></span>
                    <span class="glyphicon glyphicon-user"></span>
                    Interactions
                </h3>
            </div>
            <div class="panel-body">
                <div class="">

                </div>
                <div class="list-group">
                    @foreach ($info["interactions"] as $interaction)
                        <div class="list-group-item">
                            <a href="#" class="pull-right">Edit Interaction</a>
                            <div class="interaction-header" style="font-size:small; color: #ff7e09;">
                                {{ date("l, M d, Y",strtotime($interaction->meetingdate)) }}
                            </div>
                            <p class="list-group-item-text">
                                <?php echo $interaction->notes; ?>
                            </p>
                            <div class="interaction-people" style="color:#5c67ff;">
                                <?php
                                    $contacts = $interaction->get_attendees_objects();
                                    $names = [];
                                    foreach($contacts as $contact) {
                                        $names[] = "<a href='{{ FacultyController@show }}'>".$contact->fullname."</a>";
                                    }
                                    echo implode(", ",$names);
                                ?>
                            </div>
                            <div class="vertical-spacing-small" style="color:#2e3436;">
                                {{ $interaction->tags }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@stop