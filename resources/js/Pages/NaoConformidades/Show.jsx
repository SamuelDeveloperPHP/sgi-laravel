import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Printer, ArrowLeft } from 'lucide-react';
import { useEffect } from 'react';

export default function Show({ auth, nc }) {
    // Helper para converter data para pt-BR
    const formatDate = (dateString) => {
        if (!dateString) return '';
        const d = new Date(dateString);
        return d.toLocaleDateString('pt-BR', { timeZone: 'UTC' }); // using UTC to avoid offset issues if just YYYY-MM-DD
    };

    const handlePrint = () => {
        window.print();
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight print:hidden">Visualizar RNC</h2>}
        >
            <Head title={`RNC #${nc.id}`} />

            <div className="py-6 print:py-0">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8 print:max-w-none print:px-0">
                    
                    {/* BOTOES DE AÇÃO (Escondidos na impressão) */}
                    <div className="mb-4 flex justify-between items-center print:hidden">
                        <Link 
                            href={route('nao-conformidades.index')}
                            className="flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 transition-colors"
                        >
                            <ArrowLeft size={20} /> Voltar para Lista
                        </Link>
                        
                        <div className="flex gap-3">
                            <button 
                                onClick={handlePrint}
                                className="flex items-center gap-2 px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md font-medium transition-colors"
                            >
                                <Printer size={20} /> Imprimir / Gerar PDF
                            </button>
                        </div>
                    </div>

                    {/* FOLHA A4 PARA IMPRESSÃO */}
                    <div className="bg-white text-black shadow-lg mx-auto print:shadow-none" style={{ width: '210mm', minHeight: '297mm', padding: '15mm', boxSizing: 'border-box' }}>
                        
                        <style jsx="true">{`
                            @media print {
                                @page { margin: 15mm; size: A4; }
                                body { background-color: #fff; }
                            }
                            table.nc-table {
                                width: 100%;
                                border-collapse: collapse;
                                margin-bottom: 20px;
                                border: 2px solid #000;
                            }
                            table.nc-table th, table.nc-table td {
                                border: 1px solid #000;
                                padding: 4px 8px;
                                font-size: 13px;
                                font-family: Arial, sans-serif;
                            }
                            .nc-header {
                                background-color: #e67e22; /* Laranja da imagem */
                                font-weight: bold;
                                text-align: center;
                                font-size: 14px;
                            }
                            .nc-sub-header {
                                background-color: #e5e7eb; /* Cinza claro */
                                font-weight: bold;
                                font-size: 13px;
                            }
                        `}</style>

                        {/* CABEÇALHO */}
                        <table className="nc-table">
                            <tbody>
                                <tr>
                                    <td colSpan="6" className="nc-header">ABERTURA DA NÃO CONFORMIDADE</td>
                                </tr>
                                <tr>
                                    <td className="nc-sub-header w-32">Responsável:</td>
                                    <td className="w-1/2">{nc.user_create || 'SISTEMA'}</td>
                                    <td className="nc-sub-header w-20 text-center">Data:</td>
                                    <td className="text-center">{formatDate(nc.dataAbertura || nc.created)}</td>
                                    <td className="nc-sub-header w-28 text-center">Nº da RNC:</td>
                                    <td className="text-center font-bold text-red-600">{nc.id}</td>
                                </tr>
                            </tbody>
                        </table>

                        {/* 1. DADOS */}
                        <table className="nc-table">
                            <tbody>
                                <tr>
                                    <td colSpan="6" className="nc-header">1. DADOS DA NÃO CONFORMIDADE</td>
                                </tr>
                                <tr>
                                    <td className="nc-sub-header w-20 text-center">Interna</td>
                                    <td className="text-center w-8">{nc.dados_origem?.origem === 'Interna' ? 'X' : ''}</td>
                                    <td className="nc-sub-header w-24 text-center">Fornecedor</td>
                                    <td className="text-center w-8">{nc.dados_origem?.origem === 'Fornecedor' ? 'X' : ''}</td>
                                    <td className="nc-sub-header text-center">Cliente</td>
                                    <td className="text-center w-8">{nc.dados_origem?.origem === 'Cliente' ? 'X' : ''}</td>
                                </tr>
                                <tr>
                                    <td colSpan="2" className="nc-sub-header">Área / Fornecedor / Cliente:</td>
                                    <td colSpan="2">{nc.dados_origem?.area}</td>
                                    <td className="nc-sub-header">Data da Ocorrência:</td>
                                    <td>{formatDate(nc.dados_origem?.data_ocorrencia)}</td>
                                </tr>
                                <tr>
                                    <td colSpan="2" className="nc-sub-header">Nº Reclamação Cliente:</td>
                                    <td colSpan="2">{nc.dados_origem?.n_reclamacao}</td>
                                    <td className="nc-sub-header">Contato:</td>
                                    <td>{nc.dados_origem?.contato}</td>
                                </tr>
                                <tr>
                                    <td colSpan="2" className="nc-sub-header">Processo / Produto:</td>
                                    <td colSpan="4">{nc.dados_origem?.processo_produto}</td>
                                </tr>
                            </tbody>
                        </table>

                        {/* 2. DESCRIÇÃO */}
                        <table className="nc-table">
                            <tbody>
                                <tr>
                                    <td className="nc-header">2. DESCRIÇÃO DETALHADA DA NÃO CONFORMIDADE ou MELHORIA</td>
                                </tr>
                                <tr>
                                    <td className="align-top" style={{ minHeight: '150px' }}>
                                        <div className="ck-content" dangerouslySetInnerHTML={{ __html: nc.descOcorrencia }} />
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        {/* 3. AÇÃO CONTENÇÃO */}
                        <table className="nc-table">
                            <tbody>
                                <tr>
                                    <td colSpan="3" className="nc-header">3. AÇÃO DE CONTENÇÃO ou MELHORIA</td>
                                </tr>
                                <tr>
                                    <td className="nc-sub-header text-center">AÇÃO</td>
                                    <td className="nc-sub-header text-center w-48">RESPONSÁVEL</td>
                                    <td className="nc-sub-header text-center w-32">PRAZO</td>
                                </tr>
                                {nc.acao_contencao_grid && nc.acao_contencao_grid.map((row, i) => (
                                    <tr key={i}>
                                        <td>{row.acao}</td>
                                        <td className="text-center">{row.responsavel}</td>
                                        <td className="text-center">{formatDate(row.prazo)}</td>
                                    </tr>
                                ))}
                                {(!nc.acao_contencao_grid || nc.acao_contencao_grid.length === 0) && (
                                    <tr><td colSpan="3" className="h-6"></td></tr>
                                )}
                            </tbody>
                        </table>

                        {/* 4. ANÁLISE CAUSAS */}
                        <table className="nc-table">
                            <tbody>
                                <tr>
                                    <td colSpan="2" className="nc-header">4. ANÁLISE DAS POSSÍVEIS CAUSAS OU CAUSA RAIZ</td>
                                </tr>
                                {nc.cinco_porques?.porques && nc.cinco_porques.porques.map((pq, i) => (
                                    <tr key={i}>
                                        <td className="nc-sub-header w-24 text-center">{pq.p}</td>
                                        <td>{pq.r}</td>
                                    </tr>
                                ))}
                                <tr>
                                    <td className="nc-sub-header text-center">Causa Raiz:</td>
                                    <td className="font-bold">{nc.cinco_porques?.causa_raiz}</td>
                                </tr>
                            </tbody>
                        </table>

                        {/* 5. PLANO DE AÇÃO */}
                        <table className="nc-table">
                            <tbody>
                                <tr>
                                    <td colSpan="3" className="nc-header">5. PLANO DE AÇÃO</td>
                                </tr>
                                <tr>
                                    <td className="nc-sub-header text-center">AÇÃO</td>
                                    <td className="nc-sub-header text-center w-48">RESPONSÁVEL</td>
                                    <td className="nc-sub-header text-center w-32">PRAZO</td>
                                </tr>
                                {nc.plano_acao_grid && nc.plano_acao_grid.map((row, i) => (
                                    <tr key={i}>
                                        <td>{row.acao}</td>
                                        <td className="text-center">{row.responsavel}</td>
                                        <td className="text-center">{formatDate(row.prazo)}</td>
                                    </tr>
                                ))}
                                {(!nc.plano_acao_grid || nc.plano_acao_grid.length === 0) && (
                                    <tr><td colSpan="3" className="h-6"></td></tr>
                                )}
                            </tbody>
                        </table>

                        {/* 6. EVIDÊNCIAS */}
                        <table className="nc-table">
                            <tbody>
                                <tr>
                                    <td colSpan="2" className="nc-header">6. EVIDÊNCIAS</td>
                                </tr>
                                {nc.evidencias && nc.evidencias.length > 0 ? (
                                    <tr>
                                        <td colSpan="2" className="p-0 border-0">
                                            <div className="grid grid-cols-2 gap-4 p-4">
                                                {nc.evidencias.map((ev, i) => (
                                                    <div key={i} className="flex flex-col items-center border border-black p-2">
                                                        <div className="h-48 w-full flex items-center justify-center mb-2 overflow-hidden">
                                                            {ev.foto ? (
                                                                <img src={route('nao-conformidades.evidencias.show', { naoConformidade: nc.id, index: i })} className="max-h-full max-w-full object-contain" alt="Evidência" />
                                                            ) : (
                                                                <span className="text-slate-400">Sem Imagem</span>
                                                            )}
                                                        </div>
                                                        <div className="text-xs text-center border-t border-black w-full pt-1">
                                                            {ev.descricao || 'Sem descrição'}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    <tr><td colSpan="2" className="h-12 text-center text-slate-500">Nenhuma evidência anexada.</td></tr>
                                )}
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
