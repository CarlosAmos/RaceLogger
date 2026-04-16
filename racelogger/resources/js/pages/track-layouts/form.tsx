import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import type { BreadcrumbItem } from '@/types';
import tracksRoutes from '@/routes/tracks';
import trackLayoutsRoutes from '@/routes/track-layouts';

interface Track {
    id: number;
    name: string;
}

interface TrackLayout {
    id: number | null;
    name: string;
    length_km: number | null;
    active_from: number | null;
    active_to: number | null;
    track_id: number;
}

interface Props {
    layout: TrackLayout;
    track: Track;
    mode: 'create' | 'edit';
}

export default function TrackLayoutForm({ layout, track, mode }: Props) {
    const isEdit = mode === 'edit';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tracks', href: tracksRoutes.index().url },
        { title: track.name, href: tracksRoutes.edit(track.id).url },
        { title: isEdit ? `Edit ${layout.name}` : 'New Layout', href: '#' },
    ];

    const { data, setData, post, put, processing, errors } = useForm({
        track_id: layout.track_id,
        name: layout.name ?? '',
        length_km: layout.length_km ? String(layout.length_km) : '',
        active_from: layout.active_from ? String(layout.active_from) : '',
        active_to: layout.active_to ? String(layout.active_to) : '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (isEdit && layout.id) {
            put(trackLayoutsRoutes.update(layout.id).url);
        } else {
            post(trackLayoutsRoutes.store().url);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isEdit ? `Edit ${layout.name}` : 'New Layout'} />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div>
                    <p className="text-sm text-muted-foreground">{track.name}</p>
                    <h1 className="text-2xl font-semibold">{isEdit ? `Edit ${layout.name}` : 'New Layout'}</h1>
                </div>

                <form onSubmit={submit} className="max-w-md space-y-5">
                    <input type="hidden" name="track_id" value={data.track_id} />

                    <div className="space-y-1">
                        <Label htmlFor="name">Layout Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Grand Prix Circuit"
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="length_km">Length (km)</Label>
                        <Input
                            id="length_km"
                            type="number"
                            step="0.001"
                            value={data.length_km}
                            onChange={(e) => setData('length_km', e.target.value)}
                            placeholder="3.337"
                        />
                        <InputError message={errors.length_km} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-1">
                            <Label htmlFor="active_from">Active From (year)</Label>
                            <Input
                                id="active_from"
                                type="number"
                                value={data.active_from}
                                onChange={(e) => setData('active_from', e.target.value)}
                                placeholder="1950"
                            />
                            <InputError message={errors.active_from} />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="active_to">Active To (year)</Label>
                            <Input
                                id="active_to"
                                type="number"
                                value={data.active_to}
                                onChange={(e) => setData('active_to', e.target.value)}
                                placeholder="present"
                            />
                            <InputError message={errors.active_to} />
                        </div>
                    </div>

                    <Button type="submit" disabled={processing}>
                        {isEdit ? 'Save Changes' : 'Create Layout'}
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
