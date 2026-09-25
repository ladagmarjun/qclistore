import { Head, useForm } from '@inertiajs/react';
import { Field } from '@/components/ui';
import { TopBar } from '@/layouts/admin-layout';

export default function Login() {
    const form = useForm({ email: '', password: '', remember: false });

    return (
        <>
            <Head title="Admin Login" />
            <TopBar />
            <div className="login-wrap">
                <div className="login">
                    <h1>Admin Login</h1>
                    <p>Sign in with an administrator account.</p>
                    <form
                        noValidate
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.post('/admin/login', { onFinish: () => form.reset('password') });
                        }}
                    >
                        {form.errors.email && <div className="form-error">{form.errors.email}</div>}
                        <Field label="Email" htmlFor="email">
                            <input
                                id="email"
                                type="email"
                                autoComplete="username"
                                autoFocus
                                required
                                value={form.data.email}
                                onChange={(e) => form.setData('email', e.target.value)}
                            />
                        </Field>
                        <Field label="Password" htmlFor="password" error={form.errors.password}>
                            <input
                                id="password"
                                type="password"
                                autoComplete="current-password"
                                required
                                value={form.data.password}
                                onChange={(e) => form.setData('password', e.target.value)}
                            />
                        </Field>
                        <div className="field">
                            <label className="check">
                                <input type="checkbox" checked={form.data.remember} onChange={(e) => form.setData('remember', e.target.checked)} />
                                Remember me
                            </label>
                        </div>
                        <button className="btn" type="submit" disabled={form.processing}>
                            Sign in
                        </button>
                    </form>
                </div>
            </div>
        </>
    );
}
