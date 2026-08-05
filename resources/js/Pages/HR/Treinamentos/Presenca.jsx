import React, { useState } from 'react';
import { Head, useForm, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ArrowLeft, Trash2, CheckCircle, XCircle, Plus } from 'lucide-react';
import PrimaryButton from '@/Components/PrimaryButton';
import Swal from 'sweetalert2';

export default function PresencaIndex({ auth, treinamento, funcionarios }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        funcionario_id: '',
    });

    const addAluno = (e) => {
        e.preventDefault();
        post(route('treinamentos.addAluno', treinamento.id), {
            onSuccess: () => reset('funcionario_id'),
        });
    };

    const removeAluno = (presencaId) => {
        Swal.fire({
            title: 'Tem certeza?',
            text: "Remover aluno desta turma?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, remover!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('treinamentos.removeAluno', [treinamento.id, presencaId]));
            }
        });
    };

    const togglePresenca = (presencaId, currentState) => {
        router.put(route('treinamentos.togglePresenca', [treinamento.id, presencaId]), {
            presente: !currentState
        });
    };

    // Filter available funcionarios to not show the ones already in the list
    const addedFuncionarioIds = treinamento.presencas.map(p => p.funcionario_id);
    const availableFuncionarios = funcionarios.filter(f => !addedFuncionarioIds.includes(f.id));

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center gap-4">
                    <Link href={route('treinamentos.index')} className="text-gray-500 hover:text-gray-700">
                        <ArrowLeft className="w-6 h-6" />
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Lista de Presença: {treinamento.curso?.nome}
                    </h2>
                </div>
            }
        >
            <Head title="Lista de Presença" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    {/* Painel Esquerdo: Info da Turma e Adição de Alunos */}
                    <div className="md:col-span-1 space-y-6">
                        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Detalhes da Turma</h3>
                            <div className="space-y-3 text-sm text-gray-600">
                                <p><span className="font-semibold text-gray-800">Local:</span> {treinamento.local?.nome || 'N/D'}</p>
                                <p><span className="font-semibold text-gray-800">Instrutor:</span> {treinamento.instrutor || 'N/D'}</p>
                                <p><span className="font-semibold text-gray-800">Início:</span> {new Date(treinamento.data_inicio + 'T00:00:00').toLocaleDateString()}</p>
                                <p><span className="font-semibold text-gray-800">Status:</span> {treinamento.status}</p>
                            </div>
                        </div>

                        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Adicionar Aluno</h3>
                            <form onSubmit={addAluno} className="space-y-4">
                                <div>
                                    <select 
                                        className="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        value={data.funcionario_id}
                                        onChange={e => setData('funcionario_id', e.target.value)}
                                        required
                                    >
                                        <option value="">Selecione um funcionário...</option>
                                        {availableFuncionarios.map(f => (
                                            <option key={f.id} value={f.id}>{f.nome}</option>
                                        ))}
                                    </select>
                                    {errors.funcionario_id && <p className="mt-2 text-sm text-red-600">{errors.funcionario_id}</p>}
                                </div>
                                <PrimaryButton className="w-full justify-center gap-2" disabled={processing || !data.funcionario_id}>
                                    <Plus className="w-4 h-4" /> Incluir na Turma
                                </PrimaryButton>
                            </form>
                        </div>
                    </div>

                    {/* Painel Direito: Lista de Alunos na Turma */}
                    <div className="md:col-span-2">
                        <div className="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                            <div className="p-6 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900">Alunos Matriculados ({treinamento.presencas.length})</h3>
                            </div>
                            
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm text-left text-gray-500">
                                    <thead className="text-xs text-gray-700 uppercase bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3">Aluno</th>
                                            <th className="px-6 py-3 text-center">Presença</th>
                                            <th className="px-6 py-3 text-right">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {treinamento.presencas.length > 0 ? (
                                            treinamento.presencas.map((presenca) => (
                                                <tr key={presenca.id} className="bg-white border-b hover:bg-gray-50">
                                                    <td className="px-6 py-4 font-medium text-gray-900">
                                                        {presenca.funcionario?.nome}
                                                    </td>
                                                    <td className="px-6 py-4 text-center">
                                                        <button 
                                                            onClick={() => togglePresenca(presenca.id, presenca.presente)}
                                                            className={`inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold transition ${
                                                                presenca.presente 
                                                                    ? 'bg-green-100 text-green-700 hover:bg-green-200' 
                                                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                                            }`}
                                                        >
                                                            {presenca.presente ? <CheckCircle className="w-4 h-4" /> : <XCircle className="w-4 h-4" />}
                                                            {presenca.presente ? 'Presente' : 'Faltou'}
                                                        </button>
                                                    </td>
                                                    <td className="px-6 py-4 text-right">
                                                        <button onClick={() => removeAluno(presenca.id)} className="text-red-600 hover:text-red-900" title="Remover da turma">
                                                            <Trash2 className="w-4 h-4" />
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan="3" className="px-6 py-8 text-center text-gray-500">
                                                    Nenhum aluno matriculado nesta turma. <br/> Adicione alunos pelo painel lateral.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
