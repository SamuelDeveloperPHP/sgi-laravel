import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { Plus, Target, Calendar, User, Eye, Trash2 } from 'lucide-react';
import dayjs from 'dayjs';

export default function Index({ auth, objetivos, companies, currentCompanyId }) {
    const userPermissions = auth.user?.permissions || [];
    const canManage = userPermissions.includes('manage-objetivos-qualidade');

    const getStatusBadge = (status) => {
        const badges = {
            'rascunho': <span className="px-2 py-1 rounded-full bg-slate-100 text-slate-800 text-xs font-semibold">Rascunho</span>,
            'aguardando_revisao': <span className="px-2 py-1 rounded-full bg-blue-100 text-blue-800 text-xs font-semibold">Aguardando Revisão</span>,
            'aguardando_aprovacao': <span className="px-2 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-semibold">Aguardando Aprovação</span>,
            'aprovada': <span className="px-2 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-semibold">Aprovada</span>,
            'devolvida': <span className="px-2 py-1 rounded-full bg-red-100 text-red-800 text-xs font-semibold">Devolvida</span>,
        };
        return badges[status] || badges['rascunho'];
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Objetivos da Qualidade</h2>
                    {companies && companies.length > 0 && (
                        <div className="flex items-center gap-2">
                            <span className="text-sm text-slate-500">Empresa:</span>
                            <select
                                className="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm"
                                value={currentCompanyId}
                                onChange={(e) => {
                                    window.location.href = route('objetivos-qualidade.index', { company_id: e.target.value });
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
            <Head title="Objetivos da Qualidade" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Botões de Ação */}
                    {canManage && (
                        <div className="flex justify-end">
                            <Link
                                href={route('objetivos-qualidade.create')}
                                className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                <Plus className="w-4 h-4 mr-2" />
                                Novo Objetivo
                            </Link>
                        </div>
                    )}

                    <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-slate-900 dark:text-slate-100">
                            {objetivos.length === 0 ? (
                                <div className="text-center py-8">
                                    <Target className="mx-auto h-12 w-12 text-slate-400" />
                                    <h3 className="mt-2 text-sm font-semibold text-slate-900 dark:text-white">Nenhum Objetivo</h3>
                                    <p className="mt-1 text-sm text-slate-500">Comece criando um novo objetivo da qualidade.</p>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {objetivos.map((objetivo) => (
                                        <div key={objetivo.id} className="border border-slate-200 dark:border-slate-700 rounded-lg p-5 flex flex-col hover:border-indigo-300 transition">
                                            <div className="flex justify-between items-start mb-4">
                                                <h3 className="text-lg font-bold text-slate-800 dark:text-white line-clamp-2">{objetivo.titulo}</h3>
                                                <div className="ml-2 flex-shrink-0">
                                                    {getStatusBadge(objetivo.status)}
                                                </div>
                                            </div>
                                            
                                            <div className="mt-auto space-y-3">
                                                <div className="flex items-center text-sm text-slate-500 dark:text-slate-400">
                                                    <Calendar className="w-4 h-4 mr-2 text-indigo-400" />
                                                    Prazo: <strong className="ml-1 text-slate-700 dark:text-slate-300">{dayjs(objetivo.prazo).format('DD/MM/YYYY')}</strong>
                                                </div>
                                                
                                                <div className="flex items-center text-sm text-slate-500 dark:text-slate-400">
                                                    <User className="w-4 h-4 mr-2 text-indigo-400" />
                                                    {objetivo.responsaveis?.length || 0} Responsável(is)
                                                </div>

                                                <div className="pt-4 flex gap-2 border-t border-slate-100 dark:border-slate-700">
                                                    <Link 
                                                        href={route('objetivos-qualidade.show', objetivo.id)}
                                                        className="flex-1 inline-flex justify-center items-center px-4 py-2 bg-slate-100 dark:bg-slate-700 border border-transparent rounded-md font-semibold text-xs text-slate-700 dark:text-slate-300 uppercase tracking-widest hover:bg-slate-200 dark:hover:bg-slate-600 transition"
                                                    >
                                                        <Eye className="w-4 h-4 mr-2" />
                                                        Detalhes
                                                    </Link>
                                                    {canManage && (objetivo.status === 'rascunho' || objetivo.status === 'devolvida') && (
                                                        <Link 
                                                            href={route('objetivos-qualidade.destroy', objetivo.id)}
                                                            method="delete"
                                                            as="button"
                                                            className="inline-flex justify-center items-center px-3 py-2 bg-white dark:bg-slate-800 border border-red-300 text-red-700 rounded-md font-semibold text-xs hover:bg-red-50 transition"
                                                        >
                                                            <Trash2 className="w-4 h-4" />
                                                        </Link>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
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
