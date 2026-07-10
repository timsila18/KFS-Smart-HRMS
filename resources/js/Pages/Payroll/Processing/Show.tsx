import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Calculator, CheckCircle2, FileText, Lock, RotateCcw } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

type Run = { uuid: string; run_number: string; status: string; gross_total: number; deduction_total: number; net_total: number; items: any[]; outputs: any[] };

export default function PayrollRunShow({ run }: { run: { data: Run } }) {
    const item = run.data;
    const reverseForm = useForm({ reason: '' });

    const post = (action: string) => router.post(`/payroll/processing/${item.uuid}/${action}`, {}, { preserveScroll: true });

    return (
        <AppLayout>
            <Head title={item.run_number} />
            <div className="space-y-6">
                <section className="flex flex-col justify-between gap-4 rounded-lg border bg-card p-5 lg:flex-row lg:items-center">
                    <div>
                        <Link href="/payroll/processing" className="inline-flex items-center gap-1 text-sm text-primary hover:underline"><ArrowLeft className="h-4 w-4" /> Payroll Processing</Link>
                        <h1 className="mt-2 text-2xl font-semibold">{item.run_number}</h1>
                        <p className="mt-1 text-sm text-muted-foreground">Status: {item.status}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button onClick={() => post('calculate')}><Calculator className="h-4 w-4" /> Calculate</Button>
                        <Button variant="secondary" onClick={() => post('approve')}><CheckCircle2 className="h-4 w-4" /> Approve</Button>
                        <Button variant="secondary" onClick={() => post('lock')}><Lock className="h-4 w-4" /> Lock</Button>
                        <Button variant="secondary" onClick={() => post('outputs')}><FileText className="h-4 w-4" /> Generate Outputs</Button>
                    </div>
                </section>

                <section className="grid gap-4 sm:grid-cols-3">
                    <Card className="p-4"><p className="text-sm text-muted-foreground">Gross</p><p className="mt-2 text-2xl font-semibold">{item.gross_total.toLocaleString()}</p></Card>
                    <Card className="p-4"><p className="text-sm text-muted-foreground">Deductions</p><p className="mt-2 text-2xl font-semibold">{item.deduction_total.toLocaleString()}</p></Card>
                    <Card className="p-4"><p className="text-sm text-muted-foreground">Net</p><p className="mt-2 text-2xl font-semibold">{item.net_total.toLocaleString()}</p></Card>
                </section>

                <Card className="overflow-hidden">
                    <div className="border-b p-5"><h2 className="text-lg font-semibold">Preview</h2></div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-muted/50 text-xs uppercase text-muted-foreground"><tr><th className="px-4 py-3">Employee</th><th className="px-4 py-3">Code</th><th className="px-4 py-3">Name</th><th className="px-4 py-3">Type</th><th className="px-4 py-3">Amount</th></tr></thead>
                            <tbody className="divide-y">{item.items.map((line) => <tr key={line.uuid}><td className="px-4 py-3">{line.employee_number} - {line.employee}</td><td className="px-4 py-3">{line.code}</td><td className="px-4 py-3">{line.name}</td><td className="px-4 py-3">{line.type}</td><td className="px-4 py-3">{line.amount.toLocaleString()}</td></tr>)}</tbody>
                        </table>
                    </div>
                </Card>

                <section className="grid gap-6 lg:grid-cols-2">
                    <Card className="p-5">
                        <h2 className="text-lg font-semibold">Generated Files</h2>
                        <div className="mt-4 space-y-2">{item.outputs.map((file) => <a key={file.uuid} href={file.url} className="block rounded-md border p-3 text-sm text-primary hover:bg-primary/5">{file.type} - {file.name}</a>)}</div>
                    </Card>
                    <Card className="p-5">
                        <h2 className="text-lg font-semibold">Reverse Payroll</h2>
                        <form onSubmit={(e) => { e.preventDefault(); reverseForm.post(`/payroll/processing/${item.uuid}/reverse`); }} className="mt-4 space-y-3">
                            <div className="space-y-2"><Label>Reason</Label><Input value={reverseForm.data.reason} onChange={(e) => reverseForm.setData('reason', e.target.value)} /></div>
                            <Button variant="danger" disabled={reverseForm.processing}><RotateCcw className="h-4 w-4" /> Reverse Payroll</Button>
                        </form>
                    </Card>
                </section>
            </div>
        </AppLayout>
    );
}
