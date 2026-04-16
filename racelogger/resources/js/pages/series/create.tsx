import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/input-error';
import type { BreadcrumbItem } from '@/types';
import seriesRoutes from '@/routes/series';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Series', href: seriesRoutes.index().url },
    { title: 'New Series', href: seriesRoutes.create().url },
];

export default function SeriesCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        is_multiclass: false,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(seriesRoutes.store().url);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Series" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold">Create Series</h1>

                <form onSubmit={submit} className="max-w-md space-y-5">
                    <div className="space-y-1">
                        <Label htmlFor="name">Series Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Formula 1 World Championship"
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="is_multiclass"
                            checked={data.is_multiclass}
                            onCheckedChange={(checked) => setData('is_multiclass', !!checked)}
                        />
                        <Label htmlFor="is_multiclass">Multi-Class Championship</Label>
                    </div>

                    <Button type="submit" disabled={processing}>
                        Create Series
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
