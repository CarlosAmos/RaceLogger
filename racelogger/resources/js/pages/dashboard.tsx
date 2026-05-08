import { Head, Link } from '@inertiajs/react';
import { Download } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import * as seasons from '@/routes/seasons';
import * as series from '@/routes/series';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

interface World {
    id: number;
    name: string;
    current_year: number;
}

interface UpcomingRace {
    id: number;
    round_number: number;
    race_code: string;
    race_date: string;
    endurance: number;
    gp_name?: string;
    season?: {
        series?: {
            short_name?: string;
        };
    };
    track_layout?: {
        track?: {
            name_short?: string;
        };
    };
}

interface SeasonStats {
    races: number;
    wins: number;
    poles: number;
    fastest_laps: number;
    podiums: number;
    points: number;
    season_active: number;
}

interface SubClassRow {
    class_id: number;
    label: string;
    stats: SeasonStats;
    ordinal: string;
    position: number | null;
}

interface TeamRow {
    team: string;
    stats: SeasonStats;
}

interface CareerEntry {
    season_id: number;
    series_name: string;
    teams: string[];
    ordinal: string;
    position: number;
    stats: SeasonStats;
    sub_class_rows: SubClassRow[];
    team_rows?: TeamRow[];
}

interface ResultsGridSession {
    session_id: number;
    is_sprint: boolean;
    session_order: number;
    name: string | null;
}

interface ResultsGridRound {
    race_code: string;
    gp_name: string;
    special_event: boolean;
    sessions: ResultsGridSession[];
}

interface ResultsGridSeasonEntry {
    entrant: string;
    class?: string;
    chassis?: string;
    engine?: string;
    results: Record<number, Record<number, string | null>>;
    subclass_results?: Record<number, Record<number, string | null>> | null;
}

interface ResultsGridSeason {
    season_id: number;
    calendar: ResultsGridRound[];
    entries: ResultsGridSeasonEntry[];
}

interface ResultsGridSubCupSeason extends ResultsGridSeason {
    class_id: number;
}

interface ResultsGridSubCup {
    label: string;
    seasons: Record<number, ResultsGridSubCupSeason>;
}

interface ResultsGridSeries {
    is_multiclass: boolean;
    is_spec: boolean;
    seasons: Record<number, ResultsGridSeason>;
    sub_cups: ResultsGridSubCup[];
}

interface Driver {
    id: number;
    first_name: string;
    last_name: string;
}

interface Props {
    world: World;
    currentYear: number;
    seasons: { id: number; year: number; series: { name: string; short_name?: string } }[];
    upcomingRaces: UpcomingRace[];
    careerMap: Record<number, Record<number, CareerEntry>>;
    resultsGrid: Record<string, ResultsGridSeries>;
    driver: Driver;
    myId: number;
}

const seriesColorClass: Record<string, string> = {
    F1: 'border-red-500 bg-red-500/30 text-white',
    WEC: 'border-blue-700 bg-blue-700/30 text-white',
    NLS: 'border-orange-500 bg-orange-500/30',
    VLN: 'border-orange-500 bg-orange-500/30',
    IGC: 'border-purple-300 bg-purple-300/30 text-white',
    F2: 'border-red-400 bg-red-400/30 text-white',
    AGT: 'border-red-400 bg-red-400/30 text-white',
    BGT: 'border-purple-600 bg-purple-600/30 text-white',
    SUP2:  'border-teal-600 bg-teal-600/30 text-white',
    MAU: 'border-white-800 bg-white-800/30 text-white',
    TOY: 'border-red-800 bg-red-800/30 text-white',
};

function positionClass(pos: string | number | null): string {
    if (pos === '1' || pos === 1) return 'bg-yellow-100 text-yellow-800';
    if (pos === '2' || pos === 2) return 'bg-gray-200 text-gray-800';
    if (pos === '3' || pos === 3) return 'bg-amber-200 text-amber-800';
    if (pos !== null && pos !== undefined && pos !== '') {
        if (String(pos).match(/^\d+$/)) return 'bg-green-100 text-green-800';
        return 'bg-purple-100 text-purple-800';
    }
    return '';
}

