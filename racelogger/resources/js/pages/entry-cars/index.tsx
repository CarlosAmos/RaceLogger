import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import type { BreadcrumbItem } from '@/types';
import worldsConstructors from '@/routes/worlds/constructors';
import entryCarsRoutes from '@/routes/worlds/seasons/season-entries/entry-classes/entry-cars';
import entryCarDriversRoutes from '@/routes/entry-cars/drivers';

interface CarModel {
    id: number;
    name: string;
    constructor: { name: string } | null;
}

interface EntryCar {
    id: number;
    car_number: string;
    livery_name: string | null;
    chassis_code: string | null;
    car_model: CarModel | null;
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
    entryCars: EntryCar[];
}

export default function EntryCarsIndex({ world, season, seasonEntry, entryClass, entryCars }: Props) {
    const args = {
        world: world.id,
        season: season.id,
        season_entry: seasonEntry.id,
        entry_class: entryClass.id,
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: world.name, href: '#' },
        { title: season.name, href: '#' },
        { title: entryClass.name, href: '#' },
        { title: 'Cars', href: entryCarsRoutes.index(args).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${entryClass.name} — Cars`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm text-muted-foreground">{entryClass.name}</p>
                        <h1 className="text-2xl font-semibold">Entry Cars</h1>
                    </div>
                    <Button asChild>
                        <Link href={entryCarsRoutes.create(args).url}>
                            <Plus className="mr-2 h-4 w-4" />
                            Add Car
                        </Link>
                    </Button>
                </div>

                {entryCars.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border p-12 text-center">
                        <p className="text-muted-foreground">No cars yet.</p>
                    </div>
                ) : (
                    <div className="rounded-xl border border-sidebar-border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-sidebar-border">
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">#</th>
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Car Model</th>
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Livery</th>
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Chassis</th>
                                    <th className="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {entryCars.map((car) => (
                                    <tr key={car.id} className="border-b border-sidebar-border last:border-0">
                                        <td className="px-4 py-3 font-medium">{car.car_number}</td>
                                        <td className="px-4 py-3 text-muted-foreground">{car.car_model?.name ?? '—'}</td>
                                        <td className="px-4 py-3 text-muted-foreground">{car.livery_name ?? '—'}</td>
                                        <td className="px-4 py-3 text-muted-foreground">{car.chassis_code ?? '—'}</td>
                                        <td className="px-4 py-3 text-right">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={entryCarDriversRoutes.edit({
                                                    world: world.id,
                                                    season: season.id,
                                                    seasonEntry: seasonEntry.id,
                                                    entryClass: entryClass.id,
                                                    entryCar: car.id,
                                                }).url}>
                                                    Drivers
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
