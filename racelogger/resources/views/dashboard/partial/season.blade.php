<style>
    .career-table th {
        text-align: center;
    }

</style>

<div style="width:100%">
    <!-- // ? Year Picker -->
    <div style="width:100%">
        <h1>{{$currentYear}}</h1>
    </div>

    <div>
        <h5>Racing Career</h5>
        <table class="table career-table">
            <thead class="table-head">
                <th style="text-align:center">Season</th>
                <th>Series</th>
                <th>Team</th>
                <th>Car No.</th>
                <th>Races</th>
                <th>Wins</th>
                <th>Poles</th>
                <th>F/Laps</th>
                <th>Podiums</th>
                <th>Points</th>
                <th>Position</th>
            </thead>
            <tbody>
            @foreach($results as $seasons => $season)
                @php
                    $seriesCount = 1;                  
                @endphp

                @foreach($season as $details => $detail)                
                    
                    @php
                    $teamNo = 1;
                    $detail['teams'] = array_values($detail['teams']);
                    @endphp
                    
                    @foreach($detail["teams"] as $teams => $team)
                        <tr class="tr">
                            
                            @if(count($season) > 1) 
                                @if($seriesCount == 1)
                                    <td rowspan="{{ count($season); }}" style="vertical-align: middle; text-align:center;">{{$seasons}}</td>
                                @endif
                            @elseif(count($detail["teams"]) > 1 && $teamNo == 1)
                                <td rowspan="{{ count($detail["teams"]) }}" style="vertical-align: middle; text-align:center;">{{$seasons}}</td> 
                            @elseif(count($detail["teams"]) == 1 && $teamNo == 1)
                                <td rowspan="{{ count($detail["teams"]) }}" style="vertical-align: middle; text-align:center;">{{$seasons}}</td> 
                            @endif

                            @if($teamNo == 1)                                
                                <td rowspan="{{ count($detail["teams"]) }}" style="vertical-align: middle;">{{$detail["series"]["name"]}}</td> 
                            @endif

                            @php
                                $nextTeam = $detail["teams"][$loop->index + 1] ?? null;
                                $sameNameNext = $nextTeam && $nextTeam['name'] === $team['name'];
                                
                                $teams = array_values($detail["teams"]); // ensure sequential indexes
                                $rowspan = 1;

                                for ($x = $loop->index + 1; $x < count($teams); $x++) {
                                    if ($teams[$x]['name'] === $team['name']) {
                                        $rowspan++;
                                    } else {
                                        break;
                                    }
                                }

                                echo "rowspan = $rowspan";
                            @endphp
                            <td >{{$team["name"]}}</td>
                            <td style="text-align:center;">{{$team["car_no"]}}</td>
                            <td style="text-align:center;">{{ isset($team["results"]) ? count($team["results"]) : 0 }}</td>  
                            
                            @php
                            $raceWins = 0;
                            $poles = 0;
                            $FastLaps = 0;
                            $podiums = 0;
                            $points = 0;
                            @endphp
                                @if(isset($team["results"]))
                                    @foreach($team["results"] as $carResults => $carResult)
                                        @php

                                        if($carResult["class_position"] == 1) 
                                            $raceWins++;                        
                                        if($carResult["class_position"] <= 3) 
                                            $podiums++;                        
                                        if($carResult["grid_position"] == 1) 
                                            $poles++;
                                        if($carResult["fastest_lap"]) 
                                            $FastLaps++;
                                        $points += $carResult["points"];

                                        @endphp                                    
                                    @endforeach
                                @endif
                            <td style="text-align:center;">{{$raceWins}}</td>
                            <td style="text-align:center;">{{$poles}}</td>
                            <td style="text-align:center;">{{$FastLaps}}</td>
                            <td style="text-align:center;">{{$podiums}}</td>
                            <td style="text-align:center;">{{$points}}</td>
                            <td style="text-align:center;">{{$team["position"]}}</td>
                        </tr>

                        @php
                            $teamNo++;
                            $seriesCount++;
                        @endphp
                    @endforeach
                @endforeach
            @endforeach
            </tbody>
        </table>

    </div>


    <div class="card">
        <div class="card-header">
            <h3>Driver Results</h3>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Season Results</h3>
            </div>

            ```
            <div class="card-body">

                <table class="table table-bordered table-sm text-center">

                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Team</th>
                            <?php
                            print("<pre>");
                            print_r($results);
                            print("</pre>");
                            ?>
                        </tr>
                    </thead>

                    <tbody>


                    </tbody>

                </table>

            </div>
            ```

        </div>


    </div>




</div>

<script>


</script>