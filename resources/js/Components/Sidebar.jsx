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
    Award,
    Blocks,
    Plus,
    FolderOpen,
    ClipboardList,
    GraduationCap,
    BookOpen,
    MapPin,
    Presentation,
    UserCheck
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
    Award,
    Blocks,
    FolderOpen,
    GraduationCap,
    UserCheck
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
    const hasFmAccess = auth.user?.is_master_admin || auth.fm_empresa_access;

    return (
        <>
            {/* Mobile overlay */}
            <div 
                className={`fixed inset-0 bg-slate-900/80 z-40 lg:hidden print:hidden transition-opacity duration-300 ease-in-out ${isOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'}`} 
                onClick={() => setIsOpen(false)}
            />

            {/* Sidebar container */}
            <div className={`fixed inset-y-0 left-0 z-50 w-64 bg-gentelella-sidebar border-r border-slate-800 print:hidden transform transition-transform duration-300 ease-in-out lg:translate-x-0 ${isOpen ? 'translate-x-0' : '-translate-x-full'}`}>
                
                {/* Logo area */}
                <div className="flex h-16 shrink-0 items-center justify-between px-6 border-b border-white/10">
                    <Link href="/" className="flex items-center gap-2 group">
                        <ApplicationLogo className="block h-8 w-auto fill-current text-white transition-transform group-hover:scale-105" />
                        <span className="font-bold text-xl text-[#ECF0F1] tracking-tight">Meusgi</span>
                    </Link>
                    <button onClick={() => setIsOpen(false)} className="lg:hidden text-slate-400 hover:text-white">
                        <X className="h-6 w-6" />
                    </button>
                </div>

                {/* Navigation Links */}
                <nav className="flex flex-1 flex-col overflow-y-auto space-y-1 h-[calc(100vh-4rem-4rem)]">
                    {navigation.map((item) => {
                        // Se for um item com dropdown
                        if (item.children && item.children.length > 0) {
                            return <NavGroup key={item.name} item={item} />;
                        }

                        const isActive = item.href ? (route().current(item.href + '.*') || route().current(item.href)) : false;
                        
                        return (
                            <div className="" key={item.name}>
                                <Link
                                    href={route(item.href)}
                                    className={`
                                        group flex items-center gap-x-3 p-3 text-sm leading-6 font-medium transition-all duration-200 border-l-[4px]
                                        ${isActive 
                                            ? 'border-[#1ABB9C] bg-gentelella-sidebarHover text-white shadow-[inset_-5px_0_10px_rgba(0,0,0,0.1)]' 
                                            : 'border-transparent text-[#E7E7E7] hover:border-transparent hover:bg-gentelella-sidebarHover hover:text-[#1ABB9C]'}
                                    `}
                                >
                                    <DynamicIcon 
                                        iconName={item.icon}
                                        className={`h-5 w-5 shrink-0 transition-colors duration-200 ${isActive ? 'text-[#1ABB9C]' : 'text-[#E7E7E7] group-hover:text-[#1ABB9C]'}`} 
                                        aria-hidden="true" 
                                    />
                                    {item.name}
                                </Link>
                            </div>
                        );
                    })}

                    {/* ── Gerenciador de Arquivos ────────────────────────── */}
                    {hasFmAccess && (() => {
                        const isActive = route().current('file-manager.*');
                        return (
                            <div className="mt-auto pt-2 border-t border-white/10">
                                <p className="px-3 pt-2 pb-1 text-[10px] font-semibold uppercase tracking-widest text-[#E7E7E7]/40">Arquivos</p>
                                <Link
                                    href={route('file-manager.index')}
                                    className={`
                                        group flex items-center gap-x-3 p-3 text-sm leading-6 font-medium transition-all duration-200 border-l-[4px]
                                        ${isActive
                                            ? 'border-[#1ABB9C] bg-gentelella-sidebarHover text-white shadow-[inset_-5px_0_10px_rgba(0,0,0,0.1)]'
                                            : 'border-transparent text-[#E7E7E7] hover:border-transparent hover:bg-gentelella-sidebarHover hover:text-[#1ABB9C]'}
                                    `}
                                >
                                    <FolderOpen
                                        className={`h-5 w-5 shrink-0 transition-colors duration-200 ${isActive ? 'text-[#1ABB9C]' : 'text-[#E7E7E7] group-hover:text-[#1ABB9C]'}`}
                                        aria-hidden="true"
                                    />
                                    Gerenciar Arquivos
                                </Link>
                            </div>
                        );
                    })()}

                    {/* ── Recursos Humanos & Departamento Pessoal (Master Admin Only) ────────────── */}
                    {auth.user?.is_master_admin && (() => {
                        return (
                            <div className="mt-2 pt-2 border-t border-white/10 mb-4">
                                <p className="px-3 pt-2 pb-1 text-[10px] font-semibold uppercase tracking-widest text-[#E7E7E7]/40">RH / DP</p>
                                <NavGroup item={{
                                    name: 'Recursos Humanos',
                                    icon: 'Users',
                                    children: [
                                        { name: 'Dashboard', href: 'admin.hr.dashboard', permission: null },
                                        { name: 'Áreas', href: 'admin.hr.areas.index', permission: null },
                                        { name: 'Cargos', href: 'admin.hr.cargos.index', permission: null },
                                        { name: 'Funcionários', href: 'admin.funcionarios.index', permission: null },
                                    ]
                                }} />
                                <NavGroup item={{
                                    name: 'Departamento Pessoal',
                                    icon: 'Briefcase',
                                    children: [
                                        { name: 'Benefícios', href: 'admin.hr.beneficios.index', permission: null },
                                        { name: 'Controle de Férias', href: 'admin.ferias.index', permission: null },
                                        { name: 'Folha de Pagamento', href: 'admin.hr.folha-pagamento.index', permission: null },
                                    ]
                                }} />
                                <NavGroup item={{
                                    name: 'Treinamentos',
                                    icon: 'GraduationCap',
                                    children: [
                                        { name: 'Dashboard', href: 'treinamentos.dashboard', permission: null },
                                        { name: 'Agenda (Turmas)', href: 'treinamentos.index', permission: null },
                                        { name: 'Cursos', href: 'treinamentos-cursos.index', permission: null },
                                        { name: 'Locais', href: 'treinamentos-locais.index', permission: null },
                                        { name: 'Metas', href: 'treinamentos-metas.index', permission: null },
                                        { name: 'Relatórios', href: 'treinamentos.relatorios', permission: null },
                                    ]
                                }} />
                                <NavGroup item={{
                                    name: 'Recrutamento & Seleção',
                                    icon: 'UserCheck',
                                    children: [
                                        { name: 'Dashboard', href: 'processos-seletivos-dashboard.index', permission: null },
                                        { name: 'Vagas', href: 'processos-seletivos.index', permission: null },
                                    ]
                                }} />
                            </div>
                        );
                    })()}
                </nav>

                {/* Footer (Theme Toggle) */}
                <div className="absolute bottom-0 w-full p-4 bg-gentelella-sidebarHover border-t border-white/5">
                    <button
                        onClick={toggleDarkMode}
                        className="flex w-full items-center justify-center gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium text-[#E7E7E7] hover:text-white transition-all duration-200"
                    >
                        {isDarkMode ? (
                            <><Sun className="h-5 w-5 text-amber-500" /></>
                        ) : (
                            <><Moon className="h-5 w-5 text-indigo-400" /></>
                        )}
                    </button>
                </div>
            </div>
        </>
    );
}

