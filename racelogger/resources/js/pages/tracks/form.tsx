import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { Link } from '@inertiajs/react';
import { Plus, Pencil } from 'lucide-react';
import type { BreadcrumbItem } from '@/types';
import tracksRoutes from '@/routes/tracks';
import trackLayoutsRoutes from '@/routes/track-layouts';

interface Country {
    id: number;
    name: string;
}

interface TrackLayout {
    id: number;
    name: string;
    length_km: number | null;
    active_from: number | null;
    active_to: number | null;
}

interface Track {
    id: number | null;
    name: string;
    city: string | null;
    country_id: number | null;
    layouts?: TrackLayout[];
}

interface Props {
    track: Track;
    countries: Country[];
    mode: 'create' | 'edit';
}

export default function TrackForm({ track, countries, mode }: Props) {
    const isEdit = mode === 'edit';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tracks', href: tracksRoutes.index().url },
        { title: isEdit ? track.name : 'New Track', href: '#' },
    ];

    const { data, setData, post, put, processing, errors } = useForm({
        name: track.name ?? '',
        city: track.city ?? '',
        country_id: track.country_id ? String(track.country_id) : '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (isEdit && track.id) {
            put(tracksRoutes.update(track.id).url);
        } else {
            post(tracksRoutes.store().url);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isEdit ? `Edit ${track.name}` : 'New Track'} />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">{isEdit ? `Edit ${track.name}` : 'New Track'}</h1>

                <form onSubmit={submit} className="max-w-md space-y-5">
                    <div className="space-y-1">
                        <Label htmlFor="name">Track Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Circuit de Monaco"
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="city">City</Label>
                        <Input
                            id="city"
                            value={data.city}
                            onChange={(e) => setData('city', e.target.value)}
                            placeholder="Monte Carlo"
                        />
                        <InputError message={errors.city} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="country_id">Country</Label>
                        <select
                            id="country_id"
                            value={data.country_id}
                            onChange={(e) => setData('country_id', e.target.value)}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="">— Select country —</option>
                            {countries.map((c) => (
                                <option key={c.id} value={c.id}>{c.name}</option>
                            ))}
                        </select>
                        <InputError message={errors.country_id} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        {isEdit ? 'Save Changes' : 'Create Track'}
                    </Button>
                </form>

                {isEdit && track.id && (
                    <div className="mt-4 max-w-2xl">
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="text-lg font-semibold">Layouts</h2>
                            <Button variant="outline" size="sm" asChild>
                                <Link href={`${trackLayoutsRoutes.create().url}?track_id=${track.id}`}>
                                    <Plus className="mr-1 h-3 w-3" />
                                    Add Layout
                                </Link>
                            </Button>
                        </div>

                        {(track.layouts ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground">No layouts yet.</p>
                        ) : (
                            <div className="rounded-xl border border-sidebar-border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-sidebar-border">
                                            <th className="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
                                            <th className="px-4 py-3 text-left font-medium text-muted-foreground">Length</th>
                                            <th className="px-4 py-3 text-left font-medium text-muted-foreground">Active</th>
                                            <th className="px-4 py-3 text-right font-medium text-muted-foreground"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {(track.layouts ?? []).map((layout) => (
                                            <tr key={layout.id} className="border-b border-sidebar-border last:border-0">
                                                <td className="px-4 py-3">{layout.name}</td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {layout.length_km ? `${layout.length_km} km` : '—'}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {layout.active_from || layout.active_to
                                                        ? `${layout.active_from ?? '?'} – ${layout.active_to ?? 'present'}`
                                                        : '—'}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={trackLayoutsRoutes.edit(layout.id).url}>
                                                            <Pencil className="mr-1 h-3 w-3" />
                                                            Edit
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
                )}
            </div>
        </AppLayout>
    );
}
