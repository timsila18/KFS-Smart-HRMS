import { Head, Link, usePage } from '@inertiajs/react';
import { Bell, BriefcaseBusiness, CalendarDays, CheckCircle2, CircleDollarSign, Clock, FileBarChart, Leaf, Plane, UserPlus, Users } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { ChartPanel } from '@/Components/Dashboard/ChartPanel';
import { KfsLogo } from '@/Components/KfsLogo';
import { MetricCard } from '@/Components/Dashboard/MetricCard';
import { Card } from '@/Components/ui/card';
import { ChangePasswordPanel } from '@/Components/ChangePasswordPanel';
import { PageProps } from '@/types';

type Metric = {
    label: string;
    value: number;
    meta: string;
    trend: string;
};

type ChartData = {
    labels: string[];
    values: number[];
};

type PayrollCalendarItem = {
    uuid: string;
    code: string;
    range: string;
    payDate: string;
    status: string;
};

type NotificationItem = {
    uuid: string;
    channel: string;
    subject: string;
    body: string | null;
    isRead: boolean;
    createdAt: string;
};

type QuickAction = {
    label: string;
    description: string;
    href: string;
    permission: string;
};

type LoginHistory = {
    uuid: string;
    ip_address: string | null;
    status: string;
    logged_in_at: string;
};

type ActivityLog = {
    uuid: string;
    event: string;
    auditable_type: string;
    created_at: string;
};

type DashboardProps = PageProps<{
    dashboard: {
        cards: {
            employees: Metric;
            payrollReady: Metric;
            contractsExpiring: Metric;
            employeesOnLeave: Metric;
        };
        charts: {
            monthlyPayroll: ChartData;
            employeeDistribution: ChartData;
            leaveStatistics: ChartData;
        };
        payrollCalendar: PayrollCalendarItem[];
        notifications: NotificationItem[];
        quickActions: QuickAction[];
    };
    loginHistory: LoginHistory[];
    activityLogs: ActivityLog[];
}>;

const actionIcons = [UserPlus, CircleDollarSign, Plane, FileBarChart];

