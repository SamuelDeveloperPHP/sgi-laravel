import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Plus, Pencil, Trash2 } from 'lucide-react';
import Pagination from '@/Components/Pagination';

export default function CargosIndex({ auth, cargos, companies, filters }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingCargo, setEditingCargo] = useState(null);

    const { data, setData, post, put, delete: destroy, processing, errors, reset } = useForm({
        company_id: '',
        nome: '',
        salario_base: '',
        descricao: '',
    });

    const openModal = (cargo = null) => {
        if (cargo) {
            setEditingCargo(cargo);
            setData({
                company_id: cargo.company_id || '',
                nome: cargo.nome,
                salario_base: cargo.salario_base || '',
                descricao: cargo.descricao || '',
            });
        } else {
            setEditingCargo(null);
            reset();
        }
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        reset();
    };

    const submit = (e) => {
        e.preventDefault();
        if (editingCargo) {
            put(route('admin.hr.cargos.update', editingCargo.id), {
                onSuccess: () => closeModal(),
            });
        } else {
            post(route('admin.hr.cargos.store'), {
                onSuccess: () => closeModal(),
            });
        }
    };

    const handleDelete = (cargo) => {
        if (confirm(`Tem certeza que deseja excluir o cargo ${cargo.nome}?`)) {
            destroy(route('admin.hr.cargos.destroy', cargo.id));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Cargos e Salários</h2>}
        >
            <Head title="Cargos - Recursos Humanos" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex justify-between items-center mb-6">
                            <h3 className="text-lg font-medium text-gray-900">Lista de Cargos</h3>
                            <button
                                onClick={() => openModal()}
                                className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150 gap-2"
                            >
                                <Plus className="w-4 h-4" /> Novo Cargo
                            </button>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left text-gray-500">
                                <thead className="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                                    <tr>
                                        <th className="px-6 py-3">Nome do Cargo</th>
                                        <th className="px-6 py-3">Salário Base (R$)</th>
                                        <th className="px-6 py-3">Descrição</th>
                                        <th className="px-6 py-3 text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {cargos.data.length > 0 ? (
                                        cargos.data.map((cargo) => (
                                            <tr key={cargo.id} className="bg-white border-b hover:bg-gray-50">
                                                <td className="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">{cargo.nome}</td>
                                                <td className="px-6 py-4 text-green-600 font-medium">
                                                    {cargo.salario_base ? `R$ ${Number(cargo.salario_base).toLocaleString('pt-BR', {minimumFractionDigits: 2})}` : '-'}
                                                </td>
                                                <td className="px-6 py-4">{cargo.descricao || '-'}</td>
                                                <td className="px-6 py-4 text-right space-x-2">
                                                    <button onClick={() => openModal(cargo)} className="text-indigo-600 hover:text-indigo-900" title="Editar">
                                                        <Pencil className="w-4 h-4 inline" />
                                                    </button>
                                                    <button onClick={() => handleDelete(cargo)} className="text-red-600 hover:text-red-900" title="Excluir">
                                                        <Trash2 className="w-4 h-4 inline" />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="4" className="px-6 py-4 text-center text-gray-500">
                                                Nenhum cargo cadastrado.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <div className="mt-4">
                            <Pagination links={cargos.links} />
                        </div>
                    </div>
                </div>
            </div>

            {/* Modal */}
            {isModalOpen && (
                <div className="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={closeModal}></div>
                        <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <form onSubmit={submit}>
                                <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                        {editingCargo ? 'Editar Cargo' : 'Novo Cargo'}
                                    </h3>
                                    
                                    <div className="space-y-4">
                                        {auth.user.is_master_admin && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700">Empresa Vinculada *</label>
                                                <select
                                                    className="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                                    value={data.company_id}
                                                    onChange={e => setData('company_id', e.target.value)}
                                                    required
                                                >
                                                    <option value="">Selecione uma empresa</option>
                                                    {companies && companies.map(c => (
                                                        <option key={c.id} value={c.id}>{c.razao_social}</option>
                                                    ))}
                                                </select>
                                                {errors.company_id && <div className="text-red-500 text-xs mt-1">{errors.company_id}</div>}
                                            </div>
                                        )}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Nome do Cargo</label>
                                            <input
                                                type="text"
                                                className="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                                value={data.nome}
                                                onChange={e => setData('nome', e.target.value)}
                                                required
                                            />
                                            {errors.nome && <div className="text-red-500 text-xs mt-1">{errors.nome}</div>}
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Salário Base (Piso)</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                className="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                                value={data.salario_base}
                                                onChange={e => setData('salario_base', e.target.value)}
                                            />
                                            {errors.salario_base && <div className="text-red-500 text-xs mt-1">{errors.salario_base}</div>}
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Descrição</label>
                                            <textarea
                                                className="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                                value={data.descricao}
                                                onChange={e => setData('descricao', e.target.value)}
                                                rows="3"
                                            ></textarea>
                                            {errors.descricao && <div className="text-red-500 text-xs mt-1">{errors.descricao}</div>}
                                        </div>
                                    </div>
                                </div>
                                <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                                    >
                                        Salvar
                                    </button>
                                    <button
                                        type="button"
                                        onClick={closeModal}
                                        className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                    >
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
