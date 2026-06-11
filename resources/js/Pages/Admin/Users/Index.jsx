import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, Users, Search, ShieldCheck, User as UserIcon } from 'lucide-react';
import { useState, useEffect } from 'react';

export default function Index({ auth, users, filters, metrics, flash }) {
    const { delete: destroy } = useForm();
    const [search, setSearch] = useState(filters?.search || '');

    const handleDelete = (id) => {
        if (confirm('Tem certeza que deseja excluir este usuário?')) {
            destroy(route('admin.users.destroy', id));
        }
    };

    // Use debounce for search
    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== (filters?.search || '')) {
                router.get(
                    route('admin.users.index'),
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
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Gestão de Usuários</h2>}
        >
            <Head title="Usuários" />

            <div>
                <div className="w-full sm:px-6 lg:px-8 space-y-3">
                    
                    {/* Metrics Section */}
                    {metrics && (
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div className="bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700/50 flex items-center gap-3">
                                <div className="p-3 bg-violet-50 dark:bg-violet-500/10 rounded-lg text-violet-600 dark:text-violet-400">
                                    <Users className="w-6 h-6" />
                                </div>
                                <div>
                                    <p className="text-xs text-slate-500 dark:text-slate-400 font-medium">Total de Usuários</p>
                                    <p className="text-2xl font-bold text-slate-900 dark:text-white">{metrics.total}</p>
                                </div>
                            </div>
                            <div className="bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700/50 flex items-center gap-3">
                                <div className="p-3 bg-amber-50 dark:bg-amber-500/10 rounded-lg text-amber-600 dark:text-amber-400">
                                    <ShieldCheck className="w-6 h-6" />
                                </div>
                                <div>
                                    <p className="text-xs text-slate-500 dark:text-slate-400 font-medium">Administradores Master</p>
                                    <p className="text-2xl font-bold text-slate-900 dark:text-white">{metrics.master}</p>
                                </div>
                            </div>
                            <div className="bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700/50 flex items-center gap-3">
                                <div className="p-3 bg-blue-50 dark:bg-blue-500/10 rounded-lg text-blue-600 dark:text-blue-400">
                                    <UserIcon className="w-6 h-6" />
                                </div>
                                <div>
                                    <p className="text-xs text-slate-500 dark:text-slate-400 font-medium">Usuários Padrão</p>
                                    <p className="text-2xl font-bold text-slate-900 dark:text-white">{metrics.standard}</p>
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
                                className="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm rounded-xl focus:ring-violet-500 focus:border-violet-500 block w-full pl-10 p-2.5 transition-colors"
                                placeholder="Buscar por nome ou e-mail..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>

                        <Link 
                            href={route('admin.users.create')} 
                            className="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white px-5 py-2.5 rounded-xl transition-all font-medium text-sm shadow-md hover:shadow-lg w-full md:w-auto justify-center"
                        >
                            <Plus className="w-4 h-4" /> Novo Usuário
                        </Link>
                    </div>

                    {flash?.message && (
                        <div className="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl">
                            {flash.message}
                        </div>
                    )}
                    {flash?.error && (
                        <div className="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl">
                            {flash.error}
                        </div>
                    )}

                    <div className="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left text-slate-500 dark:text-slate-400">
                                <thead className="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-700/50 dark:text-slate-300">
                                    <tr>
                                        <th className="px-4 py-2">Nome</th>
                                        <th className="px-4 py-2">E-mail</th>
                                        <th className="px-4 py-2">Empresa (Tenant)</th>
                                        <th className="px-4 py-2">Nível</th>
                                        <th className="px-4 py-2 text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {users.data.length > 0 ? users.data.map((user) => (
                                        <tr key={user.id} className="border-b dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                            <td className="px-4 py-2 font-medium text-slate-900 dark:text-white">{user.name}</td>
                                            <td className="px-4 py-2">{user.email}</td>
                                            <td className="px-4 py-2">
                                                {user.companies && user.companies.length > 0 ? (
                                                    <span className="truncate max-w-[200px] inline-block">{user.companies.map(c => c.nome_fantasia).join(', ')}</span>
                                                ) : (
                                                    <span className="text-rose-500 italic text-xs">Sem Empresa</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-2">
                                                {user.is_master_admin ? (
                                                    <span className="px-2 py-0.5 bg-amber-100 text-amber-800 rounded-full text-xs font-bold">Master Admin</span>
                                                ) : (
                                                    <span className="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-full text-xs">Padrão</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-2 flex justify-end gap-2">
                                                <Link 
                                                    href={route('admin.users.edit', user.id)}
                                                    className="text-blue-500 hover:text-blue-700 p-1.5 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-lg transition-colors"
                                                >
                                                    <Edit className="w-4 h-4" />
                                                </Link>
                                                <button 
                                                    onClick={() => handleDelete(user.id)}
                                                    className="text-rose-500 hover:text-rose-700 p-1.5 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-lg transition-colors"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    )) : (
                                        <tr>
                                            <td colSpan="5" className="px-4 py-6 text-center text-slate-500">Nenhum usuário encontrado para essa busca.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        
                        {/* Pagination Component */}
                        {users.links && users.links.length > 3 && (
                            <div className="px-4 py-2.5 border-t border-slate-100 dark:border-slate-700/50 bg-slate-50 dark:bg-slate-800 flex flex-col md:flex-row items-center justify-between gap-3">
                                <span className="text-xs text-slate-500 dark:text-slate-400">
                                    Mostrando <span className="font-medium text-slate-900 dark:text-white">{users.from || 0}</span> a <span className="font-medium text-slate-900 dark:text-white">{users.to || 0}</span> de <span className="font-medium text-slate-900 dark:text-white">{users.total}</span>
                                </span>
                                <div className="flex flex-wrap gap-1">
                                    {users.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-1.5 text-sm rounded-md border transition-colors ${
                                                link.active
                                                    ? 'bg-violet-600 border-violet-600 text-white shadow-sm'
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
