import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle, XCircle, FileText, Download, Star, MapPin, Phone, Mail, Building, Plus, Minus, Trash2, Eye } from 'lucide-react';
import { format } from 'date-fns';
import Modal from '@/Components/Modal';

export default function Show({ auth, fornecedor, criteriosPadrao }) {
    const [activeTab, setActiveTab] = useState('visao_geral');
    
    // States for Document Rejection Modal
    const [showReprovarModal, setShowReprovarModal] = useState(false);
    const [documentoSelecionado, setDocumentoSelecionado] = useState(null);
    const { data: reprovarData, setData: setReprovarData, post: postReprovar, processing: reprovarProcessing, reset: resetReprovar } = useForm({
        status_aprovacao: 'reprovado',
        motivo_reprovacao: ''
    });

    // States for New Document Modal
    const [showDocModal, setShowDocModal] = useState(false);
    const [previewDoc, setPreviewDoc] = useState(null);
    const { data: docData, setData: setDocData, post: postDoc, processing: docProcessing, errors: docErrors, reset: resetDoc } = useForm({
        nome_documento: '',
        arquivo: null,
        data_validade: ''
    });

    // States for New Evaluation Modal
    const [showAvaliacaoModal, setShowAvaliacaoModal] = useState(false);
    const [isCustomForm, setIsCustomForm] = useState(false);

    const defaultCriterios = (criteriosPadrao && criteriosPadrao.length > 0) ? criteriosPadrao : [
        { nome: 'Qualidade (Produto/Serviço)' },
        { nome: 'Prazo de Entrega' },
        { nome: 'Atendimento e Suporte' }
    ];

    const getInitialCriterios = () => defaultCriterios.map(c => ({ ...c, nota: 5 }));

    const { data: avalData, setData: setAvalData, post: postAval, processing: avalProcessing, reset: resetAval } = useForm({
        criterios: getInitialCriterios(),
        observacoes: '',
        salvar_como_padrao: false
    });

    const openAvaliacaoModal = () => {
        setIsCustomForm(false);
        setAvalData('criterios', getInitialCriterios());
        setShowAvaliacaoModal(true);
    };

    // HANDLERS
    const handleReprovarClick = (doc) => {
        setDocumentoSelecionado(doc);
        setShowReprovarModal(true);
    };

    const submitReprovar = (e) => {
        e.preventDefault();
        postReprovar(route('fornecedor.documentos.avaliar', documentoSelecionado.id), {
            onSuccess: () => {
                setShowReprovarModal(false);
                resetReprovar();
            }
        });
    };

    const aprovarDocumento = (id) => {
        if (confirm('Aprovar este documento?')) {
            router.post(route('fornecedor.documentos.avaliar', id), {
                status_aprovacao: 'aprovado'
            });
        }
    };

    const submitDoc = (e) => {
        e.preventDefault();
        postDoc(route('fornecedor.documentos.store', fornecedor.id), {
            onSuccess: () => {
                setShowDocModal(false);
                resetDoc();
            }
        });
    };

    const submitAvaliacao = (e) => {
        e.preventDefault();
        postAval(route('fornecedor.avaliacoes.store', fornecedor.id), {
            onSuccess: () => {
                setShowAvaliacaoModal(false);
                resetAval();
            }
        });
    };

    const deleteDocumento = (id) => {
        if (confirm('Excluir este documento?')) {
            router.delete(route('fornecedor.documentos.destroy', id));
        }
    };

    // HELPERS
    const renderStars = (nota) => {
        return (
            <div className="flex text-yellow-400">
                {[1, 2, 3, 4, 5].map((star) => (
                    <Star key={star} className={`w-4 h-4 ${star <= nota ? 'fill-current' : 'text-gray-300'}`} />
                ))}
            </div>
        );
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Painel do Fornecedor</h2>
                    <Link
                        href={route('fornecedores.index')}
                        className="flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-slate-600 transition"
                    >
                        <ArrowLeft className="w-4 h-4 mr-2" />
                        Voltar
                    </Link>
                </div>
            }
        >
            <Head title={`Fornecedor: ${fornecedor.razao_social}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">

                    {/* HEADER INFO */}
                    <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6 flex flex-col md:flex-row items-center justify-between">
                        <div>
                            <h3 className="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                                <Building className="w-6 h-6 mr-2 text-indigo-500" />
                                {fornecedor.razao_social}
                            </h3>
                            <div className="mt-2 text-gray-500 dark:text-gray-400 flex space-x-4 text-sm">
                                <span>CNPJ/CPF: {fornecedor.cnpj_cpf || 'Não informado'}</span>
                                <span>|</span>
                                <span className="uppercase">Criticidade: <strong>{fornecedor.criticidade}</strong></span>
                            </div>
                        </div>
                        <div className="mt-4 md:mt-0 flex flex-col items-center">
                            <div className="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Índice de Desempenho (IDF)</div>
                            <div className="flex items-center text-3xl font-bold text-gray-900 dark:text-gray-100">
                                <Star className={`w-8 h-8 mr-2 ${fornecedor.idf_atual >= 3.5 ? 'text-yellow-400 fill-current' : (fornecedor.idf_atual > 0 ? 'text-orange-400 fill-current' : 'text-gray-300')}`} />
                                {fornecedor.idf_atual > 0 ? Number(fornecedor.idf_atual).toFixed(2) : 'N/A'}
                            </div>
                        </div>
                    </div>

                    {/* TABS */}
                    <div className="mb-4 border-b border-gray-200 dark:border-gray-700">
                        <ul className="flex flex-wrap -mb-px text-sm font-medium text-center">
                            <li className="mr-2">
                                <button
                                    onClick={() => setActiveTab('visao_geral')}
                                    className={`inline-block p-4 rounded-t-lg border-b-2 ${activeTab === 'visao_geral' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 dark:text-gray-400'}`}
                                >
                                    Visão Geral
                                </button>
                            </li>
                            <li className="mr-2">
                                <button
                                    onClick={() => setActiveTab('documentos')}
                                    className={`inline-block p-4 rounded-t-lg border-b-2 ${activeTab === 'documentos' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 dark:text-gray-400'}`}
                                >
                                    Documentos / Homologação
                                </button>
                            </li>
                            <li className="mr-2">
                                <button
                                    onClick={() => setActiveTab('avaliacoes')}
                                    className={`inline-block p-4 rounded-t-lg border-b-2 ${activeTab === 'avaliacoes' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 dark:text-gray-400'}`}
                                >
                                    Avaliações de Desempenho
                                </button>
                            </li>
                        </ul>
                    </div>

                    {/* TAB CONTENT */}
                    <div className="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg p-6">
                        
                        {/* TAB 1: VISÃO GERAL */}
                        {activeTab === 'visao_geral' && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div>
                                    <h4 className="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                        <Mail className="w-5 h-5 mr-2" /> Contato
                                    </h4>
                                    <ul className="space-y-3 text-gray-600 dark:text-gray-300">
                                        <li><strong>Nome:</strong> {fornecedor.contato_nome || '-'}</li>
                                        <li><strong>Email:</strong> {fornecedor.email || '-'}</li>
                                        <li><strong>Telefone:</strong> {fornecedor.telefone || '-'}</li>
                                    </ul>

                                    <h4 className="text-lg font-bold text-gray-900 dark:text-gray-100 mt-8 mb-4 flex items-center">
                                        <MapPin className="w-5 h-5 mr-2" /> Endereço
                                    </h4>
                                    <ul className="space-y-3 text-gray-600 dark:text-gray-300">
                                        <li><strong>CEP:</strong> {fornecedor.cep || '-'}</li>
                                        <li><strong>Rua:</strong> {fornecedor.logradouro || '-'} {fornecedor.numero ? `, ${fornecedor.numero}` : ''}</li>
                                        <li><strong>Complemento:</strong> {fornecedor.complemento || '-'}</li>
                                        <li><strong>Bairro:</strong> {fornecedor.bairro || '-'}</li>
                                        <li><strong>Cidade/UF:</strong> {fornecedor.cidade ? `${fornecedor.cidade} / ${fornecedor.estado}` : '-'}</li>
                                    </ul>
                                </div>
                                <div>
                                    <h4 className="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Informações Adicionais</h4>
                                    <div className="bg-gray-50 dark:bg-slate-900 p-4 rounded-lg">
                                        <p className="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">
                                            {fornecedor.observacoes || 'Nenhuma observação registrada.'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* TAB 2: DOCUMENTOS */}
                        {activeTab === 'documentos' && (
                            <div>
                                <div className="flex justify-between items-center mb-6">
                                    <h4 className="text-lg font-bold text-gray-900 dark:text-gray-100">Controle de Documentação</h4>
                                    {auth.user.permissions?.includes('manage-fornecedores') && (
                                        <button
                                            onClick={() => setShowDocModal(true)}
                                            className="flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold hover:bg-indigo-700 transition"
                                        >
                                            <Plus className="w-4 h-4 mr-1" /> Enviar Documento
                                        </button>
                                    )}
                                </div>
                                
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-slate-700">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documento</th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Validade</th>
                                                <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avaliado Por</th>
                                                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                            {fornecedor.documentos.map(doc => (
                                                <tr key={doc.id}>
                                                    <td className="px-4 py-4 text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center">
                                                        <FileText className="w-4 h-4 mr-2 text-indigo-500" />
                                                        {doc.nome_documento}
                                                    </td>
                                                    <td className="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                        {doc.data_validade ? format(new Date(doc.data_validade), 'dd/MM/yyyy') : '-'}
                                                    </td>
                                                    <td className="px-4 py-4 text-sm text-center">
                                                        {doc.status_aprovacao === 'aprovado' && <span className="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-bold">Aprovado</span>}
                                                        {doc.status_aprovacao === 'reprovado' && <span className="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-bold" title={doc.motivo_reprovacao}>Reprovado</span>}
                                                        {doc.status_aprovacao === 'pendente' && <span className="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-bold">Pendente</span>}
                                                    </td>
                                                    <td className="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                        {doc.avaliador ? doc.avaliador.name : '-'}
                                                    </td>
                                                    <td className="px-4 py-4 text-right text-sm">
                                                        <div className="flex items-center justify-end gap-2">
                                                            <button type="button" onClick={() => { console.log('Abrindo preview:', doc); setPreviewDoc(doc); }} className="text-indigo-600 hover:text-indigo-800" title="Visualizar Documento">
                                                                <Eye className="w-5 h-5" />
                                                            </button>
                                                            {auth.user.permissions?.includes('manage-fornecedores') && doc.status_aprovacao === 'pendente' && (
                                                                <>
                                                                    <button onClick={() => aprovarDocumento(doc.id)} className="text-green-600 hover:text-green-800" title="Aprovar">
                                                                        <CheckCircle className="w-5 h-5" />
                                                                    </button>
                                                                    <button onClick={() => handleReprovarClick(doc)} className="text-red-600 hover:text-red-800" title="Reprovar">
                                                                        <XCircle className="w-5 h-5" />
                                                                    </button>
                                                                </>
                                                            )}
                                                            {auth.user.permissions?.includes('manage-fornecedores') && (
                                                                <button onClick={() => deleteDocumento(doc.id)} className="text-gray-400 hover:text-red-600 ml-2" title="Excluir">
                                                                    <Trash2 className="w-4 h-4" />
                                                                </button>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                            {fornecedor.documentos.length === 0 && (
                                                <tr><td colSpan="5" className="px-4 py-4 text-center text-gray-500">Nenhum documento anexado.</td></tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                        {/* TAB 3: AVALIAÇÕES */}
                        {activeTab === 'avaliacoes' && (
                            <div>
                                <div className="flex justify-between items-center mb-6">
                                    <h4 className="text-lg font-bold text-gray-900 dark:text-gray-100">Histórico de Desempenho</h4>
                                    {auth.user.permissions?.includes('manage-fornecedores') && (
                                        <button
                                            onClick={openAvaliacaoModal}
                                            className="flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition"
                                        >
                                            <Star className="w-4 h-4 mr-2" />
                                            Nova Avaliação
                                        </button>
                                    )}
                                </div>

                                <div className="space-y-4">
                                    {fornecedor.avaliacoes.map(aval => (
                                        <div key={aval.id} className="bg-gray-50 dark:bg-slate-900 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                                            <div className="flex justify-between items-start mb-4">
                                                <div>
                                                    <div className="font-bold text-gray-900 dark:text-gray-100 flex items-center">
                                                        Avaliação de {format(new Date(aval.data_avaliacao), 'dd/MM/yyyy')}
                                                        <span className="ml-3 px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-xs">
                                                            Média: {aval.nota_geral}
                                                        </span>
                                                    </div>
                                                    <div className="text-xs text-gray-500 mt-1">Avaliador: {aval.avaliador?.name}</div>
                                                </div>
                                            </div>
                                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                                <div className="px-6 py-4">
                                                    <div className="space-y-1">
                                                        {aval.criterios && Array.isArray(aval.criterios) ? (
                                                            aval.criterios.map((c, i) => (
                                                                <div key={i} className="flex justify-between items-center text-xs">
                                                                    <span className="text-gray-500 uppercase">{c.nome}</span>
                                                                    {renderStars(Number(c.nota))}
                                                                </div>
                                                            ))
                                                        ) : (
                                                            <div className="text-xs text-gray-500 italic">Formato antigo</div>
                                                        )}
                                                    </div>
                                                    {aval.observacoes && (
                                                        <div className="mt-2 text-xs text-gray-600 dark:text-gray-400 italic">
                                                            *{aval.observacoes}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                    {fornecedor.avaliacoes.length === 0 && (
                                        <p className="text-center text-gray-500 py-4">Nenhuma avaliação realizada até o momento.</p>
                                    )}
                                </div>
                            </div>
                        )}

                    </div>
                </div>
            </div>

            {/* MODAL: PREVIEW DE DOCUMENTO */}
            <Modal show={previewDoc !== null} onClose={() => setPreviewDoc(null)} maxWidth="7xl">
                <div className="flex flex-col h-[90vh]">
                    <div className="flex justify-between items-center p-4 border-b dark:border-gray-700 bg-white dark:bg-slate-800">
                        <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center">
                            <FileText className="w-5 h-5 mr-2 text-indigo-500" /> 
                            Visualização: {previewDoc?.nome_documento}
                        </h2>
                        <div className="flex items-center gap-3">
                            <a href={previewDoc ? route('fornecedor.documentos.download', previewDoc.id) : '#'} download className="flex items-center px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm transition">
                                <Download className="w-4 h-4 mr-1" /> Baixar
                            </a>
                            <button onClick={() => setPreviewDoc(null)} className="text-gray-400 hover:text-gray-500">
                                <XCircle className="w-6 h-6" />
                            </button>
                        </div>
                    </div>
                    <div className="flex-grow bg-gray-100 dark:bg-slate-900 p-2 overflow-hidden">
                        {previewDoc && (
                            <iframe 
                                src={route('fornecedor.documentos.download', previewDoc.id)} 
                                className="w-full h-full border-0 rounded"
                                title={previewDoc.nome_documento}
                            ></iframe>
                        )}
                    </div>
                </div>
            </Modal>

            {/* MODAL: REPROVAR DOCUMENTO */}
            <Modal show={showReprovarModal} onClose={() => setShowReprovarModal(false)}>
                <form onSubmit={submitReprovar} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                        <XCircle className="w-5 h-5 mr-2 text-red-500" /> Reprovar Documento
                    </h2>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        O fornecedor será notificado por e-mail informando o motivo abaixo:
                    </p>
                    
                    <div className="mb-4">
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo da Reprovação *</label>
                        <textarea
                            className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-slate-900 rounded-md shadow-sm"
                            rows="4"
                            required
                            value={reprovarData.motivo_reprovacao}
                            onChange={e => setReprovarData('motivo_reprovacao', e.target.value)}
                            placeholder="Ex: Documento ilegível, vencido, não condiz com o solicitado..."
                        ></textarea>
                    </div>

                    <div className="flex justify-end gap-3 mt-6">
                        <button type="button" onClick={() => setShowReprovarModal(false)} className="px-4 py-2 bg-gray-200 text-gray-800 rounded-md">Cancelar</button>
                        <button type="submit" disabled={reprovarProcessing} className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50 flex items-center">
                            Confirmar e Enviar E-mail
                        </button>
                    </div>
                </form>
            </Modal>

            {/* MODAL: NOVO DOCUMENTO */}
            <Modal show={showDocModal} onClose={() => setShowDocModal(false)}>
                <form onSubmit={submitDoc} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Anexar Documento</h2>
                    
                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome do Documento *</label>
                            <input
                                type="text"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900"
                                required
                                value={docData.nome_documento}
                                onChange={e => setDocData('nome_documento', e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Data de Validade (Opcional)</label>
                            <input
                                type="date"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900"
                                value={docData.data_validade}
                                onChange={e => setDocData('data_validade', e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Arquivo (PDF, JPG, PNG) *</label>
                            <input
                                type="file"
                                required
                                accept="application/pdf,image/png,image/jpeg"
                                className="mt-1 block w-full"
                                onChange={e => setDocData('arquivo', e.target.files[0])}
                            />
                            {docErrors.arquivo && <p className="text-red-500 text-xs mt-1">{docErrors.arquivo}</p>}
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 mt-6">
                        <button type="button" onClick={() => setShowDocModal(false)} className="px-4 py-2 bg-gray-200 text-gray-800 rounded-md">Cancelar</button>
                        <button type="submit" disabled={docProcessing} className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50">
                            Salvar Documento
                        </button>
                    </div>
                </form>
            </Modal>

            {/* MODAL: NOVA AVALIAÇÃO */}
            <Modal show={showAvaliacaoModal} onClose={() => setShowAvaliacaoModal(false)} position="top" panelClass="w-[70vw] max-w-[70vw] max-h-[90vh] overflow-y-auto">
                <form onSubmit={submitAvaliacao} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Nova Avaliação de Desempenho</h2>
                    <p className="text-sm text-gray-500 mb-6">Como você avalia este fornecedor recentemente?</p>
                    
                    <div className="mb-6 flex items-center justify-between bg-gray-50 dark:bg-slate-700 p-4 rounded-lg">
                        <div className="text-sm text-gray-700 dark:text-gray-300">
                            <strong>Formulário: </strong> 
                            {isCustomForm ? 'Personalizado' : 'Padrão da Empresa'}
                            {isCustomForm && (
                                <div className="mt-2">
                                    <label className="inline-flex items-center">
                                        <input
                                            type="checkbox"
                                            className="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            checked={avalData.salvar_como_padrao}
                                            onChange={(e) => setAvalData('salvar_como_padrao', e.target.checked)}
                                        />
                                        <span className="ml-2 text-xs text-indigo-600 font-semibold bg-indigo-50 px-2 py-1 rounded">
                                            Salvar estas perguntas como NOVO PADRÃO para a empresa
                                        </span>
                                    </label>
                                </div>
                            )}
                        </div>
                        <button
                            type="button"
                            onClick={() => {
                                setIsCustomForm(!isCustomForm);
                                if (isCustomForm) {
                                    // Voltando para padrão
                                    setAvalData('criterios', getInitialCriterios());
                                    setAvalData('salvar_como_padrao', false);
                                }
                            }}
                            className="text-sm font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            {isCustomForm ? 'Usar Formulário Padrão' : 'Personalizar Formulário'}
                        </button>
                    </div>

                    <div className="space-y-6">
                        {avalData.criterios.map((criterio, index) => (
                            <div key={index} className="flex flex-col sm:flex-row sm:items-center justify-between border-b dark:border-gray-700 pb-4 gap-4">
                                <div className="flex-1 flex items-center gap-2">
                                    {isCustomForm ? (
                                        <>
                                            <input
                                                type="text"
                                                required
                                                className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300"
                                                placeholder="Nome do Critério"
                                                value={criterio.nome}
                                                onChange={(e) => {
                                                    const novos = [...avalData.criterios];
                                                    novos[index].nome = e.target.value;
                                                    setAvalData('criterios', novos);
                                                }}
                                            />
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    const novos = avalData.criterios.filter((_, i) => i !== index);
                                                    setAvalData('criterios', novos);
                                                }}
                                                className="p-2 text-red-500 hover:bg-red-100 rounded-md"
                                                title="Remover"
                                            >
                                                <Trash2 className="w-5 h-5" />
                                            </button>
                                        </>
                                    ) : (
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 sm:mb-0">
                                            {criterio.nome}
                                        </label>
                                    )}
                                </div>
                                
                                <div className="flex items-center space-x-4 shrink-0">
                                    <button type="button" onClick={() => {
                                        const novos = [...avalData.criterios];
                                        novos[index].nota = Math.max(1, Number(novos[index].nota) - 1);
                                        setAvalData('criterios', novos);
                                    }} className="p-2 bg-red-100 text-red-600 rounded-md hover:bg-red-200 transition-colors">
                                        <Minus className="w-6 h-6" />
                                    </button>
                                    <div className="w-8 text-center font-bold text-xl text-gray-800 dark:text-gray-100">{criterio.nota}</div>
                                    <button type="button" onClick={() => {
                                        const novos = [...avalData.criterios];
                                        novos[index].nota = Math.min(5, Number(novos[index].nota) + 1);
                                        setAvalData('criterios', novos);
                                    }} className="p-2 bg-green-100 text-green-600 rounded-md hover:bg-green-200 transition-colors">
                                        <Plus className="w-6 h-6" />
                                    </button>
                                </div>
                            </div>
                        ))}
                        
                        {isCustomForm && (
                            <div>
                                <button
                                    type="button"
                                    onClick={() => setAvalData('criterios', [...avalData.criterios, { nome: '', nota: 5 }])}
                                    className="flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-semibold"
                                >
                                    <Plus className="w-4 h-4 mr-1" /> Adicionar Novo Critério
                                </button>
                            </div>
                        )}

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Observações Gerais</label>
                            <textarea
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900"
                                rows="3"
                                value={avalData.observacoes}
                                onChange={e => setAvalData('observacoes', e.target.value)}
                            ></textarea>
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 mt-8">
                        <button type="button" onClick={() => setShowAvaliacaoModal(false)} className="px-4 py-2 bg-gray-200 text-gray-800 rounded-md">Cancelar</button>
                        <button type="submit" disabled={avalProcessing} className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50">
                            Gravar Avaliação
                        </button>
                    </div>
                </form>
            </Modal>

        </AuthenticatedLayout>
    );
}
