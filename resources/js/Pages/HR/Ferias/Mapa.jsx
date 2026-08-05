import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { Building, Calendar as CalendarIcon, ChevronLeft, ChevronRight, List } from 'lucide-react';
import { useState, useEffect } from 'react';

export default function Mapa({ auth, ferias, companies, filters }) {
    const [searchCompany, setSearchCompany] = useState(filters?.company_id || '');
    const [currentYear, setCurrentYear] = useState(parseInt(filters?.year || new Date().getFullYear()));

    const months = [
        'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun',
        'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'
    ];

    // Extrai as datas que cruzam com este mês
    const getVacationDatesInMonth = (f, monthIndex) => {
        let dates = [];

        const addGozo = (inicio, fim) => {
            if (!inicio || !fim) return;
            
            const dateStrStart = inicio.split(' ')[0];
            const dateStrEnd = fim.split(' ')[0];
            
            const [sy, sm, sd] = dateStrStart.split('-');
            const [ey, em, ed] = dateStrEnd.split('-');
            
            const startYear = parseInt(sy, 10);
            const startMonth = parseInt(sm, 10) - 1;
            const startDay = parseInt(sd, 10);
            
            const endYear = parseInt(ey, 10);
            const endMonth = parseInt(em, 10) - 1;
            const endDay = parseInt(ed, 10);

            let overlaps = false;
            if (startYear === currentYear && endYear === currentYear) {
                overlaps = monthIndex >= startMonth && monthIndex <= endMonth;
            } else if (startYear === currentYear && endYear > currentYear) {
                overlaps = monthIndex >= startMonth;
            } else if (startYear < currentYear && endYear === currentYear) {
                overlaps = monthIndex <= endMonth;
            } else if (startYear < currentYear && endYear > currentYear) {
                overlaps = true;
            }

            if (overlaps) {
                const format = (d, m) => `${d.toString().padStart(2, '0')}/${(m+1).toString().padStart(2, '0')}`;
                dates.push(`${format(startDay, startMonth)} a ${format(endDay, endMonth)}`);
            }
        };

        addGozo(f.gozo_1_inicio, f.gozo_1_fim);
        addGozo(f.gozo_2_inicio, f.gozo_2_fim);
        addGozo(f.gozo_3_inicio, f.gozo_3_fim);

        return dates;
    };

    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (searchCompany !== (filters?.company_id || '') || currentYear.toString() !== (filters?.year || '')) {
                router.get(
                    route('admin.ferias.mapa'),
                    { company_id: searchCompany, year: currentYear },
                    { preserveState: true, replace: true }
                );
            }
        }, 300);
        return () => clearTimeout(timeoutId);
    }, [searchCompany, currentYear]);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Mapa de Férias Anual</h2>}
        >
            <Head title="Mapa de Férias" />

            <div className="w-full sm:px-6 lg:px-8 space-y-4 py-8">
                
                <div className="flex flex-col md:flex-row justify-between items-center gap-3 bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700/50">
                    <div className="relative w-full md:w-1/2 flex items-center gap-2">
                        <Building className="w-5 h-5 text-slate-400 shrink-0" />
                        <select
                            className="w-full text-sm border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            value={searchCompany}
                            onChange={(e) => setSearchCompany(e.target.value)}
                        >
                            <option value="">Todas as Empresas</option>
                            {companies.map(c => (
                                <option key={c.id} value={c.id}>{c.razao_social}</option>
                            ))}
                        </select>
                    </div>

                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2 bg-slate-100 dark:bg-slate-900 rounded-lg p-1 border border-slate-200 dark:border-slate-700">
                            <button 
                                onClick={() => setCurrentYear(y => y - 1)}
                                className="p-1 hover:bg-slate-200 dark:hover:bg-slate-800 rounded text-slate-600 dark:text-slate-400 transition-colors"
                            >
                                <ChevronLeft className="w-5 h-5" />
                            </button>
                            <span className="font-bold text-slate-700 dark:text-slate-300 px-3">
                                {currentYear}
                            </span>
                            <button 
                                onClick={() => setCurrentYear(y => y + 1)}
                                className="p-1 hover:bg-slate-200 dark:hover:bg-slate-800 rounded text-slate-600 dark:text-slate-400 transition-colors"
                            >
                                <ChevronRight className="w-5 h-5" />
                            </button>
                        </div>
                        
                        <button 
                            onClick={() => router.get(route('admin.ferias.index'))}
                            className="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl transition-all font-medium text-sm shadow-md hover:shadow-lg justify-center"
                        >
                            <List className="w-4 h-4" /> Ver em Lista
                        </button>
                    </div>
                </div>

                <div className="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm text-left text-slate-500 dark:text-slate-400">
                            <thead className="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-700/50 dark:text-slate-300">
                                <tr>
                                    <th className="px-4 py-3 min-w-[200px]">Funcionário</th>
                                    {months.map((m, i) => (
                                        <th key={i} className="px-2 py-3 text-center border-l dark:border-slate-700">{m}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {ferias.length > 0 ? ferias.map((f) => (
                                    <tr key={f.id} className="border-b dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                        <td className="px-4 py-3 font-medium text-slate-900 dark:text-white">
                                            {f.funcionario?.nome}
                                            <div className="text-xs text-slate-400 font-normal">{f.funcionario?.company?.razao_social}</div>
                                        </td>
                                        {months.map((m, i) => {
                                            const dates = getVacationDatesInMonth(f, i);
                                            const hasVacation = dates.length > 0;
                                            return (
                                                <td key={i} className={`px-2 py-3 text-center border-l dark:border-slate-700 align-middle ${hasVacation ? 'bg-emerald-50 dark:bg-emerald-900/20' : ''}`}>
                                                    {hasVacation && (
                                                        <div className="flex flex-col gap-1 items-center justify-center">
                                                            {dates.map((dateStr, idx) => (
                                                                <span key={idx} className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-800/30 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700/50 whitespace-nowrap">
                                                                    {dateStr}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    )}
                                                </td>
                                            );
                                        })}
                                    </tr>
                                )) : (
                                    <tr>
                                        <td colSpan="13" className="px-4 py-6 text-center text-slate-500">Nenhum registro de férias programado para {currentYear}.</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </AuthenticatedLayout>
    );
}
