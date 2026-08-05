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

export default function CursosIndex({ auth, cursos }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingCurso, setEditingCurso] = useState(null);

    const { data, setData, post, put, delete: destroy, processing, errors, reset } = useForm({
        nome: '',
        descricao: '',
        carga_horaria: 0,
    });

    const openModal = (curso = null) => {
        if (curso) {
            setEditingCurso(curso);
            setData({
                nome: curso.nome,
                descricao: curso.descricao || '',
                carga_horaria: curso.carga_horaria || 0,
            });
        } else {
            setEditingCurso(null);
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
        if (editingCurso) {
            put(route('treinamentos-cursos.update', editingCurso.id), {
                onSuccess: () => closeModal(),
            });
        } else {
            post(route('treinamentos-cursos.store'), {
                onSuccess: () => closeModal(),
            });
        }
    };

    const handleDelete = (curso) => {
        Swal.fire({
            title: 'Tem certeza?',
            text: `Deseja realmente excluir o curso ${curso.nome}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                destroy(route('treinamentos-cursos.destroy', curso.id));
            }
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Cursos</h2>}
        >
            <Head title="Cursos - Treinamentos" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex justify-between items-center mb-6">
                            <h3 className="text-lg font-medium text-gray-900">Lista de Cursos</h3>
                            <button
                                onClick={() => openModal()}
                                className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition gap-2"
                            >
                                <Plus className="w-4 h-4" /> Novo Curso
                            </button>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left text-gray-500">
                                <thead className="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3">Nome do Curso</th>
                                        <th className="px-6 py-3">Carga Horária</th>
                                        <th className="px-6 py-3 text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {cursos.data.length > 0 ? (
                                        cursos.data.map((curso) => (
                                            <tr key={curso.id} className="bg-white border-b hover:bg-gray-50">
                                                <td className="px-6 py-4 font-medium text-gray-900">{curso.nome}</td>
                                                <td className="px-6 py-4">{curso.carga_horaria}h</td>
                                                <td className="px-6 py-4 text-right">
                                                    <button onClick={() => openModal(curso)} className="text-blue-600 hover:text-blue-900 mr-3">
                                                        <Pencil className="w-4 h-4" />
                                                    </button>
                                                    <button onClick={() => handleDelete(curso)} className="text-red-600 hover:text-red-900">
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="3" className="px-6 py-4 text-center text-gray-500">Nenhum curso cadastrado.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <Pagination links={cursos.links} className="mt-6" />
                    </div>
                </div>
            </div>

            <Modal show={isModalOpen} onClose={closeModal} maxWidth="2xl">
                <form onSubmit={submit} className="p-6 bg-slate-50 rounded-lg">
                    <h2 className="text-lg font-medium text-gray-900 mb-6">
                        {editingCurso ? 'Editar Curso' : 'Novo Curso'}
                    </h2>
                    <div className="space-y-6">
                        <div>
                            <InputLabel htmlFor="nome" value="Nome do Curso" />
                            <TextInput id="nome" type="text" className="mt-1 block w-full" value={data.nome} onChange={e => setData('nome', e.target.value)} required />
                            <InputError message={errors.nome} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="descricao" value="Descrição" />
                            <textarea
                                id="descricao"
                                className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                rows="3"
                                value={data.descricao}
                                onChange={e => setData('descricao', e.target.value)}
                            />
                            <InputError message={errors.descricao} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="carga_horaria" value="Carga Horária (horas)" />
                            <TextInput id="carga_horaria" type="number" min="0" className="mt-1 block w-full" value={data.carga_horaria} onChange={e => setData('carga_horaria', e.target.value)} required />
                            <InputError message={errors.carga_horaria} className="mt-2" />
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
