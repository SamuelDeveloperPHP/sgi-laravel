import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Plus, Pencil, Trash2, Users } from 'lucide-react';
import Pagination from '@/Components/Pagination';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Swal from 'sweetalert2';

export default function TreinamentosIndex({ auth, treinamentos, cursos, locais }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingTreinamento, setEditingTreinamento] = useState(null);

    const { data, setData, post, put, delete: destroy, processing, errors, reset } = useForm({
        curso_id: '',
        local_treinamento_id: '',
        instrutor: '',
        data_inicio: '',
        data_fim: '',
        status: 'Agendado',
    });

    const openModal = (treinamento = null) => {
        if (treinamento) {
            setEditingTreinamento(treinamento);
            setData({
                curso_id: treinamento.curso_id,
                local_treinamento_id: treinamento.local_treinamento_id || '',
                instrutor: treinamento.instrutor || '',
                data_inicio: treinamento.data_inicio,
                data_fim: treinamento.data_fim || '',
                status: treinamento.status || 'Agendado',
            });
        } else {
            setEditingTreinamento(null);
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
        if (editingTreinamento) {
            put(route('treinamentos.update', editingTreinamento.id), {
                onSuccess: () => closeModal(),
            });
        } else {
            post(route('treinamentos.store'), {
                onSuccess: () => closeModal(),
            });
        }
    };

    const handleDelete = (treinamento) => {
        Swal.fire({
            title: 'Tem certeza?',
            text: `Deseja realmente excluir esta turma?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                destroy(route('treinamentos.destroy', treinamento.id));
            }
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Turmas de Treinamento</h2>}
        >
            <Head title="Treinamentos" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex justify-between items-center mb-6">
                            <h3 className="text-lg font-medium text-gray-900">Agenda de Treinamentos</h3>
                            <button
                                onClick={() => openModal()}
                                className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition gap-2"
                            >
                                <Plus className="w-4 h-4" /> Nova Turma
                            </button>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left text-gray-500">
                                <thead className="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3">Curso</th>
                                        <th className="px-6 py-3">Local</th>
                                        <th className="px-6 py-3">Data</th>
                                        <th className="px-6 py-3">Status</th>
                                        <th className="px-6 py-3 text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {treinamentos.data.length > 0 ? (
                                        treinamentos.data.map((treinamento) => (
                                            <tr key={treinamento.id} className="bg-white border-b hover:bg-gray-50">
                                                <td className="px-6 py-4 font-medium text-gray-900">{treinamento.curso?.nome}</td>
                                                <td className="px-6 py-4">{treinamento.local?.nome || 'Não definido'}</td>
                                                <td className="px-6 py-4">
                                                    {new Date(treinamento.data_inicio + 'T00:00:00').toLocaleDateString()}
                                                    {treinamento.data_fim && treinamento.data_fim !== treinamento.data_inicio && 
                                                        ` até ${new Date(treinamento.data_fim + 'T00:00:00').toLocaleDateString()}`
                                                    }
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className={`px-2 py-1 rounded text-xs font-semibold ${
                                                        treinamento.status === 'Concluído' ? 'bg-green-100 text-green-800' :
                                                        treinamento.status === 'Agendado' ? 'bg-blue-100 text-blue-800' :
                                                        treinamento.status === 'Cancelado' ? 'bg-red-100 text-red-800' :
                                                        'bg-yellow-100 text-yellow-800'
                                                    }`}>
                                                        {treinamento.status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-right flex justify-end items-center gap-3">
                                                    <Link href={route('treinamentos.presencas', treinamento.id)} className="text-indigo-600 hover:text-indigo-900 flex items-center gap-1" title="Lista de Presença">
                                                        <Users className="w-4 h-4" /> Alunos
                                                    </Link>
                                                    <button onClick={() => openModal(treinamento)} className="text-blue-600 hover:text-blue-900">
                                                        <Pencil className="w-4 h-4" />
                                                    </button>
                                                    <button onClick={() => handleDelete(treinamento)} className="text-red-600 hover:text-red-900">
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="5" className="px-6 py-4 text-center text-gray-500">Nenhum treinamento agendado.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <Pagination links={treinamentos.links} className="mt-6" />
                    </div>
                </div>
            </div>

            <Modal show={isModalOpen} onClose={closeModal} maxWidth="2xl">
                <form onSubmit={submit} className="p-6 bg-slate-50 rounded-lg">
                    <h2 className="text-lg font-medium text-gray-900 mb-6">
                        {editingTreinamento ? 'Editar Turma' : 'Nova Turma'}
                    </h2>
                    <div className="space-y-6">
                        <div>
                            <InputLabel htmlFor="curso_id" value="Curso" />
                            <select id="curso_id" className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" value={data.curso_id} onChange={e => setData('curso_id', e.target.value)} required>
                                <option value="">Selecione o Curso</option>
                                {cursos.map(c => <option key={c.id} value={c.id}>{c.nome}</option>)}
                            </select>
                            <InputError message={errors.curso_id} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="local_treinamento_id" value="Local" />
                            <select id="local_treinamento_id" className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" value={data.local_treinamento_id} onChange={e => setData('local_treinamento_id', e.target.value)}>
                                <option value="">Selecione o Local (Opcional)</option>
                                {locais.map(l => <option key={l.id} value={l.id}>{l.nome}</option>)}
                            </select>
                            <InputError message={errors.local_treinamento_id} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="instrutor" value="Instrutor" />
                            <TextInput id="instrutor" type="text" className="mt-1 block w-full" value={data.instrutor} onChange={e => setData('instrutor', e.target.value)} />
                            <InputError message={errors.instrutor} className="mt-2" />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel htmlFor="data_inicio" value="Data de Início" />
                                <TextInput id="data_inicio" type="date" className="mt-1 block w-full" value={data.data_inicio} onChange={e => setData('data_inicio', e.target.value)} required />
                                <InputError message={errors.data_inicio} className="mt-2" />
                            </div>
                            <div>
                                <InputLabel htmlFor="data_fim" value="Data de Término" />
                                <TextInput id="data_fim" type="date" className="mt-1 block w-full" value={data.data_fim} onChange={e => setData('data_fim', e.target.value)} />
                                <InputError message={errors.data_fim} className="mt-2" />
                            </div>
                        </div>
                        <div>
                            <InputLabel htmlFor="status" value="Status" />
                            <select id="status" className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" value={data.status} onChange={e => setData('status', e.target.value)} required>
                                <option value="Agendado">Agendado</option>
                                <option value="Em Andamento">Em Andamento</option>
                                <option value="Concluído">Concluído</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                            <InputError message={errors.status} className="mt-2" />
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
