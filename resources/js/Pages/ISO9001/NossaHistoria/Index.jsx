import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css';
import { Save, History } from 'lucide-react';

export default function Index({ auth, historia, companies, currentCompanyId }) {
    const userPermissions = auth.user?.permissions || [];
    const canManage = userPermissions.includes('manage-nossa-historia');

    const [isEditing, setIsEditing] = useState(!historia?.conteudo);

    const { data, setData, post, processing, errors } = useForm({
        conteudo: historia?.conteudo || '',
        company_id: currentCompanyId || '',
    });

    const modules = {
        toolbar: [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike', 'blockquote'],
            [{'list': 'ordered'}, {'list': 'bullet'}, {'indent': '-1'}, {'indent': '+1'}],
            ['link', 'image'],
            ['clean']
        ],
    };

    const handleSave = (e) => {
        e.preventDefault();
        post(route('nossa-historia.salvar'), { 
            preserveScroll: true,
            onSuccess: () => setIsEditing(false)
        });
    };

    const handleDelete = () => {
        if (confirm('Tem certeza que deseja excluir o conteúdo?')) {
            setData('conteudo', '');
            post(route('nossa-historia.salvar'), { preserveScroll: true });
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Nossa História</h2>
                    {companies && companies.length > 0 && (
                        <div className="flex items-center gap-2">
                            <span className="text-sm text-slate-500">Empresa:</span>
                            <select
                                className="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm"
                                value={currentCompanyId}
                                onChange={(e) => {
                                    window.location.href = route('nossa-historia.index', { company_id: e.target.value });
                                }}
                            >
                                {companies.map(c => (
                                    <option key={c.id} value={c.id}>{c.nome_fantasia}</option>
                                ))}
                            </select>
                        </div>
                    )}
                </div>
            }
        >
            <Head title="Nossa História" />

            <div className="py-12">
                <div className="w-full sm:px-6 lg:px-8 space-y-6">
                    <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex justify-between items-center mb-6">
                            <div className="flex items-center gap-2">
                                <History className="h-6 w-6 text-indigo-500" />
                                <h3 className="text-lg font-medium text-slate-900 dark:text-white">
                                    A História da Empresa
                                </h3>
                            </div>
                        </div>

                        <style>{`
                            .ql-editor {
                                min-height: 500px;
                            }
                        `}</style>

                        {isEditing ? (
                            <form onSubmit={handleSave} className="space-y-4">
                                <ReactQuill 
                                    theme="snow" 
                                    value={data.conteudo} 
                                    onChange={(val) => setData('conteudo', val)}
                                    modules={modules}
                                    className="bg-white dark:text-slate-900"
                                />
                                {errors.conteudo && <p className="text-sm text-red-600 mt-1">{errors.conteudo}</p>}
                                
                                <div className="flex justify-end pt-4 gap-2">
                                    {historia?.conteudo && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setData('conteudo', historia.conteudo);
                                                setIsEditing(false);
                                            }}
                                            className="inline-flex items-center px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-slate-50 transition"
                                        >
                                            Cancelar
                                        </button>
                                    )}
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-25 transition"
                                    >
                                        <Save className="h-4 w-4 mr-2" />
                                        Salvar Nossa História
                                    </button>
                                </div>
                            </form>
                        ) : (
                            <div className="space-y-4">
                                <div className="prose max-w-none dark:prose-invert min-h-[500px]" dangerouslySetInnerHTML={{ __html: historia?.conteudo || '<p class="text-slate-500">Nenhum conteúdo definido ainda.</p>' }} />
                                
                                {canManage && (
                                    <div className="flex justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-700">
                                        <button
                                            onClick={() => setIsEditing(true)}
                                            className="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-500 rounded-md font-semibold text-xs text-slate-700 dark:text-slate-300 uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            onClick={handleDelete}
                                            disabled={processing}
                                            className="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 disabled:opacity-25 transition"
                                        >
                                            Excluir
                                        </button>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
