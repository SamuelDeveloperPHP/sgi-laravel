import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { 
    LayoutDashboard, 
    FileCheck, 
    AlertTriangle, 
    Target, 
    Building, 
    Users, 
    X, 
    Moon, 
    Sun,
    Briefcase,
    ChevronDown,
    ChevronRight,
    Award
} from 'lucide-react';
import ApplicationLogo from '@/Components/ApplicationLogo';

// Mapeamento estático para icones base do Lucide que estavam hardcoded
const lucideIcons = {
    LayoutDashboard,
    FileCheck,
    AlertTriangle,
    Target,
    Building,
    Users,
    Briefcase,
    Award
};

// Componente para renderizar ícones dinamicamente (Lucide ou FontAwesome/Remix)
const DynamicIcon = ({ iconName, className = "h-5 w-5 shrink-0" }) => {
    if (!iconName) return <div className={className} />;
    
    // Se for string no padrão font awesome / remix icon
    if (iconName.startsWith('fa-') || iconName.startsWith('fas ') || iconName.startsWith('ri-')) {
        return <i className={`${iconName} ${className}`} aria-hidden="true"></i>;
    }

    // Se for um ícone do Lucide (mapeado)
    const IconComponent = lucideIcons[iconName];
    if (IconComponent) {
        return <IconComponent className={className} aria-hidden="true" />;
    }

    // Fallback genérico se não achar o ícone
    return <div className={`bg-gray-200 dark:bg-gray-700 rounded-sm ${className}`} />;
};

export default function Sidebar({ isOpen, setIsOpen, isDarkMode, toggleDarkMode }) {
    const { auth, navigation = [] } = usePage().props;
    const userPermissions = auth.user?.permissions || [];

    // Helper para verificar permissão
    const can = (permission) => {
        if (!permission) return true; // Se não exigir permissão, permite
        if (auth.user?.is_master_admin) return true; // Master admin bypass
        return userPermissions.includes(permission);
    };

    return (
        <>
            {/* Mobile overlay */}
            <div 
                className={`fixed inset-0 bg-slate-900/80 z-40 lg:hidden transition-opacity duration-300 ease-in-out ${isOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'}`} 
                onClick={() => setIsOpen(false)}
            />

            {/* Sidebar container */}
            <div className={`fixed inset-y-0 left-0 z-50 w-64 bg-white/80 dark:bg-slate-900/80 backdrop-blur-2xl border-r border-slate-200/50 dark:border-slate-700/50 transform transition-transform duration-300 ease-in-out lg:translate-x-0 ${isOpen ? 'translate-x-0' : '-translate-x-full'}`}>
                
                {/* Logo area */}
                <div className="flex h-16 shrink-0 items-center justify-between px-6 border-b border-slate-200/50 dark:border-slate-700/50">
                    <Link href="/" className="flex items-center gap-2 group">
                        <ApplicationLogo className="block h-8 w-auto fill-current text-indigo-600 dark:text-indigo-400 transition-transform group-hover:scale-105" />
                        <span className="font-bold text-xl text-slate-900 dark:text-white tracking-tight">Meusgi</span>
                    </Link>
                    <button onClick={() => setIsOpen(false)} className="lg:hidden text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                        <X className="h-6 w-6" />
                    </button>
                </div>

                {/* Navigation Links */}
                <nav className="flex flex-1 flex-col overflow-y-auto px-4 py-4 space-y-1 h-[calc(100vh-4rem-4rem)]">
                    {navigation.map((item) => {
                        // Se for um item com dropdown
                        if (item.children && item.children.length > 0) {
                            // Filtra apenas sub-itens que tem permissão
                            const allowedChildren = item.children.filter(child => can(child.permission));
                            
                            // Se não sobrou nenhum filho autorizado, não exibe o grupo todo
                            if (allowedChildren.length === 0) return null;
                            
                            return <NavGroup key={item.name} item={{...item, children: allowedChildren}} />;
                        }

                        // Se for um item simples
                        if (!can(item.permission)) return null;

                        const isActive = item.href ? (route().current(item.href + '.*') || route().current(item.href)) : false;
                        
                        return (
                            <Link
                                key={item.name}
                                href={route(item.href)}
                                className={`
                                    group flex items-center gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all duration-200
                                    ${isActive 
                                        ? 'bg-indigo-50/80 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300 shadow-sm' 
                                        : 'text-slate-600 hover:text-indigo-600 hover:bg-slate-50/80 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800/50'}
                                `}
                            >
                                <DynamicIcon 
                                    iconName={item.icon}
                                    className={`mr-3 h-5 w-5 shrink-0 transition-colors duration-200 ${isActive ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 group-hover:text-indigo-600 dark:text-slate-400 dark:group-hover:text-white'}`} 
                                    aria-hidden="true" 
                                />
                                {item.name}
                            </Link>
                        );
                    })}
                </nav>

                {/* Footer (Theme Toggle) */}
                <div className="absolute bottom-0 w-full p-4 border-t border-slate-200/50 dark:border-slate-700/50 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md">
                    <button
                        onClick={toggleDarkMode}
                        className="flex w-full items-center gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium text-slate-600 hover:bg-slate-100/80 dark:text-slate-300 dark:hover:bg-slate-800/80 transition-all duration-200"
                    >
                        {isDarkMode ? (
                            <>
                                <Sun className="h-5 w-5 text-amber-500" />
                                Tema Claro
                            </>
                        ) : (
                            <>
                                <Moon className="h-5 w-5 text-indigo-500" />
                                Tema Escuro
                            </>
                        )}
                    </button>
                </div>
            </div>
        </>
    );
}

// Sub-componente para os menus dropdown
function NavGroup({ item }) {
    // Checa se alguma rota dos filhos está ativa no momento
    const isAnyChildActive = item.children.some(child => {
        if (!child.href) return false;
        return route().current(child.href + '.*') || route().current(child.href);
    });

    // O menu começa aberto se uma das sub-rotas for a página atual
    const [isOpen, setIsOpen] = useState(isAnyChildActive);

    // Se a página mudar e um filho se tornar ativo, abre automaticamente
    useEffect(() => {
        if (isAnyChildActive) setIsOpen(true);
    }, [isAnyChildActive]);

    return (
        <div className="space-y-1">
            <button
                onClick={() => setIsOpen(!isOpen)}
                className={`
                    w-full flex items-center justify-between gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all duration-200
                    ${isAnyChildActive 
                        ? 'text-indigo-700 dark:text-indigo-400' 
                        : 'text-slate-600 hover:text-indigo-600 hover:bg-slate-50/80 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800/50'}
                `}
            >
                <div className="flex items-center gap-x-3">
                    <DynamicIcon 
                        iconName={item.icon}
                        className={`mr-3 h-5 w-5 shrink-0 transition-colors duration-200 ${isAnyChildActive ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400'}`} 
                        aria-hidden="true" 
                    />
                    {item.name}
                </div>
                {isOpen ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
            </button>

            {/* Sub-itens expandidos */}
            {isOpen && (
                <div className="mt-1 space-y-1 pl-10 pr-2">
                    {item.children.map((child) => {
                        const isChildActive = child.href ? (route().current(child.href + '.*') || route().current(child.href)) : false;
                        return (
                            <Link
                                key={child.name}
                                href={route(child.href)}
                                className={`
                                    block rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200
                                    ${isChildActive 
                                        ? 'bg-indigo-50/80 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300 shadow-sm' 
                                        : 'text-slate-500 hover:text-indigo-600 hover:bg-slate-50/80 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50'}
                                `}
                            >
                                {child.name}
                            </Link>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
