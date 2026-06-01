import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Save, ArrowLeft, ChevronDown, ChevronUp } from 'lucide-react';
import { useState } from 'react';

export default function Form({ auth, user, companies, roles, modules, isEdit }) {
    
    // extrair ids iniciais de empresas vinculadas
    const initialCompanyIds = user.companies?.map(c => c.id) || [];
    
    // extrair nomes de permissões iniciais vinculadas
    const initialPermissions = user.permissions?.map(p => p.name) || [];
    
    // extrair a primeira role ou deixar vazio
    const initialRole = user.roles?.length > 0 ? user.roles[0].name : '';

    const { data, setData, post, put, processing, errors } = useForm({
        name: user.name || '',
        email: user.email || '',
        password: '',
        is_active: user.is_active !== undefined ? user.is_active : true,
        companies: initialCompanyIds,
        role: initialRole,
        permissions: initialPermissions,
    });

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route('admin.users.update', user.id));
        } else {
            post(route('admin.users.store'));
        }
    };

    const toggleCompany = (companyId) => {
        if (data.companies.includes(companyId)) {
            setData('companies', data.companies.filter(id => id !== companyId));
        } else {
            setData('companies', [...data.companies, companyId]);
        }
    };

    const togglePermission = (permName) => {
        if (data.permissions.includes(permName)) {
            setData('permissions', data.permissions.filter(p => p !== permName));
        } else {
            setData('permissions', [...data.permissions, permName]);
        }
    };

    const [expandedModules, setExpandedModules] = useState({});
    const toggleModule = (moduleName) => {
        setExpandedModules(prev => ({...prev, [moduleName]: !prev[moduleName]}));
    };

    // Ações padrão para as colunas da matriz
    const actionColumns = ['view', 'list', 'create', 'edit', 'delete', 'manage'];

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                {isEdit ? 'Editar Usuário' : 'Novo Usuário'}
            </h2>}
        >
            <Head title={isEdit ? 'Editar Usuário' : 'Novo Usuário'} />

            <div className="py-12">
                <div className="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">
                    
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-2xl font-bold text-slate-900 dark:text-white">Gerenciamento de Conta</h2>
                        <Link href={route('admin.users.index')} className="text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 flex items-center gap-1">
                            <ArrowLeft className="w-4 h-4" /> Voltar
                        </Link>
                    </div>

                    <form onSubmit={submit} className="space-y-8">
                        
                        {/* Seção 1: Informações Iniciais */}
                        <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                                <h3 className="text-lg font-bold text-slate-900 dark:text-white">Informações Iniciais</h3>
                            </div>
                            <div className="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nome Completo</label>
                                    <input type="text" value={data.name} onChange={e => setData('name', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" required/>
                                    {errors.name && <p className="text-red-500 text-sm mt-1">{errors.name}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Endereço de E-mail</label>
                                    <input type="email" value={data.email} onChange={e => setData('email', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" required/>
                                    {errors.email && <p className="text-red-500 text-sm mt-1">{errors.email}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Senha <span className="text-xs font-normal text-slate-500">(deixe em branco para manter)</span>
                                    </label>
                                    <input type="password" value={data.password} onChange={e => setData('password', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                    {errors.password && <p className="text-red-500 text-sm mt-1">{errors.password}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nível de Acesso Global (Tipo)</label>
                                    <select value={data.role} onChange={e => setData('role', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white">
                                        <option value="">-- Personalizado (Sem Role Global) --</option>
                                        {roles.map(r => (
                                            <option key={r.id} value={r.name}>{r.name}</option>
                                        ))}
                                    </select>
                                </div>
                                
                                <div className="md:col-span-2 mt-2 flex items-center">
                                    {/* Toggle Switch Design */}
                                    <label className="flex items-center cursor-pointer relative">
                                        <input type="checkbox" className="sr-only" checked={data.is_active} onChange={e => setData('is_active', e.target.checked)} />
                                        <div className={`w-11 h-6 rounded-full transition-colors ${data.is_active ? 'bg-indigo-600' : 'bg-slate-300 dark:bg-slate-600'}`}>
                                            <div className={`absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform ${data.is_active ? 'translate-x-5' : 'translate-x-0'}`}></div>
                                        </div>
                                        <span className="ml-3 text-sm font-medium text-slate-700 dark:text-slate-300">Conta Ativa (Liberar acesso)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {/* Seção 2: Multi-Tenant (Empresas) */}
                        <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                                <h3 className="text-lg font-bold text-slate-900 dark:text-white">Isolamento Multi-Tenant (Empresas)</h3>
                                <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">Escolha em quais painéis corporativos este usuário tem autorização para transitar e consultar dados.</p>
                            </div>
                            <div className="p-6">
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {companies.map(company => {
                                        const isChecked = data.companies.includes(company.id);
                                        return (
                                            <label key={company.id} className={`flex items-center p-4 border rounded-xl cursor-pointer transition-colors ${isChecked ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20 dark:border-indigo-400' : 'border-slate-200 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700'}`}>
                                                <input 
                                                    type="checkbox" 
                                                    className="w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                    checked={isChecked}
                                                    onChange={() => toggleCompany(company.id)}
                                                />
                                                <span className={`ml-3 font-medium ${isChecked ? 'text-indigo-900 dark:text-indigo-300' : 'text-slate-700 dark:text-slate-300'}`}>
                                                    {company.nome_fantasia}
                                                </span>
                                            </label>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>

                        {/* Seção 3: Permissões Granulares */}
                        <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center">
                                <div>
                                    <h3 className="text-lg font-bold text-slate-900 dark:text-white">Configuração de Permissões</h3>
                                    <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">Marque individualmente os privilégios na tabela abaixo.</p>
                                </div>
                                <span className="px-3 py-1 bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 text-xs font-bold rounded-md uppercase tracking-wide">
                                    GRUPOS DE MÓDULOS
                                </span>
                            </div>
                            
                            <div className="divide-y divide-slate-200 dark:divide-slate-700">
                                {modules.map((mod, index) => {
                                    const isExpanded = expandedModules[mod.name] !== false; // Default open
                                    return (
                                        <div key={index} className="overflow-hidden">
                                            {/* Cabeçalho do Módulo */}
                                            <button 
                                                type="button" 
                                                onClick={() => toggleModule(mod.name)}
                                                className="w-full px-6 py-4 flex items-center justify-between bg-indigo-50/30 dark:bg-slate-800/80 hover:bg-indigo-50/50 dark:hover:bg-slate-700 transition-colors"
                                            >
                                                <div className="flex flex-col text-left">
                                                    <span className="font-bold text-indigo-900 dark:text-indigo-300">{mod.name}</span>
                                                    <span className="text-xs text-slate-500">Módulo de {mod.name.toLowerCase()}</span>
                                                </div>
                                                {isExpanded ? <ChevronUp className="w-5 h-5 text-indigo-500" /> : <ChevronDown className="w-5 h-5 text-indigo-500" />}
                                            </button>
                                            
                                            {/* Tabela de Permissões */}
                                            {isExpanded && (
                                                <div className="px-6 pb-6 pt-2 bg-white dark:bg-slate-800">
                                                    <div className="overflow-x-auto">
                                                        <table className="w-full text-left border-collapse">
                                                            <thead>
                                                                <tr>
                                                                    <th className="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Sub-módulo</th>
                                                                    {actionColumns.map(act => (
                                                                        <th key={act} className="py-3 px-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">{act === 'view' ? 'Ver' : act === 'list' ? 'Listar' : act === 'create' ? 'Criar' : act === 'edit' ? 'Editar' : act === 'delete' ? 'Excluir' : 'Gerenciar'}</th>
                                                                    ))}
                                                                </tr>
                                                            </thead>
                                                            <tbody className="divide-y divide-slate-100 dark:divide-slate-700">
                                                                <tr className="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                                                    <td className="py-4 px-4">
                                                                        <div className="font-medium text-slate-900 dark:text-slate-100">{mod.name}</div>
                                                                        <div className="text-xs text-slate-500 dark:text-slate-400 font-mono">modulo.{mod.name.toLowerCase()}</div>
                                                                    </td>
                                                                    {actionColumns.map(act => {
                                                                        // Encontra se existe uma permissão para essa ação nesse módulo
                                                                        // Ex: act = 'view', module = 'Companies' => expect 'view-companies'
                                                                        const expectedName = `${act}-${mod.name.toLowerCase()}`;
                                                                        const permObj = mod.permissions.find(p => p.action === act || p.name === expectedName);
                                                                        
                                                                        if(!permObj) {
                                                                            return <td key={act} className="text-center py-4 px-4">-</td>;
                                                                        }

                                                                        const isChecked = data.permissions.includes(permObj.name);

                                                                        return (
                                                                            <td key={act} className="text-center py-4 px-4">
                                                                                <label className="inline-flex items-center cursor-pointer">
                                                                                    <input 
                                                                                        type="checkbox" 
                                                                                        className={`w-5 h-5 rounded border-slate-300 focus:ring-indigo-500 ${act === 'delete' ? 'text-red-500 focus:ring-red-500' : 'text-indigo-600'}`}
                                                                                        checked={isChecked}
                                                                                        onChange={() => togglePermission(permObj.name)}
                                                                                    />
                                                                                </label>
                                                                            </td>
                                                                        )
                                                                    })}
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    )
                                })}
                            </div>
                        </div>

                        <div className="flex justify-end pt-4">
                            <button type="submit" disabled={processing} className="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-xl transition-colors font-semibold text-base shadow-sm disabled:opacity-75">
                                <Save className="w-5 h-5" /> {isEdit ? 'Atualizar Perfil do Usuário' : 'Criar Novo Usuário'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
