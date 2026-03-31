<style>
    .career-table th {
        text-align: center;
    }

    .career-table-container {
        width: 100%;
        overflow-x: auto;
        border-radius: 8px;
        /* box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0; */
        margin: 20px 0;
    }

    .career-table {
        border-collapse: collapse;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        font-size: 13px;
        background: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
    }

    /* Header Styling */
    .career-table thead tr {
        background-color: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }

    .career-table th {
        padding: 12px;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.05em;
        color: #64748b;
        font-weight: 700;
    }

    /* Cell Styling */
    .career-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }

    /* The Year Group Column */
    .year-cell {
        background-color: #f8fafc;
        font-weight: 800;
        color: #1e293b;
        border-right: 1px solid #e2e8f0;
        vertical-align: top;
        text-align: center;
        width: 80px;
        vertical-align: middle;
    }

    .series-cell {
        font-weight: 600;
        border-right: 1px solid #f1f5f9;
    }

    .team-cell {
        font-style: italic;
        color: #64748b;
        border-right: 1px solid #f1f5f9;
    }

    /* Stats Columns */
    .stat-cell {
        text-align: center;
        border-right: 1px solid #f1f5f9;
        font-size:15px;
    }

    .highlight-win {
        background-color: #fefce8;
        /* Very light gold */
        color: #854d0e;
        font-weight: bold;
    }

    .font-bold {
        font-weight: 700;
    }

    .italic {
        font-style: italic;
    }

    /* The Pos Column */
    .pos-cell {
        text-align: center;
        background-color: #fdfdfd;
        width: 60px;
        border: 2px solid #e2e8f0;
    }

    .ordinal-text {
        font-weight: 900;
        text-transform: lowercase;
        letter-spacing: -0.05em;
        font-size: 14px;
        color: #0f172a;
    }

    /* Row Hover */
    .career-row:hover {
        background-color: #f1f5f9;
    }

    .pos-first {
        background: #fffb4cb5;
    }

    .pos-second {
        background: rgb(203 203 203 / 85%);
    }

    .pos-third {
        background: #FFDF9F;
    }

    .pos-general {
        background: rgba(34, 197, 94, 0.25);
    }

    .pos-dnf {
        background: rgba(100, 98, 184, 0.25);
    }

    .result-label-active { color: #000; }
    .result-label-inactive { color: #94a3b8; }
</style>

<div style="width:100%">
    <!-- // ? Year Picker -->
    <div>
        <h5>Racing Career</h5>
        <table class="career-table">
            <thead>
                <tr>
                    <th class="col-season">Season</th>
                    <th class="col-series">Series</th>
                    <th class="col-team">Team</th>
                    <th class="col-stat">Races</th>
                    <th class="col-stat">Wins</th>                    
                    <th class="col-stat">Poles</th>
                    <th class="col-stat">FL</th>
                    <th class="col-stat">Podiums</th>
                    <th class="col-stat">Points</th>
                    <th class="col-pos">Position</th>
                </tr>
            </thead>
            <tbody>
                @foreach($careerMap as $year => $seasons)
                @foreach($seasons as $season)
                <tr class="career-row">
                    @if($loop->first)
                    <td class="year-cell" rowspan="{{ $seasons->count() }}">
                        {{ $year }}
                    </td>
                    @endif

                    <td class="series-cell"><a href="{{ route('seasons.show',  $season['season_id'] ) }}">{{ $season['series_name'] }}  </a></td>
                    <td class="team-cell">{{ $season['teams']->implode(', ') }}</td>
                    <td class="stat-cell">{{ $season['stats']->races }}</td>

                    {{-- Highlight wins in gold if > 0 --}}
                    <td class="stat-cell">
                        {{ $season['stats']->wins }}
                    </td>

                    
                    <td class="stat-cell">{{ $season['stats']->poles }}</td>
                    <td class="stat-cell">{{ $season['stats']->fastest_laps }}</td>
                    <td class="stat-cell">{{ $season['stats']->podiums }}</td>
                    <td class="stat-cell font-bold">{{ $season['stats']->points }}</td>

                    @php
                    $positionClass = "";
                    if($season['ordinal'] == "1st") $positionClass = "pos-first";
                    else if($season['ordinal'] == "2nd") $positionClass = "pos-second";
                    else if($season['ordinal'] == "3rd") $positionClass = "pos-third";
                    @endphp
                    
                    <td class="pos-cell {{$positionClass}}">
                        <span class="ordinal-text">{{ $season['ordinal'] }} {{ ($season["stats"]->season_active == 1 ? '*' : ''); }}</span>
                    </td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>

    </div>

    <div style="margin-top:40px;">
        @foreach($resultsGrid as $seriesName => $seriesData)
            @php
                $isMulticlass = $seriesData['is_multiclass'];
                $isSpec       = $seriesData['is_spec'];
            @endphp

            <h5>{{ $seriesName }}</h5>

            @foreach($seriesData['seasons'] as $year => $seasonData)
                @php
                    $calendar    = $seasonData['calendar'];
                    $seasonId    = $seasonData['season_id'];
                    $seasonStats = $careerMap[$year][$seasonId] ?? null;
                    $champPos    = $seasonStats['position']      ?? '-';
                    $points      = $seasonStats['stats']->points ?? '-';
                @endphp

                <div class="career-table-container" style="margin-bottom:16px;">
                    <table class="career-table">
                        <thead>
                            <tr>
                                <th>Year</th>

                                @if($isSpec)
                                    <th>Team</th>
                                @else
                                    <th>Entrant</th>
                                    @if($isMulticlass)<th>Class</th>@endif
                                    <th>Chassis / Engine</th>
                                @endif

                                @foreach($calendar as $round => $roundData)
                                    @php $sessionCount = max(count($roundData['sessions']), 1); @endphp
                                    <th @if($sessionCount > 1) colspan="{{ $sessionCount }}" @endif>{{ $round }}</th>
                                @endforeach

                                <th>Place</th>
                                <th>Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($seasonData['entries'] as $entry)
                            <tr class="career-row">
                                <td class="year-cell">{{ $year }}</td>

                                @if($isSpec)
                                    <td class="team-cell">{{ $entry['entrant'] }}</td>
                                @else
                                    <td class="team-cell">{{ $entry['entrant'] }}</td>
                                    @if($isMulticlass)
                                        <td class="stat-cell">{{ $entry['class'] }}</td>
                                    @endif
                                    <td class="series-cell" style="font-size:11px;white-space:nowrap;">
                                        {{ $entry['chassis'] }}<br>
                                        <span class="italic" style="color:#64748b;font-weight:400;">{{ $entry['engine'] }}</span>
                                    </td>
                                @endif

                                @foreach($calendar as $round => $roundData)
                                    @if(count($roundData['sessions']) > 0)
                                        @foreach($roundData['sessions'] as $session)
                                            @php
                                                $result = $entry['results'][$round][$session['session_id']] ?? null;
                                                $resultClass = '';
                                                if ($result !== null) {
                                                    if ($result === '1')                        $resultClass = 'pos-first';
                                                    elseif ($result === '2')                    $resultClass = 'pos-second';
                                                    elseif ($result === '3')                    $resultClass = 'pos-third';
                                                    elseif (is_numeric($result))               $resultClass = 'pos-general';
                                                    else                                        $resultClass = 'pos-dnf';
                                                }
                                            @endphp
                                            @php $labelClass = $result !== null ? 'result-label-active' : 'result-label-inactive'; @endphp
                                            <td class="stat-cell {{ $resultClass }}" style="min-width:36px;text-align:center;line-height:1.3;">
                                                <span class="{{ $labelClass }}" style="display:block;font-size:10px;">{{ $roundData['race_code'] }}</span>
                                                @if(count($roundData['sessions']) > 1)
                                                    <span class="{{ $labelClass }} italic" style="display:block;font-size:10px;">{{ $session['is_sprint'] ? 'SPR' : 'FEA' }}</span>
                                                @endif
                                                <span style="display:block;">{{ $result ?? '' }}</span>
                                            </td>
                                        @endforeach
                                    @else
                                        <td class="stat-cell" style="min-width:36px;text-align:center;line-height:1.3;">
                                            <span style="display:block;font-size:10px;color:#94a3b8;">{{ $roundData['race_code'] }}</span>
                                        </td>
                                    @endif
                                @endforeach

                                @php
                    $gridPosClass = '';
                    if ($champPos == 1)      $gridPosClass = 'pos-first';
                    elseif ($champPos == 2)  $gridPosClass = 'pos-second';
                    elseif ($champPos == 3)  $gridPosClass = 'pos-third';
                @endphp
                <td class="pos-cell {{ $gridPosClass }}">{{ $seasonStats['ordinal'] ?? $champPos }}</td>
                                <td class="stat-cell font-bold">{{ $points }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        @endforeach
    </div>
</div>

<script>


</script>