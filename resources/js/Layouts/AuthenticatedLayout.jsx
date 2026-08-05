import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import Dropdown from '@/Components/Dropdown';
import Sidebar from '@/Components/Sidebar';
import Swal from 'sweetalert2';

export default function AuthenticatedLayout({ header, children, fullWidth = false }) {
    const { auth, flash } = usePage().props;
    const user = auth.user;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    
    // Dark mode state
    const [isDarkMode, setIsDarkMode] = useState(() => {
        if (typeof window !== 'undefined') {
            const saved = localStorage.getItem('theme');
            if (saved) return saved === 'dark';
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        return false;
    });

    useEffect(() => {
        if (isDarkMode) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
    }, [isDarkMode]);

    useEffect(() => {
        if (flash?.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: flash.success,
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                background: isDarkMode ? '#1f2937' : '#ffffff',
                color: isDarkMode ? '#f3f4f6' : '#111827',
                iconColor: '#10b981'
            });
        }
        if (flash?.error) {
            Swal.fire({
                icon: 'error',
                title: 'Atenção',
                text: flash.error,
                confirmButtonColor: '#ef4444',
                background: isDarkMode ? '#1f2937' : '#ffffff',
                color: isDarkMode ? '#f3f4f6' : '#111827',
            });
        }
        if (flash?.warning) {
            Swal.fire({
                icon: 'warning',
                title: 'Aviso',
                text: flash.warning,
                confirmButtonColor: '#f59e0b',
                background: isDarkMode ? '#1f2937' : '#ffffff',
                color: isDarkMode ? '#f3f4f6' : '#111827',
            });
        }
    }, [flash, isDarkMode]);

    const toggleDarkMode = () => setIsDarkMode(!isDarkMode);

    return (
        <div className="min-h-screen bg-gentelella-bg dark:bg-slate-900 transition-colors duration-300 relative font-sans text-[#73879C] dark:text-slate-300">
            <div className="relative z-10 flex min-h-screen">
                {/* Sidebar */}
                <Sidebar 
                    isOpen={sidebarOpen} 
                    setIsOpen={setSidebarOpen} 
                    isDarkMode={isDarkMode}
                    toggleDarkMode={toggleDarkMode}
                />

                {/* Main content area */}
                <div className="flex-1 flex flex-col min-w-0 lg:pl-64 print:pl-0">
                    
                    {/* Top Header */}
                    <header className="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gentelella-border dark:border-slate-700 bg-white dark:bg-slate-800 px-4 shadow-none sm:gap-x-6 sm:px-6 lg:px-8 print:hidden transition-all duration-300">
                    <button
                        type="button"
                        className="-m-2.5 p-2.5 text-[#73879C] lg:hidden dark:text-gray-200"
                        onClick={() => setSidebarOpen(true)}
                    >
                        <span className="sr-only">Abrir sidebar</span>
                        <Menu className="h-6 w-6" aria-hidden="true" />
                    </button>

                    {/* Separator for mobile */}
                    <div className="h-6 w-px bg-gray-200 dark:bg-gray-700 lg:hidden" aria-hidden="true" />

                    {/* Page Header (Breadcrumbs/Title) in Navbar */}
                    {header && (
                        <div className="flex items-center">
                            {header}
                        </div>
                    )}

                    <div className="flex flex-1 gap-x-4 self-stretch lg:gap-x-6 justify-end">
                        <div className="flex items-center gap-x-4 lg:gap-x-6">
                            
                            {/* Notificações */}
                            <Dropdown>
                                <Dropdown.Trigger>
                                    <button type="button" className="relative p-2 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                        <span className="sr-only">Ver notificações</span>
                                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                        </svg>
                                        {usePage().props.auth.notifications?.length > 0 && (
                                            <span className="absolute top-1 right-1 flex h-3 w-3">
                                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                                <span className="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                                            </span>
                                        )}
                                    </button>
                                </Dropdown.Trigger>
                                <Dropdown.Content align="right" width="w-80">
                                    <div className="block px-4 py-2 font-medium text-sm text-gray-700 dark:text-gray-200 border-b border-gray-100 dark:border-gray-700">
                                        Notificações
                                    </div>
                                    {usePage().props.auth.notifications?.length > 0 ? (
                                        <div className="max-h-64 overflow-y-auto">
                                            {usePage().props.auth.notifications.map(notification => (
                                                <div key={notification.id} className="px-4 py-3 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                                    <p className="text-sm font-semibold text-gray-800 dark:text-white">{notification.data.titulo}</p>
                                                    <p className="text-xs text-gray-600 dark:text-gray-400 mt-1">{notification.data.mensagem}</p>
                                                    <div className="mt-2 flex justify-between items-center">
                                                        <a href={notification.data.url} className="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">Ver Detalhes</a>
                                                        <button 
                                                            onClick={(e) => {
                                                                e.preventDefault();
                                                                import('@inertiajs/react').then(({ router }) => {
                                                                    router.post(route('notifications.read', notification.id), {}, { preserveScroll: true });
                                                                });
                                                            }}
                                                            className="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                                        >
                                                            Marcar como lida
                                                        </button>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center">
                                            Nenhuma notificação nova.
                                        </div>
                                    )}
                                </Dropdown.Content>
                            </Dropdown>

                            {/* Profile dropdown */}
                            <Dropdown>
                                <Dropdown.Trigger>
                                    <span className="inline-flex rounded-md">
                                        <button
                                            type="button"
                                            className="inline-flex items-center rounded-md border border-transparent bg-transparent px-3 py-2 text-sm font-medium leading-4 text-slate-600 transition duration-150 ease-in-out hover:text-slate-900 hover:bg-slate-100/50 focus:outline-none dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800/50"
                                        >
                                            {user.name}
                                            <svg className="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>
                                </Dropdown.Trigger>

                                <Dropdown.Content>
                                    <div className="block px-4 py-2 text-xs text-gray-400 dark:text-gray-500">
                                        {user.roles?.[0] || 'Sem Perfil'}
                                    </div>
                                    <Dropdown.Link href={route('profile.edit')}>Perfil</Dropdown.Link>
                                    <Dropdown.Link href={route('logout')} method="post" as="button">
                                        Sair
                                    </Dropdown.Link>
                                </Dropdown.Content>
                            </Dropdown>
                        </div>
                    </div>
                </header>

                {/* Main Content */}
                <main className="flex-1 animate-fade-in">
                    {children}
                </main>
            </div>
            </div>
        </div>
    );
}
