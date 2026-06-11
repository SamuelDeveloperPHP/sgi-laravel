import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, Search, Eye, Star, Settings, Minus } from 'lucide-react';
import Modal from '@/Components/Modal';

export default function Index({ auth, fornecedores, filters, companies, currentCompanyId, criteriosPadraoDB }) {
    const isMasterAdmin = auth.user.is_master_admin;
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

    // Formulário Padrão Modal
    const [showConfigModal, setShowConfigModal] = useState(false);
    const [criteriosPadrao, setCriteriosPadrao] = useState(
        (criteriosPadraoDB && criteriosPadraoDB.length > 0) ? criteriosPadraoDB : [
            { nome: 'Qualidade (Produto/Serviço)' },
            { nome: 'Prazo de Entrega' },
            { nome: 'Atendimento e Suporte' }
        ]
    );

    // Na hora de salvar:
    const saveConfig = (e) => {
        e.preventDefault();
        router.post(route('fornecedor.criterios.padrao', currentCompanyId), {
            criterios: criteriosPadrao
        }, {
            onSuccess: () => setShowConfigModal(false)
        });
    };

    const handleDelete = (id) => {
        if (confirm('Tem certeza que deseja excluir este fornecedor?')) {
            router.delete(route('fornecedores.destroy', id));
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('fornecedores.index'), { search, status: statusFilter, company_id: currentCompanyId });
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'aprovado': return <span className="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Aprovado</span>;
            case 'pendente': return <span className="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">Em Homologação</span>;
            case 'reprovado': return <span className="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">Reprovado</span>;
            default: return <span className="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded-full">{status}</span>;
        }
    };

    const getCriticidadeColor = (crit) => {
        switch (crit) {
            case 'alta': return 'text-red-600 font-bold';
            case 'media': return 'text-yellow-600 font-bold';
            case 'baixa': return 'text-green-600 font-bold';
            default: return 'text-gray-500';
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Gestão de Fornecedores</h2>}
        >
            <Head title="Fornecedores" />

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
                                onChange={(e) => router.get(route('fornecedores.index'), { company_id: e.target.value, search, status: statusFilter })}
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
                                <form onSubmit={handleSearch} className="flex w-full md:w-2/3 gap-2">
                                    <input
                                        type="text"
                                        placeholder="Buscar fornecedor..."
                                        className="w-1/2 rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                    />
                                    <select
                                        className="w-1/3 rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        value={statusFilter}
                                        onChange={(e) => setStatusFilter(e.target.value)}
                                    >
                                        <option value="">Todos os Status</option>
                                        <option value="aprovado">Aprovado</option>
                                        <option value="pendente">Pendente</option>
                                        <option value="reprovado">Reprovado</option>
                                        <option value="inativo">Inativo</option>
                                    </select>
                                    <button type="submit" className="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">
                                        <Search className="w-4 h-4" />
                                    </button>
                                </form>

                                <div className="flex gap-2">
                                    {auth.user.permissions?.includes('manage-fornecedores') && (
                                        <>
                                            <button
                                                onClick={() => setShowConfigModal(true)}
                                                className="flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition"
                                                title="Configurar Formulário Padrão de Avaliação"
                                            >
                                                <Settings className="w-4 h-4 sm:mr-2" />
                                                <span className="hidden sm:inline">Formulário Padrão</span>
                                            </button>
                                            <Link
                                                href={route('fornecedores.create', { company_id: currentCompanyId })}
                                                className="flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition"
                                            >
                                                <Plus className="w-4 h-4 sm:mr-2" />
                                                <span className="hidden sm:inline">Novo Fornecedor</span>
                                            </Link>
                                        </>
                                    )}
                                </div>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-slate-700">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Fornecedor</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Categoria / Criticidade</th>
                                            <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                            <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">IDF (Nota)</th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200 dark:bg-slate-800 dark:divide-gray-700">
                                        {fornecedores.data.length > 0 ? (
                                            fornecedores.data.map((item) => (
                                                <tr key={item.id}>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        <div className="font-bold">{item.razao_social}</div>
                                                        <div className="text-xs text-gray-500">CNPJ/CPF: {item.cnpj_cpf}</div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                        <div>{item.categoria || '-'}</div>
                                                        <div className={`text-xs uppercase ${getCriticidadeColor(item.criticidade)}`}>{item.criticidade}</div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-center">
                                                        {getStatusBadge(item.status_homologacao)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-center">
                                                        <div className="flex items-center justify-center font-bold text-gray-700 dark:text-gray-300">
                                                            <Star className={`w-4 h-4 mr-1 ${item.idf_atual >= 3.5 ? 'text-yellow-400' : (item.idf_atual > 0 ? 'text-orange-400' : 'text-gray-300')}`} fill="currentColor" />
                                                            {item.idf_atual > 0 ? Number(item.idf_atual).toFixed(2) : 'N/A'}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <div className="flex items-center justify-end gap-3">
                                                            <Link href={route('fornecedores.show', item.id)} className="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" title="Ver Painel">
                                                                <Eye className="w-5 h-5" />
                                                            </Link>
                                                            {auth.user.permissions?.includes('manage-fornecedores') && (
                                                                <>
                                                                    <Link href={route('fornecedores.edit', item.id)} className="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300" title="Editar Dados">
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
                                                    Nenhum fornecedor encontrado.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Paginação */}
                            {fornecedores.links && fornecedores.links.length > 3 && (
                                <div className="mt-6 flex justify-center">
                                    <div className="flex flex-wrap gap-1">
                                        {fornecedores.links.map((link, k) => (
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

            {/* MODAL CONFIGURAÇÃO PADRÃO */}
            <Modal show={showConfigModal} onClose={() => setShowConfigModal(false)} maxWidth="7xl">
                <form onSubmit={saveConfig} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Critérios Padrão de Avaliação</h2>
                    <p className="text-sm text-gray-500 mb-6">
                        Configure os campos de avaliação que serão usados como padrão para todos os fornecedores da empresa atual.
                    </p>

                    <div className="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                        {criteriosPadrao.map((crit, index) => (
                            <div key={index} className="flex items-center gap-2">
                                <input
                                    type="text"
                                    required
                                    className="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Nome do critério (ex: Qualidade)"
                                    value={crit.nome}
                                    onChange={(e) => {
                                        const novos = [...criteriosPadrao];
                                        novos[index].nome = e.target.value;
                                        setCriteriosPadrao(novos);
                                    }}
                                />
                                <button
                                    type="button"
                                    onClick={() => {
                                        const novos = criteriosPadrao.filter((_, i) => i !== index);
                                        setCriteriosPadrao(novos);
                                    }}
                                    className="p-2 bg-red-100 text-red-600 hover:bg-red-200 rounded-md transition-colors"
                                    title="Remover critério"
                                >
                                    <Trash2 className="w-5 h-5" />
                                </button>
                            </div>
                        ))}
                    </div>

                    <div className="mt-6">
                        <button
                            type="button"
                            onClick={() => setCriteriosPadrao([...criteriosPadrao, { nome: '' }])}
                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition"
                        >
                            <Plus className="w-4 h-4 mr-2" /> Adicionar Novo Critério
                        </button>
                    </div>

                    <div className="flex justify-end gap-3 mt-8 pt-4 border-t dark:border-gray-700">
                        <button type="button" onClick={() => setShowConfigModal(false)} className="px-4 py-2 bg-gray-200 text-gray-800 rounded-md font-semibold">Cancelar</button>
                        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-semibold">
                            Salvar Padrão
                        </button>
                    </div>
                </form>
            </Modal>

        </AuthenticatedLayout>
    );
}
