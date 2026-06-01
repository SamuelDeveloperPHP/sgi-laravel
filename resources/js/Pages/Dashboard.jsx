import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { 
    Activity, 
    AlertTriangle, 
    FileCheck2, 
    TrendingUp,
    ChevronRight
} from 'lucide-react';
import { 
    BarChart, 
    Bar, 
    XAxis, 
    YAxis, 
    CartesianGrid, 
    Tooltip, 
    ResponsiveContainer 
} from 'recharts';

export default function Dashboard({ auth, stats, recent_nc, recent_pa }) {
    // Dados para o gráfico simulados a partir das estatísticas
    const chartData = [
        { name: 'Não Conformidades', total: stats.total_nc, fill: '#f43f5e' },
        { name: 'Planos de Ação', total: stats.total_pa, fill: '#3b82f6' },
        { name: 'Auditorias', total: stats.total_auditorias, fill: '#10b981' },
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Dashboard SGI</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Welcome Banner */}
                    <div className="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-2xl p-6 shadow-lg text-white">
                        <h3 className="text-2xl font-bold mb-2">Bem-vindo(a) ao seu novo SGI, {auth.user.name}!</h3>
                        <p className="text-emerald-50 opacity-90">Aqui está o resumo atualizado do seu sistema de gestão de qualidade e segurança.</p>
                    </div>

                    {/* KPIs */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/50 hover:shadow-md transition-shadow">
                            <div className="flex justify-between items-start">
                                <div>
                                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">Total de Não Conformidades</p>
                                    <h4 className="text-3xl font-bold text-slate-900 dark:text-white mt-2">{stats.total_nc}</h4>
                                </div>
                                <div className="p-3 bg-rose-50 dark:bg-rose-500/10 rounded-lg">
                                    <AlertTriangle className="w-6 h-6 text-rose-500" />
                                </div>
                            </div>
                        </div>

                        <div className="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/50 hover:shadow-md transition-shadow">
                            <div className="flex justify-between items-start">
                                <div>
                                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">Planos de Ação Abertos</p>
                                    <h4 className="text-3xl font-bold text-slate-900 dark:text-white mt-2">{stats.total_pa}</h4>
                                </div>
                                <div className="p-3 bg-blue-50 dark:bg-blue-500/10 rounded-lg">
                                    <Activity className="w-6 h-6 text-blue-500" />
                                </div>
                            </div>
                        </div>

                        <div className="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/50 hover:shadow-md transition-shadow">
                            <div className="flex justify-between items-start">
                                <div>
                                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">Auditorias Internas</p>
                                    <h4 className="text-3xl font-bold text-slate-900 dark:text-white mt-2">{stats.total_auditorias}</h4>
                                </div>
                                <div className="p-3 bg-emerald-50 dark:bg-emerald-500/10 rounded-lg">
                                    <FileCheck2 className="w-6 h-6 text-emerald-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Gráfico */}
                        <div className="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/50 p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Volume de Registros</h3>
                                <TrendingUp className="w-5 h-5 text-slate-400" />
                            </div>
                            <div className="h-[300px] w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={chartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#334155" opacity={0.2} />
                                        <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{fill: '#64748b', fontSize: 12}} />
                                        <YAxis axisLine={false} tickLine={false} tick={{fill: '#64748b', fontSize: 12}} />
                                        <Tooltip 
                                            cursor={{fill: '#f1f5f9', opacity: 0.1}}
                                            contentStyle={{borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)'}}
                                        />
                                        <Bar dataKey="total" radius={[4, 4, 0, 0]} barSize={40} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </div>

                        {/* Atividades Recentes */}
                        <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/50 p-6">
                            <h3 className="text-lg font-semibold text-slate-900 dark:text-white mb-6">Últimas Não Conformidades</h3>
                            <div className="space-y-4">
                                {recent_nc.length > 0 ? recent_nc.map((nc) => (
                                    <div key={nc.id} className="group flex items-start gap-4 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors cursor-pointer">
                                        <div className="w-2 h-2 mt-2 rounded-full bg-rose-500 shrink-0"></div>
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-medium text-slate-900 dark:text-white truncate">{nc.descricao}</p>
                                            <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">{nc.setor} • {nc.data}</p>
                                        </div>
                                        <ChevronRight className="w-4 h-4 text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </div>
                                )) : (
                                    <p className="text-sm text-slate-500">Nenhum registro encontrado.</p>
                                )}
                            </div>
                            
                            <h3 className="text-lg font-semibold text-slate-900 dark:text-white mt-8 mb-6">Planos de Ação Recentes</h3>
                            <div className="space-y-4">
                                {recent_pa.length > 0 ? recent_pa.map((pa) => (
                                    <div key={pa.id} className="group flex items-start gap-4 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors cursor-pointer">
                                        <div className="w-2 h-2 mt-2 rounded-full bg-blue-500 shrink-0"></div>
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-medium text-slate-900 dark:text-white truncate">{pa.descricao}</p>
                                            <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">Prazo: {pa.prazo} • {pa.status}</p>
                                        </div>
                                        <ChevronRight className="w-4 h-4 text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </div>
                                )) : (
                                    <p className="text-sm text-slate-500">Nenhum plano encontrado.</p>
                                )}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