function champPositionClass(ordinal: string): string {
    if (ordinal === '1st') return 'bg-yellow-100 text-yellow-800';
    if (ordinal === '2nd') return 'bg-gray-200 text-gray-800';
    if (ordinal === '3rd') return 'bg-amber-200 text-amber-800';
    return '';
}

export default function Dashboard({ world, currentYear, seasons: seasonsList, upcomingRaces, careerMap, resultsGrid, driver, myId }: Props) {
    const isOwnDashboard = driver.id === myId;
    const driverName = `${driver.first_name} ${driver.last_name}`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isOwnDashboard ? 'Dashboard' : `${driverName} — Dashboard`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">{world.name} Dashboard</h1>
                        <p className="text-sm text-muted-foreground">
                            {isOwnDashboard ? (
                                <>Current Year: <strong>{currentYear}</strong></>
                            ) : (
                                <>Viewing: <strong>{driverName}</strong> &mdash; <Link href="/dashboard" className="text-blue-600 hover:underline">Back to my dashboard</Link></>
                            )}
                        </p>
                    </div>
                    {world.id === 1 && (
                        <Link
                            href="/import"
                            className="inline-flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm font-medium hover:bg-accent"
                        >
                            <Download className="h-4 w-4" />
                            Import F1 Data
                        </Link>
                    )}
                </div>

                {seasonsList.length === 0 ? (
                    <div className="rounded-lg border border-yellow-300 bg-yellow-50 p-5 text-yellow-800">
                        <strong>No active series for {currentYear}.</strong>
                        <div className="mt-3">
                            <Link href={series.create().url} className="inline-flex items-center rounded-md bg-yellow-600 px-3 py-1.5 text-sm text-white hover:bg-yellow-700">
                                Create Series
                            </Link>
                        </div>
                    </div>
                ) : (
                    <div className="flex flex-row gap-6">
                        {/* Calendar */}
                        <div className="min-w-[280px]">
                            <h4 className="mb-3 font-semibold">Calendar</h4>
                            <div className="flex flex-col gap-1">
                                {upcomingRaces.length === 0 ? (
                                    <div className="text-sm text-muted-foreground">No Upcoming Races</div>
                                ) : upcomingRaces.map(race => {
                                    const shortName = race.season?.series?.short_name ?? '';
                                    const colorCls = seriesColorClass[shortName] ?? 'border-gray-400 bg-gray-100';
                                    const raceDate = race.race_date
                                        ? new Date(race.race_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
                                        : '';
                                    return (
                                        <div key={race.id} className={`flex items-center gap-1 rounded-full border-2 px-2 py-0.5 font-semibold ${colorCls}`}>
                                            <div className="w-9 text-center text-xs font-light italic">{shortName}</div>
                                            <div className="text-[10px] text-muted-foreground">|</div>
                                            <div className="w-8 text-center text-xs">R{race.round_number}</div>
                                            <div className="w-10 text-xs font-medium">{race.race_code}{race.endurance === 1 && <span className="ml-0.5 text-[10px] font-normal opacity-70">(E)</span>}</div>
                                            <div className="text-xs">-</div>
                                            <div className="w-14 px-1 text-xs">{raceDate}</div>
                                            <div className="w-24 text-left text-xs font-light">{race.track_layout?.track?.name_short}</div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Career Section */}
                        <div className="flex flex-col justify-start">
                            <div className="mb-3 flex items-center gap-4">
                                <h3 className="text-lg font-semibold">Active Series</h3>
                                <Link href={series.create().url} className="rounded-md border px-2 py-1 text-sm hover:bg-muted">
                                    + Create New Series
                                </Link>
                                <Link href={seasons.create().url} className="text-sm text-blue-600 hover:underline">
                                    + Create New Season
                                </Link>
                            </div>

                            {/* Career Map Table */}
                            <div>
                                <h5 className="mb-2 font-medium">Racing Career</h5>
                                <div className="overflow-x-auto rounded-lg border">
                                    <table className="w-full border-collapse text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-3 py-2 text-left text-xs font-semibold uppercase text-muted-foreground">Season</th>
                                                <th className="px-3 py-2 text-left text-xs font-semibold uppercase text-muted-foreground">Series</th>
                                                <th className="px-3 py-2 text-left text-xs font-semibold uppercase text-muted-foreground">Team</th>
                                                <th className="px-3 py-2 text-center text-xs font-semibold uppercase text-muted-foreground">Races</th>
                                                <th className="px-3 py-2 text-center text-xs font-semibold uppercase text-muted-foreground">Wins</th>
                                                <th className="px-3 py-2 text-center text-xs font-semibold uppercase text-muted-foreground">Poles</th>
                                                <th className="px-3 py-2 text-center text-xs font-semibold uppercase text-muted-foreground">FL</th>
                                                <th className="px-3 py-2 text-center text-xs font-semibold uppercase text-muted-foreground">Podiums</th>
                                                <th className="px-3 py-2 text-center text-xs font-semibold uppercase text-muted-foreground">Points</th>
                                                <th className="px-3 py-2 text-center text-xs font-semibold uppercase text-muted-foreground">Position</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {Object.entries(careerMap).map(([year, yearSeasons]) => {
                                                type FlatRow =
                                                    | { kind: 'main'; seasonId: string; entry: CareerEntry }
                                                    | { kind: 'team'; seasonId: string; entry: CareerEntry; team: TeamRow; isFirst: boolean; teamCount: number }
                                                    | { kind: 'sub'; seasonId: string; entry: CareerEntry; sub: SubClassRow };

                                                const flatRows: FlatRow[] = [];
                                                Object.entries(yearSeasons).forEach(([seasonId, entry]) => {
                                                    if (entry.team_rows && entry.team_rows.length > 1) {
                                                        const teamCount = entry.team_rows.length;
                                                        entry.team_rows.forEach((team, teamIdx) =>
                                                            flatRows.push({ kind: 'team', seasonId, entry, team, isFirst: teamIdx === 0, teamCount })
                                                        );
                                                    } else {
                                                        flatRows.push({ kind: 'main', seasonId, entry });
                                                    }
                                                    (entry.sub_class_rows ?? []).forEach(sub =>
                                                        flatRows.push({ kind: 'sub', seasonId, entry, sub })
                                                    );
                                                });

                                                return flatRows.map((row, idx) => {
                                                    const stats   = row.kind === 'sub' ? row.sub.stats : row.kind === 'team' ? row.team.stats : row.entry.stats;
                                                    const ordinal = row.kind === 'sub' ? row.sub.ordinal : row.entry.ordinal;
                                                    const key     = row.kind === 'sub'
                                                        ? `${year}-${row.seasonId}-sub-${row.sub.class_id}`
                                                        : row.kind === 'team'
                                                        ? `${year}-${row.seasonId}-team-${row.team.team}`
                                                        : `${year}-${row.seasonId}`;

                                                    const showPositionCell = row.kind !== 'team' || row.isFirst;
                                                    const positionRowSpan = row.kind === 'team' && row.isFirst ? row.teamCount : undefined;

                                                    return (
                                                        <tr key={key} className={`border-b hover:bg-muted/30 ${row.kind === 'sub' ? 'bg-muted/5' : ''}`}>
                                                            {idx === 0 && (
                                                                <td rowSpan={flatRows.length} className="border-r bg-muted/20 px-3 py-2 text-center font-extrabold align-middle">
                                                                    {year}
                                                                </td>
                                                            )}
                                                            <td className="border-r px-3 py-2 font-semibold align-middle">
                                                                {row.kind === 'sub' ? (
                                                                    <span className="pl-4 text-xs text-muted-foreground">{row.sub.label}</span>
                                                                ) : (
                                                                    <Link href={`/seasons/${row.entry.season_id}`} className="text-blue-600 hover:underline">
                                                                        {row.entry.series_name}
                                                                    </Link>
                                                                )}
                                                            </td>
                                                            <td className="border-r px-3 py-2 italic text-muted-foreground">
                                                                {row.kind === 'team'
                                                                    ? row.team.team
                                                                    : row.kind === 'main'
                                                                    ? (Array.isArray(row.entry.teams) ? row.entry.teams.join(', ') : '')
                                                                    : ''}
                                                            </td>
                                                            <td className="border-r px-3 py-2 text-center">{stats.races}</td>
                                                            <td className="border-r px-3 py-2 text-center">{stats.wins}</td>
                                                            <td className="border-r px-3 py-2 text-center">{stats.poles}</td>
                                                            <td className="border-r px-3 py-2 text-center">{stats.fastest_laps}</td>
                                                            <td className="border-r px-3 py-2 text-center">{stats.podiums}</td>
                                                            <td className="border-r px-3 py-2 text-center font-bold">{stats.points}</td>
                                                            {showPositionCell && (
                                                                <td rowSpan={positionRowSpan} className={`px-3 py-2 text-center font-black align-middle ${champPositionClass(ordinal)}`}>
                                                                    {ordinal}{(row.kind === 'main' || (row.kind === 'team' && row.isFirst)) && row.entry.stats.season_active === 1 ? ' *' : ''}
                                                                </td>
                                                            )}
                                                        </tr>
                                                    );
                                                });
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {/* Results Grid per Series */}
                            <div className="mt-8 space-y-6">
                                {Object.entries(resultsGrid).map(([seriesName, seriesData]) => {
                                    const maxCols = Math.max(0, ...Object.values(seriesData.seasons).map(sd =>
                                        sd.calendar.reduce((acc, rd) => acc + Math.max(1, rd.sessions.length), 0)
                                    ));

                                    // Collect all special event races across seasons for this series
                                    const specialEvents: { year: string; gpName: string; raceCode: string; roundIdx: number; entries: ResultsGridSeasonEntry[] }[] = [];
                                    Object.entries(seriesData.seasons).forEach(([year, seasonData]) => {
                                        seasonData.calendar.forEach((round, roundIdx) => {
                                            if (round.special_event) {
                                                specialEvents.push({ year, gpName: round.gp_name || round.race_code, raceCode: round.race_code, roundIdx, entries: seasonData.entries });
                                            }
                                        });
                                    });

                                    return (
                                        <div key={seriesName}>
                                            <h5 className="mb-2 font-medium">{seriesName}</h5>
                                            <div className="overflow-x-auto rounded-lg border">
                                                <table className="w-full border-collapse text-xs">
                                                    <thead>
                                                        <tr className="border-b bg-muted/50">
                                                            <th className="px-3 py-2 text-left font-semibold text-muted-foreground">Year</th>
                                                            {seriesData.is_spec ? (
                                                                <th className="px-3 py-2 text-left font-semibold text-muted-foreground">Team</th>
                                                            ) : (
                                                                <>
                                                                    <th className="px-3 py-2 text-left font-semibold text-muted-foreground">Entrant</th>
                                                                    {seriesData.is_multiclass && <th className="px-3 py-2 font-semibold text-muted-foreground">Class</th>}
                                                                    <th className="px-3 py-2 text-left font-semibold text-muted-foreground">Chassis / Engine</th>
                                                                </>
                                                            )}
                                                            {Array.from({ length: maxCols }, (_, i) => (
                                                                <th key={i} className="px-2 py-2 text-center font-semibold text-muted-foreground">{i + 1}</th>
                                                            ))}
                                                            <th className="px-2 py-2 text-center font-semibold text-muted-foreground">Place</th>
                                                            <th className="px-2 py-2 text-center font-semibold text-muted-foreground">Points</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {Object.entries(seriesData.seasons).map(([year, seasonData]) => {
                                                            const regularCalendar = seasonData.calendar;
                                                            const seasonCols = regularCalendar.reduce((acc, rd) => acc + Math.max(1, rd.sessions.length), 0);
                                                            const paddingCols = maxCols - seasonCols;
                                                            const careerEntry = careerMap[Number(year)]?.[seasonData.season_id];
                                                            const champPos = careerEntry?.ordinal ?? '-';
                                                            const points = careerEntry?.stats?.points ?? '-';

                                                            const entryCount = seasonData.entries.length;
                                                            return seasonData.entries.map((entry, entryIdx) => (
                                                                <tr key={`${year}-${entryIdx}`} className="border-b hover:bg-muted/30">
                                                                    {entryIdx === 0 && (
                                                                        <td className="border-r bg-muted/20 px-3 py-2 text-center font-extrabold align-middle" rowSpan={entryCount > 1 ? entryCount : undefined}>{year}</td>
                                                                    )}
                                                                    {seriesData.is_spec ? (
                                                                        <td className="border-r px-3 py-2 italic text-muted-foreground">{entry.entrant}</td>
                                                                    ) : (
                                                                        <>
                                                                            <td className="border-r px-3 py-2 italic text-muted-foreground">{entry.entrant}</td>
                                                                            {seriesData.is_multiclass && <td className="border-r px-2 py-2 text-center">{entry.class}</td>}
                                                                            <td className="border-r px-3 py-2 text-[11px]">
                                                                                {entry.chassis}<br />
                                                                                <span className="italic text-muted-foreground">{entry.engine}</span>
                                                                            </td>
                                                                        </>
                                                                    )}
                                                                    {regularCalendar.map((round, origIdx) => {
                                                                        if (round.sessions.length > 0) {
                                                                            return round.sessions.map(session => {
                                                                                const result = entry.results?.[origIdx]?.[session.session_id] ?? null;
                                                                                const posCls = positionClass(result);
                                                                                const labelActive = result !== null ? 'text-black' : 'text-muted-foreground';
                                                                                return (
                                                                                    <td key={`${origIdx}-${session.session_id}`} className={`border-r px-1 py-1 text-center leading-tight ${posCls}`} style={{ minWidth: 36 }}>
                                                                                        <span className={`block text-[10px] ${labelActive}`}>{round.race_code}</span>
                                                                                        {round.sessions.length > 1 && (
                                                                                            <span className={`block text-[10px] italic ${labelActive}`}>{session.is_sprint ? 'SPR' : (session.name ?? `${session.session_order}`)}</span>
                                                                                        )}
                                                                                        <span className="block">{result ?? ''}</span>
                                                                                    </td>
                                                                                );
                                                                            });
                                                                        } else {
                                                                            return (
                                                                                <td key={origIdx} className="border-r px-1 py-1 text-center" style={{ minWidth: 36 }}>
                                                                                    <span className="block text-[10px] text-muted-foreground">{round.race_code}</span>
                                                                                </td>
                                                                            );
                                                                        }
                                                                    })}
                                                                    {Array.from({ length: paddingCols }, (_, i) => (
                                                                        <td key={`pad-${i}`} className="border-r px-1 py-1" />
                                                                    ))}
                                                                    {entryIdx === 0 && (
                                                                        <>
                                                                            <td className={`border-r px-2 py-2 text-center font-black ${champPositionClass(champPos)}`} rowSpan={entryCount > 1 ? entryCount : undefined}>{champPos}</td>
                                                                            <td className="px-2 py-2 text-center font-bold" rowSpan={entryCount > 1 ? entryCount : undefined}>{points}</td>
                                                                        </>
                                                                    )}
                                                                </tr>
                                                            ));
                                                        })}
                                                    </tbody>
                                                </table>
                                            </div>

                                            {/* Special Events — one table per event, named by gpName */}
                                            {specialEvents.length > 0 && (() => {
                                                const byEvent = specialEvents.reduce<Record<string, typeof specialEvents>>((acc, ev) => {
                                                    (acc[ev.gpName] ??= []).push(ev);
                                                    return acc;
                                                }, {});
                                                return Object.entries(byEvent).map(([gpName, events]) => {
                                                    const hasSubClass = events.some(ev =>
                                                        ev.entries.some(e => e.subclass_results != null)
                                                    );
                                                    return (
                                                        <div key={gpName} className="mt-3">
                                                            <h6 className="mb-1 text-xs font-semibold uppercase tracking-wider text-muted-foreground">{gpName}</h6>
                                                            <div className="overflow-x-auto rounded-lg border">
                                                                <table className="w-full border-collapse text-xs">
                                                                    <thead>
                                                                        <tr className="border-b bg-muted/50">
                                                                            <th className="px-3 py-2 text-center font-semibold text-muted-foreground">Year</th>
                                                                            <th className="px-3 py-2 text-left font-semibold text-muted-foreground">Entrant</th>
                                                                            {seriesData.is_multiclass && <th className="px-3 py-2 text-center font-semibold text-muted-foreground">Class</th>}
                                                                            <th className="px-3 py-2 text-left font-semibold text-muted-foreground">Chassis / Engine</th>
                                                                            {hasSubClass && <th className="px-3 py-2 text-center font-semibold text-muted-foreground">Result</th>}
                                                                            <th className="px-3 py-2 text-center font-semibold text-muted-foreground">{hasSubClass ? 'Overall' : 'Result'}</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        {events.map(({ year, roundIdx, entries }) => {
                                                                            const entriesWithResult = entries.filter(e =>
                                                                                Object.values(e.results?.[roundIdx] ?? {}).some(r => r !== null)
                                                                            );
                                                                            const visibleEntries = entriesWithResult.length > 0 ? entriesWithResult : [entries[entries.length - 1]];
                                                                            return visibleEntries.map((entry, entryIdx) => {
                                                                                const round = seriesData.seasons[Number(year)].calendar[roundIdx];
                                                                                const nonSprintSessions = round.sessions.filter((s: ResultsGridSession) => !s.is_sprint);
                                                const mainSession = nonSprintSessions.sort((a, b) => b.session_order - a.session_order)[0] ?? round.sessions[0] ?? null;
                                                                                const overallResult = mainSession ? entry.results?.[roundIdx]?.[mainSession.session_id] ?? null : null;
                                                                                const subResult = (hasSubClass && mainSession && entry.subclass_results)
                                                                                    ? entry.subclass_results?.[roundIdx]?.[mainSession.session_id] ?? null
                                                                                    : null;
                                                                                return (
                                                                                    <tr key={`se-${year}-${roundIdx}-${entryIdx}`} className="border-b hover:bg-muted/30">
                                                                                        <td className="border-r px-3 py-2 text-center font-extrabold">{year}</td>
                                                                                        <td className="border-r px-3 py-2 italic text-muted-foreground">{entry.entrant}</td>
                                                                                        {seriesData.is_multiclass && <td className="border-r px-2 py-2 text-center">{entry.class}</td>}
                                                                                        <td className="border-r px-3 py-2 text-[11px]">
                                                                                            {entry.chassis}<br />
                                                                                            <span className="italic text-muted-foreground">{entry.engine}</span>
                                                                                        </td>
                                                                                        {hasSubClass && <td className={`border-r px-2 py-2 text-center font-bold ${positionClass(subResult)}`}>{subResult ?? '-'}</td>}
                                                                                        <td className={`px-2 py-2 text-center font-bold ${positionClass(overallResult)}`}>{overallResult ?? '-'}</td>
                                                                                    </tr>
                                                                                );
                                                                            });
                                                                        })}
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    );
                                                });
                                            })()}

                                            {/* Sub-cup sections (e.g. "World GT Gold Cup") */}
                                            {(seriesData.sub_cups ?? []).map(subCup => {
                                                const subMaxCols = Math.max(0, ...Object.values(subCup.seasons).map(sd =>
                                                    sd.calendar.filter(rd => !rd.special_event).reduce((acc, rd) => acc + Math.max(1, rd.sessions.length), 0)
                                                ));
                                                return (
                                                    <div key={subCup.label} className="mt-4">
                                                        <h6 className="mb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">{subCup.label}</h6>
                                                        <div className="overflow-x-auto rounded-lg border">
                                                            <table className="w-full border-collapse text-xs">
                                                                <thead>
                                                                    <tr className="border-b bg-muted/50">
                                                                        <th className="px-3 py-2 text-left font-semibold text-muted-foreground">Year</th>
                                                                        <th className="px-3 py-2 text-left font-semibold text-muted-foreground">Entrant</th>
                                                                        <th className="px-3 py-2 text-left font-semibold text-muted-foreground">Chassis / Engine</th>
                                                                        {Array.from({ length: maxCols }, (_, i) => (
                                                                            <th key={i} className="px-2 py-2 text-center font-semibold text-muted-foreground">{i + 1}</th>
                                                                        ))}
                                                                        <th className="px-2 py-2 text-center font-semibold text-muted-foreground">Place</th>
                                                                        <th className="px-2 py-2 text-center font-semibold text-muted-foreground">Points</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    {Object.entries(subCup.seasons).map(([year, seasonData]) => {
                                                                        //const regularCalendar = seasonData.calendar.filter(rd => !rd.special_event);
                                                                        const regularCalendar = seasonData.calendar;
                                                                        const seasonCols = regularCalendar.reduce((acc, rd) => acc + Math.max(1, rd.sessions.length), 0);
                                                                        const paddingCols = subMaxCols - seasonCols;
                                                                        const subClassRow = careerMap[Number(year)]?.[seasonData.season_id]?.sub_class_rows?.find(
                                                                            sub => sub.class_id === seasonData.class_id
                                                                        );
                                                                        const champPos = subClassRow?.ordinal ?? '-';
                                                                        const points = subClassRow?.stats?.points ?? '-';

                                                                        return seasonData.entries.map((entry, entryIdx) => (
                                                                            <tr key={`${year}-${entryIdx}`} className="border-b hover:bg-muted/30">
                                                                                <td className="border-r bg-muted/20 px-3 py-2 text-center font-extrabold align-middle">{year}</td>
                                                                                <td className="border-r px-3 py-2 italic text-muted-foreground">{entry.entrant}</td>
                                                                                <td className="border-r px-3 py-2 text-[11px]">
                                                                                    {entry.chassis}<br />
                                                                                    <span className="italic text-muted-foreground">{entry.engine}</span>
                                                                                </td>
                                                                                {regularCalendar.map((round, _colIdx) => {
                                                                                    const origIdx = seasonData.calendar.indexOf(round);
                                                                                    if (round.sessions.length > 0) {
                                                                                        return round.sessions.map(session => {
                                                                                            const overallResult = entry.results?.[origIdx]?.[session.session_id] ?? null;
                                                                                            const subResult = entry.subclass_results?.[origIdx]?.[session.session_id] ?? null;
                                                                                            const posCls = positionClass(subResult ?? overallResult);
                                                                                            const labelActive = overallResult !== null ? 'text-black' : 'text-muted-foreground';
                                                                                            return (
                                                                                                <td key={`${origIdx}-${session.session_id}`} className={`border-r px-1 py-1 text-center leading-tight ${posCls}`} style={{ minWidth: 36 }}>
                                                                                                    <span className={`block text-[10px] ${labelActive}`}>{round.race_code}</span>
                                                                                                    {round.sessions.length > 1 && (
                                                                                                        <span className={`block text-[10px] italic ${labelActive}`}>{session.is_sprint ? 'SPR' : (session.name ?? `${session.session_order}`)}</span>
                                                                                                    )}
                                                                                                    <div className="flex w-full text-center justify-center">
                                                                                                        <span className="block">{overallResult ?? ''}</span>
                                                                                                        {subResult !== null && subResult !== overallResult && (
                                                                                                            <span className="block text-[9px] opacity-70">({subResult})</span>
                                                                                                        )}
                                                                                                    </div>
                                                                                                </td>
                                                                                            );
                                                                                        });
                                                                                    } else {
                                                                                        return (
                                                                                            <td key={origIdx} className="border-r px-1 py-1 text-center" style={{ minWidth: 36 }}>
                                                                                                <span className="block text-[10px] text-muted-foreground">{round.race_code}</span>
                                                                                            </td>
                                                                                        );
                                                                                    }
                                                                                })}
                                                                                {Array.from({ length: paddingCols }, (_, i) => (
                                                                                    <td key={`pad-${i}`} className="border-r px-1 py-1" />
                                                                                ))}
                                                                                <td className={`border-r px-2 py-2 text-center font-black ${champPositionClass(champPos)}`}>{champPos}</td>
                                                                                <td className="px-2 py-2 text-center font-bold">{points}</td>
                                                                            </tr>
                                                                        ));
                                                                    })}
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
