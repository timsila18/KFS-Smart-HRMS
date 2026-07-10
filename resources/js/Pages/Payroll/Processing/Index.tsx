import { Head, Link, useForm } from '@inertiajs/react';
import { FileBarChart, FileUp, Plus } from 'lucide-react';
import type React from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

type Run = { uuid: string; run_number: string; status: string; gross_total: number; deduction_total: number; net_total: number; period?: { code: string }; pay_group?: { name: string } };
type Option = { id: number; code: string; name?: string };

export default function PayrollProcessingIndex({ runs, periods, payGroups }: { runs: { data: Run[] }; periods: Option[]; payGroups: Option[] }) {
    const openForm = useForm({ payroll_period_id: periods[0]?.id ?? '', pay_group_id: payGroups[0]?.id ?? '' });
    const importForm = useForm<{ payroll_period_id: number | string; file: File | null }>({ payroll_period_id: periods[0]?.id ?? '', file: null });

    return (
        <AppLayout>
            <Head title="Payroll Processing" />
            <div className="space-y-6">
                <section className="rounded-lg border bg-primary p-5 text-primary-foreground">
                    <p className="text-sm font-semibold uppercase text-white/75">Payroll Engine</p>
                    <h1 className="mt-2 text-3xl font-semibold">Payroll Processing</h1>
                    <p className="mt-3 text-sm text-white/80">Open payroll, import adjustments, calculate, preview, approve, lock, reverse, and generate outputs from configured formulas.</p>
                </section>

                <section className="grid gap-6 lg:grid-cols-2">
                    <Card className="p-5">
                        <h2 className="text-lg font-semibold">Open Payroll</h2>
                        <form onSubmit={(e) => { e.preventDefault(); openForm.post('/payroll/processing/open'); }} className="mt-4 grid gap-4 sm:grid-cols-2">
                            <Select label="Period" value={String(openForm.data.payroll_period_id)} options={periods.map((p) => ({ value: p.id, label: p.code }))} onChange={(v) => openForm.setData('payroll_period_id', v)} />
                            <Select label="Pay Group" value={String(openForm.data.pay_group_id)} options={payGroups.map((p) => ({ value: p.id, label: p.name ?? p.code }))} onChange={(v) => openForm.setData('pay_group_id', v)} />
                            <Button type="submit" disabled={openForm.processing}><Plus className="h-4 w-4" /> Open</Button>
                        </form>
                    </Card>

                    <Card className="p-5">
                        <h2 className="text-lg font-semibold">Import Payroll Adjustments</h2>
                        <form onSubmit={(e) => { e.preventDefault(); importForm.post('/payroll/processing/adjustments/import', { forceFormData: true }); }} className="mt-4 grid gap-4 sm:grid-cols-2">
                            <Select label="Period" value={String(importForm.data.payroll_period_id)} options={periods.map((p) => ({ value: p.id, label: p.code }))} onChange={(v) => importForm.setData('payroll_period_id', v)} />
                            <div className="space-y-2"><Label>CSV File</Label><Input type="file" accept=".csv,.txt" onChange={(e) => importForm.setData('file', e.target.files?.[0] ?? null)} /></div>
                            <Button type="submit" disabled={importForm.processing}><FileUp className="h-4 w-4" /> Import</Button>
                        </form>
                    </Card>
                </section>

                <Card className="p-5">
                    <div className="flex items-center gap-2">
                        <FileBarChart className="h-5 w-5 text-primary" />
                        <h2 className="text-lg font-semibold">HRISKE Payroll Reports</h2>
                    </div>
                    <div className="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        {[
                            ['Wagebill', 'HRISKE_WAGEBILL'],
                            ['Earnings & Deductions', 'HRISKE_EARNINGS_DEDUCTIONS'],
                            ['Deduction Postings', 'HRISKE_DEDUCTION_POSTINGS'],
                            ['Individual Payment Breakdown', 'HRISKE_INDIVIDUAL_PAYMENT_BREAKDOWN'],
                            ['Bank Summary', 'HRISKE_BANK_SUMMARY'],
                            ['Bank Branch Register', 'BANK_BRANCH_REGISTER'],
                        ].map(([label, code]) => (
                            <Link key={code} href={`/reports/${code}`} className="kfs-focus rounded-md border px-3 py-2 text-sm font-medium hover:border-primary hover:bg-primary/5">
                                {label}
                            </Link>
                        ))}
                    </div>
                </Card>

                <Card className="overflow-hidden">
                    <div className="border-b p-5"><h2 className="text-lg font-semibold">Payroll Runs</h2></div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-muted/50 text-xs uppercase text-muted-foreground"><tr><th className="px-4 py-3">Run</th><th className="px-4 py-3">Period</th><th className="px-4 py-3">Status</th><th className="px-4 py-3">Gross</th><th className="px-4 py-3">Deductions</th><th className="px-4 py-3">Net</th></tr></thead>
                            <tbody className="divide-y">
                                {runs.data.map((run) => (
                                    <tr key={run.uuid}>
                                        <td className="px-4 py-3"><Link href={`/payroll/processing/${run.uuid}`} className="font-medium text-primary hover:underline">{run.run_number}</Link></td>
                                        <td className="px-4 py-3">{run.period?.code}</td>
                                        <td className="px-4 py-3">{run.status}</td>
                                        <td className="px-4 py-3">{run.gross_total.toLocaleString()}</td>
                                        <td className="px-4 py-3">{run.deduction_total.toLocaleString()}</td>
                                        <td className="px-4 py-3">{run.net_total.toLocaleString()}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}

function Select({ label, value, options, onChange }: { label: string; value: string; options: { value: string | number; label: string }[]; onChange: (value: string) => void }) {
    return <div className="space-y-2"><Label>{label}</Label><select className="h-10 w-full rounded-md border bg-background px-3 text-sm" value={value} onChange={(e) => onChange(e.target.value)}>{options.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}</select></div>;
}
