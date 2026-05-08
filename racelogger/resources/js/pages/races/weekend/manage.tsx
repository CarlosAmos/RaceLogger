import { useState, useMemo, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Flag } from 'lucide-react';
import type { BreadcrumbItem } from '@/types';
import * as seasons from '@/routes/seasons';
import * as racesWeekend from '@/routes/races/weekend';

console.log('[manage.tsx] MODULE LOADED');

// ─── Types ────────────────────────────────────────────────────────────────────

interface Driver { id: number; first_name: string; last_name: string }
interface RaceClass { id: number; name: string; display_order: number; sub_class?: string | null }
interface EntryClassRelation {
    id: number;
    race_class: RaceClass | null;
    season_entry: { entrant: { name: string } } | null;
}
interface CarModel { id: number; name: string }
interface EntryCar {
    id: number;
    car_number: number;
    livery_name: string | null;
    entry_class: EntryClassRelation | null;
    car_model: CarModel | null;
    drivers: Driver[];
    effective_from_round: number;
}
interface EntryClassWithCars {
    id: number;
    race_class: RaceClass | null;
    entry_cars: EntryCar[];
}
interface SeasonEntry { id: number; entry_classes: EntryClassWithCars[] }
interface Season { id: number; year: number; season_entries: SeasonEntry[] }

interface QualifyingResult { id: number; entry_car_id: number; position: number; best_lap_time_ms: number | null }
interface QualifyingSession {
    id: number;
    name: string;
    session_order: number;
    race_number: number;
    results: QualifyingResult[];
}

interface RaceResult {
    id: number;
    entry_car_id: number;
    position: number;
    status: string;
    laps_completed: number | null;
    gap_to_leader_ms: number | null;
    gap_laps_down: number | null;
    fastest_lap_time_ms: number | null;
}
interface RaceSession { id: number; name: string; is_sprint: boolean; session_order: number; results: RaceResult[] }

interface CalendarRace {
    id: number;
    gp_name: string;
    race_code: string;
    season_id: number;
    number_of_races: number;
    endurance: number;
    stage_names?: { name: string; point_system_id: number | null }[] | null;
    season: Season;
    entry_cars: EntryCar[];
    race_sessions: RaceSession[];
    qualifying_sessions: QualifyingSession[];
}

interface AccQualEntry { race_number: number; best_lap_ms: number | null; cup_category?: number | null }
interface AccRaceEntry { race_number: number; lap_count: number; fastest_lap_ms: number | null; gap_ms: number | null; gap_laps: number | null }
interface AccSessionData { qualifying?: Record<number, AccQualEntry[]>; race?: Record<number, AccRaceEntry[]>; stages?: Record<number, AccRaceEntry[]> }

