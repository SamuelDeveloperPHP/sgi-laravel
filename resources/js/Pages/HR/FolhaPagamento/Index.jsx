import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { CheckCircle, Calendar, Plus, Filter } from 'lucide-react';
import Pagination from '@/Components/Pagination';

export default function FolhaPagamentoIndex({ auth, folhas, companies, filters }) {
    const [isModalOpen, setIsModalOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        company_id: '',
        competencia: new Date().toISOString().slice(0, 7), // YYYY-MM
    });

    const handleFilter = (e) => {
        router.get(route('admin.hr.folha-pagamento.index'), { competencia: e.target.value }, { preserveState: true });
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.hr.folha-pagamento.store'), {
            onSuccess: () => {
                setIsModalOpen(false);
                reset();
            },
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Folha de Pagamento (Competências)</h2>}
        >
            <Head title="Folha de Pagamento" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                            <div className="flex items-center gap-2">
                                <Filter className="text-gray-400 w-5 h-5" />
                                <input
                                    type="month"
                                    defaultValue={filters.competencia || ''}
                                    onChange={handleFilter}
                                    className="border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                                {filters.competencia && (
                                    <button 
                                        onClick={() => router.get(route('admin.hr.folha-pagamento.index'))} 
                                        className="text-sm text-red-600 hover:text-red-900"
                                    >
                                        Limpar
                                    </button>
                                )}
                            </div>
                            
                            <button
                                onClick={() => setIsModalOpen(true)}
                                className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring ring-green-300 transition ease-in-out duration-150 gap-2"
                            >
                                <CheckCircle className="w-4 h-4" /> Fechar Folha Mensal
                            </button>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left text-gray-500">
                                <thead className="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                                    <tr>
                                        <th className="px-6 py-3">Competência</th>
                                        <th className="px-6 py-3">Funcionário</th>
                                        <th className="px-6 py-3 text-right">Proventos (R$)</th>
                                        <th className="px-6 py-3 text-right">Descontos (R$)</th>
                                        <th className="px-6 py-3 text-right">Líquido (R$)</th>
                                        <th className="px-6 py-3 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {folhas.data.length > 0 ? (
                                        folhas.data.map((folha) => (
                                            <tr key={folha.id} className="bg-white border-b hover:bg-gray-50">
                                                <td className="px-6 py-4 font-bold text-gray-900">{folha.competencia}</td>
                                                <td className="px-6 py-4">
                                                    <div>{folha.funcionario?.nome}</div>
                                                    <div className="text-xs text-gray-400">{folha.funcionario?.cpf || folha.funcionario?.matricula}</div>
                                                </td>
                                                <td className="px-6 py-4 text-right text-green-600">
                                                    {Number(folha.total_proventos).toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                                                </td>
                                                <td className="px-6 py-4 text-right text-red-600">
                                                    {Number(folha.total_descontos).toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                                                </td>
                                                <td className="px-6 py-4 text-right font-bold text-gray-900">
                                                    {Number(folha.salario_liquido).toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                                                </td>
                                                <td className="px-6 py-4 text-center">
                                                    <span className="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                                        {folha.status}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="6" className="px-6 py-4 text-center text-gray-500">
                                                Nenhum registro de folha encontrado.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <div className="mt-4">
                            <Pagination links={folhas.links} />
                        </div>
                    </div>
                </div>
            </div>

            {/* Modal Fechar Folha */}
            {isModalOpen && (
                <div className="fixed inset-0 z-50 overflow-y-auto">
                    <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={() => setIsModalOpen(false)}></div>
                        <span className="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                        <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <form onSubmit={submit}>
                                <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                        Fechar Folha de Pagamento
                                    </h3>
                                    <p className="text-sm text-gray-500 mb-4">
                                        Esta ação irá capturar os salários base atuais e gerar os históricos consolidados para todos os funcionários ativos na competência informada.
                                    </p>
                                    
                                    <div className="space-y-4">
                                        {auth.user.is_master_admin && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700">Empresa Vinculada *</label>
                                                <select
                                                    className="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                                    value={data.company_id}
                                                    onChange={e => setData('company_id', e.target.value)}
                                                    required
                                                >
                                                    <option value="">Selecione uma empresa</option>
                                                    {companies && companies.map(c => (
                                                        <option key={c.id} value={c.id}>{c.razao_social}</option>
                                                    ))}
                                                </select>
                                                {errors.company_id && <div className="text-red-500 text-xs mt-1">{errors.company_id}</div>}
                                            </div>
                                        )}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Competência (Mês/Ano)</label>
                                            <input
                                                type="month"
                                                className="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                                value={data.competencia}
                                                onChange={e => setData('competencia', e.target.value)}
                                                required
                                            />
                                            {errors.competencia && <div className="text-red-500 text-xs mt-1">{errors.competencia}</div>}
                                        </div>
                                    </div>
                                </div>
                                <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                                    >
                                        {processing ? 'Processando...' : 'Confirmar Fechamento'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setIsModalOpen(false)}
                                        className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                    >
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
