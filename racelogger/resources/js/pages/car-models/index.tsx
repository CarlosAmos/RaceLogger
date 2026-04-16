import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import type { BreadcrumbItem } from '@/types';
import worldsConstructors from '@/routes/worlds/constructors';
import worldsCarModels from '@/routes/worlds/constructors/car-models';
import worldsEngines from '@/routes/worlds/engines';

interface Engine {
    id: number;
    name: string;
}

interface CarModel {
    id: number;
    name: string;
    year: number | null;
    engine: Engine | null;
}

interface Constructor {
    id: number;
    name: string;
}

interface World {
    id: number;
    name: string;
}

interface Props {
    world: World;
    constructor: Constructor;
    carModels: CarModel[];
}

export default function CarModelsIndex({ world, constructor: ctor, carModels }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: world.name, href: '#' },
        { title: 'Teams', href: worldsConstructors.index(world.id).url },
        { title: ctor.name, href: '#' },
        { title: 'Car Models', href: worldsCarModels.index({ world: world.id, constructor: ctor.id }).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${ctor.name} — Car Models`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm text-muted-foreground">{ctor.name}</p>
                        <h1 className="text-2xl font-semibold">Car Models</h1>
                    </div>
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link href={worldsEngines.create(world.id).url}>
                                <Plus className="mr-2 h-4 w-4" />
                                Add Engine
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={worldsCarModels.create({ world: world.id, constructor: ctor.id }).url}>
                                <Plus className="mr-2 h-4 w-4" />
                                Add Car Model
                            </Link>
                        </Button>
                    </div>
                </div>

                {carModels.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border p-12 text-center">
                        <p className="text-muted-foreground">No car models yet.</p>
                        <Button asChild className="mt-4">
                            <Link href={worldsCarModels.create({ world: world.id, constructor: ctor.id }).url}>
                                Add Car Model
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <div className="rounded-xl border border-sidebar-border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-sidebar-border">
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Model</th>
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Year</th>
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Engine</th>
                                </tr>
                            </thead>
                            <tbody>
                                {carModels.map((m) => (
                                    <tr key={m.id} className="border-b border-sidebar-border last:border-0">
                                        <td className="px-4 py-3 font-medium">{m.name}</td>
                                        <td className="px-4 py-3 text-muted-foreground">{m.year ?? '—'}</td>
                                        <td className="px-4 py-3 text-muted-foreground">{m.engine?.name ?? '—'}</td>
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
