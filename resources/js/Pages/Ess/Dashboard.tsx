import { Head, Link } from '@inertiajs/react';
import { Bell, Briefcase, CalendarDays, FileText, GraduationCap, UserRound, WalletCards } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card } from '@/Components/ui/card';
import { EssNav } from '@/Components/Ess/EssNav';

type Props = {
    employee: any;
    summary: Record<string, number>;
    notifications: any[];
    requests: any[];
};

const cards = [
    ['Payslips', 'payslips', '/ess/payslips', WalletCards],
    ['Leave Requests', 'leave_requests', '/ess/leave', CalendarDays],
    ['Training', 'training', '/ess/training', GraduationCap],
    ['Documents', 'documents', '/ess/documents', FileText],
] as const;

export default function EssDashboard({ employee, summary, notifications, requests }: Props) {
    return (
        <AppLayout>
            <Head title="ESS Dashboard" />
            <div className="space-y-6">
                <section className="rounded-lg border bg-primary p-5 text-primary-foreground">
                    <p className="text-sm font-semibold uppercase text-white/75">Employee Self Service</p>
                    <h1 className="mt-2 text-3xl font-semibold">Welcome, {employee?.full_name ?? 'Employee'}</h1>
                    <p className="mt-2 text-sm text-white/80">{employee?.employee_number} · {employee?.station ?? 'KFS'} · {employee?.position ?? 'Staff'}</p>
                </section>

                <EssNav />

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {cards.map(([label, key, href, Icon]) => (
                        <Link key={key} href={href} className="rounded-lg border bg-card p-4 shadow-sm transition hover:border-primary hover:bg-primary/5">
                            <Icon className="h-5 w-5 text-primary" />
                            <p className="mt-3 text-3xl font-semibold">{summary?.[key] ?? 0}</p>
                            <p className="mt-1 text-sm text-muted-foreground">{label}</p>
                        </Link>
                    ))}
                </section>

                <section className="grid gap-6 lg:grid-cols-2">
                    <Card className="p-5">
                        <div className="mb-4 flex items-center gap-2"><Bell className="h-5 w-5 text-primary" /><h2 className="text-lg font-semibold">Notifications</h2></div>
                        <div className="space-y-3">{notifications.slice(0, 5).map((item) => <div key={item.uuid} className="rounded-md border p-3 text-sm"><p className="font-medium">{item.subject}</p><p className="text-muted-foreground">{item.body}</p></div>)}</div>
                    </Card>
                    <Card className="p-5">
                        <div className="mb-4 flex items-center gap-2"><Briefcase className="h-5 w-5 text-primary" /><h2 className="text-lg font-semibold">Recent Requests</h2></div>
                        <div className="space-y-3">{requests.slice(0, 5).map((item) => <div key={item.uuid} className="rounded-md border p-3 text-sm"><p className="font-medium">{item.request_type}</p><p className="text-muted-foreground">{item.status}</p></div>)}</div>
                    </Card>
                </section>
            </div>
        </AppLayout>
    );
}
