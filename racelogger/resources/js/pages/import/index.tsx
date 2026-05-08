import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { AlertCircle, CheckCircle2, ChevronDown, ChevronRight, Download, Loader2, Search } from 'lucide-react';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Import F1 Data', href: '/import' },
];

interface Race {
    id: number;
    round: number;
    name: string;
    date: string | null;
    circuit_name: string | null;
    track_found: boolean;
}

interface Constructor {
    id: number;
    name: string;
    drivers: string[];
}

interface Layout {
    id: number;
    track_name: string;
    layout_name: string;
    active_from: string | null;
    active_to: string | null;
}

interface Preview {
    races: Race[];
    constructors: Constructor[];
    drivers: never[];
    missing_files: string[];
    layouts: Layout[];
}

interface FlashProps {
    import_success?: boolean;
    import_output?: string;
    import_step?: string;
}

interface Props {
    year: number;
    preview: Preview | null;
}

export default function ImportIndex({ year, preview }: Props) {
    const { props } = usePage<{ flash: FlashProps }>();
    const flash = props.flash ?? {};

    const [yearInput, setYearInput] = useState(year > 0 ? String(year) : '');
    const [expandedConstructors, setExpandedConstructors] = useState<Set<number>>(new Set());
    const [isRunning, setIsRunning] = useState(false);
    const [runningStep, setRunningStep] = useState<'seasons' | 'calendar' | 'entries' | 'results' | null>(null);
    const [layoutAssignments, setLayoutAssignments] = useState<Record<number, number>>({});

    // Track which steps completed successfully this session (reset on year change)
    const completedSteps = new Set<string>(
        flash.import_success && flash.import_step ? [flash.import_step] : []
    );

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        const y = parseInt(yearInput, 10);
        if (y >= 1950 && y <= 2099) {
            router.get('/import', { year: y });
        }
    }

    function runStep(step: 'seasons' | 'calendar' | 'entries' | 'results') {
        setIsRunning(true);
        setRunningStep(step);
        const body: Record<string, unknown> = { year, step };
        if (step === 'calendar') body.layout_assignments = layoutAssignments;
        router.post(
            '/import/run',
            body,
            {
                onFinish: () => { setIsRunning(false); setRunningStep(null); },
                preserveScroll: true,
            }
        );
    }

    function toggleConstructor(id: number) {
        setExpandedConstructors(prev => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id);
            else next.add(id);
            return next;
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Import F1 Data" />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold">F1 Data Import</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Preview CSV data from <code>storage/app/f1_import/</code> and run import commands.
                    </p>
                </div>

                {/* Year picker */}
                <form onSubmit={handleSearch} className="flex items-center gap-3">
                    <Input
                        type="number"
                        min={1950}
                        max={2099}
                        placeholder="Year (e.g. 2023)"
                        value={yearInput}
                        onChange={e => setYearInput(e.target.value)}
                        className="w-40"
                    />
                    <Button type="submit" variant="secondary">
                        <Search className="mr-2 h-4 w-4" />
                        Preview
                    </Button>
                </form>

                {/* Flash output */}
                {flash.import_output && (
                    <div
                        className={`rounded-lg border p-4 ${
                            flash.import_success
                                ? 'border-green-500/30 bg-green-500/10'
                                : 'border-red-500/30 bg-red-500/10'
                        }`}
                    >
                        <div className="mb-2 flex items-center gap-2 font-semibold">
                            {flash.import_success ? (
                                <CheckCircle2 className="h-4 w-4 text-green-500" />
                            ) : (
                                <AlertCircle className="h-4 w-4 text-red-500" />
                            )}
                            {flash.import_success ? 'Import completed' : 'Import failed'}{' '}
                            {flash.import_step && `— step: ${flash.import_step}`}
                        </div>
                        <pre className="overflow-x-auto whitespace-pre-wrap text-xs opacity-80">
                            {flash.import_output}
                        </pre>
                    </div>
                )}

                {/* Missing files warning */}
                {preview && preview.missing_files.length > 0 && (
                    <div className="flex items-start gap-3 rounded-lg border border-yellow-500/30 bg-yellow-500/10 p-4">
                        <AlertCircle className="mt-0.5 h-5 w-5 shrink-0 text-yellow-500" />
                        <div>
                            <p className="font-semibold">Missing CSV files</p>
                            <ul className="mt-1 list-inside list-disc text-sm opacity-80">
                                {preview.missing_files.map(f => (
                                    <li key={f}>{f}</li>
                                ))}
                            </ul>
                            <p className="mt-2 text-sm opacity-70">
                                Place Ergast dataset CSVs in <code>storage/app/f1_import/</code> and reload.
                            </p>
                        </div>
                    </div>
                )}

                {/* Preview sections */}
                {preview && preview.missing_files.length === 0 && year > 0 && (
                    <>
                        {/* Import actions */}
                        <div className="rounded-lg border p-4">
                            <h2 className="mb-3 font-semibold">Run Import — {year}</h2>
                            <p className="text-muted-foreground mb-4 text-sm">
                                Run each step in order. Seasons must exist before entries; entries before results.
                            </p>
                            <div className="flex flex-wrap gap-2">
                                {(
                                    [
                                        { step: 'seasons',  label: '1. Import Seasons' },
                                        { step: 'calendar', label: '2. Import Calendar' },
                                        { step: 'entries',  label: '3. Import Entries' },
                                        { step: 'results',  label: '4. Import Results' },
                                    ] as const
                                ).map(({ step, label }) => {
                                    const done = completedSteps.has(step);
                                    const spinning = runningStep === step;
                                    return (
                                        <Button
                                            key={step}
                                            onClick={() => runStep(step)}
                                            disabled={isRunning || done}
                                            variant={done ? 'default' : 'outline'}
                                            className={done ? 'border-green-600 bg-green-600 text-white hover:bg-green-600 disabled:opacity-100' : ''}
                                        >
                                            {spinning ? (
                                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                            ) : done ? (
                                                <CheckCircle2 className="mr-2 h-4 w-4" />
                                            ) : (
                                                <Download className="mr-2 h-4 w-4" />
                                            )}
                                            {label}
                                        </Button>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Calendar */}
                        {preview.races.length > 0 && (
                            <div className="rounded-lg border">
                                <div className="border-b px-4 py-3">
                                    <h2 className="flex items-center gap-2 font-semibold">
                                        Calendar
                                        <Badge variant="secondary">{preview.races.length} rounds</Badge>
                                        {preview.races.filter(r => !r.track_found).length > 0 && (
                                            <Badge variant="destructive">
                                                {preview.races.filter(r => !r.track_found).length} missing track{preview.races.filter(r => !r.track_found).length !== 1 ? 's' : ''}
                                            </Badge>
                                        )}
                                    </h2>
                                </div>
                                <div className="divide-y">
                                    {preview.races.map(race => {
                                        const assigned = layoutAssignments[race.id];
                                        const assignedLayout = assigned
                                            ? preview.layouts.find(l => l.id === assigned)
                                            : null;
                                        const missing = !race.track_found && !assignedLayout;

                                        return (
                                            <div
                                                key={race.id}
                                                className={`flex items-start gap-4 px-4 py-2.5 text-sm ${
                                                    missing
                                                        ? 'border-l-2 border-red-500 bg-red-500/10'
                                                        : assignedLayout
                                                        ? 'border-l-2 border-green-500 bg-green-500/10'
                                                        : ''
                                                }`}
                                            >
                                                <span className="text-muted-foreground mt-0.5 w-8 shrink-0 text-right font-mono">
                                                    R{race.round}
                                                </span>
                                                <div className="flex flex-1 flex-col gap-1">
                                                    <span className="font-medium">{race.name}</span>
                                                    {race.circuit_name && (
                                                        <span className={`text-xs ${missing ? 'text-red-500' : 'text-muted-foreground'}`}>
                                                            {race.circuit_name}
                                                            {missing && ' — track not found'}
                                                        </span>
                                                    )}
                                                    {!race.track_found && (
                                                        <select
                                                            className="mt-1 w-full max-w-sm rounded border bg-background px-2 py-1 text-xs"
                                                            value={assigned ?? ''}
                                                            onChange={e => {
                                                                const val = Number(e.target.value);
                                                                setLayoutAssignments(prev => {
                                                                    const next = { ...prev };
                                                                    if (val) next[race.id] = val;
                                                                    else delete next[race.id];
                                                                    return next;
                                                                });
                                                            }}
                                                        >
                                                            <option value="">— assign a track layout —</option>
                                                            {preview.layouts.map(l => (
                                                                <option key={l.id} value={l.id}>
                                                                    {l.track_name}
                                                                    {l.layout_name ? ` · ${l.layout_name}` : ''}
                                                                    {l.active_from || l.active_to
                                                                        ? ` (${l.active_from ?? '?'}–${l.active_to ?? 'now'})`
                                                                        : ''}
                                                                </option>
                                                            ))}
                                                        </select>
                                                    )}
                                                </div>
                                                {race.date && (
                                                    <span className="text-muted-foreground mt-0.5 shrink-0">{race.date}</span>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

                        {/* Constructors + drivers */}
                        {preview.constructors.length > 0 && (
                            <div className="rounded-lg border">
                                <div className="border-b px-4 py-3">
                                    <h2 className="font-semibold">
                                        Teams &amp; Drivers{' '}
                                        <Badge variant="secondary" className="ml-1">
                                            {preview.constructors.length} teams
                                        </Badge>
                                    </h2>
                                </div>
                                <div className="divide-y">
                                    {preview.constructors.map(constructor => {
                                        const expanded = expandedConstructors.has(constructor.id);
                                        return (
                                            <div key={constructor.id}>
                                                <button
                                                    type="button"
                                                    onClick={() => toggleConstructor(constructor.id)}
                                                    className="hover:bg-accent flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition-colors"
                                                >
                                                    {expanded ? (
                                                        <ChevronDown className="h-4 w-4 shrink-0" />
                                                    ) : (
                                                        <ChevronRight className="h-4 w-4 shrink-0" />
                                                    )}
                                                    <span className="flex-1 font-medium">
                                                        {constructor.name}
                                                    </span>
                                                    <Badge variant="outline" className="ml-auto">
                                                        {constructor.drivers.length} driver
                                                        {constructor.drivers.length !== 1 ? 's' : ''}
                                                    </Badge>
                                                </button>
                                                {expanded && (
                                                    <div className="bg-muted/30 border-t px-4 py-2">
                                                        <ul className="space-y-1">
                                                            {constructor.drivers.map(driver => (
                                                                <li
                                                                    key={driver}
                                                                    className="text-muted-foreground text-sm"
                                                                >
                                                                    {driver}
                                                                </li>
                                                            ))}
                                                        </ul>
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

                        {preview.races.length === 0 && (
                            <p className="text-muted-foreground text-sm">
                                No races found for {year} in races.csv.
                            </p>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
