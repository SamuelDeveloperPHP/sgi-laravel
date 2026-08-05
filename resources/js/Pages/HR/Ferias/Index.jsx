import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Edit, Trash2, Search, Building, CalendarDays, FileText, Printer } from 'lucide-react';
import { useState, useEffect } from 'react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';

export default function Index({ auth, ferias, funcionarios, filters, flash }) {
    const [searchCompany, setSearchCompany] = useState(filters?.company_id || '');
    const [searchQuery, setSearchQuery] = useState(filters?.search || '');
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);

    const { data, setData, post, put, delete: destroy, processing, errors, reset, clearErrors } = useForm({
        funcionario_id: '',
        periodo_aquisitivo_inicio: '',
        periodo_aquisitivo_fim: '',
        dias_direito: 30,
        opcao_abono: false,
        dias_abono: 0,
        gozo_1_inicio: '',
        gozo_1_fim: '',
        gozo_2_inicio: '',
        gozo_2_fim: '',
        gozo_3_inicio: '',
        gozo_3_fim: '',
        faltas: 0,
        valor_proventos: '',
        valor_1_3: '',
        valor_1_3_abono: '',
        desconto_inss: '',
        desconto_irpf: '',
        valor_liquido: '',
        status: 'Programada'
    });

    // Filtra apenas funcionários da empresa selecionada para o dropdown
    const availableFuncionarios = searchCompany 
        ? funcionarios.filter(f => f.company_id == searchCompany)
        : funcionarios;

    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (searchCompany !== (filters?.company_id || '') || searchQuery !== (filters?.search || '')) {
                router.get(
                    route('admin.ferias.index'),
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
        setShowModal(true);
    };

    const openEditModal = (feria) => {
        clearErrors();
        setEditing(feria.id);
        setData({
            funcionario_id: feria.funcionario_id || '',
            periodo_aquisitivo_inicio: feria.periodo_aquisitivo_inicio || '',
            periodo_aquisitivo_fim: feria.periodo_aquisitivo_fim || '',
            dias_direito: feria.dias_direito || 30,
            opcao_abono: !!feria.opcao_abono,
            dias_abono: feria.dias_abono || 0,
            gozo_1_inicio: feria.gozo_1_inicio || '',
            gozo_1_fim: feria.gozo_1_fim || '',
            gozo_2_inicio: feria.gozo_2_inicio || '',
            gozo_2_fim: feria.gozo_2_fim || '',
            gozo_3_inicio: feria.gozo_3_inicio || '',
            gozo_3_fim: feria.gozo_3_fim || '',
            faltas: feria.faltas || 0,
            valor_proventos: feria.valor_proventos || '',
            valor_1_3: feria.valor_1_3 || '',
            valor_1_3_abono: feria.valor_1_3_abono || '',
            desconto_inss: feria.desconto_inss || '',
            desconto_irpf: feria.desconto_irpf || '',
            valor_liquido: feria.valor_liquido || '',
            status: feria.status || 'Programada'
        });
        setShowModal(true);
    };

    const handleDelete = (id) => {
        if (confirm('Tem certeza que deseja remover este registro de férias?')) {
            destroy(route('admin.ferias.destroy', id));
        }
    };

    const submit = (e) => {
        e.preventDefault();
        
        // Calcular líquido via JS para mandar certinho pro banco, caso o user tenha digitado e esquecido
        // Líquido = (Proventos + 1/3 Férias + Abono (se houver)) - (INSS + IRPF)
        let liq = 0;
        let proventos = parseFloat(data.valor_proventos) || 0;
        let terco = parseFloat(data.valor_1_3) || 0;
        let tercoAbono = parseFloat(data.valor_1_3_abono) || 0;
        let inss = parseFloat(data.desconto_inss) || 0;
        let irpf = parseFloat(data.desconto_irpf) || 0;
        
        liq = (proventos + terco + tercoAbono) - (inss + irpf);

        const payload = {
            ...data,
            valor_liquido: liq > 0 ? liq.toFixed(2) : 0
        };

        if (editing) {
            put(route('admin.ferias.update', editing), {
                data: payload,
                onSuccess: () => setShowModal(false)
            });
        } else {
            post(route('admin.ferias.store'), {
                data: payload,
                onSuccess: () => setShowModal(false)
            });
        }
    };

    const handlePrint = () => {
        window.print();
    };

    // Extrai as empresas únicas dos funcionários
    const companiesList = Array.from(new Map(funcionarios.map(f => [f.company.id, f.company])).values());

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Controle de Férias</h2>}
        >
            <Head title="Controle de Férias" />

            <div className="w-full sm:px-6 lg:px-8 space-y-4">
                
                {flash?.message && (
                    <div className="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl">
                        {flash.message}
                    </div>
                )}
                {flash?.error && (
                    <div className="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl">
                        {flash.error}
                    </div>
                )}

                <div className="flex flex-col md:flex-row justify-between items-center gap-3 bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700/50">
                    <div className="relative w-full md:w-1/2 flex flex-col md:flex-row items-center gap-2">
                        <div className="relative w-full flex items-center gap-2">
                            <Building className="w-5 h-5 text-slate-400 shrink-0" />
                            <select
                                className="w-full text-sm border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                value={searchCompany}
                                onChange={(e) => setSearchCompany(e.target.value)}
                            >
                                <option value="">Todas as Empresas (Tenants)</option>
                                {companiesList.map(c => (
                                    <option key={c.id} value={c.id}>{c.razao_social}</option>
                                ))}
                            </select>
                        </div>
                        <div className="relative w-full flex items-center gap-2">
                            <Search className="w-5 h-5 text-slate-400 shrink-0" />
                            <TextInput
                                type="text"
                                className="w-full text-sm"
                                placeholder="Pesquisar funcionário (Nome, Matrícula, CPF)..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="flex gap-2 w-full md:w-auto">
                        <button 
                            onClick={() => router.get(route('admin.ferias.mapa'))}
                            className="inline-flex items-center gap-2 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-5 py-2.5 rounded-xl transition-all font-medium text-sm shadow-sm w-full md:w-auto justify-center"
                        >
                            <CalendarDays className="w-4 h-4" /> Visualizar Mapa
                        </button>
                        <button 
                            onClick={handlePrint}
                            className="inline-flex items-center gap-2 bg-slate-600 hover:bg-slate-700 text-white px-5 py-2.5 rounded-xl transition-all font-medium text-sm shadow-md hover:shadow-lg w-full md:w-auto justify-center"
                        >
                            <Printer className="w-4 h-4" /> Imprimir
                        </button>
                        <button 
                            onClick={openCreateModal}
                            className="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl transition-all font-medium text-sm shadow-md hover:shadow-lg w-full md:w-auto justify-center"
                        >
                            <Plus className="w-4 h-4" /> Programar Férias
                        </button>
                    </div>
                </div>

                <div className="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden print:shadow-none print:border-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm text-left text-slate-500 dark:text-slate-400 print:text-black">
                            <thead className="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-700/50 dark:text-slate-300 print:bg-white print:text-black print:border-b-2 print:border-black">
                                <tr>
                                    <th className="px-4 py-3">Funcionário</th>
                                    <th className="px-4 py-3">Período Aquisitivo</th>
                                    <th className="px-4 py-3">1º Gozo</th>
                                    <th className="px-4 py-3 text-center">Abono (Dias)</th>
                                    <th className="px-4 py-3 text-center">Status</th>
                                    <th className="px-4 py-3 text-right print:hidden">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {ferias.data.length > 0 ? ferias.data.map((f) => (
                                    <tr key={f.id} className="border-b dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 print:border-b print:border-gray-300">
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-slate-900 dark:text-white print:text-black">{f.funcionario?.nome}</div>
                                            <div className="text-xs text-slate-400 print:text-gray-600">{f.funcionario?.company?.razao_social}</div>
                                        </td>
                                        <td className="px-4 py-3">
                                            {f.periodo_aquisitivo_inicio ? new Date(f.periodo_aquisitivo_inicio).toLocaleDateString('pt-BR') : '-'} até {f.periodo_aquisitivo_fim ? new Date(f.periodo_aquisitivo_fim).toLocaleDateString('pt-BR') : '-'}
                                        </td>
                                        <td className="px-4 py-3 font-medium">
                                            {f.gozo_1_inicio ? new Date(f.gozo_1_inicio).toLocaleDateString('pt-BR') : '-'}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            {f.opcao_abono ? <span className="text-emerald-600 font-bold">{f.dias_abono}</span> : <span className="text-slate-400">Não</span>}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <span className={`px-2 py-0.5 rounded-full text-xs font-medium print:border print:border-gray-500 print:bg-white print:text-black ${
                                                f.status === 'Programada' ? 'bg-blue-100 text-blue-700' : 
                                                f.status === 'Em Gozo' ? 'bg-amber-100 text-amber-700' : 
                                                f.status === 'Concluída' ? 'bg-emerald-100 text-emerald-700' : 
                                                'bg-slate-100 text-slate-700'
                                            }`}>
                                                {f.status}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 flex justify-end gap-2 print:hidden">
                                            <button 
                                                onClick={() => router.get(route('admin.ferias.show', f.id))}
                                                className="text-indigo-500 hover:text-indigo-700 p-1.5 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 rounded-lg transition-colors"
                                                title="Ver Detalhes"
                                            >
                                                <FileText className="w-4 h-4" />
                                            </button>
                                            <button 
                                                onClick={() => openEditModal(f)}
                                                className="text-blue-500 hover:text-blue-700 p-1.5 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-lg transition-colors"
                                                title="Editar"
                                            >
                                                <Edit className="w-4 h-4" />
                                            </button>
                                            <button 
                                                onClick={() => handleDelete(f.id)}
                                                className="text-rose-500 hover:text-rose-700 p-1.5 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-lg transition-colors"
                                                title="Excluir"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr>
                                        <td colSpan="6" className="px-4 py-6 text-center text-slate-500">Nenhum registro de férias encontrado.</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Paginação */}
                {ferias.links && ferias.data.length > 0 && (
                    <div className="flex justify-center mt-4 pb-4 print:hidden">
                        <div className="flex gap-1 flex-wrap">
                            {ferias.links.map((link, i) => (
                                <button
                                    key={i}
                                    onClick={() => link.url && router.get(link.url, { company_id: searchCompany, search: searchQuery }, { preserveState: true, replace: true })}
                                    disabled={!link.url}
                                    className={`px-3 py-1 rounded text-sm ${
                                        link.active ? 'bg-indigo-600 text-white' : 
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
            <Modal show={showModal} onClose={() => setShowModal(false)} maxWidth="4xl">
                <form onSubmit={submit} className="p-6 bg-white dark:bg-slate-800 max-h-[90vh] overflow-y-auto">
                    <h2 className="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2">
                        <CalendarDays className="w-5 h-5 text-indigo-500"/>
                        {editing ? 'Editar Programação de Férias' : 'Programar Férias'}
                    </h2>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        {/* Seção Principal */}
                        <div className="md:col-span-3 bg-slate-50 dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-700 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div className="md:col-span-3">
                                <InputLabel htmlFor="funcionario_id" value="Funcionário *" />
                                <select
                                    id="funcionario_id"
                                    className="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    value={data.funcionario_id}
                                    onChange={(e) => setData('funcionario_id', e.target.value)}
                                    required
                                >
                                    <option value="">Selecione um funcionário</option>
                                    {availableFuncionarios.map(f => (
                                        <option key={f.id} value={f.id}>{f.nome} ({f.company?.nome_fantasia})</option>
                                    ))}
                                </select>
                                <InputError className="mt-2" message={errors.funcionario_id} />
                            </div>

                            <div>
                                <InputLabel htmlFor="periodo_aquisitivo_inicio" value="Início Período Aquisitivo" />
                                <TextInput
                                    id="periodo_aquisitivo_inicio"
                                    type="date"
                                    className="mt-1 block w-full"
                                    value={data.periodo_aquisitivo_inicio}
                                    onChange={(e) => setData('periodo_aquisitivo_inicio', e.target.value)}
                                />
                            </div>
                            <div>
                                <InputLabel htmlFor="periodo_aquisitivo_fim" value="Fim Período Aquisitivo" />
                                <TextInput
                                    id="periodo_aquisitivo_fim"
                                    type="date"
                                    className="mt-1 block w-full"
                                    value={data.periodo_aquisitivo_fim}
                                    onChange={(e) => setData('periodo_aquisitivo_fim', e.target.value)}
                                />
                            </div>
                            <div>
                                <InputLabel htmlFor="dias_direito" value="Dias de Direito" />
                                <TextInput
                                    id="dias_direito"
                                    type="number"
                                    className="mt-1 block w-full"
                                    value={data.dias_direito}
                                    onChange={(e) => setData('dias_direito', e.target.value)}
                                    required
                                />
                            </div>
                        </div>

                        {/* Faltas e Abono */}
                        <div className="md:col-span-1 space-y-4">
                            <h3 className="font-semibold text-sm text-slate-700 dark:text-slate-300 border-b pb-2">Abono & Faltas</h3>
                            
                            <div>
                                <InputLabel htmlFor="faltas" value="Faltas Injustificadas" />
                                <TextInput
                                    id="faltas"
                                    type="number"
                                    min="0"
                                    className="mt-1 block w-full"
                                    value={data.faltas}
                                    onChange={(e) => setData('faltas', e.target.value)}
                                />
                            </div>

                            <div className="flex items-center gap-3 pt-2">
                                <input
                                    type="checkbox"
                                    id="opcao_abono"
                                    checked={data.opcao_abono}
                                    onChange={(e) => setData('opcao_abono', e.target.checked)}
                                    className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <InputLabel htmlFor="opcao_abono" value="Vender Férias (Abono)?" className="mb-0" />
                            </div>

                            {data.opcao_abono && (
                                <div>
                                    <InputLabel htmlFor="dias_abono" value="Qtd. Dias Abono" />
                                    <TextInput
                                        id="dias_abono"
                                        type="number"
                                        min="1"
                                        max="10"
                                        className="mt-1 block w-full border-emerald-500 focus:border-emerald-500 focus:ring-emerald-500"
                                        value={data.dias_abono}
                                        onChange={(e) => setData('dias_abono', e.target.value)}
                                    />
                                </div>
                            )}
                            
                            <div>
                                <InputLabel htmlFor="status" value="Status das Férias" />
                                <select
                                    id="status"
                                    className="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    value={data.status}
                                    onChange={(e) => setData('status', e.target.value)}
                                >
                                    <option value="Programada">Programada</option>
                                    <option value="Em Gozo">Em Gozo</option>
                                    <option value="Concluída">Concluída</option>
                                    <option value="Cancelada">Cancelada</option>
                                </select>
                            </div>
                        </div>

                        {/* Períodos de Gozo */}
                        <div className="md:col-span-2 space-y-4">
                            <h3 className="font-semibold text-sm text-slate-700 dark:text-slate-300 border-b pb-2">Fracionamento do Gozo</h3>
                            
                            <div className="grid grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg border border-slate-100 dark:border-slate-700">
                                <div>
                                    <InputLabel value="Início do 1º Período" />
                                    <TextInput
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.gozo_1_inicio}
                                        onChange={(e) => setData('gozo_1_inicio', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Fim do 1º Período" />
                                    <TextInput
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.gozo_1_fim}
                                        onChange={(e) => setData('gozo_1_fim', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4 p-3 border border-dashed border-slate-200 dark:border-slate-700 rounded-lg">
                                <div>
                                    <InputLabel value="Início do 2º Período" />
                                    <TextInput
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.gozo_2_inicio}
                                        onChange={(e) => setData('gozo_2_inicio', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Fim do 2º Período" />
                                    <TextInput
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.gozo_2_fim}
                                        onChange={(e) => setData('gozo_2_fim', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4 p-3 border border-dashed border-slate-200 dark:border-slate-700 rounded-lg">
                                <div>
                                    <InputLabel value="Início do 3º Período" />
                                    <TextInput
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.gozo_3_inicio}
                                        onChange={(e) => setData('gozo_3_inicio', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Fim do 3º Período" />
                                    <TextInput
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.gozo_3_fim}
                                        onChange={(e) => setData('gozo_3_fim', e.target.value)}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Financeiro (Digitação Manual) */}
                        <div className="md:col-span-3 mt-4 space-y-4">
                            <h3 className="font-semibold text-sm text-slate-700 dark:text-slate-300 border-b pb-2 flex items-center gap-2">
                                <FileText className="w-4 h-4"/>
                                Valores Financeiros e Recibo (Preenchimento Manual)
                            </h3>
                            
                            <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                                <div>
                                    <InputLabel value="Valor Férias (R$)" />
                                    <TextInput
                                        type="number"
                                        step="0.01"
                                        className="mt-1 block w-full text-sm"
                                        value={data.valor_proventos}
                                        onChange={(e) => setData('valor_proventos', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <InputLabel value="1/3 Férias (R$)" />
                                    <TextInput
                                        type="number"
                                        step="0.01"
                                        className="mt-1 block w-full text-sm"
                                        value={data.valor_1_3}
                                        onChange={(e) => setData('valor_1_3', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <InputLabel value="1/3 Abono (R$)" />
                                    <TextInput
                                        type="number"
                                        step="0.01"
                                        className="mt-1 block w-full text-sm"
                                        value={data.valor_1_3_abono}
                                        onChange={(e) => setData('valor_1_3_abono', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Desc. INSS (R$)" className="text-rose-600 font-medium" />
                                    <TextInput
                                        type="number"
                                        step="0.01"
                                        className="mt-1 block w-full text-sm border-rose-300 focus:border-rose-500 focus:ring-rose-500"
                                        value={data.desconto_inss}
                                        onChange={(e) => setData('desconto_inss', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Desc. IRPF (R$)" className="text-rose-600 font-medium" />
                                    <TextInput
                                        type="number"
                                        step="0.01"
                                        className="mt-1 block w-full text-sm border-rose-300 focus:border-rose-500 focus:ring-rose-500"
                                        value={data.desconto_irpf}
                                        onChange={(e) => setData('desconto_irpf', e.target.value)}
                                    />
                                </div>
                            </div>
                        </div>

                    </div>

                    <div className="mt-8 flex justify-end gap-3 border-t pt-4 border-slate-200 dark:border-slate-700">
                        <button
                            type="button"
                            onClick={() => setShowModal(false)}
                            className="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-600 dark:hover:bg-slate-700"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-5 py-2 text-sm font-bold text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {processing ? 'Salvando...' : 'Salvar Programação'}
                        </button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
