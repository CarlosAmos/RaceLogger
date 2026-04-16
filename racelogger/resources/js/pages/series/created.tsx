import { Head } from '@inertiajs/react';
import { CheckCircle } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Link } from '@inertiajs/react';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import seriesRoutes from '@/routes/series';
import seasonsRoutes from '@/routes/seasons';

interface Series {
    id: number;
    name: string;
    is_multiclass: boolean;
}

interface Props {
    series: Series;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Series', href: seriesRoutes.index().url },
    { title: 'Series Created', href: '#' },
];

export default function SeriesCreated({ series }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Series Created" />
            <div className="flex h-full flex-1 flex-col items-center justify-center gap-6 p-6">
                <CheckCircle className="h-16 w-16 text-green-500" />
                <div className="text-center">
                    <h1 className="text-2xl font-semibold">{series.name} Created!</h1>
                    {series.is_multiclass && (
                        <Badge variant="secondary" className="mt-2">Multi-Class Championship</Badge>
                    )}
                </div>

                <div className="mt-4 space-y-3 text-center">
                    <h3 className="font-medium">Would you like to create the first season?</h3>
                    <div className="flex gap-3">
                        <Button asChild>
                            <Link href={`${seasonsRoutes.create().url}?series_id=${series.id}`}>
                                Yes, Create Season
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={dashboard().url}>No, Go to Dashboard</Link>
                        </Button>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
