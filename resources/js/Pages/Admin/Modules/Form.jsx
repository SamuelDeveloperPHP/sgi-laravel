import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';

export default function Form({ auth, module, parents }) {
    const isEdit = !!module.id;

    const { data, setData, post, put, processing, errors } = useForm({
        name: module.name || '',
        slug: module.slug || '',
        route_name: module.route_name || '',
        url: module.url || '',
        icon: module.icon || '',
        parent_id: module.parent_id || '',
        order: module.order || 0,
        is_active: module.is_active !== undefined ? module.is_active : true,
        is_visible_in_menu: module.is_visible_in_menu !== undefined ? module.is_visible_in_menu : true,
        default_access_policy: module.default_access_policy || 'public',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route('admin.modules.update', module.id));
        } else {
            post(route('admin.modules.store'));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{isEdit ? 'Editar Módulo' : 'Novo Módulo'}</h2>}
        >
            <Head title={isEdit ? 'Editar Módulo' : 'Novo Módulo'} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    
                    <div className="mb-6">
                        <Link href={route('admin.modules.index')} className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 transition-colors">
                            <ArrowLeft className="w-4 h-4 mr-1" />
                            Voltar para a Lista
                        </Link>
                        <h3 className="text-2xl font-bold text-gray-900 mt-2">
                            {isEdit ? `Editar: ${module.name}` : 'Cadastrar Novo Módulo'}
                        </h3>
                        <p className="text-gray-500 text-sm mt-1">
                            Modifique as rotas, ícones ou regras de segurança deste módulo.
                        </p>
                    </div>

                    <div className="bg-white shadow-sm sm:rounded-xl border border-gray-200 overflow-hidden">
                        <form onSubmit={handleSubmit} className="p-8">
                            
                            <h4 className="text-lg font-semibold text-gray-900 mb-6 pb-2 border-b border-gray-100">
                                Informações Iniciais
                            </h4>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div>
                                    <InputLabel htmlFor="name" value="Nome do Módulo (Visível)" />
                                    <TextInput
                                        id="name"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.name} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="slug" value="Chave Única (Slug / Permissão)" />
                                    <TextInput
                                        id="slug"
                                        type="text"
                                        className="mt-1 block w-full bg-slate-50"
                                        value={data.slug}
                                        onChange={(e) => setData('slug', e.target.value)}
                                        required
                                    />
                                    <p className="text-xs text-gray-500 mt-1">Isso será usado como `permission` no sistema.</p>
                                    <InputError message={errors.slug} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="route_name" value="Nome da Rota (Laravel)" />
                                    <TextInput
                                        id="route_name"
                                        type="text"
                                        className="mt-1 block w-full font-mono text-sm"
                                        value={data.route_name}
                                        onChange={(e) => setData('route_name', e.target.value)}
                                        placeholder="ex: admin.frota.index"
                                    />
                                    <InputError message={errors.route_name} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="url" value="URL Amigável" />
                                    <TextInput
                                        id="url"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.url}
                                        onChange={(e) => setData('url', e.target.value)}
                                        placeholder="ex: admin/frota"
                                    />
                                    <InputError message={errors.url} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="icon" value="Ícone (Remix Icon / Lucide)" />
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i className={`${data.icon || 'fas fa-cube'} text-gray-400`} aria-hidden="true"></i>
                                        </div>
                                        <TextInput
                                            id="icon"
                                            type="text"
                                            className="mt-1 block w-full pl-10"
                                            value={data.icon}
                                            onChange={(e) => setData('icon', e.target.value)}
                                            placeholder="fa-solid fa-gas-pump"
                                        />
                                    </div>
                                    <p className="text-xs text-gray-500 mt-1">Use classes do FontAwesome ou Remix.</p>
                                    <InputError message={errors.icon} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="parent_id" value="Módulo Pai (agrupador no menu)" />
                                    <select
                                        id="parent_id"
                                        className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        value={data.parent_id}
                                        onChange={(e) => setData('parent_id', e.target.value)}
                                    >
                                        <option value="">Nenhum (Item Raiz)</option>
                                        {parents.map((parent) => (
                                            <option key={parent.id} value={parent.id}>{parent.name}</option>
                                        ))}
                                    </select>
                                    <p className="text-xs text-gray-500 mt-1">Selecione para criar um sub-item.</p>
                                    <InputError message={errors.parent_id} className="mt-2" />
                                </div>
                                
                                <div>
                                    <InputLabel htmlFor="order" value="Ordem de Exibição" />
                                    <TextInput
                                        id="order"
                                        type="number"
                                        className="mt-1 block w-full"
                                        value={data.order}
                                        onChange={(e) => setData('order', e.target.value)}
                                    />
                                    <InputError message={errors.order} className="mt-2" />
                                </div>

                                <div className="md:col-span-2">
                                    <InputLabel htmlFor="default_access_policy" value="Disponibilidade padrao" />
                                    <select
                                        id="default_access_policy"
                                        className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        value={data.default_access_policy}
                                        onChange={(e) => setData('default_access_policy', e.target.value)}
                                    >
                                        <option value="public">Modulo publico</option>
                                        <option value="trial_15">Disponivel por 15 dias</option>
                                        <option value="trial_30">Disponivel por 30 dias</option>
                                        <option value="private">Privado / Administrador Master</option>
                                    </select>
                                    <p className="text-xs text-gray-500 mt-1">Define o acesso padrao para novas empresas e contas publicas temporarias.</p>
                                    <InputError message={errors.default_access_policy} className="mt-2" />
                                </div>
                            </div>

                            <div className="space-y-4 mb-8 bg-slate-50 p-4 rounded-lg border border-slate-200">
                                <div className="flex items-center">
                                    <button
                                        type="button"
                                        role="switch"
                                        aria-checked={data.is_active}
                                        onClick={() => setData('is_active', !data.is_active)}
                                        className={`${data.is_active ? 'bg-indigo-600' : 'bg-gray-200'} relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2`}
                                    >
                                        <span className="sr-only">Ativar Módulo</span>
                                        <span aria-hidden="true" className={`${data.is_active ? 'translate-x-5' : 'translate-x-0'} pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out`}></span>
                                    </button>
                                    <span className="ml-3 text-sm font-medium text-gray-900">Módulo Ativável no Sistema</span>
                                </div>

                                <div className="flex items-center">
                                    <button
                                        type="button"
                                        role="switch"
                                        aria-checked={data.is_visible_in_menu}
                                        onClick={() => setData('is_visible_in_menu', !data.is_visible_in_menu)}
                                        className={`${data.is_visible_in_menu ? 'bg-blue-500' : 'bg-gray-200'} relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2`}
                                    >
                                        <span className="sr-only">Visível no Menu</span>
                                        <span aria-hidden="true" className={`${data.is_visible_in_menu ? 'translate-x-5' : 'translate-x-0'} pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out`}></span>
                                    </button>
                                    <span className="ml-3 text-sm font-medium text-gray-900">Visível na Barra Lateral (Menu)</span>
                                </div>
                            </div>

                            <div className="flex items-center justify-end gap-4 pt-4 border-t border-gray-100">
                                <Link 
                                    href={route('admin.modules.index')}
                                    className="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors"
                                >
                                    Cancelar
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center px-6 py-2.5 bg-indigo-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors disabled:opacity-75 disabled:cursor-not-allowed shadow-sm"
                                >
                                    <Save className="w-4 h-4 mr-2" />
                                    {isEdit ? 'Salvar Configurações' : 'Cadastrar Módulo'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
