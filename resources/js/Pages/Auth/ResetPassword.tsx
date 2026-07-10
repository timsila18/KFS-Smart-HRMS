import { Head, useForm } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import AuthLayout from '@/Layouts/AuthLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { FormError } from '@/Components/FormError';

export default function ResetPassword({ token, email }: { token: string; email: string }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    return (
        <AuthLayout title="Create new password" subtitle="Choose a strong password to restore your KFS Smart HRMS access.">
            <Head title="Reset Password" />
            <form onSubmit={(e) => { e.preventDefault(); post('/reset-password', { onFinish: () => reset('password', 'password_confirmation') }); }} className="space-y-5">
                <div className="space-y-2">
                    <Label htmlFor="email">Email address</Label>
                    <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                    <FormError message={errors.email} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="password">New password</Label>
                    <Input id="password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                    <FormError message={errors.password} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="password_confirmation">Confirm password</Label>
                    <Input id="password_confirmation" type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} />
                    <FormError message={errors.password_confirmation} />
                </div>
                <Button className="w-full" disabled={processing}>
                    <KeyRound className="h-4 w-4" />
                    Reset password
                </Button>
            </form>
        </AuthLayout>
    );
}
