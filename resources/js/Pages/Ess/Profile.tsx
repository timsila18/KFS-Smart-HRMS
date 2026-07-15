import { Head, useForm } from '@inertiajs/react';
import { Landmark, Phone, Save, ShieldCheck, UserRound, Users } from 'lucide-react';
import type React from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { EssNav } from '@/Components/Ess/EssNav';

type BankBranch = {
    id: number;
    label: string;
    bank_name: string;
    branch_name: string;
    bank_code: string;
    branch_code: string;
};

export default function EssProfile({
    employee,
    contacts,
    bank_accounts,
    dependants,
    bank_branches,
}: {
    employee: any;
    contacts: any[];
    bank_accounts: any[];
    dependants: any[];
    bank_branches: BankBranch[];
}) {
    const primaryBank = bank_accounts?.find((account) => account.is_primary) ?? bank_accounts?.[0] ?? {};
    const bankForm = useForm({
        bank_branch_id: primaryBank.bank_branch_id ? String(primaryBank.bank_branch_id) : '',
        account_name: primaryBank.account_name ?? employee?.full_name ?? '',
        account_number: primaryBank.account_number ?? '',
    });
    const selectedBranch = bank_branches.find((branch) => String(branch.id) === String(bankForm.data.bank_branch_id));

    const submitBank = (event: React.FormEvent) => {
        event.preventDefault();
        bankForm.post('/ess/profile/bank', { preserveScroll: true });
    };

    return (
        <AppLayout>
            <Head title="ESS Profile" />
            <div className="grid gap-6 xl:grid-cols-[280px_1fr]">
                <EssNav />
                <div className="space-y-6">
                    <Card className="p-5">
                        <div className="flex flex-wrap items-center gap-4">
                            <div className="flex h-20 w-20 items-center justify-center overflow-hidden rounded-lg bg-secondary">
                                {employee?.photo_url ? <img src={employee.photo_url} alt="" className="h-full w-full rounded-lg object-cover" /> : <UserRound className="h-9 w-9 text-muted-foreground" />}
                            </div>
                            <div>
                                <p className="text-xs font-bold uppercase text-primary">KFS employee self service</p>
                                <h1 className="mt-1 text-2xl font-semibold">{employee?.full_name ?? 'Employee'}</h1>
                                <p className="text-sm text-muted-foreground">{employee?.employee_number ?? '-'} · {employee?.department ?? 'Directorate pending'} · {employee?.station ?? 'Service station pending'}</p>
                            </div>
                        </div>
                    </Card>

                    <section className="grid gap-6 lg:grid-cols-2">
                        <Card className="p-5">
                            <div className="mb-4 flex items-center gap-2">
                                <UserRound className="h-5 w-5 text-primary" />
                                <h2 className="text-lg font-semibold">Personal & Service Details</h2>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Detail label="Staff number" value={employee?.employee_number} />
                                <Detail label="Service station" value={employee?.station} />
                                <Detail label="Directorate" value={employee?.department} />
                                <Detail label="Designation" value={employee?.position} />
                                <Detail label="Service status" value={employee?.status} />
                            </div>
                        </Card>

                        <Card className="p-5">
                            <div className="mb-4 flex items-center gap-2">
                                <Phone className="h-5 w-5 text-primary" />
                                <h2 className="text-lg font-semibold">Contacts</h2>
                            </div>
                            <RecordList rows={contacts} fallback="No contact records captured." />
                        </Card>
                    </section>

                    <section className="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                        <Card className="p-5">
                            <div className="mb-4 flex items-center gap-2">
                                <Landmark className="h-5 w-5 text-primary" />
                                <div>
                                    <h2 className="text-lg font-semibold">Payroll Bank Details</h2>
                                    <p className="text-sm text-muted-foreground">Choose your bank branch by name. KFS Smart HRMS stores the bank and branch codes automatically for net-to-bank reports.</p>
                                </div>
                            </div>
                            <form onSubmit={submitBank} className="grid gap-4">
                                <div className="space-y-2">
                                    <Label>Bank and branch</Label>
                                    <select
                                        className="h-10 w-full rounded-md border bg-background px-3 text-sm"
                                        value={bankForm.data.bank_branch_id}
                                        onChange={(event) => bankForm.setData('bank_branch_id', event.target.value)}
                                    >
                                        <option value="">Select bank branch</option>
                                        {bank_branches.map((branch) => <option key={branch.id} value={branch.id}>{branch.label}</option>)}
                                    </select>
                                    {bankForm.errors.bank_branch_id && <p className="text-sm text-destructive">{bankForm.errors.bank_branch_id}</p>}
                                </div>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label>Account name</Label>
                                        <Input value={bankForm.data.account_name} onChange={(event) => bankForm.setData('account_name', event.target.value)} />
                                        {bankForm.errors.account_name && <p className="text-sm text-destructive">{bankForm.errors.account_name}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Account number</Label>
                                        <Input value={bankForm.data.account_number} onChange={(event) => bankForm.setData('account_number', event.target.value)} />
                                        {bankForm.errors.account_number && <p className="text-sm text-destructive">{bankForm.errors.account_number}</p>}
                                    </div>
                                </div>
                                {selectedBranch && (
                                    <div className="rounded-md border bg-secondary/30 p-3 text-sm">
                                        <p className="font-medium">{selectedBranch.bank_name}</p>
                                        <p className="text-muted-foreground">{selectedBranch.branch_name}</p>
                                        <p className="mt-1 text-xs text-muted-foreground">Branch coding is captured automatically for payroll reporting.</p>
                                    </div>
                                )}
                                <Button type="submit" disabled={bankForm.processing}>
                                    <Save className="h-4 w-4" /> Save Bank Details
                                </Button>
                            </form>
                        </Card>

                        <Card className="p-5">
                            <div className="mb-4 flex items-center gap-2">
                                <ShieldCheck className="h-5 w-5 text-primary" />
                                <h2 className="text-lg font-semibold">Current Primary Bank</h2>
                            </div>
                            {primaryBank?.account_number ? (
                                <div className="rounded-md border p-3 text-sm">
                                    <p className="font-semibold">{primaryBank.bank_name}</p>
                                    <p className="text-muted-foreground">{primaryBank.branch_name}</p>
                                    <p className="mt-3"><span className="text-muted-foreground">Account name:</span> {primaryBank.account_name ?? '-'}</p>
                                    <p><span className="text-muted-foreground">Account number:</span> {primaryBank.account_number}</p>
                                    <p className="mt-3 text-xs text-muted-foreground">Bank and branch codes are stored securely for HRISKE-style bank schedules.</p>
                                </div>
                            ) : (
                                <p className="rounded-md border border-dashed p-3 text-sm text-muted-foreground">No primary bank account captured yet.</p>
                            )}
                        </Card>
                    </section>

                    <section className="grid gap-6 lg:grid-cols-2">
                        <Panel title="Dependants" icon={Users} rows={dependants} />
                        <Panel title="All Bank Accounts" icon={Landmark} rows={bank_accounts} />
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}

function Detail({ label, value }: { label: string; value?: any }) {
    return (
        <div className="rounded-md border bg-background p-3">
            <p className="text-xs font-bold uppercase text-muted-foreground">{label}</p>
            <p className="mt-1 text-sm font-semibold">{value || '-'}</p>
        </div>
    );
}

function Panel({ title, icon: Icon, rows }: { title: string; icon: any; rows: any[] }) {
    return (
        <Card className="p-5">
            <div className="mb-4 flex items-center gap-2">
                <Icon className="h-5 w-5 text-primary" />
                <h2 className="text-lg font-semibold">{title}</h2>
            </div>
            <RecordList rows={rows} fallback="No records captured yet." />
        </Card>
    );
}

function RecordList({ rows, fallback }: { rows: any[]; fallback: string }) {
    return (
        <div className="space-y-2">
            {(rows ?? []).map((row, index) => (
                <div key={row.uuid ?? index} className="rounded-md border p-3 text-sm">
                    {Object.entries(row)
                        .filter(([key]) => !['id', 'employee_id', 'bank_branch_id', 'bank_code', 'branch_code', 'created_by', 'updated_by', 'deleted_by', 'deleted_at', 'metadata'].includes(key))
                        .slice(0, 6)
                        .map(([key, value]) => (
                            <p key={key}><span className="text-muted-foreground">{key.replaceAll('_', ' ')}:</span> {String(value ?? '-')}</p>
                        ))}
                </div>
            ))}
            {(rows ?? []).length === 0 && <p className="text-sm text-muted-foreground">{fallback}</p>}
        </div>
    );
}
