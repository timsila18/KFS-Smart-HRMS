import { Head, Link } from '@inertiajs/react';
import { Bell, CalendarDays, Download, FileArchive, FileText, Landmark, Settings, ShieldCheck, Star, WalletCards } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card } from '@/Components/ui/card';
import { EssNav } from '@/Components/Ess/EssNav';

export default function EssListPage({ title, description, rows }: { title: string; description: string; rows: Record<string, any>[] }) {
    return (
        <AppLayout>
            <Head title={title} />
            <div className="grid gap-6 xl:grid-cols-[280px_1fr]">
                <EssNav />
                <div className="space-y-6">
                    <section className="rounded-lg border bg-card p-5">
                        <p className="text-xs font-bold uppercase text-primary">Kenya Forest Service ESS</p>
                        <h1 className="mt-1 text-2xl font-semibold">{title}</h1>
                        <p className="mt-2 text-sm text-muted-foreground">{description}</p>
                    </section>
                    {renderEssSection(title, rows ?? [])}
                </div>
            </div>
        </AppLayout>
    );
}

function renderEssSection(title: string, rows: Record<string, any>[]) {
    if (title === 'Leave') return <LeaveSection rows={rows} />;
    if (title === 'Payslips' || title === 'Payroll History') return <PayslipSection rows={rows} />;
    if (title === 'P9') return <P9Section rows={rows} />;
    if (title === 'Documents') return <DocumentsSection rows={rows} />;
    if (title === 'Issued Documents') return <IssuedDocumentsSection rows={rows} />;
    if (title === 'Service Policies') return <PoliciesSection rows={rows} />;
    if (title === 'Performance') return <PerformanceSection rows={rows} />;
    if (title === 'Training') return <CardsSection icon={Star} rows={rows} empty="No training nominations or completions have been posted." />;
    if (title === 'Duty Attendance') return <CardsSection icon={CalendarDays} rows={rows} empty="No duty attendance records have been posted." />;
    if (title === 'Loans & Deductions') return <LoansSection rows={rows} />;
    if (title === 'Messages' || title === 'Notifications') return <MessagesSection rows={rows} />;
    if (title === 'My Settings') return <SettingsSection rows={rows} />;

    return <TableSection rows={rows} />;
}

function LeaveSection({ rows }: { rows: Record<string, any>[] }) {
    const pending = rows.filter((row) => row.status === 'submitted' || row.status === 'pending').length;
    const approvedDays = rows.filter((row) => row.status === 'approved').reduce((total, row) => total + Number(row.requested_days ?? 0), 0);

    return (
        <div className="grid gap-6 xl:grid-cols-[1fr_380px]">
            <Card className="p-5">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="font-semibold">Leave Actions</h2>
                        <p className="mt-1 text-sm text-muted-foreground">Apply, track status, and download request history. Every KFS leave application uses one approver.</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/ess/requests" className="inline-flex h-10 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground">Apply Leave</Link>
                        <Link href="/ess/requests" className="inline-flex h-10 items-center rounded-md border px-4 text-sm font-medium hover:bg-secondary">Request Off Day</Link>
                    </div>
                </div>
                <div className="mt-5 grid gap-3 md:grid-cols-3">
                    <Metric label="Approved days" value={approvedDays} />
                    <Metric label="Pending approvals" value={pending} />
                    <Metric label="Leave records" value={rows.length} />
                </div>
            </Card>
            <Card className="p-5">
                <h2 className="font-semibold">Balances & Status</h2>
                <div className="mt-4 space-y-3 text-sm">
                    <Line label="Annual leave balance" value={`${Math.max(0, 30 - approvedDays)} days`} />
                    <Line label="Pending approvals" value={pending} />
                    <Line label="Upcoming holidays" value="Configured by HR" />
                    <Line label="Off-day requests" value={rows.filter((row) => row.reason?.toString().toLowerCase().includes('off')).length} />
                </div>
            </Card>
            <Card className="xl:col-span-2">
                <TableSection rows={rows} actions />
            </Card>
        </div>
    );
}

function PayslipSection({ rows }: { rows: Record<string, any>[] }) {
    const latest = rows[0];

    return (
        <div className="space-y-6">
            <Card className="p-5">
                <h2 className="font-semibold">Latest Payslip</h2>
                <div className="mt-4 grid gap-3 md:grid-cols-3">
                    <Metric label="Latest period" value={latest?.period ?? 'Not published'} />
                    <Metric label="Gross pay" value={latest ? `KES ${money(latest.gross)}` : 'KES 0'} />
                    <Metric label="Net pay" value={latest ? `KES ${money(latest.net)}` : 'KES 0'} />
                </div>
                {latest && <div className="mt-4 flex justify-end gap-2"><ActionLinks row={latest} /></div>}
            </Card>
            <Card>
                <TableSection rows={rows} actions />
            </Card>
        </div>
    );
}

