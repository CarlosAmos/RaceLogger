import { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { ChevronDown, ChevronRight, Trophy } from 'lucide-react';
import type { BreadcrumbItem } from '@/types';

interface RecordRow {
    name: string;
    value: string;
    extra: string | null;
}

interface RecordsSection {
    [key: string]: RecordRow[];
}

interface RecordsData {
    entries: RecordsSection;
    wins: RecordsSection;
    poles: RecordsSection;
    fastest_laps: RecordsSection;
    podiums: RecordsSection;
    points: RecordsSection;
    race_finishes: RecordsSection;
    championships: RecordsSection;
}

interface Props {
    records: RecordsData | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Records', href: '/lap-records' },
];

const SECTION_CONFIGS: {
    key: keyof RecordsData;
    label: string;
    cards: { key: string; label: string }[];
}[] = [
    {
        key: 'entries',
        label: 'Entries',
        cards: [
            { key: 'total', label: 'Most Entries' },
            { key: 'consecutive_entries', label: 'Consecutive Entries' },
            { key: 'consecutive_starts', label: 'Consecutive Starts' },
            { key: 'one_constructor', label: 'Entries with One Constructor' },
            { key: 'youngest', label: 'Youngest at First Entry' },
            { key: 'oldest', label: 'Oldest at First Entry' },
        ],
    },
    {
        key: 'wins',
        label: 'Race Wins',
        cards: [
            { key: 'total', label: 'Most Wins' },
            { key: 'percentage', label: 'Highest Win %' },
            { key: 'consecutive', label: 'Consecutive Wins' },
            { key: 'in_season', label: 'Wins in One Season' },
            { key: 'pct_in_season', label: 'Win % in One Season' },
            { key: 'single_constructor', label: 'Wins with One Constructor' },
            { key: 'first_season', label: 'Wins in First Season' },
            { key: 'races_before_win', label: 'Races Before First Win' },
            { key: 'at_same_gp', label: 'Wins at Same GP' },
            { key: 'youngest', label: 'Youngest Winner' },
            { key: 'oldest', label: 'Oldest Winner' },
            { key: 'without_win', label: 'Races Without a Win' },
        ],
    },
    {
        key: 'poles',
        label: 'Pole Positions',
        cards: [
            { key: 'total', label: 'Most Poles' },
            { key: 'percentage', label: 'Highest Pole %' },
            { key: 'consecutive', label: 'Consecutive Poles' },
            { key: 'in_season', label: 'Poles in One Season' },
            { key: 'pct_in_season', label: 'Pole % in One Season' },
            { key: 'at_same_gp', label: 'Poles at Same GP' },
            { key: 'youngest', label: 'Youngest Pole Sitter' },
            { key: 'oldest', label: 'Oldest Pole Sitter' },
        ],
    },
    {
        key: 'fastest_laps',
        label: 'Fastest Laps',
        cards: [
            { key: 'total', label: 'Most Fastest Laps' },
            { key: 'percentage', label: 'Highest Fastest Lap %' },
            { key: 'consecutive', label: 'Consecutive Fastest Laps' },
            { key: 'in_season', label: 'Fastest Laps in One Season' },
            { key: 'pct_in_season', label: 'Fastest Lap % in One Season' },
            { key: 'at_same_gp', label: 'Fastest Laps at Same GP' },
            { key: 'youngest', label: 'Youngest Fastest Lap' },
            { key: 'oldest', label: 'Oldest Fastest Lap' },
        ],
    },
    {
        key: 'podiums',
        label: 'Podiums',
        cards: [
            { key: 'total', label: 'Most Podiums' },
            { key: 'percentage', label: 'Highest Podium %' },
            { key: 'consecutive', label: 'Consecutive Podiums' },
            { key: 'in_season', label: 'Podiums in One Season' },
            { key: 'pct_in_season', label: 'Podium % in One Season' },
            { key: 'at_same_gp', label: 'Podiums at Same GP' },
            { key: 'youngest', label: 'Youngest Podium' },
            { key: 'oldest', label: 'Oldest Podium' },
        ],
    },
    {
        key: 'points',
        label: 'Points',
        cards: [
            { key: 'total', label: 'Most Points' },
            { key: 'in_season', label: 'Points in One Season' },
            { key: 'consecutive', label: 'Consecutive Points Finishes' },
            { key: 'youngest', label: 'Youngest Points Finish' },
            { key: 'oldest', label: 'Oldest Points Finish' },
        ],
    },
    {
        key: 'race_finishes',
        label: 'Race Finishes',
        cards: [
            { key: 'total', label: 'Most Race Finishes' },
            { key: 'consecutive', label: 'Consecutive Finishes' },
        ],
    },
    {
        key: 'championships',
        label: 'Drivers Championships',
        cards: [
            { key: 'total', label: 'Most Championships' },
            { key: 'youngest', label: 'Youngest Champion' },
            { key: 'oldest', label: 'Oldest Champion' },
        ],
    },
];

function RecordCard({ title, rows }: { title: string; rows: RecordRow[] }) {
    if (!rows || rows.length === 0) return null;

    return (
        <div className="rounded-lg border border-border bg-card shadow-sm overflow-hidden">
            <div className="bg-muted/50 px-3 py-2 border-b border-border">
                <h4 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{title}</h4>
            </div>
            <table className="w-full text-sm">
                <tbody>
                    {rows.map((row, i) => (
                        <tr key={i} className={i % 2 === 0 ? 'bg-background' : 'bg-muted/20'}>
                            <td className="w-7 px-2 py-1.5 text-center text-xs font-bold text-muted-foreground">
                                {i + 1}
                            </td>
                            <td className="px-2 py-1.5">
                                <span className="font-medium">{row.name}</span>
                                {row.extra && (
                                    <span className="block text-xs text-muted-foreground">{row.extra}</span>
                                )}
                            </td>
                            <td className="px-2 py-1.5 text-right font-semibold tabular-nums">
                                {row.value}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function AccordionSection({
    sectionKey,
    label,
    cards,
    sectionData,
    defaultOpen,
}: {
    sectionKey: string;
    label: string;
    cards: { key: string; label: string }[];
    sectionData: RecordsSection | undefined;
    defaultOpen: boolean;
}) {
    const [open, setOpen] = useState(defaultOpen);

    return (
        <div className="rounded-xl border border-border overflow-hidden">
            <button
                type="button"
                onClick={() => setOpen((o) => !o)}
                className="flex w-full items-center justify-between px-5 py-3.5 bg-card hover:bg-muted/40 transition-colors text-left"
            >
                <div className="flex items-center gap-2">
                    <Trophy className="h-4 w-4 text-muted-foreground" />
                    <span className="font-semibold">{label}</span>
                </div>
                {open ? (
                    <ChevronDown className="h-4 w-4 text-muted-foreground" />
                ) : (
                    <ChevronRight className="h-4 w-4 text-muted-foreground" />
                )}
            </button>

            {open && (
                <div className="border-t border-border bg-background p-4">
                    {!sectionData ? (
                        <p className="text-sm text-muted-foreground">No data available.</p>
                    ) : (
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {cards.map((card) => {
                                const rows = sectionData[card.key];
                                if (!rows || rows.length === 0) return null;
                                return (
                                    <RecordCard key={`${sectionKey}-${card.key}`} title={card.label} rows={rows} />
                                );
                            })}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

export default function LapRecordsIndex({ records }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Records" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center gap-2">
                    <Trophy className="h-6 w-6" />
                    <h1 className="text-2xl font-semibold">Records</h1>
                </div>

                {!records ? (
                    <div className="rounded-xl border border-border bg-card p-8 text-center">
                        <p className="text-muted-foreground">No records data available. Complete some races to see records here.</p>
                    </div>
                ) : (
                    <div className="flex flex-col gap-3">
                        {SECTION_CONFIGS.map((section, i) => (
                            <AccordionSection
                                key={section.key}
                                sectionKey={section.key}
                                label={section.label}
                                cards={section.cards}
                                sectionData={records[section.key]}
                                defaultOpen={i === 0}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
