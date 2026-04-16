import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/input-error';
import type { BreadcrumbItem } from '@/types';
import worldsConstructors from '@/routes/worlds/constructors';
import teams from '@/routes/teams';

interface World {
    id: number;
    name: string;
}

interface Props {
    world: World;
}

export default function TeamCreate({ world }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: world.name, href: '#' },
        { title: 'Teams', href: worldsConstructors.index(world.id).url },
        { title: 'New Team', href: '#' },
    ];

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        base_country: '',
        active: true,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(teams.store().url);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Team" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">New Team</h1>

                <form onSubmit={submit} className="max-w-md space-y-5">
                    <div className="space-y-1">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Red Bull Racing"
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="base_country">Base Country</Label>
                        <Input
                            id="base_country"
                            value={data.base_country}
                            onChange={(e) => setData('base_country', e.target.value)}
                            placeholder="United Kingdom"
                        />
                        <InputError message={errors.base_country} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="active"
                            checked={data.active}
                            onCheckedChange={(v) => setData('active', Boolean(v))}
                        />
                        <Label htmlFor="active">Active</Label>
                    </div>

                    <Button type="submit" disabled={processing}>
                        Create Team
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