function P9Section({ rows }: { rows: Record<string, any>[] }) {
    const taxable = rows.reduce((total, row) => total + Number(row.taxable_pay ?? 0), 0);
    const paye = rows.reduce((total, row) => total + Number(row.paye ?? 0), 0);

    return (
        <div className="space-y-6">
            <section className="grid gap-3 md:grid-cols-3">
                <Metric label="Taxable pay" value={`KES ${money(taxable)}`} />
                <Metric label="PAYE" value={`KES ${money(paye)}`} />
                <Metric label="Periods" value={rows.length} />
            </section>
            <Card><TableSection rows={rows} /></Card>
        </div>
    );
}

function DocumentsSection({ rows }: { rows: Record<string, any>[] }) {
    return (
        <div className="space-y-6">
            <Card className="p-5">
                <h2 className="font-semibold">Personal Document Details</h2>
                <p className="mt-1 text-sm text-muted-foreground">National ID, KRA PIN, SHA/SHIF, NSSF, and bank records are maintained here for official payroll and HR reporting.</p>
                <div className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <Metric label="Documents posted" value={rows.length} />
                    <Metric label="Statutory records" value="Profile" />
                    <Metric label="Bank updates" value="My Profile" />
                    <Metric label="Approval mode" value="HR review" />
                </div>
            </Card>
            <CardsSection icon={FileText} rows={rows} empty="No staff documents have been posted." />
        </div>
    );
}

function IssuedDocumentsSection({ rows }: { rows: Record<string, any>[] }) {
    return <CardsSection icon={FileArchive} rows={rows} empty="No appointment letters, contracts, posting letters, or service documents have been issued yet." />;
}

function PoliciesSection({ rows }: { rows: Record<string, any>[] }) {
    return (
        <div className="space-y-4">
            <Card className="p-5">
                <h2 className="font-semibold">KFS Service Documents</h2>
                <p className="mt-1 text-sm text-muted-foreground">Policies, circulars, manuals, and shared HR forms available to KFS staff. These are official service documents for Kenya Forest Service operations.</p>
            </Card>
            <CardsSection icon={ShieldCheck} rows={rows} empty="No KFS service policies have been published." />
        </div>
    );
}

function PerformanceSection({ rows }: { rows: Record<string, any>[] }) {
    return (
        <Card className="p-5">
            <h2 className="font-semibold">Performance Appraisal</h2>
            <p className="mt-1 text-sm text-muted-foreground">Track targets, supervisor feedback, scores, and appraisal stages for KFS performance management.</p>
            <div className="mt-5 grid gap-3 md:grid-cols-4">
                <Metric label="Appraisals" value={rows.length} />
                <Metric label="Open stages" value={rows.filter((row) => row.status !== 'completed').length} />
                <Metric label="Completed" value={rows.filter((row) => row.status === 'completed').length} />
                <Metric label="Average score" value={average(rows.map((row) => Number(row.overall_score ?? 0)))} />
            </div>
            <div className="mt-5">
                <TableSection rows={rows} />
            </div>
        </Card>
    );
}

function LoansSection({ rows }: { rows: Record<string, any>[] }) {
    const total = rows.reduce((sum, row) => sum + Number(row.amount ?? 0), 0);
    const monthly = rows[0]?.amount ?? 0;

    return (
        <div className="space-y-6">
            <section className="grid gap-3 md:grid-cols-3">
                <Metric label="Active deductions" value={rows.length} />
                <Metric label="Total deductions" value={`KES ${money(total)}`} />
                <Metric label="Latest deduction" value={`KES ${money(monthly)}`} />
            </section>
            <Card><TableSection rows={rows} /></Card>
        </div>
    );
}

function MessagesSection({ rows }: { rows: Record<string, any>[] }) {
    return <CardsSection icon={Bell} rows={rows} empty="No messages or notifications have been posted." />;
}

function SettingsSection({ rows }: { rows: Record<string, any>[] }) {
    return (
        <div className="grid gap-6 lg:grid-cols-2">
            <Card className="p-5">
                <div className="mb-4 flex items-center gap-2">
                    <Settings className="h-5 w-5 text-primary" />
                    <h2 className="font-semibold">Preferences</h2>
                </div>
                <div className="space-y-3">
                    <Line label="Theme" value="System ready" />
                    <Line label="Email notifications" value="Enabled" />
                    <Line label="SMS notifications" value="Configurable" />
                    <Line label="In-app notifications" value="Enabled" />
                </div>
            </Card>
            <Card className="p-5">
                <h2 className="font-semibold">Password & Security</h2>
                <p className="mt-2 text-sm text-muted-foreground">Use My Settings to review account access and password guidance. Password reveal is available on login and password forms.</p>
                <div className="mt-4">
                    <TableSection rows={rows} />
                </div>
            </Card>
        </div>
    );
}

