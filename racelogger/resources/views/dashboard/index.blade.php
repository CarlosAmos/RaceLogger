@extends('layouts.app')

@section('content')

<style>
    .container {
        margin-right: 0 !important;
        margin-left: 0 !important;
    }

    .calender_race {
        display: flex;
        flex-direction: row;
        align-items: center;
        margin: 4px 0;
        padding: 0 10px;
        font-weight: 600;
        color: black;
    }

    .calender_race.wec {
        border-radius: 20px;
        border: 2px solid #0007a9;
        background: #0000c98a;
        color: #f3f3f3;
    }

    .calender_race.nls {
        border-radius: 20px;
        border: 2px solid #c97900; 
        background: #c9790094;        
    }

    .calender_race.igc {
        border-radius: 20px;
        border: 2px solid #9000c9; 
        background: #9000c994;
    }

    .calender_race.f2 {
        border-radius: 20px;
        border: 2px solid #bd5656; 
        background: #bd56569d;
        color: white;
    }

    .calender_race_divider {
        width: 15px;
        text-align: center;
        font-weight: 100;
    }

    .calender_race_divider.wec {
        color: #0000c98a;
    }

    .calender_race_divider.nls {
        color: #c97900;
    }

    .calender_race_divider.igc {
        color: #9000c9;
    }

    .calender_race_divider.f2 {
        color: #bd5656;
    }
</style>


<h1>{{ $world->name }} Dashboard</h1>

<p>
    Current Year: <strong>{{ $currentYear }}</strong>
</p>

<hr>

@if($seasons->count() === 0)

<div style="padding: 20px; background: #fff3cd; border-radius: 6px;">
    <strong>No active series for {{ $currentYear }}.</strong>
    <br><br>

    <a href="{{ route('series.create') }}">
        <button>Create Series</button>
    </a>
</div>

@else



<div style="display: flex; flex-direction: row; justify-content: flex-start;">
    <div class="season-calendar">
        <h4>Calendar</h4>
        @forelse($upcomingRaces as $race)
        @php
        $raceSeries = "";
        $raceSeriesDivider = "";
        if($race->season?->series?->short_name == "WEC") { $raceSeries = "wec"; }
        else if($race->season?->series?->short_name == "NLS") { $raceSeries = "nls"; }
        else if($race->season?->series?->short_name == "IGC") { $raceSeries = "igc"; }
        else if($race->season?->series?->short_name == "F2") { $raceSeries = "f2"; }
        @endphp
        <div class="calender_race {{$raceSeries}}">
            <div style="font-weight:100;width:38px;text-align:center;"><i>{{ $race->season?->series?->short_name }}</i></div>
            <div class="calender_race_divider {{$raceSeries}}">|</div>
            <div style="width:35px; text-align:center;">R{{ $race->round_number }}</div>
            <div style="width:40px; ">{{ $race->race_code }}</div>
            <div style="width:10px;">-</div>
            <div style="width:65px;padding:0 4px;">{{ \Carbon\Carbon::parse($race->race_date)->format('M d') }}</div>
            <div style="width:85px; text-align:left;font-weight:100;margin-right:1px;">{{ $race->trackLayout?->track?->name_short }}</div>

        </div>
        @empty
        <div>
            No Upcoming Races
        </div>
        @endforelse


    </div>
    <div style="display: flex; gap: 5px; flex-wrap: wrap; flex-direction: column;margin-left:10px;">
        <div style="display: flex;">
            <h3 style="margin:0;">Active Series</h3>
            <div class="dashboard-actions">
                <a href="{{ route('series.create') }}" class="btn btn-primary">
                    + Create New Series
                </a>
            </div>
            <div class="dashboard-actions">
                <a href="{{ route('seasons.create') }}">
                    + Create New Season
                </a>
            </div>
        </div>

        @foreach($seasons as $season)
        <div style="border:1px solid #ccc; padding:15px; width:220px; background:#fff;">

            <h4>{{ $season->series->name }}</h4>

            <div style="margin-top:10px;">
                <a href="{{ route('seasons.show', $season->id) }}">
                    <button>Open Season</button>
                </a>

                <a href="{{ route('seasons.edit', $season->id, $world) }}">
                    <button>Edit</button>
                </a>
            </div>

        </div>

        @endforeach

    </div>
</div>


@endif

@endsection