interface Props {
    race: CalendarRace;
    defaultTab: string;
    activeRaceSession: RaceSession;
    raceSessionsByNumber: Record<number, RaceSession>;
    sprintRaceSession: RaceSession | null;
    hasSprint: string | null;
    stageNames?: { name: string; point_system_id: number | null }[] | null;
    accSessionData?: AccSessionData;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

interface ClassGroup { classId: number; className: string; displayOrder: number; cars: EntryCar[] }

function buildRaceCarGroups(entryCars: EntryCar[]): ClassGroup[] {
    const groups = new Map<number, ClassGroup>();
    for (const car of entryCars) {
        const rc = car.entry_class?.race_class;
        if (!rc) continue;
        if (!groups.has(rc.id)) {
            groups.set(rc.id, { classId: rc.id, className: rc.name, displayOrder: rc.display_order, cars: [] });
        }
        groups.get(rc.id)!.cars.push(car);
    }
    return [...groups.values()]
        .sort((a, b) => a.displayOrder - b.displayOrder)
        .map(g => ({ ...g, cars: [...g.cars].sort((a, b) => a.car_number - b.car_number) }));
}

/** Qualifying groups: classes with the same name are merged into one table. */
function buildQualCarGroups(entryCars: EntryCar[]): ClassGroup[] {
    const groups = new Map<string, ClassGroup>();
    for (const car of entryCars) {
        const rc = car.entry_class?.race_class;
        if (!rc) continue;
        if (!groups.has(rc.name)) {
            groups.set(rc.name, { classId: rc.id, className: rc.name, displayOrder: rc.display_order, cars: [] });
        }
        groups.get(rc.name)!.cars.push(car);
    }
    return [...groups.values()]
        .sort((a, b) => a.displayOrder - b.displayOrder)
        .map(g => ({ ...g, cars: [...g.cars].sort((a, b) => a.car_number - b.car_number) }));
}

function buildSeasonCarGroups(race: CalendarRace): ClassGroup[] {
    const groups = new Map<number, ClassGroup>();
    for (const entry of race.season?.season_entries ?? []) {
        for (const ec of entry.entry_classes ?? []) {
            const rc = ec.race_class;
            if (!rc) continue;
            if (!groups.has(rc.id)) {
                groups.set(rc.id, { classId: rc.id, className: rc.name, displayOrder: rc.display_order, cars: [] });
            }
            for (const car of ec.entry_cars ?? []) {
                groups.get(rc.id)!.cars.push(car);
            }
        }
    }
    return [...groups.values()]
        .sort((a, b) => a.displayOrder - b.displayOrder)
        .map(g => ({ ...g, cars: [...g.cars].sort((a, b) => a.car_number - b.car_number) }));
}

function msToLap(ms: number | null | undefined): string {
    if (!ms) return '';
    const minutes = Math.floor(ms / 60000);
    const seconds = Math.floor((ms % 60000) / 1000);
    const millis = ms % 1000;
    return `${minutes}:${String(seconds).padStart(2, '0')}:${String(millis).padStart(3, '0')}`;
}

function formatGapResult(r: RaceResult): string {
    if (r.gap_laps_down) return `+${r.gap_laps_down}L`;
    if (r.gap_to_leader_ms) return msToLap(r.gap_to_leader_ms);
    return '';
}

function formatLap(raw: string): string {
    let d = raw.replace(/\D/g, '');
    if (d.length > 6) d = d.substring(0, 6);
    if (d.length <= 1) return d;
    if (d.length <= 3) return d[0] + ':' + d.substring(1);
    return d[0] + ':' + d.substring(1, 3) + ':' + d.substring(3);
}

function formatGap(raw: string): string {
    if (raw.startsWith('+')) return raw.replace(/[^+\dL]/gi, '').toUpperCase();
    let d = raw.replace(/\D/g, '');
    if (d.length > 6) d = d.substring(0, 6);
    if (d.length <= 1) return d;
    if (d.length <= 3) return d[0] + ':' + d.substring(1);
    return d[0] + ':' + d.substring(1, 3) + ':' + d.substring(3);
}

type QualSlot = { entryCarId: string; bestLap: string };

function buildQualData(classGroups: ClassGroup[], savedSessions: QualifyingSession[]): Record<string, QualSlot> {
    const data: Record<string, QualSlot> = {};
    savedSessions.forEach((session, si) => {
        session.results.forEach(result => {
            if (!result.entry_car_id) return;
            const gi = classGroups.findIndex(g => g.cars.some(c => c.id === result.entry_car_id));
            if (gi === -1) return;
            data[`${si}:${gi}:${result.position}`] = {
                entryCarId: String(result.entry_car_id),
                bestLap: msToLap(result.best_lap_time_ms),
            };
        });
    });
    return data;
}

type RaceSlot = { entryCarId: string; status: string; laps: string; gap: string; fastestLap: string };

function buildRaceData(classGroups: ClassGroup[], savedResults: RaceResult[]): RaceSlot[] {
    const total = classGroups.reduce((s, g) => s + g.cars.length, 0);
    return Array.from({ length: total }, (_, i) => {
        const saved = savedResults.find(r => r.position === i + 1);
        return {
            entryCarId: saved ? String(saved.entry_car_id) : '',
            status: saved?.status ?? 'finished',
            laps: saved?.laps_completed != null ? String(saved.laps_completed) : '',
            gap: saved ? formatGapResult(saved) : '',
            fastestLap: saved ? msToLap(saved.fastest_lap_time_ms) : '',
        };
    });
}

function carLabel(car: EntryCar): string {
    const name = car.livery_name ?? car.entry_class?.season_entry?.entrant?.name ?? '';
    const model = car.car_model?.name ?? '';
    const drivers = car.drivers.map(d => `${d.first_name} ${d.last_name}`).join(', ');
    let label = `#${car.car_number} ${name}`;
    if (model) label += ` (${model})`;
    if (car.effective_from_round > 1) label += ` [from R${car.effective_from_round}]`;
    if (drivers) label += ` - ${drivers}`;
    return label;
}

/** Extract number from a tab key like 'q_2', 'r_3', or 's_1'. Returns 1 for single-race tabs. */
function raceNumberFromTab(tab: string): number {
    if (tab.startsWith('q_') || tab.startsWith('r_') || tab.startsWith('s_')) {
        return parseInt(tab.split('_')[1], 10);
    }
    return 1;
}

function isQualTab(tab: string): boolean {
    return tab === 'qualifying' || tab.startsWith('q_');
}

function isRaceTab(tab: string): boolean {
    return tab === 'race' || tab.startsWith('r_');
}

function isStageTab(tab: string): boolean {
    return tab.startsWith('s_');
}

// ─── Sub-components ────────────────────────────────────────────────────────────

function LapInput({ value, onChange }: { value: string; onChange: (v: string) => void }) {
    return (
        <Input
            value={value}
            onChange={e => onChange(formatLap(e.target.value))}
            placeholder="0:00:000"
            maxLength={8}
            className="w-28 font-mono text-center"
        />
    );
}

function GapTimeInput({ value, onChange }: { value: string; onChange: (v: string) => void }) {
    return (
        <Input
            value={value}
            onChange={e => onChange(formatGap(e.target.value))}
            placeholder="0:00:000 or +1L"
            maxLength={10}
            className="w-32 font-mono"
        />
    );
}

function CarNumberQuickSelect({
    cars,
    disabledIds,
    currentId,
    onSelect,
}: {
    cars: EntryCar[];
    disabledIds: Set<string>;
    currentId: string;
    onSelect: (id: string) => void;
}) {
    const currentCar = cars.find(c => String(c.id) === currentId);
    const [value, setValue] = useState(currentCar ? String(currentCar.car_number ?? '') : '');

    useEffect(() => {
        const car = cars.find(c => String(c.id) === currentId);
        setValue(car ? String(car.car_number ?? '') : '');
    }, [currentId]);

    function handleChange(e: React.ChangeEvent<HTMLInputElement>) {
        const input = e.target.value;
        setValue(input);
        if (!input) return;
        const match = cars.find(
            c =>
                String(c.car_number) === input &&
                (!disabledIds.has(String(c.id)) || currentId === String(c.id))
        );
        if (match) {
            onSelect(String(match.id));
        }
    }

    return (
        <Input
            type="number"
            value={value}
            onChange={handleChange}
            placeholder="#"
            className="w-14 text-center [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
        />
    );
}

// ─── Main Component ────────────────────────────────────────────────────────────

const STATUSES = ['finished', 'dnf', 'dsq', 'dns'] as const;

export default function ManageWeekend({
    race,
    defaultTab,
    activeRaceSession,
    raceSessionsByNumber,
    sprintRaceSession,
    hasSprint,
    stageNames,
    accSessionData,
}: Props) {
    console.log('[ManageWeekend] COMPONENT FUNCTION CALLED');
    const showSprint    = hasSprint === '1' || hasSprint === 1 as unknown;
    const numberOfRaces = race.number_of_races ?? 1;
    const multiRace     = numberOfRaces > 1;
    const hasStages     = !!(stageNames?.length);
    const stageCount    = stageNames?.length ?? 0;

    const seasonCarGroups = useMemo(() => buildSeasonCarGroups(race), []);
    const raceCarGroups   = useMemo(() => buildRaceCarGroups(race.entry_cars ?? []), [race.entry_cars]);
    const qualCarGroups   = useMemo(() => buildQualCarGroups(race.entry_cars ?? []), [race.entry_cars]);

    const [activeTab, setActiveTab] = useState<string>(defaultTab ?? 'participants');
    const [selectedCarIds, setSelectedCarIds] = useState<Set<number>>(
        () => new Set((race.entry_cars ?? []).map(c => c.id))
    );

    useEffect(() => {
        console.log('[ManageWeekend] Mount — defaultTab:', defaultTab);
        console.log('[ManageWeekend] race.entry_cars count:', race.entry_cars?.length ?? 0);
        console.log('[ManageWeekend] accSessionData:', accSessionData);
        console.log('[ManageWeekend] qualifying keys:', Object.keys(accSessionData?.qualifying ?? {}));
        console.log('[ManageWeekend] seasonCarGroups:', seasonCarGroups.map(g => ({ class: g.className, carCount: g.cars.length })));
    }, []);

    // Per-race qualifying format (number of Q sessions)
    const [qualFormatByRace, setQualFormatByRace] = useState<Record<number, number>>(() => {
        const result: Record<number, number> = {};
        for (let rn = 1; rn <= numberOfRaces; rn++) {
            const sessions = (race.qualifying_sessions ?? []).filter(s => (s.race_number ?? 1) === rn);
            result[rn] = sessions.length > 0 ? sessions.length : 1;
        }
        return result;
    });

    // Per-race qualifying slot data
    const [qualDataByRace, setQualDataByRace] = useState<Record<number, Record<string, QualSlot>>>(() => {
        const result: Record<number, Record<string, QualSlot>> = {};
        for (let rn = 1; rn <= numberOfRaces; rn++) {
            const sessions = (race.qualifying_sessions ?? []).filter(s => (s.race_number ?? 1) === rn);
            result[rn] = buildQualData(qualCarGroups, sessions);
        }
        return result;
    });

    // Per-race / per-stage result rows
    const [raceDataByRace, setRaceDataByRace] = useState<Record<number, RaceSlot[]>>(() => {
        const result: Record<number, RaceSlot[]> = {};
        const count = hasStages ? stageCount : numberOfRaces;
        for (let n = 1; n <= count; n++) {
            const session = raceSessionsByNumber?.[n] ?? (n === 1 && !hasStages ? activeRaceSession : null);
            result[n] = buildRaceData(raceCarGroups, session?.results ?? []);
        }
        if (hasStages) {
            const finalSession = raceSessionsByNumber?.[stageCount + 1] ?? activeRaceSession;
            result[stageCount + 1] = buildRaceData(raceCarGroups, finalSession?.results ?? []);
        }
        return result;
    });

    // Sprint (single-race only)
    const [sprintData, setSprintData] = useState<RaceSlot[]>(
        () => buildRaceData(raceCarGroups, sprintRaceSession?.results ?? [])
    );

    const participantsExist = (race.entry_cars?.length ?? 0) > 0;
    const submitUrl = racesWeekend.update(race.id, { query: { has_sprint: hasSprint ?? 0 } }).url;
    const form = useForm({});

    const CUP_CATEGORY_LABEL: Record<number, string> = { 0: 'Pro', 1: 'Pro-Am', 2: 'Am', 3: 'Silver' };

    function autoAssignFromAcc() {
        console.log('[AutoAssign] Function called');
        if (!accSessionData?.qualifying) {
            console.log('[AutoAssign] No qualifying data available');
            return;
        }

        // Collect unique raceNumber → cupCategory across all qualifying sessions
        const accCarMap = new Map<number, number | null>();
        for (const session of Object.values(accSessionData.qualifying)) {
            for (const entry of session) {
                if (!accCarMap.has(entry.race_number)) {
                    accCarMap.set(entry.race_number, entry.cup_category ?? null);
                }
            }
        }

        console.log('[AutoAssign] ACC car numbers:', [...accCarMap.keys()]);
        console.log('[AutoAssign] Season car groups:', seasonCarGroups.map(g => ({ class: g.className, cars: g.cars.map(c => c.car_number) })));

        if (accCarMap.size === 0) {
            console.log('[AutoAssign] accCarMap is empty, aborting');
            return;
        }

        const matchedIds = new Set<number>();

        for (const [raceNumber, cupCategory] of accCarMap) {
            const targetSubClass = cupCategory !== null ? (CUP_CATEGORY_LABEL[cupCategory] ?? null) : null;
            let matched = false;

            // Prefer matching by car_number + sub_class (handles same car_number in different classes)
            if (targetSubClass !== null) {
                for (const group of seasonCarGroups) {
                    for (const car of group.cars) {
                        if (Number(car.car_number) === raceNumber &&
                            car.entry_class?.race_class?.sub_class === targetSubClass) {
                            matchedIds.add(car.id);
                            matched = true;
                        }
                    }
                }
            }

            // Fall back to car_number only when sub_class is not available
            if (!matched) {
                for (const group of seasonCarGroups) {
                    for (const car of group.cars) {
                        if (Number(car.car_number) === raceNumber) {
                            matchedIds.add(car.id);
                        }
                    }
                }
            }
        }

        console.log('[AutoAssign] Matched car IDs:', [...matchedIds]);
        setSelectedCarIds(new Set(matchedIds));
    }

    function submit(action: 'save' | 'complete' = 'save') {
        const raceNumber = raceNumberFromTab(activeTab);

        if (activeTab === 'participants') {
            console.log('[Submit] participants selectedCarIds:', [...selectedCarIds]);
            form.transform(() => ({
                submitted_tab: 'participants',
                action,
                participants: [...selectedCarIds],
            }));

        } else if (isQualTab(activeTab)) {
            const qualFormat = qualFormatByRace[raceNumber] ?? 1;
            const qualData   = qualDataByRace[raceNumber] ?? {};
            form.transform(() => ({
                submitted_tab: activeTab,
                action,
                qualifying: {
                    format: qualFormat,
                    sessions: Array.from({ length: qualFormat }, (_, si) => ({
                        results: qualCarGroups.flatMap((group, gi) =>
                            group.cars.map((_, posIdx) => {
                                const pos  = posIdx + 1;
                                const slot = qualData[`${si}:${gi}:${pos}`];
                                return {
                                    position:     pos,
                                    entry_car_id: slot?.entryCarId || null,
                                    best_lap:     slot?.bestLap    || null,
                                };
                            })
                        ),
                    })),
                },
            }));

        } else if (activeTab === 'sprint_race') {
            form.transform(() => ({
                submitted_tab: 'sprint_race',
                action,
                sprint_race_session_id: sprintRaceSession?.id ?? 0,
                spr_results: sprintData.map((slot, i) => ({
                    position:      i + 1,
                    entry_car_id:  slot.entryCarId || null,
                    status:        slot.status,
                    laps_completed: slot.laps       || null,
                    gap:           slot.gap         || null,
                    fastest_lap:   slot.fastestLap  || null,
                })),
            }));

        } else if (isStageTab(activeTab)) {
            const stageNum = raceNumberFromTab(activeTab);
            const raceData = raceDataByRace[stageNum] ?? [];
            const session  = raceSessionsByNumber?.[stageNum] ?? null;
            form.transform(() => ({
                submitted_tab:  activeTab,
                action,
                race_session_id: session?.id,
                results: raceData.map((slot, i) => ({
                    position:      i + 1,
                    entry_car_id:  slot.entryCarId || null,
                    status:        slot.status,
                    laps_completed: slot.laps       || null,
                    gap:           slot.gap         || null,
                    fastest_lap:   slot.fastestLap  || null,
                })),
            }));

        } else if (isRaceTab(activeTab)) {
            const dataKey  = hasStages ? stageCount + 1 : raceNumber;
            const raceData = raceDataByRace[dataKey] ?? [];
            const session  = hasStages
                ? (raceSessionsByNumber?.[stageCount + 1] ?? activeRaceSession)
                : (raceSessionsByNumber?.[raceNumber] ?? (raceNumber === 1 ? activeRaceSession : null));
            form.transform(() => ({
                submitted_tab:  activeTab,
                action,
                race_session_id: session?.id,
                results: raceData.map((slot, i) => ({
                    position:      i + 1,
                    entry_car_id:  slot.entryCarId || null,
                    status:        slot.status,
                    laps_completed: slot.laps       || null,
                    gap:           slot.gap         || null,
                    fastest_lap:   slot.fastestLap  || null,
                })),
            }));
        }

        form.post(submitUrl);
    }

    function toggleCar(carId: number) {
        setSelectedCarIds(prev => {
            const next = new Set(prev);
            if (next.has(carId)) next.delete(carId);
            else next.add(carId);
            return next;
        });
    }

    function toggleSelectAll(group: ClassGroup) {
        const carsWithDrivers = group.cars.filter(c => c.drivers.length > 0);
        const allSelected = carsWithDrivers.length > 0 && carsWithDrivers.every(c => selectedCarIds.has(c.id));
        setSelectedCarIds(prev => {
            const next = new Set(prev);
            if (allSelected) {
                group.cars.forEach(c => next.delete(c.id));
            } else {
                carsWithDrivers.forEach(c => next.add(c.id));
            }
            return next;
        });
    }

    function setQualSlot(raceNum: number, si: number, gi: number, pos: number, patch: Partial<QualSlot>) {
        const key = `${si}:${gi}:${pos}`;
        setQualDataByRace(prev => ({
            ...prev,
            [raceNum]: {
                ...prev[raceNum],
                [key]: { ...(prev[raceNum]?.[key] ?? { entryCarId: '', bestLap: '' }), ...patch },
            },
        }));
    }

    function setRaceSlot(raceNum: number, index: number, patch: Partial<RaceSlot>) {
        setRaceDataByRace(prev => ({
            ...prev,
            [raceNum]: (prev[raceNum] ?? []).map((r, i) => i === index ? { ...r, ...patch } : r),
        }));
    }

    function setSprintSlot(index: number, patch: Partial<RaceSlot>) {
        setSprintData(prev => prev.map((r, i) => i === index ? { ...r, ...patch } : r));
    }

    /**
     * Import a single qualifying session from ACC JSON into the form.
     * fileIndex = the Qualifying_N.json number (1-based, sequential across the whole weekend).
     * si        = the session slot index (0-based) within the qualifying panel to fill.
     */
    /**
     * For endurance qualifying: average best_lap_ms across all ACC sessions per car,
     * sort within each class group, and populate a single qualifying session.
     */
    function importAverageQualFromAcc(raceNum: number) {
        const sessions = Object.values(accSessionData?.qualifying ?? {});
        if (sessions.length === 0) return;

        // Sum best_lap_ms per car number across all sessions (skip nulls/invalid)
        const lapSums = new Map<number, { total: number; count: number }>();
        for (const session of sessions) {
            for (const entry of session) {
                if (!entry.best_lap_ms) continue;
                if (!lapSums.has(entry.race_number)) {
                    lapSums.set(entry.race_number, { total: 0, count: 0 });
                }
                const s = lapSums.get(entry.race_number)!;
                s.total += entry.best_lap_ms;
                s.count += 1;
            }
        }

        const avgMap = new Map<number, number>();
        for (const [raceNumber, { total, count }] of lapSums) {
            avgMap.set(raceNumber, Math.round(total / count));
        }

        const patch: Record<string, QualSlot> = {};
        const si = 0;

        for (let gi = 0; gi < qualCarGroups.length; gi++) {
            const group = qualCarGroups[gi];
            const carsWithTimes = group.cars.map(car => ({
                car,
                avgMs: avgMap.get(Number(car.car_number)) ?? null,
            }));

            // Sort ascending by average; cars with no time go to the bottom
            carsWithTimes.sort((a, b) => {
                if (a.avgMs === null && b.avgMs === null) return 0;
                if (a.avgMs === null) return 1;
                if (b.avgMs === null) return -1;
                return a.avgMs - b.avgMs;
            });

            carsWithTimes.forEach(({ car, avgMs }, posIdx) => {
                patch[`${si}:${gi}:${posIdx + 1}`] = {
                    entryCarId: String(car.id),
                    bestLap: avgMs !== null ? msToLap(avgMs) : '',
                };
            });
        }

        setQualFormatByRace(prev => ({ ...prev, [raceNum]: 1 }));
        setQualDataByRace(prev => ({ ...prev, [raceNum]: patch }));
    }

    function importQualSessionFromAcc(raceNum: number, si: number, fileIndex: number) {
        console.log('[ACC Import] accSessionData:', accSessionData);
        console.log('[ACC Import] fileIndex:', fileIndex, 'accLines:', accSessionData?.qualifying?.[fileIndex]);
        console.log('[ACC Import] entry_cars car_numbers:', race.entry_cars?.map(c => c.car_number));
        const accLines = accSessionData?.qualifying?.[fileIndex];
        if (!accLines?.length) { console.log('[ACC Import] no accLines, aborting'); return; }
        const groupPositions: Record<number, number> = {};
        const patch: Record<string, QualSlot> = {};
        for (const line of accLines) {
            const car = race.entry_cars?.find(c => Number(c.car_number) === line.race_number);
            console.log('[ACC Import] line race_number:', line.race_number, '-> car found:', car?.id);
            if (!car) continue;
            const gi = qualCarGroups.findIndex(g => g.cars.some(c => c.id === car.id));
            if (gi === -1) continue;
            groupPositions[gi] = (groupPositions[gi] ?? 0) + 1;
            patch[`${si}:${gi}:${groupPositions[gi]}`] = {
                entryCarId: String(car.id),
                bestLap: msToLap(line.best_lap_ms),
            };
        }
        setQualDataByRace(prev => ({
            ...prev,
            [raceNum]: { ...prev[raceNum], ...patch },
        }));
    }

    function importRaceFromAcc(raceNum: number, writeKey?: number) {
        const accLines = accSessionData?.race?.[raceNum];
        if (!accLines?.length) return;
        const total = raceCarGroups.reduce((s, g) => s + g.cars.length, 0);
        const newData: RaceSlot[] = Array.from({ length: total }, () => ({
            entryCarId: '', status: 'finished', laps: '', gap: '', fastestLap: '',
        }));
        accLines.forEach((line, idx) => {
            if (idx >= total) return;
            const car = race.entry_cars?.find(c => Number(c.car_number) === line.race_number);
            newData[idx] = {
                entryCarId: car ? String(car.id) : '',
                status: 'finished',
                laps: String(line.lap_count),
                fastestLap: msToLap(line.fastest_lap_ms),
                gap: idx === 0 ? '' : (
                    line.gap_laps ? `+${line.gap_laps}L` :
                    line.gap_ms   ? msToLap(line.gap_ms)  : ''
                ),
            };
        });
        setRaceDataByRace(prev => ({ ...prev, [writeKey ?? raceNum]: newData }));
    }

    function importStageFromAcc(stageNum: number) {
        const accLines = accSessionData?.stages?.[stageNum];
        if (!accLines?.length) return;
        const total = raceCarGroups.reduce((s, g) => s + g.cars.length, 0);
        const newData: RaceSlot[] = Array.from({ length: total }, () => ({
            entryCarId: '', status: 'finished', laps: '', gap: '', fastestLap: '',
        }));
        accLines.forEach((line, idx) => {
            if (idx >= total) return;
            const car = race.entry_cars?.find(c => Number(c.car_number) === line.race_number);
            newData[idx] = {
                entryCarId: car ? String(car.id) : '',
                status: 'finished',
                laps: String(line.lap_count),
                fastestLap: msToLap(line.fastest_lap_ms),
                gap: idx === 0 ? '' : (
                    line.gap_laps ? `+${line.gap_laps}L` :
                    line.gap_ms   ? msToLap(line.gap_ms)  : ''
                ),
            };
        });
        setRaceDataByRace(prev => ({ ...prev, [stageNum]: newData }));
    }

    const seasonId = race.season_id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Season', href: seasons.show(seasonId).url },
        { title: race.gp_name ?? race.race_code, href: '#' },
    ];

