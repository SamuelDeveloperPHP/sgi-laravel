import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Edit, Trash2, FileCheck2 } from 'lucide-react';

export default function Index({ auth, auditorias, flash }) {
    const { delete: destroy } = useForm();

    const handleDelete = (id) => {
        if (confirm('Tem certeza que deseja excluir esta auditoria?')) {
            destroy(route('auditorias.destroy', id));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Auditorias Internas</h2>}
        >
            <Head title="Auditorias Internas" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Header Actions */}
                    <div className="flex justify-between items-center bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/50">
                        <div className="flex items-center gap-3">
                            <div className="p-3 bg-emerald-50 dark:bg-emerald-500/10 rounded-lg">
                                <FileCheck2 className="w-6 h-6 text-emerald-500" />
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-slate-900 dark:text-white">Gerenciamento de Auditorias</h3>
                                <p className="text-sm text-slate-500 dark:text-slate-400">Visualize e gerencie todas as auditorias da qualidade.</p>
                            </div>
                        </div>
                        <Link 
                            href={route('auditorias.create')} 
                            className="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl transition-colors font-medium text-sm"
                        >
                            <Plus className="w-4 h-4" /> Nova Auditoria
                        </Link>
                    </div>

                    {/* Messages */}
                    {flash?.message && (
                        <div className="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl">
                            {flash.message}
                        </div>
                    )}

                    {/* Table */}
                    <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left text-slate-500 dark:text-slate-400">
                                <thead className="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-700/50 dark:text-slate-300">
                                    <tr>
                                        <th scope="col" className="px-6 py-4">Localidade</th>
                                        <th scope="col" className="px-6 py-4">Setor</th>
                                        <th scope="col" className="px-6 py-4">Data</th>
                                        <th scope="col" className="px-6 py-4">Auditor Líder</th>
                                        <th scope="col" className="px-6 py-4 text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {auditorias.data.length > 0 ? auditorias.data.map((auditoria) => (
                                        <tr key={auditoria.id} className="border-b dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                            <td className="px-6 py-4 font-medium text-slate-900 dark:text-white">
                                                {auditoria.localidade}
                                            </td>
                                            <td className="px-6 py-4">{auditoria.setor}</td>
                                            <td className="px-6 py-4">{new Date(auditoria.dataRealizacao).toLocaleDateString('pt-BR')}</td>
                                            <td className="px-6 py-4">{auditoria.auditorlider}</td>
                                            <td className="px-6 py-4 flex justify-end gap-3">
                                                <Link 
                                                    href={route('auditorias.show', auditoria.id)}
                                                    className="text-emerald-500 hover:text-emerald-700 p-2 hover:bg-emerald-50 rounded-lg"
                                                    title="Visualizar/Imprimir Relatório"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                                </Link>
                                                <Link 
                                                    href={route('auditorias.edit', auditoria.id)}
                                                    className="text-blue-500 hover:text-blue-700 transition-colors p-2 hover:bg-blue-50 rounded-lg"
                                                    title="Editar"
                                                >
                                                    <Edit className="w-4 h-4" />
                                                </Link>
                                                <button 
                                                    onClick={() => handleDelete(auditoria.id)}
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
                                                Nenhuma auditoria encontrada.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        {/* Pagination (Simplificada para o MVP) */}
                        <div className="px-6 py-4 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
                            <span className="text-sm text-slate-500">Mostrando página {auditorias.current_page} de {auditorias.last_page}</span>
                            <div className="flex gap-2">
                                {/* Links de paginação do Laravel podem ser renderizados aqui mapeando auditorias.links */}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
