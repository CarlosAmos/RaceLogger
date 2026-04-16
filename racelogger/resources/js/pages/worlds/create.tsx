import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import type { BreadcrumbItem } from '@/types';
import worlds from '@/routes/worlds';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Select World', href: '/' },
    { title: 'New World', href: worlds.create().url },
];

export default function WorldsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        start_year: new Date().getFullYear(),
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(worlds.store().url);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create World" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">Create New World</h1>

                <form onSubmit={submit} className="max-w-md space-y-5">
                    <div className="space-y-1">
                        <Label htmlFor="name">World Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="My Racing World"
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="start_year">Start Year</Label>
                        <Input
                            id="start_year"
                            type="number"
                            value={data.start_year}
                            onChange={(e) => setData('start_year', parseInt(e.target.value))}
                            min={1900}
                            required
                        />
                        <InputError message={errors.start_year} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        Create World
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
