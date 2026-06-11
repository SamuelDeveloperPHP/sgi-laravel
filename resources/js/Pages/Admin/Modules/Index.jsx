import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, Search, Filter, LayoutDashboard, FileCheck, AlertTriangle, Target, Building, Users, Briefcase, Award } from 'lucide-react';
import Swal from 'sweetalert2';

export default function Index({ auth, modules, filters }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'todos');
    const [visibilityFilter, setVisibilityFilter] = useState(filters.visibility || 'todos');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('admin.modules.index'), {
            search: searchTerm,
            status: statusFilter,
            visibility: visibilityFilter
        }, { preserveState: true, replace: true });
    };

    const deleteModule = (id) => {
        Swal.fire({
            title: 'Tem certeza?',
            text: "Esta ação excluirá o módulo e pode quebrar navegações associadas!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('admin.modules.destroy', id));
            }
        });
    };

    const lucideIcons = { LayoutDashboard, FileCheck, AlertTriangle, Target, Building, Users, Briefcase, Award };

    const DynamicIcon = ({ iconName, className = "h-5 w-5" }) => {
        if (!iconName) return <div className={className} />;
        if (iconName.startsWith('fa-') || iconName.startsWith('fas ') || iconName.startsWith('ri-')) {
            return <i className={`${iconName} ${className} text-indigo-600`} aria-hidden="true"></i>;
        }
        const IconComponent = lucideIcons[iconName];
        if (IconComponent) return <IconComponent className={`${className} text-indigo-600`} aria-hidden="true" />;
        return <i className={`fas fa-cube ${className} text-indigo-600`} aria-hidden="true"></i>; // fallback estético
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Módulos do Sistema</h2>}
        >
            <Head title="Módulos do Sistema" />

            <div className="py-12">
                <div className="w-full sm:px-6 lg:px-8">
                    <div className="bg-white shadow-sm sm:rounded-lg overflow-hidden border border-gray-200">
                        
                        {/* Header Action */}
                        <div className="p-6 border-b border-gray-200 bg-white flex justify-between items-center">
                            <div>
                                <h3 className="text-lg font-bold text-gray-900">Módulos do Sistema</h3>
                                <p className="text-sm text-gray-500">Gerencie os nós de menu e escopos de permissões das telas.</p>
                            </div>
                            <Link
                                href={route('admin.modules.create')}
                                className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm"
                            >
                                <Plus className="w-4 h-4 mr-2" />
                                Novo Módulo
                            </Link>
                        </div>

                        {/* Filters */}
                        <div className="p-4 bg-gray-50 border-b border-gray-200">
                            <form onSubmit={handleSearch} className="flex flex-col sm:flex-row gap-4">
                                <div className="flex-1">
                                    <label className="block text-xs font-medium text-gray-700 mb-1">Buscar (Nome, Rota, Slug)</label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <Search className="h-4 w-4 text-gray-400" />
                                        </div>
                                        <input
                                            type="text"
                                            className="block w-full pl-10 sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Pesquise..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                        />
                                    </div>
                                </div>
                                <div className="sm:w-48">
                                    <label className="block text-xs font-medium text-gray-700 mb-1">Status</label>
                                    <select
                                        className="block w-full sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                        value={statusFilter}
                                        onChange={(e) => setStatusFilter(e.target.value)}
                                    >
                                        <option value="todos">Todos</option>
                                        <option value="ativos">Apenas Ativos</option>
                                        <option value="inativos">Apenas Inativos</option>
                                    </select>
                                </div>
                                <div className="sm:w-48">
                                    <label className="block text-xs font-medium text-gray-700 mb-1">Visibilidade no Menu</label>
                                    <select
                                        className="block w-full sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                        value={visibilityFilter}
                                        onChange={(e) => setVisibilityFilter(e.target.value)}
                                    >
                                        <option value="todos">Todos</option>
                                        <option value="menu">Apenas Visíveis</option>
                                        <option value="ocultos">Apenas Ocultos</option>
                                    </select>
                                </div>
                                <div className="flex items-end">
                                    <button type="submit" className="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm flex items-center">
                                        <Filter className="w-4 h-4 mr-2" />
                                        Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>

                        {/* Table */}
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-white">
                                    <tr>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-semibold text-gray-500 tracking-wider">
                                            # ID
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-semibold text-gray-500 tracking-wider">
                                            IDENTIFICAÇÃO
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-semibold text-gray-500 tracking-wider">
                                            CONFIG. BASE
                                        </th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-semibold text-gray-500 tracking-wider">
                                            STATUS / VISÃO
                                        </th>
                                        <th scope="col" className="relative px-6 py-3">
                                            <span className="sr-only">AÇÕES</span>
                                            <span className="text-xs font-semibold text-gray-500 tracking-wider block text-center">AÇÕES</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {modules.map((module) => (
                                        <tr key={module.id} className="hover:bg-slate-50 transition-colors">
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {String(module.id).padStart(4, '0')}
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center">
                                                    <div className="flex-shrink-0 h-10 w-10 bg-indigo-50 rounded-lg flex items-center justify-center">
                                                        <DynamicIcon iconName={module.icon} />
                                                    </div>
                                                    <div className="ml-4">
                                                        <div className="text-sm font-bold text-gray-900">{module.name}</div>
                                                        <div className="text-xs text-gray-400 font-mono mt-0.5">{module.slug}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-xs text-gray-500 flex flex-col gap-1">
                                                    <div><span className="font-semibold">Rota API:</span> {module.route_name || 'n/a'}</div>
                                                    <div><span className="font-semibold">URL Amigável:</span> {module.url || 'n/a'}</div>
                                                    {module.parent && (
                                                        <div className="mt-1">
                                                            <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                                Pai: #{module.parent_id} - {module.parent.name}
                                                            </span>
                                                        </div>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex flex-col gap-2 items-start">
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${module.is_active ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'}`}>
                                                        {module.is_active ? 'Ativo' : 'Inativo'}
                                                    </span>
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${module.is_visible_in_menu ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-gray-100 text-gray-800 border border-gray-200'}`}>
                                                        {module.is_visible_in_menu ? 'No Menu' : 'Oculto'}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <div className="flex items-center justify-center gap-3">
                                                    <Link
                                                        href={route('admin.modules.edit', module.id)}
                                                        className="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 p-2 rounded-md transition-colors"
                                                        title="Editar"
                                                    >
                                                        <Edit className="w-4 h-4" />
                                                    </Link>
                                                    <button
                                                        onClick={() => deleteModule(module.id)}
                                                        className="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 p-2 rounded-md transition-colors"
                                                        title="Excluir"
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {modules.length === 0 && (
                                        <tr>
                                            <td colSpan="5" className="px-6 py-12 text-center text-gray-500">
                                                <div className="flex flex-col items-center">
                                                    <Search className="w-8 h-8 mb-2 text-gray-300" />
                                                    <p>Nenhum módulo encontrado.</p>
                                                </div>
                                            </td>
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
