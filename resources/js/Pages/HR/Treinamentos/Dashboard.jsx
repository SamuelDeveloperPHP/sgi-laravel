import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Target, Clock, TrendingUp, Award, Calendar } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

export default function TreinamentoDashboard({ auth, ano, metaHoras, horasRealizadas, chartData, topCursos }) {
    const handleAnoChange = (e) => {
        router.get(route('treinamentos.dashboard'), { ano: e.target.value }, { preserveState: true });
    };

    const percentualConcluido = metaHoras > 0 ? Math.min(Math.round((horasRealizadas / metaHoras) * 100), 100) : 0;

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard de Treinamentos</h2>}
        >
            <Head title="Dashboard - Treinamentos" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Header Controls */}
                    <div className="flex justify-between items-center">
                        <h3 className="text-lg font-medium text-gray-900">Visão Geral: Ano {ano}</h3>
                        <div className="flex items-center gap-2">
                            <label htmlFor="ano" className="text-sm font-medium text-gray-700">Selecione o Ano:</label>
                            <select id="ano" value={ano} onChange={handleAnoChange} className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                {[...Array(5)].map((_, i) => {
                                    const y = new Date().getFullYear() - i;
                                    return <option key={y} value={y}>{y}</option>;
                                })}
                            </select>
                        </div>
                    </div>

                    {/* KPI Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500">
                            <div className="flex justify-between items-start">
                                <div>
                                    <p className="text-sm font-medium text-gray-500 uppercase">Horas Oferecidas</p>
                                    <p className="mt-2 text-3xl font-semibold text-gray-900">{horasRealizadas}h</p>
                                </div>
                                <div className="p-3 bg-indigo-50 rounded-full">
                                    <Clock className="w-6 h-6 text-indigo-600" />
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-blue-500">
                            <div className="flex justify-between items-start">
                                <div>
                                    <p className="text-sm font-medium text-gray-500 uppercase">Meta Anual</p>
                                    <p className="mt-2 text-3xl font-semibold text-gray-900">{metaHoras}h</p>
                                </div>
                                <div className="p-3 bg-blue-50 rounded-full">
                                    <Target className="w-6 h-6 text-blue-600" />
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500">
                            <div className="flex justify-between items-start">
                                <div className="w-full">
                                    <p className="text-sm font-medium text-gray-500 uppercase">Progresso da Meta</p>
                                    <div className="mt-2 flex items-center justify-between">
                                        <p className="text-3xl font-semibold text-gray-900">{percentualConcluido}%</p>
                                        <TrendingUp className="w-6 h-6 text-green-500" />
                                    </div>
                                    <div className="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                                        <div className="bg-green-500 h-2.5 rounded-full" style={{ width: `${percentualConcluido}%` }}></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Charts & Lists */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        {/* Bar Chart */}
                        <div className="bg-white shadow-sm sm:rounded-lg p-6 lg:col-span-2">
                            <h3 className="text-lg font-medium text-gray-900 mb-6 flex items-center gap-2">
                                <Calendar className="w-5 h-5 text-gray-500"/> Evolução Mensal (Horas)
                            </h3>
                            <div className="h-80">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={chartData} margin={{ top: 5, right: 20, bottom: 5, left: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                        <XAxis dataKey="name" />
                                        <YAxis />
                                        <Tooltip cursor={{fill: '#f3f4f6'}} />
                                        <Bar dataKey="horas" fill="#4f46e5" radius={[4, 4, 0, 0]} name="Horas" />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </div>

                        {/* Top Courses */}
                        <div className="bg-white shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-6 flex items-center gap-2">
                                <Award className="w-5 h-5 text-gray-500"/> Top 5 Cursos Mais Realizados
                            </h3>
                            {topCursos.length > 0 ? (
                                <ul className="divide-y divide-gray-200">
                                    {topCursos.map((curso, idx) => (
                                        <li key={idx} className="py-4 flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <span className="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">
                                                    {idx + 1}
                                                </span>
                                                <span className="text-sm font-medium text-gray-900 truncate max-w-[200px]" title={curso.nome}>
                                                    {curso.nome}
                                                </span>
                                            </div>
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {curso.total} presenças
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-gray-500 text-center py-8">Nenhum dado disponível para o ano selecionado.</p>
                            )}
                        </div>

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
