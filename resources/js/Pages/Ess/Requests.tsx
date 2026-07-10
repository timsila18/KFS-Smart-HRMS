import { Head, useForm } from '@inertiajs/react';
import type React from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { EssNav } from '@/Components/Ess/EssNav';

export default function EssRequests({ rows }: { rows: any[] }) {
    const form = useForm({ request_type: 'general', remarks: '', payload: {} });

    return (
        <AppLayout>
            <Head title="ESS Requests" />
            <div className="space-y-6">
                <EssNav />
                <Card className="p-5">
                    <h1 className="text-2xl font-semibold">Requests</h1>
                    <form onSubmit={(e: React.FormEvent) => { e.preventDefault(); form.post('/ess/requests', { preserveScroll: true, onSuccess: () => form.reset() }); }} className="mt-4 grid gap-4 md:grid-cols-[220px_1fr_auto]">
                        <div className="space-y-2"><Label>Type</Label><Input value={form.data.request_type} onChange={(e) => form.setData('request_type', e.target.value)} /></div>
                        <div className="space-y-2"><Label>Remarks</Label><Input value={form.data.remarks} onChange={(e) => form.setData('remarks', e.target.value)} /></div>
                        <div className="flex items-end"><Button disabled={form.processing}>Submit</Button></div>
                    </form>
                </Card>
                <EssList rows={rows} />
            </div>
        </AppLayout>
    );
}

function EssList({ rows }: { rows: any[] }) {
    return <Card className="p-5"><div className="space-y-3">{rows.map((row) => <div key={row.uuid} className="rounded-md border p-3 text-sm"><p className="font-medium">{row.request_type}</p><p className="text-muted-foreground">{row.status} · {row.remarks}</p></div>)}{rows.length === 0 && <p className="text-sm text-muted-foreground">No requests submitted.</p>}</div></Card>;
}
