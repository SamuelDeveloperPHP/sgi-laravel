import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css';
import { Save, ArrowLeft } from 'lucide-react';
import Select from 'react-select';

export default function Form({ auth, ata, users, companyId, participantesIds = [] }) {
    
    // Preparar as opções do Select Múltiplo
    const userOptions = users.map(u => ({ value: u.id, label: u.name }));
    const defaultSelected = userOptions.filter(o => participantesIds.includes(o.value));

    const { data, setData, post, put, processing, errors } = useForm({
        company_id: companyId,
        data: ata ? ata.data.split('T')[0] : '', // Se vier data do BD
        hora_inicio: ata ? ata.hora_inicio : '',
        hora_termino: ata ? ata.hora_termino : '',
        local: ata ? ata.local : '',
        assunto: ata ? ata.assunto : '',
        pautas: ata ? ata.pautas : '',
        registro: ata ? ata.registro : '',
        participantes: participantesIds
    });

    const handleSelectChange = (selectedOptions) => {
        setData('participantes', selectedOptions ? selectedOptions.map(o => o.value) : []);
    };

    const submit = (e) => {
        e.preventDefault();
        if (ata) {
            put(route('atas-reuniao.update', ata.id));
        } else {
            post(route('atas-reuniao.store'));
        }
    };

    // Temas escuros para react-select
    const customStyles = {
        control: (provided) => ({
            ...provided,
            backgroundColor: 'transparent',
            borderColor: '#374151',
        }),
        menu: (provided) => ({
            ...provided,
            backgroundColor: '#1f2937',
        }),
        option: (provided, state) => ({
            ...provided,
            backgroundColor: state.isFocused ? '#374151' : 'transparent',
            color: '#d1d5db',
        }),
        multiValue: (provided) => ({
            ...provided,
            backgroundColor: '#374151',
        }),
        multiValueLabel: (provided) => ({
            ...provided,
            color: '#d1d5db',
        }),
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {ata ? 'Editar Ata de Reunião' : 'Nova Ata de Reunião'}
            </h2>}
        >
            <Head title={ata ? 'Editar Ata' : 'Nova Ata'} />

            <div className="py-12">
                <div className="w-full sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            
                            <form onSubmit={submit} className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Data *</label>
                                        <input
                                            type="date"
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={data.data}
                                            onChange={e => setData('data', e.target.value)}
                                            required
                                        />
                                        {errors.data && <p className="mt-1 text-sm text-red-600">{errors.data}</p>}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Início *</label>
                                        <input
                                            type="time"
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={data.hora_inicio}
                                            onChange={e => setData('hora_inicio', e.target.value)}
                                            required
                                        />
                                        {errors.hora_inicio && <p className="mt-1 text-sm text-red-600">{errors.hora_inicio}</p>}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Término *</label>
                                        <input
                                            type="time"
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={data.hora_termino}
                                            onChange={e => setData('hora_termino', e.target.value)}
                                            required
                                        />
                                        {errors.hora_termino && <p className="mt-1 text-sm text-red-600">{errors.hora_termino}</p>}
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Local *</label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        value={data.local}
                                        onChange={e => setData('local', e.target.value)}
                                        placeholder="Ex: Sala de Reuniões 01, Google Meet, etc."
                                        required
                                    />
                                    {errors.local && <p className="mt-1 text-sm text-red-600">{errors.local}</p>}
                                </div>

                                <div className="dark:text-slate-300">
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Participantes (Assinaturas exigidas)</label>
                                    <Select
                                        isMulti
                                        options={userOptions}
                                        defaultValue={defaultSelected}
                                        onChange={handleSelectChange}
                                        placeholder="Selecione os usuários..."
                                        styles={document.documentElement.classList.contains('dark') ? customStyles : undefined}
                                        className="basic-multi-select"
                                        classNamePrefix="select"
                                    />
                                    {errors.participantes && <p className="mt-1 text-sm text-red-600">{errors.participantes}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Assunto *</label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        value={data.assunto}
                                        onChange={e => setData('assunto', e.target.value)}
                                        required
                                    />
                                    {errors.assunto && <p className="mt-1 text-sm text-red-600">{errors.assunto}</p>}
                                </div>

                                <div className="quill-dark">
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pautas *</label>
                                    <ReactQuill 
                                        theme="snow" 
                                        value={data.pautas} 
                                        onChange={(val) => setData('pautas', val)}
                                        style={{ minHeight: '150px' }}
                                    />
                                    {errors.pautas && <p className="mt-1 text-sm text-red-600">{errors.pautas}</p>}
                                </div>

                                <div className="quill-dark mt-8 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Registro (Anotações da Reunião)</label>
                                    <ReactQuill 
                                        theme="snow" 
                                        value={data.registro} 
                                        onChange={(val) => setData('registro', val)}
                                        style={{ minHeight: '300px' }}
                                    />
                                    {errors.registro && <p className="mt-1 text-sm text-red-600">{errors.registro}</p>}
                                </div>

                                <div className="flex items-center justify-end gap-4 mt-8">
                                    <Link
                                        href={route('atas-reuniao.index')}
                                        className="flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-slate-600 transition"
                                    >
                                        <ArrowLeft className="w-4 h-4 mr-2" />
                                        Voltar
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-25 transition"
                                    >
                                        <Save className="w-4 h-4 mr-2" />
                                        Salvar Ata
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
