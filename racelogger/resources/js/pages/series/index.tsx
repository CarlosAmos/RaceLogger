import { Head } from '@inertiajs/react';
import { List, Plus } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';
import seriesRoutes from '@/routes/series';
import seasonsRoutes from '@/routes/seasons';

interface Series {
    id: number;
    name: string;
    is_multiclass: boolean;
}

interface Props {
    series: Series[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Series', href: seriesRoutes.index().url },
];

export default function SeriesIndex({ series }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Series" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <List className="h-6 w-6" />
                        <h1 className="text-2xl font-semibold">Series</h1>
                    </div>
                    <Button asChild>
                        <Link href={seriesRoutes.create().url}>
                            <Plus className="mr-2 h-4 w-4" />
                            New Series
                        </Link>
                    </Button>
                </div>

                {series.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border p-12 text-center">
                        <List className="mx-auto mb-3 h-10 w-10 text-muted-foreground" />
                        <p className="text-muted-foreground">No series yet. Create your first one.</p>
                        <Button asChild className="mt-4">
                            <Link href={seriesRoutes.create().url}>Create Series</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="rounded-xl border border-sidebar-border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-sidebar-border">
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Type</th>
                                    <th className="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {series.map((s) => (
                                    <tr key={s.id} className="border-b border-sidebar-border last:border-0">
                                        <td className="px-4 py-3 font-medium">{s.name}</td>
                                        <td className="px-4 py-3">
                                            {s.is_multiclass ? (
                                                <Badge variant="secondary">Multi-Class</Badge>
                                            ) : (
                                                <Badge variant="outline">Single Class</Badge>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={seasonsRoutes.create().url + `?series_id=${s.id}`}>
                                                    New Season
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
