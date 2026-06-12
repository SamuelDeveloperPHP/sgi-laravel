import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css';
import { Save, ArrowLeft } from 'lucide-react';
import Select from 'react-select';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
export default function Form({ auth, objetivo, users, currentCompanyId }) {
    const isEdit = !!objetivo.id;

    const { data, setData, post, put, processing, errors } = useForm({
        titulo: objetivo.titulo || '',
        descricao: objetivo.descricao || '',
        prazo: objetivo.prazo ? objetivo.prazo.substring(0, 10) : '',
        responsaveis: (objetivo && objetivo.responsaveis_ids) ? objetivo.responsaveis_ids : [],
        revisor_id: (objetivo && objetivo.revisor_id) ? objetivo.revisor_id : '',
        aprovador_id: (objetivo && objetivo.aprovador_id) ? objetivo.aprovador_id : '',
        company_id: currentCompanyId || '',
    });

    const userOptions = users.map(user => ({
        value: user.id,
        label: user.name
    }));

    const selectedUsers = userOptions.filter(opt => data.responsaveis.includes(opt.value));

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route('objetivos-qualidade.update', objetivo.id));
        } else {
            post(route('objetivos-qualidade.store'));
        }
    };

    const modules = {
        toolbar: [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike', 'blockquote'],
            [{'list': 'ordered'}, {'list': 'bullet'}, {'indent': '-1'}, {'indent': '+1'}],
            ['link', 'image'],
            ['clean']
        ],
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">{isEdit ? 'Editar Objetivo da Qualidade' : 'Novo Objetivo da Qualidade'}</h2>}
        >
            <Head title={isEdit ? 'Editar Objetivo' : 'Novo Objetivo'} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    <div className="flex mb-4">
                        <Link href={route('objetivos-qualidade.index')} className="inline-flex items-center text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
                            <ArrowLeft className="w-4 h-4 mr-1" />
                            Voltar para lista
                        </Link>
                    </div>

                    <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Título do Objetivo *</label>
                                <input 
                                    type="text"
                                    value={data.titulo}
                                    onChange={e => setData('titulo', e.target.value)}
                                    className="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    required
                                />
                                {errors.titulo && <p className="text-sm text-red-600 mt-1">{errors.titulo}</p>}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Prazo de Conclusão *</label>
                                    <input 
                                        type="date"
                                        value={data.prazo}
                                        onChange={e => setData('prazo', e.target.value)}
                                        className="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        required
                                    />
                                    {errors.prazo && <p className="text-sm text-red-600 mt-1">{errors.prazo}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Responsáveis *</label>
                                    <Select
                                        isMulti
                                        options={userOptions}
                                        value={selectedUsers}
                                        onChange={(selected) => setData('responsaveis', selected ? selected.map(s => s.value) : [])}
                                        placeholder="Selecione os usuários..."
                                        className="react-select-container"
                                        classNamePrefix="react-select"
                                    />
                                    {errors.responsaveis && <p className="text-sm text-red-600 mt-1">{errors.responsaveis}</p>}
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Descrição / Detalhes</label>
                                <style>{`
                                    .ql-editor {
                                        min-height: 250px;
                                    }
                                `}</style>
                                <ReactQuill 
                                    theme="snow" 
                                    value={data.descricao} 
                                    onChange={(val) => setData('descricao', val)}
                                    modules={modules}
                                    className="bg-white dark:text-slate-900"
                                />
                                {errors.descricao && <p className="text-sm text-red-600 mt-1">{errors.descricao}</p>}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 pt-6 border-t border-slate-100 dark:border-slate-700">
                                {/* Revisor */}
                                <div>
                                    <InputLabel value="Revisor (Aprovará a Etapa de Revisão)" />
                                    <Select
                                        options={userOptions}
                                        value={userOptions.find(o => o.value === data.revisor_id)}
                                        onChange={(val) => setData('revisor_id', val ? val.value : '')}
                                        placeholder="Selecione um usuário..."
                                        className="react-select-container mt-1"
                                        classNamePrefix="react-select"
                                        isClearable
                                    />
                                    <InputError message={errors.revisor_id} className="mt-2" />
                                </div>

                                {/* Aprovador */}
                                <div>
                                    <InputLabel value="Aprovador (Aprovará a Etapa Final)" />
                                    <Select
                                        options={userOptions}
                                        value={userOptions.find(o => o.value === data.aprovador_id)}
                                        onChange={(val) => setData('aprovador_id', val ? val.value : '')}
                                        placeholder="Selecione um usuário..."
                                        className="react-select-container mt-1"
                                        classNamePrefix="react-select"
                                        isClearable
                                    />
                                    <InputError message={errors.aprovador_id} className="mt-2" />
                                </div>
                            </div>

                            <div className="flex justify-end border-t border-slate-200 dark:border-slate-700 pt-6">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-25"
                                >
                                    <Save className="w-4 h-4 mr-2" />
                                    {isEdit ? 'Salvar Alterações' : 'Salvar Objetivo'}
                                </button>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
