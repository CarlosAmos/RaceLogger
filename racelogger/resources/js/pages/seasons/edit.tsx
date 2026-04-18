import { useState } from 'react';
import { Head, useForm, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, X, Calendar, Pencil } from 'lucide-react';
import type { BreadcrumbItem } from '@/types';
import * as seasons from '@/routes/seasons';
import * as pointSystemRoutes from '@/routes/point-systems';
import entryCars from '@/routes/entry-cars';
import entryCarDriversRoutes from '@/routes/entry-cars/drivers';

interface Series {
    id: number;
    name: string;
}

interface TrackLayout {
    id: number;
    name: string;
    track: {
        name: string;
        city: string | null;
        country: { name: string } | null;
    };
}

interface PointSystem {
    id: number;
    name: string;
    rules: { id: number; type: string; position: number; points: number }[];
    bonus_rules: { id: number; type: string; points: number }[];
}

interface CalendarRace {
    id: number;
    track_layout_id: number;
    gp_name: string;
    race_code: string;
    race_date: string;
    sprint_race: number;
    endurance: number;
    number_of_races: number;
    point_system_id: number | null;
    layout: {
        id: number;
        name: string;
        track: {
            name: string;
            city: string | null;
            country: { name: string } | null;
        };
    };
}

interface SeasonClass {
    id: number;
    name: string;
    display_order: number;
}

interface Driver {
    id: number;
    first_name: string;
    last_name: string;
}

interface EntryCar {
    id: number;
    car_number: string;
    drivers: Driver[];
    car_model: { name: string; year: number } | null;
}

interface EntryClass {
    id: number;
    race_class: { id: number; name: string };
    entry_cars: EntryCar[];
}

interface SeasonEntry {
    id: number;
    display_name: string | null;
    entrant: { id: number; name: string } | null;
    constructor: { name: string } | null;
    entry_classes: EntryClass[];
}

interface Season {
    id: number;
    year: number;
    series_id: number;
    point_system_id: number | null;
    season_classes: SeasonClass[];
    season_entries: SeasonEntry[];
}

interface CircuitRow {
    layoutId: number;
    trackName: string;
    layoutName: string;
    city: string;
    country: string;
    gpName: string;
    raceCode: string;
    raceDate: string;
    sprintRace: boolean;
    endurance: boolean;
    numberOfRaces: number;
    pointSystemId: string;
}

interface Props {
    season: Season;
    series: Series[];
    seriesId: number;
    defaultYear: number;
    layouts: TrackLayout[];
    calendarRaces: CalendarRace[];
    pointSystems: PointSystem[];
    worlds: { id: number };
    tab: string;
}

type Tab = 'circuits' | 'classes' | 'teams' | 'points' | 'basic';

function circuitsFromCalendar(calendarRaces: CalendarRace[]): CircuitRow[] {
    return calendarRaces.map((race) => ({
        layoutId: race.track_layout_id,
        trackName: race.layout.track.name,
        layoutName: race.layout.name,
        city: race.layout.track.city ?? '',
        country: race.layout.track.country?.name ?? '',
        gpName: race.gp_name,
        raceCode: race.race_code,
        raceDate: race.race_date,
        sprintRace: race.sprint_race === 1,
        endurance: race.endurance === 1,
        numberOfRaces: race.number_of_races ?? 1,
        pointSystemId: race.point_system_id ? String(race.point_system_id) : '',
    }));
}

