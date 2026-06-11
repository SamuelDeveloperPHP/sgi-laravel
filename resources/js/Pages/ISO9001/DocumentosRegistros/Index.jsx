import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, History, FileText } from 'lucide-react';
import FormModal from './FormModal';
import RevisoesModal from './RevisoesModal';

export default function Index({ auth, documentos, companies, currentCompanyId, users }) {
    const userPermissions = auth.user?.permissions || [];
    const canManage = userPermissions.includes('manage-controle-documentos');

    const [isFormOpen, setIsFormOpen] = useState(false);
    const [isHistoryOpen, setIsHistoryOpen] = useState(false);
    const [selectedDocumento, setSelectedDocumento] = useState(null);

    const openCreate = () => {
        setSelectedDocumento(null);
        setIsFormOpen(true);
    };

    const openEdit = (doc) => {
        setSelectedDocumento(doc);
        setIsFormOpen(true);
    };

    const openHistory = (doc) => {
        setSelectedDocumento(doc);
        setIsHistoryOpen(true);
    };

    const handleDelete = (id) => {
        if (confirm('Tem certeza que deseja excluir este documento? Todo o histórico de revisões será apagado também.')) {
            router.delete(route('controle-documentos.destroy', id), {
                preserveScroll: true
            });
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                        Controle de Documentos e Registros
                    </h2>
                    {companies && companies.length > 0 && (
                        <div className="flex items-center gap-2">
                            <span className="text-sm text-slate-500">Empresa:</span>
                            <select
                                className="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm"
                                value={currentCompanyId}
                                onChange={(e) => {
                                    window.location.href = route('controle-documentos.index', { company_id: e.target.value });
                                }}
                            >
                                {companies.map(c => (
                                    <option key={c.id} value={c.id}>{c.nome_fantasia}</option>
                                ))}
                            </select>
                        </div>
                    )}
                </div>
            }
        >
            <Head title="Controle de Documentos" />

            <div className="py-12">
                <div className="max-w-screen-2xl mx-auto sm:px-6 lg:px-8">
                    
                    <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 dark:border-slate-700">
                        <div className="p-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                            <div className="flex items-center gap-2">
                                <FileText className="h-6 w-6 text-indigo-500" />
                                <h3 className="text-lg font-medium text-slate-900 dark:text-white">Matriz de Documentos</h3>
                            </div>
                            {canManage && (
                                <button
                                    onClick={openCreate}
                                    className="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-semibold transition shadow-sm"
                                >
                                    <Plus className="h-4 w-4 mr-2" />
                                    Novo Documento
                                </button>
                            )}
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                                <thead className="bg-gray-50 dark:bg-slate-900/50">
                                    <tr>
                                        <th scope="col" className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Código</th>
                                        <th scope="col" className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Identificação</th>
                                        <th scope="col" className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Área</th>
                                        <th scope="col" className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rev Atual</th>
                                        <th scope="col" className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Local</th>
                                        <th scope="col" className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tempo Arq.</th>
                                        <th scope="col" className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                    {documentos.length === 0 ? (
                                        <tr>
                                            <td colSpan="7" className="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                                Nenhum documento cadastrado na matriz.
                                            </td>
                                        </tr>
                                    ) : (
                                        documentos.map((doc) => (
                                            <tr key={doc.id} className="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                                <td className="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white whitespace-nowrap">
                                                    {doc.codigo || '-'}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
                                                    <span className="font-semibold block">{doc.identificacao}</span>
                                                    <span className="text-xs text-slate-500">{doc.tipo_documento}</span>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                                                    {doc.area || '-'}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-center">
                                                    <span className="bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300 px-2 py-1 rounded-md font-bold text-xs">
                                                        {doc.revisao_atual || '-'}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                                                    {doc.local_arquivo || '-'}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                                                    {doc.tempo_arquivamento || '-'}
                                                </td>
                                                <td className="px-4 py-3 text-sm font-medium text-center space-x-2 whitespace-nowrap">
                                                    <button
                                                        onClick={() => openHistory(doc)}
                                                        className="inline-flex p-1.5 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 transition"
                                                        title="Histórico de Revisão"
                                                    >
                                                        <History className="h-4 w-4" />
                                                    </button>
                                                    {canManage && (
                                                        <>
                                                            <button
                                                                onClick={() => openEdit(doc)}
                                                                className="inline-flex p-1.5 bg-slate-100 text-slate-600 rounded hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 transition"
                                                                title="Editar Matriz"
                                                            >
                                                                <Edit className="h-4 w-4" />
                                                            </button>
                                                            <button
                                                                onClick={() => handleDelete(doc.id)}
                                                                className="inline-flex p-1.5 bg-red-50 text-red-600 rounded hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 transition"
                                                                title="Excluir"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </button>
                                                        </>
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <FormModal 
                show={isFormOpen} 
                onClose={() => setIsFormOpen(false)} 
                documento={selectedDocumento}
                currentCompanyId={currentCompanyId}
            />

            <RevisoesModal 
                show={isHistoryOpen} 
                onClose={() => setIsHistoryOpen(false)} 
                documento={selectedDocumento}
                users={users}
            />
            
        </AuthenticatedLayout>
    );
}
