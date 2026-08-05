import React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Search, FileSpreadsheet } from 'lucide-react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputLabel from '@/Components/InputLabel';

export default function RelatoriosIndex({ auth, cursos, resultados, filtros }) {
    const { data, setData, get, processing } = useForm({
        curso_id: filtros.curso_id || '',
        status: filtros.status || '',
        data_inicio: filtros.data_inicio || '',
        data_fim: filtros.data_fim || '',
        filtrar: '1'
    });

    const submit = (e) => {
        e.preventDefault();
        get(route('treinamentos.relatorios'));
    };

    const clearFilters = () => {
        router.get(route('treinamentos.relatorios'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Relatórios de Treinamentos</h2>}
        >
            <Head title="Relatórios - Treinamentos" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Filtros */}
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                            <Search className="w-5 h-5 text-gray-500"/> Filtros de Busca
                        </h3>
                        <form onSubmit={submit} className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <InputLabel value="Curso" />
                                <select className="mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" value={data.curso_id} onChange={e => setData('curso_id', e.target.value)}>
                                    <option value="">Todos os Cursos</option>
                                    {cursos.map(c => <option key={c.id} value={c.id}>{c.nome}</option>)}
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Status" />
                                <select className="mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" value={data.status} onChange={e => setData('status', e.target.value)}>
                                    <option value="">Todos</option>
                                    <option value="Agendado">Agendado</option>
                                    <option value="Em Andamento">Em Andamento</option>
                                    <option value="Concluído">Concluído</option>
                                    <option value="Cancelado">Cancelado</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Período Inicial" />
                                <input type="date" className="mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" value={data.data_inicio} onChange={e => setData('data_inicio', e.target.value)} />
                            </div>
                            <div>
                                <InputLabel value="Período Final" />
                                <input type="date" className="mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" value={data.data_fim} onChange={e => setData('data_fim', e.target.value)} />
                            </div>
                            <div className="md:col-span-4 flex justify-end gap-3 mt-2">
                                <SecondaryButton type="button" onClick={clearFilters}>Limpar Filtros</SecondaryButton>
                                <PrimaryButton type="submit" disabled={processing} className="gap-2">
                                    <Search className="w-4 h-4"/> Buscar
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>

                    {/* Resultados */}
                    {filtros.filtrar && (
                        <div className="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                            <div className="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                                <h3 className="text-lg font-medium text-gray-900">Resultados da Busca: {resultados.length} turmas encontradas</h3>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm text-left text-gray-500">
                                    <thead className="text-xs text-gray-700 uppercase bg-white">
                                        <tr>
                                            <th className="px-6 py-3">Data</th>
                                            <th className="px-6 py-3">Curso</th>
                                            <th className="px-6 py-3">Carga Horária</th>
                                            <th className="px-6 py-3">Status</th>
                                            <th className="px-6 py-3">Alunos Presentes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {resultados.length > 0 ? (
                                            resultados.map((treinamento) => (
                                                <tr key={treinamento.id} className="bg-white border-t hover:bg-gray-50">
                                                    <td className="px-6 py-4">{new Date(treinamento.data_inicio + 'T00:00:00').toLocaleDateString()}</td>
                                                    <td className="px-6 py-4 font-medium text-gray-900">{treinamento.curso?.nome}</td>
                                                    <td className="px-6 py-4">{treinamento.curso?.carga_horaria}h</td>
                                                    <td className="px-6 py-4">
                                                        <span className={`px-2 py-1 rounded text-xs font-semibold ${
                                                            treinamento.status === 'Concluído' ? 'bg-green-100 text-green-800' :
                                                            treinamento.status === 'Agendado' ? 'bg-blue-100 text-blue-800' :
                                                            treinamento.status === 'Cancelado' ? 'bg-red-100 text-red-800' :
                                                            'bg-yellow-100 text-yellow-800'
                                                        }`}>
                                                            {treinamento.status}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        {treinamento.presenças_confirmadas} presentes
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan="5" className="px-6 py-8 text-center text-gray-500">
                                                    Nenhum resultado encontrado para os filtros selecionados.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
