import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Edit, Trash2, Activity } from 'lucide-react';

export default function Index({ auth, planos, flash }) {
    const { delete: destroy } = useForm();

    const handleDelete = (id) => {
        if (confirm('Tem certeza que deseja excluir este Plano de Ação?')) {
            destroy(route('planos-acao.destroy', id));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Planos de Ação</h2>}
        >
            <Head title="Planos de Ação" />

            <div className="py-12">
                <div className="w-full sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Header Actions */}
                    <div className="flex justify-between items-center bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/50">
                        <div className="flex items-center gap-3">
                            <div className="p-3 bg-blue-50 dark:bg-blue-500/10 rounded-lg">
                                <Activity className="w-6 h-6 text-blue-500" />
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-slate-900 dark:text-white">Gerenciamento de Planos de Ação (PA)</h3>
                                <p className="text-sm text-slate-500 dark:text-slate-400">Acompanhe as ações preventivas e corretivas.</p>
                            </div>
                        </div>
                        <Link 
                            href={route('planos-acao.create')} 
                            className="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl transition-colors font-medium text-sm"
                        >
                            <Plus className="w-4 h-4" /> Novo PA
                        </Link>
                    </div>

                    {/* Messages */}
                    {flash?.message && (
                        <div className="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-xl">
                            {flash.message}
                        </div>
                    )}

                    {/* Table */}
                    <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left text-slate-500 dark:text-slate-400">
                                <thead className="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-700/50 dark:text-slate-300">
                                    <tr>
                                        <th scope="col" className="px-6 py-4">O que aconteceu?</th>
                                        <th scope="col" className="px-6 py-4">Onde ocorreu?</th>
                                        <th scope="col" className="px-6 py-4">Prazo</th>
                                        <th scope="col" className="px-6 py-4">Status</th>
                                        <th scope="col" className="px-6 py-4 text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {planos.data.length > 0 ? planos.data.map((plano) => (
                                        <tr key={plano.id} className="border-b dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                            <td className="px-6 py-4 font-medium text-slate-900 dark:text-white">
                                                {plano.o_q_aconteceu ? plano.o_q_aconteceu.substring(0, 50) + '...' : ''}
                                            </td>
                                            <td className="px-6 py-4">{plano.onde_ocorreu}</td>
                                            <td className="px-6 py-4">{plano.dt_prazo ? new Date(plano.dt_prazo).toLocaleDateString('pt-BR') : '-'}</td>
                                            <td className="px-6 py-4">
                                                <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                    plano.status === 'Concluído' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' 
                                                    : 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400'
                                                }`}>
                                                    {plano.status || 'Aberto'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 flex justify-end gap-3">
                                                <Link 
                                                    href={route('planos-acao.edit', plano.id)}
                                                    className="text-blue-500 hover:text-blue-700 transition-colors p-2 hover:bg-blue-50 rounded-lg"
                                                    title="Editar"
                                                >
                                                    <Edit className="w-4 h-4" />
                                                </Link>
                                                <button 
                                                    onClick={() => handleDelete(plano.id)}
                                                    className="text-rose-500 hover:text-rose-700 transition-colors p-2 hover:bg-rose-50 rounded-lg"
                                                    title="Excluir"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    )) : (
                                        <tr>
                                            <td colSpan="5" className="px-6 py-8 text-center text-slate-500">
                                                Nenhum Plano de Ação encontrado.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        {/* Pagination */}
                        <div className="px-6 py-4 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
                            <span className="text-sm text-slate-500">Mostrando página {planos.current_page} de {planos.last_page}</span>
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
