import { Head } from '@inertiajs/react';
import { Landmark, Phone, UserRound, Users } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card } from '@/Components/ui/card';
import { EssNav } from '@/Components/Ess/EssNav';

export default function EssProfile({ employee, contacts, bank_accounts, dependants }: { employee: any; contacts: any[]; bank_accounts: any[]; dependants: any[] }) {
    return (
        <AppLayout>
            <Head title="ESS Profile" />
            <div className="grid gap-6 xl:grid-cols-[280px_1fr]">
                <EssNav />
                <div className="space-y-6">
                    <Card className="p-5">
                        <div className="flex flex-wrap items-center gap-4">
                            <div className="flex h-20 w-20 items-center justify-center rounded-lg bg-secondary">
                                {employee?.photo_url ? <img src={employee.photo_url} alt="" className="h-full w-full rounded-lg object-cover" /> : <UserRound className="h-9 w-9 text-muted-foreground" />}
                            </div>
                            <div>
                                <p className="text-xs font-bold uppercase text-primary">KFS employee profile</p>
                                <h1 className="mt-1 text-2xl font-semibold">{employee?.full_name ?? 'Employee'}</h1>
                                <p className="text-sm text-muted-foreground">{employee?.employee_number ?? '-'} · {employee?.department ?? 'Directorate pending'} · {employee?.station ?? 'Service station pending'}</p>
                            </div>
                        </div>
                    </Card>
                    <section className="grid gap-6 lg:grid-cols-3">
                        <Panel title="Contacts" icon={Phone} rows={contacts} />
                        <Panel title="Bank Details" icon={Landmark} rows={bank_accounts} />
                        <Panel title="Dependants" icon={Users} rows={dependants} />
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}

function Panel({ title, icon: Icon, rows }: { title: string; icon: any; rows: any[] }) {
    return (
        <Card className="p-5">
            <div className="mb-4 flex items-center gap-2">
                <Icon className="h-5 w-5 text-primary" />
                <h2 className="text-lg font-semibold">{title}</h2>
            </div>
            <div className="space-y-2">
                {rows.map((row, i) => (
                    <div key={row.uuid ?? i} className="rounded-md border p-3 text-sm">
                        {Object.entries(row).filter(([key]) => !['id', 'created_by', 'updated_by', 'deleted_by', 'deleted_at'].includes(key)).slice(0, 5).map(([key, value]) => (
                            <p key={key}><span className="text-muted-foreground">{key.replaceAll('_', ' ')}:</span> {String(value ?? '-')}</p>
                        ))}
                    </div>
                ))}
                {rows.length === 0 && <p className="text-sm text-muted-foreground">No records captured yet.</p>}
            </div>
        </Card>
    );
}
