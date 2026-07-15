import { Head, Link } from '@inertiajs/react';
import { Bell, CalendarDays, ClipboardCheck, FileText, Leaf, MessageSquareText, ShieldCheck, UserRound, WalletCards } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card } from '@/Components/ui/card';
import { EssNav } from '@/Components/Ess/EssNav';
import { KfsLogo } from '@/Components/KfsLogo';

type WorkspaceCard = { title: string; href: string; description: string };
type Props = {
    employee: any;
    summary: Record<string, number>;
    employment: Record<string, string>;
    latestPayslip: { uuid: string; period: string; net: number } | null;
    leaveBalance: { available: number; upcoming: number };
    profileCompletion: number;
    notifications: any[];
    requests: any[];
    workspaceCards: WorkspaceCard[];
};

const statCards = [
    ['Latest payslip', WalletCards, 'payslips'],
    ['Leave requests', CalendarDays, 'leave_requests'],
    ['Duty records', ClipboardCheck, 'attendance'],
    ['Messages', MessageSquareText, 'messages'],
] as const;

export default function EssDashboard({ employee, summary, employment, latestPayslip, leaveBalance, profileCompletion, notifications, requests, workspaceCards }: Props) {
    return (
        <AppLayout>
            <Head title="KFS Employee Self Service" />
            <div className="grid gap-6 xl:grid-cols-[280px_1fr]">
                <EssNav />

                <div className="space-y-6">
                    <section className="rounded-lg border bg-card p-5 shadow-sm">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p className="text-xs font-semibold text-muted-foreground">Kenya Forest Service · Employee Self Service · My Dashboard</p>
                                <h2 className="mt-3 text-xl font-bold">You are logged in as {employee?.full_name ?? 'KFS staff'}</h2>
                                <p className="mt-1 text-sm text-muted-foreground">KFS Smart HRMS personal workspace for service records, payroll, leave, notices, documents, and requests.</p>
                            </div>
                            <div className="flex flex-wrap items-center gap-2">
                                <div className="flex h-14 w-20 items-center justify-center rounded-md border bg-white p-1 shadow-sm">
                                    <KfsLogo className="max-h-full max-w-full object-contain" />
                                </div>
                                <Link href="/ess/messages" className="kfs-focus inline-flex h-9 items-center rounded-md border px-3 text-sm font-medium hover:bg-secondary">Messages</Link>
                                <Link href="/ess/notifications" className="kfs-focus inline-flex h-9 items-center rounded-md border px-3 text-sm font-medium hover:bg-secondary">Notifications</Link>
                                <Link href="/ess/settings" className="kfs-focus inline-flex h-9 items-center rounded-md border px-3 text-sm font-medium hover:bg-secondary">Settings</Link>
                            </div>
                        </div>
                    </section>

                    <section className="rounded-lg border bg-card p-4">
                        <div className="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-center text-sm font-bold text-blue-800 dark:border-blue-900/60 dark:bg-blue-950/20 dark:text-blue-100">
                            ESS
                        </div>
                    </section>

                    <section className="grid gap-4 rounded-lg border bg-card p-5 shadow-sm lg:grid-cols-[1fr_auto]">
                        <div>
                            <div className="flex items-center gap-3">
                                <div className="flex h-16 w-20 items-center justify-center rounded-md border bg-white p-1 shadow-sm">
                                    <KfsLogo className="max-h-full max-w-full object-contain" />
                                </div>
                                <div>
                                    <p className="text-xs font-bold uppercase text-primary">Kenya Forest Service</p>
                                    <h1 className="text-3xl font-bold">My KFS Service</h1>
                                    <p className="text-sm text-muted-foreground">{new Date().toLocaleDateString(undefined, { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' })}</p>
                                </div>
                            </div>
                            <p className="mt-4 max-w-3xl text-sm text-muted-foreground">
                                This is your Kenya Forest Service employee self service space for payslips, leave, profile details, service notices, documents, performance, deductions, and personal requests.
                            </p>
                        </div>
                        <div className="flex flex-wrap items-start gap-2">
                            <Link href="/ess/payslips" className="kfs-focus inline-flex h-10 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90">Payslips</Link>
                            <Link href="/ess/requests" className="kfs-focus inline-flex h-10 items-center rounded-md bg-secondary px-4 text-sm font-medium text-secondary-foreground hover:bg-secondary/80">Apply leave</Link>
                            <Link href="/ess/profile" className="kfs-focus inline-flex h-10 items-center rounded-md border px-4 text-sm font-medium hover:bg-secondary">My profile</Link>
                        </div>
                    </section>

                    <section className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        {(workspaceCards ?? []).slice(0, 12).map((card) => (
                            <Link key={card.href} href={card.href} className="rounded-lg border bg-slate-700 p-4 text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800 dark:bg-card dark:hover:bg-secondary">
                                <h2 className="text-sm font-bold">{card.title}</h2>
                                <p className="mt-3 text-xs leading-5 text-slate-200 dark:text-muted-foreground">{card.description}</p>
                            </Link>
                        ))}
                    </section>

                    <section className="rounded-lg border bg-card p-5">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <h2 className="text-lg font-semibold">My employment details</h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Staff number {employment?.staff_number ?? employee?.employee_number ?? '-'} in Service. Your records, approvals, and personal payroll history stay here.
                                </p>
                            </div>
                            <ShieldCheck className="h-6 w-6 text-primary" />
                        </div>
                        <dl className="mt-4 grid gap-3 md:grid-cols-3">
                            {Object.entries(employment ?? {}).filter(([key]) => key !== 'staff_number').map(([key, value]) => (
                                <div key={key} className="rounded-md border bg-background p-3">
                                    <dt className="text-xs font-bold uppercase text-muted-foreground">{key.replaceAll('_', ' ')}</dt>
                                    <dd className="mt-2 text-sm font-semibold">{value || '-'}</dd>
                                </div>
                            ))}
                        </dl>
                    </section>

                    <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <SummaryCard icon={WalletCards} label="Latest payslip" value={latestPayslip?.period ?? 'Not published'} detail={latestPayslip ? `Net KES ${money(latestPayslip.net)}` : 'Payroll history will appear here'} href="/ess/payslips" />
                        <SummaryCard icon={CalendarDays} label="My leave" value={`${leaveBalance?.available ?? 0} days`} detail={`${leaveBalance?.upcoming ?? 0} upcoming leave request(s)`} href="/ess/leave" tone="amber" />
                        <SummaryCard icon={UserRound} label="My profile" value={`${profileCompletion ?? 0}%`} detail="Profile completeness" href="/ess/profile" tone="emerald" />
                        <SummaryCard icon={Leaf} label="KFS status" value={employee?.status ?? 'active'} detail={employee?.station ?? 'Service station pending'} href="/ess/profile" tone="green" />
                    </section>

                    <section className="grid gap-6 lg:grid-cols-2">
                        <ActivityPanel title="My notices" icon={Bell} rows={notifications ?? []} empty="No notices yet." />
                        <ActivityPanel title="My requests" icon={FileText} rows={requests ?? []} empty="No personal requests submitted." />
                    </section>

                    <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {statCards.map(([label, Icon, key]) => (
                            <Card key={key} className="p-4">
                                <Icon className="h-5 w-5 text-primary" />
                                <p className="mt-3 text-2xl font-bold">{summary?.[key] ?? 0}</p>
                                <p className="text-sm text-muted-foreground">{label}</p>
                            </Card>
                        ))}
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}

function SummaryCard({ icon: Icon, label, value, detail, href, tone = 'blue' }: { icon: any; label: string; value: string; detail: string; href: string; tone?: string }) {
    const tones: Record<string, string> = {
        blue: 'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-100',
        amber: 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-100',
        emerald: 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-100',
        green: 'border-green-200 bg-green-50 text-green-900 dark:border-green-900/50 dark:bg-green-950/20 dark:text-green-100',
    };

    return (
        <Link href={href} className={`rounded-lg border p-4 shadow-sm transition hover:-translate-y-0.5 ${tones[tone]}`}>
            <Icon className="h-5 w-5" />
            <p className="mt-3 text-xs font-bold uppercase opacity-75">{label}</p>
            <p className="mt-1 text-2xl font-bold">{value}</p>
            <p className="mt-1 text-xs opacity-80">{detail}</p>
        </Link>
    );
}

function ActivityPanel({ title, icon: Icon, rows, empty }: { title: string; icon: any; rows: any[]; empty: string }) {
    return (
        <Card className="p-5">
            <div className="mb-4 flex items-center gap-2">
                <Icon className="h-5 w-5 text-primary" />
                <h2 className="text-lg font-semibold">{title}</h2>
            </div>
            <div className="space-y-3">
                {rows.slice(0, 5).map((item, index) => (
                    <div key={item.uuid ?? index} className="rounded-md border p-3 text-sm">
                        <p className="font-semibold">{item.subject ?? item.request_type ?? item.status ?? 'KFS Smart HRMS'}</p>
                        <p className="mt-1 text-muted-foreground">{item.body ?? item.remarks ?? item.status ?? 'Submitted'}</p>
                    </div>
                ))}
                {rows.length === 0 && <p className="text-sm text-muted-foreground">{empty}</p>}
            </div>
        </Card>
    );
}

function money(value: number) {
    return new Intl.NumberFormat('en-KE', { maximumFractionDigits: 2 }).format(value);
}
