@extends('layouts.app')

@section('content')

<h1>Create Series</h1>

@if ($errors->any())
    <div style="background: #f8d7da; padding: 10px; margin-bottom: 15px;">
        <strong>There were some errors:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif


<form method="POST" action="{{ route('series.store') }}">
    @csrf

    <div>
        <label>Series Name</label><br>
        <input type="text" name="name" required>
    </div>

    <br>

    <div>
        <label>
            <input type="checkbox" name="is_multiclass">
            Multi-Class Championship
        </label>
    </div>

    <br>

    <button type="submit">Create Series</button>
</form>

@endsection
