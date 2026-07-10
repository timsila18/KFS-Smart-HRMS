import AppLayout from '@/Layouts/AppLayout';
import { ChartPanel } from '@/Components/Dashboard/ChartPanel';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Link, router } from '@inertiajs/react';
import { Download, FileSpreadsheet, Filter, Save } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';

type Report = {
    code: string;
    name: string;
    module: string;
    is_schedulable: boolean;
    schedule_frequency?: string | null;
    schedule_recipients?: string[] | null;
    next_run_at?: string | null;
};

type Option = { id: number; code: string; name: string };
type Dataset = { columns: string[]; rows: Record<string, string | number | null>[]; charts: { labels: string[]; values: number[] } };
type ScheduleFrequency = { label: string; interval: string };

export default function Show({
    report,
    filters,
    dataset,
    scheduleFrequencies,
    periods,
    departments,
}: {
    report: Report;
    filters: Record<string, string>;
    dataset: Dataset;
    scheduleFrequencies: Record<string, ScheduleFrequency>;
    periods: Option[];
    departments: Option[];
}) {
    const [localFilters, setLocalFilters] = useState<Record<string, string>>({
        period_id: filters.period_id ?? '',
        department_id: filters.department_id ?? '',
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
    });
    const [schedule, setSchedule] = useState({
        schedule_frequency: report.schedule_frequency ?? '',
        schedule_recipients: (report.schedule_recipients ?? []).join(', '),
    });

    const query = useMemo(() => new URLSearchParams(Object.entries(localFilters).filter(([, value]) => value)).toString(), [localFilters]);
    const exportUrl = (format: 'excel' | 'pdf') => `/reports/${report.code}/export/${format}${query ? `?${query}` : ''}`;

    const submitFilters = (event: FormEvent) => {
        event.preventDefault();
        router.get(`/reports/${report.code}`, localFilters, { preserveState: true, replace: true });
    };

    const saveSchedule = (event: FormEvent) => {
        event.preventDefault();
        router.post(`/reports/${report.code}/schedule`, {
            schedule_frequency: schedule.schedule_frequency || null,
            schedule_recipients: schedule.schedule_recipients.split(',').map((value) => value.trim()).filter(Boolean),
            ...localFilters,
        });
    };

    return (
        <AppLayout>
            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                    <div>
                        <Link href="/reports" className="text-sm font-medium text-primary">Reports</Link>
                        <h1 className="mt-1 text-3xl font-bold">{report.name}</h1>
                        <p className="mt-2 text-sm text-muted-foreground">{report.module} | {report.code}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <a
                            href={exportUrl('excel')}
                            className="kfs-focus inline-flex h-10 items-center justify-center gap-2 rounded-md bg-secondary px-4 text-sm font-medium text-secondary-foreground transition hover:bg-secondary/80"
                        >
                            <FileSpreadsheet className="h-4 w-4" />
                            Excel
                        </a>
                        <a
                            href={exportUrl('pdf')}
                            className="kfs-focus inline-flex h-10 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition hover:bg-primary/90"
                        >
                            <Download className="h-4 w-4" />
                            PDF
                        </a>
                    </div>
                </div>

                <Card className="p-4">
                    <form onSubmit={submitFilters} className="grid gap-4 md:grid-cols-5">
                        <div>
                            <Label htmlFor="period_id">Payroll Period</Label>
                            <select
                                id="period_id"
                                className="mt-2 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                value={localFilters.period_id}
                                onChange={(event) => setLocalFilters({ ...localFilters, period_id: event.target.value })}
                            >
                                <option value="">All periods</option>
                                {periods.map((period) => <option key={period.id} value={period.id}>{period.code}</option>)}
                            </select>
                        </div>
                        <div>
                            <Label htmlFor="department_id">Department</Label>
                            <select
                                id="department_id"
                                className="mt-2 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                value={localFilters.department_id}
                                onChange={(event) => setLocalFilters({ ...localFilters, department_id: event.target.value })}
                            >
                                <option value="">All departments</option>
                                {departments.map((department) => <option key={department.id} value={department.id}>{department.name}</option>)}
                            </select>
                        </div>
                        <div>
                            <Label htmlFor="date_from">Date From</Label>
                            <Input id="date_from" type="date" value={localFilters.date_from} onChange={(event) => setLocalFilters({ ...localFilters, date_from: event.target.value })} />
                        </div>
                        <div>
                            <Label htmlFor="date_to">Date To</Label>
                            <Input id="date_to" type="date" value={localFilters.date_to} onChange={(event) => setLocalFilters({ ...localFilters, date_to: event.target.value })} />
                        </div>
                        <div className="flex items-end">
                            <Button type="submit" className="w-full">
                                <Filter className="h-4 w-4" />
                                Apply
                            </Button>
                        </div>
                    </form>
                </Card>

                <div className="grid gap-4 lg:grid-cols-[1fr_320px]">
                    <ChartPanel
                        title={`${report.name} Chart`}
                        description="Generated from the current filter selection."
                        type="bar"
                        labels={dataset.charts.labels}
                        values={dataset.charts.values}
                        currency={report.module === 'payroll' || report.module === 'statutory' || report.module === 'deductions'}
                    />

                    <Card className="h-fit p-4">
                        <h2 className="font-semibold">Scheduled Report</h2>
                        <p className="mt-1 text-sm text-muted-foreground">Configure recurring delivery for this report.</p>
                        <form onSubmit={saveSchedule} className="mt-4 space-y-3">
                            <div>
                                <Label htmlFor="schedule_frequency">Frequency</Label>
                                <select
                                    id="schedule_frequency"
                                    className="mt-2 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                    value={schedule.schedule_frequency}
                                    onChange={(event) => setSchedule({ ...schedule, schedule_frequency: event.target.value })}
                                >
                                    <option value="">Disabled</option>
                                    {Object.entries(scheduleFrequencies).map(([value, frequency]) => (
                                        <option key={value} value={value}>{frequency.label}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="schedule_recipients">Recipients</Label>
                                <Input
                                    id="schedule_recipients"
                                    value={schedule.schedule_recipients}
                                    onChange={(event) => setSchedule({ ...schedule, schedule_recipients: event.target.value })}
                                    placeholder="finance@kfs.go.ke, hr@kfs.go.ke"
                                />
                            </div>
                            {report.next_run_at && <p className="text-xs text-muted-foreground">Next run: {report.next_run_at}</p>}
                            <Button type="submit" className="w-full">
                                <Save className="h-4 w-4" />
                                Save Schedule
                            </Button>
                        </form>
                    </Card>
                </div>

                <Card className="overflow-hidden">
                    <div className="border-b p-4">
                        <h2 className="font-semibold">Preview</h2>
                        <p className="text-sm text-muted-foreground">{dataset.rows.length.toLocaleString()} records</p>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-secondary text-secondary-foreground">
                                <tr>
                                    {dataset.columns.map((column) => <th key={column} className="whitespace-nowrap px-4 py-3 text-left font-semibold">{column.replaceAll('_', ' ')}</th>)}
                                </tr>
                            </thead>
                            <tbody>
                                {dataset.rows.length === 0 && (
                                    <tr><td className="px-4 py-8 text-center text-muted-foreground" colSpan={Math.max(dataset.columns.length, 1)}>No records matched the selected filters.</td></tr>
                                )}
                                {dataset.rows.slice(0, 250).map((row, index) => (
                                    <tr key={index} className="border-t">
                                        {dataset.columns.map((column) => <td key={column} className="whitespace-nowrap px-4 py-3">{row[column] ?? '-'}</td>)}
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
