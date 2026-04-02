# components.md — Component Index

> Blade partials with their variables, and React/TSX components with their props.

---

## Blade Partials

### `dashboard/partial/season.blade.php`
Included by `dashboard/index.blade.php`. Renders the full career section.

**Required variables (passed via view):**
| Variable | Type | Description |
|----------|------|-------------|
| `$careerMap` | `array<year, Collection<seasonId, season[]>>` | Career summary keyed by year then season ID. Each item has `season_id`, `series_name`, `teams`, `stats` (object), `ordinal`, `position`. |
| `$resultsGrid` | `array<seriesName, seriesData>` | Per-series race-by-race result grid. See `CareerResultsGridService`. |

**`$resultsGrid` shape per series:**
```php
[
  'is_multiclass' => bool,
  'is_spec'       => bool,
  'seasons'       => [
    year => [
      'season_id' => int,
      'calendar'  => [ roundNumber => ['race_code' => string, 'sessions' => [['session_id', 'is_sprint']]] ],
      'entries'   => [ ['entrant', 'class', 'chassis', 'engine', 'results' => [round => [sessionId => result]]] ]
    ]
  ]
]
```

---

### `races/weekend/partials/participants.blade.php`
**Required variables:**
| Variable | Description |
|----------|-------------|
| `$race` | `CalendarRace` model instance |
| `$entries` | Collection of entry cars for this race |

---

### `races/weekend/partials/qualifying.blade.php`
**Required variables:**
| Variable | Description |
|----------|-------------|
| `$race` | `CalendarRace` model |
| `$qualifyingSessions` | Collection of `QualifyingSession` models |
| `$entries` | Entry cars |

---

### `races/weekend/partials/race-results.blade.php`
**Required variables:**
| Variable | Description |
|----------|-------------|
| `$race` | `CalendarRace` model |
| `$raceSession` | `RaceSession` model (feature race) |
| `$entries` | Entry cars with existing results |

---

### `races/weekend/partials/sprint-race-results.blade.php`
**Required variables:**
| Variable | Description |
|----------|-------------|
| `$race` | `CalendarRace` model |
| `$sprintSession` | `RaceSession` model (sprint) |
| `$entries` | Entry cars |

---

### `seasons/form.blade.php`
Shared create/edit fields for Season.
**Required variables:** `$series` (Collection for select), `$pointSystems` (Collection)

### `tracks/form.blade.php`
Shared fields for Track create/edit.
**Required variables:** `$countries` (Collection)

### `track_layouts/form.blade.php`
Shared fields for TrackLayout create/edit.
**Required variables:** `$track` (Track model)

---

## React/TypeScript Components (`resources/js/components/`)

### App Shell Components

#### `app-shell.tsx`
Top-level wrapper. No external props — composes sidebar + content.

#### `app-header.tsx`
| Prop | Type | Description |
|------|------|-------------|
| `breadcrumbs` | `BreadcrumbItem[]` | Page breadcrumb trail |

#### `app-sidebar.tsx`
No external props. Reads nav items internally.

#### `app-content.tsx`
| Prop | Type |
|------|------|
| `children` | `ReactNode` |
| `variant?` | `'default' \| 'sidebar'` |

#### `breadcrumbs.tsx`
| Prop | Type |
|------|------|
| `breadcrumbs` | `BreadcrumbItem[]` — `{ title: string, href?: string }` |

#### `heading.tsx`
| Prop | Type |
|------|------|
| `title` | `string` |
| `description?` | `string` |

#### `text-link.tsx`
| Prop | Type |
|------|------|
| `href` | `string` |
| `children` | `ReactNode` |

---

### Form & Feedback Components

#### `input-error.tsx`
| Prop | Type |
|------|------|
| `message?` | `string` |
| `className?` | `string` |

#### `alert-error.tsx`
| Prop | Type |
|------|------|
| `message` | `string` |

---

### Auth/Settings Components

#### `appearance-tabs.tsx`
No props — reads current theme from context, renders light/dark/system switcher.

#### `delete-user.tsx`
No props — self-contained delete account form with confirmation.

#### `two-factor-setup-modal.tsx`
| Prop | Type |
|------|------|
| `open` | `boolean` |
| `onClose` | `() => void` |
| `qrCode` | `string` (SVG) |
| `setupKey` | `string` |

#### `two-factor-recovery-codes.tsx`
| Prop | Type |
|------|------|
| `codes` | `string[]` |

---

### UI Primitives (`resources/js/components/ui/`)
shadcn/ui components — standard props per component. See [shadcn/ui docs](https://ui.shadcn.com).

| Component | Notes |
|-----------|-------|
| `alert.tsx` | `variant?: 'default' \| 'destructive'` |
| *(others)* | Standard shadcn/ui API |
