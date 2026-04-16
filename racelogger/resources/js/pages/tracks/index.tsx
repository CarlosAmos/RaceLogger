import { Head } from '@inertiajs/react';
import { MapPin, Plus, Pencil } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import type { BreadcrumbItem } from '@/types';
import tracksRoutes from '@/routes/tracks';
import trackLayoutsRoutes from '@/routes/track-layouts';

interface TrackLayout {
    id: number;
    name: string;
    length_km: number | null;
    active_from: number | null;
    active_to: number | null;
}

interface Track {
    id: number;
    name: string;
    city: string | null;
    country: { name: string } | null;
    layouts: TrackLayout[];
}

interface Props {
    tracks: Track[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tracks', href: tracksRoutes.index().url },
];

export default function TracksIndex({ tracks }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tracks" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <MapPin className="h-6 w-6" />
                        <h1 className="text-2xl font-semibold">Tracks</h1>
                    </div>
                    <Button asChild>
                        <Link href={tracksRoutes.create().url}>
                            <Plus className="mr-2 h-4 w-4" />
                            Add Circuit
                        </Link>
                    </Button>
                </div>

                {tracks.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border p-12 text-center">
                        <MapPin className="mx-auto mb-3 h-10 w-10 text-muted-foreground" />
                        <p className="text-muted-foreground">No tracks yet. Add the first circuit.</p>
                        <Button asChild className="mt-4">
                            <Link href={tracksRoutes.create().url}>Add Circuit</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {tracks.map((track) => (
                            <div key={track.id} className="rounded-xl border border-sidebar-border p-4">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <h2 className="font-semibold">{track.name}</h2>
                                        {(track.city || track.country) && (
                                            <p className="text-sm text-muted-foreground">
                                                {[track.city, track.country?.name].filter(Boolean).join(', ')}
                                            </p>
                                        )}
                                    </div>
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={tracksRoutes.edit(track.id).url}>
                                            <Pencil className="mr-1 h-3 w-3" />
                                            Edit
                                        </Link>
                                    </Button>
                                </div>

                                {track.layouts.length > 0 && (
                                    <div className="mt-3 rounded-lg border border-sidebar-border">
                                        <table className="w-full text-sm">
                                            <thead>
                                                <tr className="border-b border-sidebar-border">
                                                    <th className="px-3 py-2 text-left font-medium text-muted-foreground">Layout</th>
                                                    <th className="px-3 py-2 text-left font-medium text-muted-foreground">Length</th>
                                                    <th className="px-3 py-2 text-left font-medium text-muted-foreground">Active</th>
                                                    <th className="px-3 py-2 text-right font-medium text-muted-foreground"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {track.layouts.map((layout) => (
                                                    <tr key={layout.id} className="border-b border-sidebar-border last:border-0">
                                                        <td className="px-3 py-2">{layout.name}</td>
                                                        <td className="px-3 py-2 text-muted-foreground">
                                                            {layout.length_km ? `${layout.length_km} km` : '—'}
                                                        </td>
                                                        <td className="px-3 py-2 text-muted-foreground">
                                                            {layout.active_from || layout.active_to
                                                                ? `${layout.active_from ?? '?'} – ${layout.active_to ?? 'present'}`
                                                                : '—'}
                                                        </td>
                                                        <td className="px-3 py-2 text-right">
                                                            <Button variant="ghost" size="sm" asChild>
                                                                <Link href={trackLayoutsRoutes.edit(layout.id).url}>
                                                                    <Pencil className="h-3 w-3" />
                                                                </Link>
                                                            </Button>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}

                                <div className="mt-3">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={`${trackLayoutsRoutes.create().url}?track_id=${track.id}`}>
                                            <Plus className="mr-1 h-3 w-3" />
                                            Add Layout
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
