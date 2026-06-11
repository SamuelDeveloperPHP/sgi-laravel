import React, { useState } from 'react';
import Modal from '@/Components/Modal';
import { useForm, router } from '@inertiajs/react';
import { Trash2, Plus, Calendar, User, FileText, CheckCircle } from 'lucide-react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import Select from 'react-select';
import dayjs from 'dayjs';

export default function RevisoesModal({ show, onClose, documento, users }) {
    const [isAdding, setIsAdding] = useState(false);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        revisao: '',
        data_revisao: dayjs().format('YYYY-MM-DD'),
        alteracoes: '',
        responsavel_id: '',
        aprovador_id: '',
    });

    const userOptions = users ? users.map(user => ({
        value: user.id,
        label: user.name
    })) : [];

    const handleOpenAdd = () => {
        reset();
        clearErrors();
        setIsAdding(true);
    };

    const submitAdd = (e) => {
        e.preventDefault();
        post(route('controle-documentos.revisoes.store', documento.id), {
            onSuccess: () => {
                setIsAdding(false);
                reset();
            }
        });
    };

    const deleteRevisao = (revisaoId) => {
        if (confirm('Tem certeza que deseja excluir esta revisão do histórico?')) {
            router.delete(route('controle-documentos.revisoes.destroy', { id: documento.id, revisaoId: revisaoId }), {
                preserveScroll: true
            });
        }
    };

    if (!documento) return null;

    return (
        <Modal show={show} onClose={onClose} maxWidth="4xl">
            <div className="p-6 bg-slate-50 dark:bg-slate-900 min-h-[500px]">
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h2 className="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                            <FileText className="h-5 w-5 text-indigo-500" />
                            Histórico de Revisão
                        </h2>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            {documento.codigo ? `${documento.codigo} - ` : ''}{documento.identificacao}
                        </p>
                    </div>
                    
                    {!isAdding && (
                        <button
                            onClick={handleOpenAdd}
                            className="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-semibold transition"
                        >
                            <Plus className="h-4 w-4 mr-2" />
                            Adicionar Revisão
                        </button>
                    )}
                </div>

                {isAdding ? (
                    <div className="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 mb-6">
                        <h3 className="text-md font-semibold text-slate-800 dark:text-slate-200 mb-4">Nova Revisão</h3>
                        <form onSubmit={submitAdd} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel value="Número da Revisão *" />
                                    <TextInput
                                        className="mt-1 block w-full"
                                        value={data.revisao}
                                        onChange={(e) => setData('revisao', e.target.value)}
                                        placeholder="Ex: 01, 02..."
                                        required
                                    />
                                    <InputError message={errors.revisao} className="mt-1" />
                                </div>
                                <div>
                                    <InputLabel value="Data da Revisão *" />
                                    <TextInput
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.data_revisao}
                                        onChange={(e) => setData('data_revisao', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.data_revisao} className="mt-1" />
                                </div>
                            </div>

                            <div>
                                <InputLabel value="Alterações / Motivo" />
                                <textarea
                                    className="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm w-full mt-1"
                                    rows="3"
                                    value={data.alteracoes}
                                    onChange={(e) => setData('alteracoes', e.target.value)}
                                />
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel value="Responsável pela Alteração" />
                                    <Select
                                        options={userOptions}
                                        value={userOptions.find(o => o.value === data.responsavel_id)}
                                        onChange={(val) => setData('responsavel_id', val ? val.value : '')}
                                        className="react-select-container mt-1"
                                        classNamePrefix="react-select"
                                        isClearable
                                        menuPosition="fixed"
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Aprovador" />
                                    <Select
                                        options={userOptions}
                                        value={userOptions.find(o => o.value === data.aprovador_id)}
                                        onChange={(val) => setData('aprovador_id', val ? val.value : '')}
                                        className="react-select-container mt-1"
                                        classNamePrefix="react-select"
                                        isClearable
                                        menuPosition="fixed"
                                    />
                                </div>
                            </div>

                            <div className="flex justify-end gap-2 pt-4">
                                <button
                                    type="button"
                                    onClick={() => setIsAdding(false)}
                                    className="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-md hover:bg-slate-50 dark:hover:bg-slate-800 text-sm transition"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-semibold transition disabled:opacity-50"
                                >
                                    Salvar Revisão
                                </button>
                            </div>
                        </form>
                    </div>
                ) : null}

                <div className="space-y-4">
                    {documento.revisoes && documento.revisoes.length > 0 ? (
                        documento.revisoes.map((rev) => (
                            <div key={rev.id} className="bg-white dark:bg-slate-800 rounded-lg p-5 shadow-sm border border-slate-200 dark:border-slate-700 relative">
                                <button 
                                    onClick={() => deleteRevisao(rev.id)}
                                    className="absolute top-4 right-4 text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20"
                                    title="Excluir Revisão"
                                >
                                    <Trash2 className="h-4 w-4" />
                                </button>
                                
                                <div className="flex items-center gap-3 mb-3">
                                    <span className="bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 text-xs font-bold px-3 py-1 rounded-full">
                                        REV {rev.revisao}
                                    </span>
                                    <span className="text-sm text-slate-500 dark:text-slate-400 flex items-center">
                                        <Calendar className="h-4 w-4 mr-1" />
                                        {dayjs(rev.data_revisao).format('DD/MM/YYYY')}
                                    </span>
                                </div>
                                
                                <p className="text-slate-700 dark:text-slate-300 text-sm mb-4">
                                    <span className="font-semibold block mb-1">Alterações:</span>
                                    {rev.alteracoes || <span className="text-slate-400 italic">Não detalhadas</span>}
                                </p>
                                
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-slate-100 dark:border-slate-700 pt-3">
                                    <div className="flex items-center text-sm">
                                        <User className="h-4 w-4 text-slate-400 mr-2" />
                                        <span className="text-slate-600 dark:text-slate-400 mr-1">Responsável:</span>
                                        <span className="font-medium text-slate-800 dark:text-slate-200">{rev.responsavel ? rev.responsavel.name : '-'}</span>
                                    </div>
                                    <div className="flex items-center text-sm">
                                        <CheckCircle className="h-4 w-4 text-emerald-500 mr-2" />
                                        <span className="text-slate-600 dark:text-slate-400 mr-1">Aprovado por:</span>
                                        <span className="font-medium text-slate-800 dark:text-slate-200">{rev.aprovador ? rev.aprovador.name : '-'}</span>
                                    </div>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="text-center py-12 bg-white dark:bg-slate-800 rounded-lg border border-dashed border-slate-300 dark:border-slate-700">
                            <FileText className="h-10 w-10 text-slate-400 mx-auto mb-3" />
                            <h3 className="text-lg font-medium text-slate-900 dark:text-white">Nenhum histórico encontrado</h3>
                            <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">Este documento ainda não possui revisões registradas.</p>
                        </div>
                    )}
                </div>

                <div className="mt-8 flex justify-end border-t border-slate-200 dark:border-slate-700 pt-4">
                    <button
                        onClick={onClose}
                        className="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-md hover:bg-slate-300 dark:hover:bg-slate-600 font-semibold text-sm transition"
                    >
                        Fechar Janela
                    </button>
                </div>
            </div>
        </Modal>
    );
}
