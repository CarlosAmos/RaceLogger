import { useEffect, useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { BreadcrumbItem } from '@/types';
import * as seasons from '@/routes/seasons';
import * as races from '@/routes/races';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Driver {
    id: number;
    first_name: string;
    last_name: string;
}

interface ResultDriver {
    id: number;
    driver_id: number;
    driver: Driver;
}

interface PointSystemRule {
    type: string;
    position: number;
    points: number;
}

interface PointSystemBonusRule {
    type: string;
    points: number;
    requires_finish: boolean;
    min_position_required: number | null;
}

interface PointSystem {
    id: number;
    rules: PointSystemRule[];
    bonus_rules: PointSystemBonusRule[];
}

interface Result {
    id: number;
    race_session_id: number;
    entry_car_id: number;
    position: number | string | null;
    class_position: number | string | null;
    sub_class_position: number | string | null;
    points_awarded: number;
    sub_class_points_awarded: number | null;
    status: string;
    fastest_lap: boolean;
    fastest_lap_time_ms: number | null;
    result_drivers: ResultDriver[];
}

interface QualifyingResult {
    id: number;
    entry_car_id: number;
    position: number;
    best_lap_time_ms: number | null;
}

interface QualifyingSession {
    id: number;
    session_order: number;
    race_number: number;
    results: QualifyingResult[];
}

interface RaceSession {
    id: number;
    is_sprint: boolean;
    session_order: number;
}

interface CalendarRace {
    id: number;
    round_number: number;
    race_code: string;
    sprint_race: number;
    endurance: number;
    results: Result[];
    race_sessions: RaceSession[];
    qualifying_sessions: QualifyingSession[];
    point_system: PointSystem | null;
}

interface EntryCar {
    id: number;
    car_number: string;
    livery_name: string | null;
    car_model: { name: string; year: number; hybrid?: boolean; engine?: { name: string } | null } | null;
    drivers: Driver[];
}

interface EntryClass {
    id: number;
    race_class_id: number;
    race_class: { id: number; name: string };
    entry_cars: EntryCar[];
}

interface SeasonEntry {
    id: number;
    display_name: string | null;
    entrant: { id: number; name: string } | null;
    constructor: { id: number; name: string } | null;
    entry_classes: EntryClass[];
}

interface SeasonClass {
    id: number;
    name: string;
    sub_class: string | null;
    display_order: number;
}

interface Season {
    id: number;
    year: number;
    series_id: number;
    name?: string;
    season_classes: SeasonClass[];
    season_entries: SeasonEntry[];
    calendar_races: CalendarRace[];
    point_system: PointSystem | null;
}

interface Series {
    id: number;
    name: string;
    short_name?: string;
}

interface World {
    id: number;
    name: string;
}

interface ChampScenarioRow {
    leader_pos: number;
    rivals: Record<number, number | null>;
    next_race?: boolean;
}

interface ChampionResult {
    entry_car_id: number;
    entryCar: { car_number: string };
}

interface ChampScenario {
    leader: ChampionResult;
    rivals: (ChampionResult & { entry_car_id: number })[];
    rows: ChampScenarioRow[];
}

interface AccDriver {
    first_name: string;
    last_name: string;
}

interface AccCar {
    car_number: string;
    livery_name: string;
    livery_differs: boolean;
    car_matched: boolean;
    drivers: AccDriver[];
}

interface AccTeam {
    team_guid: string;
    team_name: string;
    team_matched: boolean;
    nationality: number | null;
    cars: AccCar[];
}

interface AccEntrant {
    id: number;
    name: string;
    display_name: string;
    constructor_id: number | null;
    entry_class_id: number | null;
}

interface AccCarModel {
    id: number;
    name: string;
    constructor_id: number;
}

interface AccConstructor {
    id: number;
    name: string;
}

interface AccImport {
    race: { id: number; gp_name: string; race_code: string; round: number } | null;
    teams: AccTeam[];
    entrants: AccEntrant[];
    car_models: AccCarModel[];
}

interface Props {
    season: Season;
    series: Series[];
    world: World;
    tab: string;
    classScenarios: Record<number, ChampScenario>;
    accImport: AccImport | null;
    allEntrants: { id: number; name: string }[];
    constructors: AccConstructor[];
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function msToLap(ms: number | null): string {
    if (!ms) return '';
    const minutes = Math.floor(ms / 60000);
    const seconds = Math.floor((ms % 60000) / 1000);
    const milliseconds = ms % 1000;
    return `${minutes}:${String(seconds).padStart(2, '0')}.${String(milliseconds).padStart(3, '0')}`;
}

function ordinalSuffix(n: number): string {
    const s = ['th', 'st', 'nd', 'rd'];
    const v = n % 100;
    return n + (s[(v - 20) % 10] || s[v] || s[0]);
}

type ResultCellMode = 'class' | 'overall' | 'subclass';

function getResultPos(result: Result, mode: ResultCellMode): number | string | null {
    if (mode === 'overall') return result.class_position;
    if (mode === 'subclass') return result.sub_class_position;
    return result.class_position;
}

function resultCellClass(result: Result | undefined, mode: ResultCellMode = 'class'): string {
    if (!result) return 'border border-border';
    const pos = getResultPos(result, mode);
    const status = result.status?.toUpperCase();

    if (result.status === 'finished' && typeof pos === 'number') {
        if (pos === 1) return 'bg-yellow-400 text-black font-semibold border border-border';
        if (pos === 2) return 'bg-gray-400 text-white font-semibold border border-border';
        if (pos === 3) return 'bg-amber-700 text-white font-semibold border border-border';
        const effectivePts = mode === 'subclass' ? (result.sub_class_points_awarded ?? result.points_awarded) : result.points_awarded;
        if (effectivePts > 0) return 'bg-green-600 text-white border border-border';
        return 'border border-border';
    }

    switch (status) {
        case 'DSQ': return 'bg-gray-900 text-white border border-border';
        case 'RET':
        case 'DNF': return 'bg-purple-200 text-black border border-border';
        case 'DNS': return 'text-gray-400 italic border border-border';
        case 'DNQ':
        case 'DNPQ': return 'bg-red-600 text-white border border-border';
        default: return 'border border-border';
    }
}

function resultCellText(result: Result | undefined, mode: ResultCellMode = 'class'): string {
    if (!result) return '';
    if (result.status === 'finished') return String(getResultPos(result, mode) ?? '');
    return (result.status ?? '').toUpperCase();
}

function calcOverallPoints(result: Result, pointSystem: PointSystem | null): number {
    if (!pointSystem || result.status !== 'finished') return 0;
    const pos = Number(result.position);
    if (!pos) return 0;
    const rule = pointSystem.rules.find(r => r.type === 'race' && Number(r.position) === pos);
    let pts = Number(rule?.points ?? 0);
    if (result.fastest_lap) {
        const flRule = pointSystem.bonus_rules?.find(r => r.type === 'fastest_lap');
        if (flRule) {
            const withinPos = flRule.min_position_required == null || pos <= Number(flRule.min_position_required);
            if (withinPos) pts += Number(flRule.points ?? 0);
        }
    }
    return pts;
}

// ─── Component ────────────────────────────────────────────────────────────────

export default function SeasonShow({ season, series, world, tab: initialTab, classScenarios, accImport, allEntrants, constructors }: Props) {
    const currentTab = initialTab || 'calender';
    const [accModalOpen, setAccModalOpen] = useState(() =>
        (accImport?.teams.filter(t => t.cars.some(c => !c.car_matched)).length ?? 0) > 0
    );
    const [carAssign, setCarAssign] = useState<Record<string, { entrantId: string; classId: string; carModelId: string }>>(() => {
        if (!accImport) return {};
        const init: Record<string, { entrantId: string; classId: string; carModelId: string }> = {};
        for (const team of accImport.teams) {
            const matchedEntrant = accImport.entrants.find(
                e => e.display_name.toLowerCase() === team.team_name.toLowerCase()
            );
            for (const car of team.cars) {
                if (car.car_matched) continue;
                const entrantId = matchedEntrant ? String(matchedEntrant.id) : '';
                const classId = matchedEntrant?.entry_class_id ? String(matchedEntrant.entry_class_id) : '';
                const models = matchedEntrant?.constructor_id
                    ? accImport.car_models.filter(m => m.constructor_id === matchedEntrant.constructor_id)
                    : accImport.car_models;
                const carModelId = models.length === 1 ? String(models[0].id) : '';
                init[car.car_number] = { entrantId, classId, carModelId };
            }
        }
        return init;
    });
    const [assignedCars, setAssignedCars] = useState<Set<string>>(new Set());
    const [teamCreate, setTeamCreate] = useState<Record<string, { entrantId: string; constructorId: string; displayName: string }>>({});
    const [champSubTab, setChampSubTab] = useState<'overall' | 'sprint' | 'endurance'>('overall');

    useEffect(() => {
        if (accImport && !accModalOpen) {
            router.post(`/seasons/${season.id}/acc/assign-drivers`, {}, {
                preserveScroll: true,
                preserveState: true,
            });
        }
    }, []);

    function setTeamField(teamGuid: string, field: 'entrantId' | 'constructorId' | 'displayName', value: string) {
        setTeamCreate(prev => {
            const current = prev[teamGuid] ?? { entrantId: '', constructorId: '', displayName: '' };
            return { ...prev, [teamGuid]: { ...current, [field]: value } };
        });
    }

    function handleCreateEntry(teamGuid: string) {
        const form = teamCreate[teamGuid];
        if (!form?.entrantId || !form?.constructorId) return;
        router.post(`/seasons/${season.id}/acc/create-entry`, {
            entrant_id: Number(form.entrantId),
            constructor_id: Number(form.constructorId),
            display_name: form.displayName || null,
        }, { preserveScroll: true });
    }

    function setCarField(carNumber: string, field: 'entrantId' | 'classId' | 'carModelId', value: string) {
        setCarAssign(prev => {
            const current = prev[carNumber] ?? { entrantId: '', classId: '', carModelId: '' };
            const updated = { ...current, [field]: value };
            if (field === 'entrantId') { updated.carModelId = ''; updated.classId = ''; }
            return { ...prev, [carNumber]: updated };
        });
    }

    function filteredCarModels(carNumber: string, overrideEntrantId?: string): AccCarModel[] {
        if (!accImport) return [];
        const entrantId = Number(overrideEntrantId ?? carAssign[carNumber]?.entrantId);
        const entrant = accImport.entrants.find(e => e.id === entrantId);
        if (!entrant?.constructor_id) return accImport.car_models;
        return accImport.car_models.filter(m => m.constructor_id === entrant.constructor_id);
    }

    function handleAssignAll() {
        if (!accImport) return;
        for (const team of accImport.teams) {
            for (const car of team.cars) {
                if (car.car_matched || assignedCars.has(car.car_number)) continue;
                const a = carAssign[car.car_number] ?? { entrantId: '', classId: '', carModelId: '' };
                if (a.entrantId && a.classId && a.carModelId) {
                    handleAssign(team.team_name, car, a.entrantId, a.classId, a.carModelId);
                }
            }
        }
    }

    function handleAssign(teamName: string, car: AccCar, entrantId: string, classId: string, carModelId: string) {
        if (!accImport || !entrantId || !classId || !carModelId) return;
        const entrant = accImport.entrants.find(e => e.id === Number(entrantId));
        if (!entrant) return;
        const liveryName = teamName !== entrant.display_name ? teamName : null;
        router.post(`/seasons/${season.id}/acc/assign-car`, {
            season_entry_id: entrant.id,
            season_class_id: Number(classId),
            car_number: car.car_number,
            car_model_id: Number(carModelId),
            livery_name: liveryName,
            drivers: car.drivers.map(d => ({ first_name: d.first_name, last_name: d.last_name })),
        }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => setAssignedCars(prev => new Set(prev).add(car.car_number)),
        });
    }
    const seriesName = series[0]?.name ?? '';
    const shortName = series[0]?.short_name ?? '';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: `${seriesName} ${season.year}`, href: seasons.show(season.id).url },
    ];

    // Build entry car lookup: carId -> { classId, className, carNumber, teamName, teamId }
    const entryCarMap = useMemo(() => {
        const map = new Map<number, {
            classId: number;
            className: string;
            carNumber: string;
            teamName: string;
            teamId: number;
            entryId: number;
        }>();
        for (const entry of season.season_entries) {
            for (const ec of entry.entry_classes) {
                for (const car of ec.entry_cars) {
                    map.set(car.id, {
                        classId: ec.race_class_id,
                        className: ec.race_class.name,
                        carNumber: car.car_number,
                        teamName: car.livery_name ?? entry.display_name ?? entry.entrant?.name ?? '',
                        teamId: entry.entrant?.id ?? 0,
                        entryId: entry.id,
                    });
                }
            }
        }
        return map;
    }, [season.season_entries]);

    // Helper: build class standings tables from a given race array
    const buildClassTablesFromRaces = (raceList: CalendarRace[]) => {
        const tables = new Map<number, {
            id: number;
            name: string;
            subClass: string | null;
            classIds: number[];
            isOverall?: boolean;
            displayOrder: number;
            rows: Map<number, {
                driverId: number;
                driver: Driver;
                teamName: string;
                carNumber: string;
                raceResults: Map<number, Result>;
                totalPoints: number;
                totalOverallPoints: number;
            }>;
        }>();

        for (const sc of season.season_classes) {
            tables.set(sc.id, {
                id: sc.id,
                name: sc.name,
                subClass: sc.sub_class,
                classIds: [sc.id],
                displayOrder: sc.display_order,
                rows: new Map(),
            });
        }

        for (const race of raceList) {
            for (const result of race.results) {
                const carInfo = entryCarMap.get(result.entry_car_id);
                if (!carInfo) continue;

                const table = tables.get(carInfo.classId);
                if (!table) continue;

                for (const rd of result.result_drivers ?? []) {
                    const driver = rd.driver;
                    if (!driver) continue;

                    if (!table.rows.has(driver.id)) {
                        table.rows.set(driver.id, {
                            driverId: driver.id,
                            driver,
                            teamName: carInfo.teamName,
                            carNumber: carInfo.carNumber,
                            raceResults: new Map(),
                            totalPoints: 0,
                            totalOverallPoints: 0,
                        });
                    }

                    const row = table.rows.get(driver.id)!;
                    row.raceResults.set(result.race_session_id, result);
                    row.totalPoints += Number((table.subClass !== null ? result.sub_class_points_awarded : result.points_awarded) ?? 0);
                    const effectivePtSystem = race.point_system ?? season.point_system;
                    row.totalOverallPoints += calcOverallPoints(result, effectivePtSystem);
                }
            }
        }

        return Array.from(tables.values())
            .sort((a, b) => a.displayOrder - b.displayOrder);
    };

    // Build class standings tables (all races)
    const classTables = useMemo(
        () => buildClassTablesFromRaces(season.calendar_races),
        [season, entryCarMap]
    );

    // Sorted races (all)
    const sortedRaces = useMemo(
        () => [...season.calendar_races].sort((a, b) => a.round_number - b.round_number),
        [season.calendar_races]
    );

    // Detect mix of endurance and non-endurance races
    const hasEnduranceMix = useMemo(
        () => sortedRaces.some(r => r.endurance) && sortedRaces.some(r => !r.endurance),
        [sortedRaces]
    );

    const enduranceSortedRaces = useMemo(
        () => sortedRaces.filter(r => r.endurance),
        [sortedRaces]
    );

    const regularSortedRaces = useMemo(
        () => sortedRaces.filter(r => !r.endurance),
        [sortedRaces]
    );

    const enduranceClassTables = useMemo(
        () => hasEnduranceMix ? buildClassTablesFromRaces(enduranceSortedRaces) : [],
        [hasEnduranceMix, enduranceSortedRaces, entryCarMap]
    );

    const regularClassTables = useMemo(
        () => hasEnduranceMix ? buildClassTablesFromRaces(regularSortedRaces) : [],
        [hasEnduranceMix, regularSortedRaces, entryCarMap]
    );

    // ─── Render: driver championship standings ─────────────────────────────────
    const renderDriverStandings = (classTbls: ReturnType<typeof buildClassTablesFromRaces>, raceList: CalendarRace[], keyPrefix = '') => {
        return classTbls.map((cls) => {
            const hiddenPtsMap = new Map<number, number>();
            for (const row of cls.rows.values()) {
                if (row.totalPoints > 0) continue;
                let hidden = 0;
                for (const race of raceList) {
                    for (const session of race.race_sessions) {
                        const classCompetitors = race.results.filter(r =>
                            r.race_session_id === session.id &&
                            entryCarMap.get(r.entry_car_id)?.classId === cls.id
                        ).length;
                        if (classCompetitors === 0) continue;
                        const result = row.raceResults.get(session.id);
                        if (!result) {
                            hidden += classCompetitors + 1;
                        } else if (result.status === 'finished') {
                            hidden += Number(result.class_position ?? classCompetitors + 1);
                        } else {
                            hidden += classCompetitors;
                        }
                    }
                }
                hiddenPtsMap.set(row.driverId, hidden);
            }

            const sortedRows = Array.from(cls.rows.values()).sort((a, b) => {
                if (a.totalPoints > 0 && b.totalPoints > 0)
                    return b.totalPoints - a.totalPoints || a.teamName.localeCompare(b.teamName);
                if (a.totalPoints > 0) return -1;
                if (b.totalPoints > 0) return 1;
                const ha = hiddenPtsMap.get(a.driverId) ?? 0;
                const hb = hiddenPtsMap.get(b.driverId) ?? 0;
                return ha - hb || a.teamName.localeCompare(b.teamName);
            });
            const leaderPts = sortedRows[0]?.totalPoints ?? 0;

            const driverRankMap = new Map<number, number>();
            let dRank = 0, prevDriverKey = '';
            for (const row of sortedRows) {
                const key = row.totalPoints > 0
                    ? `pts:${row.teamName}:${row.totalPoints}`
                    : `npts:${row.teamName}:${hiddenPtsMap.get(row.driverId) ?? 0}`;
                if (key !== prevDriverKey) { dRank++; prevDriverKey = key; }
                driverRankMap.set(row.driverId, dRank);
            }
            const driverRank = (id: number) => driverRankMap.get(id) ?? 0;

            return (
                <div key={`${keyPrefix}driver-${cls.id}`}>
                    <h4 className="mb-2 uppercase font-bold border-b border-border pb-1">
                        {cls.name}{cls.subClass ? <span className="normal-case font-normal text-muted-foreground"> — {cls.subClass}</span> : ''}
                    </h4>
                    <div className="overflow-x-auto">
                        <table className="text-sm text-center w-full border-collapse">
                            <thead>
                                <tr>
                                    <th className="border border-border px-2 py-1">Pos</th>
                                    <th className="border border-border px-2 py-1 text-left">Driver</th>
                                    <th className="border border-border px-2 py-1">No.</th>
                                    <th className="border border-border px-2 py-1 text-left">Team</th>
                                    {raceList.map((race) =>
                                        race.race_sessions.length > 0
                                            ? race.race_sessions.map((session) => (
                                                <th key={session.id} className="border border-border px-2 py-1">
                                                    <Link
                                                        href={races.show(race.id, {
                                                            query: { has_sprint: race.sprint_race },
                                                        }).url}
                                                        className="hover:underline"
                                                    >
                                                        {session.is_sprint
                                                            ? `${race.race_code}S`
                                                            : race.race_sessions.filter(s => !s.is_sprint).length > 1
                                                                ? `${race.race_code} R${session.session_order}${race.endurance ? ' (E)' : ''}`
                                                                : `${race.race_code}${race.endurance ? ' (E)' : ''}`}
                                                    </Link>
                                                </th>
                                            ))
                                            : [
                                                <th key={race.id} className="border border-border px-2 py-1">
                                                    <Link
                                                        href={races.show(race.id, {
                                                            query: { has_sprint: race.sprint_race },
                                                        }).url}
                                                        className="hover:underline"
                                                    >
                                                        {`${race.race_code}${race.endurance ? ' (E)' : ''}`}
                                                    </Link>
                                                </th>
                                            ]
                                    )}
                                    <th className="border border-border px-2 py-1 font-bold">Pts</th>
                                    <th className="border border-border px-2 py-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                {sortedRows.map((row, idx) => {
                                    const displayPos = driverRank(row.driverId);
                                    return (
                                    <tr key={row.driverId}>
                                        <td className="border border-border px-2 py-1">{displayPos}</td>
                                        <td className="border border-border px-2 py-1 text-left whitespace-nowrap">
                                            {row.driver.first_name} {row.driver.last_name}
                                        </td>
                                        <td className="border border-border px-2 py-1">#{row.carNumber}</td>
                                        <td className="border border-border px-2 py-1 text-left text-muted-foreground whitespace-nowrap">
                                            {row.teamName}
                                        </td>
                                        {raceList.map((race) =>
                                            race.race_sessions.length > 0
                                                ? race.race_sessions.map((session) => {
                                                    const result = row.raceResults.get(session.id);
                                                    const pBadge = (() => {
                                                        if (!result) return false;
                                                        const raceNum = session.is_sprint ? 1 : session.session_order;
                                                        const finalQS = [...(race.qualifying_sessions ?? [])]
                                                            .filter(qs => qs.race_number === raceNum)
                                                            .sort((a, b) => b.session_order - a.session_order)[0];
                                                        if (!finalQS) return false;
                                                        const classPole = finalQS.results
                                                            .filter((qr) => {
                                                                const ci = entryCarMap.get(qr.entry_car_id);
                                                                return ci ? cls.classIds.includes(ci.classId) : false;
                                                            })
                                                            .sort((a, b) => a.position - b.position)[0];
                                                        return classPole?.entry_car_id === result.entry_car_id;
                                                    })();
                                                    return (
                                                        <td
                                                            key={session.id}
                                                            className={`px-2 py-1 text-center ${resultCellClass(result, cls.isOverall ? 'overall' : cls.subClass ? 'subclass' : 'class')}`}
                                                        >
                                                            {resultCellText(result, cls.isOverall ? 'overall' : cls.subClass ? 'subclass' : 'class')}
                                                            {pBadge && (
                                                                <sup className="ml-0.5 text-xs font-bold">P</sup>
                                                            )}
                                                            {result?.fastest_lap && (
                                                                <sup className="ml-0.5 text-xs font-bold text-purple-600">FL</sup>
                                                            )}
                                                        </td>
                                                    );
                                                })
                                                : [<td key={race.id} className="border border-border px-2 py-1" />]
                                        )}
                                        <td className="border border-border px-2 py-1 font-bold">
                                            {Number(row.totalPoints).toFixed(Number.isInteger(row.totalPoints) ? 0 : 1)}
                                        </td>
                                        <td className="border border-border px-2 py-1 text-muted-foreground text-xs">
                                            {row.totalPoints > 0 && idx > 0 && `${(row.totalPoints - leaderPts).toFixed(0)}`}
                                        </td>
                                    </tr>
                                    );
                                })}

                                {/* Pole Position row */}
                                <tr className="bg-muted/50 font-semibold">
                                    <td className="border border-border px-2 py-1 text-left" colSpan={4}>
                                        Pole Position
                                    </td>
                                    {raceList.map((race) =>
                                        race.race_sessions.length > 0
                                            ? race.race_sessions.map((session) => {
                                                const raceNum = session.is_sprint ? 1 : session.session_order;
                                                const finalQS = [...(race.qualifying_sessions ?? [])]
                                                    .filter(qs => qs.race_number === raceNum)
                                                    .sort((a, b) => b.session_order - a.session_order)[0];
                                                const pole = finalQS?.results
                                                    .filter((qr) => {
                                                        const ci = entryCarMap.get(qr.entry_car_id);
                                                        return ci ? cls.classIds.includes(ci.classId) : false;
                                                    })
                                                    .sort((a, b) => a.position - b.position)[0];
                                                const carInfo = pole
                                                    ? entryCarMap.get(pole.entry_car_id)
                                                    : undefined;
                                                return (
                                                    <td
                                                        key={session.id}
                                                        className="border border-border px-2 py-1 text-center text-xs"
                                                    >
                                                        {carInfo ? (
                                                            <>
                                                                <div>#{carInfo.carNumber}</div>
                                                                <div className="text-muted-foreground">
                                                                    {msToLap(pole?.best_lap_time_ms ?? null)}
                                                                </div>
                                                            </>
                                                        ) : '–'}
                                                    </td>
                                                );
                                            })
                                            : [<td key={race.id} className="border border-border px-2 py-1 text-center text-xs">–</td>]
                                    )}
                                    <td className="border border-border px-2 py-1" colSpan={2} />
                                </tr>

                                {/* Fastest Lap row */}
                                <tr className="bg-muted/50 font-semibold">
                                    <td className="border border-border px-2 py-1 text-left" colSpan={4}>
                                        Fastest Lap
                                    </td>
                                    {raceList.map((race) =>
                                        race.race_sessions.length > 0
                                            ? race.race_sessions.map((session) => {
                                                const fastest = race.results.find(
                                                    (r) => {
                                                        const ci = entryCarMap.get(r.entry_car_id);
                                                        return r.race_session_id === session.id &&
                                                            r.fastest_lap &&
                                                            (ci ? cls.classIds.includes(ci.classId) : false);
                                                    }
                                                );
                                                const carInfo = fastest
                                                    ? entryCarMap.get(fastest.entry_car_id)
                                                    : undefined;
                                                return (
                                                    <td
                                                        key={session.id}
                                                        className="border border-border px-2 py-1 text-center text-xs"
                                                    >
                                                        {carInfo ? (
                                                            <>
                                                                <div>#{carInfo.carNumber}</div>
                                                                <div className="text-muted-foreground">
                                                                    {msToLap(fastest?.fastest_lap_time_ms ?? null)}
                                                                </div>
                                                            </>
                                                        ) : '–'}
                                                    </td>
                                                );
                                            })
                                            : [<td key={race.id} className="border border-border px-2 py-1 text-center text-xs">–</td>]
                                    )}
                                    <td className="border border-border px-2 py-1" colSpan={2} />
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            );
        });
    };

    // ─── Render: team championship standings ───────────────────────────────────
    const renderTeamStandings = (classTbls: ReturnType<typeof buildClassTablesFromRaces>, raceList: CalendarRace[], keyPrefix = '') => {
        return classTbls.map((cls) => {
            const mode: 'best-car' | 'per-car' | 'f1-dual' = (() => {
                if (['F1', 'F2', 'F3'].includes(shortName)) return 'f1-dual';
                if (shortName === 'WEC' && ['GT3', 'LMP2', 'GTE'].some(c => cls.name.toUpperCase().includes(c)))
                    return 'per-car';
                return 'best-car';
            })();

            const sessionHeaders = raceList.map((race) =>
                race.race_sessions.length > 0
                    ? race.race_sessions.map((session) => (
                        <th key={session.id} className="border border-border px-2 py-1">
                            <Link
                                href={races.show(race.id, { query: { has_sprint: race.sprint_race } }).url}
                                className="hover:underline"
                            >
                                {session.is_sprint
                                    ? `${race.race_code}S`
                                    : race.race_sessions.filter(s => !s.is_sprint).length > 1
                                        ? `${race.race_code} R${session.session_order}${race.endurance ? ' (E)' : ''}`
                                        : `${race.race_code}${race.endurance ? ' (E)' : ''}`}
                            </Link>
                        </th>
                    ))
                    : [<th key={race.id} className="border border-border px-2 py-1">{`${race.race_code}${race.endurance ? ' (E)' : ''}`}</th>]
            );

            const sessionCells = (sessionResults: Map<number, Result>) =>
                raceList.map((race) =>
                    race.race_sessions.length > 0
                        ? race.race_sessions.map((session) => {
                            const result = sessionResults.get(session.id);
                            return (
                                <td key={session.id} className={`px-2 py-1 ${resultCellClass(result)}`}>
                                    {result
                                        ? result.status === 'finished'
                                            ? result.class_position
                                            : result.status?.toUpperCase()
                                        : ''}
                                </td>
                            );
                        })
                        : [<td key={race.id} className="border border-border px-2 py-1" />]
                );

            const denseRank = (pts: number[], idx: number): number => {
                let rank = 1;
                for (let i = 0; i < idx; i++) {
                    if (pts[i] !== pts[idx]) rank++;
                }
                return rank;
            };

            // ── best-car mode ──────────────────────────────────────────
            if (mode === 'best-car') {
                type BCTeam = { teamName: string; bySession: Map<number, Result[]> };
                const teamMap = new Map<number, BCTeam>();
                for (const race of raceList) {
                    for (const result of race.results) {
                        if (result.status !== 'finished' || result.class_position == null) continue;
                        const carInfo = entryCarMap.get(result.entry_car_id);
                        if (!carInfo || !cls.classIds.includes(carInfo.classId)) continue;
                        if (!teamMap.has(carInfo.teamId))
                            teamMap.set(carInfo.teamId, { teamName: carInfo.teamName, bySession: new Map() });
                        const t = teamMap.get(carInfo.teamId)!;
                        if (!t.bySession.has(result.race_session_id)) t.bySession.set(result.race_session_id, []);
                        t.bySession.get(result.race_session_id)!.push(result);
                    }
                }
                type BCRow = { teamName: string; sessionResults: Map<number, Result>; totalPoints: number };
                const rows: BCRow[] = [];
                for (const [, team] of teamMap) {
                    const sessionResults = new Map<number, Result>();
                    let totalPoints = 0;
                    for (const [sessionId, results] of team.bySession) {
                        const best = results.reduce((a, b) =>
                            Number(a.class_position) <= Number(b.class_position) ? a : b
                        );
                        sessionResults.set(sessionId, best);
                        totalPoints += Number(best.points_awarded ?? 0);
                    }
                    rows.push({ teamName: team.teamName, sessionResults, totalPoints });
                }
                rows.sort((a, b) => b.totalPoints - a.totalPoints);
                if (rows.length === 0) return null;
                const pts = rows.map(r => r.totalPoints);
                return (
                    <div key={`${keyPrefix}team-${cls.id}`}>
                        <h5 className="mb-2 border-b border-border pb-1 font-bold">{cls.name} — Team Standings</h5>
                        <div className="overflow-x-auto">
                            <table className="text-sm text-center w-full border-collapse">
                                <thead><tr>
                                    <th className="border border-border px-2 py-1">Pos</th>
                                    <th className="border border-border px-2 py-1 text-left">Team</th>
                                    {sessionHeaders}
                                    <th className="border border-border px-2 py-1 font-bold">Pts</th>
                                </tr></thead>
                                <tbody>
                                    {rows.map((row, idx) => (
                                        <tr key={idx}>
                                            <td className="border border-border px-2 py-1">{denseRank(pts, idx)}</td>
                                            <td className="border border-border px-2 py-1 text-left whitespace-nowrap">{row.teamName}</td>
                                            {sessionCells(row.sessionResults)}
                                            <td className="border border-border px-2 py-1 font-bold">
                                                {Number(row.totalPoints).toFixed(Number.isInteger(row.totalPoints) ? 0 : 1)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                );
            }

            // ── per-car mode ───────────────────────────────────────────
            if (mode === 'per-car') {
                type PCRow = { label: string; sessionResults: Map<number, Result>; totalPoints: number };
                const carMap = new Map<number, PCRow>();
                for (const race of raceList) {
                    for (const result of race.results) {
                        if (result.status !== 'finished' || result.class_position == null) continue;
                        const carInfo = entryCarMap.get(result.entry_car_id);
                        if (!carInfo || !cls.classIds.includes(carInfo.classId)) continue;
                        if (!carMap.has(result.entry_car_id)) {
                            carMap.set(result.entry_car_id, {
                                label: `#${carInfo.carNumber} ${carInfo.teamName}`,
                                sessionResults: new Map(),
                                totalPoints: 0,
                            });
                        }
                        const row = carMap.get(result.entry_car_id)!;
                        row.sessionResults.set(result.race_session_id, result);
                        row.totalPoints += Number(result.points_awarded ?? 0);
                    }
                }
                const rows = Array.from(carMap.values()).sort((a, b) => b.totalPoints - a.totalPoints);
                if (rows.length === 0) return null;
                const pts = rows.map(r => r.totalPoints);
                return (
                    <div key={`${keyPrefix}team-${cls.id}`}>
                        <h5 className="mb-2 border-b border-border pb-1 font-bold">{cls.name} — Team Standings</h5>
                        <div className="overflow-x-auto">
                            <table className="text-sm text-center w-full border-collapse">
                                <thead><tr>
                                    <th className="border border-border px-2 py-1">Pos</th>
                                    <th className="border border-border px-2 py-1 text-left">Car</th>
                                    {sessionHeaders}
                                    <th className="border border-border px-2 py-1 font-bold">Pts</th>
                                </tr></thead>
                                <tbody>
                                    {rows.map((row, idx) => (
                                        <tr key={idx}>
                                            <td className="border border-border px-2 py-1">{denseRank(pts, idx)}</td>
                                            <td className="border border-border px-2 py-1 text-left whitespace-nowrap">{row.label}</td>
                                            {sessionCells(row.sessionResults)}
                                            <td className="border border-border px-2 py-1 font-bold">
                                                {Number(row.totalPoints).toFixed(Number.isInteger(row.totalPoints) ? 0 : 1)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                );
            }

            // ── f1-dual mode ───────────────────────────────────────────
            type F1DriverRow = {
                driverId: number;
                driverName: string;
                carNumber: string;
                sessionResults: Map<number, Result>;
                totalPoints: number;
            };
            type F1TeamRow = {
                teamId: number;
                teamName: string;
                drivers: F1DriverRow[];
                totalPoints: number;
            };

            const driverRowMap = new Map<number, F1DriverRow>();
            const driverTeamMap = new Map<number, number>();
            for (const race of raceList) {
                for (const result of race.results) {
                    const carInfo = entryCarMap.get(result.entry_car_id);
                    if (!carInfo || !cls.classIds.includes(carInfo.classId)) continue;
                    for (const rd of result.result_drivers ?? []) {
                        const driver = rd.driver;
                        if (!driver) continue;
                        driverTeamMap.set(driver.id, carInfo.teamId);
                        if (!driverRowMap.has(driver.id)) {
                            driverRowMap.set(driver.id, {
                                driverId: driver.id,
                                driverName: `${driver.first_name} ${driver.last_name}`,
                                carNumber: carInfo.carNumber,
                                sessionResults: new Map(),
                                totalPoints: 0,
                            });
                        }
                        const dr = driverRowMap.get(driver.id)!;
                        dr.sessionResults.set(result.race_session_id, result);
                        dr.totalPoints += Number(result.points_awarded ?? 0);
                    }
                }
            }

            const f1TeamMap = new Map<number, F1TeamRow>();
            for (const [driverId, driverRow] of driverRowMap) {
                const teamId = driverTeamMap.get(driverId) ?? 0;
                if (!f1TeamMap.has(teamId)) {
                    let teamName = '';
                    for (const [, ci] of entryCarMap) {
                        if (ci.teamId === teamId && cls.classIds.includes(ci.classId)) { teamName = ci.teamName; break; }
                    }
                    f1TeamMap.set(teamId, { teamId, teamName, drivers: [], totalPoints: 0 });
                }
                f1TeamMap.get(teamId)!.drivers.push(driverRow);
            }
            const f1Teams: F1TeamRow[] = [];
            for (const team of f1TeamMap.values()) {
                team.drivers.sort((a, b) => b.totalPoints - a.totalPoints);
                team.totalPoints = team.drivers.reduce((s, d) => s + d.totalPoints, 0);
                f1Teams.push(team);
            }
            f1Teams.sort((a, b) => b.totalPoints - a.totalPoints);
            if (f1Teams.length === 0) return null;
            const teamPts = f1Teams.map(t => t.totalPoints);

            return (
                <div key={`${keyPrefix}team-${cls.id}`}>
                    <h5 className="mb-2 border-b border-border pb-1 font-bold">{cls.name} — Team Standings</h5>
                    <div className="overflow-x-auto">
                        <table className="text-sm text-center w-full border-collapse">
                            <thead><tr>
                                <th className="border border-border px-2 py-1">Pos</th>
                                <th className="border border-border px-2 py-1 text-left">Driver</th>
                                <th className="border border-border px-2 py-1">No.</th>
                                <th className="border border-border px-2 py-1 text-left">Team</th>
                                {sessionHeaders}
                                <th className="border border-border px-2 py-1 font-bold">Pts</th>
                            </tr></thead>
                            <tbody>
                                {f1Teams.map((team, tIdx) =>
                                    team.drivers.map((driver, dIdx) => (
                                        <tr key={`${team.teamId}-${driver.driverId}`}>
                                            {dIdx === 0 && (
                                                <td className="border border-border px-2 py-1" rowSpan={team.drivers.length}>
                                                    {denseRank(teamPts, tIdx)}
                                                </td>
                                            )}
                                            <td className="border border-border px-2 py-1 text-left whitespace-nowrap">
                                                {driver.driverName}
                                            </td>
                                            <td className="border border-border px-2 py-1">#{driver.carNumber}</td>
                                            {dIdx === 0 && (
                                                <td className="border border-border px-2 py-1 text-left whitespace-nowrap" rowSpan={team.drivers.length}>
                                                    {team.teamName}
                                                </td>
                                            )}
                                            {sessionCells(driver.sessionResults)}
                                            {dIdx === 0 && (
                                                <td className="border border-border px-2 py-1 font-bold" rowSpan={team.drivers.length}>
                                                    {Number(team.totalPoints).toFixed(Number.isInteger(team.totalPoints) ? 0 : 1)}
                                                </td>
                                            )}
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            );
        });
    };

    type ClassTable = ReturnType<typeof buildClassTablesFromRaces>[0];

    /** Merge multiple sub-class tables into one combined "Overall" table. */
    const mergeClassTables = (groupName: string, subTables: ClassTable[]): ClassTable => {
        const mergedRows = new Map<number, ClassTable['rows'] extends Map<number, infer V> ? V : never>();
        for (const table of subTables) {
            for (const [driverId, row] of table.rows) {
                if (!mergedRows.has(driverId)) {
                    mergedRows.set(driverId, {
                        ...row,
                        raceResults: new Map(row.raceResults),
                        totalPoints: row.totalOverallPoints,
                        totalOverallPoints: row.totalOverallPoints,
                    });
                } else {
                    const existing = mergedRows.get(driverId)!;
                    for (const [sessionId, result] of row.raceResults) {
                        existing.raceResults.set(sessionId, result);
                    }
                    existing.totalPoints += row.totalOverallPoints;
                    existing.totalOverallPoints += row.totalOverallPoints;
                }
            }
        }
        return {
            id: -(subTables[0].id),
            name: groupName,
            subClass: null,
            classIds: subTables.flatMap(t => t.classIds),
            isOverall: true,
            displayOrder: Math.min(...subTables.map(t => t.displayOrder)),
            rows: mergedRows,
        };
    };

    /** Group classTables by class name; for groups with sub-classes, prepend an Overall merged table. */
    const withOverallTables = (classTbls: ClassTable[]): ClassTable[] => {
        const groups = new Map<string, ClassTable[]>();
        for (const t of classTbls) {
            if (!groups.has(t.name)) groups.set(t.name, []);
            groups.get(t.name)!.push(t);
        }
        const result: ClassTable[] = [];
        for (const [groupName, tables] of groups) {
            const hasSubClasses = tables.some(t => t.subClass !== null);
            if (hasSubClasses && tables.length > 1) {
                const overall = mergeClassTables(groupName, tables);
                result.push(overall, ...tables);
            } else {
                result.push(...tables);
            }
        }
        return result;
    };

    const renderStandingsWithOverall = (
        classTbls: ClassTable[],
        raceList: CalendarRace[],
        keyPrefix = '',
    ) => renderDriverStandings(
        withOverallTables(classTbls).filter(t => t.subClass?.toLowerCase() !== 'pro'),
        raceList,
        keyPrefix,
    );

    const renderTeamStandingsWithOverall = (
        classTbls: ClassTable[],
        raceList: CalendarRace[],
        keyPrefix = '',
    ) => renderTeamStandings(
        withOverallTables(classTbls).filter(t => t.subClass?.toLowerCase() !== 'pro'),
        raceList,
        keyPrefix,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${seriesName} ${season.year}`} />

            {/* ACC Import Modal */}
            {accImport && (
                <Dialog open={accModalOpen} onOpenChange={setAccModalOpen}>
                    <DialogContent className="sm:max-w-[900px] max-h-[80vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>
                                ACC Import — {accImport.race?.gp_name ?? accImport.race?.race_code} (R{accImport.race?.round})
                            </DialogTitle>
                        </DialogHeader>
                        <div className="flex items-center gap-3 mb-3">
                            <p className="text-sm text-muted-foreground flex-1">
                                The following teams/cars from the qualifying file have unmatched entries in this season.
                            </p>
                            <button
                                type="button"
                                onClick={() => router.post(`/seasons/${season.id}/acc/assign-drivers`, {}, { preserveScroll: true, preserveState: true })}
                                className="shrink-0 rounded bg-muted border border-border px-3 py-1.5 text-xs font-medium hover:bg-accent"
                            >
                                Assign All Drivers
                            </button>
                            <button
                                type="button"
                                onClick={handleAssignAll}
                                className="shrink-0 rounded bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700"
                            >
                                Assign All Ready
                            </button>
                        </div>
                        <div className="space-y-4">
                            {accImport.teams.filter(team => team.cars.some(car => !car.car_matched)).map((team) => {
                                const entrantExists = allEntrants.some(e => e.name.toLowerCase() === team.team_name.toLowerCase());
                                return (
                                <div key={team.team_guid} className="rounded-md border border-border overflow-hidden">
                                    <div className="bg-muted/50 px-3 py-2 space-y-2">
                                        <div className="flex items-center gap-2">
                                            <span className="font-semibold text-sm">{team.team_name}</span>
                                            {!team.team_matched && (
                                                <span className="rounded bg-orange-100 px-1.5 py-0.5 text-xs text-orange-700 dark:bg-orange-900/40 dark:text-orange-300">
                                                    Team unmatched
                                                </span>
                                            )}
                                            {!team.team_matched && !entrantExists && (
                                                <a
                                                    href={`/worlds/${world.id}/entrants/create?name=${encodeURIComponent(team.team_name)}${team.nationality !== null ? `&acc_nationality=${team.nationality}` : ''}`}
                                                    className="ml-auto rounded bg-muted border border-border px-2.5 py-1 text-xs font-medium hover:bg-accent"
                                                >
                                                    Create Entrant
                                                </a>
                                            )}
                                        </div>
                                        {!team.team_matched && (
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <select
                                                    value={teamCreate[team.team_guid]?.entrantId ?? ''}
                                                    onChange={e => {
                                                        const entrantId = e.target.value;
                                                        setTeamField(team.team_guid, 'entrantId', entrantId);
                                                        const entrant = allEntrants.find(en => String(en.id) === entrantId);
                                                        if (entrant && entrant.name !== team.team_name) {
                                                            setTeamField(team.team_guid, 'displayName', team.team_name);
                                                        } else {
                                                            setTeamField(team.team_guid, 'displayName', '');
                                                        }
                                                    }}
                                                    className="rounded border border-border bg-background px-2 py-1 text-xs text-foreground"
                                                >
                                                    <option value="" disabled>Entrant…</option>
                                                    {allEntrants.map(e => (
                                                        <option key={e.id} value={e.id}>{e.name}</option>
                                                    ))}
                                                </select>
                                                <select
                                                    value={teamCreate[team.team_guid]?.constructorId ?? ''}
                                                    onChange={e => setTeamField(team.team_guid, 'constructorId', e.target.value)}
                                                    className="rounded border border-border bg-background px-2 py-1 text-xs text-foreground"
                                                >
                                                    <option value="" disabled>Constructor…</option>
                                                    {constructors.map(c => (
                                                        <option key={c.id} value={c.id}>{c.name}</option>
                                                    ))}
                                                </select>
                                                <input
                                                    type="text"
                                                    placeholder="Display name (optional)"
                                                    value={teamCreate[team.team_guid]?.displayName ?? ''}
                                                    onChange={e => setTeamField(team.team_guid, 'displayName', e.target.value)}
                                                    className="rounded border border-border bg-background px-2 py-1 text-xs text-foreground w-44"
                                                />
                                                <button
                                                    type="button"
                                                    disabled={!teamCreate[team.team_guid]?.entrantId || !teamCreate[team.team_guid]?.constructorId}
                                                    onClick={() => handleCreateEntry(team.team_guid)}
                                                    className="rounded bg-green-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-green-700 disabled:opacity-40 disabled:cursor-not-allowed"
                                                >
                                                    Create
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                    <table className="w-full text-sm">
                                        <tbody>
                                            {team.cars.map((car) => {
                                                const assignment = carAssign[car.car_number] ?? { entrantId: '', classId: '', carModelId: '' };
                                                const models = filteredCarModels(car.car_number);
                                                const canAssign = !!assignment.entrantId && !!assignment.classId && !!assignment.carModelId;
                                                return (
                                                    <tr key={car.car_number} className="border-t border-border/50">
                                                        <td className="py-2 pl-3 pr-4 font-mono font-bold align-top">
                                                            <div>#{car.car_number}</div>
                                                            <div className="font-normal text-muted-foreground text-xs mt-0.5 whitespace-nowrap">
                                                                {car.drivers.map((d: AccDriver) => `${d.first_name} ${d.last_name}`).join(', ')}
                                                            </div>
                                                        </td>
                                                        <td className="py-2 pr-4 text-muted-foreground text-xs align-top italic">
                                                            {car.livery_differs && car.livery_name}
                                                        </td>
                                                        <td className="py-2 pr-3 align-top">
                                                            {assignedCars.has(car.car_number) ? (
                                                                <span className="text-xs text-green-600 font-semibold">Assigned ✓</span>
                                                            ) : !car.car_matched ? (
                                                                <div className="flex items-center gap-2 flex-wrap justify-end">
                                                                    <select
                                                                        value={assignment.entrantId}
                                                                        onChange={e => setCarField(car.car_number, 'entrantId', e.target.value)}
                                                                        className="rounded border border-border bg-background px-2 py-1 text-xs text-foreground"
                                                                    >
                                                                        <option value="" disabled>Team…</option>
                                                                        {accImport.entrants.map(e => (
                                                                            <option key={e.id} value={e.id}>{e.display_name}</option>
                                                                        ))}
                                                                    </select>
                                                                    <select
                                                                        value={assignment.classId}
                                                                        onChange={e => setCarField(car.car_number, 'classId', e.target.value)}
                                                                        disabled={!assignment.entrantId}
                                                                        className="rounded border border-border bg-background px-2 py-1 text-xs text-foreground disabled:opacity-50"
                                                                    >
                                                                        <option value="" disabled>Class…</option>
                                                                        {season.season_classes.map(c => (
                                                                            <option key={c.id} value={c.id}>
                                                                                {c.name}{c.sub_class ? ` - ${c.sub_class}` : ''}
                                                                            </option>
                                                                        ))}
                                                                    </select>
                                                                    <select
                                                                        value={assignment.carModelId}
                                                                        onChange={e => setCarField(car.car_number, 'carModelId', e.target.value)}
                                                                        disabled={!assignment.entrantId}
                                                                        className="rounded border border-border bg-background px-2 py-1 text-xs text-foreground disabled:opacity-50"
                                                                    >
                                                                        <option value="" disabled>Car model…</option>
                                                                        {models.map(m => (
                                                                            <option key={m.id} value={m.id}>{m.name}</option>
                                                                        ))}
                                                                    </select>
                                                                    <button
                                                                        type="button"
                                                                        disabled={!canAssign}
                                                                        onClick={() => handleAssign(team.team_name, car, assignment.entrantId, assignment.classId, assignment.carModelId)}
                                                                        className="rounded bg-blue-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed"
                                                                    >
                                                                        Assign
                                                                    </button>
                                                                </div>
                                                            ) : null}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            );
                            })}
                        </div>
                    </DialogContent>
                </Dialog>
            )}
            <div className="flex h-full flex-1 flex-col gap-4 p-6">

                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">{season.name ?? `${seriesName} ${season.year}`}</h1>
                        <p className="text-sm text-muted-foreground">
                            Year: {season.year} · Series: {seriesName}
                        </p>
                    </div>
                    <Link
                        href={`/worlds/${world.id}/seasons/${season.id}/edit`}
                        className="inline-flex items-center rounded-md border border-border bg-card px-4 py-2 text-sm font-medium hover:bg-accent"
                    >
                        Edit Season
                    </Link>
                </div>

                {/* Tab nav */}
                <div className="flex gap-0 border-b border-border">
                    <Link
                        href={seasons.show(season.id, { query: { tab: 'calender' } }).url}
                        className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${
                            currentTab === 'calender'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        Results
                    </Link>
                    <Link
                        href={seasons.show(season.id, { query: { tab: 'teams' } }).url}
                        className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${
                            currentTab === 'teams'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        Teams
                    </Link>
                </div>

                {/* RESULTS TAB */}
                {currentTab === 'calender' && (
                    <div className="flex flex-col gap-8">
                        {/* Championship clinch scenarios */}
                        {Object.keys(classScenarios).length > 1000 && (
                            <div>
                                <h4 className="mb-3 text-center font-bold text-lg">
                                    Potential Champion{classTables.length > 1 ? 's' : ''}
                                </h4>
                                <div className="flex flex-wrap justify-evenly gap-6">
                                    {classTables.map((cls) => {
                                        const scenario = classScenarios[cls.id];
                                        if (!scenario) return null;
                                        return (
                                            <div key={cls.id} className="flex flex-col items-center">
                                                <h4 className="mb-2 font-semibold uppercase border-b border-border pb-1">
                                                    {cls.name}
                                                </h4>
                                                <p className="mb-2 text-sm italic">
                                                    How can #{scenario?.leader?.entryCar?.car_number ?? 0} become champion
                                                </p>
                                                <table className="border-collapse text-sm">
                                                    <thead>
                                                        <tr>
                                                            <th className="border border-border px-3 py-1 bg-gray-900 text-white">
                                                                Leader (#{scenario?.leader?.entryCar?.car_number ?? 0})
                                                            </th>
                                                            {(scenario?.rivals ?? []).map((rival) => (
                                                                <th
                                                                    key={rival.entry_car_id}
                                                                    className="border border-border px-3 py-1 bg-gray-900 text-white"
                                                                >
                                                                    #{rival.entryCar.car_number} needs to be
                                                                </th>
                                                            ))}
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {scenario.rows.map((row, i) => (
                                                            <tr key={i}>
                                                                <td className="border border-border px-3 py-1 font-bold bg-gray-50">
                                                                    P{row.leader_pos}
                                                                </td>
                                                                {row.next_race ? (
                                                                    <td
                                                                        colSpan={scenario.rivals.length}
                                                                        className="border border-border px-3 py-1 text-center text-gray-500 bg-gray-100"
                                                                    >
                                                                        Go to next race
                                                                    </td>
                                                                ) : (
                                                                    scenario.rivals.map((rival) => {
                                                                        const pos = row.rivals[rival.entry_car_id] ?? null;
                                                                        if (!pos) {
                                                                            return (
                                                                                <td
                                                                                    key={rival.entry_car_id}
                                                                                    className="border border-border px-3 py-1 bg-yellow-200 font-semibold text-center"
                                                                                >
                                                                                    —
                                                                                </td>
                                                                            );
                                                                        }
                                                                        return (
                                                                            <td
                                                                                key={rival.entry_car_id}
                                                                                className={`border border-border px-3 py-1 text-center ${pos > 10 ? 'bg-gray-100 text-gray-500' : 'bg-green-100 font-semibold'}`}
                                                                            >
                                                                                {pos > 10
                                                                                    ? 'Next Race'
                                                                                    : `${ordinalSuffix(pos)}${pos > 1 ? ' or lower' : ''}`}
                                                                            </td>
                                                                        );
                                                                    })
                                                                )}
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

                        {/* Championship sub-tabs (only when season mixes endurance + regular races) */}
                        {hasEnduranceMix && (
                            <div className="flex gap-0 border-b border-border">
                                {(['overall', 'sprint', 'endurance'] as const).map((tab) => (
                                    <button
                                        key={tab}
                                        type="button"
                                        onClick={() => setChampSubTab(tab)}
                                        className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors capitalize ${
                                            champSubTab === tab
                                                ? 'border-primary text-primary'
                                                : 'border-transparent text-muted-foreground hover:text-foreground'
                                        }`}
                                    >
                                        {tab === 'overall' ? 'Overall' : tab === 'sprint' ? 'Sprint' : 'Endurance'}
                                    </button>
                                ))}
                            </div>
                        )}

                        {/* Standings — filtered by champSubTab when hasEnduranceMix */}
                        {(!hasEnduranceMix || champSubTab === 'overall') && (
                            <>
                                {renderStandingsWithOverall(classTables, sortedRaces, 'overall-')}
                                {renderTeamStandingsWithOverall(classTables, sortedRaces, 'overall-')}
                            </>
                        )}
                        {hasEnduranceMix && champSubTab === 'endurance' && (
                            <>
                                {renderStandingsWithOverall(enduranceClassTables, enduranceSortedRaces, 'end-')}
                                {renderTeamStandingsWithOverall(enduranceClassTables, enduranceSortedRaces, 'end-')}
                            </>
                        )}
                        {hasEnduranceMix && champSubTab === 'sprint' && (
                            <>
                                {renderStandingsWithOverall(regularClassTables, regularSortedRaces, 'sprint-')}
                                {renderTeamStandingsWithOverall(regularClassTables, regularSortedRaces, 'sprint-')}
                            </>
                        )}
                    </div>
                )}

                {/* TEAMS TAB */}
                {currentTab === 'teams' && (
                    <div className="flex flex-col gap-8">
                        {season.season_classes.map((sc) => {
                            type GroupedCar = {
                                groupKey: string;
                                carModelName: string;
                                engineName: string;
                                hybrid: boolean;
                                cars: (EntryCar & { entrantName: string })[];
                            };
                            const groups = new Map<string, GroupedCar>();

                            for (const entry of season.season_entries) {
                                for (const ec of entry.entry_classes) {
                                    if (ec.race_class_id !== sc.id) continue;
                                    for (const car of ec.entry_cars) {
                                        const modelId = car.car_model?.engine?.name ?? 'noengine';
                                        const groupKey = `${entry.id}_${car.car_model?.name}_${modelId}`;
                                        if (!groups.has(groupKey)) {
                                            groups.set(groupKey, {
                                                groupKey,
                                                carModelName: car.car_model?.name ?? '—',
                                                engineName: car.car_model?.engine?.name ?? '—',
                                                hybrid: car.car_model?.hybrid ?? false,
                                                cars: [],
                                            });
                                        }
                                        groups.get(groupKey)!.cars.push({
                                            ...car,
                                            entrantName:
                                                entry.display_name ?? entry.entrant?.name ?? '—',
                                        });
                                    }
                                }
                            }

                            const groupList = Array.from(groups.values());

                            return (
                                <div key={sc.id}>
                                    <h4 className="mb-2 uppercase font-bold border-b border-border pb-1">
                                        {sc.name}
                                    </h4>
                                    <div className="overflow-x-auto">
                                        <table className="text-sm w-full border-collapse">
                                            <thead>
                                                <tr className="bg-muted/50">
                                                    <th className="border border-border px-3 py-1.5 text-left">Entrant</th>
                                                    <th className="border border-border px-3 py-1.5 text-left">Car</th>
                                                    <th className="border border-border px-3 py-1.5 text-left">Engine</th>
                                                    <th className="border border-border px-3 py-1.5 text-left">Hybrid</th>
                                                    <th className="border border-border px-3 py-1.5 text-center">No.</th>
                                                    <th className="border border-border px-3 py-1.5 text-left">Drivers</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {groupList.map((group) =>
                                                    [...group.cars]
                                                        .sort((a, b) => Number(a.car_number) - Number(b.car_number))
                                                        .map((car, carIdx) => (
                                                            <tr key={car.id}>
                                                                {carIdx === 0 && (
                                                                    <td
                                                                        rowSpan={group.cars.length}
                                                                        className="border border-border px-3 py-1.5 font-semibold align-middle"
                                                                    >
                                                                        {car.entrantName}
                                                                    </td>
                                                                )}
                                                                {carIdx === 0 && (
                                                                    <>
                                                                        <td
                                                                            rowSpan={group.cars.length}
                                                                            className="border border-border px-3 py-1.5 align-middle"
                                                                        >
                                                                            {group.carModelName}
                                                                        </td>
                                                                        <td
                                                                            rowSpan={group.cars.length}
                                                                            className="border border-border px-3 py-1.5 align-middle"
                                                                        >
                                                                            {group.engineName}
                                                                        </td>
                                                                        <td
                                                                            rowSpan={group.cars.length}
                                                                            className="border border-border px-3 py-1.5 align-middle"
                                                                        >
                                                                            {group.hybrid ? 'Hybrid' : '—'}
                                                                        </td>
                                                                    </>
                                                                )}
                                                                <td className="border border-border px-3 py-1.5 text-center font-bold">
                                                                    #{car.car_number}
                                                                </td>
                                                                <td className="border border-border px-3 py-1.5">
                                                                    {car.drivers.length > 0 ? (
                                                                        car.drivers.map((d) => (
                                                                            <div key={d.id}>
                                                                                {d.first_name} {d.last_name}
                                                                            </div>
                                                                        ))
                                                                    ) : (
                                                                        <span className="italic text-muted-foreground">
                                                                            No drivers
                                                                        </span>
                                                                    )}
                                                                </td>
                                                            </tr>
                                                        ))
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
