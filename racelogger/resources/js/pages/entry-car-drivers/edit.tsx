import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
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

    const breadcrumbs: BreadcrumbItem[] = [
        { title: world.name, href: '#' },
        { title: season.name, href: '#' },
        { title: `Car #${entryCar.car_number}`, href: '#' },
        { title: 'Drivers', href: '#' },
    ];

    const { data, setData, post, processing, errors } = useForm({
        drivers: assignedDrivers,
    });

    function toggleDriver(driverId: number) {
        setData('drivers', data.drivers.includes(driverId)
            ? data.drivers.filter((id) => id !== driverId)
            : [...data.drivers, driverId]
        );
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(entryCarDriversRoutes.update(routeArgs).url);
    }

    const availableDrivers = drivers.filter((d) => !otherCarDriverIds.includes(d.id));
    const unavailableDrivers = drivers.filter((d) => otherCarDriverIds.includes(d.id));

    const driverCard = (driver: Driver, disabled: boolean) => {
        const isAssigned = data.drivers.includes(driver.id);
        return (
            <label
                key={driver.id}
                className={`flex cursor-pointer flex-col gap-0.5 rounded-lg border p-3 transition-colors ${
                    disabled
                        ? 'border-border bg-muted opacity-50 cursor-not-allowed'
                        : isAssigned
                        ? 'border-green-500 bg-green-500/10'
                        : 'border-border hover:border-primary'
                }`}
            >
                <div className="flex items-center gap-2">
                    <Checkbox
                        id={`driver-${driver.id}`}
                        checked={isAssigned}
                        disabled={disabled}
                        onCheckedChange={() => !disabled && toggleDriver(driver.id)}
                    />
                    <span className="font-semibold text-sm">{driver.first_name} {driver.last_name}</span>
                </div>
                {driver.country && (
                    <span className="pl-6 text-xs text-muted-foreground">{driver.country.name}</span>
                )}
            </label>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Car #${entryCar.car_number} — Drivers`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm text-muted-foreground">{season.name} — Car #{entryCar.car_number}</p>
                        <h1 className="text-2xl font-semibold">Assign Drivers</h1>
                    </div>
                </div>

                <form onSubmit={submit} className="flex flex-col gap-6">
                    <Button type="submit" disabled={processing} className="w-fit">
                        {processing ? 'Saving…' : 'Save Drivers'}
                    </Button>

                    {errors.drivers && <p className="text-sm text-destructive">{errors.drivers}</p>}

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
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Unavailable Drivers</h3>
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
