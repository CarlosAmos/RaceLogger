<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name', 'Virtual Racing Logger') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f5f5f5;
        }

        header {
            background: #111;
            color: white;
            padding: 15px 20px;
        }

        header a {
            color: white;
            text-decoration: none;
            margin-right: 15px;
        }

        .container {
            padding: 20px;
        }

        .flash-success {
            background: #d4edda;
            padding: 10px;
            margin-bottom: 15px;
        }

        .flash-error {
            background: #f8d7da;
            padding: 10px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<header>
    <strong>Virtual Racing Logger</strong>

    @if(isset($activeWorld) && $activeWorld)
        <div style="margin-top: 8px;">
            Active World: <strong>{{ $activeWorld->name }}</strong>
        </div>

        <div style="margin-top: 10px;">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('series.index') }}">Series</a>
            <a href="{{ route('seasons.index') }}">Seasons</a>
            <a href="{{ route('tracks.index') }}">Tracks</a>
            <a href="{{ route('worlds.constructors.index', $world) }}">Teams</a>
            <a href="{{ route('drivers.index') }}">Drivers</a>
            <a href="{{ route('world.select') }}">Change World</a>
        </div>
    @endif
</header>

<div class="container">

    @if(session('success'))
        <div class="flash-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="flash-error">
            {{ session('error') }}
        </div>
    @endif

    @yield('content')

</div>

</body>
</html>
