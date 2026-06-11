import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { 
    Send, CheckCircle, XCircle, Download, Clock, User, 
    CheckSquare, Target, Calendar, ArrowLeft, Edit,
    ListTodo
} from 'lucide-react';
import dayjs from 'dayjs';

export default function Show({ auth, objetivo }) {
    const userPermissions = auth.user?.permissions || [];
    const canManage = userPermissions.includes('manage-objetivos-qualidade');

    const { post, processing } = useForm({});

    const isRascunho = objetivo.status === 'rascunho' || objetivo.status === 'devolvida';
    const isAguardandoRevisao = objetivo.status === 'aguardando_revisao';
    const isAguardandoAprovacao = objetivo.status === 'aguardando_aprovacao';
    const isAprovada = objetivo.status === 'aprovada';

    const getStatusBadge = (status) => {
        const badges = {
            'rascunho': <span className="px-3 py-1 rounded-full bg-slate-100 text-slate-800 text-sm font-semibold border border-slate-200">Rascunho</span>,
            'aguardando_revisao': <span className="px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-sm font-semibold border border-blue-200">Aguardando Revisão</span>,
            'aguardando_aprovacao': <span className="px-3 py-1 rounded-full bg-amber-100 text-amber-800 text-sm font-semibold border border-amber-200">Aguardando Aprovação</span>,
            'aprovada': <span className="px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-sm font-semibold border border-emerald-200">Aprovada</span>,
            'devolvida': <span className="px-3 py-1 rounded-full bg-red-100 text-red-800 text-sm font-semibold border border-red-200">Devolvida</span>,
        };
        return badges[status] || badges['rascunho'];
    };

    const handleAction = (route) => {
        post(route, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Detalhes do Objetivo</h2>}
        >
            <Head title="Detalhes do Objetivo" />

            <div className="py-12">
                <div className="w-full sm:px-6 lg:px-8 space-y-6">
                    
                    <div className="flex mb-4">
                        <Link href={route('objetivos-qualidade.index')} className="inline-flex items-center text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
                            <ArrowLeft className="w-4 h-4 mr-1" />
                            Voltar para lista
                        </Link>
                    </div>

                    <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6 flex justify-between items-start">
                        <div>
                            <h3 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                                <Target className="h-6 w-6 text-indigo-500" />
                                {objetivo.titulo}
                            </h3>
                            <div className="mt-3 flex items-center gap-6">
                                <div className="flex items-center text-sm text-slate-500 dark:text-slate-400">
                                    <Calendar className="w-4 h-4 mr-2" />
                                    Prazo: <strong className="ml-1 text-slate-700 dark:text-slate-300">{dayjs(objetivo.prazo).format('DD/MM/YYYY')}</strong>
                                </div>
                            </div>
                        </div>
                        <div className="flex flex-col items-end gap-3">
                            {getStatusBadge(objetivo.status)}
                            <div className="flex gap-2">
                                {isRascunho && canManage && (
                                    <Link 
                                        href={route('objetivos-qualidade.edit', objetivo.id)}
                                        className="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-md font-semibold text-xs text-slate-700 dark:text-slate-300 uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                                    >
                                        <Edit className="h-4 w-4 mr-2" />
                                        Editar
                                    </Link>
                                )}
                                {isAprovada && (
                                    <a 
                                        href={route('objetivos-qualidade.pdf', objetivo.id)} 
                                        className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition"
                                    >
                                        <Download className="h-4 w-4 mr-2" />
                                        Baixar PDF
                                    </a>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        {/* Editor Principal */}
                        <div className="lg:col-span-2 space-y-6">
                            
                            <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <h4 className="text-lg font-medium text-slate-900 dark:text-white mb-4">Descrição</h4>
                                <div className="prose max-w-none dark:prose-invert" dangerouslySetInnerHTML={{ __html: objetivo.descricao || '<p class="text-slate-500 italic">Sem descrição.</p>' }} />
                            </div>

                            <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <div className="flex justify-between items-center mb-4">
                                    <h4 className="text-lg font-medium text-slate-900 dark:text-white">Planos de Ação Vinculados</h4>
                                    <Link 
                                        href={route('planos-acao.create', { objetivo_qualidade_id: objetivo.id })}
                                        className="inline-flex items-center px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded-md text-xs font-semibold uppercase hover:bg-indigo-100 transition"
                                    >
                                        <ListTodo className="h-3 w-3 mr-1" />
                                        Criar Plano de Ação
                                    </Link>
                                </div>
                                {objetivo.planos_acao?.length > 0 ? (
                                    <ul className="space-y-3">
                                        {objetivo.planos_acao.map(pa => (
                                            <li key={pa.id} className="border border-slate-200 dark:border-slate-700 rounded-lg p-3 flex justify-between items-center">
                                                <span className="text-sm font-medium dark:text-slate-200">Plano #{pa.id}</span>
                                                <Link href={route('planos-acao.show', pa.id)} className="text-indigo-600 text-sm hover:underline">Ver Plano</Link>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="text-sm text-slate-500 italic">Nenhum plano de ação aberto para este objetivo.</p>
                                )}
                            </div>

                        </div>

                        {/* Coluna Lateral: Responsáveis, Timeline e Ações */}
                        <div className="space-y-6">
                            
                            {/* Responsáveis */}
                            <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <h4 className="text-md font-medium text-slate-900 dark:text-white mb-4">Responsáveis</h4>
                                <ul className="space-y-3">
                                    {objetivo.responsaveis?.map(resp => (
                                        <li key={resp.id} className="flex items-center gap-3">
                                            <div className="bg-slate-100 dark:bg-slate-700 p-2 rounded-full">
                                                <User className="h-4 w-4 text-slate-600 dark:text-slate-300" />
                                            </div>
                                            <span className="text-sm font-medium text-slate-700 dark:text-slate-200">{resp.name}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            {/* Bloco de Ações */}
                            {canManage && !isAprovada && (
                                <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                    <h4 className="text-md font-medium text-slate-900 dark:text-white mb-4">Ações Disponíveis</h4>
                                    
                                    <div className="space-y-3">
                                        {isRascunho && (
                                            <button
                                                onClick={() => handleAction(route('objetivos-qualidade.enviar-revisao', objetivo.id))}
                                                disabled={processing}
                                                className="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-25 transition"
                                            >
                                                <Send className="h-4 w-4 mr-2" />
                                                Enviar para Revisão
                                            </button>
                                        )}

                                        {isAguardandoRevisao && (
                                            <>
                                                <button
                                                    onClick={() => handleAction(route('objetivos-qualidade.aprovar-revisao', objetivo.id))}
                                                    disabled={processing || objetivo.elaborador_id === auth.user.id}
                                                    title={objetivo.elaborador_id === auth.user.id ? "Você não pode revisar um objetivo que elaborou." : ""}
                                                    className="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 disabled:opacity-50 transition"
                                                >
                                                    <CheckSquare className="h-4 w-4 mr-2" />
                                                    Aprovar Revisão
                                                </button>
                                                <button
                                                    onClick={() => handleAction(route('objetivos-qualidade.devolver', objetivo.id))}
                                                    disabled={processing}
                                                    className="w-full inline-flex justify-center items-center px-4 py-2 bg-white border border-red-300 text-red-700 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-red-50 disabled:opacity-25 transition"
                                                >
                                                    <XCircle className="h-4 w-4 mr-2" />
                                                    Devolver
                                                </button>
                                            </>
                                        )}

                                        {isAguardandoAprovacao && (
                                            <>
                                                <button
                                                    onClick={() => handleAction(route('objetivos-qualidade.aprovar-final', objetivo.id))}
                                                    disabled={processing}
                                                    className="w-full inline-flex justify-center items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 disabled:opacity-25 transition"
                                                >
                                                    <CheckCircle className="h-4 w-4 mr-2" />
                                                    Aprovação Final
                                                </button>
                                                <button
                                                    onClick={() => handleAction(route('objetivos-qualidade.devolver', objetivo.id))}
                                                    disabled={processing}
                                                    className="w-full inline-flex justify-center items-center px-4 py-2 bg-white border border-red-300 text-red-700 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-red-50 disabled:opacity-25 transition"
                                                >
                                                    <XCircle className="h-4 w-4 mr-2" />
                                                    Devolver
                                                </button>
                                            </>
                                        )}
                                    </div>
                                    
                                    {isAguardandoRevisao && objetivo.elaborador_id === auth.user.id && (
                                        <p className="mt-3 text-xs text-red-500 font-medium">Atenção: O elaborador não pode revisar o próprio documento.</p>
                                    )}
                                </div>
                            )}

                            {/* Bloco Timeline */}
                            <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <h4 className="text-md font-medium text-slate-900 dark:text-white mb-4">Histórico de Aprovações</h4>
                                
                                <div className="space-y-6">
                                    {/* Elaborador */}
                                    <div className="flex gap-4">
                                        <div className="mt-1 bg-slate-100 dark:bg-slate-700 p-2 rounded-full h-min">
                                            <User className="h-4 w-4 text-slate-500 dark:text-slate-300" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-slate-900 dark:text-white">Elaboração</p>
                                            <p className="text-sm text-slate-600 dark:text-slate-400">{objetivo.elaborador ? objetivo.elaborador.name : 'Pendente'}</p>
                                            {objetivo.data_elaboracao && (
                                                <p className="text-xs text-slate-400 mt-1 flex items-center">
                                                    <Clock className="h-3 w-3 mr-1" /> {dayjs(objetivo.data_elaboracao).format('DD/MM/YYYY HH:mm')}
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    {/* Revisor */}
                                    <div className="flex gap-4">
                                        <div className={`mt-1 p-2 rounded-full h-min ${objetivo.data_revisao ? 'bg-blue-50 dark:bg-blue-900/30' : 'bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700'}`}>
                                            <CheckSquare className={`h-4 w-4 ${objetivo.data_revisao ? 'text-blue-500' : 'text-slate-400'}`} />
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-slate-900 dark:text-white">Revisão {objetivo.revisor && !objetivo.data_revisao ? '(Designado)' : ''}</p>
                                            <p className="text-sm text-slate-600 dark:text-slate-400">{objetivo.revisor ? objetivo.revisor.name : 'Pendente'}</p>
                                            {objetivo.data_revisao && (
                                                <p className="text-xs text-slate-400 mt-1 flex items-center">
                                                    <Clock className="h-3 w-3 mr-1" /> {dayjs(objetivo.data_revisao).format('DD/MM/YYYY HH:mm')}
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    {/* Aprovador */}
                                    <div className="flex gap-4">
                                        <div className={`mt-1 p-2 rounded-full h-min ${objetivo.data_aprovacao ? 'bg-emerald-50 dark:bg-emerald-900/30' : 'bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700'}`}>
                                            <CheckCircle className={`h-4 w-4 ${objetivo.data_aprovacao ? 'text-emerald-500' : 'text-slate-400'}`} />
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-slate-900 dark:text-white">Aprovação {objetivo.aprovador && !objetivo.data_aprovacao ? '(Designado)' : ''}</p>
                                            <p className="text-sm text-slate-600 dark:text-slate-400">{objetivo.aprovador ? objetivo.aprovador.name : 'Pendente'}</p>
                                            {objetivo.data_aprovacao && (
                                                <p className="text-xs text-slate-400 mt-1 flex items-center">
                                                    <Clock className="h-3 w-3 mr-1" /> {dayjs(objetivo.data_aprovacao).format('DD/MM/YYYY HH:mm')}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                    
                                    {objetivo.hash_assinatura && (
                                        <div className="pt-4 border-t border-slate-100 dark:border-slate-700">
                                            <p className="text-xs font-semibold text-slate-900 dark:text-white mb-1">Hash de Autenticidade:</p>
                                            <p className="text-xs text-slate-500 dark:text-slate-400 break-all font-mono bg-slate-50 dark:bg-slate-900 p-2 rounded">{objetivo.hash_assinatura}</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
