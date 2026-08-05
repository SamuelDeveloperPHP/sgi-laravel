import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, Eye, Search, Grid, CheckCircle, Clock, XCircle } from 'lucide-react';
import { format } from 'date-fns';
import { ptBR } from 'date-fns/locale';

export default function Index({ auth, analises, filters, companies, currentCompanyId }) {
    const isMasterAdmin = auth.user.is_master_admin;
    const [search, setSearch] = useState(filters.search || '');

    const handleDelete = (id) => {
        if (confirm('Tem certeza que deseja excluir esta análise SWOT de forma permanente?')) {
            router.delete(route('analise-swot.destroy', id));
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('analise-swot.index'), { search, company_id: currentCompanyId });
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'approved':
                return (
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                        <CheckCircle className="w-3.5 h-3.5" />
                        Aprovado
                    </span>
                );
            case 'pending_approval':
                return (
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                        <Clock className="w-3.5 h-3.5" />
                        Aguardando Aprovação
                    </span>
                );
            case 'rejected':
                return (
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400">
                        <XCircle className="w-3.5 h-3.5" />
                        Rejeitado
                    </span>
                );
            default:
                return (
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-800 dark:bg-slate-700/50 dark:text-slate-300">
                        <Edit className="w-3.5 h-3.5" />
                        Rascunho
                    </span>
                );
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Análise SWOT</h2>}
        >
            <Head title="Análise SWOT" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    
                    {/* Filtro de Empresa para Master Admin */}
                    {isMasterAdmin && (
                        <div className="mb-6 bg-white dark:bg-slate-800 p-5 rounded-xl border border-slate-100 dark:border-slate-700 shadow-sm">
                            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">
                                Filtrar por Empresa
                            </label>
                            <select
                                className="w-full sm:w-1/3 rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                value={currentCompanyId}
                                onChange={(e) => router.get(route('analise-swot.index'), { company_id: e.target.value, search })}
                            >
                                <option value="">Selecione uma Empresa</option>
                                {companies.map(company => (
                                    <option key={company.id} value={company.id}>{company.nome_fantasia}</option>
                                ))}
                            </select>
                        </div>
                    )}

                    <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm rounded-xl border border-slate-100 dark:border-slate-700">
                        <div className="p-6">
                            
                            {/* Top Bar (Busca + Novo) */}
                            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                                <form onSubmit={handleSearch} className="flex w-full md:w-1/2">
                                    <div className="relative w-full">
                                        <input
                                            type="text"
                                            placeholder="Buscar análise por título..."
                                            className="w-full pl-4 pr-12 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm transition-all duration-200"
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                        />
                                        <button type="submit" className="absolute right-2.5 top-1/2 -translate-y-1/2 p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                                            <Search className="w-4.5 h-4.5" />
                                        </button>
                                    </div>
                                </form>

                                <div className="flex gap-2 w-full md:w-auto">
                                    <Link
                                        href={route('analise-swot.create', { company_id: currentCompanyId })}
                                        className="flex items-center justify-center w-full md:w-auto px-5 py-2.5 bg-indigo-600 dark:bg-indigo-500 text-white rounded-lg font-semibold text-sm hover:bg-indigo-700 dark:hover:bg-indigo-600 shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 transition-all duration-200"
                                    >
                                        <Plus className="w-4 h-4 mr-2" />
                                        Nova Análise SWOT
                                    </Link>
                                </div>
                            </div>

                            {/* Tabela de SWOT */}
                            <div className="overflow-x-auto rounded-lg border border-slate-100 dark:border-slate-700">
                                <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                    <thead className="bg-slate-50 dark:bg-slate-900/40">
                                        <tr>
                                            <th className="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Título</th>
                                            <th className="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Data Análise</th>
                                            <th className="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Aprovador</th>
                                            <th className="px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                            <th className="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-slate-200 dark:bg-slate-800 dark:divide-slate-700">
                                        {analises.data.length > 0 ? (
                                            analises.data.map(analise => (
                                                <tr key={analise.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-900/10 transition-colors">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                                            <Grid className="w-4 h-4 text-indigo-500" />
                                                            {analise.titulo}
                                                        </div>
                                                        {isMasterAdmin && (
                                                            <div className="text-xs text-slate-400 mt-0.5 ml-6">{analise.company?.nome_fantasia || 'Sem empresa'}</div>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                                        {analise.data_analise ? format(new Date(analise.data_analise), 'dd/MM/yyyy', { locale: ptBR }) : '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                                        {analise.aprovador?.name || 'Não atribuído'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-center">
                                                        {getStatusBadge(analise.status)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <div className="flex justify-end gap-2">
                                                            <Link
                                                                href={route('analise-swot.show', analise.id)}
                                                                className="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded transition-colors"
                                                                title="Visualizar Matriz"
                                                            >
                                                                <Eye className="w-4 h-4" />
                                                            </Link>
                                                            
                                                            {(analise.status === 'draft' || analise.status === 'rejected') && (
                                                                <Link
                                                                    href={route('analise-swot.edit', analise.id)}
                                                                    className="p-1.5 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded transition-colors"
                                                                    title="Editar"
                                                                >
                                                                    <Edit className="w-4 h-4" />
                                                                </Link>
                                                            )}
                                                            
                                                            <button
                                                                onClick={() => handleDelete(analise.id)}
                                                                className="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded transition-colors"
                                                                title="Excluir"
                                                            >
                                                                <Trash2 className="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan="5" className="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                                    Nenhuma análise SWOT cadastrada.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Paginação */}
                            {analises.links && analises.links.length > 3 && (
                                <div className="mt-6 flex justify-center gap-1">
                                    {analises.links.map((link, i) => (
                                        <button
                                            key={i}
                                            disabled={!link.url}
                                            onClick={() => router.get(link.url, { search, company_id: currentCompanyId })}
                                            className={`px-3 py-1.5 rounded text-sm transition-colors ${
                                                link.active
                                                    ? 'bg-indigo-600 text-white font-semibold'
                                                    : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            )}

                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
