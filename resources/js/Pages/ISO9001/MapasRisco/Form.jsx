import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Trash2, ArrowLeft, Save, AlertTriangle, Shield, Users } from 'lucide-react';

export default function Form({ auth, mapaRisco, users, companies, companyId }) {
    const isEdit = !!mapaRisco;

    const { data, setData, post, put, processing, errors } = useForm({
        company_id: companyId || mapaRisco?.company_id || '',
        titulo: mapaRisco?.titulo || '',
        setor: mapaRisco?.setor || '',
        aprovador_id: mapaRisco?.aprovador_id || '',
        data_mapeamento: mapaRisco?.data_mapeamento ? mapaRisco.data_mapeamento.split('T')[0] : '',
        pontos_risco: mapaRisco?.pontos_risco || [],
    });

    // Estado do ponto que está sendo editado
    const [ponto, setPonto] = useState({
        local_detalhado: '',
        grupo_risco: 'Físico', // Físico, Químico, Biológico, Ergonômico, Acidentes
        agente_risco: '',
        gravidade: 'Pequeno', // Pequeno, Médio, Grande
        numero_trabalhadores_expostos: 0,
        medidas_preventivas: '',
    });

    const addPonto = () => {
        if (!ponto.local_detalhado || !ponto.agente_risco) {
            alert('Por favor, preencha o local detalhado e o agente de risco.');
            return;
        }

        setData('pontos_risco', [...data.pontos_risco, { ...ponto }]);
        setPonto({
            local_detalhado: '',
            grupo_risco: 'Físico',
            agente_risco: '',
            gravidade: 'Pequeno',
            numero_trabalhadores_expostos: 0,
            medidas_preventivas: '',
        });
    };

    const removePonto = (index) => {
        setData('pontos_risco', data.pontos_risco.filter((_, i) => i !== index));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (data.pontos_risco.length === 0) {
            alert('Adicione pelo menos um ponto de risco ao mapeamento.');
            return;
        }

        if (isEdit) {
            put(route('mapas-risco.update', mapaRisco.id));
        } else {
            post(route('mapas-risco.store'));
        }
    };

    const getGrupoColor = (grupo) => {
        switch (grupo) {
            case 'Físico': return 'bg-emerald-500 text-white border-emerald-600'; // Verde
            case 'Químico': return 'bg-rose-500 text-white border-rose-600'; // Vermelho
            case 'Biológico': return 'bg-amber-800 text-white border-amber-900'; // Marrom (Use amber-800/amber-900 for dark brown appearance)
            case 'Ergonômico': return 'bg-yellow-400 text-slate-900 border-yellow-500'; // Amarelo
            case 'Acidentes': return 'bg-blue-500 text-white border-blue-600'; // Azul
            default: return 'bg-slate-500';
        }
    };

    const getGravidadeSize = (gravidade) => {
        switch (gravidade) {
            case 'Pequeno': return 'h-6 w-6 text-xs';
            case 'Médio': return 'h-10 w-10 text-sm';
            case 'Grande': return 'h-14 w-14 text-base font-bold';
            default: return 'h-8 w-8';
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {isEdit ? 'Editar Mapa de Risco' : 'Novo Mapa de Risco'}
                </h2>
            }
        >
            <Head title={isEdit ? 'Editar Mapa de Risco' : 'Novo Mapa de Risco'} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link
                            href={route('mapas-risco.index')}
                            className="inline-flex items-center text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                        >
                            <ArrowLeft className="w-4 h-4 mr-1.5" />
                            Voltar para Listagem
                        </Link>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        
                        {/* Seção 1: Dados do Mapeamento */}
                        <div className="bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-100 dark:border-slate-700 p-6">
                            <h3 className="text-base font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                                <Shield className="w-5 h-5 text-indigo-500" />
                                Informações Básicas
                            </h3>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Título do Mapa</label>
                                    <input
                                        type="text"
                                        placeholder="Ex.: Mapeamento Geral do Galpão A"
                                        className="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        value={data.titulo}
                                        onChange={(e) => setData('titulo', e.target.value)}
                                        required
                                    />
                                    {errors.titulo && <p className="mt-1 text-xs text-rose-500">{errors.titulo}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Setor</label>
                                    <input
                                        type="text"
                                        placeholder="Ex.: Manutenção, Administrativo, Produção"
                                        className="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        value={data.setor}
                                        onChange={(e) => setData('setor', e.target.value)}
                                        required
                                    />
                                    {errors.setor && <p className="mt-1 text-xs text-rose-500">{errors.setor}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Aprovador do Fluxo</label>
                                    <select
                                        className="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        value={data.aprovador_id}
                                        onChange={(e) => setData('aprovador_id', e.target.value)}
                                        required
                                    >
                                        <option value="">Selecione o usuário aprovador...</option>
                                        {users.map(user => (
                                            <option key={user.id} value={user.id}>{user.name} ({user.email})</option>
                                        ))}
                                    </select>
                                    {errors.aprovador_id && <p className="mt-1 text-xs text-rose-500">{errors.aprovador_id}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Data do Mapeamento</label>
                                    <input
                                        type="date"
                                        className="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        value={data.data_mapeamento}
                                        onChange={(e) => setData('data_mapeamento', e.target.value)}
                                        required
                                    />
                                    {errors.data_mapeamento && <p className="mt-1 text-xs text-rose-500">{errors.data_mapeamento}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Seção 2: Adicionar Riscos (Pontos) */}
                        <div className="bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-100 dark:border-slate-700 p-6">
                            <h3 className="text-base font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                                <AlertTriangle className="w-5 h-5 text-amber-500" />
                                Mapeamento de Pontos de Risco (NR-5)
                            </h3>

                            <div className="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-5 border border-slate-100 dark:border-slate-800 mb-6">
                                <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-4">Adicionar Ponto de Risco</h4>
                                
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
                                    <div>
                                        <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Local Detalhado / Máquina</label>
                                        <input
                                            type="text"
                                            placeholder="Ex.: Próximo à Prensa 02"
                                            className="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs"
                                            value={ponto.local_detalhado}
                                            onChange={(e) => setPonto({ ...ponto, local_detalhado: e.target.value })}
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Grupo de Risco (Cor NR-5)</label>
                                        <select
                                            className="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs"
                                            value={ponto.grupo_risco}
                                            onChange={(e) => setPonto({ ...ponto, grupo_risco: e.target.value })}
                                        >
                                            <option value="Físico">Físico (Verde)</option>
                                            <option value="Químico">Químico (Vermelho)</option>
                                            <option value="Biológico">Biológico (Marrom)</option>
                                            <option value="Ergonômico">Ergonômico (Amarelo)</option>
                                            <option value="Acidentes">Acidentes (Azul)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Agente de Risco / Descrição</label>
                                        <input
                                            type="text"
                                            placeholder="Ex.: Ruído Contínuo, Postura Inadequada"
                                            className="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs"
                                            value={ponto.agente_risco}
                                            onChange={(e) => setPonto({ ...ponto, agente_risco: e.target.value })}
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Gravidade (Tamanho do Círculo)</label>
                                        <select
                                            className="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs"
                                            value={ponto.gravidade}
                                            onChange={(e) => setPonto({ ...ponto, gravidade: e.target.value })}
                                        >
                                            <option value="Pequeno">Pequena</option>
                                            <option value="Médio">Média</option>
                                            <option value="Grande">Grande</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Qtd. Trabalhadores Expostos</label>
                                        <input
                                            type="number"
                                            min="0"
                                            className="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs"
                                            value={ponto.numero_trabalhadores_expostos}
                                            onChange={(e) => setPonto({ ...ponto, numero_trabalhadores_expostos: parseInt(e.target.value) || 0 })}
                                        />
                                    </div>

                                    <div className="md:col-span-3">
                                        <label className="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Medidas Preventivas Propostas</label>
                                        <textarea
                                            placeholder="Ex.: Uso obrigatório de protetor auricular tipo concha, treinamento de ergonomia..."
                                            rows="2"
                                            className="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs"
                                            value={ponto.medidas_preventivas}
                                            onChange={(e) => setPonto({ ...ponto, medidas_preventivas: e.target.value })}
                                        />
                                    </div>
                                </div>

                                <div className="flex justify-end">
                                    <button
                                        type="button"
                                        onClick={addPonto}
                                        className="inline-flex items-center px-4 py-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 rounded-lg text-xs font-semibold hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors"
                                    >
                                        <Plus className="w-3.5 h-3.5 mr-1.5" />
                                        Inserir no Mapa
                                    </button>
                                </div>
                            </div>

                            {/* Tabela de Riscos Inseridos */}
                            <div className="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-800">
                                <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                    <thead className="bg-slate-50 dark:bg-slate-900/40">
                                        <tr>
                                            <th className="px-5 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Visual</th>
                                            <th className="px-5 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Local</th>
                                            <th className="px-5 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Agente (Grupo)</th>
                                            <th className="px-5 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Trabalhadores</th>
                                            <th className="px-5 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Medidas Propostas</th>
                                            <th className="px-5 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-slate-200 dark:bg-slate-800 dark:divide-slate-700">
                                        {data.pontos_risco.length > 0 ? (
                                            data.pontos_risco.map((p, i) => (
                                                <tr key={i} className="hover:bg-slate-50/50 dark:hover:bg-slate-900/10">
                                                    <td className="px-5 py-4 whitespace-nowrap">
                                                        <div className="flex items-center justify-center">
                                                            <div 
                                                                className={`rounded-full flex items-center justify-center border font-bold shadow-md ${getGrupoColor(p.grupo_risco)} ${getGravidadeSize(p.gravidade)}`}
                                                                title={`Risco ${p.grupo_risco} - ${p.gravidade}`}
                                                            >
                                                                {p.gravidade.charAt(0)}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-5 py-4 text-xs font-semibold text-slate-950 dark:text-white">
                                                        {p.local_detalhado}
                                                    </td>
                                                    <td className="px-5 py-4 text-xs">
                                                        <div className="font-semibold text-slate-900 dark:text-slate-200">{p.agente_risco}</div>
                                                        <div className="text-slate-400 text-[10px]">{p.grupo_risco}</div>
                                                    </td>
                                                    <td className="px-5 py-4 text-center text-xs font-semibold text-slate-900 dark:text-slate-200">
                                                        {p.numero_trabalhadores_expostos}
                                                    </td>
                                                    <td className="px-5 py-4 text-xs text-slate-500 dark:text-slate-400 max-w-xs truncate" title={p.medidas_preventivas}>
                                                        {p.medidas_preventivas || '-'}
                                                    </td>
                                                    <td className="px-5 py-4 whitespace-nowrap text-right text-xs font-medium">
                                                        <button
                                                            type="button"
                                                            onClick={() => removePonto(i)}
                                                            className="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded transition-colors"
                                                        >
                                                            <Trash2 className="w-4 h-4" />
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan="6" className="px-5 py-8 text-center text-xs text-slate-400 dark:text-slate-500 italic">
                                                    Nenhum ponto de risco adicionado. Adicione no formulário acima.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {/* Botões de Gravação */}
                        <div className="flex justify-end gap-3">
                            <Link
                                href={route('mapas-risco.index')}
                                className="px-5 py-2.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-sm font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                            >
                                Cancelar
                            </Link>

                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center px-5 py-2.5 bg-indigo-600 dark:bg-indigo-500 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 dark:hover:bg-indigo-600 shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 transition-all duration-200 disabled:opacity-55"
                            >
                                <Save className="w-4 h-4 mr-2" />
                                {isEdit ? 'Atualizar Rascunho' : 'Salvar Rascunho'}
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
