import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem } from '@/types';
import entryCarDriversRoutes from '@/routes/entry-cars/drivers';

interface Driver {
    id: number;
    first_name: string;
    last_name: string;
    country: { name: string } | null;
}

interface EntryCar {
    id: number;
    car_number: string;
    effective_from_round: number;
}

interface EntryClass {
    id: number;
    name: string;
}

interface SeasonEntry {
    id: number;
}

interface Season {
    id: number;
    name: string;
}

interface World {
    id: number;
    name: string;
}

interface Props {
    world: World;
    season: Season;
    seasonEntry: SeasonEntry;
    entryClass: EntryClass;
    entryCar: EntryCar;
    drivers: Driver[];
    assignedDrivers: number[];
    otherCarDriverIds: number[];
}

export default function EntryCarDriversEdit({
    world,
    season,
    seasonEntry,
    entryClass,
    entryCar,
    drivers,
    assignedDrivers,
    otherCarDriverIds,
}: Props) {
    const routeArgs = {
        world: world.id,
        season: season.id,
        seasonEntry: seasonEntry.id,
        entryClass: entryClass.id,
        entryCar: entryCar.id,
    };

    const carLabel = entryCar.effective_from_round > 1
        ? `Car #${entryCar.car_number} (from R${entryCar.effective_from_round})`
        : `Car #${entryCar.car_number}`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: world.name, href: '#' },
        { title: season.name, href: '#' },
        { title: carLabel, href: '#' },
        { title: 'Drivers', href: '#' },
    ];

    const [search, setSearch] = useState('');
    const [selectedDrivers, setSelectedDrivers] = useState<number[]>(assignedDrivers);
    const [reassignIds, setReassignIds] = useState<number[]>([]);
    const [processing, setProcessing] = useState(false);

    function toggleDriver(driverId: number) {
        setSelectedDrivers((prev) =>
            prev.includes(driverId) ? prev.filter((id) => id !== driverId) : [...prev, driverId]
        );
    }

    function toggleReassign(driverId: number) {
        const isQueued = reassignIds.includes(driverId);
        setReassignIds((prev) =>
            isQueued ? prev.filter((id) => id !== driverId) : [...prev, driverId]
        );
        setSelectedDrivers((prev) =>
            isQueued ? prev.filter((id) => id !== driverId) : [...prev, driverId]
        );
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        setProcessing(true);
        router.post(
            entryCarDriversRoutes.update(routeArgs).url,
            { drivers: selectedDrivers, reassign_ids: reassignIds },
            { onFinish: () => setProcessing(false) }
        );
    }

    const matchesSearch = (d: Driver) => {
        const q = search.toLowerCase();
        return !q || `${d.first_name} ${d.last_name}`.toLowerCase().includes(q);
    };

    const availableDrivers = drivers.filter((d) => !otherCarDriverIds.includes(d.id) && matchesSearch(d));
    const unavailableDrivers = drivers.filter((d) => otherCarDriverIds.includes(d.id) && matchesSearch(d));

    const driverCard = (driver: Driver, unavailable: boolean) => {
        const isSelected = selectedDrivers.includes(driver.id);
        const isQueued = reassignIds.includes(driver.id);

        let cardClass = isSelected
            ? 'border-green-500 bg-green-500/10 cursor-pointer'
            : 'border-border hover:border-primary cursor-pointer';

        if (unavailable) {
            cardClass = isQueued
                ? 'border-amber-500 bg-amber-500/10 cursor-pointer'
                : 'border-border bg-muted opacity-60 cursor-pointer hover:opacity-100 hover:border-amber-400';
        }

        return (
            <div
                key={driver.id}
                role="button"
                tabIndex={0}
                onClick={() => unavailable ? toggleReassign(driver.id) : toggleDriver(driver.id)}
                onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { unavailable ? toggleReassign(driver.id) : toggleDriver(driver.id); } }}
                className={`flex flex-col gap-0.5 rounded-lg border p-3 transition-colors select-none ${cardClass}`}
            >
                <div className="flex items-center gap-2">
                    <input
                        type="checkbox"
                        checked={isSelected}
                        readOnly
                        tabIndex={-1}
                        className="pointer-events-none h-4 w-4 rounded border-gray-300 accent-primary"
                    />
                    <span className="font-semibold text-sm">{driver.first_name} {driver.last_name}</span>
                    {isQueued && (
                        <span className="ml-auto text-xs font-medium text-amber-600 dark:text-amber-400">Reassign</span>
                    )}
                </div>
                {driver.country && (
                    <span className="pl-6 text-xs text-muted-foreground">{driver.country.name}</span>
                )}
            </div>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${carLabel} — Drivers`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm text-muted-foreground">{season.name} — {carLabel}</p>
                        <h1 className="text-2xl font-semibold">Assign Drivers</h1>
                    </div>
                </div>

                <Input
                    placeholder="Search drivers..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="max-w-sm"
                />

                <form onSubmit={submit} className="flex flex-col gap-6">
                    <Button type="submit" disabled={processing} className="w-fit">
                        {processing ? 'Saving…' : 'Save Drivers'}
                    </Button>

                    <div className="flex flex-col gap-3">
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Available Drivers</h3>
                        {availableDrivers.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No drivers available.</p>
                        ) : (
                            <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                                {availableDrivers.map((d) => driverCard(d, false))}
                            </div>
                        )}
                    </div>

                    {unavailableDrivers.length > 0 && (
                        <div className="flex flex-col gap-3">
                            <div>
                                <h3 className="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Assigned Elsewhere</h3>
                                <p className="text-xs text-muted-foreground mt-0.5">Click a driver to reassign them to this car.</p>
                            </div>
                            <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                                {unavailableDrivers.map((d) => driverCard(d, true))}
                            </div>
                        </div>
                    )}

                    <Button type="submit" disabled={processing} className="w-fit">
                        {processing ? 'Saving…' : 'Save Drivers'}
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
