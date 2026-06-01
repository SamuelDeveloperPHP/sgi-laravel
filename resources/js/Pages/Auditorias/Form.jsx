import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Save, ArrowLeft } from 'lucide-react';
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css';

export default function Form({ auth, auditoria, isEdit }) {
    const { data, setData, post, put, processing, errors } = useForm({
        localidade: auditoria.localidade || '',
        setor: auditoria.setor || '',
        dataRealizacao: auditoria.dataRealizacao ? auditoria.dataRealizacao.split('T')[0] : '',
        horario_inicio: auditoria.horario_inicio || '',
        horario_termino: auditoria.horario_termino || '',
        norma: auditoria.norma || 'NBR ISO 9001:2015',
        requisitos: auditoria.requisitos || '',
        auditorlider: auditoria.auditorlider || '',
        escopo: auditoria.escopo || '',
        equipeAuditora: auditoria.equipeAuditora || '',
        areas_processo: auditoria.areas_processo || '',
        auditado: auditoria.auditado || '',
        doc_avaliados: auditoria.doc_avaliados || '',
        adms_sit_id: auditoria.adms_sit_id || 1, // Defaulting to 1 (Ativo)
        relatorio: auditoria.relatorio || '',
        qtde_NC_encontradas: auditoria.qtde_NC_encontradas || '',
        evidenciaobjetiva: auditoria.evidenciaobjetiva || '',
        conclusoes: auditoria.conclusoes || '',
    });

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route('auditorias.update', auditoria.id));
        } else {
            post(route('auditorias.store'));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                {isEdit ? 'Editar Auditoria' : 'Nova Auditoria'}
            </h2>}
        >
            <Head title={isEdit ? 'Editar Auditoria' : 'Nova Auditoria'} />

            <div className="py-12">
                <div className="max-w-5xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden">
                        
                        <div className="p-6 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/50">
                            <h3 className="text-lg font-bold text-slate-900 dark:text-white">
                                Detalhes da Auditoria (Layout Legado Mapeado)
                            </h3>
                            <Link href={route('auditorias.index')} className="text-sm font-medium text-slate-500 hover:text-slate-700 flex items-center gap-1">
                                <ArrowLeft className="w-4 h-4" /> Voltar
                            </Link>
                        </div>

                        <form onSubmit={submit} className="p-6 space-y-8">
                            
                            {/* Bloco 1: Informações Básicas */}
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                                <div className="md:col-span-1">
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"><span className="text-red-500">*</span> Localidade</label>
                                    <input type="text" value={data.localidade} onChange={e => setData('localidade', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                    {errors.localidade && <p className="text-rose-500 text-sm mt-1">{errors.localidade}</p>}
                                </div>
                                <div className="md:col-span-1">
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"><span className="text-red-500">*</span> Setor</label>
                                    <input type="text" placeholder="Insira o setor" value={data.setor} onChange={e => setData('setor', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                </div>
                                <div className="md:col-span-1">
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"><span className="text-red-500">*</span> Dt. Realização</label>
                                    <input type="date" value={data.dataRealizacao} onChange={e => setData('dataRealizacao', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                </div>
                                <div className="md:col-span-1 flex gap-2">
                                    <div className="w-1/2">
                                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"><span className="text-red-500">*</span> Início</label>
                                        <input type="time" value={data.horario_inicio} onChange={e => setData('horario_inicio', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                    </div>
                                    <div className="w-1/2">
                                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"><span className="text-red-500">*</span> Término</label>
                                        <input type="time" value={data.horario_termino} onChange={e => setData('horario_termino', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                    </div>
                                </div>
                            </div>

                            {/* Bloco 2: Norma e Escopo */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"><span className="text-red-500">*</span> Norma</label>
                                    <input type="text" value={data.norma} onChange={e => setData('norma', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"><span className="text-red-500">*</span> Requisitos da norma</label>
                                    <input type="text" value={data.requisitos} onChange={e => setData('requisitos', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Escopo da auditoria</label>
                                    <textarea rows="2" value={data.escopo} onChange={e => setData('escopo', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white"></textarea>
                                </div>
                            </div>

                            {/* Bloco 3: Equipe */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"><span className="text-red-500">*</span> Auditor Líder</label>
                                    <input type="text" value={data.auditorlider} onChange={e => setData('auditorlider', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Equipe de Auditores</label>
                                    <textarea rows="2" value={data.equipeAuditora} onChange={e => setData('equipeAuditora', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white"></textarea>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Áreas/ processos</label>
                                    <textarea rows="2" value={data.areas_processo} onChange={e => setData('areas_processo', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white"></textarea>
                                </div>
                            </div>

                            {/* Bloco 4: Avaliados e Situacao */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Auditado(s)</label>
                                    <textarea rows="2" value={data.auditado} onChange={e => setData('auditado', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white"></textarea>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Documentos avaliados</label>
                                    <textarea rows="2" value={data.doc_avaliados} onChange={e => setData('doc_avaliados', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white"></textarea>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"><span className="text-red-500">*</span> Situação</label>
                                    <select value={data.adms_sit_id} onChange={e => setData('adms_sit_id', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white">
                                        <option value="1">Ativo / Em Andamento</option>
                                        <option value="2">Inativo</option>
                                        <option value="3">Concluído</option>
                                    </select>
                                </div>
                            </div>

                            {/* Bloco 5: Relatorio */}
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"><span className="text-red-500">*</span> Relatório</label>
                                <div className="bg-white dark:bg-slate-900 rounded-lg overflow-hidden border border-slate-300 dark:border-slate-700">
                                    <ReactQuill
                                        theme="snow"
                                        value={data.relatorio}
                                        onChange={(content) => setData('relatorio', content)}
                                        className="h-96 mb-12"
                                    />
                                    <style jsx="true">{`
                                        .quill { height: 100%; display: flex; flex-direction: column; }
                                        .ql-container { flex-grow: 1; min-height: 500px; font-family: inherit; font-size: 1rem; }
                                        .dark .ql-toolbar { background-color: #334155; border-color: #475569; }
                                        .dark .ql-container { border-color: #475569; color: #f8fafc; }
                                        .dark .ql-editor.ql-blank::before { color: #94a3b8; }
                                    `}</style>
                                </div>
                            </div>

                            <hr className="border-slate-200 dark:border-slate-700" />

                            {/* Bloco 6: Resultados */}
                            <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Qtde. NC</label>
                                    <input type="number" min="0" max="100" value={data.qtde_NC_encontradas} onChange={e => setData('qtde_NC_encontradas', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                </div>
                                <div className="md:col-span-5">
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Evidências Objetivas</label>
                                    <textarea rows="4" value={data.evidenciaobjetiva} onChange={e => setData('evidenciaobjetiva', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white"></textarea>
                                </div>
                                <div className="md:col-span-5">
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Conclusões</label>
                                    <textarea rows="4" value={data.conclusoes} onChange={e => setData('conclusoes', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white"></textarea>
                                </div>
                            </div>

                            <div className="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-700/50">
                                <button type="submit" disabled={processing} className="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl transition-colors font-medium text-sm shadow-sm disabled:opacity-75">
                                    <Save className="w-4 h-4" /> {isEdit ? 'Atualizar Auditoria' : 'Salvar Auditoria'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
