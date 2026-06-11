import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, Eye, Mail, FileText } from 'lucide-react';
import { format } from 'date-fns';

export default function Index({ auth, atas, companies, currentCompanyId }) {
    const isMasterAdmin = auth.user.is_master_admin;

    const handleDelete = (id) => {
        if (confirm('Tem certeza que deseja excluir esta ata?')) {
            router.delete(route('atas-reuniao.destroy', id));
        }
    };

    const handleSolicitar = (id) => {
        if (confirm('Deseja enviar e-mail solicitando a assinatura de todos os participantes?')) {
            router.post(route('atas-reuniao.solicitar-assinaturas', id));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Atas de Reunião</h2>}
        >
            <Head title="Atas de Reunião" />

            <div className="py-12">
                <div className="w-full sm:px-6 lg:px-8">
                    
                    {isMasterAdmin && (
                        <div className="mb-6 bg-white dark:bg-slate-800 p-4 rounded-lg shadow">
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Filtrar por Empresa
                            </label>
                            <select
                                className="w-full sm:w-1/3 rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={currentCompanyId}
                                onChange={(e) => router.get(route('atas-reuniao.index'), { company_id: e.target.value })}
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
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Registros de Atas</h3>
                                {auth.user.permissions?.includes('manage-atas-reuniao') && (
                                    <Link
                                        href={route('atas-reuniao.create', { company_id: currentCompanyId })}
                                        className="flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition"
                                    >
                                        <Plus className="w-4 h-4 mr-2" />
                                        Nova Ata
                                    </Link>
                                )}
                            </div>

                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-slate-700">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Assunto</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Local</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                            <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Assinaturas</th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200 dark:bg-slate-800 dark:divide-gray-700">
                                        {atas.length > 0 ? (
                                            atas.map((ata) => (
                                                <tr key={ata.id}>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        {format(new Date(ata.data), 'dd/MM/yyyy')}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                        {ata.assunto}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                        {ata.local}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                            ${ata.status === 'rascunho' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : ''}
                                                            ${ata.status === 'aguardando_assinaturas' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400' : ''}
                                                            ${ata.status === 'concluida' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400' : ''}
                                                        `}>
                                                            {ata.status === 'rascunho' && 'Rascunho'}
                                                            {ata.status === 'aguardando_assinaturas' && 'Aguardando Assinaturas'}
                                                            {ata.status === 'concluida' && 'Concluída'}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400 font-medium">
                                                        {ata.status_assinaturas}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <div className="flex items-center justify-end gap-2">
                                                            <Link href={route('atas-reuniao.show', ata.id)} className="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300" title="Ver / Assinar">
                                                                <Eye className="w-5 h-5" />
                                                            </Link>
                                                            
                                                            {ata.status === 'rascunho' && auth.user.permissions?.includes('manage-atas-reuniao') && (
                                                                <>
                                                                    <Link href={route('atas-reuniao.edit', ata.id)} className="text-amber-600 hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-300" title="Editar">
                                                                        <Edit className="w-5 h-5" />
                                                                    </Link>
                                                                    <button onClick={() => handleSolicitar(ata.id)} className="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" title="Solicitar Assinaturas (E-mail)">
                                                                        <Mail className="w-5 h-5" />
                                                                    </button>
                                                                </>
                                                            )}

                                                            <a href={route('atas-reuniao.pdf', ata.id)} target="_blank" className="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300" title="Download PDF">
                                                                <FileText className="w-5 h-5" />
                                                            </a>

                                                            {auth.user.permissions?.includes('manage-atas-reuniao') && (
                                                                <button onClick={() => handleDelete(ata.id)} className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" title="Excluir">
                                                                    <Trash2 className="w-5 h-5" />
                                                                </button>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan="6" className="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                                    Nenhuma ata encontrada.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
