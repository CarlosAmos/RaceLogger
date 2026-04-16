import { Head } from '@inertiajs/react';
import { Car, Plus } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import type { BreadcrumbItem } from '@/types';
import worldsConstructors from '@/routes/worlds/constructors';
import worldsEntrants from '@/routes/worlds/entrants';
import worldsCarModels from '@/routes/worlds/constructors/car-models';
import teamRoutes from '@/routes/teams';

interface Country {
    id: number;
    name: string;
}

interface Constructor {
    id: number;
    name: string;
    color: string | null;
    country: Country | null;
}

interface Entrant {
    id: number;
    name: string;
    country: Country | null;
}

interface Team {
    id: number;
    name: string;
    base_country: string | null;
    active: boolean;
}

interface World {
    id: number;
    name: string;
}

interface Props {
    world: World;
    constructors: Constructor[];
    entrants: Entrant[];
    teams: Team[];
}

export default function ConstructorsIndex({ world, constructors, entrants, teams }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: world.name, href: '#' },
        { title: 'Teams', href: worldsConstructors.index(world.id).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Teams" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Car className="h-6 w-6" />
                        <h1 className="text-2xl font-semibold">Teams</h1>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={worldsEntrants.create(world.id).url}>
                                <Plus className="mr-2 h-4 w-4" />
                                New Entrant
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={teamRoutes.create().url}>
                                <Plus className="mr-2 h-4 w-4" />
                                New Team
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={worldsConstructors.create(world.id).url}>
                                <Plus className="mr-2 h-4 w-4" />
                                New Constructor
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <section>
                        <h2 className="mb-3 text-lg font-semibold">Constructors</h2>
                        {constructors.length === 0 ? (
                            <div className="rounded-xl border border-dashed border-sidebar-border p-8 text-center">
                                <p className="text-sm text-muted-foreground">No constructors yet.</p>
                            </div>
                        ) : (
                            <div className="rounded-xl border border-sidebar-border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-sidebar-border">
                                            <th className="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
                                            <th className="px-4 py-3 text-left font-medium text-muted-foreground">Country</th>
                                            <th className="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {constructors.map((c) => (
                                            <tr key={c.id} className="border-b border-sidebar-border last:border-0">
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center gap-2">
                                                        {c.color && (
                                                            <span
                                                                className="inline-block h-3 w-3 rounded-full"
                                                                style={{ backgroundColor: c.color }}
                                                            />
                                                        )}
                                                        {c.name}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">{c.country?.name ?? '—'}</td>
                                                <td className="px-4 py-3 text-right">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={worldsCarModels.index({ world: world.id, constructor: c.id }).url}>
                                                            Car Models
                                                        </Link>
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </section>

                    <section>
                        <h2 className="mb-3 text-lg font-semibold">Entrants</h2>
                        {entrants.length === 0 ? (
                            <div className="rounded-xl border border-dashed border-sidebar-border p-8 text-center">
                                <p className="text-sm text-muted-foreground">No entrants yet.</p>
                            </div>
                        ) : (
                            <div className="rounded-xl border border-sidebar-border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-sidebar-border">
                                            <th className="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
                                            <th className="px-4 py-3 text-left font-medium text-muted-foreground">Country</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {entrants.map((e) => (
                                            <tr key={e.id} className="border-b border-sidebar-border last:border-0">
                                                <td className="px-4 py-3">{e.name}</td>
                                                <td className="px-4 py-3 text-muted-foreground">{e.country?.name ?? '—'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </section>

                    <section>
                        <h2 className="mb-3 text-lg font-semibold">Teams</h2>
                        {teams.length === 0 ? (
                            <div className="rounded-xl border border-dashed border-sidebar-border p-8 text-center">
                                <p className="text-sm text-muted-foreground">No teams yet.</p>
                            </div>
                        ) : (
                            <div className="rounded-xl border border-sidebar-border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-sidebar-border">
                                            <th className="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
                                            <th className="px-4 py-3 text-left font-medium text-muted-foreground">Base</th>
                                            <th className="px-4 py-3 text-left font-medium text-muted-foreground">Active</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {teams.map((t) => (
                                            <tr key={t.id} className="border-b border-sidebar-border last:border-0">
                                                <td className="px-4 py-3 font-medium">{t.name}</td>
                                                <td className="px-4 py-3 text-muted-foreground">{t.base_country ?? '—'}</td>
                                                <td className="px-4 py-3">
                                                    <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${t.active ? 'bg-green-100 text-green-800' : 'bg-muted text-muted-foreground'}`}>
                                                        {t.active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
