<?php

namespace App\Http\Controllers;

use App\Models\World;
use App\Models\Series;
use App\Models\Season;
use App\Models\Driver;
use App\Models\CalendarRace;
use App\Models\SeasonEntry;
use App\Models\ResultDriver;
use App\Services\DriverCareerService;
use App\Services\CareerResultsGridService;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(DriverCareerService $service, CareerResultsGridService $gridService)
    {
        $worldId = session('active_world_id');

        $world = World::findOrFail($worldId);

        $currentYear = $world->current_year ?? Carbon::now()->year;

        // Get seasons for current year with their series
        $seasons = Season::whereHas('series', function ($query) use ($worldId) {
                $query->where('world_id', $worldId);
            })
            ->with('series')
            ->orderBy('year', 'asc')
            ->get();

        $upcomingRaces = CalendarRace::with(['season.series','trackLayout.track'])
            ->where('is_locked', 0)
            ->whereHas('season', function ($query) use ($currentYear) {
                $query->where('year', '>=', $currentYear);
            })
            ->whereHas('season.series', function ($query) use ($worldId) {
                $query->where('world_id', $worldId);
            })
            ->orderBy('race_date', 'asc')
            ->get();

        if($worldId == 2) {
            $myId = 194; // My Id
        } else if($worldId == 3) {
            $myId = 1569;
        } else {
            $myId = 2671;
        }
        
        
        // $myId = 186;
        // $myId = 161;
        //$results = $this->getDriverSeasonResults($myId, $worldId);
        $careerMap   = $service->getCareerStructure($myId, $worldId);
        $resultsGrid = $gridService->getResultsGrid($myId, $worldId);

        //dd($careerMap);
        return Inertia::render('dashboard', compact(
            'world',
            'currentYear',
            'seasons',
            'upcomingRaces',
            'careerMap',
            'resultsGrid'
        ));
    }

    public function getDriverSeasonResults($driverId, $worldId)
    {
        $driverResults = ResultDriver::with([
            'result.raceSession.calendarRace.season.series',
            'result.entryCar.entryClass.seasonEntry.entrant',
            'result.raceSession.calendarRace',
            'result.raceSession.calendarRace.qualifyingSessions.qualifyingResults',
        ])
        ->where('driver_id', $driverId)
        ->get();

        
       
        $raceCalendars = CalendarRace::with([
            'season.series'
            ])
            ->whereHas('season.series', function ($q) use ($worldId) {
                $q->where('world_id', $worldId);
            })
        ->get();

        $upcomingDriver = Driver::with([
            'entryCars.entryClass.seasonEntry.entrant',
            'entryCars.entryClass.raceClass.season',
            'entryCars.entryClass.raceClass.season.series'
        ])->where("id",$driverId)
        ->get();

        

        $seasons = [];       

        foreach($driverResults as $driverResult) {
            $race = $driverResult->result->raceSession->calendarRace;
            $raceResult = $driverResult->result;
            //$qualiSessions = $driverResult->result->raceSession->calendarRace->qualifyingSessions;
            $qualiSessions = $driverResult->result->raceSession->calendarRace->qualifyingSessions;
            $entryCar =  $driverResult->result->entryCar;
            $entrantCar = $entryCar->entryClass->seasonEntry->entrant;             

            $season = $race->season;
            $series = $season->series;
            $seasonId = $season->id;
            $entryCarId = $entryCar->id;
            $qualiPosition = 0;
            foreach($qualiSessions as $sessions => $session) {
                foreach($session->qualifyingResults as $results => $result) {
                    if ((int)$result["entry_car_id"] == (int)$entryCarId) {
                        $qualiPosition = $result["position"];   
                        break 2;
                    };
                }
            }

            if(!isset($seasons[$season->year][$seasonId]["rounds"][$race->round_number])) $seasons[$season->year][$seasonId]["rounds"][$race->round_number] = [];
            // ? Race
            if(!isset($seasons[$season->year][$seasonId]["rounds"][$race->round_number]["team"][$entryCarId]))  $seasons[$season->year][$seasonId]["rounds"][$race->round_number]["teams"][$entryCarId]["name"] = $entrantCar->name;
            $seasons[$season->year][$seasonId]["rounds"][$race->round_number] = [
                'round' => $race->round_number,
                'race_code' => $race->race_code,
            ];

            

            $seasons[$season->year][$seasonId]["teams"][$entryCarId]["name"] = $entrantCar->name;
            $seasons[$season->year][$seasonId]["teams"][$entryCarId]["car_no"] = $entryCar->car_number;
            $seasons[$season->year][$seasonId]["teams"][$entryCarId]["position"] = "1st";
            $seasons[$season->year][$seasonId]["teams"][$entryCarId]["results"][$raceResult->race_session_id] = [
                'class_position' => $driverResult->result->class_position,
                'fastest_lap' =>  $driverResult->result->fastest_lap,
                'points' => $driverResult->result->points_awarded,
                'grid_position' => $qualiPosition
            ];
            // ? Serie
            $seasons[$season->year][$seasonId]["series"] = $series;            
        }
        //dd($driverResult);
        
        // ? Add new season details to career including races
        foreach($upcomingDriver as $index => $upcoming) {
            $entryCarList = $upcoming->entryCars;

            foreach($entryCarList as $entry) {
                $seasonEntry = $entry->entryClass->seasonEntry;
                $season = $entry->entryClass->raceClass->season;
                $series = $season->series;
                $entrant = $entry->entryClass->seasonEntry->entrant;

                if(!isset($seasons[$season->year][$season->id]["teams"][$entry->id])) $seasons[$season->year][$season->id]["teams"][$entry->id] = [
                    "name" => $entrant->name,
                    "car_no" => $entry->car_no,
                    "position" => "-"
                ];

                if(!isset($seasons[$season->year][$season->id]["series"])) $seasons[$season->year][$season->id]["series"] = $series;
                //dd($series);
            }


        }

        //dd($seasons);


        // ? Add missing races for season
        foreach($raceCalendars as $races => $race) {
            $season = $race->season;
            if($season->is_simulated == 1) continue;

            // if(!isset($seasons[$season->year])) $seasons[$season->year] = [];
            // if(!isset($seasons[$season->year][$season->id])) $seasons[$season->year][$season->id] = [
            //     "series" => $season->series,
            //     "rounds" => []
            // ];

            if(isset($seasons[$season->year][$season->id]) &&
            !isset($seasons[$season->year][$season->id]["rounds"][$race->round_number])) $seasons[$season->year][$season->id]["rounds"][$race->round_number] = [
                'round' => $race->round_number,
                'race_code' => $race->race_code,
            ];


            //dd($race);
        }


        //dd($seasons);

        return $seasons;
    }
}
