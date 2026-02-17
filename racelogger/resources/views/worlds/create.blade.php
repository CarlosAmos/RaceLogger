@extends('layouts.app')

@section('content')

<h1>Create New World</h1>

<form method="POST" action="{{ route('worlds.store') }}">
    @csrf

    <div>
        <label>World Name</label><br>
        <input type="text" name="name" required>
    </div>

    <br>

    <div>
        <label>Start Year</label><br>
        <input type="number" name="start_year" required>
    </div>

    <br>

    <button type="submit">Create World</button>
</form>

@endsection
