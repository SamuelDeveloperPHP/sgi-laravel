import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Save, ArrowLeft } from 'lucide-react';

export default function Form({ auth, company, isEdit }) {
    const { data, setData, post, put, processing, errors } = useForm({
        nome_fantasia: company.nome_fantasia || '',
        razao_social: company.razao_social || '',
        cnpj: company.cnpj || '',
        status: company.status !== undefined ? company.status : true,
    });

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route('admin.companies.update', company.id));
        } else {
            post(route('admin.companies.store'));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                {isEdit ? 'Editar Empresa' : 'Nova Empresa'}
            </h2>}
        >
            <Head title={isEdit ? 'Editar Empresa' : 'Nova Empresa'} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden">
                        
                        <div className="p-6 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/50">
                            <h3 className="text-lg font-bold text-slate-900 dark:text-white">Dados da Empresa</h3>
                            <Link href={route('admin.companies.index')} className="text-sm font-medium text-slate-500 hover:text-slate-700 flex items-center gap-1">
                                <ArrowLeft className="w-4 h-4" /> Voltar
                            </Link>
                        </div>

                        <form onSubmit={submit} className="p-6 space-y-6">
                            
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"><span className="text-rose-500">*</span> Nome Fantasia</label>
                                    <input type="text" value={data.nome_fantasia} onChange={e => setData('nome_fantasia', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                    {errors.nome_fantasia && <p className="text-rose-500 text-sm mt-1">{errors.nome_fantasia}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Razão Social</label>
                                    <input type="text" value={data.razao_social} onChange={e => setData('razao_social', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">CNPJ</label>
                                    <input type="text" value={data.cnpj} onChange={e => setData('cnpj', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                    {errors.cnpj && <p className="text-rose-500 text-sm mt-1">{errors.cnpj}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
                                    <select value={data.status ? '1' : '0'} onChange={e => setData('status', e.target.value === '1')} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white">
                                        <option value="1">Ativo</option>
                                        <option value="0">Inativo</option>
                                    </select>
                                </div>
                            </div>

                            <div className="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-700/50">
                                <button type="submit" disabled={processing} className="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl transition-colors font-medium text-sm shadow-sm disabled:opacity-75">
                                    <Save className="w-4 h-4" /> {isEdit ? 'Atualizar Empresa' : 'Salvar Empresa'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
