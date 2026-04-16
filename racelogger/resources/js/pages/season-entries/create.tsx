import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import type { BreadcrumbItem } from '@/types';
import worldsSeasons from '@/routes/worlds/seasons';
import worldsSeasonEntries from '@/routes/worlds/seasons/season-entries';

interface Entrant {
    id: number;
    name: string;
}

interface Constructor {
    id: number;
    name: string;
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
    entrants: Entrant[];
    constructors: Constructor[];
}

export default function SeasonEntryCreate({ world, season, entrants, constructors }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: world.name, href: '#' },
        { title: season.name, href: '#' },
        { title: 'Add Team Entry', href: '#' },
    ];

    const { data, setData, post, processing, errors } = useForm({
        entrant_id: '',
        constructor_id: '',
        display_name: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(worldsSeasonEntries.store({ world: world.id, season: season.id }).url);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add Team Entry" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div>
                    <p className="text-sm text-muted-foreground">{season.name}</p>
                    <h1 className="text-2xl font-semibold">Add Team Entry</h1>
                </div>

                <form onSubmit={submit} className="max-w-md space-y-5">
                    <div className="space-y-1">
                        <Label htmlFor="entrant_id">Entrant</Label>
                        <select
                            id="entrant_id"
                            value={data.entrant_id}
                            onChange={(e) => setData('entrant_id', e.target.value)}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            required
                        >
                            <option value="">— Select entrant —</option>
                            {entrants.map((e) => (
                                <option key={e.id} value={e.id}>{e.name}</option>
                            ))}
                        </select>
                        <InputError message={errors.entrant_id} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="constructor_id">Constructor</Label>
                        <select
                            id="constructor_id"
                            value={data.constructor_id}
                            onChange={(e) => setData('constructor_id', e.target.value)}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            required
                        >
                            <option value="">— Select constructor —</option>
                            {constructors.map((c) => (
                                <option key={c.id} value={c.id}>{c.name}</option>
                            ))}
                        </select>
                        <InputError message={errors.constructor_id} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="display_name">Display Name (optional)</Label>
                        <Input
                            id="display_name"
                            value={data.display_name}
                            onChange={(e) => setData('display_name', e.target.value)}
                            placeholder="Override team name for this season"
                        />
                        <InputError message={errors.display_name} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        Add to Season
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
