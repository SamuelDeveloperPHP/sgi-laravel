import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, Building, Search, CheckCircle, XCircle } from 'lucide-react';
import { useState, useEffect } from 'react';

export default function Index({ auth, companies, filters, metrics, flash }) {
    const { delete: destroy } = useForm();
    const [search, setSearch] = useState(filters?.search || '');

    const handleDelete = (id) => {
        if (confirm('Tem certeza que deseja inativar/excluir esta empresa?')) {
            destroy(route('admin.companies.destroy', id));
        }
    };

    // Use debounce for search
    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== (filters?.search || '')) {
                router.get(
                    route('admin.companies.index'),
                    { search },
                    { preserveState: true, replace: true }
                );
            }
        }, 300);
        return () => clearTimeout(timer);
    }, [search]);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Gestão de Empresas (Tenants)</h2>}
        >
            <Head title="Empresas" />

            <div>
                <div className="w-full sm:px-6 lg:px-8 space-y-3">

                    {/* Metrics Section */}
                    {metrics && (
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div className="bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700/50 flex items-center gap-3">
                                <div className="p-3 bg-indigo-50 dark:bg-indigo-500/10 rounded-lg text-indigo-600 dark:text-indigo-400">
                                    <Building className="w-6 h-6" />
                                </div>
                                <div>
                                    <p className="text-xs text-slate-500 dark:text-slate-400 font-medium">Total de Empresas</p>
                                    <p className="text-2xl font-bold text-slate-900 dark:text-white">{metrics.total}</p>
                                </div>
                            </div>
                            <div className="bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700/50 flex items-center gap-3">
                                <div className="p-3 bg-emerald-50 dark:bg-emerald-500/10 rounded-lg text-emerald-600 dark:text-emerald-400">
                                    <CheckCircle className="w-6 h-6" />
                                </div>
                                <div>
                                    <p className="text-xs text-slate-500 dark:text-slate-400 font-medium">Empresas Ativas</p>
                                    <p className="text-2xl font-bold text-slate-900 dark:text-white">{metrics.active}</p>
                                </div>
                            </div>
                            <div className="bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700/50 flex items-center gap-3">
                                <div className="p-3 bg-rose-50 dark:bg-rose-500/10 rounded-lg text-rose-600 dark:text-rose-400">
                                    <XCircle className="w-6 h-6" />
                                </div>
                                <div>
                                    <p className="text-xs text-slate-500 dark:text-slate-400 font-medium">Empresas Inativas</p>
                                    <p className="text-2xl font-bold text-slate-900 dark:text-white">{metrics.inactive}</p>
                                </div>
                            </div>
                        </div>
                    )}
                    
                    <div className="flex flex-col md:flex-row justify-between items-center gap-3 bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700/50">
                        {/* Search Input */}
                        <div className="relative w-full md:w-96">
                            <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                <Search className="w-5 h-5" />
                            </div>
                            <input
                                type="text"
                                className="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm rounded-xl focus:ring-teal-500 focus:border-teal-500 block w-full pl-10 p-2.5 transition-colors"
                                placeholder="Buscar por nome ou CNPJ..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>

                        <Link 
                            href={route('admin.companies.create')} 
                            className="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white px-5 py-2.5 rounded-xl transition-all font-medium text-sm shadow-md hover:shadow-lg w-full md:w-auto justify-center"
                        >
                            <Plus className="w-4 h-4" /> Nova Empresa
                        </Link>
                    </div>

                    {flash?.message && (
                        <div className="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl">
                            {flash.message}
                        </div>
                    )}

                    <div className="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left text-slate-500 dark:text-slate-400">
                                <thead className="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-700/50 dark:text-slate-300">
                                    <tr>
                                        <th className="px-4 py-2">ID</th>
                                        <th className="px-4 py-2">Nome Fantasia</th>
                                        <th className="px-4 py-2">CNPJ</th>
                                        <th className="px-4 py-2">Status</th>
                                        <th className="px-4 py-2 text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {companies.data.length > 0 ? companies.data.map((company) => (
                                        <tr key={company.id} className="border-b dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                            <td className="px-4 py-2">#{company.id}</td>
                                            <td className="px-4 py-2 font-medium text-slate-900 dark:text-white">{company.nome_fantasia}</td>
                                            <td className="px-4 py-2">{company.cnpj || '-'}</td>
                                            <td className="px-4 py-2">
                                                <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${company.status ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'}`}>
                                                    {company.status ? 'Ativo' : 'Inativo'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-2 flex justify-end gap-2">
                                                <Link 
                                                    href={route('admin.companies.edit', company.id)}
                                                    className="text-blue-500 hover:text-blue-700 p-1.5 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-lg transition-colors"
                                                >
                                                    <Edit className="w-4 h-4" />
                                                </Link>
                                                <button 
                                                    onClick={() => handleDelete(company.id)}
                                                    className="text-rose-500 hover:text-rose-700 p-1.5 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-lg transition-colors"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    )) : (
                                        <tr>
                                            <td colSpan="5" className="px-4 py-6 text-center text-slate-500">Nenhuma empresa encontrada para essa busca.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        
                        {/* Pagination Component */}
                        {companies.links && companies.links.length > 3 && (
                            <div className="px-4 py-2.5 border-t border-slate-100 dark:border-slate-700/50 bg-slate-50 dark:bg-slate-800 flex flex-col md:flex-row items-center justify-between gap-3">
                                <span className="text-xs text-slate-500 dark:text-slate-400">
                                    Mostrando <span className="font-medium text-slate-900 dark:text-white">{companies.from || 0}</span> a <span className="font-medium text-slate-900 dark:text-white">{companies.to || 0}</span> de <span className="font-medium text-slate-900 dark:text-white">{companies.total}</span>
                                </span>
                                <div className="flex flex-wrap gap-1">
                                    {companies.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-1.5 text-sm rounded-md border transition-colors ${
                                                link.active
                                                    ? 'bg-teal-600 border-teal-600 text-white shadow-sm'
                                                    : 'bg-white dark:bg-slate-700 border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-600'
                                            } ${!link.url && 'opacity-50 cursor-not-allowed hover:bg-white dark:hover:bg-slate-700'}`}
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
        </AuthenticatedLayout>
    );
}
