import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { ShieldCheck, XCircle, CheckCircle } from 'lucide-react';

export default function EmpresaAcesso({ companies }) {
    
    const handleToggle = (companyId) => {
        router.post(route('file-manager.empresa-acesso.toggle', companyId), {}, {
            preserveScroll: true
        });
    };

    return (
        <AuthenticatedLayout header="Acesso por Empresa (Master Admin)">
            <Head title="Acesso por Empresa" />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div className="p-6 text-gray-900">
                            
                            <div className="mb-6 flex items-center gap-3">
                                <ShieldCheck className="w-8 h-8 text-indigo-500" />
                                <div>
                                    <h3 className="text-lg font-medium">Controle de Acesso ao Gerenciador de Arquivos</h3>
                                    <p className="text-sm text-gray-500">Defina quais empresas possuem acesso ao módulo de arquivos. O Master Admin sempre tem acesso global.</p>
                                </div>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                ID
                                            </th>
                                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Nome Fantasia
                                            </th>
                                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Razão Social
                                            </th>
                                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th scope="col" className="relative px-6 py-3">
                                                <span className="sr-only">Ações</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {companies.map((company) => (
                                            <tr key={company.id}>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {company.id}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {company.nome_fantasia}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {company.razao_social}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {company.fm_habilitado ? (
                                                        <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                            Liberado
                                                        </span>
                                                    ) : (
                                                        <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                            Bloqueado
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <button
                                                        onClick={() => handleToggle(company.id)}
                                                        className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-sm transition-colors ${
                                                            company.fm_habilitado 
                                                                ? 'text-red-600 bg-red-50 hover:bg-red-100' 
                                                                : 'text-green-600 bg-green-50 hover:bg-green-100'
                                                        }`}
                                                    >
                                                        {company.fm_habilitado ? (
                                                            <><XCircle className="w-4 h-4" /> Revogar Acesso</>
                                                        ) : (
                                                            <><CheckCircle className="w-4 h-4" /> Liberar Acesso</>
                                                        )}
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
