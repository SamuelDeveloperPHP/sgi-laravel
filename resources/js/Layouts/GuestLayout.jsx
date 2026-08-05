import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children, wide = false }) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-slate-50 pt-6 sm:justify-center sm:pt-0 dark:bg-slate-900 selection:bg-teal-500 selection:text-white">
            <div className="mb-6">
                <Link href="/">
                    <ApplicationLogo className="hover:scale-105 transition-transform" />
                </Link>
            </div>

            <div className={`mt-6 w-full overflow-hidden bg-white px-6 py-8 shadow-xl sm:rounded-2xl dark:bg-slate-800 border border-slate-100 dark:border-slate-700 relative ${wide ? 'sm:max-w-4xl' : 'sm:max-w-md'}`}>
                <div className="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-teal-500 to-emerald-500"></div>
                {children}
            </div>
        </div>
    );
}
