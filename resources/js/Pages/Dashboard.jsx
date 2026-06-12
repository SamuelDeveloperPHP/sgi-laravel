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
            header={<h2 className="font-normal text-xl text-[#73879C] leading-tight">Dashboard SGI</h2>}
        >
            <Head title="Dashboard" />

            <div className="pt-6 pb-12">
                <div className="w-full sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Welcome Banner */}
                    <div className="bg-gradient-to-r from-[#1ABB9C] to-[#26B99A] p-6 shadow-sm text-white relative overflow-hidden mb-6">
                        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
                        <h3 className="text-2xl font-normal mb-1">Bem-vindo(a) ao seu novo SGI, {auth.user.name}!</h3>
                        <p className="text-white text-sm opacity-90">Aqui está o resumo atualizado do seu sistema de gestão de qualidade e segurança.</p>
                    </div>

                    {/* KPIs */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="x_panel">
                            <div className="x_content">
                                <div className="flex justify-between items-start">
                                    <div>
                                        <p className="text-sm font-medium text-[#73879C]">Total de Não Conformidades</p>
                                        <h3 className="text-3xl font-bold text-slate-800 dark:text-white mt-2">{stats.total_nc}</h3>
                                    </div>
                                    <div className="p-3 bg-red-100 dark:bg-red-900/30 rounded-2xl">
                                        <AlertTriangle className="h-6 w-6 text-red-600 dark:text-red-400" />
                                    </div>
                                </div>
                                <div className="mt-4 flex items-center text-sm">
                                    <span className="text-red-500 flex items-center font-medium"><TrendingUp className="h-4 w-4 mr-1"/> {stats.nc_abertas} Abertas</span>
                                    <span className="text-[#73879C] ml-2">vs último mês</span>
                                </div>
                            </div>
                        </div>

                        <div className="x_panel">
                            <div className="x_content">
                                <div className="flex justify-between items-start">
                                    <div>
                                        <p className="text-sm font-medium text-[#73879C]">Planos de Ação Abertos</p>
                                        <h3 className="text-3xl font-bold text-slate-800 dark:text-white mt-2">{stats.total_pa}</h3>
                                    </div>
                                    <div className="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-2xl">
                                        <Activity className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                </div>
                                <div className="mt-4 flex items-center text-sm">
                                    <span className="text-[#73879C] flex items-center font-medium">Acompanhamento</span>
                                    <span className="text-[#73879C] ml-2">contínuo</span>
                                </div>
                            </div>
                        </div>

                        <div className="x_panel">
                            <div className="x_content">
                                <div className="flex justify-between items-start">
                                    <div>
                                        <p className="text-sm font-medium text-[#73879C]">Auditorias Internas</p>
                                        <h3 className="text-3xl font-bold text-slate-800 dark:text-white mt-2">{stats.total_auditorias}</h3>
                                    </div>
                                    <div className="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-2xl">
                                        <FileCheck2 className="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                </div>
                                <div className="mt-4 flex items-center text-sm">
                                    <span className="text-emerald-500 flex items-center font-medium">Em dia</span>
                                    <span className="text-[#73879C] ml-2">neste ciclo</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Gráfico */}
                        <div className="lg:col-span-2 x_panel">
                            <div className="x_title">
                                <h2>Volume de Registros</h2>
                                <div className="clearfix"></div>
                            </div>
                            <div className="x_content">
                                <div className="h-[300px] w-full">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <BarChart data={chartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                                            <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#E6E9ED" />
                                            <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{fill: '#73879C', fontSize: 12}} />
                                            <YAxis axisLine={false} tickLine={false} tick={{fill: '#73879C', fontSize: 12}} />
                                            <Tooltip 
                                                cursor={{fill: '#f1f5f9', opacity: 0.1}}
                                                contentStyle={{borderRadius: '0px', border: '1px solid #E6E9ED', boxShadow: 'none'}}
                                            />
                                            <Bar dataKey="total" fill="#26B99A" barSize={40} />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </div>
                            </div>
                        </div>

                        {/* Atividades Recentes */}
                        <div className="x_panel">
                            <div className="x_title">
                                <h2>Últimas Atividades</h2>
                                <div className="clearfix"></div>
                            </div>
                            <div className="x_content">
                                <div className="space-y-4">
                                    <h4 className="text-[#73879C] font-semibold mb-2">Não Conformidades</h4>
                                    {recent_nc.length > 0 ? recent_nc.map((nc) => (
                                        <div key={nc.id} className="group flex items-start gap-4 p-2 border-b border-[#E6E9ED] last:border-0 hover:bg-slate-50 transition-colors cursor-pointer">
                                            <div className="flex-1 min-w-0">
                                                <p className="text-[13px] font-bold text-[#73879C] truncate">{nc.descricao}</p>
                                                <p className="text-xs text-[#73879C] mt-1">{nc.setor} • {nc.data}</p>
                                            </div>
                                            <ChevronRight className="w-4 h-4 text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                                        </div>
                                    )) : (
                                        <p className="text-sm text-slate-500">Nenhum registro encontrado.</p>
                                    )}
                                </div>
                                
                                <div className="space-y-4 mt-6">
                                    <h4 className="text-[#73879C] font-semibold mb-2">Planos de Ação</h4>
                                    {recent_pa.length > 0 ? recent_pa.map((pa) => (
                                        <div key={pa.id} className="group flex items-start gap-4 p-2 border-b border-[#E6E9ED] last:border-0 hover:bg-slate-50 transition-colors cursor-pointer">
                                            <div className="flex-1 min-w-0">
                                                <p className="text-[13px] font-bold text-[#73879C] truncate">{pa.descricao}</p>
                                                <p className="text-xs text-[#73879C] mt-1">Prazo: {pa.prazo} • {pa.status}</p>
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
            </div>
        </AuthenticatedLayout>
    );
}
