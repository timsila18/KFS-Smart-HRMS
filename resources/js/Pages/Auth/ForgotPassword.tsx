import { Head, Link, useForm } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import AuthLayout from '@/Layouts/AuthLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { FormError } from '@/Components/FormError';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    return (
        <AuthLayout title="Reset access" subtitle="Enter your registered email and KFS Smart HRMS will send password reset instructions.">
            <Head title="Forgot Password" />
            {status && <div className="mb-4 rounded-md border border-primary/25 bg-primary/10 p-3 text-sm text-primary">{status}</div>}
            <form onSubmit={(e) => { e.preventDefault(); post('/forgot-password'); }} className="space-y-5">
                <div className="space-y-2">
                    <Label htmlFor="email">Email address</Label>
                    <Input id="email" type="email" value={data.email} autoFocus onChange={(e) => setData('email', e.target.value)} />
                    <FormError message={errors.email} />
                </div>
                <Button type="submit" className="w-full" disabled={processing}>
                    <Mail className="h-4 w-4" />
                    Send reset link
                </Button>
                <Link href="/login" className="block text-center text-sm font-medium text-primary hover:underline">
                    Back to login
                </Link>
            </form>
        </AuthLayout>
    );
}
