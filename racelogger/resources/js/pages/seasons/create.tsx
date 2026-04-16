import { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, X, Calendar } from 'lucide-react';
import type { BreadcrumbItem } from '@/types';
import * as seasons from '@/routes/seasons';
import * as pointSystemRoutes from '@/routes/point-systems';

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

interface ClassRow {
    name: string;
}

interface Props {
    series: Series[];
    seriesId: number | null;
    defaultYear: number;
    layouts: TrackLayout[];
    pointSystems: PointSystem[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Create Season', href: '/seasons/create' },
];

type Tab = 'circuits' | 'classes' | 'points' | 'basic';

export default function SeasonCreate({ series, seriesId, defaultYear, layouts, pointSystems }: Props) {
    const [activeTab, setActiveTab] = useState<Tab>('circuits');
    const [circuits, setCircuits] = useState<CircuitRow[]>([]);
    const [classes, setClasses] = useState<ClassRow[]>([]);

    const form = useForm({
        series_id: String(seriesId ?? series[0]?.id ?? ''),
        year: String(defaultYear),
        point_system_id: '',
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
        form.post(seasons.store().url);
    }

    const selectedPs = pointSystems.find((ps) => String(ps.id) === form.data.point_system_id);

    const shortLayout = (name: string) => name.replace('Grand Prix', 'GP');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Season" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center gap-2">
                    <Calendar className="h-6 w-6" />
                    <h1 className="text-2xl font-semibold">Create Season</h1>
                </div>

                <form onSubmit={handleSubmit} className="flex flex-col gap-6">
                    {/* Tab Nav */}
                    <div className="flex gap-2 border-b border-border pb-1">
                        {(['circuits', 'classes', 'points', 'basic'] as Tab[]).map((tab) => (
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
                                    key={i}
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

                    <div className="flex items-center gap-3 border-t border-border pt-4">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Creating...' : 'Create Season'}
                        </Button>
                        <span className="text-sm text-muted-foreground">
                            {circuits.length} circuit{circuits.length !== 1 ? 's' : ''} · {classes.length || '1 (default)'} class{classes.length !== 1 ? 'es' : ''}
                        </span>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
