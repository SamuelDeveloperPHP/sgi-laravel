import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, User, Building, CalendarDays, Receipt, CreditCard, DollarSign, PiggyBank } from 'lucide-react';

export default function Show({ auth, feria }) {
    
    // Formatador de Moeda
    const formatCurrency = (value) => {
        if (value === null || value === undefined) return 'R$ 0,00';
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Detalhes do Recibo de Férias</h2>}
        >
            <Head title="Detalhes das Férias" />

            <div className="w-full max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div className="flex items-center gap-4">
                    <Link 
                        href={route('admin.ferias.index')}
                        className="p-2 text-slate-500 hover:text-slate-700 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 transition-colors"
                    >
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <Receipt className="w-6 h-6 text-indigo-500" />
                        Recibo / Histórico de Férias
                    </h1>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {/* Resumo do Funcionário */}
                    <div className="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/50 p-6 flex flex-col items-center text-center">
                        <div className="w-20 h-20 bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400 rounded-full flex items-center justify-center text-3xl font-bold mb-4">
                            {feria.funcionario?.nome?.charAt(0) || '?'}
                        </div>
                        <h3 className="text-lg font-bold text-slate-900 dark:text-white">{feria.funcionario?.nome}</h3>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mb-4">{feria.funcionario?.company?.razao_social}</p>
                        
                        <Link 
                            href={route('admin.funcionarios.show', feria.funcionario_id)}
                            className="inline-flex items-center gap-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-lg transition-colors"
                        >
                            <User className="w-4 h-4" /> Ver Perfil Completo
                        </Link>
                    </div>

                    {/* Detalhes do Período */}
                    <div className="md:col-span-2 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/50 p-6">
                        <div className="flex justify-between items-center mb-6 border-b border-slate-100 dark:border-slate-700 pb-4">
                            <h3 className="text-lg font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                <CalendarDays className="w-5 h-5 text-indigo-500" /> Períodos de Férias
                            </h3>
                            <span className={`px-3 py-1 rounded-full text-sm font-bold ${
                                feria.status === 'Programada' ? 'bg-blue-100 text-blue-700' : 
                                feria.status === 'Em Gozo' ? 'bg-amber-100 text-amber-700' : 
                                feria.status === 'Concluída' ? 'bg-emerald-100 text-emerald-700' : 
                                'bg-slate-100 text-slate-700'
                            }`}>
                                {feria.status}
                            </span>
                        </div>

                        <div className="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <p className="text-sm font-semibold text-slate-500 uppercase">Período Aquisitivo</p>
                                <p className="text-lg font-medium text-slate-800 dark:text-slate-200">
                                    {feria.periodo_aquisitivo_inicio ? new Date(feria.periodo_aquisitivo_inicio).toLocaleDateString('pt-BR') : '-'} 
                                    <span className="mx-2 text-slate-400">até</span> 
                                    {feria.periodo_aquisitivo_fim ? new Date(feria.periodo_aquisitivo_fim).toLocaleDateString('pt-BR') : '-'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm font-semibold text-slate-500 uppercase">Faltas Injustificadas</p>
                                <p className="text-lg font-medium text-slate-800 dark:text-slate-200">{feria.faltas || 0} dias</p>
                            </div>
                        </div>

                        <div className="space-y-4">
                            <div className="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-lg border border-slate-200 dark:border-slate-700 flex justify-between items-center">
                                <div>
                                    <p className="text-sm text-slate-500 font-medium mb-1">1º Período de Gozo</p>
                                    <p className="font-semibold text-slate-800 dark:text-slate-200">
                                        {feria.gozo_1_inicio ? new Date(feria.gozo_1_inicio).toLocaleDateString('pt-BR') : '-'} a {feria.gozo_1_fim ? new Date(feria.gozo_1_fim).toLocaleDateString('pt-BR') : '-'}
                                    </p>
                                </div>
                            </div>
                            
                            {(feria.gozo_2_inicio || feria.gozo_2_fim) && (
                                <div className="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-lg border border-slate-200 dark:border-slate-700 flex justify-between items-center">
                                    <div>
                                        <p className="text-sm text-slate-500 font-medium mb-1">2º Período de Gozo</p>
                                        <p className="font-semibold text-slate-800 dark:text-slate-200">
                                            {feria.gozo_2_inicio ? new Date(feria.gozo_2_inicio).toLocaleDateString('pt-BR') : '-'} a {feria.gozo_2_fim ? new Date(feria.gozo_2_fim).toLocaleDateString('pt-BR') : '-'}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {(feria.gozo_3_inicio || feria.gozo_3_fim) && (
                                <div className="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-lg border border-slate-200 dark:border-slate-700 flex justify-between items-center">
                                    <div>
                                        <p className="text-sm text-slate-500 font-medium mb-1">3º Período de Gozo</p>
                                        <p className="font-semibold text-slate-800 dark:text-slate-200">
                                            {feria.gozo_3_inicio ? new Date(feria.gozo_3_inicio).toLocaleDateString('pt-BR') : '-'} a {feria.gozo_3_fim ? new Date(feria.gozo_3_fim).toLocaleDateString('pt-BR') : '-'}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {feria.opcao_abono && (
                                <div className="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-lg border border-emerald-200 dark:border-emerald-800 flex justify-between items-center">
                                    <div>
                                        <p className="text-sm text-emerald-600 dark:text-emerald-400 font-medium mb-1">Abono Pecuniário (Venda de Férias)</p>
                                        <p className="font-semibold text-emerald-700 dark:text-emerald-300">
                                            {feria.dias_abono} dias vendidos
                                        </p>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Resumo Financeiro */}
                    <div className="md:col-span-3 bg-slate-900 dark:bg-slate-950 rounded-xl shadow-lg border border-slate-800 p-8 text-white">
                        <div className="flex items-center gap-3 mb-8">
                            <DollarSign className="w-8 h-8 text-emerald-400" />
                            <h2 className="text-2xl font-bold">Resumo Financeiro (Valores Manuais)</h2>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                            <div className="bg-slate-800/50 rounded-xl p-5 border border-slate-700/50">
                                <p className="text-slate-400 text-sm font-medium mb-2 uppercase tracking-wider">Valor das Férias</p>
                                <p className="text-xl font-semibold text-white">{formatCurrency(feria.valor_proventos)}</p>
                            </div>
                            <div className="bg-slate-800/50 rounded-xl p-5 border border-slate-700/50">
                                <p className="text-slate-400 text-sm font-medium mb-2 uppercase tracking-wider">1/3 Constitucional</p>
                                <p className="text-xl font-semibold text-white">{formatCurrency(feria.valor_1_3)}</p>
                            </div>
                            <div className="bg-slate-800/50 rounded-xl p-5 border border-slate-700/50">
                                <p className="text-slate-400 text-sm font-medium mb-2 uppercase tracking-wider">1/3 s/ Abono</p>
                                <p className="text-xl font-semibold text-white">{formatCurrency(feria.valor_1_3_abono)}</p>
                            </div>
                            <div className="bg-slate-800/50 rounded-xl p-5 border border-slate-700/50">
                                <p className="text-rose-400 text-sm font-medium mb-2 uppercase tracking-wider">Descontos (INSS + IRPF)</p>
                                <p className="text-xl font-semibold text-rose-300">
                                    - {formatCurrency((parseFloat(feria.desconto_inss) || 0) + (parseFloat(feria.desconto_irpf) || 0))}
                                </p>
                            </div>
                        </div>

                        <div className="bg-indigo-600 rounded-xl p-6 border border-indigo-500 shadow-inner flex flex-col md:flex-row items-center justify-between">
                            <div className="flex items-center gap-4 mb-4 md:mb-0">
                                <PiggyBank className="w-10 h-10 text-indigo-200" />
                                <div>
                                    <p className="text-indigo-200 text-sm font-medium uppercase tracking-wider">Total Líquido a Receber</p>
                                    <p className="text-3xl font-bold text-white">{formatCurrency(feria.valor_liquido)}</p>
                                </div>
                            </div>
                            <div className="text-sm text-indigo-200 max-w-xs text-center md:text-right">
                                O valor líquido acima foi calculado com base nos valores preenchidos manualmente pelo usuário.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
