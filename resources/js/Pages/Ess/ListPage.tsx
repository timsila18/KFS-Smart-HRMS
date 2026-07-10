import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card } from '@/Components/ui/card';
import { EssNav } from '@/Components/Ess/EssNav';

export default function EssListPage({ title, description, rows }: { title: string; description: string; rows: Record<string, any>[] }) {
    const columns = rows.length > 0 ? Object.keys(rows[0]).filter((key) => !['id', 'deleted_at', 'created_by', 'updated_by', 'deleted_by'].includes(key)) : [];

    return (
        <AppLayout>
            <Head title={title} />
            <div className="space-y-6">
                <EssNav />
                <section className="rounded-lg border bg-card p-5"><h1 className="text-2xl font-semibold">{title}</h1><p className="mt-2 text-sm text-muted-foreground">{description}</p></section>
                <Card className="overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-muted/50 text-xs uppercase text-muted-foreground"><tr>{columns.map((column) => <th key={column} className="px-4 py-3">{column.replaceAll('_', ' ')}</th>)}</tr></thead>
                            <tbody className="divide-y">
                                {rows.map((row, index) => <tr key={row.uuid ?? index}>{columns.map((column) => <td key={column} className="px-4 py-3">{renderValue(row[column])}</td>)}</tr>)}
                                {rows.length === 0 && <tr><td className="px-4 py-8 text-center text-muted-foreground">No records found.</td></tr>}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}

function renderValue(value: any) {
    if (value === null || value === undefined) return '-';
    if (typeof value === 'object') return JSON.stringify(value);
    if (String(value).startsWith('/storage') || String(value).startsWith('http')) return <a href={String(value)} className="text-primary hover:underline">Download</a>;
    return String(value);
}
