import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/input-error';
import { Trash2, Plus } from 'lucide-react';
import type { BreadcrumbItem } from '@/types';
import pointSystems from '@/routes/point-systems';

interface Props {
    seasonId?: number | null;
}

interface PointRow {
    position: number;
    points: string;
}

export default function PointSystemCreate({ seasonId }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Point Systems', href: '#' },
        { title: 'New Point System', href: '#' },
    ];

    const [raceRows, setRaceRows] = useState<PointRow[]>(
        Array.from({ length: 10 }, (_, i) => ({ position: i + 1, points: '' }))
    );
    const [qualRows, setQualRows] = useState<PointRow[]>([{ position: 1, points: '' }]);

    const form = useForm({
        name: '',
        description: '',
        season_id: seasonId ? String(seasonId) : '',
        enable_qualifying: false,
        enable_fastest_lap: false,
        fastest_lap_points: '',
    });

    function addRaceRow() {
        setRaceRows(prev => [...prev, { position: prev.length + 1, points: '' }]);
    }

    function removeRaceRow(idx: number) {
        setRaceRows(prev =>
            prev.filter((_, i) => i !== idx).map((row, i) => ({ ...row, position: i + 1 }))
        );
    }

    function setRacePoints(idx: number, val: string) {
        setRaceRows(prev => prev.map((r, i) => i === idx ? { ...r, points: val } : r));
    }

    function addQualRow() {
        setQualRows(prev => [...prev, { position: prev.length + 1, points: '' }]);
    }

    function removeQualRow(idx: number) {
        setQualRows(prev =>
            prev.filter((_, i) => i !== idx).map((row, i) => ({ ...row, position: i + 1 }))
        );
    }

    function setQualPoints(idx: number, val: string) {
        setQualRows(prev => prev.map((r, i) => i === idx ? { ...r, points: val } : r));
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();

        const racePointsObj: Record<string, string> = {};
        raceRows.forEach(r => {
            if (r.points !== '') racePointsObj[r.position] = r.points;
        });

        const qualPointsObj: Record<string, string> = {};
        if (form.data.enable_qualifying) {
            qualRows.forEach(r => {
                if (r.points !== '') qualPointsObj[r.position] = r.points;
            });
        }

        form.transform((d) => ({
            ...d,
            race_points: racePointsObj,
            qualifying_points: qualPointsObj,
        }));

        form.post(pointSystems.store().url);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Point System" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">New Point System</h1>
                </div>

                <form onSubmit={submit} className="max-w-2xl space-y-6">
                    {/* Basic Info */}
                    <div className="space-y-4">
                        <div className="space-y-1">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                required
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="description">Description</Label>
                            <textarea
                                id="description"
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                                rows={3}
                                className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            />
                            <InputError message={form.errors.description} />
                        </div>
                    </div>

                    {/* Race Points */}
                    <div className="space-y-3">
                        <h2 className="text-base font-medium">Race Points</h2>
                        <div className="rounded-xl border border-sidebar-border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-sidebar-border">
                                        <th className="px-4 py-2 text-left font-medium text-muted-foreground w-16">Pos</th>
                                        <th className="px-4 py-2 text-left font-medium text-muted-foreground">Points</th>
                                        <th className="px-4 py-2 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {raceRows.map((row, idx) => (
                                        <tr key={idx} className="border-b border-sidebar-border last:border-0">
                                            <td className="px-4 py-2 text-muted-foreground">{row.position}</td>
                                            <td className="px-4 py-2">
                                                <Input
                                                    type="number"
                                                    min="0"
                                                    step="any"
                                                    value={row.points}
                                                    onChange={(e) => setRacePoints(idx, e.target.value)}
                                                    className="h-7 w-24"
                                                />
                                            </td>
                                            <td className="px-4 py-2">
                                                <button
                                                    type="button"
                                                    onClick={() => removeRaceRow(idx)}
                                                    className="text-muted-foreground hover:text-destructive"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Button type="button" variant="outline" size="sm" onClick={addRaceRow}>
                            <Plus className="mr-1 h-3 w-3" />
                            Add Position
                        </Button>
                    </div>

                    {/* Qualifying Points */}
                    <div className="space-y-3">
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="enable_qualifying"
                                checked={form.data.enable_qualifying}
                                onCheckedChange={(v) => form.setData('enable_qualifying', Boolean(v))}
                            />
                            <Label htmlFor="enable_qualifying">Enable Qualifying Points</Label>
                        </div>

                        {form.data.enable_qualifying && (
                            <div className="space-y-3 pl-6">
                                <div className="rounded-xl border border-sidebar-border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b border-sidebar-border">
                                                <th className="px-4 py-2 text-left font-medium text-muted-foreground w-16">Pos</th>
                                                <th className="px-4 py-2 text-left font-medium text-muted-foreground">Points</th>
                                                <th className="px-4 py-2 w-10"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {qualRows.map((row, idx) => (
                                                <tr key={idx} className="border-b border-sidebar-border last:border-0">
                                                    <td className="px-4 py-2 text-muted-foreground">{row.position}</td>
                                                    <td className="px-4 py-2">
                                                        <Input
                                                            type="number"
                                                            min="0"
                                                            step="any"
                                                            value={row.points}
                                                            onChange={(e) => setQualPoints(idx, e.target.value)}
                                                            className="h-7 w-24"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => removeQualRow(idx)}
                                                            className="text-muted-foreground hover:text-destructive"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                <Button type="button" variant="outline" size="sm" onClick={addQualRow}>
                                    <Plus className="mr-1 h-3 w-3" />
                                    Add Qualifying Position
                                </Button>
                            </div>
                        )}
                    </div>

                    {/* Fastest Lap */}
                    <div className="space-y-3">
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="enable_fastest_lap"
                                checked={form.data.enable_fastest_lap}
                                onCheckedChange={(v) => form.setData('enable_fastest_lap', Boolean(v))}
                            />
                            <Label htmlFor="enable_fastest_lap">Award Fastest Lap Points</Label>
                        </div>

                        {form.data.enable_fastest_lap && (
                            <div className="space-y-1 pl-6">
                                <Label htmlFor="fastest_lap_points">Fastest Lap Points</Label>
                                <Input
                                    id="fastest_lap_points"
                                    type="number"
                                    min="0"
                                    step="any"
                                    value={form.data.fastest_lap_points}
                                    onChange={(e) => form.setData('fastest_lap_points', e.target.value)}
                                    className="w-32"
                                />
                                <InputError message={form.errors.fastest_lap_points} />
                            </div>
                        )}
                    </div>

                    <Button type="submit" disabled={form.processing}>
                        Create Point System
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
