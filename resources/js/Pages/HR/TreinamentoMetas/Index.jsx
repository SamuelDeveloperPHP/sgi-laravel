import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Plus, Pencil, Trash2 } from 'lucide-react';
import Pagination from '@/Components/Pagination';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Swal from 'sweetalert2';

export default function MetasIndex({ auth, metas }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingMeta, setEditingMeta] = useState(null);

    const { data, setData, post, put, delete: destroy, processing, errors, reset } = useForm({
        ano: new Date().getFullYear(),
        meta_horas_treinamento: '',
    });

    const openModal = (meta = null) => {
        if (meta) {
            setEditingMeta(meta);
            setData({
                ano: meta.ano,
                meta_horas_treinamento: meta.meta_horas_treinamento,
            });
        } else {
            setEditingMeta(null);
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
        if (editingMeta) {
            put(route('treinamentos-metas.update', editingMeta.id), {
                onSuccess: () => closeModal(),
            });
        } else {
            post(route('treinamentos-metas.store'), {
                onSuccess: () => closeModal(),
            });
        }
    };

    const handleDelete = (meta) => {
        Swal.fire({
            title: 'Tem certeza?',
            text: `Deseja realmente excluir a meta de ${meta.ano}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                destroy(route('treinamentos-metas.destroy', meta.id));
            }
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Metas de Treinamento</h2>}
        >
            <Head title="Metas de Treinamento" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex justify-between items-center mb-6">
                            <h3 className="text-lg font-medium text-gray-900">Metas Anuais</h3>
                            <button
                                onClick={() => openModal()}
                                className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 transition gap-2"
                            >
                                <Plus className="w-4 h-4" /> Nova Meta
                            </button>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left text-gray-500">
                                <thead className="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3">Ano</th>
                                        <th className="px-6 py-3">Meta (Horas)</th>
                                        <th className="px-6 py-3 text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {metas.data.length > 0 ? (
                                        metas.data.map((meta) => (
                                            <tr key={meta.id} className="bg-white border-b hover:bg-gray-50">
                                                <td className="px-6 py-4 font-medium text-gray-900">{meta.ano}</td>
                                                <td className="px-6 py-4">{meta.meta_horas_treinamento}h</td>
                                                <td className="px-6 py-4 text-right">
                                                    <button onClick={() => openModal(meta)} className="text-blue-600 hover:text-blue-900 mr-3">
                                                        <Pencil className="w-4 h-4" />
                                                    </button>
                                                    <button onClick={() => handleDelete(meta)} className="text-red-600 hover:text-red-900">
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="3" className="px-6 py-4 text-center text-gray-500">Nenhuma meta cadastrada.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <Pagination links={metas.links} className="mt-6" />
                    </div>
                </div>
            </div>

            <Modal show={isModalOpen} onClose={closeModal} maxWidth="xl">
                <form onSubmit={submit} className="p-6 bg-slate-50 rounded-lg">
                    <h2 className="text-lg font-medium text-gray-900 mb-6">
                        {editingMeta ? 'Editar Meta' : 'Nova Meta'}
                    </h2>
                    <div className="space-y-6">
                        <div>
                            <InputLabel htmlFor="ano" value="Ano de Referência" />
                            <TextInput id="ano" type="number" min="2000" max="2100" className="mt-1 block w-full" value={data.ano} onChange={e => setData('ano', e.target.value)} required />
                            <InputError message={errors.ano} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="meta_horas_treinamento" value="Meta de Horas (Total p/ Empresa)" />
                            <TextInput id="meta_horas_treinamento" type="number" min="1" className="mt-1 block w-full" value={data.meta_horas_treinamento} onChange={e => setData('meta_horas_treinamento', e.target.value)} required />
                            <InputError message={errors.meta_horas_treinamento} className="mt-2" />
                        </div>
                    </div>
                    <div className="mt-6 flex justify-end">
                        <SecondaryButton onClick={closeModal}>Cancelar</SecondaryButton>
                        <PrimaryButton className="ml-3" disabled={processing}>Salvar</PrimaryButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
