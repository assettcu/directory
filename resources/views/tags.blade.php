@extends('layouts.master')

@section('content')
    <ul class="breadcrumb">
        <li><a href="/">Home</a></li>
        <li class="active">Tags</li>
    </ul>
    <?php foreach($masterlist as $title => $taglist): ?>
    <?php
        $sublist = array_chunk($taglist,ceil(count($taglist)/3),true);
    ?>
    <div class="col-lg-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title"><?php echo $title; ?></h3>
            </div>
            <div class="panel-body">
                <?php for($a=0; $a < count($sublist); $a++): ?>
                <div class="col-lg-4">
                    <div class="list-group">
                        <?php foreach($sublist[$a] as $tag => $count): ?>
                        <a href="#" class="list-group-item">
                            <span class="badge"><?php echo $count; ?></span>
                            <?php echo $tag; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
@stop