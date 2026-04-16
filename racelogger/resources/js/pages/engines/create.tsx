import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/input-error';
import type { BreadcrumbItem } from '@/types';
import worldsEngines from '@/routes/worlds/engines';

interface World {
    id: number;
    name: string;
}

interface Props {
    world: World;
}

export default function EngineCreate({ world }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: world.name, href: '#' },
        { title: 'Engines', href: worldsEngines.index(world.id).url },
        { title: 'New Engine', href: '#' },
    ];

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        configuration: '',
        capacity: '',
        hybrid: false,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(worldsEngines.store(world.id).url);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Engine" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">New Engine</h1>

                <form onSubmit={submit} className="max-w-md space-y-5">
                    <div className="space-y-1">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Ferrari 048"
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="configuration">Configuration</Label>
                        <Input
                            id="configuration"
                            value={data.configuration}
                            onChange={(e) => setData('configuration', e.target.value)}
                            placeholder="V10"
                        />
                        <InputError message={errors.configuration} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="capacity">Capacity</Label>
                        <Input
                            id="capacity"
                            value={data.capacity}
                            onChange={(e) => setData('capacity', e.target.value)}
                            placeholder="3.0L"
                        />
                        <InputError message={errors.capacity} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="hybrid"
                            checked={data.hybrid}
                            onCheckedChange={(checked) => setData('hybrid', !!checked)}
                        />
                        <Label htmlFor="hybrid">Hybrid Power Unit</Label>
                    </div>

                    <Button type="submit" disabled={processing}>
                        Create Engine
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
