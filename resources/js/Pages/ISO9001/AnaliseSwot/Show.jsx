import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle, Clock, XCircle, Grid, Check, X, MessageSquare, Shield, Activity, GitMerge, ListTodo, Target } from 'lucide-react';
import { format } from 'date-fns';
import { ptBR } from 'date-fns/locale';

export default function Show({ auth, analise }) {
    const user = auth.user;
    const isMasterAdmin = user.is_master_admin;
    const isAprovador = user.id === analise.aprovador_id || isMasterAdmin;

    const [showRejectionModal, setShowRejectionModal] = useState(false);
    const [motivoRejeicao, setMotivoRejeicao] = useState('');

    const [activeTab, setActiveTab] = useState('fatores');

    const handleEnviarAprovacao = () => {
        if (confirm('Deseja enviar esta análise para aprovação? Ela será bloqueada para edição.')) {
            router.post(route('analise-swot.enviar-aprovacao', analise.id));
        }
    };

    const handleAprovar = () => {
        if (confirm('Tem certeza que deseja aprovar esta análise SWOT?')) {
            router.post(route('analise-swot.aprovar', analise.id));
        }
    };

    const handleRejeitar = (e) => {
        e.preventDefault();
        if (!motivoRejeicao.trim()) {
            alert('Por favor, informe a justificativa da rejeição.');
            return;
        }

        router.post(route('analise-swot.rejeitar', analise.id), {
            motivo_rejeicao: motivoRejeicao
        }, {
            onSuccess: () => {
                setShowRejectionModal(false);
                setMotivoRejeicao('');
            }
        });
    };

    const getStatusDetails = (status) => {
        switch (status) {
            case 'approved':
                return { label: 'Aprovado', color: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/20 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900', icon: CheckCircle };
            case 'pending_approval':
                return { label: 'Aguardando Aprovação', color: 'text-amber-600 bg-amber-50 dark:bg-amber-950/20 dark:text-amber-400 border-amber-200 dark:border-amber-900', icon: Clock };
            case 'rejected':
                return { label: 'Rejeitado', color: 'text-rose-600 bg-rose-50 dark:bg-rose-950/20 dark:text-rose-400 border-rose-200 dark:border-rose-900', icon: XCircle };
            default:
                return { label: 'Rascunho', color: 'text-slate-600 bg-slate-50 dark:bg-slate-900/50 dark:text-slate-400 border-slate-200 dark:border-slate-800', icon: Shield };
        }
    };

    const statusInfo = getStatusDetails(analise.status);
    const StatusIcon = statusInfo.icon;

    // Cálculos da Matriz SWOT Avançada
    const safeArray = (arr) => Array.isArray(arr) ? arr : [];
    
    const strengths = safeArray(analise.strengths);
    const weaknesses = safeArray(analise.weaknesses);
    const opportunities = safeArray(analise.opportunities);
    const threats = safeArray(analise.threats);
    const cruzamentos = safeArray(analise.cruzamentos);
    const planos = safeArray(analise.planos_acao);

    const calcPontuacao = (arr) => arr.reduce((acc, item) => acc + (Number(item.pontuacao) || 0), 0);

    const scoreS = calcPontuacao(strengths);
    const scoreW = calcPontuacao(weaknesses);
    const scoreO = calcPontuacao(opportunities);
    const scoreT = calcPontuacao(threats);

    const totalPositivo = scoreS + scoreO;
    const totalNegativo = scoreW + scoreT;
    const totalGeral = totalPositivo + totalNegativo;

    const favorabilidade = totalGeral > 0 ? ((totalPositivo / totalGeral) * 100).toFixed(1) : 0;
    
    // Identificar a situação estratégica
    let situacaoEstrategica = "Neutro";
    let situacaoColor = "text-slate-600";
    if (favorabilidade >= 70) {
        situacaoEstrategica = "Altamente Favorável (Ofensiva)";
        situacaoColor = "text-emerald-600";
    } else if (favorabilidade >= 50) {
        situacaoEstrategica = "Favorável (Desenvolvimento)";
        situacaoColor = "text-blue-600";
    } else if (favorabilidade >= 35) {
        situacaoEstrategica = "Atenção (Manutenção)";
        situacaoColor = "text-amber-600";
    } else {
        situacaoEstrategica = "Crítico (Sobrevivência)";
        situacaoColor = "text-rose-600";
    }

    const getFatorNome = (id) => {
        const todos = [...strengths, ...weaknesses, ...opportunities, ...threats];
        const f = todos.find(f => String(f.id) === String(id));
        return f ? f.nome : 'Fator não encontrado';
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Análise SWOT Avançada</h2>}
        >
            <Head title={`SWOT: ${analise.titulo}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    
                    {/* Barra de Ações do Topo */}
                    <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                        <Link
                            href={route('analise-swot.index')}
                            className="inline-flex items-center text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                        >
                            <ArrowLeft className="w-4 h-4 mr-1.5" />
                            Voltar para Listagem
                        </Link>

                        <div className="flex gap-2 w-full md:w-auto">
                            {(analise.status === 'draft' || analise.status === 'rejected') && (
                                <>
                                    <Link
                                        href={route('analise-swot.edit', analise.id)}
                                        className="inline-flex items-center justify-center px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 rounded-lg text-sm font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                                    >
                                        Editar Rascunho
                                    </Link>
                                    
                                    <button
                                        onClick={handleEnviarAprovacao}
                                        className="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 transition-all"
                                    >
                                        Enviar para Aprovação
                                    </button>
                                </>
                            )}

                            {analise.status === 'pending_approval' && isAprovador && (
                                <div className="flex gap-2 w-full">
                                    <button
                                        onClick={() => setShowRejectionModal(true)}
                                        className="inline-flex items-center justify-center flex-1 md:flex-initial px-4 py-2 border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-400 rounded-lg text-sm font-semibold hover:bg-rose-100 dark:hover:bg-rose-950/30 transition-colors"
                                    >
                                        <X className="w-4 h-4 mr-1.5" />
                                        Rejeitar
                                    </button>
                                    
                                    <button
                                        onClick={handleAprovar}
                                        className="inline-flex items-center justify-center flex-1 md:flex-initial px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold shadow-lg shadow-emerald-500/10 transition-all"
                                    >
                                        <Check className="w-4 h-4 mr-1.5" />
                                        Aprovar
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        
                        {/* Seção Principal (Esquerda - 3 colunas) */}
                        <div className="lg:col-span-3 space-y-6">
                            
                            {/* Card de Informações Gerais */}
                            <div className="bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-100 dark:border-slate-700 p-6 relative overflow-hidden">
                                <div className="flex flex-col sm:flex-row justify-between items-start gap-4 mb-4">
                                    <div className="z-10">
                                        <h3 className="text-2xl font-bold text-slate-900 dark:text-white">{analise.titulo}</h3>
                                        {analise.objetivo_estrategico && (
                                            <div className="flex items-center gap-2 mt-2 text-sm text-indigo-700 dark:text-indigo-400 font-medium">
                                                <Target className="w-4 h-4" />
                                                <span>{analise.objetivo_estrategico}</span>
                                            </div>
                                        )}
                                        <p className="text-sm text-slate-500 mt-2">
                                            Data: <span className="font-semibold text-slate-700 dark:text-slate-300">
                                                {analise.data_analise ? format(new Date(analise.data_analise), 'dd/MM/yyyy', { locale: ptBR }) : '-'}
                                            </span>
                                        </p>
                                    </div>
                                    <div className={`inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border font-bold text-sm z-10 ${statusInfo.color}`}>
                                        <StatusIcon className="w-4 h-4" />
                                        {statusInfo.label}
                                    </div>
                                </div>

                                <div className="mt-6 pt-6 border-t border-slate-100 dark:border-slate-700 grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div className="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-lg border border-slate-100 dark:border-slate-700/50 text-center">
                                        <div className="text-xs font-bold text-emerald-600 uppercase mb-1">Score S</div>
                                        <div className="text-2xl font-black text-slate-800 dark:text-white">{scoreS}</div>
                                    </div>
                                    <div className="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-lg border border-slate-100 dark:border-slate-700/50 text-center">
                                        <div className="text-xs font-bold text-rose-600 uppercase mb-1">Score W</div>
                                        <div className="text-2xl font-black text-slate-800 dark:text-white">{scoreW}</div>
                                    </div>
                                    <div className="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-lg border border-slate-100 dark:border-slate-700/50 text-center">
                                        <div className="text-xs font-bold text-blue-600 uppercase mb-1">Score O</div>
                                        <div className="text-2xl font-black text-slate-800 dark:text-white">{scoreO}</div>
                                    </div>
                                    <div className="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-lg border border-slate-100 dark:border-slate-700/50 text-center">
                                        <div className="text-xs font-bold text-amber-600 uppercase mb-1">Score T</div>
                                        <div className="text-2xl font-black text-slate-800 dark:text-white">{scoreT}</div>
                                    </div>
                                </div>
                            </div>

                            {/* Abas */}
                            <div className="bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-100 dark:border-slate-700 overflow-hidden">
                                <div className="flex border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
                                    <button onClick={() => setActiveTab('fatores')} className={`flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 transition-colors ${activeTab === 'fatores' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-white dark:bg-slate-800' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'}`}>
                                        <Activity className="w-4 h-4" /> 1. Fatores SWOT
                                    </button>
                                    <button onClick={() => setActiveTab('cruzamentos')} className={`flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 transition-colors ${activeTab === 'cruzamentos' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-white dark:bg-slate-800' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'}`}>
                                        <GitMerge className="w-4 h-4" /> 2. Cruzamentos TOWS
                                    </button>
                                    <button onClick={() => setActiveTab('planos')} className={`flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 transition-colors ${activeTab === 'planos' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-white dark:bg-slate-800' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'}`}>
                                        <ListTodo className="w-4 h-4" /> 3. Planos de Ação
                                    </button>
                                </div>

                                <div className="p-6">
                                    {activeTab === 'fatores' && (
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            {/* Forças */}
                                            <div className="bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800/50 rounded-xl p-5 shadow-sm">
                                                <h5 className="font-bold text-emerald-800 dark:text-emerald-400 mb-3 border-b border-emerald-200 dark:border-emerald-800/50 pb-2 flex justify-between">
                                                    <span>Forças (S)</span>
                                                    <span>{strengths.length}</span>
                                                </h5>
                                                {strengths.length > 0 ? (
                                                    <ul className="space-y-2">
                                                        {strengths.map((item, i) => (
                                                            <li key={i} className="bg-white dark:bg-slate-800 p-3 rounded shadow-sm border border-emerald-100 dark:border-emerald-900/50">
                                                                <div className="text-sm font-semibold text-slate-800 dark:text-slate-200">{item.nome}</div>
                                                                <div className="text-xs text-slate-500 mt-1">Imp: {item.importancia} | Int: {item.intensidade} | <span className="font-bold text-emerald-600">Score: {item.pontuacao}</span></div>
                                                            </li>
                                                        ))}
                                                    </ul>
                                                ) : <p className="text-sm text-emerald-600/60 italic">Nenhuma força.</p>}
                                            </div>

                                            {/* Fraquezas */}
                                            <div className="bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800/50 rounded-xl p-5 shadow-sm">
                                                <h5 className="font-bold text-rose-800 dark:text-rose-400 mb-3 border-b border-rose-200 dark:border-rose-800/50 pb-2 flex justify-between">
                                                    <span>Fraquezas (W)</span>
                                                    <span>{weaknesses.length}</span>
                                                </h5>
                                                {weaknesses.length > 0 ? (
                                                    <ul className="space-y-2">
                                                        {weaknesses.map((item, i) => (
                                                            <li key={i} className="bg-white dark:bg-slate-800 p-3 rounded shadow-sm border border-rose-100 dark:border-rose-900/50">
                                                                <div className="text-sm font-semibold text-slate-800 dark:text-slate-200">{item.nome}</div>
                                                                <div className="text-xs text-slate-500 mt-1">Imp: {item.importancia} | Int: {item.intensidade} | <span className="font-bold text-rose-600">Score: {item.pontuacao}</span></div>
                                                            </li>
                                                        ))}
                                                    </ul>
                                                ) : <p className="text-sm text-rose-600/60 italic">Nenhuma fraqueza.</p>}
                                            </div>

                                            {/* Oportunidades */}
                                            <div className="bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800/50 rounded-xl p-5 shadow-sm">
                                                <h5 className="font-bold text-blue-800 dark:text-blue-400 mb-3 border-b border-blue-200 dark:border-blue-800/50 pb-2 flex justify-between">
                                                    <span>Oportunidades (O)</span>
                                                    <span>{opportunities.length}</span>
                                                </h5>
                                                {opportunities.length > 0 ? (
                                                    <ul className="space-y-2">
                                                        {opportunities.map((item, i) => (
                                                            <li key={i} className="bg-white dark:bg-slate-800 p-3 rounded shadow-sm border border-blue-100 dark:border-blue-900/50">
                                                                <div className="text-sm font-semibold text-slate-800 dark:text-slate-200">{item.nome}</div>
                                                                <div className="text-xs text-slate-500 mt-1">Imp: {item.importancia} | Int: {item.intensidade} | <span className="font-bold text-blue-600">Score: {item.pontuacao}</span></div>
                                                            </li>
                                                        ))}
                                                    </ul>
                                                ) : <p className="text-sm text-blue-600/60 italic">Nenhuma oportunidade.</p>}
                                            </div>

                                            {/* Ameaças */}
                                            <div className="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/50 rounded-xl p-5 shadow-sm">
                                                <h5 className="font-bold text-amber-800 dark:text-amber-400 mb-3 border-b border-amber-200 dark:border-amber-800/50 pb-2 flex justify-between">
                                                    <span>Ameaças (T)</span>
                                                    <span>{threats.length}</span>
                                                </h5>
                                                {threats.length > 0 ? (
                                                    <ul className="space-y-2">
                                                        {threats.map((item, i) => (
                                                            <li key={i} className="bg-white dark:bg-slate-800 p-3 rounded shadow-sm border border-amber-100 dark:border-amber-900/50">
                                                                <div className="text-sm font-semibold text-slate-800 dark:text-slate-200">{item.nome}</div>
                                                                <div className="text-xs text-slate-500 mt-1">Imp: {item.importancia} | Int: {item.intensidade} | <span className="font-bold text-amber-600">Score: {item.pontuacao}</span></div>
                                                            </li>
                                                        ))}
                                                    </ul>
                                                ) : <p className="text-sm text-amber-600/60 italic">Nenhuma ameaça.</p>}
                                            </div>
                                        </div>
                                    )}

                                    {activeTab === 'cruzamentos' && (
                                        <div className="space-y-4">
                                            {cruzamentos.length > 0 ? cruzamentos.map((cruz) => (
                                                <div key={cruz.id} className="bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 p-4 rounded-xl">
                                                    <div className="flex flex-col">
                                                        <span className="text-xs font-bold text-indigo-500 mb-2 uppercase tracking-wide border-b border-indigo-100 dark:border-indigo-900/30 pb-1">{cruz.tipo.replace('_', ' x ')}</span>
                                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3 text-sm">
                                                            <div className="bg-white dark:bg-slate-800 p-2 rounded shadow-sm">
                                                                <span className="text-xs text-slate-400 block mb-1">Fator Interno</span>
                                                                <span className="font-medium text-slate-800 dark:text-slate-200">{getFatorNome(cruz.fator_interno_id)}</span>
                                                            </div>
                                                            <div className="bg-white dark:bg-slate-800 p-2 rounded shadow-sm">
                                                                <span className="text-xs text-slate-400 block mb-1">Fator Externo</span>
                                                                <span className="font-medium text-slate-800 dark:text-slate-200">{getFatorNome(cruz.fator_externo_id)}</span>
                                                            </div>
                                                        </div>
                                                        <div className="bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded border border-indigo-100 dark:border-indigo-900/50">
                                                            <span className="text-xs font-bold text-indigo-800 dark:text-indigo-400 block mb-1">Diretriz Estratégica</span>
                                                            <span className="text-sm font-semibold text-slate-900 dark:text-white">{cruz.estrategia}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            )) : <p className="text-sm text-slate-500 text-center py-8">Nenhum cruzamento definido.</p>}
                                        </div>
                                    )}

                                    {activeTab === 'planos' && (
                                        <div className="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
                                            <table className="w-full text-left border-collapse">
                                                <thead className="bg-slate-50 dark:bg-slate-900/80">
                                                    <tr className="text-sm text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                                                        <th className="px-4 py-3 font-medium">Ação</th>
                                                        <th className="px-4 py-3 font-medium">Responsável</th>
                                                        <th className="px-4 py-3 font-medium">Prazo</th>
                                                        <th className="px-4 py-3 font-medium">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {planos.length > 0 ? planos.map((plano) => (
                                                        <tr key={plano.id} className="border-b border-slate-100 dark:border-slate-800 text-sm hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                                                            <td className="px-4 py-4 font-medium text-slate-800 dark:text-slate-200">{plano.acao}</td>
                                                            <td className="px-4 py-4 text-slate-600 dark:text-slate-400">{plano.responsavel}</td>
                                                            <td className="px-4 py-4 text-slate-600 dark:text-slate-400">
                                                                {plano.prazo ? format(new Date(plano.prazo), 'dd/MM/yyyy') : '-'}
                                                            </td>
                                                            <td className="px-4 py-4">
                                                                <span className={`px-2.5 py-1 rounded-full text-xs font-semibold ${
                                                                    plano.status === 'Concluído' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' :
                                                                    plano.status === 'Em Andamento' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' :
                                                                    plano.status === 'Atrasado' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' :
                                                                    'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400'
                                                                }`}>
                                                                    {plano.status}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    )) : (
                                                        <tr><td colSpan="4" className="text-center py-8 text-slate-500">Nenhum plano de ação adicionado.</td></tr>
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}
                                </div>
                            </div>
                            
                            {/* Conclusão Geral */}
                            {analise.conclusao && (
                                <div className="bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-100 dark:border-slate-700 p-6">
                                    <h3 className="text-base font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                                        <Grid className="w-5 h-5 text-indigo-500" />
                                        Conclusão Geral
                                    </h3>
                                    <div className="text-sm text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 p-5 rounded-lg border border-slate-100 dark:border-slate-800">
                                        <div className="whitespace-pre-wrap">{analise.conclusao}</div>
                                    </div>
                                </div>
                            )}

                        </div>

                        {/* Painel Lateral (Direita - 1 coluna) */}
                        <div className="space-y-6">
                            
                            {/* Card de Índice de Favorabilidade */}
                            <div className="bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-100 dark:border-slate-700 p-6">
                                <h3 className="text-base font-bold text-slate-800 dark:text-white mb-4 text-center">Índice de Favorabilidade</h3>
                                <div className="flex flex-col items-center justify-center py-4">
                                    <div className="relative w-32 h-32 flex items-center justify-center rounded-full border-8 border-slate-100 dark:border-slate-700 mb-4">
                                        <div className={`text-3xl font-black ${situacaoColor}`}>
                                            {favorabilidade}%
                                        </div>
                                    </div>
                                    <span className={`text-sm font-bold uppercase text-center ${situacaoColor}`}>
                                        {situacaoEstrategica}
                                    </span>
                                    <p className="text-xs text-center text-slate-500 mt-3">
                                        Calculado com base no peso (Importância x Intensidade) das Forças e Oportunidades em relação ao total.
                                    </p>
                                </div>
                            </div>

                            {/* Card de Quantidades */}
                            <div className="bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-100 dark:border-slate-700 p-6">
                                <h3 className="text-base font-bold text-slate-800 dark:text-white mb-4">Métricas</h3>
                                <div className="space-y-3">
                                    <div className="flex justify-between items-center text-sm">
                                        <div className="flex items-center gap-2 text-slate-600 dark:text-slate-400"><Activity className="w-4 h-4" /> Total de Fatores</div>
                                        <div className="font-bold text-slate-800 dark:text-white">{strengths.length + weaknesses.length + opportunities.length + threats.length}</div>
                                    </div>
                                    <div className="flex justify-between items-center text-sm">
                                        <div className="flex items-center gap-2 text-slate-600 dark:text-slate-400"><GitMerge className="w-4 h-4" /> Cruzamentos (Estratégias)</div>
                                        <div className="font-bold text-slate-800 dark:text-white">{cruzamentos.length}</div>
                                    </div>
                                    <div className="flex justify-between items-center text-sm">
                                        <div className="flex items-center gap-2 text-slate-600 dark:text-slate-400"><ListTodo className="w-4 h-4" /> Planos de Ação</div>
                                        <div className="font-bold text-slate-800 dark:text-white">{planos.length}</div>
                                    </div>
                                </div>
                            </div>

                            {/* Card do Histórico/Fluxo */}
                            <div className="bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-100 dark:border-slate-700 p-6">
                                <h3 className="text-base font-bold text-slate-800 dark:text-white mb-4">Fluxo do Processo</h3>

                                <div className="space-y-6 relative before:absolute before:left-3.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-100 dark:before:bg-slate-700">
                                    
                                    {/* Etapa 1: Criação */}
                                    <div className="flex gap-4 relative">
                                        <div className="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-950/20 text-emerald-600 flex items-center justify-center z-10">
                                            <Check className="w-4 h-4" />
                                        </div>
                                        <div>
                                            <p className="text-xs font-bold text-slate-900 dark:text-white">Criado</p>
                                            <p className="text-[10px] text-slate-400">Por: {analise.criador?.name || 'Não identificado'}</p>
                                            <p className="text-[10px] text-slate-400">{analise.created_at ? format(new Date(analise.created_at), 'dd/MM/yyyy HH:mm', { locale: ptBR }) : ''}</p>
                                        </div>
                                    </div>

                                    {/* Etapa 2: Aprovação */}
                                    <div className="flex gap-4 relative">
                                        <div className={`w-7 h-7 rounded-full flex items-center justify-center z-10 ${
                                            analise.status === 'approved' 
                                                ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/20' 
                                                : analise.status === 'rejected'
                                                ? 'bg-rose-100 text-rose-600 dark:bg-rose-950/20'
                                                : 'bg-slate-100 text-slate-400 dark:bg-slate-700'
                                        }`}>
                                            {analise.status === 'approved' ? (
                                                <Check className="w-4 h-4" />
                                            ) : analise.status === 'rejected' ? (
                                                <X className="w-4 h-4" />
                                            ) : (
                                                <Clock className="w-4 h-4" />
                                            )}
                                        </div>
                                        <div>
                                            <p className="text-xs font-bold text-slate-900 dark:text-white">Aprovação do Gestor</p>
                                            <p className="text-[10px] text-slate-400">Aprovador: {analise.aprovador?.name || 'Não atribuído'}</p>
                                            
                                            {analise.status === 'approved' && (
                                                <p className="text-[10px] text-emerald-500 font-semibold mt-1">Aprovado em produção</p>
                                            )}
                                            {analise.status === 'rejected' && (
                                                <div className="mt-1 bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900/50 rounded p-2 text-[10px] text-rose-600 dark:text-rose-400">
                                                    <span className="font-bold flex items-center gap-1">
                                                        <MessageSquare className="w-3 h-3" />
                                                        Rejeitado:
                                                    </span>
                                                    {analise.motivo_rejeicao}
                                                </div>
                                            )}
                                            {analise.status === 'pending_approval' && (
                                                <p className="text-[10px] text-amber-500 font-semibold mt-1">Aguardando decisão</p>
                                            )}
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {/* Modal de Rejeição */}
            {showRejectionModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 p-4 transition-opacity">
                    <div className="bg-white dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700 shadow-2xl max-w-md w-full overflow-hidden">
                        <div className="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
                            <h3 className="text-base font-bold text-slate-900 dark:text-white">Justificativa da Rejeição</h3>
                            <button onClick={() => setShowRejectionModal(false)} className="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-600">
                                <X className="w-5 h-5" />
                            </button>
                        </div>
                        <form onSubmit={handleRejeitar}>
                            <div className="p-6">
                                <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-2">Descreva detalhadamente o motivo da rejeição:</label>
                                <textarea
                                    className="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-sm"
                                    rows="4"
                                    placeholder="Ex.: Necessário reavaliar a ameaça de novos entrantes no mercado..."
                                    value={motivoRejeicao}
                                    onChange={(e) => setMotivoRejeicao(e.target.value)}
                                    required
                                />
                            </div>
                            <div className="px-6 py-4 bg-slate-50 dark:bg-slate-900/40 border-t border-slate-100 dark:border-slate-700 flex justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={() => setShowRejectionModal(false)}
                                    className="px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold shadow-lg shadow-rose-500/10 transition-all"
                                >
                                    Confirmar Rejeição
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
