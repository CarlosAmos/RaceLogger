import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import type { BreadcrumbItem } from '@/types';
import worldsConstructors from '@/routes/worlds/constructors';
import worldsEntrants from '@/routes/worlds/entrants';

interface Country {
    id: number;
    name: string;
}

interface World {
    id: number;
    name: string;
}

interface Props {
    world: World;
    countries: Country[];
}

export default function EntrantCreate({ world, countries }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: world.name, href: '#' },
        { title: 'Teams', href: worldsConstructors.index(world.id).url },
        { title: 'New Entrant', href: '#' },
    ];

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        country_id: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(worldsEntrants.store(world.id).url);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Entrant" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">New Entrant</h1>

                <form onSubmit={submit} className="max-w-md space-y-5">
                    <div className="space-y-1">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Scuderia Ferrari S.p.A."
                            required
                        />
                        <InputError message={errors.name} />
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
                        Create Entrant
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