export default function SeasonEdit({
    season,
    series,
    seriesId,
    defaultYear,
    layouts,
    calendarRaces,
    pointSystems,
    worlds,
    tab: initialTab,
}: Props) {
    const [activeTab, setActiveTab] = useState<Tab>((initialTab as Tab) ?? 'circuits');
    const [teamSearch, setTeamSearch] = useState('');
    const [teamSort, setTeamSort] = useState<'latest' | 'az' | 'za'>('latest');
    const [circuits, setCircuits] = useState<CircuitRow[]>(() => circuitsFromCalendar(calendarRaces));
    const [classes, setClasses] = useState(() =>
        season.season_classes.map((c) => ({ name: c.name }))
    );

    const form = useForm({
        series_id: String(seriesId),
        year: String(defaultYear),
        point_system_id: season.point_system_id ? String(season.point_system_id) : '',
        circuits: [] as any[],
        classes: [] as string[],
    });

    function addCircuit(layout: TrackLayout) {
        setCircuits([
            ...circuits,
            {
                layoutId: layout.id,
                trackName: layout.track.name,
                layoutName: layout.name,
                city: layout.track.city ?? '',
                country: layout.track.country?.name ?? '',
                gpName: '',
                raceCode: '',
                raceDate: '',
                sprintRace: false,
                endurance: false,
                numberOfRaces: 1,
                pointSystemId: '',
            },
        ]);
    }

    function removeCircuit(index: number) {
        setCircuits(circuits.filter((_, i) => i !== index));
    }

    function updateCircuit(index: number, patch: Partial<CircuitRow>) {
        setCircuits(circuits.map((c, i) => (i === index ? { ...c, ...patch } : c)));
    }

    function addClass() {
        setClasses([...classes, { name: '' }]);
    }

    function updateClass(index: number, name: string) {
        setClasses(classes.map((c, i) => (i === index ? { name } : c)));
    }

    function removeClass(index: number) {
        setClasses(classes.filter((_, i) => i !== index));
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            circuits: circuits.map((c) => ({
                layout_id: c.layoutId,
                gp_name: c.gpName,
                race_code: c.raceCode.toUpperCase(),
                race_date: c.raceDate,
                sprint_race: c.sprintRace ? 1 : 0,
                endurance: c.endurance ? 1 : 0,
                number_of_races: c.numberOfRaces,
                point_system_id: c.pointSystemId || null,
            })),
            classes: classes.map((c) => c.name).filter(Boolean),
        }));
        form.put(seasons.update(season.id).url);
    }

    const selectedPs = pointSystems.find((ps) => String(ps.id) === form.data.point_system_id);
    const shortLayout = (name: string) => name.replace('Grand Prix', 'GP');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: `Season ${season.year}`, href: seasons.show(season.id).url },
        { title: 'Edit', href: seasons.edit(season.id).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Season ${season.year}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center gap-2">
                    <Calendar className="h-6 w-6" />
                    <h1 className="text-2xl font-semibold">Edit Season {season.year}</h1>
                </div>

                <form onSubmit={handleSubmit} className="flex flex-col gap-6">
                    {/* Tab Nav */}
                    <div className="flex gap-2 border-b border-border pb-1">
                        {(['circuits', 'classes', 'teams', 'points', 'basic'] as Tab[]).map((tab) => (
                            <button
                                key={tab}
                                type="button"
                                onClick={() => setActiveTab(tab)}
                                className={`rounded-t px-4 py-2 text-sm font-medium capitalize transition-colors ${
                                    activeTab === tab
                                        ? 'border-b-2 border-primary text-primary'
                                        : 'text-muted-foreground hover:text-foreground'
                                }`}
                            >
                                {tab === 'basic' ? 'Basic Info' : tab.charAt(0).toUpperCase() + tab.slice(1)}
                            </button>
                        ))}
                    </div>

                    {/* CIRCUITS TAB */}
                    {activeTab === 'circuits' && (
                        <div className="flex flex-col gap-4">
                            <h3 className="text-lg font-semibold">Season Calendar</h3>

                            {circuits.length === 0 && (
                                <p className="text-sm text-muted-foreground">No circuits added yet. Pick from the list below.</p>
                            )}

                            {circuits.map((c, i) => (
                                <div
                                    key={`${c.layoutId}-${i}`}
                                    className="rounded-xl border border-border bg-card p-4 shadow-sm"
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="text-lg font-bold">R{i + 1}</span>
                                            <Input
                                                value={c.gpName}
                                                onChange={(e) => updateCircuit(i, { gpName: e.target.value })}
                                                placeholder="Grand Prix Name"
                                                className="w-72"
                                            />
                                            <Input
                                                value={c.raceCode}
                                                onChange={(e) =>
                                                    updateCircuit(i, { raceCode: e.target.value.toUpperCase().slice(0, 3) })
                                                }
                                                placeholder="CODE"
                                                maxLength={3}
                                                className="w-20 text-center font-semibold uppercase"
                                            />
                                            <Input
                                                type="date"
                                                value={c.raceDate}
                                                onChange={(e) => updateCircuit(i, { raceDate: e.target.value })}
                                                className="w-44"
                                            />
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <span className="text-sm italic text-muted-foreground">
                                                {c.city}, {c.country}
                                            </span>
                                            <button
                                                type="button"
                                                onClick={() => removeCircuit(i)}
                                                className="rounded-full bg-destructive px-2 py-0.5 text-xs text-white"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                    <div className="mt-2 flex flex-wrap items-center justify-between gap-3 pl-8">
                                        <div className="flex items-center gap-4">
                                            <div className="flex items-center gap-2">
                                                <Label className="text-xs italic">Points</Label>
                                                <select
                                                    value={c.pointSystemId}
                                                    onChange={(e) => updateCircuit(i, { pointSystemId: e.target.value })}
                                                    className="rounded-full border border-border bg-background px-2 py-0.5 text-sm"
                                                >
                                                    <option value="">Season Default</option>
                                                    {pointSystems.map((ps) => (
                                                        <option key={ps.id} value={String(ps.id)}>
                                                            {ps.name}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                            <label className="flex items-center gap-1.5 text-sm">
                                                <input
                                                    type="checkbox"
                                                    checked={c.sprintRace}
                                                    onChange={(e) => updateCircuit(i, { sprintRace: e.target.checked })}
                                                />
                                                <span className="italic">Sprint Race</span>
                                            </label>
                                            <label className="flex items-center gap-1.5 text-sm">
                                                <input
                                                    type="checkbox"
                                                    checked={c.endurance}
                                                    onChange={(e) => updateCircuit(i, { endurance: e.target.checked })}
                                                />
                                                <span className="italic">Endurance</span>
                                            </label>
                                            <div className="flex items-center gap-1.5 text-sm">
                                                <span className="italic">Races</span>
                                                <input
                                                    type="number"
                                                    min={1}
                                                    max={99}
                                                    value={c.numberOfRaces}
                                                    onChange={(e) => updateCircuit(i, { numberOfRaces: Math.max(1, parseInt(e.target.value) || 1) })}
                                                    className="w-14 rounded-md border border-border bg-background px-2 py-0.5 text-center text-sm"
                                                />
                                            </div>
                                        </div>
                                        <span className="text-sm italic text-muted-foreground">
                                            {shortLayout(c.layoutName)} ({c.trackName})
                                        </span>
                                    </div>
                                </div>
                            ))}

                            <hr className="border-border" />
                            <h4 className="font-medium">Add Circuits</h4>
                            {(() => {
                                const grouped = layouts.reduce<Record<string, TrackLayout[]>>((acc, layout) => {
                                    const country = layout.track.country?.name ?? 'Unknown';
                                    (acc[country] ??= []).push(layout);
                                    return acc;
                                }, {});
                                return Object.keys(grouped).sort().map((country) => (
                                    <div key={country}>
                                        <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">{country}</p>
                                        <div className="flex flex-wrap gap-3">
                                            {grouped[country]
                                                .sort((a, b) => a.track.name.localeCompare(b.track.name))
                                                .map((layout) => (
                                                    <button
                                                        key={layout.id}
                                                        type="button"
                                                        onClick={() => addCircuit(layout)}
                                                        className="w-48 cursor-pointer rounded-lg border border-border bg-card p-2.5 text-left transition-colors hover:border-primary hover:bg-accent"
                                                    >
                                                        <strong className="block text-sm">{layout.track.name}</strong>
                                                        <small className="text-xs text-muted-foreground">{layout.name}</small>
                                                    </button>
                                                ))}
                                        </div>
                                    </div>
                                ));
                            })()}
                        </div>
                    )}

                    {/* CLASSES TAB */}
                    {activeTab === 'classes' && (
                        <div className="flex flex-col gap-4">
                            <h3 className="text-lg font-semibold">Season Classes</h3>
                            {classes.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    No classes defined. A default "Overall" class will be used.
                                </p>
                            )}
                            {classes.map((c, i) => (
                                <div key={i} className="flex items-center gap-2">
                                    <Input
                                        value={c.name}
                                        onChange={(e) => updateClass(i, e.target.value)}
                                        placeholder="Class name"
                                        className="w-64"
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => removeClass(i)}
                                    >
                                        <X className="h-4 w-4" />
                                    </Button>
                                </div>
                            ))}
                            <Button type="button" variant="outline" size="sm" onClick={addClass} className="w-fit">
                                <Plus className="mr-1 h-4 w-4" />
                                Add Class
                            </Button>
                        </div>
                    )}

                    {/* TEAMS TAB */}
                    {activeTab === 'teams' && (
                        <div className="flex flex-col gap-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-semibold">Teams</h3>
                                <Button asChild variant="outline" size="sm">
                                    <Link href={`/worlds/${worlds.id}/seasons/${season.id}/season-entries/create`}>
                                        <Plus className="mr-1 h-4 w-4" />
                                        Add Team
                                    </Link>
                                </Button>
                            </div>

                            {season.season_entries.length > 0 && (
                                <div className="flex items-center gap-2">
                                    <Input
                                        placeholder="Search teams..."
                                        value={teamSearch}
                                        onChange={(e) => setTeamSearch(e.target.value)}
                                        className="max-w-sm"
                                    />
                                    <select
                                        value={teamSort}
                                        onChange={(e) => setTeamSort(e.target.value as 'latest' | 'az' | 'za')}
                                        className="rounded-md border border-border bg-background px-3 py-2 text-sm"
                                    >
                                        <option value="latest">Latest</option>
                                        <option value="az">A → Z</option>
                                        <option value="za">Z → A</option>
                                    </select>
                                </div>
                            )}

                            {season.season_entries.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No teams entered yet.</p>
                            ) : (
                                <div className="flex flex-col gap-3">
                                    {season.season_entries.filter((entry) => {
                                        const q = teamSearch.toLowerCase();
                                        if (!q) return true;
                                        if ((entry.display_name ?? '').toLowerCase().includes(q)) return true;
                                        if ((entry.entrant?.name ?? '').toLowerCase().includes(q)) return true;
                                        return entry.entry_classes.some((ec) =>
                                            ec.entry_cars.some((car) =>
                                                car.car_number.toLowerCase().includes(q) ||
                                                car.drivers.some((d) =>
                                                    `${d.first_name} ${d.last_name}`.toLowerCase().includes(q)
                                                )
                                            )
                                        );
                                    }).sort((a, b) => {
                                        if (teamSort === 'latest') return b.id - a.id;
                                        const nameA = (a.display_name ?? a.entrant?.name ?? '').toLowerCase();
                                        const nameB = (b.display_name ?? b.entrant?.name ?? '').toLowerCase();
                                        return teamSort === 'az' ? nameA.localeCompare(nameB) : nameB.localeCompare(nameA);
                                    }).map((entry) => (
                                        <div key={entry.id} className="rounded-xl border border-border bg-card p-4">
                                            <div className="flex items-center justify-between border-b border-border pb-2 mb-3">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-semibold">
                                                        {entry.display_name ?? entry.entrant?.name}
                                                    </span>
                                                    {entry.display_name && (
                                                        <span className="text-xs italic text-muted-foreground">
                                                            {entry.entrant?.name}
                                                        </span>
                                                    )}
                                                    <Link
                                                        href={entryCars.create_entry({ world: worlds.id, season: season.id, seasonEntry: entry.id }).url}
                                                        className="flex h-5 w-5 items-center justify-center rounded-full border border-green-600 text-green-600 hover:bg-green-600 hover:text-white text-xs font-bold leading-none"
                                                    >
                                                        +
                                                    </Link>
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    {entry.constructor && (
                                                        <span className="text-sm text-muted-foreground">
                                                            {entry.constructor.name}
                                                        </span>
                                                    )}
                                                    <button
                                                        type="button"
                                                        title="Remove team from season"
                                                        className="flex items-center gap-1 text-xs text-muted-foreground hover:text-destructive"
                                                        onClick={() => {
                                                            if (!confirm(`Remove "${entry.display_name ?? entry.entrant?.name}" from this season? This will also delete all their cars and driver assignments.`)) return;
                                                            router.delete(
                                                                `/worlds/${worlds.id}/seasons/${season.id}/season-entries/${entry.id}`,
                                                                { preserveScroll: true }
                                                            );
                                                        }}
                                                    >
                                                        <X className="h-3.5 w-3.5" />
                                                        <span>Remove team</span>
                                                    </button>
                                                </div>
                                            </div>
                                            <div className="flex flex-wrap gap-3">
                                                {entry.entry_classes.flatMap((ec) =>
                                                    ec.entry_cars.map((car) => (
                                                        <div
                                                            key={car.id}
                                                            className="relative flex flex-col items-center rounded-xl border border-border bg-background p-3 w-44"
                                                        >
                                                            <button
                                                                type="button"
                                                                title="Remove car"
                                                                className="absolute top-1.5 right-1.5 text-muted-foreground hover:text-destructive"
                                                                onClick={() => {
                                                                    if (!confirm(`Remove car #${car.car_number} from this entry?`)) return;
                                                                    router.delete(
                                                                        `/worlds/${worlds.id}/seasons/${season.id}/season-entries/${entry.id}/entry-classes/${ec.id}/entry-cars/${car.id}`,
                                                                        { preserveScroll: true }
                                                                    );
                                                                }}
                                                            >
                                                                <X className="h-3 w-3" />
                                                            </button>
                                                            <span className="text-xs italic text-muted-foreground mb-1">
                                                                {ec.race_class.name}
                                                            </span>
                                                            {car.car_model && (
                                                                <>
                                                                    <strong className="text-sm text-center leading-tight">
                                                                        {car.car_model.name}
                                                                    </strong>
                                                                    <span className="text-xs italic text-muted-foreground mb-1">
                                                                        {car.car_model.year}
                                                                    </span>
                                                                </>
                                                            )}
                                                            <span className="text-3xl font-bold">
                                                                #{car.car_number}
                                                            </span>
                                                            <div className="mt-1 flex items-center gap-2">
                                                                <Link
                                                                    href={entryCarDriversRoutes.edit({ world: worlds.id, season: season.id, seasonEntry: entry.id, entryClass: ec.id, entryCar: car.id }).url}
                                                                    className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                                                                >
                                                                    <Pencil className="h-3 w-3" /> Drivers
                                                                </Link>
                                                                {car.drivers.length > 0 && (
                                                                    <button
                                                                        type="button"
                                                                        title="Clear all drivers"
                                                                        className="flex items-center text-muted-foreground hover:text-destructive"
                                                                        onClick={() => {
                                                                            if (!confirm(`Clear all drivers from car #${car.car_number}?`)) return;
                                                                            router.post(
                                                                                entryCarDriversRoutes.update({ world: worlds.id, season: season.id, seasonEntry: entry.id, entryClass: ec.id, entryCar: car.id }).url,
                                                                                { drivers: [] },
                                                                                { preserveScroll: true }
                                                                            );
                                                                        }}
                                                                    >
                                                                        <X className="h-3 w-3" />
                                                                    </button>
                                                                )}
                                                            </div>
                                                            <div className="mt-2 flex flex-col items-center gap-0.5">
                                                                {car.drivers.length > 0 ? (
                                                                    car.drivers.map((d) => (
                                                                        <span
                                                                            key={d.id}
                                                                            className="text-xs rounded-full bg-muted px-2 py-0.5"
                                                                        >
                                                                            {d.first_name} {d.last_name}
                                                                        </span>
                                                                    ))
                                                                ) : (
                                                                    <span className="text-xs text-muted-foreground italic">
                                                                        No drivers
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    ))
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {/* POINTS TAB */}
                    {activeTab === 'points' && (
                        <div className="flex flex-col gap-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-semibold">Default Points System</h3>
                                <Button asChild variant="outline" size="sm">
                                    <Link href={pointSystemRoutes.create().url}>
                                        <Plus className="mr-1 h-4 w-4" />
                                        Create Points System
                                    </Link>
                                </Button>
                            </div>
                            <div className="flex items-center gap-3">
                                <select
                                    value={form.data.point_system_id}
                                    onChange={(e) => form.setData('point_system_id', e.target.value)}
                                    className="rounded-md border border-border bg-background px-3 py-2 text-sm"
                                >
                                    <option value="">-- No Points System --</option>
                                    {pointSystems.map((ps) => (
                                        <option key={ps.id} value={String(ps.id)}>
                                            {ps.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {selectedPs && (
                                <div className="rounded-lg border border-border bg-card p-4">
                                    <strong className="block mb-2 text-sm">Race Points</strong>
                                    <ul className="text-sm space-y-1">
                                        {selectedPs.rules
                                            .filter((r) => r.type === 'race')
                                            .sort((a, b) => a.position - b.position)
                                            .map((r) => (
                                                <li key={r.id}>
                                                    P{r.position} → {r.points} pts
                                                </li>
                                            ))}
                                    </ul>
                                </div>
                            )}
                        </div>
                    )}

                    {/* BASIC INFO TAB */}
                    {activeTab === 'basic' && (
                        <div className="flex flex-col gap-4 max-w-sm">
                            <h3 className="text-lg font-semibold">Basic Info</h3>

                            <div className="flex flex-col gap-1.5">
                                <Label>Series</Label>
                                <select
                                    value={form.data.series_id}
                                    onChange={(e) => form.setData('series_id', e.target.value)}
                                    className="rounded-md border border-border bg-background px-3 py-2 text-sm"
                                    required
                                >
                                    {series.map((s) => (
                                        <option key={s.id} value={String(s.id)}>
                                            {s.name}
                                        </option>
                                    ))}
                                </select>
                                {form.errors.series_id && (
                                    <p className="text-sm text-destructive">{form.errors.series_id}</p>
                                )}
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label>Season Year</Label>
                                <Input
                                    type="number"
                                    value={form.data.year}
                                    onChange={(e) => form.setData('year', e.target.value)}
                                    min={1900}
                                    max={2100}
                                    required
                                />
                                {form.errors.year && (
                                    <p className="text-sm text-destructive">{form.errors.year}</p>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Error messages */}
                    {(form.errors.circuits || form.errors.classes) && (
                        <div className="rounded-md border border-destructive bg-destructive/10 p-3 text-sm text-destructive">
                            {form.errors.circuits && <p>{form.errors.circuits}</p>}
                            {form.errors.classes && <p>{form.errors.classes}</p>}
                        </div>
                    )}

                    {activeTab !== 'teams' && (
                        <div className="flex items-center gap-3 border-t border-border pt-4">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? 'Saving...' : 'Update Season'}
                            </Button>
                        </div>
                    )}
                </form>
            </div>
        </AppLayout>
    );
}
