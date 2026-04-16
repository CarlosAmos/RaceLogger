import { Head, useForm } from '@inertiajs/react';
import { Globe, Plus } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import type { BreadcrumbItem } from '@/types';
import worlds from '@/routes/worlds';
import world from '@/routes/world';

interface World {
    id: number;
    name: string;
    start_year: number;
    current_year: number;
}

interface Props {
    worlds: World[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Select World', href: '/' },
];

function SelectWorldForm({ worldId }: { worldId: number }) {
    const { post, processing } = useForm({});

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(world.select.store(worldId).url);
    }

    return (
        <form onSubmit={submit}>
            <Button type="submit" disabled={processing} size="sm">
                Select
            </Button>
        </form>
    );
}

export default function WorldsIndex({ worlds: worldList }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Select World" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Globe className="h-6 w-6" />
                        <h1 className="text-2xl font-semibold">Select World</h1>
                    </div>
                    <Button asChild>
                        <Link href={worlds.create().url}>
                            <Plus className="mr-2 h-4 w-4" />
                            New World
                        </Link>
                    </Button>
                </div>

                {worldList.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border p-12 text-center">
                        <Globe className="mx-auto mb-3 h-10 w-10 text-muted-foreground" />
                        <p className="text-muted-foreground">No worlds yet. Create your first one.</p>
                        <Button asChild className="mt-4">
                            <Link href={worlds.create().url}>Create World</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {worldList.map((w) => (
                            <div
                                key={w.id}
                                className="flex items-center justify-between rounded-xl border border-sidebar-border bg-card p-4"
                            >
                                <div>
                                    <p className="font-semibold">{w.name}</p>
                                    <p className="text-sm text-muted-foreground">
                                        Started {w.start_year} · Current year {w.current_year}
                                    </p>
                                </div>
                                <SelectWorldForm worldId={w.id} />
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