    // Build tab list
    const tabs: { key: string; label: string; disabled?: boolean }[] = [
        { key: 'participants', label: 'Participants' },
        ...(hasStages
            ? [
                { key: 'qualifying', label: 'Qualifying', disabled: !participantsExist },
                ...(stageNames ?? []).map((stage, i) => ({
                    key: `s_${i + 1}`,
                    label: stage.name || `Stage ${i + 1}`,
                    disabled: !participantsExist,
                })),
                { key: 'race', label: 'Race Results', disabled: !participantsExist },
              ]
            : !multiRace
            ? [
                { key: 'qualifying',   label: 'Qualifying',   disabled: !participantsExist },
                ...(showSprint ? [{ key: 'sprint_race', label: 'Sprint Race', disabled: !participantsExist }] : []),
                { key: 'race',         label: 'Race Results', disabled: !participantsExist },
              ]
            : Array.from({ length: numberOfRaces }, (_, i) => i + 1).flatMap(rn => [
                { key: `q_${rn}`, label: `Q${rn}`, disabled: !participantsExist },
                { key: `r_${rn}`, label: `R${rn}`, disabled: !participantsExist },
              ])
        ),
    ];

    // Active race number for rendering
    const activeRaceNum = raceNumberFromTab(activeTab);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Manage Weekend — ${race.gp_name ?? race.race_code}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-6">

                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Flag className="h-6 w-6" />
                        <div>
                            <h1 className="text-2xl font-semibold">{race.gp_name ?? race.race_code}</h1>
                            <p className="text-sm text-muted-foreground">Manage Race Weekend</p>
                        </div>
                    </div>
                    <a
                        href={seasons.show(seasonId, { query: { tab: 'results' } }).url}
                        className="text-sm text-muted-foreground underline hover:text-foreground"
                    >
                        ← Back to Season
                    </a>
                </div>

