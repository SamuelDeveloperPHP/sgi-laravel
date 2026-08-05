import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Building2, Clock3, Eye, Search, ShieldCheck } from 'lucide-react';
import { useEffect, useState } from 'react';

const formatCnpj = (value) => String(value || '').replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
const formatDate = (value) => value ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value)) : '-';

export default function Index({ auth, companies, filters, pendingCount, flash }) {
    const [search, setSearch] = useState(filters?.search || '');

    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== (filters?.search || '')) {
                router.get(route('admin.company-registrations.index'), { search }, {
                    preserveState: true,
                    replace: true,
                });
            }
        }, 350);

        return () => clearTimeout(timer);
    }, [search]);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-semibold text-slate-800 dark:text-slate-200">Análise de pré-cadastros</h2>}
        >
            <Head title="Pré-cadastros pendentes" />

            <div className="space-y-5 sm:px-6 lg:px-8">
                <section className="grid gap-4 md:grid-cols-2">
                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/30">
                        <div className="flex items-center gap-4">
                            <div className="rounded-xl bg-amber-100 p-3 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300"><Clock3 className="h-6 w-6" /></div>
                            <div><p className="text-sm text-amber-800 dark:text-amber-200">Aguardando análise</p><p className="text-3xl font-bold text-amber-950 dark:text-white">{pendingCount}</p></div>
                        </div>
                    </div>
                    <div className="rounded-xl border border-teal-200 bg-teal-50 p-5 dark:border-teal-900 dark:bg-teal-950/30">
                        <div className="flex items-center gap-4">
                            <div className="rounded-xl bg-teal-100 p-3 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300"><ShieldCheck className="h-6 w-6" /></div>
                            <p className="text-sm text-teal-900 dark:text-teal-100">Confirme CNPJ, domínio e administrador antes de liberar dados sensíveis.</p>
                        </div>
                    </div>
                </section>

                {flash?.success && <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">{flash.success}</div>}
                {flash?.error && <div className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">{flash.error}</div>}

                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div className="border-b border-slate-200 p-4 dark:border-slate-700">
                        <div className="relative max-w-md">
                            <Search className="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-slate-400" />
                            <input
                                type="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Buscar por empresa, CNPJ ou administrador"
                                className="w-full rounded-lg border-slate-300 py-2 pl-10 text-sm focus:border-teal-500 focus:ring-teal-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            />
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-slate-50 text-xs uppercase text-slate-600 dark:bg-slate-900/60 dark:text-slate-300">
                                <tr><th className="px-4 py-3">Empresa</th><th className="px-4 py-3">CNPJ</th><th className="px-4 py-3">Administrador</th><th className="px-4 py-3">Enviado em</th><th className="px-4 py-3 text-right">Análise</th></tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-700">
                                {companies.data.length > 0 ? companies.data.map((company) => (
                                    <tr key={company.id} className="hover:bg-slate-50 dark:hover:bg-slate-700/40">
                                        <td className="px-4 py-3"><div className="flex items-center gap-3"><span className="rounded-lg bg-slate-100 p-2 text-slate-600 dark:bg-slate-700 dark:text-slate-200"><Building2 className="h-4 w-4" /></span><div><p className="font-medium text-slate-900 dark:text-white">{company.nome_fantasia}</p><p className="text-xs text-slate-500">{company.razao_social}</p></div></div></td>
                                        <td className="whitespace-nowrap px-4 py-3 font-mono text-slate-700 dark:text-slate-200">{formatCnpj(company.cnpj)}</td>
                                        <td className="px-4 py-3"><p className="text-slate-800 dark:text-slate-100">{company.nome_administrador}</p><p className="text-xs text-slate-500">{company.email_administrador}</p></td>
                                        <td className="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-slate-300">{formatDate(company.created_at)}</td>
                                        <td className="px-4 py-3 text-right"><Link href={route('admin.company-registrations.show', company.id)} className="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-3 py-2 font-medium text-white transition hover:bg-teal-700"><Eye className="h-4 w-4" /> Analisar</Link></td>
                                    </tr>
                                )) : (
                                    <tr><td colSpan="5" className="px-4 py-12 text-center text-slate-500">Nenhum pré-cadastro pendente encontrado.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {companies.links?.length > 3 && (
                        <div className="flex flex-wrap gap-1 border-t border-slate-200 p-4 dark:border-slate-700">
                            {companies.links.map((link) => <Link key={link.label} href={link.url || '#'} preserveScroll className={`rounded-md border px-3 py-1.5 text-sm ${link.active ? 'border-teal-600 bg-teal-600 text-white' : 'border-slate-300 text-slate-600 dark:border-slate-600 dark:text-slate-300'} ${!link.url ? 'pointer-events-none opacity-50' : ''}`} dangerouslySetInnerHTML={{ __html: link.label }} />)}
                        </div>
                    )}
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
