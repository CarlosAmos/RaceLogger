import { Head } from '@inertiajs/react';
import { CheckCircle } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import series from '@/routes/series';

interface World {
    id: number;
    name: string;
    start_year: number;
}

interface Props {
    world: World;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Select World', href: '/' },
    { title: 'World Created', href: '#' },
];

export default function WorldsCreated({ world }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="World Created" />
            <div className="flex h-full flex-1 flex-col items-center justify-center gap-6 p-6">
                <CheckCircle className="h-16 w-16 text-green-500" />
                <div className="text-center">
                    <h1 className="text-2xl font-semibold">{world.name} Created!</h1>
                    <p className="mt-1 text-muted-foreground">Start Year: {world.start_year}</p>
                </div>

                <div className="mt-4 space-y-3 text-center">
                    <h3 className="font-medium">What would you like to do next?</h3>
                    <div className="flex gap-3">
                        <Button asChild>
                            <Link href={series.create().url}>Create First Series</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={dashboard().url}>Go to Dashboard</Link>
                        </Button>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
