import { Head, router } from '@inertiajs/react';
import { CheckCircle2, FileText, XCircle } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';

type Approval = {
    id: number;
    uuid: string;
    status: string;
    approval_level: number;
    leave_request?: {
        uuid: string;
        start_date: string;
        end_date: string;
        requested_days: string;
        status: string;
        reason?: string | null;
        employee?: {
            employee_number: string;
            full_name: string;
            department?: { name: string } | null;
            station?: { name: string } | null;
        } | null;
        leave_type?: { name: string } | null;
    } | null;
};

type Paginated<T> = { data: T[] };

export default function LeaveApprovals({ approvals }: { approvals: Paginated<Approval> }) {
    const act = (approval: Approval, action: 'approve' | 'reject') => {
        router.post(`/leave/approvals/${approval.id}/${action}`, {}, { preserveScroll: true });
    };

    return (
        <AppLayout>
            <Head title="Leave Approvals" />
            <div className="space-y-6">
                <section className="rounded-lg border bg-primary p-6 text-primary-foreground">
                    <p className="text-sm font-semibold uppercase">One Approver Workflow</p>
                    <h1 className="mt-2 text-3xl font-bold">Leave Approvals</h1>
                    <p className="mt-2 text-sm text-primary-foreground/80">Review, approve, or reject submitted ESS leave requests.</p>
                </section>

                <Card className="overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-muted/50 text-xs uppercase text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-3">Employee</th>
                                    <th className="px-4 py-3">Leave</th>
                                    <th className="px-4 py-3">Dates</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {approvals.data.map((approval) => {
                                    const request = approval.leave_request;
                                    return (
                                        <tr key={approval.uuid}>
                                            <td className="px-4 py-3">
                                                <p className="font-medium">{request?.employee?.full_name ?? 'Employee'}</p>
                                                <p className="text-xs text-muted-foreground">{request?.employee?.employee_number ?? '-'}</p>
                                            </td>
                                            <td className="px-4 py-3">
                                                <p>{request?.leave_type?.name ?? 'Leave'}</p>
                                                <p className="text-xs text-muted-foreground">{request?.requested_days ?? 0} days</p>
                                            </td>
                                            <td className="px-4 py-3">{request?.start_date} to {request?.end_date}</td>
                                            <td className="px-4 py-3">
                                                <span className="rounded-md bg-secondary px-2 py-1 text-xs font-medium">{approval.status}</span>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-2">
                                                    <a href={`/leave/approvals/${approval.id}/form`} className="inline-flex h-10 items-center gap-2 rounded-md border px-4 text-sm font-medium hover:bg-secondary">
                                                        <FileText className="h-4 w-4" /> Form
                                                    </a>
                                                    {approval.status === 'pending' && (
                                                        <>
                                                        <Button type="button" onClick={() => act(approval, 'approve')}>
                                                            <CheckCircle2 className="h-4 w-4" /> Approve
                                                        </Button>
                                                        <Button type="button" variant="danger" onClick={() => act(approval, 'reject')}>
                                                            <XCircle className="h-4 w-4" /> Reject
                                                        </Button>
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                                {approvals.data.length === 0 && (
                                    <tr><td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">No leave approvals pending.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
