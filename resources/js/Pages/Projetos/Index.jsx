import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Plus, Briefcase, Calendar, CheckCircle2, MoreVertical, Clock, Users } from 'lucide-react';
import Dropdown from '@/Components/Dropdown';

export default function Index({ projetos }) {
    // Cores condicionais baseadas no progresso
    const getProgressColor = (progress) => {
        if (progress === 100) return 'bg-emerald-500';
        if (progress > 50) return 'bg-blue-500';
        if (progress > 0) return 'bg-amber-500';
        return 'bg-gray-300 dark:bg-gray-600';
    };

    const getStatusBadge = (progress) => {
        if (progress === 100) {
            return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">Concluído</span>;
        }
        return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">Em Andamento</span>;
    };

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

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Projetos</h2>}
        >
            <Head title="Projetos" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div className="flex-1 min-w-0">
                            <h2 className="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:truncate sm:text-3xl sm:tracking-tight">
                                Gestão de Projetos
                            </h2>
                            <p className="text-sm text-gray-500 mt-1">Acompanhe e gerencie todos os projetos da sua organização.</p>
                        </div>
                        <div className="flex">
                            <Link
                                href={route('projetos.create')}
                                className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                            >
                                <Plus className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
                                Criar Projeto
                            </Link>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                {projetos.map((projeto) => (
                    <div key={projeto.id} className="bg-white dark:bg-gray-800 rounded-xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-gray-100 dark:border-gray-700/50 flex flex-col transition-all hover:-translate-y-1 hover:shadow-lg">
                        <div className="p-5 flex-1 relative">
                            <div className="flex items-start justify-between mb-4">
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white line-clamp-1 mb-1">
                                        <Link href={route('projetos.show', projeto.id)} className="hover:text-indigo-600 transition-colors">
                                            {projeto.nomeProjeto || 'Sem Nome'}
                                        </Link>
                                    </h3>
                                    <div className="flex items-center gap-2 text-xs text-gray-500">
                                        <span>#PRJ-{projeto.id.toString().padStart(3, '0')}</span>
                                        <span>•</span>
                                        {getStatusBadge(projeto.porc_concluido || 0)}
                                    </div>
                                </div>
                                <div className="-mt-1 -mr-2">
                                    <Dropdown>
                                        <Dropdown.Trigger>
                                            <button className="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                                <MoreVertical className="w-4 h-4" />
                                            </button>
                                        </Dropdown.Trigger>
                                        <Dropdown.Content align="right">
                                            <Dropdown.Link href={route('projetos.show', projeto.id)}>
                                                Acessar Quadro
                                            </Dropdown.Link>
                                            <Dropdown.Link href={route('projetos.edit', projeto.id)}>
                                                Editar Projeto
                                            </Dropdown.Link>
                                            <div className="border-t border-gray-100 dark:border-gray-700"></div>
                                            <Dropdown.Link href={route('projetos.destroy', projeto.id)} method="delete" as="button" className="text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                                                Excluir
                                            </Dropdown.Link>
                                        </Dropdown.Content>
                                    </Dropdown>
                                </div>
                            </div>
                            
                            <p className="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 min-h-[2.5rem] mb-5">
                                {stripHtml(projeto.descricao)}
                            </p>

                            <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-4 border-t border-gray-50 dark:border-gray-700/50 pt-4">
                                <div className="flex items-center gap-1.5" title="Data de Entrega">
                                    <Clock className="w-4 h-4 text-gray-400" />
                                    <span className="font-medium">{projeto.data_fim ? new Date(projeto.data_fim + 'T12:00:00').toLocaleDateString('pt-BR') : 'Sem prazo'}</span>
                                </div>
                                <div className="flex -space-x-2 overflow-hidden" title="Equipe do Projeto">
                                    {projeto.membros?.slice(0, 3).map((membro, i) => (
                                        <img key={membro.id} className="inline-block h-6 w-6 rounded-full ring-2 ring-white dark:ring-gray-800" src={getAvatarUrl(membro.name, i)} alt={membro.name} title={membro.name}/>
                                    ))}
                                    {projeto.membros?.length > 3 && (
                                        <div className="inline-flex h-6 w-6 items-center justify-center rounded-full ring-2 ring-white dark:ring-gray-800 bg-gray-100 dark:bg-gray-700">
                                            <span className="text-[10px] font-medium leading-none text-gray-500 dark:text-gray-400">+{projeto.membros.length - 3}</span>
                                        </div>
                                    )}
                                    {!projeto.membros?.length && projeto.responsavel && (
                                        <img className="inline-block h-6 w-6 rounded-full ring-2 ring-white dark:ring-gray-800" src={getAvatarUrl(projeto.responsavel.name, 0)} alt={projeto.responsavel.name} title={`Líder: ${projeto.responsavel.name}`} />
                                    )}
                                    {!projeto.membros?.length && !projeto.responsavel && (
                                        <span className="text-xs text-gray-400">Sem equipe</span>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="bg-gray-50/50 dark:bg-gray-800/30 px-5 py-4 border-t border-gray-100 dark:border-gray-700/50 rounded-b-xl">
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-xs font-semibold text-gray-600 dark:text-gray-300">Progresso</span>
                                <span className="text-xs font-bold text-gray-900 dark:text-white">{projeto.porc_concluido || 0}%</span>
                            </div>
                            <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                <div 
                                    className={`${getProgressColor(projeto.porc_concluido || 0)} h-1.5 rounded-full transition-all duration-500`} 
                                    style={{ width: `${projeto.porc_concluido || 0}%` }}
                                ></div>
                            </div>
                        </div>
                    </div>
                ))}

                {projetos.length === 0 && (
                    <div className="col-span-full py-16 text-center bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 border-dashed">
                        <div className="mx-auto h-16 w-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                            <Briefcase className="h-8 w-8 text-gray-400" />
                        </div>
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-1">Nenhum projeto encontrado</h3>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-sm mx-auto">Comece criando um novo projeto para organizar suas tarefas e acompanhar o progresso da equipe.</p>
                        <Link
                            href={route('projetos.create')}
                            className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
                        >
                            <Plus className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
                            Criar Primeiro Projeto
                        </Link>
                    </div>
                )}
            </div>
            </div>
            </div>
        </AuthenticatedLayout>
    );
}
