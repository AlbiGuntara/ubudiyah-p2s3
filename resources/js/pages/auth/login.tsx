import { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from '@/components/ui/card';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        username: '',
        password: '',
        remember: false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <>
            <Head title="Login" />
            <div
                className="flex min-h-screen items-center justify-center bg-cover bg-center bg-no-repeat p-4"
                style={{ backgroundImage: "url('/bg/login.png')" }}
            >
                <Card className="w-full max-w-md">
                    <CardHeader className="text-center">
                        <div className="mx-auto mb-4 flex h-20 w-20 items-center justify-center">
                            <img
                                src="/logo/p2s3.png"
                                alt="Logo"
                                className="h-full w-full object-contain"
                            />
                        </div>
                        <CardTitle className="text-2xl">
                            Selamat Datang
                        </CardTitle>
                        <CardDescription>
                            Sistem Manajemen Pelanggaran Ubudiyah
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="space-y-2">
                                <label
                                    htmlFor="username"
                                    className="text-sm font-medium"
                                >
                                    Username
                                </label>
                                <Input
                                    id="username"
                                    type="text"
                                    value={data.username}
                                    onChange={(e) =>
                                        setData('username', e.target.value)
                                    }
                                    placeholder="Masukkan username"
                                    required
                                />
                                {errors.username && (
                                    <p className="text-sm text-red-500">
                                        {errors.username}
                                    </p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <label
                                    htmlFor="password"
                                    className="text-sm font-medium"
                                >
                                    Password
                                </label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) =>
                                        setData('password', e.target.value)
                                    }
                                    placeholder="Masukkan password"
                                    required
                                />
                            </div>
                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="remember"
                                    checked={data.remember}
                                    onChange={(e) =>
                                        setData('remember', e.target.checked)
                                    }
                                    className="rounded border-gray-300"
                                />
                                <label
                                    htmlFor="remember"
                                    className="text-sm text-muted-foreground"
                                >
                                    Ingat saya
                                </label>
                            </div>
                            <Button
                                type="submit"
                                className="w-full"
                                loading={processing}
                            >
                                Masuk
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
