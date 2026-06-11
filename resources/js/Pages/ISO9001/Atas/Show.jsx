import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle, FileText } from 'lucide-react';
import { format } from 'date-fns';

export default function Show({ auth, ata, meuStatus }) {
    
    const handleAssinar = () => {
        if (confirm('Confirma a assinatura eletrônica desta ata? Esta ação não pode ser desfeita.')) {
            router.post(route('atas-reuniao.assinar', ata.id));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Ata de Reunião</h2>}
        >
            <Head title="Ata de Reunião" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    
                    {meuStatus && !meuStatus.assinado && ata.status === 'aguardando_assinaturas' && (
                        <div className="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                            <div className="flex items-center justify-between">
                                <div className="flex">
                                    <div className="flex-shrink-0">
                                        <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                        </svg>
                                    </div>
                                    <div className="ml-3">
                                        <p className="text-sm text-yellow-700 font-medium">
                                            Sua assinatura é necessária nesta ata.
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <button
                                        onClick={handleAssinar}
                                        className="bg-indigo-600 text-white px-4 py-2 rounded-md font-semibold text-sm hover:bg-indigo-700 flex items-center shadow"
                                    >
                                        <CheckCircle className="w-4 h-4 mr-2" />
                                        Assinar Digitalmente
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                        <div className="p-8">
                            
                            {/* Header da Ata */}
                            <div className="grid grid-cols-12 border border-gray-900 dark:border-gray-300 mb-6 text-gray-900 dark:text-gray-100">
                                <div className="col-span-3 border-r border-gray-900 dark:border-gray-300 flex flex-col items-center justify-center p-4">
                                    <div className="text-3xl text-red-800 dark:text-red-400 mb-2">&#x2628;</div>
                                    <div className="text-xs font-bold text-center uppercase text-red-800 dark:text-red-400">
                                        {ata.empresa.nome_fantasia}
                                    </div>
                                </div>
                                <div className="col-span-6 border-r border-gray-900 dark:border-gray-300 flex items-center justify-center p-4">
                                    <h1 className="text-xl font-bold">ATA DE REUNIÃO</h1>
                                </div>
                                <div className="col-span-3 flex flex-col">
                                    <div className="border-b border-gray-900 dark:border-gray-300 p-2 flex text-sm">
                                        <span className="font-bold w-16">Data:</span>
                                        <span>{format(new Date(ata.data), 'dd/MM/yyyy')}</span>
                                    </div>
                                    <div className="border-b border-gray-900 dark:border-gray-300 p-2 flex text-sm">
                                        <span className="font-bold w-16">Início:</span>
                                        <span>{ata.hora_inicio.slice(0, 5)}</span>
                                    </div>
                                    <div className="p-2 flex text-sm">
                                        <span className="font-bold w-16">Término:</span>
                                        <span>{ata.hora_termino.slice(0, 5)}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Informações Básicas */}
                            <div className="border border-gray-900 dark:border-gray-300 text-sm text-gray-900 dark:text-gray-100 mb-6">
                                <div className="border-b border-gray-900 dark:border-gray-300 p-2">
                                    <span className="font-bold uppercase mr-2">Local:</span>
                                    {ata.local}
                                </div>
                                <div className="border-b border-gray-900 dark:border-gray-300 p-2">
                                    <span className="font-bold uppercase mr-2">Participantes:</span>
                                    {ata.participantes.map(p => p.user.name).join(', ')}
                                </div>
                                <div className="border-b border-gray-900 dark:border-gray-300 p-2">
                                    <span className="font-bold uppercase mr-2">Assunto:</span>
                                    {ata.assunto}
                                </div>
                                <div className="p-2">
                                    <span className="font-bold uppercase block mb-2">Pautas:</span>
                                    <div className="prose prose-sm dark:prose-invert max-w-none" dangerouslySetInnerHTML={{ __html: ata.pautas }}></div>
                                </div>
                            </div>

                            {/* Registro */}
                            <div className="mb-8">
                                <h3 className="font-bold uppercase text-gray-900 dark:text-gray-100 mb-2">Registro:</h3>
                                <div className="border border-gray-900 dark:border-gray-300 p-4 min-h-[200px] text-gray-900 dark:text-gray-100 prose prose-sm dark:prose-invert max-w-none" dangerouslySetInnerHTML={{ __html: ata.registro || '' }}></div>
                            </div>

                            <div className="text-gray-900 dark:text-gray-100 mb-8 uppercase text-sm">
                                <strong>Responsável pelo registro da ata:</strong> {ata.responsavel.name}
                            </div>

                            {/* Tabela de Assinaturas (Somente se não for rascunho) */}
                            {ata.status !== 'rascunho' && (
                                <div className="mt-8 border-t border-gray-200 dark:border-gray-700 pt-8">
                                    <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">Assinaturas Eletrônicas</h3>
                                    <div className="overflow-x-auto">
                                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700">
                                            <thead className="bg-gray-50 dark:bg-slate-700">
                                                <tr>
                                                    <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nome</th>
                                                    <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Data/Hora</th>
                                                    <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Hash</th>
                                                    <th className="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody className="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                {ata.participantes.map(part => (
                                                    <tr key={part.id}>
                                                        <td className="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 font-medium">
                                                            {part.user.name}
                                                        </td>
                                                        <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                                            {part.data_assinatura ? format(new Date(part.data_assinatura), 'dd/MM/yyyy HH:mm:ss') : '-'}
                                                        </td>
                                                        <td className="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 break-all max-w-[200px]">
                                                            {part.hash_assinatura || '-'}
                                                        </td>
                                                        <td className="px-4 py-3 text-sm text-center">
                                                            {part.assinado ? (
                                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400">
                                                                    Assinado
                                                                </span>
                                                            ) : (
                                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                                    Pendente
                                                                </span>
                                                            )}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            )}

                        </div>
                        
                        <div className="bg-gray-50 dark:bg-slate-700/50 px-6 py-4 flex items-center justify-between border-t border-gray-200 dark:border-gray-700">
                            <Link
                                href={route('atas-reuniao.index')}
                                className="flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition"
                            >
                                <ArrowLeft className="w-4 h-4 mr-2" />
                                Voltar para a lista
                            </Link>

                            <a
                                href={route('atas-reuniao.pdf', ata.id)}
                                target="_blank"
                                className="flex items-center px-4 py-2 bg-white dark:bg-slate-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition"
                            >
                                <FileText className="w-4 h-4 mr-2 text-gray-500" />
                                Baixar PDF
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
