# routes.md — API & Web Route Index

> Auto-generated index. Source: `routes/web.php`, `routes/settings.php`

## Middleware Legend
- `auth` — requires login
- `verified` — requires email verification
- `world.selected` — requires active world session

---

## Public Routes

| Method | URI | Action | Name |
|--------|-----|--------|------|
| GET | `/` | `WorldController@index` | `world.select` |
| POST | `/world/select/{world}` | `WorldController@select` | `world.select.store` |
| GET | `/worlds/{world}/created` | `WorldController@created` | — |

---

## Auth-Protected Routes `[auth]`

### Worlds (resource)
| Method | URI | Action |
|--------|-----|--------|
| GET | `/worlds` | `WorldController@index` |
| POST | `/worlds` | `WorldController@store` |
| GET | `/worlds/{world}` | `WorldController@show` |
| GET | `/worlds/{world}/edit` | `WorldController@edit` |
| PUT/PATCH | `/worlds/{world}` | `WorldController@update` |
| DELETE | `/worlds/{world}` | `WorldController@destroy` |

---

## World-Context Routes `[auth, world.selected]`

### Dashboard
| Method | URI | Action | Name |
|--------|-----|--------|------|
| GET | `/dashboard` | `DashboardController@index` | `dashboard` |
| GET | `/records` | `LapRecordController@index` | — |

### Series (resource)
| Method | URI | Action | Name |
|--------|-----|--------|------|
| GET | `/series` | `SeriesController@index` | `series.index` |
| GET | `/series/create` | `SeriesController@create` | `series.create` |
| POST | `/series` | `SeriesController@store` | `series.store` |
| GET | `/series/{series}` | `SeriesController@show` | `series.show` |
| GET | `/series/{series}/edit` | `SeriesController@edit` | `series.edit` |
| PUT/PATCH | `/series/{series}` | `SeriesController@update` | `series.update` |
| DELETE | `/series/{series}` | `SeriesController@destroy` | `series.destroy` |
| GET | `/series/{series}/created` | `SeriesController@created` | — |

### Seasons (resource)
| Method | URI | Action | Name |
|--------|-----|--------|------|
| GET | `/seasons` | `SeasonController@index` | `seasons.index` |
| GET | `/seasons/create` | `SeasonController@create` | `seasons.create` |
| POST | `/seasons` | `SeasonController@store` | `seasons.store` |
| GET | `/seasons/{season}` | `SeasonController@show` | `seasons.show` |
| GET | `/seasons/{season}/edit` | `SeasonController@edit` | `seasons.edit` |
| PUT/PATCH | `/seasons/{season}` | `SeasonController@update` | `seasons.update` |
| DELETE | `/seasons/{season}` | `SeasonController@destroy` | `seasons.destroy` |

### Drivers (resource)
| Method | URI | Action |
|--------|-----|--------|
| GET/POST/PUT/DELETE | `/drivers` | `DriverController` (full resource) |
| GET/POST/PUT/DELETE | `/worlds/{world}/drivers` | `WorldDriverController` (nested resource) |

### Teams (resource)
| Method | URI | Action |
|--------|-----|--------|
| GET/POST/PUT/DELETE | `/teams` | `TeamController` (full resource) |

### Tracks (resource)
| Method | URI | Action |
|--------|-----|--------|
| GET/POST/PUT/DELETE | `/tracks` | `TrackController` |
| GET/POST/PUT/DELETE | `/track-layouts` | `TrackLayoutController` |

### Constructors & Vehicles (nested resources)
| Method | URI | Action |
|--------|-----|--------|
| GET/POST/PUT/DELETE | `/worlds/{world}/constructors` | `ConstructorController` |
| GET/POST/PUT/DELETE | `/worlds/{world}/constructors/{constructor}/car-models` | `ConstructorCarModelController` |
| GET/POST/PUT/DELETE | `/worlds/{world}/engines` | `WorldEngineController` |

### Entrants & Entry Management (nested resources)
| Method | URI | Action |
|--------|-----|--------|
| GET/POST/PUT/DELETE | `/worlds/{world}/entrants` | `EntrantController` |
| GET/POST/PUT/DELETE | `/worlds/{world}/seasons/{season}/season-entries` | `SeasonEntryController` |
| POST | `/worlds/{world}/seasons/{season}/season-entries/{seasonEntry}/entry-classes` | `EntryClassController@store` |
| DELETE | `/worlds/{world}/seasons/{season}/season-entries/{seasonEntry}/entry-classes/{entryClass}` | `EntryClassController@destroy` |
| GET/POST/PUT/DELETE | `/worlds/{world}/seasons/{season}/season-entries/{seasonEntry}/entry-classes/{entryClass}/entry-cars` | `EntryCarController` |
| GET | `/worlds/{world}/seasons/{season}/season-entries/{seasonEntry}/entry_create` | `EntryCarController@create_entry` |
| POST | `/worlds/{world}/seasons/{season}/season-entries/{seasonEntry}/entry_create` | `EntryCarController@store_entry` |
| GET | `.../entry-cars/{entryCar}/drivers` | `EntryCarDriverController@edit` |
| POST | `.../entry-cars/{entryCar}/drivers` | `EntryCarDriverController@update` |

### Race Weekend
| Method | URI | Action | Name |
|--------|-----|--------|------|
| GET | `/races/{race}` | `RaceWeekendController@show` | `races.show` |
| POST | `/races/{race}/weekend` | `RaceWeekendController@update` | `races.weekend.update` |

### Results (resource)
| Method | URI | Action |
|--------|-----|--------|
| GET/POST/PUT/DELETE | `/results` | `ResultController` (full resource) |
| GET/POST/PUT/DELETE | `/race-sessions` | `RaceSessionController` (full resource) |
| GET/POST/PUT/DELETE | `/calendar-races` | `CalendarRaceController` (full resource) |

### Points Systems (resource)
| Method | URI | Action |
|--------|-----|--------|
| GET/POST/PUT/DELETE | `/points-systems` | `PointsSystemController` |
| GET/POST | `/point-systems` | `PointSystemController` |

---

## Settings Routes `[auth]` / `[auth, verified]`

| Method | URI | Action | Name | Extra Middleware |
|--------|-----|--------|------|-----------------|
| GET | `/settings/profile` | `ProfileController@edit` | `profile.edit` | auth |
| PATCH | `/settings/profile` | `ProfileController@update` | `profile.update` | auth |
| DELETE | `/settings/profile` | `ProfileController@destroy` | `profile.destroy` | auth, verified |
| GET | `/settings/password` | `PasswordController@edit` | `user-password.edit` | auth, verified |
| PUT | `/settings/password` | `PasswordController@update` | `user-password.update` | auth, verified, throttle:6,1 |
| GET | `/settings/appearance` | Inertia `settings/appearance` | `appearance.edit` | auth, verified |
| GET | `/settings/two-factor` | `TwoFactorAuthenticationController@show` | `two-factor.show` | auth, verified |

---

> No separate `api.php` file exists — all routes go through web.php with session auth.