// Sub-componente para os menus dropdown
// Visual no padrao Gentelella v4
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

    const highlighted = isAnyChildActive || isOpen;

    return (
        <div className="">
            <button
                onClick={() => setIsOpen(!isOpen)}
                className={`
                    group w-full flex items-center justify-between gap-x-3 p-3 text-sm leading-6 font-medium transition-all duration-200 border-l-[4px]
                    ${highlighted
                        ? 'border-[#1ABB9C] bg-gentelella-sidebarHover text-white shadow-[inset_-5px_0_10px_rgba(0,0,0,0.1)]'
                        : 'border-transparent text-[#E7E7E7] hover:border-transparent hover:bg-gentelella-sidebarHover hover:text-[#1ABB9C]'
                    }
                `}
            >
                <div className="flex items-center gap-x-3">
                    <DynamicIcon
                        iconName={item.icon}
                        className={`h-5 w-5 shrink-0 transition-colors duration-200 ${highlighted ? 'text-[#1ABB9C]' : 'text-[#E7E7E7] group-hover:text-[#1ABB9C]'}`}
                        aria-hidden="true"
                    />
                    {item.name}
                </div>
                {isOpen ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
            </button>

            {/* Sub-itens expandidos com estilo de lista do Gentelella */}
            {isOpen && (
                <ul className="relative ml-7 mt-1 mb-2 space-y-1 before:absolute before:left-0 before:top-0 before:bottom-4 before:w-px before:bg-[#E7E7E7]/30">
                    {item.children.map((child) => {
                        const isChildActive = child.href ? (route().current(child.href + '.*') || route().current(child.href)) : false;
                        const createRoute = child.href ? child.href.replace('.index', '.create') : null;
                        const hasCreateRoute = createRoute && route().has(createRoute);

                        return (
                            <li key={child.name} className="relative group/child flex items-center justify-between">
                                <Link
                                    href={route(child.href)}
                                    className={`
                                        flex-1 flex items-center gap-x-3 pl-6 pr-2 py-1.5 text-[13px] font-medium transition-all duration-200 relative
                                        before:absolute before:left-0 before:top-1/2 before:-mt-px before:w-3 before:h-px before:bg-[#E7E7E7]/30
                                        ${isChildActive
                                            ? 'text-white font-bold'
                                            : 'text-[#E7E7E7]/70 hover:text-white'}
                                    `}
                                >
                                    {child.name}
                                </Link>
                                
                                {hasCreateRoute && (
                                    <Link
                                        href={route(createRoute)}
                                        className="opacity-0 group-hover/child:opacity-100 p-1 mr-2 rounded text-[#E7E7E7]/50 hover:text-white hover:bg-white/10 transition-all duration-200"
                                        title={`Cadastrar ${child.name}`}
                                        onClick={(e) => e.stopPropagation()}
                                    >
                                        <Plus className="w-3 h-3" />
                                    </Link>
                                )}
                            </li>
                        );
                    })}
                </ul>
            )}
        </div>
    );
}
