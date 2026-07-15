import AppLayout from '@/Layouts/AppLayout';
import { Card } from '@/Components/ui/card';
import { Link } from '@inertiajs/react';
import { CalendarClock, FileBarChart, FileDown } from 'lucide-react';

type Report = {
    id: number;
    uuid: string;
    code: string;
    display_code?: string;
    name: string;
    module: string;
    is_schedulable: boolean;
    schedule_frequency?: string | null;
    next_run_at?: string | null;
};

type ReportRun = {
    id: number;
    uuid: string;
    status: string;
    file_path?: string | null;
    completed_at?: string | null;
    created_at: string;
    report?: Report;
};

export default function Index({ reports, latestRuns }: { reports: Record<string, Report[]>; latestRuns: ReportRun[] }) {
    return (
        <AppLayout>
            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-wide text-primary">Enterprise Reporting</p>
                        <h1 className="mt-1 text-3xl font-bold">KFS Reports</h1>
                        <p className="mt-2 max-w-3xl text-sm text-muted-foreground">
                            Payroll, statutory, HR, contracts, leave, performance, training, and scheduled management reports.
                        </p>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-[1fr_320px]">
                    <div className="space-y-5">
                        {Object.entries(reports).map(([module, items]) => (
                            <section key={module} className="space-y-3">
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-muted-foreground">{module}</h2>
                                <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                    {items.map((report) => (
                                        <Card key={report.code} className="flex min-h-36 flex-col justify-between p-4">
                                            <div>
                                                <div className="flex items-center justify-between gap-3">
                                                    <FileBarChart className="h-5 w-5 text-primary" />
                                                    {report.is_schedulable && (
                                                        <span className="rounded-md bg-secondary px-2 py-1 text-xs text-secondary-foreground">
                                                            {report.schedule_frequency}
                                                        </span>
                                                    )}
                                                </div>
                                                <h3 className="mt-4 font-semibold">{report.name}</h3>
                                                <p className="mt-1 text-xs text-muted-foreground">{report.display_code ?? report.code}</p>
                                            </div>
                                            <Link
                                                href={`/reports/${report.code}`}
                                                className="kfs-focus mt-4 inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition hover:bg-primary/90"
                                            >
                                                Open report
                                            </Link>
                                        </Card>
                                    ))}
                                </div>
                            </section>
                        ))}
                    </div>

                    <Card className="h-fit p-4">
                        <div className="flex items-center gap-2">
                            <CalendarClock className="h-5 w-5 text-primary" />
                            <h2 className="font-semibold">Recent Runs</h2>
                        </div>
                        <div className="mt-4 space-y-3">
                            {latestRuns.length === 0 && <p className="text-sm text-muted-foreground">No report runs yet.</p>}
                            {latestRuns.map((run) => (
                                <div key={run.uuid} className="rounded-md border p-3">
                                    <div className="flex items-start justify-between gap-2">
                                        <div>
                                            <p className="text-sm font-medium">{run.report?.name ?? 'Report'}</p>
                                            <p className="text-xs text-muted-foreground">{run.created_at}</p>
                                        </div>
                                        <FileDown className="h-4 w-4 text-muted-foreground" />
                                    </div>
                                    <span className="mt-2 inline-flex rounded-md bg-secondary px-2 py-1 text-xs text-secondary-foreground">
                                        {run.status}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
