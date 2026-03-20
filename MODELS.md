# RaceLogger - Models and Relationships

**Framework:** Laravel (PHP) with Eloquent ORM
**Model Directory:** `racelogger/app/Models/`

---

## Models

### World
- `id`, `name`, `start_year`, `is_canonical`, `current_year`
- hasMany: Series, Team, Driver, Constructor, Entrant, Engine, LapRecord

### Series
- `id`, `world_id`, `name`, `is_multiclass`
- belongsTo: World
- hasMany: Season, CalendarRace

### Season
- `id`, `series_id`, `world_id`, `year`, `point_system_id`, `is_simulated`
- belongsTo: Series, World, PointSystem
- hasMany: CalendarRace, SeasonTeamEntry, SeasonClass, SeasonEntry

### Driver
- `id`, `world_id`, `first_name`, `last_name`, `country_id`, `date_of_birth`
- belongsTo: World, Country
- hasMany: LapRecord, ResultDriver
- belongsToMany: EntryCar (via `entry_car_driver`)

### Team
- `id`, `world_id`, `name`, `base_country`, `active`
- belongsTo: World
- hasMany: SeasonTeamEntry

### Country
- `id`, `name`, `iso_code`, `continent`
- hasMany: Track, Constructor, Driver

### Track
- `id`, `name`, `country_id`, `city`
- belongsTo: Country
- hasMany: TrackLayout

### TrackLayout
- `id`, `track_id`, `name`, `length_km`, `turn_count`, `active_from`, `active_to`
- belongsTo: Track
- hasMany: CalendarRace

### CalendarRace
- `id`, `season_id`, `track_layout_id`, `round_number`, `gp_name`, `race_code`, `race_date`, `point_system_id`, `sprint_race`, `is_locked`
- belongsTo: Season, TrackLayout, PointSystem
- hasMany: RaceSession, QualifyingSession, RaceEntryCar
- belongsToMany: EntryCar (via `race_entry_cars`)
- hasManyThrough: Result (through RaceSession)

### RaceSession
- `id`, `calendar_race_id`, `name`, `session_order`, `is_sprint`, `reverse_grid`, `reverse_grid_from_position`
- belongsTo: CalendarRace
- hasMany: Result

### Result
- `id`, `race_session_id`, `entry_car_id`, `position`, `class_position`, `status` (finished/dnf/dsq/dns), `laps_completed`, `gap_to_leader_ms`, `gap_laps_down`, `fastest_lap_time_ms`, `fastest_lap`, `points_awarded`
- belongsTo: RaceSession, EntryCar
- hasMany: ResultDriver
- belongsToMany: Driver (via `result_drivers`)

### ResultDriver
- `id`, `result_id`, `driver_id`, `driver_order`
- belongsTo: Result, Driver

### QualifyingSession
- `id`, `calendar_race_id`, `name`, `session_order`, `is_elimination`, `final_target`
- belongsTo: CalendarRace
- hasMany: QualifyingResult

### QualifyingResult
- `id`, `qualifying_session_id`, `entry_car_id`, `position`, `best_lap_time_ms`, `average_lap_time_ms`, `laps_set`
- belongsTo: QualifyingSession, EntryCar

### Constructor
- `id`, `world_id`, `name`, `country_id`
- belongsTo: World, Country
- hasMany: CarModel, Entry, Entrant

### Engine
- `id`, `world_id`, `name`, `configuration`, `capacity`, `hybrid`
- belongsTo: World
- hasMany: CarModel

### CarModel
- `id`, `constructor_id`, `engine_id`, `name`, `year`
- belongsTo: Constructor, Engine
- hasMany: EntryCar

### EntryCar
- `id`, `entry_class_id`, `car_model_id`, `car_number`, `livery_name`, `chassis_code`
- belongsTo: EntryClass, CarModel
- hasMany: Result, QualifyingResult
- belongsToMany: Driver (via `entry_car_driver`)

