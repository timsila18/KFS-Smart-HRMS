import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card } from '@/Components/ui/card';
import { EssNav } from '@/Components/Ess/EssNav';

export default function EssListPage({ title, description, rows }: { title: string; description: string; rows: Record<string, any>[] }) {
    const columns = rows.length > 0 ? Object.keys(rows[0]).filter((key) => !['id', 'deleted_at', 'created_by', 'updated_by', 'deleted_by'].includes(key)) : [];

    return (
        <AppLayout>
            <Head title={title} />
            <div className="grid gap-6 xl:grid-cols-[280px_1fr]">
                <EssNav />
                <div className="space-y-6">
                    <section className="rounded-lg border bg-card p-5">
                        <p className="text-xs font-bold uppercase text-primary">Employee Self Service</p>
                        <h1 className="mt-1 text-2xl font-semibold">{title}</h1>
                        <p className="mt-2 text-sm text-muted-foreground">{description}</p>
                    </section>
                    <Card className="overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b bg-muted/50 text-xs uppercase text-muted-foreground">
                                    <tr>{columns.map((column) => <th key={column} className="px-4 py-3">{column.replaceAll('_', ' ')}</th>)}</tr>
                                </thead>
                                <tbody className="divide-y">
                                    {rows.map((row, index) => (
                                        <tr key={row.uuid ?? index} className="align-top">
                                            {columns.map((column) => <td key={column} className="px-4 py-3">{renderValue(row[column])}</td>)}
                                        </tr>
                                    ))}
                                    {rows.length === 0 && (
                                        <tr>
                                            <td colSpan={Math.max(columns.length, 1)} className="px-4 py-10 text-center text-muted-foreground">
                                                No KFS service records have been posted here yet.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

function renderValue(value: any) {
    if (value === null || value === undefined || value === '') return '-';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') return JSON.stringify(value);
    if (String(value).startsWith('/storage') || String(value).startsWith('http')) return <a href={String(value)} className="text-primary hover:underline">Open</a>;
    return String(value);
}
