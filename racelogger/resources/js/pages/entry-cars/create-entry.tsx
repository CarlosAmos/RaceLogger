import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import type { BreadcrumbItem } from '@/types';
import entryCarRoutes from '@/routes/entry-cars';

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

interface SeasonClass {
    id: number;
    name: string;
    sub_class: string | null;
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
    carModels: CarModel[];
    entryClasses: SeasonClass[];
}

export default function EntryCarCreateEntry({ world, season, seasonEntry, carModels, entryClasses }: Props) {
    const routeArgs = {
        world: world.id,
        season: season.id,
        seasonEntry: seasonEntry.id,
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: world.name, href: '#' },
        { title: season.name, href: '#' },
        { title: 'Add Entry Car', href: '#' },
    ];

    const { data, setData, post, processing, errors } = useForm({
        entry_class_id: '',
        season_entry_id: String(seasonEntry.id),
        car_number: '',
        car_model_id: '',
        livery_name: '',
        chassis_code: '',
        effective_from_round: 1,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(entryCarRoutes.store(routeArgs).url);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add Entry Car" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div>
                    <p className="text-sm text-muted-foreground">{season.name}</p>
                    <h1 className="text-2xl font-semibold">Add Entry Car</h1>
                </div>

                <form onSubmit={submit} className="max-w-md space-y-5">
                    <div className="space-y-1">
                        <Label htmlFor="entry_class_id">Class</Label>
                        <select
                            id="entry_class_id"
                            value={data.entry_class_id}
                            onChange={(e) => setData('entry_class_id', e.target.value)}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            required
                        >
                            <option value="">— Select class —</option>
                            {entryClasses.map((c) => (
                                <option key={c.id} value={c.id}>{c.name}{c.sub_class ? ` - ${c.sub_class}` : ''}</option>
                            ))}
                        </select>
                        <InputError message={errors.entry_class_id} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="car_number">Car Number</Label>
                        <Input
                            id="car_number"
                            value={data.car_number}
                            onChange={(e) => setData('car_number', e.target.value)}
                            placeholder="1"
                            required
                        />
                        <InputError message={errors.car_number} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="car_model_id">Car Model</Label>
                        <select
                            id="car_model_id"
                            value={data.car_model_id}
                            onChange={(e) => setData('car_model_id', e.target.value)}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            required
                        >
                            <option value="">— Select car model —</option>
                            {carModels.map((m) => (
                                <option key={m.id} value={m.id}>
                                    {m.name}{m.year ? ` (${m.year})` : ''}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.car_model_id} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="livery_name">Livery Name</Label>
                        <Input
                            id="livery_name"
                            value={data.livery_name}
                            onChange={(e) => setData('livery_name', e.target.value)}
                            placeholder="Optional"
                        />
                        <InputError message={errors.livery_name} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="chassis_code">Chassis Code</Label>
                        <Input
                            id="chassis_code"
                            value={data.chassis_code}
                            onChange={(e) => setData('chassis_code', e.target.value)}
                            placeholder="Optional"
                        />
                        <InputError message={errors.chassis_code} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="effective_from_round">Active From Round</Label>
                        <Input
                            id="effective_from_round"
                            type="number"
                            min={1}
                            value={data.effective_from_round}
                            onChange={(e) => setData('effective_from_round', Number(e.target.value))}
                        />
                        <p className="text-xs text-muted-foreground">Set to 1 unless this is a mid-season car change</p>
                        <InputError message={errors.effective_from_round} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        Add Entry Car
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
