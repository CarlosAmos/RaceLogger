@extends('layouts.app')

@section('content')
<div class="container">

    <h2>Add Engine to {{ $world->name }}</h2>

    <form method="POST" action="{{ route('worlds.engines.store', $world) }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Engine Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Configuration</label>
            <input type="text" name="configuration" class="form-control" placeholder="V6, V8...">
        </div>

        <div class="mb-3">
            <label class="form-label">Capacity</label>
            <input type="text" name="capacity" class="form-control" placeholder="1.6L">
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="hybrid" class="form-check-input" id="hybrid">
            <label class="form-check-label" for="hybrid">Hybrid</label>
        </div>

        <button class="btn btn-primary">Create Engine</button>
    </form>

</div>
@endsection
