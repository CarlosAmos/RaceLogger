import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import type { BreadcrumbItem } from '@/types';
import worldsConstructors from '@/routes/worlds/constructors';
import worldsCarModels from '@/routes/worlds/constructors/car-models';

interface Engine {
    id: number;
    name: string;
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
    engines: Engine[];
}

export default function CarModelCreate({ world, constructor: ctor, engines }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: world.name, href: '#' },
        { title: 'Teams', href: worldsConstructors.index(world.id).url },
        { title: ctor.name, href: '#' },
        { title: 'Car Models', href: worldsCarModels.index({ world: world.id, constructor: ctor.id }).url },
        { title: 'New Car Model', href: '#' },
    ];

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        year: '',
        engine_id: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(worldsCarModels.store({ world: world.id, constructor: ctor.id }).url);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Car Model" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div>
                    <p className="text-sm text-muted-foreground">{ctor.name}</p>
                    <h1 className="text-2xl font-semibold">New Car Model</h1>
                </div>

                <form onSubmit={submit} className="max-w-md space-y-5">
                    <div className="space-y-1">
                        <Label htmlFor="name">Model Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="F2004"
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="year">Year</Label>
                        <Input
                            id="year"
                            type="number"
                            value={data.year}
                            onChange={(e) => setData('year', e.target.value)}
                            placeholder="2004"
                        />
                        <InputError message={errors.year} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="engine_id">Engine</Label>
                        <select
                            id="engine_id"
                            value={data.engine_id}
                            onChange={(e) => setData('engine_id', e.target.value)}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="">— No engine —</option>
                            {engines.map((e) => (
                                <option key={e.id} value={e.id}>{e.name}</option>
                            ))}
                        </select>
                        <InputError message={errors.engine_id} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        Create Car Model
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
