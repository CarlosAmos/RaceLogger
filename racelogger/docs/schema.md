# schema.md — Database Schema

> Compressed reference. Source: `database/migrations/`, `app/Models/`
> All tables use `id` (PK, auto-increment) and `timestamps` unless noted.

---

## Core Hierarchy

```
worlds
  └── series
        └── seasons
              └── calendar_races
                    ├── race_sessions
                    │     └── results
                    │           └── result_drivers
                    └── qualifying_sessions
                          └── qualifying_results
```

---

## Tables

### `worlds`
| Column | Type | Notes |
|--------|------|-------|
| name | string | |
| start_year | year | |
| current_year | year | |
| is_canonical | boolean | |

---

### `series`
| Column | Type | Notes |
|--------|------|-------|
| world_id | FK → worlds | |
| name | string | |
| short_name | string | e.g. "WEC", "F1", "NLS" |
| is_multiclass | boolean | enables class_position in results |

---

### `seasons`
| Column | Type | Notes |
|--------|------|-------|
| series_id | FK → series | |
| year | year | |
| is_simulated | boolean | |
| point_system_id | FK → point_systems | default points system |

---

### `calendar_races`
| Column | Type | Notes |
|--------|------|-------|
| season_id | FK → seasons | |
| track_layout_id | FK → track_layouts | |
| round_number | int | sort order within season |
| gp_name | string | full race name |
| race_code | string | short code e.g. "BHR" |
| race_date | date | |
| point_system_id | FK → point_systems | nullable; overrides season default |
| is_locked | boolean | prevents result edits |

---

### `race_sessions`
| Column | Type | Notes |
|--------|------|-------|
| calendar_race_id | FK → calendar_races | |
| name | string | e.g. "Race", "Sprint" |
| session_order | int | |
| is_sprint | boolean | |
| reverse_grid | boolean | |
| reverse_grid_from_position | int | nullable |

---

### `results`
| Column | Type | Notes |
|--------|------|-------|
| race_session_id | FK → race_sessions | |
| entry_car_id | FK → entry_cars | |
| position | int | overall finish position |
| class_position | int | nullable; only for multiclass |
| status | enum | `finished`, `dnf`, `dns`, `dsq` |
| gap_to_leader_ms | bigint | nullable |
| gap_laps_down | int | nullable |
| laps_completed | int | |
| fastest_lap_time_ms | bigint | nullable |
| fastest_lap | boolean | fastest lap award flag |
| points_awarded | decimal | |

---

### `result_drivers`
| Column | Type | Notes |
|--------|------|-------|
| result_id | FK → results | |
| driver_id | FK → drivers | |
| driver_order | int | for co-driver sorting |

---

### `qualifying_sessions`
| Column | Type | Notes |
|--------|------|-------|
| calendar_race_id | FK → calendar_races | |
| name | string | |
| session_order | int | |

---

### `qualifying_results`
| Column | Type | Notes |
|--------|------|-------|
| qualifying_session_id | FK → qualifying_sessions | |
| entry_car_id | FK → entry_cars | |
| position | int | |
| best_lap_time_ms | bigint | |
| average_lap_time_ms | bigint | nullable |
| laps_set | int | |

---

### `drivers`
| Column | Type | Notes |
|--------|------|-------|
| world_id | FK → worlds | |
| first_name | string | |
| last_name | string | |
| nationality | string | |
| date_of_birth | date | |
| rating | int | nullable |
| country_id | FK → countries | |

---

### `constructors`
| Column | Type | Notes |
|--------|------|-------|
| world_id | FK → worlds | |
| name | string | |
| country | string | |
| color | string | hex color |
| country_id | FK → countries | |

---

### `car_models`
| Column | Type | Notes |
|--------|------|-------|
| constructor_id | FK → constructors | |
| name | string | |
| year | int | |
| engine_id | FK → engines | |

---

### `engines`
| Column | Type | Notes |
|--------|------|-------|
| name | string | |
| capacity | int | cc |
| configuration | string | e.g. "V6", "Flat-6" |

---

### `entrants`
| Column | Type | Notes |
|--------|------|-------|
| world_id | FK → worlds | |
| name | string | team/entrant display name |

---

### `season_entries`
| Column | Type | Notes |
|--------|------|-------|
| season_id | FK → seasons | |
| entrant_id | FK → entrants | |
| constructor_id | FK → constructors | |
| display_name | string | nullable |

---

### `season_classes`
| Column | Type | Notes |
|--------|------|-------|
| season_id | FK → seasons | |
| name | string | e.g. "Hypercar", "GTE" |

---

### `entry_classes`
| Column | Type | Notes |
|--------|------|-------|
| season_entry_id | FK → season_entries | |
| race_class_id | FK → season_classes | |

---

### `entry_cars`
| Column | Type | Notes |
|--------|------|-------|
| entry_class_id | FK → entry_classes | |
| car_model_id | FK → car_models | |
| car_number | string | |
| livery_name | string | nullable |
| chassis_code | string | nullable |

---

### `entry_car_driver` (pivot)
| Column | Type |
|--------|------|
| entry_car_id | FK → entry_cars |
| driver_id | FK → drivers |

---

### `point_systems`
| Column | Type |
|--------|------|
| name | string |

### `point_system_rules`
| Column | Type | Notes |
|--------|------|-------|
| point_system_id | FK → point_systems | |
| type | enum | `race`, `qualifying`, etc. |
| position | int | finishing position |
| points | decimal | |

### `point_system_bonus_rules`
| Column | Type | Notes |
|--------|------|-------|
| point_system_id | FK → point_systems | |
| type | enum | `fastest_lap`, `pole`, etc. |
| points | decimal | |
| requires_finish | boolean | |
| min_position_required | int | nullable |
| eligibility | string | nullable |

---

### `tracks`
| Column | Type |
|--------|------|
| name | string |
| name_short | string |
| city | string |
| country | string |
| country_id | FK → countries |

### `track_layouts`
| Column | Type | Notes |
|--------|------|-------|
| track_id | FK → tracks | |
| name | string | |
| layout_type | string | |
| year | int | year this layout was active from |

---

### `lap_records`
| Column | Type | Notes |
|--------|------|-------|
| world_id | FK → worlds | |
| track_layout_id | FK → track_layouts | |
| session_type | enum | `race`, `qualifying` |
| driver_id | FK → drivers | |
| season_id | FK → seasons | |
| lap_time_ms | bigint | |
| record_date | date | |

### `lap_record_logs`
> Same columns as `lap_records` — historical log of all broken records.

---

### `countries`
| Column | Type |
|--------|------|
| name | string |
| code | string | ISO 2-letter |

---

## Key Model Relationships

```
World          hasMany Series, Driver, Constructor, Entrant
Series         hasMany Season; belongsTo World
Season         hasMany CalendarRace, SeasonEntry, SeasonClass; belongsTo Series
CalendarRace   hasMany RaceSession, QualifyingSession; belongsTo Season, TrackLayout
RaceSession    hasMany Result; belongsTo CalendarRace
Result         belongsTo RaceSession, EntryCar; hasMany ResultDriver
ResultDriver   belongsTo Result, Driver
EntryCar       belongsToMany Driver (entry_car_driver); belongsTo EntryClass, CarModel
EntryClass     belongsTo SeasonEntry, SeasonClass; hasMany EntryCar
SeasonEntry    belongsTo Season, Entrant, Constructor; hasMany EntryClass
```
