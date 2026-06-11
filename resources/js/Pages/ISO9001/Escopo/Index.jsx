import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css';
import { 
    Save, 
    Send, 
    CheckCircle, 
    XCircle, 
    Download, 
    Clock, 
    User, 
    CheckSquare, 
    FileSignature 
} from 'lucide-react';
import Select from 'react-select';
import dayjs from 'dayjs';

export default function Index({ auth, escopo, companies, users, currentCompanyId }) {
    const userPermissions = auth.user?.permissions || [];
    const canManage = userPermissions.includes('manage-escopo');

    const [isEditing, setIsEditing] = useState(
        (escopo.status === 'rascunho' || escopo.status === 'devolvida') && canManage
    );

    const { data, setData, post, processing, errors } = useForm({
        conteudo: escopo.conteudo || '',
        revisor_id: escopo.revisor_id || '',
        aprovador_id: escopo.aprovador_id || '',
        company_id: currentCompanyId || '',
    });

    const userOptions = users ? users.map(user => ({
        value: user.id,
        label: user.name
    })) : [];

    const isRascunho = escopo.status === 'rascunho' || escopo.status === 'devolvida';
    const isAguardandoRevisao = escopo.status === 'aguardando_revisao';
    const isAguardandoAprovacao = escopo.status === 'aguardando_aprovacao';
    const isAprovada = escopo.status === 'aprovada';

    // Formatar Status
    const getStatusBadge = (status) => {
        const badges = {
            'rascunho': <span className="px-2 py-1 rounded-full bg-slate-100 text-slate-800 text-xs font-semibold">Rascunho</span>,
            'aguardando_revisao': <span className="px-2 py-1 rounded-full bg-blue-100 text-blue-800 text-xs font-semibold">Aguardando Revisão</span>,
            'aguardando_aprovacao': <span className="px-2 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-semibold">Aguardando Aprovação</span>,
            'aprovada': <span className="px-2 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-semibold">Aprovada</span>,
            'devolvida': <span className="px-2 py-1 rounded-full bg-red-100 text-red-800 text-xs font-semibold">Devolvida</span>,
        };
        return badges[status] || badges['rascunho'];
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

    const handleAction = (route) => {
        post(route, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Escopo do SGI</h2>
                    {companies && companies.length > 0 && (
                        <div className="flex items-center gap-2">
                            <span className="text-sm text-slate-500">Empresa:</span>
                            <select
                                className="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm"
                                value={currentCompanyId}
                                onChange={(e) => {
                                    window.location.href = route('escopo.index', { company_id: e.target.value });
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
            <Head title="Escopo do SGI" />

            <div className="py-12">
                <div className="max-w-[95%] mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Header de Status */}
                    <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6 flex justify-between items-center">
                        <div>
                            <h3 className="text-lg font-medium text-slate-900 dark:text-white flex items-center gap-2">
                                <FileSignature className="h-5 w-5 text-indigo-500" />
                                Status do Documento
                            </h3>
                            <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">Status atual do documento.</p>
                        </div>
                        <div className="flex items-center gap-4">
                            {getStatusBadge(escopo.status)}
                            {isAprovada && (
                                <a 
                                    href={route('escopo.pdf', { company_id: currentCompanyId })} 
                                    className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    <Download className="h-4 w-4 mr-2" />
                                    Baixar PDF
                                </a>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        {/* Editor Principal */}
                        <div className="lg:col-span-2 bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <style>{`
                                .ql-editor {
                                    min-height: 500px;
                                }
                            `}</style>
                            {isEditing ? (
                                <div className="space-y-4">
                                    <ReactQuill 
                                        theme="snow" 
                                        value={data.conteudo} 
                                        onChange={(val) => setData('conteudo', val)}
                                        modules={modules}
                                        className="bg-white dark:text-slate-900"
                                    />
                                    {errors.conteudo && <p className="text-sm text-red-600 mt-1">{errors.conteudo}</p>}
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Selecionar Revisor
                                            </label>
                                            <Select
                                                options={userOptions}
                                                value={userOptions.find(o => o.value === data.revisor_id)}
                                                onChange={(val) => setData('revisor_id', val ? val.value : '')}
                                                placeholder="Selecione um usuário..."
                                                className="react-select-container"
                                                classNamePrefix="react-select"
                                                isClearable
                                            />
                                            {errors.revisor_id && <p className="text-sm text-red-600 mt-1">{errors.revisor_id}</p>}
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Selecionar Aprovador
                                            </label>
                                            <Select
                                                options={userOptions}
                                                value={userOptions.find(o => o.value === data.aprovador_id)}
                                                onChange={(val) => setData('aprovador_id', val ? val.value : '')}
                                                placeholder="Selecione um usuário..."
                                                className="react-select-container"
                                                classNamePrefix="react-select"
                                                isClearable
                                            />
                                            {errors.aprovador_id && <p className="text-sm text-red-600 mt-1">{errors.aprovador_id}</p>}
                                        </div>
                                    </div>

                                    <div className="flex justify-end pt-8">
                                        <button
                                            onClick={() => handleAction(route('escopo.salvar-rascunho'))}
                                            disabled={processing}
                                            className="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-500 rounded-md font-semibold text-xs text-slate-700 dark:text-slate-300 uppercase tracking-widest shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                                        >
                                            <Save className="h-4 w-4 mr-2" />
                                            Salvar Rascunho
                                        </button>
                                    </div>
                                </div>
                            ) : (
                                <div className="prose max-w-none dark:prose-invert min-h-[500px]" dangerouslySetInnerHTML={{ __html: escopo.conteudo || '<p class="text-slate-500">Nenhum conteúdo definido ainda.</p>' }} />
                            )}
                        </div>

                        {/* Coluna Lateral: Timeline e Ações */}
                        <div className="space-y-6">
                            
                            {/* Bloco de Ações */}
                            {canManage && !isAprovada && (
                                <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                    <h4 className="text-md font-medium text-slate-900 dark:text-white mb-4">Ações Disponíveis</h4>
                                    
                                    <div className="space-y-3">
                                        {isRascunho && (
                                            <button
                                                onClick={() => handleAction(route('escopo.enviar-revisao'))}
                                                disabled={processing}
                                                className="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-25 transition"
                                            >
                                                <Send className="h-4 w-4 mr-2" />
                                                Enviar para Revisão
                                            </button>
                                        )}

                                        {isAguardandoRevisao && (
                                            <>
                                                <button
                                                    onClick={() => handleAction(route('escopo.aprovar-revisao'))}
                                                    disabled={processing || escopo.elaborador_id === auth.user.id}
                                                    title={escopo.elaborador_id === auth.user.id ? "Você não pode revisar um documento que elaborou." : ""}
                                                    className="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 disabled:opacity-50 transition"
                                                >
                                                    <CheckSquare className="h-4 w-4 mr-2" />
                                                    Aprovar Revisão
                                                </button>
                                                <button
                                                    onClick={() => handleAction(route('escopo.devolver'))}
                                                    disabled={processing}
                                                    className="w-full inline-flex justify-center items-center px-4 py-2 bg-white border border-red-300 text-red-700 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-red-50 disabled:opacity-25 transition"
                                                >
                                                    <XCircle className="h-4 w-4 mr-2" />
                                                    Devolver
                                                </button>
                                            </>
                                        )}

                                        {isAguardandoAprovacao && (
                                            <>
                                                <button
                                                    onClick={() => handleAction(route('escopo.aprovar-final'))}
                                                    disabled={processing}
                                                    className="w-full inline-flex justify-center items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 disabled:opacity-25 transition"
                                                >
                                                    <CheckCircle className="h-4 w-4 mr-2" />
                                                    Aprovação Final
                                                </button>
                                                <button
                                                    onClick={() => handleAction(route('escopo.devolver'))}
                                                    disabled={processing}
                                                    className="w-full inline-flex justify-center items-center px-4 py-2 bg-white border border-red-300 text-red-700 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-red-50 disabled:opacity-25 transition"
                                                >
                                                    <XCircle className="h-4 w-4 mr-2" />
                                                    Devolver
                                                </button>
                                            </>
                                        )}
                                    </div>
                                    
                                    {isAguardandoRevisao && escopo.elaborador_id === auth.user.id && (
                                        <p className="mt-3 text-xs text-red-500 font-medium">Atenção: O elaborador não pode revisar o próprio escopo.</p>
                                    )}
                                </div>
                            )}

                            {/* Bloco Timeline */}
                            <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <h4 className="text-md font-medium text-slate-900 dark:text-white mb-4">Histórico de Aprovações</h4>
                                
                                <div className="space-y-6">
                                    {/* Elaborador */}
                                    <div className="flex gap-4">
                                        <div className="mt-1 bg-slate-100 dark:bg-slate-700 p-2 rounded-full h-min">
                                            <User className="h-4 w-4 text-slate-500 dark:text-slate-300" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-slate-900 dark:text-white">Elaboração</p>
                                            <p className="text-sm text-slate-600 dark:text-slate-400">{escopo.elaborador ? escopo.elaborador.name : 'Pendente'}</p>
                                            {escopo.data_elaboracao && (
                                                <p className="text-xs text-slate-400 mt-1 flex items-center">
                                                    <Clock className="h-3 w-3 mr-1" /> {dayjs(escopo.data_elaboracao).format('DD/MM/YYYY HH:mm')}
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    {/* Revisor */}
                                    <div className="flex gap-4">
                                        <div className={`mt-1 p-2 rounded-full h-min ${escopo.data_revisao ? 'bg-blue-50 dark:bg-blue-900/30' : 'bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700'}`}>
                                            <CheckSquare className={`h-4 w-4 ${escopo.data_revisao ? 'text-blue-500' : 'text-slate-400'}`} />
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-slate-900 dark:text-white">Revisão {escopo.revisor && !escopo.data_revisao ? '(Designado)' : ''}</p>
                                            <p className="text-sm text-slate-600 dark:text-slate-400">{escopo.revisor ? escopo.revisor.name : 'Pendente'}</p>
                                            {escopo.data_revisao && (
                                                <p className="text-xs text-slate-400 mt-1 flex items-center">
                                                    <Clock className="h-3 w-3 mr-1" /> {dayjs(escopo.data_revisao).format('DD/MM/YYYY HH:mm')}
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    {/* Aprovador */}
                                    <div className="flex gap-4">
                                        <div className={`mt-1 p-2 rounded-full h-min ${escopo.data_aprovacao ? 'bg-emerald-50 dark:bg-emerald-900/30' : 'bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700'}`}>
                                            <CheckCircle className={`h-4 w-4 ${escopo.data_aprovacao ? 'text-emerald-500' : 'text-slate-400'}`} />
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-slate-900 dark:text-white">Aprovação {escopo.aprovador && !escopo.data_aprovacao ? '(Designado)' : ''}</p>
                                            <p className="text-sm text-slate-600 dark:text-slate-400">{escopo.aprovador ? escopo.aprovador.name : 'Pendente'}</p>
                                            {escopo.data_aprovacao && (
                                                <p className="text-xs text-slate-400 mt-1 flex items-center">
                                                    <Clock className="h-3 w-3 mr-1" /> {dayjs(escopo.data_aprovacao).format('DD/MM/YYYY HH:mm')}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                    
                                    {escopo.hash_assinatura && (
                                        <div className="pt-4 border-t border-slate-100 dark:border-slate-700">
                                            <p className="text-xs font-semibold text-slate-900 dark:text-white mb-1">Hash de Autenticidade:</p>
                                            <p className="text-xs text-slate-500 dark:text-slate-400 break-all font-mono bg-slate-50 dark:bg-slate-900 p-2 rounded">{escopo.hash_assinatura}</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