export default function Dashboard({ dashboard, loginHistory, activityLogs }: DashboardProps) {
    const { auth } = usePage<PageProps>().props;
    const permissions = new Set(auth.permissions);
    const visibleActions = dashboard.quickActions.filter((action) => permissions.has(action.permission));

    return (
        <AppLayout>
            <Head title="KFS Dashboard" />
            <div className="space-y-6">
                <section className="overflow-hidden rounded-lg border bg-primary text-primary-foreground shadow-sm">
                    <div className="grid gap-6 p-5 md:grid-cols-[1fr_320px] md:p-6">
                        <div>
                            <KfsLogo className="mb-4 h-16 w-auto rounded-md bg-white p-2 shadow-sm" />
                            <p className="text-sm font-semibold uppercase text-white/75">Kenya Forest Service HRMS</p>
                            <h1 className="mt-2 text-3xl font-semibold">KFS Command Dashboard</h1>
                            <p className="mt-3 max-w-3xl text-sm leading-6 text-white/78">
                                Monitor workforce strength, payroll readiness, contract risk, leave activity, and operational alerts across KFS stations.
                            </p>
                        </div>
                        <div className="grid grid-cols-2 gap-3 text-sm">
                            <div className="rounded-lg border border-white/15 bg-white/10 p-3">
                                <p className="text-white/70">Role</p>
                                <p className="mt-1 font-semibold">{auth.roles[0] ?? 'Assigned user'}</p>
                            </div>
                            <div className="rounded-lg border border-white/15 bg-white/10 p-3">
                                <p className="text-white/70">Permissions</p>
                                <p className="mt-1 font-semibold">{auth.permissions.length}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <MetricCard icon={Users} tone="green" {...dashboard.cards.employees} />
                    <MetricCard icon={CheckCircle2} tone="blue" {...dashboard.cards.payrollReady} />
                    <MetricCard icon={BriefcaseBusiness} tone="gold" {...dashboard.cards.contractsExpiring} />
                    <MetricCard icon={Leaf} tone="red" {...dashboard.cards.employeesOnLeave} />
                </section>

                <section className="grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
                    <ChartPanel
                        title="Monthly Payroll"
                        description="Net payroll totals across recent payroll periods."
                        type="line"
                        labels={dashboard.charts.monthlyPayroll.labels}
                        values={dashboard.charts.monthlyPayroll.values}
                        currency
                    />
                    <ChartPanel
                        title="Employee Distribution"
                        description="Active employees grouped by KFS region."
                        type="doughnut"
                        labels={dashboard.charts.employeeDistribution.labels}
                        values={dashboard.charts.employeeDistribution.values}
                    />
                </section>

                <section className="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                    <ChartPanel
                        title="Leave Statistics"
                        description="Approved and submitted leave days by leave type this year."
                        type="bar"
                        labels={dashboard.charts.leaveStatistics.labels}
                        values={dashboard.charts.leaveStatistics.values}
                    />

                    <div className="grid gap-6">
                        <Card className="p-5">
                            <div className="mb-4 flex items-center gap-2">
                                <CalendarDays className="h-5 w-5 text-primary" />
                                <h2 className="text-lg font-semibold">Payroll Calendar</h2>
                            </div>
                            <div className="space-y-3">
                                {dashboard.payrollCalendar.map((period) => (
                                    <div key={period.uuid} className="flex flex-col gap-2 rounded-md border p-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p className="font-medium">{period.code}</p>
                                            <p className="text-sm text-muted-foreground">{period.range}</p>
                                        </div>
                                        <div className="text-left sm:text-right">
                                            <p className="text-sm font-medium">{period.payDate}</p>
                                            <span className="mt-1 inline-flex rounded-md bg-secondary px-2 py-1 text-xs font-medium text-secondary-foreground">{period.status}</span>
                                        </div>
                                    </div>
                                ))}
                                {dashboard.payrollCalendar.length === 0 && <p className="text-sm text-muted-foreground">No upcoming payroll periods configured.</p>}
                            </div>
                        </Card>

                        <Card className="p-5">
                            <div className="mb-4 flex items-center gap-2">
                                <Bell className="h-5 w-5 text-primary" />
                                <h2 className="text-lg font-semibold">Notifications</h2>
                            </div>
                            <div className="space-y-3">
                                {dashboard.notifications.map((notification) => (
                                    <div key={notification.uuid} className="rounded-md border p-3">
                                        <div className="flex items-start justify-between gap-3">
                                            <p className="text-sm font-medium">{notification.subject}</p>
                                            {!notification.isRead && <span className="rounded-md bg-accent px-2 py-1 text-xs font-semibold text-accent-foreground">New</span>}
                                        </div>
                                        <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">{notification.body ?? notification.channel}</p>
                                        <p className="mt-2 text-xs text-muted-foreground">{notification.createdAt}</p>
                                    </div>
                                ))}
                                {dashboard.notifications.length === 0 && <p className="text-sm text-muted-foreground">No notifications yet.</p>}
                            </div>
                        </Card>
                    </div>
                </section>

                <section className="rounded-lg border bg-card p-5 shadow-sm">
                    <div className="mb-5">
                        <h2 className="text-lg font-semibold">Quick Actions</h2>
                        <p className="mt-1 text-sm text-muted-foreground">Role-aware shortcuts for common KFS Smart HRMS workflows.</p>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        {(visibleActions.length > 0 ? visibleActions : dashboard.quickActions).map((action, index) => {
                            const Icon = actionIcons[index] ?? FileBarChart;
                            const enabled = permissions.has(action.permission);

                            return enabled ? (
                                <Link key={action.label} href={action.href} className="rounded-lg border p-4 transition hover:border-primary hover:bg-primary/5">
                                    <Icon className="h-5 w-5 text-primary" />
                                    <p className="mt-3 font-medium">{action.label}</p>
                                    <p className="mt-1 text-sm text-muted-foreground">{action.description}</p>
                                </Link>
                            ) : (
                                <div key={action.label} className="rounded-lg border bg-muted/40 p-4 opacity-70">
                                    <Icon className="h-5 w-5 text-muted-foreground" />
                                    <p className="mt-3 font-medium">{action.label}</p>
                                    <p className="mt-1 text-sm text-muted-foreground">Permission required: {action.permission}</p>
                                </div>
                            );
                        })}
                    </div>
                </section>

                <section className="grid gap-6 xl:grid-cols-2">
                    <Card className="p-5">
                        <div className="mb-4 flex items-center gap-2">
                            <Clock className="h-5 w-5 text-primary" />
                            <h2 className="text-lg font-semibold">Recent Login History</h2>
                        </div>
                        <div className="space-y-3">
                            {loginHistory.map((item) => (
                                <div key={item.uuid} className="flex items-center justify-between gap-3 rounded-md border p-3 text-sm">
                                    <div>
                                        <p className="font-medium">{new Date(item.logged_in_at).toLocaleString()}</p>
                                        <p className="text-muted-foreground">{item.ip_address ?? 'Unknown IP'}</p>
                                    </div>
                                    <span className="rounded-md bg-secondary px-2 py-1 text-xs font-medium">{item.status}</span>
                                </div>
                            ))}
                            {loginHistory.length === 0 && <p className="text-sm text-muted-foreground">No login records yet.</p>}
                        </div>
                    </Card>

                    <Card className="p-5">
                        <div className="mb-4 flex items-center gap-2">
                            <FileBarChart className="h-5 w-5 text-primary" />
                            <h2 className="text-lg font-semibold">Recent Activity</h2>
                        </div>
                        <div className="space-y-3">
                            {activityLogs.map((item) => (
                                <div key={item.uuid} className="rounded-md border p-3">
                                    <p className="text-sm font-medium">{item.event}</p>
                                    <p className="mt-1 text-xs text-muted-foreground">{item.auditable_type} - {new Date(item.created_at).toLocaleString()}</p>
                                </div>
                            ))}
                            {activityLogs.length === 0 && <p className="text-sm text-muted-foreground">No activity records yet.</p>}
                        </div>
                    </Card>
                </section>

                <ChangePasswordPanel />
            </div>
        </AppLayout>
    );
}