                {/* Tab Nav */}
                <div className="flex gap-2 border-b border-border pb-1">
                    {tabs.map(t => (
                        <button
                            key={t.key}
                            type="button"
                            disabled={t.disabled}
                            onClick={() => !t.disabled && setActiveTab(t.key)}
                            className={`rounded-t px-4 py-2 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-40 ${
                                activeTab === t.key
                                    ? 'border-b-2 border-primary text-primary'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            {t.label}
                        </button>
                    ))}
                </div>

                {/* ── PARTICIPANTS ── */}
                {activeTab === 'participants' && (
                    <div className="flex flex-col gap-6">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-semibold">Select Race Participants</h3>
                            {accSessionData?.qualifying && Object.keys(accSessionData.qualifying).length > 0 && (
                                <Button type="button" variant="outline" size="sm" onClick={autoAssignFromAcc}>
                                    Auto-Assign from ACC
                                </Button>
                            )}
                        </div>
                        {seasonCarGroups.map((group) => (
                            <div key={group.classId} className="rounded-xl border border-border bg-card p-4 shadow-sm">
                                {seasonCarGroups.length > 1 && (
                                    <div className="mb-3 flex items-center justify-between border-b border-border pb-2">
                                        <span className="font-bold uppercase">{group.className}</span>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => toggleSelectAll(group)}
                                        >
                                            {group.cars.filter(c => c.drivers.length > 0).every(c => selectedCarIds.has(c.id)) && group.cars.some(c => c.drivers.length > 0) ? 'Deselect All' : 'Select All'}
                                        </Button>
                                    </div>
                                )}
                                <div className="grid grid-cols-2 gap-2 md:grid-cols-3 lg:grid-cols-4">
                                    {group.cars.map(car => {
                                        const checked = selectedCarIds.has(car.id);
                                        const hasDrivers = car.drivers.length > 0;
                                        const name = car.livery_name ?? car.entry_class?.season_entry?.entrant?.name ?? '';
                                        return (
                                            <label
                                                key={car.id}
                                                className={`flex items-center gap-2 rounded-lg border p-2.5 transition-colors ${
                                                    !hasDrivers
                                                        ? 'cursor-not-allowed border-border bg-background opacity-40'
                                                        : checked
                                                            ? 'cursor-pointer border-primary bg-primary/10'
                                                            : 'cursor-pointer border-border bg-background hover:bg-accent'
                                                }`}
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={checked}
                                                    disabled={!hasDrivers}
                                                    onChange={() => hasDrivers && toggleCar(car.id)}
                                                    className="rounded"
                                                />
                                                <span className="text-sm">
                                                    <strong>#{car.car_number}</strong> {name}
                                                </span>
                                            </label>
                                        );
                                    })}
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* ── QUALIFYING (single-race key: 'qualifying', multi-race key: 'q_N') ── */}
                {isQualTab(activeTab) && (
                    <QualifyingPanel
                        raceNum={activeRaceNum}
                        qualFormat={qualFormatByRace[activeRaceNum] ?? 1}
                        setQualFormat={n =>
                            setQualFormatByRace(prev => ({ ...prev, [activeRaceNum]: n }))
                        }
                        qualData={qualDataByRace[activeRaceNum] ?? {}}
                        setQualSlot={(si, gi, pos, patch) => setQualSlot(activeRaceNum, si, gi, pos, patch)}
                        raceCarGroups={qualCarGroups}
                        onImportAccSession={(si) => {
                            const fileIndex = (activeRaceNum - 1) * (qualFormatByRace[activeRaceNum] ?? 1) + si + 1;
                            importQualSessionFromAcc(activeRaceNum, si, fileIndex);
                        }}
                        hasAccDataForSession={(si) => {
                            const fileIndex = (activeRaceNum - 1) * (qualFormatByRace[activeRaceNum] ?? 1) + si + 1;
                            return !!(accSessionData?.qualifying?.[fileIndex]?.length);
                        }}
                        onImportAccAverage={
                            race.endurance === 1 && Object.keys(accSessionData?.qualifying ?? {}).length > 1
                                ? () => importAverageQualFromAcc(activeRaceNum)
                                : undefined
                        }
                    />
                )}

                {/* ── SPRINT RACE ── */}
                {activeTab === 'sprint_race' && showSprint && (
                    <RaceResultsGrid
                        title="Sprint Race Results"
                        classGroups={raceCarGroups}
                        data={sprintData}
                        onUpdate={(idx, patch) => setSprintSlot(idx, patch)}
                    />
                )}

                {/* ── STAGE RESULTS (key: 's_N' for multi-stage events like Spa 24hrs) ── */}
                {isStageTab(activeTab) && (
                    <RaceResultsGrid
                        title={stageNames?.[activeRaceNum - 1]?.name ?? `Stage ${activeRaceNum}`}
                        classGroups={raceCarGroups}
                        data={raceDataByRace[activeRaceNum] ?? []}
                        onUpdate={(idx, patch) => setRaceSlot(activeRaceNum, idx, patch)}
                        onImportAcc={() => importStageFromAcc(activeRaceNum)}
                        importAccEnabled={!!(accSessionData?.stages?.[activeRaceNum]?.length)}
                    />
                )}

                {/* ── RACE RESULTS (single-race key: 'race', multi-race key: 'r_N') ── */}
                {isRaceTab(activeTab) && (
                    <RaceResultsGrid
                        title={hasStages || !multiRace ? 'Race Results' : `Race ${activeRaceNum} Results`}
                        classGroups={raceCarGroups}
                        data={raceDataByRace[hasStages ? stageCount + 1 : activeRaceNum] ?? []}
                        onUpdate={(idx, patch) => setRaceSlot(hasStages ? stageCount + 1 : activeRaceNum, idx, patch)}
                        onImportAcc={
                            hasStages
                                ? (accSessionData?.race?.[1]?.length ? () => importRaceFromAcc(1, stageCount + 1) : undefined)
                                : (accSessionData?.race?.[activeRaceNum]?.length ? () => importRaceFromAcc(activeRaceNum) : undefined)
                        }
                    />
                )}

                {/* Actions */}
                <div className="flex items-center gap-3 border-t border-border pt-4">
                    <Button
                        type="button"
                        disabled={form.processing}
                        onClick={() => submit('save')}
                    >
                        {form.processing ? 'Saving...' : 'Save'}
                    </Button>
                    <Button
                        type="button"
                        variant="default"
                        className="bg-green-600 hover:bg-green-700"
                        disabled={form.processing}
                        onClick={() => submit('complete')}
                    >
                        Complete Weekend
                    </Button>
                </div>

            </div>
        </AppLayout>
    );
}

// ─── Qualifying Panel ──────────────────────────────────────────────────────────

interface QualifyingPanelProps {
    raceNum: number;
    qualFormat: number;
    setQualFormat: (n: number) => void;
    qualData: Record<string, QualSlot>;
    setQualSlot: (si: number, gi: number, pos: number, patch: Partial<QualSlot>) => void;
    raceCarGroups: ClassGroup[];
    onImportAccSession?: (si: number) => void;
    hasAccDataForSession?: (si: number) => boolean;
    onImportAccAverage?: () => void;
}

function QualifyingPanel({
    raceNum,
    qualFormat,
    setQualFormat,
    qualData,
    setQualSlot,
    raceCarGroups,
    onImportAccSession,
    hasAccDataForSession,
    onImportAccAverage,
}: QualifyingPanelProps) {
    return (
        <div className="flex flex-col gap-4">
            <div className="flex flex-wrap items-center justify-between gap-4">
                <div className="flex items-center gap-2">
                    <label className="text-sm font-medium">Format</label>
                    <select
                        value={qualFormat}
                        onChange={e => setQualFormat(Number(e.target.value))}
                        className="rounded-md border border-border bg-background px-3 py-1.5 text-sm"
                    >
                        <option value={1}>Single Session</option>
                        <option value={2}>Q1 + Q2</option>
                        <option value={3}>Q1 + Q2 + Q3</option>
                        <option value={4}>Q1 + Q2 + Q3 + Q4</option>
                    </select>
                </div>
                {onImportAccAverage && (
                    <Button type="button" variant="outline" size="sm" onClick={onImportAccAverage}>
                        Import ACC Average (Endurance)
                    </Button>
                )}
            </div>

            {Array.from({ length: qualFormat }, (_, si) => {
                const sessionName = qualFormat === 1 ? 'Qualifying' : `Q${si + 1}`;
                return (
                    <div key={si} className="rounded-xl border border-border bg-card shadow-sm">
                        <div className="flex items-center justify-between border-b border-border px-4 py-2">
                            <span className="font-semibold">{sessionName}</span>
                            {onImportAccSession && hasAccDataForSession?.(si) && (
                                <Button type="button" variant="outline" size="sm" onClick={() => onImportAccSession(si)}>
                                    Import from ACC
                                </Button>
                            )}
                        </div>
                        <div className="p-4">
                            {raceCarGroups.map((group, gi) => {
                                const selectedInGroup = new Set(
                                    group.cars.map((_, posIdx) => qualData[`${si}:${gi}:${posIdx + 1}`]?.entryCarId).filter(Boolean)
                                );
                                return (
                                    <div key={group.classId} className="mb-4">
                                        {raceCarGroups.length > 1 && (
                                            <h6 className="mb-2 border-b border-border pb-1 text-xs font-bold uppercase text-muted-foreground">
                                                {group.className}
                                            </h6>
                                        )}
                                        <table className="w-full text-sm">
                                            <thead>
                                                <tr className="border-b border-border text-left text-xs text-muted-foreground">
                                                    <th className="w-12 py-1 pr-3">Pos</th>
                                                    <th className="py-1 pr-3">Car</th>
                                                    <th className="w-32 py-1">Best Lap</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {group.cars.map((_, posIdx) => {
                                                    const pos  = posIdx + 1;
                                                    const key  = `${si}:${gi}:${pos}`;
                                                    const slot = qualData[key] ?? { entryCarId: '', bestLap: '' };
                                                    return (
                                                        <tr key={pos} className="border-b border-border/50 last:border-0">
                                                            <td className="py-1.5 pr-3 font-bold">{pos}</td>
                                                            <td className="py-1.5 pr-3">
                                                                <div className="flex items-center gap-1">
                                                                    <CarNumberQuickSelect
                                                                        cars={group.cars}
                                                                        disabledIds={selectedInGroup}
                                                                        currentId={slot.entryCarId}
                                                                        onSelect={id => setQualSlot(si, gi, pos, { entryCarId: id })}
                                                                    />
                                                                    <select
                                                                        value={slot.entryCarId}
                                                                        onChange={e => setQualSlot(si, gi, pos, { entryCarId: e.target.value })}
                                                                        className="w-full rounded-md border border-border bg-background px-2 py-1 text-sm"
                                                                    >
                                                                        <option value="">-- Select Car --</option>
                                                                        {group.cars.map(car => (
                                                                            <option
                                                                                key={car.id}
                                                                                value={String(car.id)}
                                                                                disabled={
                                                                                    selectedInGroup.has(String(car.id)) &&
                                                                                    slot.entryCarId !== String(car.id)
                                                                                }
                                                                            >
                                                                                {carLabel(car)}
                                                                            </option>
                                                                        ))}
                                                                    </select>
                                                                </div>
                                                            </td>
                                                            <td className="py-1.5">
                                                                <LapInput
                                                                    value={slot.bestLap}
                                                                    onChange={v => setQualSlot(si, gi, pos, { bestLap: v })}
                                                                />
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
                    </div>
                );
            })}
        </div>
    );
}

// ─── Race Results Grid (shared by Race and Sprint tabs) ────────────────────────

interface RaceResultsGridProps {
    title: string;
    classGroups: ClassGroup[];
    data: RaceSlot[];
    onUpdate: (index: number, patch: Partial<RaceSlot>) => void;
    onImportAcc?: () => void;
    importAccEnabled?: boolean;
}

function RaceResultsGrid({ title, classGroups, data, onUpdate, onImportAcc, importAccEnabled = true }: RaceResultsGridProps) {
    const allCars        = classGroups.flatMap(g => g.cars);
    const totalPositions = allCars.length;
    const selectedCarIds = new Set(data.map(r => r.entryCarId).filter(Boolean));

    function handleLapsChange(idx: number, newLaps: string) {
        if (idx === 0 || !newLaps) {
            onUpdate(idx, { laps: newLaps });
            return;
        }
        const firstLaps = data[0]?.laps;
        if (!firstLaps) {
            onUpdate(idx, { laps: newLaps });
            return;
        }
        const firstNum   = parseInt(firstLaps, 10);
        const currentNum = parseInt(newLaps, 10);
        if (!isNaN(firstNum) && !isNaN(currentNum) && currentNum < firstNum) {
            onUpdate(idx, { laps: newLaps, gap: `+${firstNum - currentNum}L` });
        } else {
            onUpdate(idx, { laps: newLaps });
        }
    }

    return (
        <div className="rounded-xl border border-border bg-card shadow-sm">
            <div className="flex items-center justify-between border-b border-border px-4 py-2">
                <span className="font-semibold">{title}</span>
                {onImportAcc && (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={onImportAcc}
                        disabled={!importAccEnabled}
                        title={!importAccEnabled ? 'No ACC data available for this stage' : undefined}
                    >
                        Import from ACC
                    </Button>
                )}
            </div>
            <div className="overflow-x-auto p-4">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-border text-left text-xs text-muted-foreground">
                            <th className="w-12 py-1 pr-3">Pos</th>
                            <th className="py-1 pr-3">Car</th>
                            <th className="w-28 py-1 pr-3">Status</th>
                            <th className="w-20 py-1 pr-3">Laps</th>
                            <th className="w-32 py-1 pr-3">Gap</th>
                            <th className="w-32 py-1">Fastest Lap</th>
                        </tr>
                    </thead>
                    <tbody>
                        {Array.from({ length: totalPositions }, (_, idx) => {
                            const slot = data[idx] ?? { entryCarId: '', status: 'finished', laps: '', gap: '', fastestLap: '' };
                            return (
                                <tr key={idx} className="border-b border-border/50 last:border-0">
                                    <td className="py-1.5 pr-3 font-bold">{idx + 1}</td>
                                    <td className="py-1.5 pr-3">
                                        <div className="flex items-center gap-1">
                                            <CarNumberQuickSelect
                                                cars={allCars}
                                                disabledIds={selectedCarIds}
                                                currentId={slot.entryCarId}
                                                onSelect={id => onUpdate(idx, { entryCarId: id })}
                                            />
                                            <select
                                                value={slot.entryCarId}
                                                onChange={e => onUpdate(idx, { entryCarId: e.target.value })}
                                                className="w-full rounded-md border border-border bg-background px-2 py-1 text-sm"
                                            >
                                                <option value="">-- Select Car --</option>
                                                {classGroups.map(g => (
                                                    <optgroup key={g.classId} label={classGroups.length > 1 ? g.className : undefined}>
                                                        {g.cars.map(car => (
                                                            <option
                                                                key={car.id}
                                                                value={String(car.id)}
                                                                disabled={
                                                                    selectedCarIds.has(String(car.id)) &&
                                                                    slot.entryCarId !== String(car.id)
                                                                }
                                                            >
                                                                {carLabel(car)}
                                                            </option>
                                                        ))}
                                                    </optgroup>
                                                ))}
                                            </select>
                                        </div>
                                    </td>
                                    <td className="py-1.5 pr-3">
                                        <select
                                            value={slot.status}
                                            onChange={e => onUpdate(idx, { status: e.target.value })}
                                            className="rounded-md border border-border bg-background px-2 py-1 text-sm"
                                        >
                                            {STATUSES.map(s => (
                                                <option key={s} value={s}>{s.toUpperCase()}</option>
                                            ))}
                                        </select>
                                    </td>
                                    <td className="py-1.5 pr-3">
                                        <Input
                                            type="number"
                                            value={slot.laps}
                                            onChange={e => handleLapsChange(idx, e.target.value)}
                                            className="w-20"
                                            min={0}
                                        />
                                    </td>
                                    <td className="py-1.5 pr-3">
                                        <GapTimeInput
                                            value={slot.gap}
                                            onChange={v => onUpdate(idx, { gap: v })}
                                        />
                                    </td>
                                    <td className="py-1.5">
                                        <LapInput
                                            value={slot.fastestLap}
                                            onChange={v => onUpdate(idx, { fastestLap: v })}
                                        />
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
