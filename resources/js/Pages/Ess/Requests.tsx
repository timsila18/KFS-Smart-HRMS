import { Head, useForm } from '@inertiajs/react';
import type React from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { EssNav } from '@/Components/Ess/EssNav';

export default function EssRequests({ rows }: { rows: any[] }) {
    const form = useForm({
        request_type: 'leave',
        remarks: '',
        payload: {
            leave_type_code: 'ANNUAL',
            start_date: '',
            end_date: '',
            requested_days: '',
            reason: '',
        },
    });

    return (
        <AppLayout>
            <Head title="ESS Requests" />
            <div className="grid gap-6 xl:grid-cols-[280px_1fr]">
                <EssNav />
                <div className="space-y-6">
                    <Card className="p-5">
                        <p className="text-xs font-bold uppercase text-primary">KFS leave service</p>
                        <h1 className="mt-1 text-2xl font-semibold">Leave Application</h1>
                        <p className="mt-1 text-sm text-muted-foreground">Submit leave to one approver. HR Manager is used first, HR Admin is the fallback approver.</p>
                        <form
                            onSubmit={(e: React.FormEvent) => {
                                e.preventDefault();
                                form.post('/ess/requests', { preserveScroll: true, onSuccess: () => form.reset() });
                            }}
                            className="mt-4 grid gap-4 md:grid-cols-3"
                        >
                            <div className="space-y-2">
                                <Label>Leave Type</Label>
                                <select
                                    className="h-10 w-full rounded-md border bg-background px-3 text-sm"
                                    value={form.data.payload.leave_type_code}
                                    onChange={(e) => form.setData('payload', { ...form.data.payload, leave_type_code: e.target.value })}
                                >
                                    <option value="ANNUAL">Annual Leave</option>
                                    <option value="SICK">Sick Leave</option>
                                    <option value="MATERNITY">Maternity Leave</option>
                                    <option value="PATERNITY">Paternity Leave</option>
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label>Start Date</Label>
                                <Input type="date" value={form.data.payload.start_date} onChange={(e) => form.setData('payload', { ...form.data.payload, start_date: e.target.value })} />
                            </div>
                            <div className="space-y-2">
                                <Label>End Date</Label>
                                <Input type="date" value={form.data.payload.end_date} onChange={(e) => form.setData('payload', { ...form.data.payload, end_date: e.target.value })} />
                            </div>
                            <div className="space-y-2">
                                <Label>Days</Label>
                                <Input type="number" min="0.01" step="0.01" placeholder="Auto if blank" value={form.data.payload.requested_days} onChange={(e) => form.setData('payload', { ...form.data.payload, requested_days: e.target.value })} />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label>Reason</Label>
                                <Input value={form.data.remarks} onChange={(e) => form.setData('remarks', e.target.value)} />
                            </div>
                            <div className="md:col-span-3">
                                <Button type="submit" disabled={form.processing}>Submit Leave Application</Button>
                            </div>
                            {Object.values(form.errors).length > 0 && (
                                <div className="md:col-span-3 rounded-md border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">
                                    {Object.values(form.errors).join(' ')}
                                </div>
                            )}
                        </form>
                    </Card>
                    <EssList rows={rows} />
                </div>
            </div>
        </AppLayout>
    );
}

function EssList({ rows }: { rows: any[] }) {
    return <Card className="p-5"><div className="space-y-3">{rows.map((row) => <div key={row.uuid} className="rounded-md border p-3 text-sm"><p className="font-medium">{row.request_type}</p><p className="text-muted-foreground">{row.status} · {row.remarks}</p></div>)}{rows.length === 0 && <p className="text-sm text-muted-foreground">No requests submitted.</p>}</div></Card>;
}
