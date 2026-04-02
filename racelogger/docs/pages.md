# pages.md — View / Page Tree

> All Blade views. "Full page" = extends a layout. "Partial" = included/yielded fragment.

## Layouts

| File | Purpose |
|------|---------|
| `resources/views/layouts/app.blade.php` | Primary app shell (nav, content slot) |
| `resources/views/app.blade.php` | Inertia/React root entry point |

---

## Full Pages (server-rendered Blade)

### Dashboard & Records
| File | Route | Notes |
|------|-------|-------|
| `resources/views/dashboard/index.blade.php` | `GET /dashboard` | Main dashboard with career table, calendar, series grid |
| `resources/views/lap-records/index.blade.php` | `GET /records` | Track lap record table |

### World Management
| File | Route |
|------|-------|
| `resources/views/worlds/index.blade.php` | `GET /` (world select) |
| `resources/views/worlds/create.blade.php` | `GET /worlds/create` |
| `resources/views/worlds/created.blade.php` | `GET /worlds/{world}/created` |

### Series & Seasons
| File | Route |
|------|-------|
| `resources/views/series/index.blade.php` | `GET /series` |
| `resources/views/series/create.blade.php` | `GET /series/create` |
| `resources/views/series/created.blade.php` | `GET /series/{series}/created` |
| `resources/views/seasons/create.blade.php` | `GET /seasons/create` |
| `resources/views/seasons/show.blade.php` | `GET /seasons/{season}` |

### Constructors & Vehicles
| File | Route |
|------|-------|
| `resources/views/constructors/index.blade.php` | `GET /worlds/{world}/constructors` |
| `resources/views/constructors/create.blade.php` | `GET /worlds/{world}/constructors/create` |
| `resources/views/car-models/index.blade.php` | `GET .../car-models` |
| `resources/views/car-models/create.blade.php` | `GET .../car-models/create` |
| `resources/views/engines/index.blade.php` | `GET /worlds/{world}/engines` |
| `resources/views/engines/create.blade.php` | `GET /worlds/{world}/engines/create` |

### Entry Management
| File | Route |
|------|-------|
| `resources/views/entrants/create.blade.php` | `GET /worlds/{world}/entrants/create` |
| `resources/views/season_entries/index.blade.php` | `GET .../season-entries` |
| `resources/views/season_entries/create.blade.php` | `GET .../season-entries/create` |
| `resources/views/entry-cars/index.blade.php` | `GET .../entry-cars` |
| `resources/views/entry-cars/create.blade.php` | `GET .../entry-cars/create` |
| `resources/views/entry-cars/create_entry.blade.php` | `GET .../entry_create` |
| `resources/views/entry-car-drivers/edit.blade.php` | `GET .../drivers` |

### Race Weekend
| File | Route |
|------|-------|
| `resources/views/races/weekend/manage.blade.php` | `GET /races/{race}` |

### Tracks
| File | Route |
|------|-------|
| `resources/views/tracks/index.blade.php` | `GET /tracks` |

### Points
| File | Route |
|------|-------|
| `resources/views/point-systems/create.blade.php` | `GET /point-systems/create` |

---

## Partials (server-side includes)

| File | Included By | Purpose |
|------|------------|---------|
| `resources/views/dashboard/partial/season.blade.php` | `dashboard/index` | Career summary table + per-series results grid |
| `resources/views/races/weekend/partials/participants.blade.php` | `races/weekend/manage` | Entry list for a race weekend |
| `resources/views/races/weekend/partials/qualifying.blade.php` | `races/weekend/manage` | Qualifying result entry form |
| `resources/views/races/weekend/partials/race-results.blade.php` | `races/weekend/manage` | Feature race result entry form |
| `resources/views/races/weekend/partials/sprint-race-results.blade.php` | `races/weekend/manage` | Sprint race result entry form |
| `resources/views/seasons/form.blade.php` | `seasons/*` | Shared season create/edit form fields |
| `resources/views/tracks/form.blade.php` | `tracks/*` | Shared track form fields |
| `resources/views/track_layouts/form.blade.php` | `track_layouts/*` | Shared track layout form fields |

---

## Frontend (Inertia + React/TypeScript)

> Located under `resources/js/`. Rendered client-side via Inertia SSR bridge.

### Pages (`resources/js/pages/`)
| Path | Notes |
|------|-------|
| `auth/*` | Login, register, password reset, email verify |
| `settings/*` | Profile, password, appearance, 2FA |

### Layouts (`resources/js/layouts/`)
| Path | Notes |
|------|-------|
| `app/` | Authenticated app shell |
| `auth/` | Unauthenticated (login/register) shell |
| `settings/` | Settings sub-layout |

### Shared Components (`resources/js/components/`)
| File | Purpose |
|------|---------|
| `app-shell.tsx` | Top-level app wrapper |
| `app-header.tsx` | Top navigation bar |
| `app-sidebar.tsx` | Side navigation |
| `app-sidebar-header.tsx` | Sidebar logo/brand section |
| `app-content.tsx` | Main content area wrapper |
| `app-logo.tsx` / `app-logo-icon.tsx` | Logo variants |
| `nav-main.tsx` | Primary nav links |
| `nav-footer.tsx` | Bottom nav items |
| `nav-user.tsx` | User avatar/menu in nav |
| `breadcrumbs.tsx` | Page breadcrumb trail |
| `heading.tsx` | Page heading component |
| `text-link.tsx` | Styled anchor |
| `input-error.tsx` | Form field error display |
| `alert-error.tsx` | Alert banner |
| `appearance-tabs.tsx` | Theme switcher tabs |
| `delete-user.tsx` | Account deletion form |
| `two-factor-setup-modal.tsx` | 2FA setup dialog |
| `two-factor-recovery-codes.tsx` | 2FA codes display |
| `ui/*` | shadcn/ui primitives (alert, button, etc.) |
