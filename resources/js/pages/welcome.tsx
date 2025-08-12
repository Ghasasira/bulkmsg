import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Welcome" />
            <div className="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900">
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 flex flex-col items-center space-y-6">
                    {/* Logo placeholder */}
                    <div className="w-24 h-24 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center text-lg font-bold text-gray-700 dark:text-gray-200">
                        Logo
                    </div>

                    {/* Buttons */}
                    {auth.user ? (
                        <Link
                            href={route('dashboard')}
                            className="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md"
                        >
                            Dashboard
                        </Link>
                    ) : (
                        <div className="flex space-x-4">
                            <Link
                                href={route('login')}
                                className="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md"
                            >
                                Log in
                            </Link>
                            <Link
                                href={route('register')}
                                className="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-md"
                            >
                                Register
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
