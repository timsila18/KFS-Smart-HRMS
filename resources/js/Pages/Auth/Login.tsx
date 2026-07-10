import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircle, LogIn } from 'lucide-react';
import type React from 'react';
import AuthLayout from '@/Layouts/AuthLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { FormError } from '@/Components/FormError';

export default function Login({ status }: { canResetPassword: boolean; status?: string; twoFactorEnabled: boolean }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post('/login', { onFinish: () => reset('password') });
    };

    return (
        <AuthLayout title="Sign in" subtitle="Use your KFS Smart HRMS credentials to access HR, payroll, ESS, and reports.">
            <Head title="Login" />
            {status && <div className="mb-4 rounded-md border border-primary/25 bg-primary/10 p-3 text-sm text-primary">{status}</div>}
            <form onSubmit={submit} className="space-y-5">
                <div className="space-y-2">
                    <Label htmlFor="email">Email address</Label>
                    <Input id="email" type="email" value={data.email} autoComplete="username" autoFocus onChange={(e) => setData('email', e.target.value)} />
                    <FormError message={errors.email} />
                </div>

                <div className="space-y-2">
                    <div className="flex items-center justify-between gap-3">
                        <Label htmlFor="password">Password</Label>
                        <Link href="/forgot-password" className="text-sm font-medium text-primary hover:underline">
                            Forgot password?
                        </Link>
                    </div>
                    <Input id="password" type="password" value={data.password} autoComplete="current-password" onChange={(e) => setData('password', e.target.value)} />
                    <FormError message={errors.password} />
                </div>

                <label className="flex items-center gap-3 text-sm">
                    <input
                        type="checkbox"
                        checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)}
                        className="rounded border-input text-primary focus:ring-primary"
                    />
                    Remember this device
                </label>

                <Button className="w-full" disabled={processing}>
                    {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : <LogIn className="h-4 w-4" />}
                    Sign in
                </Button>
            </form>
        </AuthLayout>
    );
}