function CardsSection({ icon: Icon, rows, empty }: { icon: any; rows: Record<string, any>[]; empty: string }) {
    return (
        <Card className="p-5">
            <div className="space-y-3">
                {rows.map((row, index) => (
                    <div key={row.uuid ?? index} className="flex flex-col justify-between gap-3 rounded-md border p-3 text-sm md:flex-row md:items-center">
                        <div className="min-w-0">
                            <div className="flex items-center gap-2">
                                <Icon className="h-4 w-4 text-primary" />
                                <p className="font-semibold">{row.title ?? row.document ?? row.subject ?? row.period ?? row.status ?? 'KFS record'}</p>
                            </div>
                            <p className="mt-1 text-muted-foreground">{row.description ?? row.body ?? row.reference ?? row.reason ?? renderCompact(row)}</p>
                        </div>
                        <ActionLinks row={row} />
                    </div>
                ))}
                {rows.length === 0 && <p className="rounded-md border border-dashed p-4 text-sm text-muted-foreground">{empty}</p>}
            </div>
        </Card>
    );
}

function TableSection({ rows, actions = false }: { rows: Record<string, any>[]; actions?: boolean }) {
    const columns = rows.length > 0 ? Object.keys(rows[0]).filter((key) => !['id', 'deleted_at', 'created_by', 'updated_by', 'deleted_by', 'url', 'file_path'].includes(key)) : [];

    return (
        <div className="overflow-hidden rounded-lg border bg-card">
            <div className="overflow-x-auto">
                <table className="w-full text-left text-sm">
                    <thead className="border-b bg-muted/50 text-xs uppercase text-muted-foreground">
                        <tr>
                            {columns.map((column) => <th key={column} className="px-4 py-3">{column.replaceAll('_', ' ')}</th>)}
                            {actions && <th className="px-4 py-3 text-right">Actions</th>}
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {rows.map((row, index) => (
                            <tr key={row.uuid ?? index} className="align-top">
                                {columns.map((column) => <td key={column} className="px-4 py-3">{renderValue(row[column])}</td>)}
                                {actions && <td className="px-4 py-3 text-right"><ActionLinks row={row} /></td>}
                            </tr>
                        ))}
                        {rows.length === 0 && (
                            <tr>
                                <td colSpan={Math.max(columns.length + (actions ? 1 : 0), 1)} className="px-4 py-10 text-center text-muted-foreground">
                                    No KFS service records have been posted here yet.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function ActionLinks({ row }: { row: Record<string, any> }) {
    const url = row.url ?? row.file_path;

    if (!url) {
        return null;
    }

    return (
        <div className="flex shrink-0 flex-wrap justify-end gap-2">
            <a href={String(url)} className="inline-flex h-8 items-center gap-1 rounded-md bg-primary px-3 text-xs font-medium text-primary-foreground"><Download className="h-3.5 w-3.5" /> Download</a>
        </div>
    );
}

function Metric({ label, value }: { label: string; value: any }) {
    return (
        <div className="rounded-md border bg-background p-4">
            <p className="text-xs font-bold uppercase text-muted-foreground">{label}</p>
            <p className="mt-2 text-xl font-bold">{value}</p>
        </div>
    );
}

function Line({ label, value }: { label: string; value: any }) {
    return <p className="flex justify-between gap-4 border-b pb-2"><span className="text-muted-foreground">{label}</span><span className="font-semibold">{value}</span></p>;
}

function renderValue(value: any) {
    if (value === null || value === undefined || value === '') return '-';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') return JSON.stringify(value);
    if (String(value).startsWith('/storage') || String(value).startsWith('http')) return <a href={String(value)} className="text-primary hover:underline">Open</a>;
    return String(value);
}

function renderCompact(row: Record<string, any>) {
    return Object.entries(row)
        .filter(([key, value]) => !['uuid', 'id'].includes(key) && value !== null && value !== undefined && value !== '')
        .slice(0, 3)
        .map(([key, value]) => `${key.replaceAll('_', ' ')}: ${value}`)
        .join(' | ');
}

function money(value: number) {
    return new Intl.NumberFormat('en-KE', { maximumFractionDigits: 2 }).format(value || 0);
}

function average(values: number[]) {
    const scored = values.filter((value) => value > 0);
    if (scored.length === 0) return '-';
    return (scored.reduce((sum, value) => sum + value, 0) / scored.length).toFixed(1);
}