### RaceEntryCar  *(pivot/join model)*
- `id`, `calendar_race_id`, `entry_car_id`
- belongsTo: CalendarRace, EntryCar

### Entrant
- `id`, `world_id`, `name`, `country_id`
- belongsTo: World, Country
- hasMany: SeasonEntry

### SeasonEntry
- `id`, `entrant_id`, `season_id`, `series_id`, `constructor_id`, `display_name`
- belongsTo: Entrant, Constructor
- hasMany: EntryClass

### SeasonTeamEntry
- `id`, `season_id`, `team_id`, `engine_supplier_id`
- belongsTo: Season, Team, EngineSupplier
- hasMany: CarEntry

### SeasonClass
- `id`, `season_id`, `name`, `display_order`
- belongsTo: Season
- hasMany: EntryClass (via `race_class_id`)

### Entry
- `id`, `constructor_id`
- belongsTo: Constructor
- hasMany: EntryClass

### EntryClass
- `id`, `entry_id`, `season_entry_id`, `race_class_id`
- belongsTo: Entry, SeasonEntry, SeasonClass (as `race_class_id`)
- hasMany: Car, EntryCar

### CarEntry
- `id`, `season_team_entry_id`, `car_model_name`, `number`
- belongsTo: SeasonTeamEntry
- hasMany: Result

### Car
- `id`, `entry_class_id`
- belongsTo: EntryClass

### EngineSupplier
- `id`, `name`, `manufacturer`
- hasMany: SeasonTeamEntry

### PointSystem
- `id`, `name`, `description`
- hasMany: PointSystemRule, PointSystemBonusRule

### PointSystemRule
- `id`, `point_system_id`, `type` (race/qualifying), `position`, `points`
- belongsTo: PointSystem

### PointSystemBonusRule
- `id`, `point_system_id`, `type`, `points`
- belongsTo: PointSystem

### LapRecord
- `id`, `world_id`, `track_layout_id`, `session_type`, `driver_id`, `season_id`, `lap_time_ms`, `record_date`
- belongsTo: World, Driver, TrackLayout

### LapRecordLog  *(audit log — no relationships)*
- `id`, `world_id`, `track_layout_id`, `session_type`, `driver_id`, `season_id`, `lap_time_ms`, `record_date`

### User  *(authentication only)*
- `id`, `name`, `email`, `password`, `email_verified_at`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `remember_token`

---

## Pivot / Junction Tables

| Table | Connects |
|---|---|
| `entry_car_driver` | Driver ↔ EntryCar |
| `race_entry_cars` | CalendarRace ↔ EntryCar |
| `result_drivers` | Result ↔ Driver (with `driver_order`) |

---

## Hierarchy Overview

```
World
├── Series → Season → CalendarRace
│                         ├── RaceSession → Result → ResultDriver → Driver
│                         ├── QualifyingSession → QualifyingResult → EntryCar
│                         └── TrackLayout → Track → Country
│             ├── SeasonTeamEntry → Team / EngineSupplier
│             ├── SeasonEntry → Entrant
│             └── SeasonClass
├── Constructor → CarModel → EntryCar
│             └── Engine
├── Driver (belongsToMany EntryCar)
├── Entrant → SeasonEntry → EntryClass → EntryCar
└── Country → Track → TrackLayout
```

---

## Key Design Notes

- **Multi-driver cars**: Results link to multiple drivers via `ResultDriver` (useful for endurance/shared drives).
- **Multi-class racing**: `SeasonClass` + `EntryClass` enable class-based championships; `Result.class_position` tracks class finishing order.
- **Sprint races**: Supported via `RaceSession.is_sprint` and `CalendarRace.sprint_race`.
- **Flexible point systems**: `PointSystem` can be set at Season level or overridden per `CalendarRace`.
- **Entry hierarchy**: Entrant → SeasonEntry → EntryClass → EntryCar → Result (tracks the full chain from organisation to on-track car).
- **Lap records**: `LapRecord` holds the current record; `LapRecordLog` is an immutable audit trail.
