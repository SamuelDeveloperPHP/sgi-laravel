import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Plus, Briefcase, Filter } from 'lucide-react';

export default function Index({ projetos }) {
    const stripHtml = (html) => {
        if (!html) return 'Nenhuma descrição fornecida para este projeto.';
        const doc = new DOMParser().parseFromString(html, 'text/html');
        return doc.body.textContent || "";
    };

    const getAvatarUrl = (name, index = 0) => {
        const colors = ['6366f1', '10b981', 'f59e0b', 'ef4444', '8b5cf6'];
        const color = colors[index % colors.length];
        return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=${color}&color=fff&size=32`;
    };

    // Helper to format date to "MMM DD" (e.g. "Apr 30")
    const formatDue = (dateString) => {
        if (!dateString) return 'TBD';
        const date = new Date(dateString + 'T12:00:00');
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    };

    const getStatusInfo = (progress) => {
        if (progress === 100) return { text: 'Concluído', colorClass: 'text-emerald-500', bgClass: 'bg-emerald-500' };
        if (progress >= 50) return { text: 'On track', colorClass: 'text-emerald-500', bgClass: 'bg-emerald-500' };
        if (progress > 20) return { text: 'At risk', colorClass: 'text-amber-500', bgClass: 'bg-amber-500' };
        if (progress > 0) return { text: 'Blocked', colorClass: 'text-red-500', bgClass: 'bg-red-500' };
        return { text: 'On hold', colorClass: 'text-amber-600', bgClass: 'bg-amber-600' };
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Projetos</h2>}
        >
            <Head title="Projetos" />

            <div className="py-8 bg-gray-50/30 min-h-screen">
                <div className="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4 border-b border-gray-200 pb-4">
                        <div>
                            <span className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1 block">Workspace</span>
                            <h2 className="text-3xl font-bold text-gray-900 dark:text-white">
                                Projects
                            </h2>
                        </div>
                        <div className="flex items-center gap-3">
                            <button className="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-50 transition-colors shadow-sm">
                                Filters
                            </button>
                            <Link
                                href={route('projetos.create')}
                                className="inline-flex items-center gap-2 rounded-md bg-emerald-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-600 transition-colors"
                            >
                                <Plus className="h-4 w-4" />
                                New project
                            </Link>
                        </div>
                    </div>

                    {/* Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        {projetos.map((projeto) => {
                            const progresso = projeto.porc_concluido || 0;
                            const status = getStatusInfo(progresso);

                            return (
                                <Link 
                                    key={projeto.id} 
                                    href={route('projetos.show', projeto.id)}
                                    className="block bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition-shadow group"
                                >
                                    {/* Top row */}
                                    <div className="flex justify-between items-start mb-1">
                                        <h3 className="text-[17px] font-bold text-gray-900 group-hover:text-emerald-600 transition-colors line-clamp-1">
                                            {projeto.nomeProjeto || 'Sem Nome'}
                                        </h3>
                                        <div className={`flex items-center gap-1.5 text-[13px] font-medium whitespace-nowrap ml-3 ${status.colorClass}`}>
                                            <div className={`w-1.5 h-1.5 rounded-full ${status.bgClass}`}></div>
                                            {status.text}
                                        </div>
                                    </div>

                                    {/* Company/Identifier */}
                                    <div className="text-[13px] text-gray-500 mb-3">
                                        {projeto.responsavel ? projeto.responsavel.name : `Projeto #${projeto.id.toString().padStart(3, '0')}`}
                                    </div>

                                    {/* Description */}
                                    <p className="text-[14px] text-gray-600 line-clamp-2 min-h-[40px] mb-6">
                                        {stripHtml(projeto.descricao)}
                                    </p>

                                    {/* Progress Area */}
                                    <div className="mb-4">
                                        <div className="flex justify-between items-end mb-1.5">
                                            <span className="text-[13px] text-gray-500">Progress</span>
                                            <span className="text-[13px] font-semibold text-gray-700">{progresso}%</span>
                                        </div>
                                        <div className="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                            <div 
                                                className={`h-full ${status.bgClass} rounded-full transition-all duration-500`}
                                                style={{ width: `${progresso}%` }}
                                            ></div>
                                        </div>
                                    </div>

                                    {/* Footer */}
                                    <div className="flex justify-between items-center mt-auto pt-2">
                                        <div className="flex -space-x-1.5">
                                            {projeto.membros?.slice(0, 4).map((membro, i) => (
                                                <img 
                                                    key={membro.id} 
                                                    className="w-7 h-7 rounded-full border-2 border-white" 
                                                    src={getAvatarUrl(membro.name, i)} 
                                                    alt={membro.name} 
                                                    title={membro.name}
                                                />
                                            ))}
                                            {(!projeto.membros || projeto.membros.length === 0) && projeto.responsavel && (
                                                <img 
                                                    className="w-7 h-7 rounded-full border-2 border-white" 
                                                    src={getAvatarUrl(projeto.responsavel.name, 0)} 
                                                    alt={projeto.responsavel.name} 
                                                    title={projeto.responsavel.name} 
                                                />
                                            )}
                                            {(!projeto.membros || projeto.membros.length === 0) && !projeto.responsavel && (
                                                <div className="w-7 h-7 rounded-full border-2 border-white bg-gray-100 flex items-center justify-center">
                                                    <span className="text-[10px] text-gray-400">?</span>
                                                </div>
                                            )}
                                        </div>
                                        <div className="text-[13px] text-gray-500">
                                            Due {formatDue(projeto.data_fim)}
                                        </div>
                                    </div>
                                </Link>
                            );
                        })}
                    </div>

                    {projetos.length === 0 && (
                        <div className="mt-8 text-center bg-white rounded-xl border border-gray-200 p-12">
                            <div className="mx-auto h-16 w-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                <Briefcase className="h-8 w-8 text-gray-400" />
                            </div>
                            <h3 className="text-lg font-bold text-gray-900 mb-1">No projects found</h3>
                            <p className="text-sm text-gray-500 mb-6">Get started by creating your first project.</p>
                            <Link
                                href={route('projetos.create')}
                                className="inline-flex items-center gap-2 rounded-md bg-emerald-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-600 transition-colors"
                            >
                                <Plus className="h-4 w-4" />
                                New project
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
