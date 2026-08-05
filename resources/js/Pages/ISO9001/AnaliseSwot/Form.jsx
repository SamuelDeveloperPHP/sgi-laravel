import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Trash2, ArrowLeft, Save, Grid, Shield, GitMerge, ListTodo, Activity } from 'lucide-react';

export default function Form({ auth, analise, users, companies, companyId }) {
    const isEdit = !!analise;

    const { data, setData, post, put, processing, errors } = useForm({
        company_id: companyId || analise?.company_id || '',
        titulo: analise?.titulo || '',
        objetivo_estrategico: analise?.objetivo_estrategico || '',
        data_analise: analise?.data_analise ? analise.data_analise.split('T')[0] : '',
        aprovador_id: analise?.aprovador_id || '',
        strengths: analise?.strengths || [],
        weaknesses: analise?.weaknesses || [],
        opportunities: analise?.opportunities || [],
        threats: analise?.threats || [],
        cruzamentos: analise?.cruzamentos || [],
        planos_acao: analise?.planos_acao || [],
        conclusao: analise?.conclusao || '',
    });

    const [activeTab, setActiveTab] = useState('fatores');

    // Inputs temporários para fatores
    const [swotInput, setSwotInput] = useState({
        S: { nome: '', importancia: 3, intensidade: 3 },
        W: { nome: '', importancia: 3, intensidade: 3 },
        O: { nome: '', importancia: 3, intensidade: 3 },
        T: { nome: '', importancia: 3, intensidade: 3 }
    });

    const addItem = (category, field) => {
        if (!swotInput[category].nome.trim()) return;
        const pontuacao = Number(swotInput[category].importancia) * Number(swotInput[category].intensidade);
        const newItem = {
            id: Date.now().toString(),
            nome: swotInput[category].nome.trim(),
            importancia: Number(swotInput[category].importancia),
            intensidade: Number(swotInput[category].intensidade),
            pontuacao
        };
        setData(field, [...data[field], newItem]);
        setSwotInput({ ...swotInput, [category]: { nome: '', importancia: 3, intensidade: 3 } });
    };

    const removeItem = (id, field) => {
        setData(field, data[field].filter((item) => item.id !== id));
    };

    // Cruzamentos temporários
    const [cruzInput, setCruzInput] = useState({
        tipo: 'S_O',
        fator_interno_id: '',
        fator_externo_id: '',
        estrategia: ''
    });

    const addCruzamento = () => {
        if (!cruzInput.fator_interno_id || !cruzInput.fator_externo_id || !cruzInput.estrategia.trim()) return;
        setData('cruzamentos', [...data.cruzamentos, { ...cruzInput, id: Date.now().toString() }]);
        setCruzInput({ ...cruzInput, estrategia: '' }); // resetar apenas a estrategia
    };

    const removeCruzamento = (id) => {
        setData('cruzamentos', data.cruzamentos.filter((c) => c.id !== id));
    };

    // Planos de Ação temporários
    const [planoInput, setPlanoInput] = useState({
        acao: '',
        responsavel: '',
        prazo: '',
        status: 'Não Iniciado'
    });

    const addPlano = () => {
        if (!planoInput.acao.trim()) return;
        setData('planos_acao', [...data.planos_acao, { ...planoInput, id: Date.now().toString() }]);
        setPlanoInput({ acao: '', responsavel: '', prazo: '', status: 'Não Iniciado' });
    };

    const removePlano = (id) => {
        setData('planos_acao', data.planos_acao.filter((p) => p.id !== id));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route('analise-swot.update', analise.id));
        } else {
            post(route('analise-swot.store'));
        }
    };

    const getFatorNome = (id, tipo) => {
        const todos = [...data.strengths, ...data.weaknesses, ...data.opportunities, ...data.threats];
        const f = todos.find(f => f.id === id);
        return f ? f.nome : 'Fator removido';
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                    {isEdit ? 'Editar Análise SWOT' : 'Nova Análise SWOT'}
                </h2>
            }
        >
            <Head title={isEdit ? 'Editar Análise SWOT' : 'Nova Análise SWOT'} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link
                            href={route('analise-swot.index')}
                            className="inline-flex items-center text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                        >
                            <ArrowLeft className="w-4 h-4 mr-1.5" />
                            Voltar para Listagem
                        </Link>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        
                        {/* Seção 1: Dados Base */}
                        <div className="bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-100 dark:border-slate-700 p-6">
                            <h3 className="text-base font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                                <Shield className="w-5 h-5 text-indigo-500" />
                                Informações Básicas
                            </h3>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Título da Análise</label>
                                    <input
                                        type="text"
                                        placeholder="Ex.: Planejamento Estratégico 2026"
                                        className="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        value={data.titulo}
                                        onChange={(e) => setData('titulo', e.target.value)}
                                        required
                                    />
                                    {errors.titulo && <p className="mt-1 text-xs text-rose-500">{errors.titulo}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Objetivo Estratégico</label>
                                    <input
                                        type="text"
                                        placeholder="Ex.: Estabelecer a empresa como autoridade..."
                                        className="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        value={data.objetivo_estrategico}
                                        onChange={(e) => setData('objetivo_estrategico', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Data da Análise</label>
                                    <input
                                        type="date"
                                        className="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        value={data.data_analise}
                                        onChange={(e) => setData('data_analise', e.target.value)}
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Aprovador</label>
                                    <select
                                        className="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        value={data.aprovador_id}
                                        onChange={(e) => setData('aprovador_id', e.target.value)}
                                    >
                                        <option value="">Selecione o usuário aprovador (opcional)...</option>
                                        {users.map(user => (
                                            <option key={user.id} value={user.id}>{user.name} ({user.email})</option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                        </div>

                        {/* Abas */}
                        <div className="bg-white dark:bg-slate-800 shadow-sm rounded-xl border border-slate-100 dark:border-slate-700 overflow-hidden">
                            <div className="flex border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
                                <button type="button" onClick={() => setActiveTab('fatores')} className={`flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 transition-colors ${activeTab === 'fatores' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-white dark:bg-slate-800' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'}`}>
                                    <Activity className="w-4 h-4" />
                                    1. Fatores SWOT
                                </button>
                                <button type="button" onClick={() => setActiveTab('cruzamentos')} className={`flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 transition-colors ${activeTab === 'cruzamentos' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-white dark:bg-slate-800' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'}`}>
                                    <GitMerge className="w-4 h-4" />
                                    2. Cruzamentos TOWS
                                </button>
                                <button type="button" onClick={() => setActiveTab('planos')} className={`flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 transition-colors ${activeTab === 'planos' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-white dark:bg-slate-800' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'}`}>
                                    <ListTodo className="w-4 h-4" />
                                    3. Planos de Ação
                                </button>
                            </div>

                            <div className="p-6">
                                {/* TAB: FATORES */}
                                {activeTab === 'fatores' && (
                                    <div className="grid grid-cols-1 xl:grid-cols-2 gap-8">
                                        {[
                                            { id: 'S', field: 'strengths', title: 'Forças', color: 'emerald' },
                                            { id: 'W', field: 'weaknesses', title: 'Fraquezas', color: 'rose' },
                                            { id: 'O', field: 'opportunities', title: 'Oportunidades', color: 'blue' },
                                            { id: 'T', field: 'threats', title: 'Ameaças', color: 'amber' }
                                        ].map((quad) => (
                                            <div key={quad.id} className={`bg-${quad.color}-50 dark:bg-${quad.color}-900/10 border-t-4 border-${quad.color}-500 rounded-b-xl p-5`}>
                                                <h4 className={`text-sm font-bold text-${quad.color}-800 dark:text-${quad.color}-400 mb-4 uppercase`}>{quad.title}</h4>
                                                
                                                <div className="flex gap-2 mb-4 items-center flex-wrap sm:flex-nowrap">
                                                    <input
                                                        type="text"
                                                        className={`w-full sm:flex-1 rounded-md border-${quad.color}-200 dark:border-${quad.color}-800/50 dark:bg-slate-900 dark:text-white text-sm`}
                                                        placeholder="Item..."
                                                        value={swotInput[quad.id].nome}
                                                        onChange={(e) => setSwotInput({ ...swotInput, [quad.id]: { ...swotInput[quad.id], nome: e.target.value } })}
                                                    />
                                                    <div className="flex gap-2">
                                                        <select
                                                            title="Importância (1 a 5)"
                                                            className={`w-20 rounded-md border-${quad.color}-200 dark:border-${quad.color}-800/50 dark:bg-slate-900 dark:text-white text-sm`}
                                                            value={swotInput[quad.id].importancia}
                                                            onChange={(e) => setSwotInput({ ...swotInput, [quad.id]: { ...swotInput[quad.id], importancia: e.target.value } })}
                                                        >
                                                            {[1,2,3,4,5].map(v => <option key={v} value={v}>Imp: {v}</option>)}
                                                        </select>
                                                        <select
                                                            title="Intensidade (1 a 5)"
                                                            className={`w-20 rounded-md border-${quad.color}-200 dark:border-${quad.color}-800/50 dark:bg-slate-900 dark:text-white text-sm`}
                                                            value={swotInput[quad.id].intensidade}
                                                            onChange={(e) => setSwotInput({ ...swotInput, [quad.id]: { ...swotInput[quad.id], intensidade: e.target.value } })}
                                                        >
                                                            {[1,2,3,4,5].map(v => <option key={v} value={v}>Int: {v}</option>)}
                                                        </select>
                                                        <button type="button" onClick={() => addItem(quad.id, quad.field)} className={`px-3 py-2 bg-${quad.color}-600 text-white rounded-md hover:bg-${quad.color}-700`}>
                                                            <Plus className="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </div>

                                                <ul className="space-y-2">
                                                    {data[quad.field].map((item) => (
                                                        <li key={item.id} className={`flex justify-between items-center bg-white dark:bg-slate-800 p-2.5 rounded shadow-sm border border-${quad.color}-100 dark:border-${quad.color}-900/50`}>
                                                            <div className="flex flex-col">
                                                                <span className="text-sm font-semibold text-slate-700 dark:text-slate-200">{item.nome}</span>
                                                                <span className="text-xs text-slate-500">Imp: {item.importancia} | Int: {item.intensidade} | <strong className={`text-${quad.color}-600`}>Score: {item.pontuacao}</strong></span>
                                                            </div>
                                                            <button type="button" onClick={() => removeItem(item.id, quad.field)} className="text-rose-400 hover:text-rose-600"><Trash2 className="w-4 h-4" /></button>
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                {/* TAB: CRUZAMENTOS */}
                                {activeTab === 'cruzamentos' && (
                                    <div className="space-y-6">
                                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 items-end bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700">
                                            <div>
                                                <label className="block text-xs font-medium text-slate-500 mb-1">Tipo de Estratégia</label>
                                                <select
                                                    className="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm"
                                                    value={cruzInput.tipo}
                                                    onChange={(e) => setCruzInput({ ...cruzInput, tipo: e.target.value, fator_interno_id: '', fator_externo_id: '' })}
                                                >
                                                    <option value="S_O">Força x Oportunidade (Ofensiva)</option>
                                                    <option value="S_T">Força x Ameaça (Confronto)</option>
                                                    <option value="W_O">Fraqueza x Oportunidade (Reforço)</option>
                                                    <option value="W_T">Fraqueza x Ameaça (Defesa)</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label className="block text-xs font-medium text-slate-500 mb-1">Fator Interno</label>
                                                <select
                                                    className="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm"
                                                    value={cruzInput.fator_interno_id}
                                                    onChange={(e) => setCruzInput({ ...cruzInput, fator_interno_id: e.target.value })}
                                                >
                                                    <option value="">Selecione...</option>
                                                    {(cruzInput.tipo.startsWith('S') ? data.strengths : data.weaknesses).map(f => (
                                                        <option key={f.id} value={f.id}>{f.nome}</option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div>
                                                <label className="block text-xs font-medium text-slate-500 mb-1">Fator Externo</label>
                                                <select
                                                    className="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm"
                                                    value={cruzInput.fator_externo_id}
                                                    onChange={(e) => setCruzInput({ ...cruzInput, fator_externo_id: e.target.value })}
                                                >
                                                    <option value="">Selecione...</option>
                                                    {(cruzInput.tipo.endsWith('O') ? data.opportunities : data.threats).map(f => (
                                                        <option key={f.id} value={f.id}>{f.nome}</option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div>
                                                <label className="block text-xs font-medium text-slate-500 mb-1">Diretriz Estratégica</label>
                                                <div className="flex gap-2">
                                                    <input
                                                        type="text"
                                                        className="flex-1 rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm"
                                                        placeholder="Qual ação tomar?"
                                                        value={cruzInput.estrategia}
                                                        onChange={(e) => setCruzInput({ ...cruzInput, estrategia: e.target.value })}
                                                    />
                                                    <button type="button" onClick={addCruzamento} className="px-3 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                                        <Plus className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="space-y-3">
                                            {data.cruzamentos.map((cruz) => (
                                                <div key={cruz.id} className="flex justify-between items-center bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                                                    <div className="flex flex-col">
                                                        <span className="text-xs font-bold text-indigo-500 mb-1">{cruz.tipo.replace('_', ' x ')}</span>
                                                        <span className="text-sm text-slate-500 dark:text-slate-400">
                                                            <strong>Interno:</strong> {getFatorNome(cruz.fator_interno_id)} | <strong>Externo:</strong> {getFatorNome(cruz.fator_externo_id)}
                                                        </span>
                                                        <span className="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-1">{cruz.estrategia}</span>
                                                    </div>
                                                    <button type="button" onClick={() => removeCruzamento(cruz.id)} className="text-rose-400 hover:text-rose-600 p-2"><Trash2 className="w-4 h-4" /></button>
                                                </div>
                                            ))}
                                            {data.cruzamentos.length === 0 && <p className="text-sm text-slate-500 text-center py-4">Nenhum cruzamento definido.</p>}
                                        </div>
                                    </div>
                                )}

                                {/* TAB: PLANOS DE AÇÃO */}
                                {activeTab === 'planos' && (
                                    <div className="space-y-6">
                                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 items-end bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700">
                                            <div className="md:col-span-2">
                                                <label className="block text-xs font-medium text-slate-500 mb-1">Ação</label>
                                                <input
                                                    type="text"
                                                    className="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm"
                                                    placeholder="O que deve ser feito?"
                                                    value={planoInput.acao}
                                                    onChange={(e) => setPlanoInput({ ...planoInput, acao: e.target.value })}
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-xs font-medium text-slate-500 mb-1">Responsável</label>
                                                <input
                                                    type="text"
                                                    className="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm"
                                                    placeholder="Quem?"
                                                    value={planoInput.responsavel}
                                                    onChange={(e) => setPlanoInput({ ...planoInput, responsavel: e.target.value })}
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-xs font-medium text-slate-500 mb-1">Prazo</label>
                                                <div className="flex gap-2">
                                                    <input
                                                        type="date"
                                                        className="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm"
                                                        value={planoInput.prazo}
                                                        onChange={(e) => setPlanoInput({ ...planoInput, prazo: e.target.value })}
                                                    />
                                                    <button type="button" onClick={addPlano} className="px-3 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                                        <Plus className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="overflow-x-auto">
                                            <table className="w-full text-left border-collapse">
                                                <thead>
                                                    <tr className="border-b border-slate-200 dark:border-slate-700 text-sm text-slate-500 dark:text-slate-400">
                                                        <th className="pb-2 font-medium">Ação</th>
                                                        <th className="pb-2 font-medium">Responsável</th>
                                                        <th className="pb-2 font-medium">Prazo</th>
                                                        <th className="pb-2 font-medium">Status</th>
                                                        <th className="pb-2 font-medium text-right">Opções</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {data.planos_acao.map((plano) => (
                                                        <tr key={plano.id} className="border-b border-slate-100 dark:border-slate-800 text-sm">
                                                            <td className="py-3 font-medium text-slate-800 dark:text-slate-200">{plano.acao}</td>
                                                            <td className="py-3 text-slate-600 dark:text-slate-400">{plano.responsavel}</td>
                                                            <td className="py-3 text-slate-600 dark:text-slate-400">{plano.prazo}</td>
                                                            <td className="py-3 text-slate-600 dark:text-slate-400">
                                                                <select 
                                                                    className="text-xs rounded border-slate-200 dark:border-slate-700 dark:bg-slate-900 py-1"
                                                                    value={plano.status}
                                                                    onChange={(e) => {
                                                                        const updated = data.planos_acao.map(p => p.id === plano.id ? { ...p, status: e.target.value } : p);
                                                                        setData('planos_acao', updated);
                                                                    }}
                                                                >
                                                                    <option value="Não Iniciado">Não Iniciado</option>
                                                                    <option value="Em Andamento">Em Andamento</option>
                                                                    <option value="Concluído">Concluído</option>
                                                                    <option value="Atrasado">Atrasado</option>
                                                                </select>
                                                            </td>
                                                            <td className="py-3 text-right">
                                                                <button type="button" onClick={() => removePlano(plano.id)} className="text-rose-400 hover:text-rose-600"><Trash2 className="w-4 h-4" /></button>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                    {data.planos_acao.length === 0 && (
                                                        <tr><td colSpan="5" className="text-center py-4 text-slate-500">Nenhum plano de ação adicionado.</td></tr>
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Botões de Gravação */}
                        <div className="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                            <Link
                                href={route('analise-swot.index')}
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
                                {isEdit ? 'Atualizar Análise' : 'Salvar Análise'}
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
