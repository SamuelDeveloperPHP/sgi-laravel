import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, FileText, Download, Search } from 'lucide-react';
import { format } from 'date-fns';

export default function Index({ auth, calibracoes, filters, companies, currentCompanyId }) {
    const isMasterAdmin = auth.user.is_master_admin;
    const [search, setSearch] = useState(filters.search || '');

    const handleDelete = (id) => {
        if (confirm('Tem certeza que deseja excluir este equipamento e todos os seus arquivos?')) {
            router.delete(route('controle-calibracoes.destroy', id));
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('controle-calibracoes.index'), { search, company_id: currentCompanyId });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Controle de Calibrações</h2>}
        >
            <Head title="Controle de Calibrações" />

            <div className="py-12">
                <div className="max-w-[95%] mx-auto sm:px-6 lg:px-8">
                    
                    {isMasterAdmin && (
                        <div className="mb-6 bg-white dark:bg-slate-800 p-4 rounded-lg shadow">
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Filtrar por Empresa
                            </label>
                            <select
                                className="w-full sm:w-1/3 rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={currentCompanyId}
                                onChange={(e) => router.get(route('controle-calibracoes.index'), { company_id: e.target.value, search })}
                            >
                                <option value="">Selecione uma Empresa</option>
                                {companies.map(company => (
                                    <option key={company.id} value={company.id}>{company.nome_fantasia}</option>
                                ))}
                            </select>
                        </div>
                    )}

                    <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                                <form onSubmit={handleSearch} className="flex w-full md:w-1/2">
                                    <input
                                        type="text"
                                        placeholder="Pesquisar equipamento..."
                                        className="w-full rounded-l-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                    />
                                    <button type="submit" className="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-r-md font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">
                                        <Search className="w-4 h-4" />
                                    </button>
                                </form>

                                <div className="flex gap-2">
                                    <a
                                        href={route('controle-calibracoes.pdf', { company_id: currentCompanyId })}
                                        target="_blank"
                                        className="flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition"
                                    >
                                        <FileText className="w-4 h-4 mr-2" />
                                        Exportar Planilha (PDF)
                                    </a>

                                    {auth.user.permissions?.includes('manage-controle-calibracoes') && (
                                        <Link
                                            href={route('controle-calibracoes.create', { company_id: currentCompanyId })}
                                            className="flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition"
                                        >
                                            <Plus className="w-4 h-4 mr-2" />
                                            Novo Equipamento
                                        </Link>
                                    )}
                                </div>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-slate-700">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Equipamento / Local</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Identificação</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Última Calib.</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status / Próxima</th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200 dark:bg-slate-800 dark:divide-gray-700">
                                        {calibracoes.data.length > 0 ? (
                                            calibracoes.data.map((item) => (
                                                <tr key={item.id}>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        <div className="font-bold">{item.equipamento}</div>
                                                        <div className="text-xs text-gray-500">{item.local}</div>
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                        {item.identificacao}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                        {item.data_ultima_calibracao ? format(new Date(item.data_ultima_calibracao), 'dd/MM/yyyy') : '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                        <div className={`font-bold ${
                                                            item.status_cor === 'red' ? 'text-red-600 dark:text-red-400' :
                                                            item.status_cor === 'yellow' ? 'text-yellow-600 dark:text-yellow-400' :
                                                            item.status_cor === 'green' ? 'text-green-600 dark:text-green-400' :
                                                            'text-gray-500'
                                                        }`}>
                                                            {item.status_prazo}
                                                        </div>
                                                        <div className="text-xs text-gray-500">
                                                            {item.data_proxima_calibracao ? format(new Date(item.data_proxima_calibracao), 'dd/MM/yyyy') : '-'}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <div className="flex items-center justify-end gap-3">
                                                            {item.arquivo_certificado && (
                                                                <a href={route('controle-calibracoes.download', item.id)} className="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300" title="Baixar Certificado">
                                                                    <Download className="w-5 h-5" />
                                                                </a>
                                                            )}
                                                            {auth.user.permissions?.includes('manage-controle-calibracoes') && (
                                                                <>
                                                                    <Link href={route('controle-calibracoes.edit', item.id)} className="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300" title="Editar">
                                                                        <Edit className="w-5 h-5" />
                                                                    </Link>
                                                                    <button onClick={() => handleDelete(item.id)} className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" title="Excluir">
                                                                        <Trash2 className="w-5 h-5" />
                                                                    </button>
                                                                </>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan="5" className="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                                    Nenhum equipamento cadastrado.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Paginação */}
                            {calibracoes.links && calibracoes.links.length > 3 && (
                                <div className="mt-6 flex justify-center">
                                    <div className="flex flex-wrap gap-1">
                                        {calibracoes.links.map((link, k) => (
                                            <Link
                                                key={k}
                                                href={link.url}
                                                className={`px-4 py-2 border rounded text-sm ${
                                                    link.active
                                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-slate-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-slate-700'
                                                } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                onClick={(e) => !link.url && e.preventDefault()}
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}

                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
