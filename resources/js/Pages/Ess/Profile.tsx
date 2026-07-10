import { Head } from '@inertiajs/react';
import { Landmark, Phone, UserRound, Users } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card } from '@/Components/ui/card';
import { EssNav } from '@/Components/Ess/EssNav';

export default function EssProfile({ employee, contacts, bank_accounts, dependants }: { employee: any; contacts: any[]; bank_accounts: any[]; dependants: any[] }) {
    return (
        <AppLayout>
            <Head title="ESS Profile" />
            <div className="space-y-6">
                <EssNav />
                <Card className="p-5"><div className="flex items-center gap-4"><div className="flex h-16 w-16 items-center justify-center rounded-lg bg-secondary">{employee?.photo_url ? <img src={employee.photo_url} className="h-full w-full rounded-lg object-cover" /> : <UserRound className="h-8 w-8 text-muted-foreground" />}</div><div><h1 className="text-2xl font-semibold">{employee?.full_name}</h1><p className="text-sm text-muted-foreground">{employee?.employee_number} · {employee?.department}</p></div></div></Card>
                <section className="grid gap-6 lg:grid-cols-3">
                    <Panel title="Contacts" icon={Phone} rows={contacts} />
                    <Panel title="Bank Details" icon={Landmark} rows={bank_accounts} />
                    <Panel title="Dependants" icon={Users} rows={dependants} />
                </section>
            </div>
        </AppLayout>
    );
}

function Panel({ title, icon: Icon, rows }: { title: string; icon: any; rows: any[] }) {
    return <Card className="p-5"><div className="mb-4 flex items-center gap-2"><Icon className="h-5 w-5 text-primary" /><h2 className="text-lg font-semibold">{title}</h2></div><div className="space-y-2">{rows.map((row, i) => <div key={row.uuid ?? i} className="rounded-md border p-3 text-sm">{Object.entries(row).slice(0, 3).map(([k, v]) => <p key={k}><span className="text-muted-foreground">{k.replaceAll('_', ' ')}:</span> {String(v ?? '-')}</p>)}</div>)}</div></Card>;
}
