import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Printer, ArrowLeft } from 'lucide-react';
import { useEffect } from 'react';

export default function Show({ auth, auditoria }) {
    // Helper para converter data para pt-BR
    const formatDate = (dateString) => {
        if (!dateString) return '';
        const d = new Date(dateString);
        return new Intl.DateTimeFormat('pt-BR').format(d);
    };

    const handlePrint = () => {
        window.print();
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight print:hidden">Visualizar Auditoria</h2>}
        >
            <Head title={`Auditoria - ${auditoria.id}`} />

            <style>
                {`
                    @media print {
                        @page {
                            margin: 15mm;
                        }
                        body {
                            background-color: white !important;
                            -webkit-print-color-adjust: exact;
                        }
                        /* Oculta as quebras forçadas dentro do tbody para que a tabela flua naturalmente */
                        .print-break-inside-auto {
                            break-inside: auto;
                        }
                    }
                `}
            </style>

            {/* Container Principal */}
            <div className="py-12 print:py-0 print:bg-white bg-slate-100 dark:bg-slate-900 min-h-screen">
                
                {/* Actions Toolbar (Hidden on print) */}
                <div className="max-w-[210mm] mx-auto mb-6 flex justify-between items-center px-4 sm:px-0 print:hidden">
                    <Link href={route('auditorias.index')} className="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 flex items-center gap-2">
                        <ArrowLeft className="w-4 h-4" /> Voltar para Lista
                    </Link>
                    <button 
                        onClick={handlePrint}
                        className="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl transition-colors font-medium text-sm shadow-sm"
                    >
                        <Printer className="w-4 h-4" /> Imprimir Relatório (A4)
                    </button>
                </div>

                {/* Folha A4 - Tabela Estrutural para repetição de Cabeçalho e Rodapé */}
                <div className="w-full max-w-[210mm] min-h-[297mm] mx-auto bg-white shadow-xl print:shadow-none p-10 sm:p-4 text-slate-900">
                    
                    <table className="w-full">
                        {/* CABEÇALHO (Repete em todas as páginas na impressão) */}
                        <thead className="table-header-group">
                            <tr>
                                <td>
                                    {/* Header Content com padding-bottom para desgrudar do texto */}
                                    <div className="border-b-2 border-slate-900 pb-4 mb-6 flex justify-between items-end">
                                        <div>
                                            <h1 className="text-2xl font-bold uppercase tracking-wide">Relatório de Auditoria</h1>
                                            <p className="text-slate-600 mt-1 font-medium text-sm">Documento Oficial - Norma: {auditoria.norma}</p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-lg font-bold">Nº {auditoria.id.toString().padStart(4, '0')}</p>
                                            <p className="text-sm text-slate-600">Emissão: {formatDate(auditoria.created)}</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </thead>

                        {/* CORPO DO DOCUMENTO */}
                        <tbody className="table-row-group">
                            <tr>
                                <td className="pt-2 pb-6">
                                    
                                    {/* Informações Básicas */}
                                    <div className="grid grid-cols-2 gap-x-8 gap-y-4 mb-8 text-sm">
                                        <div>
                                            <p className="text-slate-500 font-semibold text-xs uppercase tracking-wider mb-1">Localidade / Unidade</p>
                                            <p className="font-medium text-base">{auditoria.localidade}</p>
                                        </div>
                                        <div>
                                            <p className="text-slate-500 font-semibold text-xs uppercase tracking-wider mb-1">Setor</p>
                                            <p className="font-medium text-base">{auditoria.setor}</p>
                                        </div>
                                        <div>
                                            <p className="text-slate-500 font-semibold text-xs uppercase tracking-wider mb-1">Data de Realização</p>
                                            <p className="font-medium text-base">{formatDate(auditoria.dataRealizacao)}</p>
                                        </div>
                                        <div>
                                            <p className="text-slate-500 font-semibold text-xs uppercase tracking-wider mb-1">Período (Início - Término)</p>
                                            <p className="font-medium text-base">{auditoria.horario_inicio} às {auditoria.horario_termino}</p>
                                        </div>
                                        <div>
                                            <p className="text-slate-500 font-semibold text-xs uppercase tracking-wider mb-1">Auditor Líder</p>
                                            <p className="font-medium text-base">{auditoria.auditorlider}</p>
                                        </div>
                                        <div>
                                            <p className="text-slate-500 font-semibold text-xs uppercase tracking-wider mb-1">Auditado(s)</p>
                                            <p className="font-medium text-base">{auditoria.auditado || '-'}</p>
                                        </div>
                                    </div>

                                    <hr className="border-slate-200 mb-8" />

                                    {/* Blocos de Texto (Escopo, Equipe) */}
                                    <div className="space-y-6 mb-8 text-sm">
                                        {auditoria.escopo && (
                                            <div>
                                                <h3 className="font-bold text-slate-900 mb-2 uppercase tracking-wide">Escopo da Auditoria</h3>
                                                <p className="whitespace-pre-line text-slate-700 leading-relaxed">{auditoria.escopo}</p>
                                            </div>
                                        )}
                                        {auditoria.requisitos && (
                                            <div>
                                                <h3 className="font-bold text-slate-900 mb-2 uppercase tracking-wide">Requisitos Avaliados</h3>
                                                <p className="whitespace-pre-line text-slate-700 leading-relaxed">{auditoria.requisitos}</p>
                                            </div>
                                        )}
                                    </div>

                                    {/* RELATÓRIO PRINCIPAL (CKEditor HTML) */}
                                    <div className="mb-8">
                                        <h3 className="font-bold text-slate-900 mb-4 uppercase tracking-wide text-lg border-b border-slate-200 pb-2">Relatório Detalhado</h3>
                                        <div 
                                            className="ck-content prose prose-slate max-w-none text-slate-800 leading-relaxed p-0"
                                            dangerouslySetInnerHTML={{ __html: auditoria.relatorio }}
                                        />
                                    </div>

                                    {/* Conclusões e Evidências */}
                                    <div className="space-y-8 mt-12 pt-8 border-t border-slate-300 print:break-inside-avoid">
                                        {auditoria.evidenciaobjetiva && (
                                            <div>
                                                <h3 className="font-bold text-slate-900 mb-2 uppercase tracking-wide">Evidências Objetivas</h3>
                                                <p className="whitespace-pre-line text-slate-700 bg-slate-50 p-4 rounded-lg border border-slate-100">{auditoria.evidenciaobjetiva}</p>
                                            </div>
                                        )}
                                        {auditoria.conclusoes && (
                                            <div>
                                                <h3 className="font-bold text-slate-900 mb-2 uppercase tracking-wide">Conclusões</h3>
                                                <p className="whitespace-pre-line text-slate-700 bg-slate-50 p-4 rounded-lg border border-slate-100">{auditoria.conclusoes}</p>
                                            </div>
                                        )}
                                        
                                        {auditoria.qtde_NC_encontradas !== null && (
                                            <div className="flex items-center gap-3 bg-red-50 text-red-900 p-4 rounded-lg border border-red-100 font-medium">
                                                <span className="text-xl font-bold">{auditoria.qtde_NC_encontradas}</span> Não Conformidades foram encontradas.
                                            </div>
                                        )}
                                    </div>

                                    {/* Assinaturas */}
                                    <div className="grid grid-cols-2 gap-16 mt-24 pt-8 print:break-inside-avoid text-center">
                                        <div>
                                            <div className="border-t border-slate-400 pt-2 font-bold text-sm">
                                                {auditoria.auditorlider}
                                            </div>
                                            <div className="text-xs text-slate-500 uppercase tracking-wide mt-1">Auditor Líder</div>
                                        </div>
                                        <div>
                                            <div className="border-t border-slate-400 pt-2 font-bold text-sm">
                                                {auditoria.auditado || 'Responsável Auditado'}
                                            </div>
                                            <div className="text-xs text-slate-500 uppercase tracking-wide mt-1">Representante Auditado</div>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                        </tbody>
                        
                        {/* RODAPÉ (Repete em todas as páginas na impressão) */}
                        <tfoot className="table-footer-group">
                            <tr>
                                <td>
                                    {/* Footer Content com padding-top para criar espaço do texto */}
                                    <div className="pt-8 mt-4 border-t border-slate-200 text-center text-xs text-slate-500">
                                        <p>Documento gerado pelo SGI Corporativo</p>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
