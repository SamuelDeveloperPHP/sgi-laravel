import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, CheckCircle, Clock, XCircle, AlertTriangle, Shield, Check, X, Users, MessageSquare } from 'lucide-react';
import { format } from 'date-fns';
import { ptBR } from 'date-fns/locale';

export default function Show({ auth, mapaRisco }) {
    const user = auth.user;
    const isMasterAdmin = user.is_master_admin;
    const isAprovador = user.id === mapaRisco.aprovador_id || isMasterAdmin;

    const [showRejectionModal, setShowRejectionModal] = useState(false);
    const [motivoRejeicao, setMotivoRejeicao] = useState('');

    const handleEnviarAprovacao = () => {
        if (confirm('Deseja enviar este mapa de risco para aprovação? Ele será bloqueado para edição.')) {
            router.post(route('mapas-risco.enviar-aprovacao', mapaRisco.id));
        }
    };

    const handleAprovar = () => {
        if (confirm('Tem certeza que deseja aprovar este mapa de risco?')) {
            router.post(route('mapas-risco.aprovar', mapaRisco.id));
        }
    };

    const handleRejeitar = (e) => {
        e.preventDefault();
        if (!motivoRejeicao.trim()) {
            alert('Por favor, informe a justificativa da rejeição.');
            return;
        }

        router.post(route('mapas-risco.rejeitar', mapaRisco.id), {
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
                return {
                    label: 'Aprovado',
                    color: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/20 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900',
                    icon: CheckCircle
                };
            case 'pending_approval':
                return {
                    label: 'Aguardando Aprovação',
                    color: 'text-amber-600 bg-amber-50 dark:bg-amber-950/20 dark:text-amber-400 border-amber-200 dark:border-amber-900',
                    icon: Clock
                };
            case 'rejected':
                return {
                    label: 'Rejeitado',
                    color: 'text-rose-600 bg-rose-50 dark:bg-rose-950/20 dark:text-rose-400 border-rose-200 dark:border-rose-900',
                    icon: XCircle
                };
            default:
                return {
                    label: 'Rascunho',
                    color: 'text-slate-600 bg-slate-50 dark:bg-slate-900/50 dark:text-slate-400 border-slate-200 dark:border-slate-800',
                    icon: Shield
                };
        }
    };

    const statusInfo = getStatusDetails(mapaRisco.status);
    const StatusIcon = statusInfo.icon;

    const getGrupoColor = (grupo) => {
        switch (grupo) {
            case 'Físico': return 'bg-emerald-500 text-white border-emerald-600';
            case 'Químico': return 'bg-rose-500 text-white border-rose-600';
            case 'Biológico': return 'bg-amber-800 text-white border-amber-900';
            case 'Ergonômico': return 'bg-yellow-400 text-slate-900 border-yellow-500';
            case 'Acidentes': return 'bg-blue-500 text-white border-blue-600';
            default: return 'bg-slate-500';
        }
    };

    const getGravidadeSize = (gravidade) => {
        switch (gravidade) {
            case 'Pequeno': return 'h-8 w-8 text-xs';
            case 'Médio': return 'h-14 w-14 text-sm';
            case 'Grande': return 'h-20 w-20 text-base font-bold';
            default: return 'h-10 w-10';
        }
    };

    // Estatísticas rápidas
    const pontos = mapaRisco.pontos_risco || [];
    const totalTrabalhadores = pontos.reduce((acc, p) => acc + (p.numero_trabalhadores_expostos || 0), 0);
    const totalRiscos = pontos.length;
    const countPorGrupo = pontos.reduce((acc, p) => {
        acc[p.grupo_risco] = (acc[p.grupo_risco] || 0) + 1;
        return acc;
    }, {});

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Mapeamento de Riscos</h2>}
        >
            <Head title={`Mapeamento: ${mapaRisco.titulo}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    
                    {/* Barra de Ações do Topo */}
                    <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                        <Link
                            href={route('mapas-risco.index')}
                            className="inline-flex items-center text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                        >
                            <ArrowLeft className="w-4 h-4 mr-1.5" />
                            Voltar para Listagem
                        </Link>

                        <div className="flex gap-2 w-full md:w-auto">
                            {(mapaRisco.status === 'draft' || mapaRisco.status === 'rejected') && (
                                <>
                                    <Link
                                        href={route('mapas-risco.edit', mapaRisco.id)}
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

                            {mapaRisco.status === 'pending_approval' && isAprovador && (
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

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        {/* Seção Principal (Esquerda - 2 colunas) */}
                        <div className="lg:col-span-2 space-y-6">
                            
                            {/* Card de Informações Gerais */}
                            <div className="bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-100 dark:border-slate-700 p-6">
                                <div className="flex flex-col sm:flex-row justify-between items-start gap-4 mb-6">
                                    <div>
                                        <h3 className="text-xl font-bold text-slate-900 dark:text-white">{mapaRisco.titulo}</h3>
                                        <p className="text-sm text-slate-500 mt-1">
                                            Setor: <span className="font-semibold text-slate-700 dark:text-slate-300">{mapaRisco.setor}</span>
                                        </p>
                                    </div>
                                    <div className={`inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border font-bold text-sm ${statusInfo.color}`}>
                                        <StatusIcon className="w-4 h-4" />
                                        {statusInfo.label}
                                    </div>
                                </div>

                                {/* Seção Gráfica Premium do Mapa de Risco */}
                                <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-4">
                                    Distribuição Visual dos Riscos Mapeados
                                </h4>
                                <div className="bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 rounded-xl p-8 mb-6 flex flex-wrap justify-center items-center gap-10 min-h-64 shadow-inner">
                                    {pontos.length > 0 ? (
                                        pontos.map((p, i) => (
                                            <div key={i} className="flex flex-col items-center group relative cursor-pointer">
                                                {/* Círculo de Risco de Produção */}
                                                <div 
                                                    className={`rounded-full flex items-center justify-center border font-bold shadow-lg transform group-hover:scale-110 transition-all duration-300 ${getGrupoColor(p.grupo_risco)} ${getGravidadeSize(p.gravidade)}`}
                                                >
                                                    {p.gravidade.charAt(0)}
                                                </div>
                                                <div className="text-[10px] font-semibold text-slate-600 dark:text-slate-400 mt-2 text-center max-w-24 truncate">
                                                    {p.local_detalhado}
                                                </div>

                                                {/* Tooltip Hover Premium */}
                                                <div className="absolute z-10 bottom-full mb-2 hidden group-hover:block bg-slate-900 text-white text-xs rounded-lg p-3 w-56 shadow-xl border border-slate-700 pointer-events-none">
                                                    <p className="font-bold border-b border-slate-700 pb-1 mb-1">{p.local_detalhado}</p>
                                                    <p><span className="text-slate-400">Grupo:</span> {p.grupo_risco}</p>
                                                    <p><span className="text-slate-400">Agente:</span> {p.agente_risco}</p>
                                                    <p><span className="text-slate-400">Gravidade:</span> {p.gravidade}</p>
                                                    <p><span className="text-slate-400">Expostos:</span> {p.numero_trabalhadores_expostos}</p>
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        <div className="text-slate-400 dark:text-slate-500 italic text-sm">
                                            Nenhum ponto de risco registrado.
                                        </div>
                                    )}
                                </div>

                                {/* Quadro de Cores NR-5 */}
                                <div className="grid grid-cols-2 sm:grid-cols-5 gap-2 border-t border-slate-100 dark:border-slate-700 pt-4 text-center">
                                    <div className="flex items-center gap-1.5 text-xs text-slate-500 justify-center">
                                        <span className="w-3 h-3 rounded-full bg-emerald-500"></span> Físico
                                    </div>
                                    <div className="flex items-center gap-1.5 text-xs text-slate-500 justify-center">
                                        <span className="w-3 h-3 rounded-full bg-rose-500"></span> Químico
                                    </div>
                                    <div className="flex items-center gap-1.5 text-xs text-slate-500 justify-center">
                                        <span className="w-3 h-3 rounded-full bg-amber-800"></span> Biológico
                                    </div>
                                    <div className="flex items-center gap-1.5 text-xs text-slate-500 justify-center">
                                        <span className="w-3 h-3 rounded-full bg-yellow-400"></span> Ergonômico
                                    </div>
                                    <div className="flex items-center gap-1.5 text-xs text-slate-500 justify-center">
                                        <span className="w-3 h-3 rounded-full bg-blue-500"></span> Acidentes
                                    </div>
                                </div>

                            </div>

                            {/* Tabela de Detalhes dos Riscos */}
                            <div className="bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-100 dark:border-slate-700 p-6">
                                <h3 className="text-base font-bold text-slate-800 dark:text-white mb-4">Pontos de Risco Cadastrados</h3>
                                <div className="overflow-x-auto rounded-lg border border-slate-100 dark:border-slate-700">
                                    <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                        <thead className="bg-slate-50 dark:bg-slate-900/40">
                                            <tr>
                                                <th className="px-5 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Grupo</th>
                                                <th className="px-5 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Local Detalhado</th>
                                                <th className="px-5 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Agente Causador</th>
                                                <th className="px-5 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Expostos</th>
                                                <th className="px-5 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Medidas Preventivas</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-slate-200 dark:bg-slate-800 dark:divide-slate-700">
                                            {pontos.length > 0 ? (
                                                pontos.map((p, i) => (
                                                    <tr key={i} className="hover:bg-slate-50/50 dark:hover:bg-slate-900/10 transition-colors">
                                                        <td className="px-5 py-4 whitespace-nowrap">
                                                            <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-semibold ${getGrupoColor(p.grupo_risco)}`}>
                                                                {p.grupo_risco}
                                                            </span>
                                                        </td>
                                                        <td className="px-5 py-4 text-xs font-semibold text-slate-950 dark:text-white">
                                                            {p.local_detalhado}
                                                        </td>
                                                        <td className="px-5 py-4 text-xs">
                                                            <div className="font-semibold text-slate-900 dark:text-slate-200">{p.agente_risco}</div>
                                                            <div className="text-slate-400 text-[10px]">Gravidade: {p.gravidade}</div>
                                                        </td>
                                                        <td className="px-5 py-4 text-center text-xs font-semibold text-slate-900 dark:text-slate-200">
                                                            {p.numero_trabalhadores_expostos}
                                                        </td>
                                                        <td className="px-5 py-4 text-xs text-slate-600 dark:text-slate-400 max-w-xs break-words">
                                                            {p.medidas_preventivas || '-'}
                                                        </td>
                                                    </tr>
                                                ))
                                            ) : (
                                                <tr>
                                                    <td colSpan="5" className="px-5 py-6 text-center text-xs text-slate-400 dark:text-slate-500 italic">
                                                        Nenhum detalhe disponível.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>

                        {/* Painel Lateral (Direita - 1 coluna) */}
                        <div className="space-y-6">
                            
                            {/* Card de Estatísticas */}
                            <div className="bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-100 dark:border-slate-700 p-6">
                                <h3 className="text-base font-bold text-slate-800 dark:text-white mb-4">Métricas do Mapa</h3>
                                <div className="space-y-4">
                                    <div className="flex justify-between items-center bg-slate-50 dark:bg-slate-900 p-3 rounded-lg">
                                        <div className="text-xs text-slate-500">Total de Agentes</div>
                                        <div className="text-sm font-bold text-slate-900 dark:text-white">{totalRiscos}</div>
                                    </div>
                                    <div className="flex justify-between items-center bg-slate-50 dark:bg-slate-900 p-3 rounded-lg">
                                        <div className="text-xs text-slate-500">Colaboradores Expostos</div>
                                        <div className="text-sm font-bold text-slate-900 dark:text-white">{totalTrabalhadores}</div>
                                    </div>

                                    <div className="border-t border-slate-100 dark:border-slate-700 pt-4">
                                        <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Por Categoria</h4>
                                        <div className="space-y-2">
                                            {Object.entries(countPorGrupo).map(([grupo, count]) => (
                                                <div key={grupo} className="flex justify-between items-center text-xs">
                                                    <div className="flex items-center gap-1.5">
                                                        <span className={`w-2 h-2 rounded-full ${getGrupoColor(grupo).split(' ')[0]}`}></span>
                                                        {grupo}
                                                    </div>
                                                    <div className="font-bold text-slate-800 dark:text-slate-200">{count}</div>
                                                </div>
                                            ))}
                                        </div>
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
                                            <p className="text-[10px] text-slate-400">Por: {mapaRisco.criador?.name || 'Não identificado'}</p>
                                            <p className="text-[10px] text-slate-400">{mapaRisco.created_at ? format(new Date(mapaRisco.created_at), 'dd/MM/yyyy HH:mm', { locale: ptBR }) : ''}</p>
                                        </div>
                                    </div>

                                    {/* Etapa 2: Aprovação */}
                                    <div className="flex gap-4 relative">
                                        <div className={`w-7 h-7 rounded-full flex items-center justify-center z-10 ${
                                            mapaRisco.status === 'approved' 
                                                ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/20' 
                                                : mapaRisco.status === 'rejected'
                                                ? 'bg-rose-100 text-rose-600 dark:bg-rose-950/20'
                                                : 'bg-slate-100 text-slate-400 dark:bg-slate-700'
                                        }`}>
                                            {mapaRisco.status === 'approved' ? (
                                                <Check className="w-4 h-4" />
                                            ) : mapaRisco.status === 'rejected' ? (
                                                <X className="w-4 h-4" />
                                            ) : (
                                                <Clock className="w-4 h-4" />
                                            )}
                                        </div>
                                        <div>
                                            <p className="text-xs font-bold text-slate-900 dark:text-white">Aprovação do Gestor</p>
                                            <p className="text-[10px] text-slate-400">Aprovador: {mapaRisco.aprovador?.name || 'Não atribuído'}</p>
                                            
                                            {mapaRisco.status === 'approved' && (
                                                <p className="text-[10px] text-emerald-500 font-semibold mt-1">Aprovado em produção</p>
                                            )}
                                            {mapaRisco.status === 'rejected' && (
                                                <div className="mt-1 bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900/50 rounded p-2 text-[10px] text-rose-600 dark:text-rose-400">
                                                    <span className="font-bold flex items-center gap-1">
                                                        <MessageSquare className="w-3 h-3" />
                                                        Rejeitado:
                                                    </span>
                                                    {mapaRisco.motivo_rejeicao}
                                                </div>
                                            )}
                                            {mapaRisco.status === 'pending_approval' && (
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
                                    placeholder="Ex.: Necessário reavaliar a gravidade do ruído no torno mecânico 04..."
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
