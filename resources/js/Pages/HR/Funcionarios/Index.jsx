import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Edit, Trash2, Users, Search, Building, Printer } from 'lucide-react';
import { useState, useEffect } from 'react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';

export default function Index({ auth, funcionarios, companies, areas, cargos, filters, flash }) {
    const [searchCompany, setSearchCompany] = useState(filters?.company_id || '');
    const [searchQuery, setSearchQuery] = useState(filters?.search || '');
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [activeTab, setActiveTab] = useState('pessoais');

    const { data, setData, post, put, delete: destroy, processing, errors, reset, clearErrors } = useForm({
        company_id: '',
        nome: '',
        cpf: '',
        matricula: '',
        data_admissao: '',
        dependentes: 0,
        estado_civil: '',
        salario_bruto: '',
        telefone: '',
        email: '',
        observacoes: '',
        status: 'Ativo',
        area_id: '',
        cargo_id: '',
        genero: '',
        data_demissao: '',
        motivo_demissao: '',
        cep: '',
        logradouro: '',
        numero: '',
        complemento: '',
        bairro: '',
        cidade: '',
        estado: '',
        carga_horaria_mensal: '',
        horario_trabalho: '',
        data_nascimento: '',
        rg: '',
        nacionalidade: '',
        titulo_eleitor: '',
        carteira_reservista: '',
        naturalidade: '',
        ctps: '',
        pis: '',
        celular: '',
        nome_mae: '',
        nome_pai: '',
        escolaridade: '',
        tipo_sanguineo: '',
        banco: '',
        agencia: '',
        conta_corrente: '',
        parcelas_ferias: '',
        data_decimo_terceiro: '',
        parcelas_decimo_terceiro: '',
    });

    // Buscar CEP
    useEffect(() => {
        const fetchCep = async () => {
            const cleanCep = data.cep?.replace(/\D/g, '') || '';
            if (cleanCep.length === 8) {
                try {
                    const res = await fetch(`https://viacep.com.br/ws/${cleanCep}/json/`);
                    const result = await res.json();
                    if (!result.erro) {
                        setData(d => ({
                            ...d,
                            logradouro: result.logradouro || '',
                            bairro: result.bairro || '',
                            cidade: result.localidade || '',
                            estado: result.uf || '',
                        }));
                    }
                } catch (err) {
                    console.error('Erro ao buscar CEP', err);
                }
            }
        };
        fetchCep();
    }, [data.cep]);

    // Update list on filter change with debounce
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (searchCompany !== (filters?.company_id || '') || searchQuery !== (filters?.search || '')) {
                router.get(
                    route('admin.funcionarios.index'),
                    { company_id: searchCompany, search: searchQuery },
                    { preserveState: true, replace: true }
                );
            }
        }, 300);
        return () => clearTimeout(timeoutId);
    }, [searchCompany, searchQuery]);

    const openCreateModal = () => {
        clearErrors();
        reset();
        setEditing(null);
        setActiveTab('pessoais');
        setShowModal(true);
    };

    const openEditModal = (funcionario) => {
        clearErrors();
        setEditing(funcionario.id);
        setData({
            company_id: funcionario.company_id || '',
            nome: funcionario.nome || '',
            cpf: funcionario.cpf || '',
            matricula: funcionario.matricula || '',
            data_admissao: funcionario.data_admissao || '',
            dependentes: funcionario.dependentes || 0,
            estado_civil: funcionario.estado_civil || '',
            salario_bruto: funcionario.salario_bruto || '',
            telefone: funcionario.telefone || '',
            email: funcionario.email || '',
            observacoes: funcionario.observacoes || '',
            status: funcionario.status || 'Ativo',
            area_id: funcionario.area_id || '',
            cargo_id: funcionario.cargo_id || '',
            genero: funcionario.genero || '',
            data_demissao: funcionario.data_demissao || '',
            motivo_demissao: funcionario.motivo_demissao || '',
            cep: funcionario.cep || '',
            logradouro: funcionario.logradouro || '',
            numero: funcionario.numero || '',
            complemento: funcionario.complemento || '',
            bairro: funcionario.bairro || '',
            cidade: funcionario.cidade || '',
            estado: funcionario.estado || '',
            carga_horaria_mensal: funcionario.carga_horaria_mensal || '',
            horario_trabalho: funcionario.horario_trabalho || '',
            data_nascimento: funcionario.data_nascimento || '',
            rg: funcionario.rg || '',
            nacionalidade: funcionario.nacionalidade || '',
            titulo_eleitor: funcionario.titulo_eleitor || '',
            carteira_reservista: funcionario.carteira_reservista || '',
            naturalidade: funcionario.naturalidade || '',
            ctps: funcionario.ctps || '',
            pis: funcionario.pis || '',
            celular: funcionario.celular || '',
            nome_mae: funcionario.nome_mae || '',
            nome_pai: funcionario.nome_pai || '',
            escolaridade: funcionario.escolaridade || '',
            tipo_sanguineo: funcionario.tipo_sanguineo || '',
            banco: funcionario.banco || '',
            agencia: funcionario.agencia || '',
            conta_corrente: funcionario.conta_corrente || '',
            parcelas_ferias: funcionario.parcelas_ferias || '',
            data_decimo_terceiro: funcionario.data_decimo_terceiro || '',
            parcelas_decimo_terceiro: funcionario.parcelas_decimo_terceiro || '',
        });
        setActiveTab('pessoais');
        setShowModal(true);
    };

    const handleDelete = (id) => {
        if (confirm('Tem certeza que deseja remover este funcionário? Todos os registros de férias vinculados também poderão ser perdidos.')) {
            destroy(route('admin.funcionarios.destroy', id));
        }
    };

    const submit = (e) => {
        e.preventDefault();
        if (editing) {
            put(route('admin.funcionarios.update', editing), {
                onSuccess: () => setShowModal(false)
            });
        } else {
            post(route('admin.funcionarios.store'), {
                onSuccess: () => setShowModal(false)
            });
        }
    };

    const handlePrint = () => {
        window.print();
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Gestão de Funcionários</h2>}
        >
            <Head title="Funcionários" />

            <div className="w-full sm:px-6 lg:px-8 space-y-4 py-8 print:py-0 print:px-0">
                
                {flash?.message && (
                    <div className="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl print:hidden">
                        {flash.message}
                    </div>
                )}
                {flash?.error && (
                    <div className="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl print:hidden">
                        {flash.error}
                    </div>
                )}

                <div className="flex flex-col md:flex-row justify-between items-center gap-3 bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700/50 print:hidden">
                    <div className="relative w-full md:w-1/2 flex flex-col md:flex-row items-center gap-2">
                        <div className="relative w-full flex items-center gap-2">
                            <Building className="w-5 h-5 text-slate-400 shrink-0" />
                            <select
                                className="w-full text-sm border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                value={searchCompany}
                                onChange={(e) => setSearchCompany(e.target.value)}
                            >
                                <option value="">Todas as Empresas (Tenants)</option>
                                {companies.map(c => (
                                    <option key={c.id} value={c.id}>{c.razao_social}</option>
                                ))}
                            </select>
                        </div>
                        <div className="relative w-full flex items-center gap-2">
                            <Search className="w-5 h-5 text-slate-400 shrink-0" />
                            <TextInput
                                type="text"
                                className="w-full text-sm"
                                placeholder="Pesquisar por nome, cpf ou matrícula..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="flex gap-2 w-full md:w-auto">
                        <button 
                            onClick={handlePrint}
                            className="inline-flex items-center gap-2 bg-slate-600 hover:bg-slate-700 text-white px-5 py-2.5 rounded-xl transition-all font-medium text-sm shadow-md hover:shadow-lg w-full md:w-auto justify-center"
                        >
                            <Printer className="w-4 h-4" /> Imprimir
                        </button>
                        <button 
                            onClick={openCreateModal}
                            className="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white px-5 py-2.5 rounded-xl transition-all font-medium text-sm shadow-md hover:shadow-lg w-full md:w-auto justify-center"
                        >
                            <Plus className="w-4 h-4" /> Novo Funcionário
                        </button>
                    </div>
                </div>

                <div className="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden print:shadow-none print:border-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm text-left text-slate-500 dark:text-slate-400 print:text-black">
                            <thead className="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-700/50 dark:text-slate-300 print:bg-white print:text-black print:border-b-2 print:border-black">
                                <tr>
                                    <th className="px-4 py-3">Funcionário</th>
                                    <th className="px-4 py-3">Matrícula</th>
                                    <th className="px-4 py-3">Cargo</th>
                                    <th className="px-4 py-3">Área</th>
                                    <th className="px-4 py-3 text-center">Status</th>
                                    <th className="px-4 py-3 text-right print:hidden">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {funcionarios.data.length > 0 ? funcionarios.data.map((func) => (
                                    <tr key={func.id} className="border-b dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 print:border-b print:border-gray-300">
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-slate-900 dark:text-white print:text-black">{func.nome}</div>
                                            <div className="text-xs text-slate-400 print:text-gray-600">{func.email || func.cpf || '-'}</div>
                                        </td>
                                        <td className="px-4 py-3">{func.matricula || '-'}</td>
                                        <td className="px-4 py-3">{func.cargo?.nome || '-'}</td>
                                        <td className="px-4 py-3">{func.area?.nome || '-'}</td>
                                        <td className="px-4 py-3 text-center">
                                            <span className={`px-2 py-0.5 rounded-full text-xs font-medium print:border print:border-gray-500 print:bg-white print:text-black ${
                                                func.status === 'Ativo' ? 'bg-emerald-100 text-emerald-700' : 
                                                func.status === 'Férias' ? 'bg-amber-100 text-amber-700' : 
                                                'bg-slate-100 text-slate-700'
                                            }`}>
                                                {func.status}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 flex justify-end gap-2 print:hidden">
                                            <button 
                                                onClick={() => router.get(route('admin.funcionarios.show', func.id))}
                                                className="text-indigo-500 hover:text-indigo-700 p-1.5 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 rounded-lg transition-colors"
                                                title="Ver Detalhes"
                                            >
                                                <Users className="w-4 h-4" />
                                            </button>
                                            <button 
                                                onClick={() => openEditModal(func)}
                                                className="text-blue-500 hover:text-blue-700 p-1.5 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-lg transition-colors"
                                                title="Editar"
                                            >
                                                <Edit className="w-4 h-4" />
                                            </button>
                                            <button 
                                                onClick={() => handleDelete(func.id)}
                                                className="text-rose-500 hover:text-rose-700 p-1.5 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-lg transition-colors"
                                                title="Excluir"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr>
                                        <td colSpan="6" className="px-4 py-6 text-center text-slate-500">Nenhum funcionário encontrado.</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Paginação */}
                {funcionarios.links && funcionarios.data.length > 0 && (
                    <div className="flex justify-center mt-4 pb-4 print:hidden">
                        <div className="flex gap-1 flex-wrap">
                            {funcionarios.links.map((link, i) => (
                                <button
                                    key={i}
                                    onClick={() => link.url && router.get(link.url, { company_id: searchCompany, search: searchQuery }, { preserveState: true, replace: true })}
                                    disabled={!link.url}
                                    className={`px-3 py-1 rounded text-sm ${
                                        link.active ? 'bg-teal-600 text-white' : 
                                        !link.url ? 'text-slate-400 cursor-not-allowed' : 
                                        'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* Modal Cadastrar/Editar */}
            <Modal show={showModal} onClose={() => setShowModal(false)} maxWidth="6xl">
                <form onSubmit={submit} className="p-6 bg-slate-50 dark:bg-slate-900 rounded-lg overflow-y-auto max-h-[90vh]">
                    <div className="flex justify-between items-center mb-6 pb-2 border-b">
                        <h2 className="text-xl font-bold text-slate-900 dark:text-slate-100">
                            {editing ? 'Editar Funcionário' : 'Novo Funcionário'}
                        </h2>
                    </div>

                    {/* Abas */}
                    <div className="flex border-b border-slate-200 dark:border-slate-700 mb-6 overflow-x-auto hide-scrollbar">
                        <button
                            type="button"
                            onClick={() => setActiveTab('pessoais')}
                            className={`px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap transition-colors ${
                                activeTab === 'pessoais' ? 'border-teal-500 text-teal-600 dark:text-teal-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                            }`}
                        >
                            Dados Principais e Pessoais
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('funcionais')}
                            className={`px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap transition-colors ${
                                activeTab === 'funcionais' ? 'border-teal-500 text-teal-600 dark:text-teal-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                            }`}
                        >
                            Dados Funcionais
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('endereco')}
                            className={`px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap transition-colors ${
                                activeTab === 'endereco' ? 'border-teal-500 text-teal-600 dark:text-teal-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                            }`}
                        >
                            Endereço Residencial
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('documentos')}
                            className={`px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap transition-colors ${
                                activeTab === 'documentos' ? 'border-teal-500 text-teal-600 dark:text-teal-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                            }`}
                        >
                            Documentação
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('familiares')}
                            className={`px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap transition-colors ${
                                activeTab === 'familiares' ? 'border-teal-500 text-teal-600 dark:text-teal-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                            }`}
                        >
                            Dados Familiares
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('bancarios')}
                            className={`px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap transition-colors ${
                                activeTab === 'bancarios' ? 'border-teal-500 text-teal-600 dark:text-teal-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                            }`}
                        >
                            Dados Bancários
                        </button>
                    </div>

                    {/* Conteúdo das Abas */}
                    {activeTab === 'pessoais' && (
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm">
                        <div className="md:col-span-3">
                            <h3 className="font-semibold text-slate-700 border-b pb-2 mb-4">Dados Principais e Pessoais</h3>
                        </div>

                        <div className="md:col-span-3">
                            <InputLabel htmlFor="company_id" value="Empresa Vinculada *" />
                            <select
                                id="company_id"
                                className="mt-1 block w-full border-slate-300 rounded-md shadow-sm"
                                value={data.company_id}
                                onChange={(e) => setData('company_id', e.target.value)}
                                required
                            >
                                <option value="">Selecione uma empresa</option>
                                {companies.map(c => (
                                    <option key={c.id} value={c.id}>{c.razao_social}</option>
                                ))}
                            </select>
                            <InputError className="mt-2" message={errors.company_id} />
                        </div>

                        <div className="md:col-span-2">
                            <InputLabel htmlFor="nome" value="Nome Completo *" />
                            <TextInput
                                id="nome"
                                className="mt-1 block w-full"
                                value={data.nome}
                                onChange={(e) => setData('nome', e.target.value)}
                                required
                            />
                            <InputError className="mt-2" message={errors.nome} />
                        </div>

                        <div>
                            <InputLabel htmlFor="data_nascimento" value="Data de Nascimento" />
                            <TextInput
                                id="data_nascimento"
                                type="date"
                                className="mt-1 block w-full"
                                value={data.data_nascimento}
                                onChange={(e) => setData('data_nascimento', e.target.value)}
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="email" value="E-mail Pessoal" />
                            <TextInput
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="telefone" value="Telefone Fixo" />
                            <TextInput
                                id="telefone"
                                className="mt-1 block w-full"
                                value={data.telefone}
                                onChange={(e) => setData('telefone', e.target.value)}
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="celular" value="Celular" />
                            <TextInput
                                id="celular"
                                className="mt-1 block w-full"
                                value={data.celular}
                                onChange={(e) => setData('celular', e.target.value)}
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="genero" value="Gênero" />
                            <select
                                id="genero"
                                className="mt-1 block w-full border-slate-300 rounded-md shadow-sm"
                                value={data.genero}
                                onChange={(e) => setData('genero', e.target.value)}
                            >
                                <option value="">Selecione</option>
                                <option value="M">Masculino</option>
                                <option value="F">Feminino</option>
                                <option value="O">Outro</option>
                                <option value="N">Não Informado</option>
                            </select>
                        </div>

                        <div>
                            <InputLabel htmlFor="nacionalidade" value="Nacionalidade" />
                            <TextInput
                                id="nacionalidade"
                                className="mt-1 block w-full"
                                value={data.nacionalidade}
                                onChange={(e) => setData('nacionalidade', e.target.value)}
                                placeholder="Brasileira"
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="naturalidade" value="Naturalidade" />
                            <TextInput
                                id="naturalidade"
                                className="mt-1 block w-full"
                                value={data.naturalidade}
                                onChange={(e) => setData('naturalidade', e.target.value)}
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="escolaridade" value="Escolaridade" />
                            <TextInput
                                id="escolaridade"
                                className="mt-1 block w-full"
                                value={data.escolaridade}
                                onChange={(e) => setData('escolaridade', e.target.value)}
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="tipo_sanguineo" value="Tipo Sanguíneo" />
                            <TextInput
                                id="tipo_sanguineo"
                                className="mt-1 block w-full"
                                value={data.tipo_sanguineo}
                                onChange={(e) => setData('tipo_sanguineo', e.target.value)}
                                placeholder="Ex: O+"
                            />
                        </div>
                    </div>
                    )}

                    {activeTab === 'familiares' && (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm mt-4">
                        <div className="md:col-span-3">
                            <h3 className="font-semibold text-slate-700 border-b pb-2 mb-4">Dados Familiares</h3>
                        </div>
                        <div>
                            <InputLabel htmlFor="estado_civil" value="Estado Civil" />
                            <select
                                id="estado_civil"
                                className="mt-1 block w-full border-slate-300 rounded-md shadow-sm"
                                value={data.estado_civil}
                                onChange={(e) => setData('estado_civil', e.target.value)}
                            >
                                <option value="">Selecione</option>
                                <option value="Solteiro(a)">Solteiro(a)</option>
                                <option value="Casado(a)">Casado(a)</option>
                                <option value="Divorciado(a)">Divorciado(a)</option>
                                <option value="Viúvo(a)">Viúvo(a)</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel htmlFor="dependentes" value="Qtd. Dependentes" />
                            <TextInput
                                id="dependentes"
                                type="number"
                                min="0"
                                className="mt-1 block w-full"
                                value={data.dependentes}
                                onChange={(e) => setData('dependentes', e.target.value)}
                            />
                        </div>
                        <div className="md:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel htmlFor="nome_mae" value="Nome da Mãe" />
                                <TextInput
                                    id="nome_mae"
                                    className="mt-1 block w-full"
                                    value={data.nome_mae}
                                    onChange={(e) => setData('nome_mae', e.target.value)}
                                />
                            </div>
                            <div>
                                <InputLabel htmlFor="nome_pai" value="Nome do Pai" />
                                <TextInput
                                    id="nome_pai"
                                    className="mt-1 block w-full"
                                    value={data.nome_pai}
                                    onChange={(e) => setData('nome_pai', e.target.value)}
                                />
                            </div>
                        </div>
                    </div>
                    )}

                    {activeTab === 'documentos' && (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm mt-4">
                        <div className="md:col-span-3">
                            <h3 className="font-semibold text-slate-700 border-b pb-2 mb-4">Documentação</h3>
                        </div>
                        <div>
                            <InputLabel htmlFor="cpf" value="CPF" />
                            <TextInput
                                id="cpf"
                                className="mt-1 block w-full"
                                value={data.cpf}
                                onChange={(e) => setData('cpf', e.target.value)}
                                placeholder="000.000.000-00"
                            />
                            <InputError className="mt-2" message={errors.cpf} />
                        </div>
                        <div>
                            <InputLabel htmlFor="rg" value="RG" />
                            <TextInput
                                id="rg"
                                className="mt-1 block w-full"
                                value={data.rg}
                                onChange={(e) => setData('rg', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="ctps" value="Carteira de Trabalho (CTPS)" />
                            <TextInput
                                id="ctps"
                                className="mt-1 block w-full"
                                value={data.ctps}
                                onChange={(e) => setData('ctps', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="pis" value="PIS" />
                            <TextInput
                                id="pis"
                                className="mt-1 block w-full"
                                value={data.pis}
                                onChange={(e) => setData('pis', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="titulo_eleitor" value="Título de Eleitor" />
                            <TextInput
                                id="titulo_eleitor"
                                className="mt-1 block w-full"
                                value={data.titulo_eleitor}
                                onChange={(e) => setData('titulo_eleitor', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="carteira_reservista" value="Carteira de Reservista" />
                            <TextInput
                                id="carteira_reservista"
                                className="mt-1 block w-full"
                                value={data.carteira_reservista}
                                onChange={(e) => setData('carteira_reservista', e.target.value)}
                            />
                        </div>
                    </div>
                    )}

                    {activeTab === 'funcionais' && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm mt-4">
                        <div className="md:col-span-2">
                            <h3 className="font-semibold text-slate-700 border-b pb-2 mb-4">Dados Funcionais</h3>
                        </div>

                        <div>
                            <InputLabel htmlFor="matricula" value="Matrícula" />
                            <TextInput
                                id="matricula"
                                className="mt-1 block w-full"
                                value={data.matricula}
                                onChange={(e) => setData('matricula', e.target.value)}
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="data_admissao" value="Data de Admissão" />
                            <TextInput
                                id="data_admissao"
                                type="date"
                                className="mt-1 block w-full"
                                value={data.data_admissao}
                                onChange={(e) => setData('data_admissao', e.target.value)}
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="area_id" value="Departamento (Área)" />
                            <select
                                id="area_id"
                                className="mt-1 block w-full border-slate-300 rounded-md shadow-sm"
                                value={data.area_id}
                                onChange={(e) => setData('area_id', e.target.value)}
                            >
                                <option value="">Selecione a área</option>
                                {areas?.map(a => (
                                    <option key={a.id} value={a.id}>{a.nome}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <InputLabel htmlFor="cargo_id" value="Cargo Ocupado" />
                            <select
                                id="cargo_id"
                                className="mt-1 block w-full border-slate-300 rounded-md shadow-sm"
                                value={data.cargo_id}
                                onChange={(e) => setData('cargo_id', e.target.value)}
                            >
                                <option value="">Selecione o cargo</option>
                                {cargos?.map(c => (
                                    <option key={c.id} value={c.id}>{c.nome}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <InputLabel htmlFor="salario_bruto" value="Salário Bruto Específico (R$)" />
                            <TextInput
                                id="salario_bruto"
                                type="number"
                                step="0.01"
                                className="mt-1 block w-full"
                                value={data.salario_bruto}
                                onChange={(e) => setData('salario_bruto', e.target.value)}
                                placeholder="Padrão: valor do cargo"
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="status" value="Status" />
                            <select
                                id="status"
                                className="mt-1 block w-full border-slate-300 rounded-md shadow-sm"
                                value={data.status}
                                onChange={(e) => setData('status', e.target.value)}
                            >
                                <option value="Ativo">Ativo</option>
                                <option value="Férias">Férias</option>
                                <option value="Afastado">Afastado</option>
                                <option value="Desligado">Desligado</option>
                            </select>
                        </div>
                        
                        {data.status === 'Desligado' && (
                            <>
                                <div>
                                    <InputLabel htmlFor="data_demissao" value="Data de Demissão" />
                                    <TextInput
                                        id="data_demissao"
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.data_demissao}
                                        onChange={(e) => setData('data_demissao', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <InputLabel htmlFor="motivo_demissao" value="Motivo da Demissão" />
                                    <TextInput
                                        id="motivo_demissao"
                                        className="mt-1 block w-full"
                                        value={data.motivo_demissao}
                                        onChange={(e) => setData('motivo_demissao', e.target.value)}
                                    />
                                </div>
                            </>
                        )}
                        <div>
                            <InputLabel htmlFor="carga_horaria_mensal" value="Carga Horária Mensal (h)" />
                            <TextInput
                                id="carga_horaria_mensal"
                                type="number"
                                className="mt-1 block w-full"
                                value={data.carga_horaria_mensal}
                                onChange={(e) => setData('carga_horaria_mensal', e.target.value)}
                                placeholder="Ex: 220"
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="horario_trabalho" value="Horário de Trabalho Padrão" />
                            <TextInput
                                id="horario_trabalho"
                                className="mt-1 block w-full"
                                value={data.horario_trabalho}
                                onChange={(e) => setData('horario_trabalho', e.target.value)}
                                placeholder="Ex: 08:00 às 18:00"
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="parcelas_ferias" value="Férias pagas em qtas parcelas?" />
                            <TextInput
                                id="parcelas_ferias"
                                type="number"
                                min="0"
                                className="mt-1 block w-full"
                                value={data.parcelas_ferias}
                                onChange={(e) => setData('parcelas_ferias', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="data_decimo_terceiro" value="Datas 13º Salário" />
                            <TextInput
                                id="data_decimo_terceiro"
                                className="mt-1 block w-full"
                                value={data.data_decimo_terceiro}
                                onChange={(e) => setData('data_decimo_terceiro', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="parcelas_decimo_terceiro" value="13º Salário em qtas parcelas?" />
                            <TextInput
                                id="parcelas_decimo_terceiro"
                                type="number"
                                min="0"
                                className="mt-1 block w-full"
                                value={data.parcelas_decimo_terceiro}
                                onChange={(e) => setData('parcelas_decimo_terceiro', e.target.value)}
                            />
                        </div>
                    </div>
                    )}

                    {activeTab === 'bancarios' && (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm mt-4">
                        <div className="md:col-span-3">
                            <h3 className="font-semibold text-slate-700 border-b pb-2 mb-4">Dados Bancários</h3>
                        </div>
                        <div>
                            <InputLabel htmlFor="banco" value="Banco" />
                            <TextInput
                                id="banco"
                                className="mt-1 block w-full"
                                value={data.banco}
                                onChange={(e) => setData('banco', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="agencia" value="Agência" />
                            <TextInput
                                id="agencia"
                                className="mt-1 block w-full"
                                value={data.agencia}
                                onChange={(e) => setData('agencia', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="conta_corrente" value="Conta Corrente" />
                            <TextInput
                                id="conta_corrente"
                                className="mt-1 block w-full"
                                value={data.conta_corrente}
                                onChange={(e) => setData('conta_corrente', e.target.value)}
                            />
                        </div>
                    </div>
                    )}

                    {/* Endereço */}
                    {activeTab === 'endereco' && (
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm mt-4">
                        <div className="md:col-span-4">
                            <h3 className="font-semibold text-slate-700 border-b pb-2 mb-4">Endereço Residencial</h3>
                        </div>

                        <div>
                            <InputLabel htmlFor="cep" value="CEP" />
                            <TextInput
                                id="cep"
                                className="mt-1 block w-full"
                                value={data.cep}
                                onChange={(e) => setData('cep', e.target.value)}
                                placeholder="00000-000"
                            />
                        </div>

                        <div className="md:col-span-2">
                            <InputLabel htmlFor="logradouro" value="Logradouro" />
                            <TextInput
                                id="logradouro"
                                className="mt-1 block w-full"
                                value={data.logradouro}
                                onChange={(e) => setData('logradouro', e.target.value)}
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="numero" value="Número" />
                            <TextInput
                                id="numero"
                                className="mt-1 block w-full"
                                value={data.numero}
                                onChange={(e) => setData('numero', e.target.value)}
                            />
                        </div>

                        <div className="md:col-span-2">
                            <InputLabel htmlFor="complemento" value="Complemento" />
                            <TextInput
                                id="complemento"
                                className="mt-1 block w-full"
                                value={data.complemento}
                                onChange={(e) => setData('complemento', e.target.value)}
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="bairro" value="Bairro" />
                            <TextInput
                                id="bairro"
                                className="mt-1 block w-full"
                                value={data.bairro}
                                onChange={(e) => setData('bairro', e.target.value)}
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="cidade" value="Cidade" />
                            <TextInput
                                id="cidade"
                                className="mt-1 block w-full"
                                value={data.cidade}
                                onChange={(e) => setData('cidade', e.target.value)}
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="estado" value="UF" />
                            <TextInput
                                id="estado"
                                className="mt-1 block w-full"
                                value={data.estado}
                                onChange={(e) => setData('estado', e.target.value)}
                                maxLength="2"
                            />
                        </div>
                    </div>
                    )}

                    <div className="mt-6 flex justify-end gap-3 border-t border-slate-200 dark:border-slate-700 pt-4">
                        <button
                            type="button"
                            onClick={() => setShowModal(false)}
                            className="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-4 py-2 text-sm font-medium text-white bg-teal-600 border border-transparent rounded-lg hover:bg-teal-700 disabled:opacity-50"
                        >
                            {processing ? 'Salvando...' : 'Salvar Funcionário'}
                        </button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
