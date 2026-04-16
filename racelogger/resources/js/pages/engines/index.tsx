import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';
import worldsEngines from '@/routes/worlds/engines';

interface Engine {
    id: number;
    name: string;
    configuration: string | null;
    capacity: string | null;
    hybrid: boolean;
}

interface World {
    id: number;
    name: string;
}

interface Props {
    world: World;
    engines: Engine[];
}

export default function EnginesIndex({ world, engines }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: world.name, href: '#' },
        { title: 'Engines', href: worldsEngines.index(world.id).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Engines" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Engines</h1>
                    <Button asChild>
                        <Link href={worldsEngines.create(world.id).url}>
                            <Plus className="mr-2 h-4 w-4" />
                            New Engine
                        </Link>
                    </Button>
                </div>

                {engines.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border p-12 text-center">
                        <p className="text-muted-foreground">No engines yet.</p>
                        <Button asChild className="mt-4">
                            <Link href={worldsEngines.create(world.id).url}>New Engine</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="rounded-xl border border-sidebar-border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-sidebar-border">
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Configuration</th>
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Capacity</th>
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                {engines.map((e) => (
                                    <tr key={e.id} className="border-b border-sidebar-border last:border-0">
                                        <td className="px-4 py-3 font-medium">{e.name}</td>
                                        <td className="px-4 py-3 text-muted-foreground">{e.configuration ?? '—'}</td>
                                        <td className="px-4 py-3 text-muted-foreground">{e.capacity ?? '—'}</td>
                                        <td className="px-4 py-3">
                                            {e.hybrid ? (
                                                <Badge variant="secondary">Hybrid</Badge>
                                            ) : (
                                                <Badge variant="outline">ICE</Badge>
                                            )}
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
