import React from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Users, DollarSign, Briefcase, TrendingUp, TrendingDown, UserMinus, UserPlus } from 'lucide-react';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
} from 'chart.js';
import { Line, Doughnut } from 'react-chartjs-2';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement
);

export default function Dashboard({ auth, stats }) {
    // Preparar dados do gráfico de Evolução da Folha
    const lineChartData = {
        labels: stats.evolucaoFolha.map(e => e.competencia).reverse(),
        datasets: [
            {
                label: 'Custo Total Folha (R$)',
                data: stats.evolucaoFolha.map(e => e.total_custo).reverse(),
                borderColor: 'rgb(79, 70, 229)',
                backgroundColor: 'rgba(79, 70, 229, 0.5)',
                tension: 0.3,
            },
            {
                label: 'Salário Líquido Pago (R$)',
                data: stats.evolucaoFolha.map(e => e.total_liquido).reverse(),
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.5)',
                tension: 0.3,
            }
        ],
    };

    // Preparar dados do gráfico de Gênero
    const generoLabels = {
        'M': 'Masculino',
        'F': 'Feminino',
        'O': 'Outro',
        'N': 'Não Informado'
    };
    
    const doughnutData = {
        labels: stats.genero.map(g => generoLabels[g.genero] || 'N/I'),
        datasets: [
            {
                data: stats.genero.map(g => g.total),
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(201, 203, 207, 0.8)',
                ],
                borderWidth: 1,
            },
        ],
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard de Recursos Humanos</h2>}
        >
            <Head title="RH Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Top Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div className="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 border-blue-500">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-500 uppercase font-semibold">Total Funcionários</p>
                                    <h4 className="text-3xl font-bold text-gray-900 mt-2">{stats.totalFuncionarios}</h4>
                                </div>
                                <div className="p-3 bg-blue-100 rounded-full">
                                    <Users className="w-6 h-6 text-blue-600" />
                                </div>
                            </div>
                            <div className="mt-4 text-sm text-gray-600">
                                <span className="font-semibold text-green-600">{stats.ativos}</span> ativos | <span className="font-semibold text-red-600">{stats.inativos}</span> inativos
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 border-green-500">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-500 uppercase font-semibold">Massa Salarial (Mês)</p>
                                    <h4 className="text-3xl font-bold text-gray-900 mt-2">
                                        R$ {Number(stats.massaSalarialAtual).toLocaleString('pt-BR')}
                                    </h4>
                                </div>
                                <div className="p-3 bg-green-100 rounded-full">
                                    <Briefcase className="w-6 h-6 text-green-600" />
                                </div>
                            </div>
                            <div className="mt-4 text-sm text-gray-600">
                                Salário Médio: R$ {Number(stats.salarioMedio).toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 border-indigo-500">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-500 uppercase font-semibold">Admissões ({stats.turnover.ano})</p>
                                    <h4 className="text-3xl font-bold text-gray-900 mt-2">{stats.turnover.admissoes}</h4>
                                </div>
                                <div className="p-3 bg-indigo-100 rounded-full">
                                    <UserPlus className="w-6 h-6 text-indigo-600" />
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 border-red-500">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-500 uppercase font-semibold">Demissões ({stats.turnover.ano})</p>
                                    <h4 className="text-3xl font-bold text-gray-900 mt-2">{stats.turnover.demissoes}</h4>
                                </div>
                                <div className="p-3 bg-red-100 rounded-full">
                                    <UserMinus className="w-6 h-6 text-red-600" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Charts Row */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div className="lg:col-span-2 bg-white overflow-hidden shadow-sm rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                                <TrendingUp className="w-5 h-5 text-gray-500" /> Evolução da Folha de Pagamento
                            </h3>
                            <div className="h-80">
                                {stats.evolucaoFolha.length > 0 ? (
                                    <Line 
                                        data={lineChartData} 
                                        options={{ maintainAspectRatio: false, responsive: true }}
                                    />
                                ) : (
                                    <div className="flex items-center justify-center h-full text-gray-500">
                                        Nenhuma folha de pagamento registrada.
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                                <Users className="w-5 h-5 text-gray-500" /> Distribuição por Gênero
                            </h3>
                            <div className="h-64 mt-8 flex justify-center">
                                {stats.genero.length > 0 ? (
                                    <Doughnut 
                                        data={doughnutData} 
                                        options={{ 
                                            maintainAspectRatio: false,
                                            plugins: { legend: { position: 'bottom' } }
                                        }}
                                    />
                                ) : (
                                    <div className="flex items-center justify-center h-full text-gray-500">
                                        Sem dados de gênero.
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
