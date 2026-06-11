import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { Save, ArrowLeft, Search } from 'lucide-react';

export default function Form({ auth, fornecedor, companyId }) {
    
    const { data, setData, put, post, processing, errors } = useForm({
        company_id: companyId,
        razao_social: fornecedor?.razao_social || '',
        cnpj_cpf: fornecedor?.cnpj_cpf || '',
        categoria: fornecedor?.categoria || '',
        criticidade: fornecedor?.criticidade || 'media',
        status_homologacao: fornecedor?.status_homologacao || 'pendente',
        contato_nome: fornecedor?.contato_nome || '',
        email: fornecedor?.email || '',
        telefone: fornecedor?.telefone || '',
        cep: fornecedor?.cep || '',
        logradouro: fornecedor?.logradouro || '',
        numero: fornecedor?.numero || '',
        complemento: fornecedor?.complemento || '',
        bairro: fornecedor?.bairro || '',
        cidade: fornecedor?.cidade || '',
        estado: fornecedor?.estado || '',
        observacoes: fornecedor?.observacoes || '',
    });

    const [isFetchingCep, setIsFetchingCep] = useState(false);
    const [isFetchingCnpj, setIsFetchingCnpj] = useState(false);

    const handleCnpjCpfChange = (e) => {
        let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não for número

        if (value.length <= 11) {
            // Máscara CPF: 000.000.000-00
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        } else {
            // Máscara CNPJ: 00.000.000/0000-00
            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
        }

        setData('cnpj_cpf', value);
    };

    const handleTelefoneChange = (e) => {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length <= 10) {
            value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
        } else {
            value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
        }
        setData('telefone', value);
    };

    const handleCepChange = (e) => {
        let value = e.target.value.replace(/\D/g, '');
        value = value.replace(/^(\d{5})(\d)/, '$1-$2');
        setData('cep', value);
    };

    const buscarCnpj = async () => {
        const cleanCnpj = data.cnpj_cpf.replace(/\D/g, '');
        if (cleanCnpj.length !== 14) return; // Só busca se for CNPJ (14 dígitos)

        setIsFetchingCnpj(true);
        try {
            // Utilizando a API pública BrasilAPI (gratuita, rápida e sem CORS issues)
            const response = await fetch(`https://brasilapi.com.br/api/cnpj/v1/${cleanCnpj}`);
            const json = await response.json();
            
            if (response.ok && !json.erro) {
                // Preencher formulário com os dados retornados
                setData(prevData => ({
                    ...prevData,
                    razao_social: json.razao_social || prevData.razao_social,
                    cep: json.cep ? json.cep.replace(/^(\d{5})(\d{3})$/, '$1-$2') : prevData.cep,
                    logradouro: json.logradouro || json.descricao_tipo_de_logradouro + ' ' + json.logradouro,
                    numero: json.numero || prevData.numero,
                    complemento: json.complemento || prevData.complemento,
                    bairro: json.bairro || prevData.bairro,
                    cidade: json.municipio || prevData.cidade,
                    estado: json.uf || prevData.estado,
                    telefone: json.ddd_telefone_1 || prevData.telefone,
                }));
            } else {
                alert('CNPJ não encontrado ou inválido.');
            }
        } catch (error) {
            console.error('Erro ao buscar CNPJ:', error);
            alert('Erro ao comunicar com a API de CNPJ.');
        } finally {
            setIsFetchingCnpj(false);
        }
    };

    const buscarCep = async () => {
        const cleanCep = data.cep.replace(/\D/g, '');
        if (cleanCep.length !== 8) return;

        setIsFetchingCep(true);
        try {
            const response = await fetch(`https://viacep.com.br/ws/${cleanCep}/json/`);
            const json = await response.json();
            if (!json.erro) {
                setData(prevData => ({
                    ...prevData,
                    logradouro: json.logradouro,
                    bairro: json.bairro,
                    cidade: json.localidade,
                    estado: json.uf,
                }));
            }
        } catch (error) {
            console.error('Erro ao buscar CEP:', error);
        } finally {
            setIsFetchingCep(false);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        if (fornecedor) {
            put(route('fornecedores.update', fornecedor.id));
        } else {
            post(route('fornecedores.store'));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {fornecedor ? 'Editar Fornecedor' : 'Novo Fornecedor'}
            </h2>}
        >
            <Head title={fornecedor ? 'Editar Fornecedor' : 'Novo Fornecedor'} />

            <div className="py-12">
                <div className="w-full sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            
                            <form onSubmit={submit} className="space-y-8">
                                
                                {/* DADOS BÁSICOS */}
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 border-b pb-2 mb-4">Dados Principais</h3>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Razão Social / Nome *</label>
                                            <input
                                                type="text"
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={data.razao_social}
                                                onChange={e => setData('razao_social', e.target.value)}
                                                required
                                            />
                                            {errors.razao_social && <p className="mt-1 text-sm text-red-600">{errors.razao_social}</p>}
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">CNPJ / CPF</label>
                                            <div className="flex mt-1">
                                                <input
                                                    type="text"
                                                    className="block w-full rounded-l-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    value={data.cnpj_cpf}
                                                    onChange={handleCnpjCpfChange}
                                                    maxLength="18"
                                                    placeholder="000.000.000-00 ou 00.000.000/0000-00"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={buscarCnpj}
                                                    disabled={isFetchingCnpj || data.cnpj_cpf.replace(/\D/g, '').length !== 14}
                                                    className="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-r-md font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                                                    title="Buscar dados do CNPJ"
                                                >
                                                    <Search className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Categoria (Opcional)</label>
                                            <input
                                                type="text"
                                                placeholder="Ex: Matéria Prima, TI..."
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={data.categoria}
                                                onChange={e => setData('categoria', e.target.value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Criticidade</label>
                                            <select
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={data.criticidade}
                                                onChange={e => setData('criticidade', e.target.value)}
                                            >
                                                <option value="baixa">Baixa</option>
                                                <option value="media">Média</option>
                                                <option value="alta">Alta</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Status de Homologação</label>
                                            <select
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={data.status_homologacao}
                                                onChange={e => setData('status_homologacao', e.target.value)}
                                            >
                                                <option value="pendente">Pendente / Em Homologação</option>
                                                <option value="aprovado">Aprovado</option>
                                                <option value="reprovado">Reprovado</option>
                                                <option value="inativo">Inativo</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                {/* CONTATO */}
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 border-b pb-2 mb-4">Contato Oficial</h3>
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome do Contato</label>
                                            <input
                                                type="text"
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={data.contato_nome}
                                                onChange={e => setData('contato_nome', e.target.value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">E-mail Corporativo</label>
                                            <input
                                                type="email"
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={data.email}
                                                onChange={e => setData('email', e.target.value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Telefone / Celular</label>
                                            <input
                                                type="text"
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={data.telefone}
                                                onChange={handleTelefoneChange}
                                                maxLength="15"
                                                placeholder="(00) 00000-0000"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {/* ENDEREÇO COMPLETO */}
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 border-b pb-2 mb-4">Endereço (Matriz)</h3>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">CEP</label>
                                            <div className="flex mt-1">
                                                <input
                                                    type="text"
                                                    className="block w-full rounded-l-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    value={data.cep}
                                                    onChange={handleCepChange}
                                                    maxLength="9"
                                                    placeholder="00000-000"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={buscarCep}
                                                    disabled={isFetchingCep}
                                                    className="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-r-md font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                                                >
                                                    <Search className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                        <div className="md:col-span-2">
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Logradouro / Rua</label>
                                            <input
                                                type="text"
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={data.logradouro}
                                                onChange={e => setData('logradouro', e.target.value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Número</label>
                                            <input
                                                type="text"
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={data.numero}
                                                onChange={e => setData('numero', e.target.value)}
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Complemento</label>
                                            <input
                                                type="text"
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={data.complemento}
                                                onChange={e => setData('complemento', e.target.value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Bairro</label>
                                            <input
                                                type="text"
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={data.bairro}
                                                onChange={e => setData('bairro', e.target.value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Cidade</label>
                                            <input
                                                type="text"
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={data.cidade}
                                                onChange={e => setData('cidade', e.target.value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado (UF)</label>
                                            <input
                                                type="text"
                                                maxLength="2"
                                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 uppercase"
                                                value={data.estado}
                                                onChange={e => setData('estado', e.target.value.toUpperCase())}
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Observações Gerais</label>
                                    <textarea
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        rows="3"
                                        value={data.observacoes}
                                        onChange={e => setData('observacoes', e.target.value)}
                                    ></textarea>
                                </div>

                                <div className="flex items-center justify-end gap-4 mt-8 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <Link
                                        href={route('fornecedores.index')}
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
                                        {fornecedor ? 'Salvar Alterações' : 'Cadastrar Fornecedor'}
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
