import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Edit, Trash2, Users } from 'lucide-react';

export default function Index({ auth, users, flash }) {
    const { delete: destroy } = useForm();

    const handleDelete = (id) => {
        if (confirm('Tem certeza que deseja excluir este usuário?')) {
            destroy(route('admin.users.destroy', id));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Gestão de Usuários</h2>}
        >
            <Head title="Usuários" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    <div className="flex justify-between items-center bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/50">
                        <div className="flex items-center gap-3">
                            <div className="p-3 bg-violet-50 dark:bg-violet-500/10 rounded-lg">
                                <Users className="w-6 h-6 text-violet-500" />
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-slate-900 dark:text-white">Usuários do Sistema</h3>
                                <p className="text-sm text-slate-500 dark:text-slate-400">Gerencie acessos e vincule usuários a empresas.</p>
                            </div>
                        </div>
                        <Link 
                            href={route('admin.users.create')} 
                            className="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white px-4 py-2 rounded-xl transition-colors font-medium text-sm"
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

                    <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left text-slate-500 dark:text-slate-400">
                                <thead className="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-700/50 dark:text-slate-300">
                                    <tr>
                                        <th className="px-6 py-4">Nome</th>
                                        <th className="px-6 py-4">E-mail</th>
                                        <th className="px-6 py-4">Empresa (Tenant)</th>
                                        <th className="px-6 py-4">Nível</th>
                                        <th className="px-6 py-4 text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {users.data.length > 0 ? users.data.map((user) => (
                                        <tr key={user.id} className="border-b dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                            <td className="px-6 py-4 font-medium text-slate-900 dark:text-white">{user.name}</td>
                                            <td className="px-6 py-4">{user.email}</td>
                                            <td className="px-6 py-4">
                                                {user.company ? user.company.nome_fantasia : <span className="text-rose-500 italic">Sem Empresa</span>}
                                            </td>
                                            <td className="px-6 py-4">
                                                {user.is_master_admin ? (
                                                    <span className="px-2 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-bold">Master Admin</span>
                                                ) : (
                                                    <span className="px-2 py-1 bg-slate-100 text-slate-700 rounded-full text-xs">Padrão</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 flex justify-end gap-3">
                                                <Link 
                                                    href={route('admin.users.edit', user.id)}
                                                    className="text-blue-500 hover:text-blue-700 p-2 hover:bg-blue-50 rounded-lg"
                                                >
                                                    <Edit className="w-4 h-4" />
                                                </Link>
                                                <button 
                                                    onClick={() => handleDelete(user.id)}
                                                    className="text-rose-500 hover:text-rose-700 p-2 hover:bg-rose-50 rounded-lg"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    )) : (
                                        <tr>
                                            <td colSpan="5" className="px-6 py-8 text-center text-slate-500">Nenhum usuário encontrado.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
