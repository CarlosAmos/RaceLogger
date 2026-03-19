<style>
    .career-table th {
        text-align: center;
    }

    .career-table-container {
        width: 100%;
        overflow-x: auto;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        margin: 20px 0;
    }

    .career-table {
        width: 100%;
        border-collapse: collapse;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        font-size: 13px;
        background: white;
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
        background: #ffc1078c;
    }

    .pos-second {
        background: rgb(203 203 203 / 85%);
    }

    .pos-third {
        background: #e371007a
    }
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
                    <th class="col-stat">Podiums</th>
                    <th class="col-stat">Poles</th>
                    <th class="col-stat">FL</th>
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

                    <td class="series-cell">{{ $season['series_name'] }}</td>
                    <td class="team-cell">{{ $season['teams']->implode(', ') }}</td>
                    <td class="stat-cell">{{ $season['stats']->races }}</td>

                    {{-- Highlight wins in gold if > 0 --}}
                    <td class="stat-cell">
                        {{ $season['stats']->wins }}
                    </td>

                    <td class="stat-cell">{{ $season['stats']->podiums }}</td>
                    <td class="stat-cell">{{ $season['stats']->poles }}</td>
                    <td class="stat-cell">{{ $season['stats']->fastest_laps }}</td>
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
        <h5>Complete {Race Series} results</h5>
    </div>
</div>

<script>


</script>