import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { Building2, Search, Loader2, CheckCircle2, AlertCircle, LogOut, MapPin, Phone, Mail, FileText, UserCircle } from 'lucide-react';

/**
 * Pagina de Onboarding — obrigatoria para usuarios sem company_id
 * (exceto master admin, que bypassa o middleware RequireCompany).
 *
 * Sections do formulario (similar ao cadastro de Fornecedor):
 *   1. Dados basicos (nome fantasia, razao social, CNPJ)
 *   2. Endereco completo
 *   3. Contato corporativo
 *   4. Confirmacao do administrador (auto-preenchido com user atual)
 *   5. Observacoes (opcional)
 */
export default function CompanyOnboarding({ userName, userEmail }) {
    const { data, setData, post, processing, errors } = useForm({
        // Identificacao
        nome_fantasia: '',
        razao_social: '',
        cnpj: '',
        // Endereco
        cep: '',
        logradouro: '',
        numero: '',
        complemento: '',
        bairro: '',
        cidade: '',
        estado: '',
        // Contato
        email_corporativo: '',
        telefone: '',
        dominio_corporativo: '',
        email_administrador: userEmail,
        email_recuperacao_secundario: '',
        // Observacoes
        observacoes: '',
    });

    const [lookupState, setLookupState] = useState({
        loading: false,
        success: false,
        error: null,
    });

    const formatCnpjMask = (value) => {
        const digits = String(value).replace(/\D/g, '').slice(0, 14);
        if (digits.length <= 2) return digits;
        if (digits.length <= 5) return `${digits.slice(0, 2)}.${digits.slice(2)}`;
        if (digits.length <= 8) return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5)}`;
        if (digits.length <= 12) return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8)}`;
        return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8, 12)}-${digits.slice(12)}`;
    };

    const formatCepMask = (value) => {
        const digits = String(value).replace(/\D/g, '').slice(0, 8);
        if (digits.length <= 5) return digits;
        return `${digits.slice(0, 5)}-${digits.slice(5)}`;
    };

    const formatPhoneMask = (value) => {
        const digits = String(value).replace(/\D/g, '').slice(0, 11);
        if (digits.length <= 2) return digits;
        if (digits.length <= 6) return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
        if (digits.length <= 10) return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
        return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
    };

    const handleCnpjChange = (e) => {
        setData('cnpj', formatCnpjMask(e.target.value));
        setLookupState({ loading: false, success: false, error: null });
    };

    const handleLookup = async () => {
        const cnpjDigits = data.cnpj.replace(/\D/g, '');
        if (cnpjDigits.length !== 14) {
            setLookupState({
                loading: false,
                success: false,
                error: 'Digite os 14 dígitos do CNPJ antes de buscar.',
            });
            return;
        }

        setLookupState({ loading: true, success: false, error: null });

        try {
            const response = await fetch(route('onboarding.lookup-cnpj'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ cnpj: cnpjDigits }),
            });

            if (!response.ok) {
                const err = await response.json().catch(() => ({}));
                setLookupState({
                    loading: false,
                    success: false,
                    error: err.error || 'Não foi possível consultar o CNPJ agora.',
                });
                return;
            }

            const lookup = await response.json();

            setData((prev) => ({
                ...prev,
                nome_fantasia: lookup.nome_fantasia || prev.nome_fantasia,
                razao_social: lookup.razao_social || prev.razao_social,
                email_corporativo: lookup.email || prev.email_corporativo,
                dominio_corporativo: lookup.dominio_corporativo || prev.dominio_corporativo,
                telefone: lookup.telefone ? formatPhoneMask(lookup.telefone) : prev.telefone,
                cep: lookup.cep ? formatCepMask(lookup.cep) : prev.cep,
                logradouro: lookup.logradouro || prev.logradouro,
                numero: lookup.numero || prev.numero,
                complemento: lookup.complemento || prev.complemento,
                bairro: lookup.bairro || prev.bairro,
                cidade: lookup.cidade || prev.cidade,
                estado: lookup.estado || prev.estado,
            }));
            setLookupState({ loading: false, success: true, error: null });
        } catch (e) {
            setLookupState({
                loading: false,
                success: false,
                error: 'Erro de rede ao consultar o CNPJ.',
            });
        }
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('onboarding.complete'));
    };

    const logout = () => {
        router.post(route('logout'));
    };

    // Componente reutilizavel de input com label
    const Field = ({ label, error, required, children, span = 1, hint }) => (
        <div className={`md:col-span-${span}`}>
            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                {label} {required && <span className="text-red-500">*</span>}
            </label>
            {children}
            {hint && !error && <p className="text-xs text-slate-500 mt-1">{hint}</p>}
            {error && <p className="text-red-500 text-sm mt-1">{error}</p>}
        </div>
    );

    const inputClass = "w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500";

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-50 to-indigo-50 dark:from-slate-900 dark:to-indigo-950 py-8 px-4">
            <Head title="Cadastro de Empresa — Onboarding" />

            <div className="max-w-3xl mx-auto">
                <div className="text-center mb-8">
                    <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-100 dark:bg-indigo-900/40 mb-4">
                        <Building2 className="w-8 h-8 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <h1 className="text-3xl font-bold text-slate-900 dark:text-white">
                        Bem-vindo, {userName}!
                    </h1>
                    <p className="mt-2 text-slate-600 dark:text-slate-400">
                        Para começar a usar o sistema, cadastre sua empresa.
                    </p>
                    <p className="text-xs text-slate-500 mt-1">{userEmail}</p>
                </div>

                <form onSubmit={submit} className="space-y-6">

                    {/* SEÇÃO 1: Identificação */}
                    <section className="bg-white dark:bg-slate-800 rounded-2xl shadow border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <header className="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 flex items-center gap-2">
                            <FileText className="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                            <h2 className="text-lg font-bold text-slate-900 dark:text-white">
                                Identificação
                            </h2>
                        </header>
                        <div className="p-6 space-y-4">
                            <Field label="CNPJ" required error={errors.cnpj}
                                   hint="Use o botão 'Buscar' para preencher automaticamente os dados pela APIBrasil.">
                                <div className="flex gap-2">
                                    <input
                                        type="text"
                                        value={data.cnpj}
                                        onChange={handleCnpjChange}
                                        placeholder="00.000.000/0000-00"
                                        maxLength={18}
                                        className={`${inputClass} font-mono flex-1`}
                                        required
                                        autoFocus
                                    />
                                    <button
                                        type="button"
                                        onClick={handleLookup}
                                        disabled={lookupState.loading || processing}
                                        className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-400 text-white rounded-lg text-sm font-medium"
                                    >
                                        {lookupState.loading ? (
                                            <><Loader2 className="w-4 h-4 animate-spin" /> Buscando...</>
                                        ) : (
                                            <><Search className="w-4 h-4" /> Buscar</>
                                        )}
                                    </button>
                                </div>
                                {lookupState.success && (
                                    <p className="text-green-600 dark:text-green-400 text-sm mt-1 flex items-center gap-1">
                                        <CheckCircle2 className="w-4 h-4" /> Dados pré-preenchidos. Revise antes de enviar.
                                    </p>
                                )}
                                {lookupState.error && (
                                    <p className="text-amber-600 dark:text-amber-400 text-sm mt-1 flex items-center gap-1">
                                        <AlertCircle className="w-4 h-4" /> {lookupState.error}
                                    </p>
                                )}
                            </Field>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <Field label="Nome Fantasia" required error={errors.nome_fantasia}>
                                    <input type="text" value={data.nome_fantasia}
                                           onChange={(e) => setData('nome_fantasia', e.target.value)}
                                           placeholder="Como sua empresa é conhecida"
                                           className={inputClass} required maxLength={255} />
                                </Field>
                                <Field label="Razão Social" required error={errors.razao_social}>
                                    <input type="text" value={data.razao_social}
                                           onChange={(e) => setData('razao_social', e.target.value)}
                                           placeholder="Nome jurídico completo"
                                           className={inputClass} required maxLength={255} />
                                </Field>
                            </div>
                        </div>
                    </section>

                    {/* SEÇÃO 2: Endereço */}
                    <section className="bg-white dark:bg-slate-800 rounded-2xl shadow border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <header className="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 flex items-center gap-2">
                            <MapPin className="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                            <h2 className="text-lg font-bold text-slate-900 dark:text-white">
                                Endereço
                            </h2>
                            <span className="text-xs text-slate-500 ml-auto">Opcional</span>
                        </header>
                        <div className="p-6 grid grid-cols-1 md:grid-cols-6 gap-4">
                            <div className="md:col-span-2">
                                <Field label="CEP" error={errors.cep}>
                                    <input type="text" value={data.cep}
                                           onChange={(e) => setData('cep', formatCepMask(e.target.value))}
                                           placeholder="00000-000" maxLength={9}
                                           className={`${inputClass} font-mono`} />
                                </Field>
                            </div>
                            <div className="md:col-span-4">
                                <Field label="Logradouro" error={errors.logradouro}>
                                    <input type="text" value={data.logradouro}
                                           onChange={(e) => setData('logradouro', e.target.value)}
                                           placeholder="Rua, Av., Travessa..."
                                           className={inputClass} maxLength={255} />
                                </Field>
                            </div>
                            <div className="md:col-span-2">
                                <Field label="Número" error={errors.numero}>
                                    <input type="text" value={data.numero}
                                           onChange={(e) => setData('numero', e.target.value)}
                                           className={inputClass} maxLength={20} />
                                </Field>
                            </div>
                            <div className="md:col-span-4">
                                <Field label="Complemento" error={errors.complemento}>
                                    <input type="text" value={data.complemento}
                                           onChange={(e) => setData('complemento', e.target.value)}
                                           placeholder="Sala, andar, bloco..."
                                           className={inputClass} maxLength={255} />
                                </Field>
                            </div>
                            <div className="md:col-span-3">
                                <Field label="Bairro" error={errors.bairro}>
                                    <input type="text" value={data.bairro}
                                           onChange={(e) => setData('bairro', e.target.value)}
                                           className={inputClass} maxLength={255} />
                                </Field>
                            </div>
                            <div className="md:col-span-2">
                                <Field label="Cidade" error={errors.cidade}>
                                    <input type="text" value={data.cidade}
                                           onChange={(e) => setData('cidade', e.target.value)}
                                           className={inputClass} maxLength={255} />
                                </Field>
                            </div>
                            <div className="md:col-span-1">
                                <Field label="UF" error={errors.estado}>
                                    <input type="text" value={data.estado}
                                           onChange={(e) => setData('estado', e.target.value.toUpperCase())}
                                           placeholder="SP" maxLength={2}
                                           className={`${inputClass} uppercase`} />
                                </Field>
                            </div>
                        </div>
                    </section>

                    {/* SEÇÃO 3: Contato corporativo */}
                    <section className="bg-white dark:bg-slate-800 rounded-2xl shadow border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <header className="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 flex items-center gap-2">
                            <Phone className="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                            <h2 className="text-lg font-bold text-slate-900 dark:text-white">
                                Contato Corporativo
                            </h2>
                        </header>
                        <div className="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <Field label="Domínio corporativo" required error={errors.dominio_corporativo}
                                   hint="Exemplo: engetecnica.com.br. Todos os e-mails devem usar exatamente este domínio.">
                                <input type="text" value={data.dominio_corporativo}
                                       onChange={(e) => setData('dominio_corporativo', e.target.value)}
                                       placeholder="empresa.com.br"
                                       className={inputClass} maxLength={253} required />
                            </Field>
                            <Field label="E-mail corporativo" required error={errors.email_corporativo}>
                                <input type="email" value={data.email_corporativo}
                                       onChange={(e) => setData('email_corporativo', e.target.value)}
                                       placeholder="contato@empresa.com.br"
                                       className={inputClass} maxLength={255} required />
                            </Field>
                            <Field label="Telefone" error={errors.telefone}>
                                <input type="text" value={data.telefone}
                                       onChange={(e) => setData('telefone', formatPhoneMask(e.target.value))}
                                       placeholder="(00) 00000-0000"
                                       className={`${inputClass} font-mono`} maxLength={16} />
                            </Field>
                        </div>
                    </section>

                    {/* SEÇÃO 4: Administrador (read-only, vem do usuário logado) */}
                    <section className="bg-white dark:bg-slate-800 rounded-2xl shadow border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <header className="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 flex items-center gap-2">
                            <UserCircle className="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                            <h2 className="text-lg font-bold text-slate-900 dark:text-white">
                                Administrador do Sistema
                            </h2>
                        </header>
                        <div className="p-6 space-y-4 text-sm text-slate-700 dark:text-slate-300">
                            <p>
                                Você será o <strong>Administrador</strong> desta empresa no sistema. Após o cadastro, você poderá criar outros usuários e atribuir permissões.
                            </p>
                            <div className="bg-slate-50 dark:bg-slate-900 rounded-lg p-3 grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                                <div><span className="text-slate-500">Nome:</span> <strong>{userName}</strong></div>
                                <div><span className="text-slate-500">E-mail:</span> <strong>{userEmail}</strong></div>
                            </div>
                            <input type="hidden" name="email_administrador" value={data.email_administrador} />
                            {errors.email_administrador && <p className="text-red-500 text-sm">{errors.email_administrador}</p>}
                            <Field label="Segundo e-mail de recuperação" required error={errors.email_recuperacao_secundario}
                                   hint="Deve ser diferente do administrador e usar o mesmo domínio da empresa.">
                                <input type="email" value={data.email_recuperacao_secundario}
                                       onChange={(e) => setData('email_recuperacao_secundario', e.target.value)}
                                       placeholder="recuperacao@empresa.com.br"
                                       className={inputClass} maxLength={255} required />
                            </Field>
                        </div>
                    </section>

                    {/* SEÇÃO 5: Observações */}
                    <section className="bg-white dark:bg-slate-800 rounded-2xl shadow border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <header className="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                            <h2 className="text-lg font-bold text-slate-900 dark:text-white">
                                Observações
                                <span className="text-xs font-normal text-slate-500 ml-2">Opcional</span>
                            </h2>
                        </header>
                        <div className="p-6">
                            <Field label="Notas adicionais" error={errors.observacoes}>
                                <textarea value={data.observacoes}
                                          onChange={(e) => setData('observacoes', e.target.value)}
                                          rows={3}
                                          maxLength={5000}
                                          placeholder="Qualquer informação adicional sobre a empresa"
                                          className={inputClass} />
                            </Field>
                        </div>
                    </section>

                    {/* Disclaimer LGPD */}
                    <div className="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4 text-sm text-blue-900 dark:text-blue-200 flex items-start gap-3">
                        <Mail className="w-5 h-5 mt-0.5 flex-shrink-0" />
                        <div>
                            <strong>Privacidade (LGPD):</strong> os dados públicos do CNPJ são consultados pela APIBrasil. Os dados confirmados ficam vinculados somente à empresa cadastrada.
                        </div>
                    </div>

                    {/* Ações */}
                    <div className="flex items-center justify-between pt-2 pb-8">
                        <button type="button" onClick={logout}
                                className="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                            <LogOut className="w-4 h-4" /> Sair
                        </button>
                        <button type="submit" disabled={processing}
                                className="inline-flex items-center gap-2 px-8 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-400 text-white rounded-lg text-sm font-semibold shadow-md">
                            {processing ? (
                                <><Loader2 className="w-4 h-4 animate-spin" /> Cadastrando...</>
                            ) : (
                                <>Cadastrar empresa <CheckCircle2 className="w-4 h-4" /></>
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
