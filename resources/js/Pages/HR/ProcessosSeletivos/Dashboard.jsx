import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { Users, Briefcase, DollarSign, Target } from 'lucide-react';

export default function Dashboard({ auth, kpis, funil }) {
    
    const formatCurrency = (value) => {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
    };

    const funnelStages = [
        'Triagem de Currículo', 
        'Teste Prático', 
        'Dinâmica de Grupo', 
        'Entrevista Inicial', 
        'Entrevista com Gerentes', 
        'Entrevista Final', 
        'Aprovado', 
        'Reprovado'
    ];

    const maxFunnelValue = Math.max(...Object.values(funil), 1); // Avoid div by 0

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Dashboard Processo Seletivo</h2>}>
            <Head title="Dashboard de Recrutamento" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* KPIs */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div className="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 border border-slate-200 dark:border-slate-700">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                                    <Briefcase className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">Processos Ativos</p>
                                    <p className="text-2xl font-semibold text-slate-900 dark:text-slate-100">{kpis.processos_abertos}</p>
                                    <p className="text-xs text-slate-400 mt-1">de {kpis.total_processos} totais</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 border border-slate-200 dark:border-slate-700">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                                    <Users className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">Total de Candidatos</p>
                                    <p className="text-2xl font-semibold text-slate-900 dark:text-slate-100">{kpis.total_candidatos}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 border border-slate-200 dark:border-slate-700">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                                    <Target className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">Custo Planejado</p>
                                    <p className="text-lg font-semibold text-slate-900 dark:text-slate-100">{formatCurrency(kpis.custo_planejado)}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 border border-slate-200 dark:border-slate-700">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400">
                                    <DollarSign className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">Custo Realizado</p>
                                    <p className="text-lg font-semibold text-slate-900 dark:text-slate-100">{formatCurrency(kpis.custo_realizado)}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Funil de Contratação */}
                    <div className="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 border border-slate-200 dark:border-slate-700">
                        <h3 className="text-lg font-medium text-slate-900 dark:text-slate-100 mb-6">Funil Geral de Candidatos</h3>
                        
                        <div className="space-y-4">
                            {funnelStages.map((stage) => {
                                const count = funil[stage] || 0;
                                const percentage = Math.round((count / maxFunnelValue) * 100);
                                
                                return (
                                    <div key={stage} className="relative">
                                        <div className="flex justify-between text-sm mb-1">
                                            <span className="font-medium text-slate-700 dark:text-slate-300">{stage}</span>
                                            <span className="text-slate-500 dark:text-slate-400">{count} candidatos</span>
                                        </div>
                                        <div className="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-4">
                                            <div 
                                                className={`h-4 rounded-full ${stage === 'Aprovado' ? 'bg-emerald-500' : stage === 'Reprovado' ? 'bg-red-500' : 'bg-indigo-500'}`} 
                                                style={{ width: `${percentage}%` }}
                                            ></div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
