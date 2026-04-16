import { Head, Link } from '@inertiajs/react';
import { CalendarDays, Plus, Eye, Pencil } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import seasonsRoutes from '@/routes/seasons';

interface Season {
    id: number;
    year: number;
    round_count: number;
    class_count: number;
}

interface SeriesGroup {
    series: { id: number; name: string };
    seasons: Season[];
}

interface Props {
    world: { id: number; name: string };
    seasons: SeriesGroup[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Seasons', href: seasonsRoutes.index().url },
];

export default function SeasonsIndex({ world, seasons }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Seasons" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <CalendarDays className="h-6 w-6" />
                        <div>
                            <h1 className="text-2xl font-semibold">Seasons</h1>
                            <p className="text-sm text-muted-foreground">{world.name}</p>
                        </div>
                    </div>
                    <Button asChild>
                        <Link href={seasonsRoutes.create().url}>
                            <Plus className="mr-2 h-4 w-4" />
                            New Season
                        </Link>
                    </Button>
                </div>

                {seasons.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border p-12 text-center">
                        <CalendarDays className="mx-auto mb-3 h-10 w-10 text-muted-foreground" />
                        <p className="text-muted-foreground">No seasons yet for {world.name}.</p>
                        <Button asChild className="mt-4">
                            <Link href={seasonsRoutes.create().url}>Create First Season</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="flex flex-col gap-8">
                        {seasons.map((group) => (
                            <div key={group.series.id}>
                                <h2 className="mb-3 text-lg font-semibold">{group.series.name}</h2>
                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                    {group.seasons.map((season) => (
                                        <div
                                            key={season.id}
                                            className="flex flex-col gap-4 rounded-xl border border-sidebar-border bg-card p-4 shadow-sm"
                                        >
                                            <div className="flex items-start justify-between">
                                                <span className="text-3xl font-bold">{season.year}</span>
                                            </div>

                                            <div className="flex gap-4 text-sm text-muted-foreground">
                                                <span>{season.round_count} round{season.round_count !== 1 ? 's' : ''}</span>
                                                {season.class_count > 1 && (
                                                    <span>{season.class_count} classes</span>
                                                )}
                                            </div>

                                            <div className="flex gap-2">
                                                <Button variant="outline" size="sm" asChild className="flex-1">
                                                    <Link href={seasonsRoutes.show(season.id).url}>
                                                        <Eye className="mr-1 h-3 w-3" />
                                                        View
                                                    </Link>
                                                </Button>
                                                <Button variant="outline" size="sm" asChild className="flex-1">
                                                    <Link href={seasonsRoutes.edit(season.id).url}>
                                                        <Pencil className="mr-1 h-3 w-3" />
                                                        Edit
                                                    </Link>
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
