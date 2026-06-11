import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { Building2, Search, Loader2, CheckCircle2, AlertCircle, LogOut } from 'lucide-react';

/**
 * Pagina de Onboarding — obrigatoria para usuarios sem company_id
 * (exceto master admin, que bypassa o middleware RequireCompany).
 *
 * Fluxo:
 *   1. Usuario digita CNPJ (com mascara visual)
 *   2. (Opcional) clica em "Buscar" para auto-preencher via ReceitaWS
 *   3. Edita/confirma nome fantasia e razao social
 *   4. Submete -> backend valida checksum, unicidade e cria a empresa
 *   5. Redireciona para /dashboard com role "Administrador da Empresa"
 *
 * Layout standalone (sem AuthenticatedLayout/Sidebar) porque sem
 * company_id a navegacao dinamica via Module ainda nao funciona.
 */
export default function CompanyOnboarding({ userName, userEmail }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        nome_fantasia: '',
        razao_social: '',
        cnpj: '',
    });

    const [lookupState, setLookupState] = useState({
        loading: false,
        success: false,
        error: null,
    });

    // Formata CNPJ enquanto digita: XX.XXX.XXX/XXXX-XX
    const formatCnpjMask = (value) => {
        const digits = String(value).replace(/\D/g, '').slice(0, 14);
        if (digits.length <= 2) return digits;
        if (digits.length <= 5) return `${digits.slice(0, 2)}.${digits.slice(2)}`;
        if (digits.length <= 8) return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5)}`;
        if (digits.length <= 12) return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8)}`;
        return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8, 12)}-${digits.slice(12)}`;
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
                    error: err.error || 'Não foi possível consultar o CNPJ. Preencha manualmente.',
                });
                return;
            }

            const lookup = await response.json();
            setData((prev) => ({
                ...prev,
                nome_fantasia: lookup.nome_fantasia || prev.nome_fantasia,
                razao_social: lookup.razao_social || prev.razao_social,
            }));
            setLookupState({ loading: false, success: true, error: null });
        } catch (e) {
            setLookupState({
                loading: false,
                success: false,
                error: 'Erro de rede ao consultar CNPJ. Preencha manualmente.',
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

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-50 to-indigo-50 dark:from-slate-900 dark:to-indigo-950 flex items-center justify-center p-4">
            <Head title="Cadastro de Empresa — Onboarding" />

            <div className="w-full max-w-2xl">
                {/* Cabecalho de boas-vindas */}
                <div className="text-center mb-8">
                    <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-100 dark:bg-indigo-900/40 mb-4">
                        <Building2 className="w-8 h-8 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <h1 className="text-3xl font-bold text-slate-900 dark:text-white">
                        Bem-vindo, {userName}!
                    </h1>
                    <p className="mt-2 text-slate-600 dark:text-slate-400">
                        Para começar a usar o sistema, precisamos cadastrar sua empresa.
                    </p>
                    <p className="text-xs text-slate-500 dark:text-slate-500 mt-1">
                        {userEmail}
                    </p>
                </div>

                {/* Card do formulario */}
                <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                        <h2 className="text-lg font-bold text-slate-900 dark:text-white">
                            Dados da Empresa
                        </h2>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Você pode buscar os dados automaticamente pelo CNPJ ou preencher manualmente.
                        </p>
                    </div>

                    <form onSubmit={submit} className="p-6 space-y-5">
                        {/* CNPJ com lookup */}
                        <div>
                            <label htmlFor="cnpj" className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                CNPJ <span className="text-red-500">*</span>
                            </label>
                            <div className="flex gap-2">
                                <input
                                    id="cnpj"
                                    type="text"
                                    value={data.cnpj}
                                    onChange={handleCnpjChange}
                                    placeholder="00.000.000/0000-00"
                                    maxLength={18}
                                    className="flex-1 rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                                    required
                                    autoFocus
                                />
                                <button
                                    type="button"
                                    onClick={handleLookup}
                                    disabled={lookupState.loading || processing}
                                    className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-400 dark:disabled:bg-slate-600 text-white rounded-lg text-sm font-medium transition-colors"
                                >
                                    {lookupState.loading ? (
                                        <>
                                            <Loader2 className="w-4 h-4 animate-spin" />
                                            Buscando...
                                        </>
                                    ) : (
                                        <>
                                            <Search className="w-4 h-4" />
                                            Buscar
                                        </>
                                    )}
                                </button>
                            </div>
                            {errors.cnpj && (
                                <p className="text-red-500 text-sm mt-1">{errors.cnpj}</p>
                            )}
                            {lookupState.success && !errors.cnpj && (
                                <p className="text-green-600 dark:text-green-400 text-sm mt-1 flex items-center gap-1">
                                    <CheckCircle2 className="w-4 h-4" />
                                    Dados preenchidos automaticamente. Revise e ajuste se necessário.
                                </p>
                            )}
                            {lookupState.error && (
                                <p className="text-amber-600 dark:text-amber-400 text-sm mt-1 flex items-center gap-1">
                                    <AlertCircle className="w-4 h-4" />
                                    {lookupState.error}
                                </p>
                            )}
                        </div>

                        {/* Nome Fantasia */}
                        <div>
                            <label htmlFor="nome_fantasia" className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Nome Fantasia <span className="text-red-500">*</span>
                            </label>
                            <input
                                id="nome_fantasia"
                                type="text"
                                value={data.nome_fantasia}
                                onChange={(e) => setData('nome_fantasia', e.target.value)}
                                placeholder="Como sua empresa é conhecida"
                                className="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                                maxLength={255}
                            />
                            {errors.nome_fantasia && (
                                <p className="text-red-500 text-sm mt-1">{errors.nome_fantasia}</p>
                            )}
                        </div>

                        {/* Razao Social */}
                        <div>
                            <label htmlFor="razao_social" className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Razão Social <span className="text-red-500">*</span>
                            </label>
                            <input
                                id="razao_social"
                                type="text"
                                value={data.razao_social}
                                onChange={(e) => setData('razao_social', e.target.value)}
                                placeholder="Nome jurídico completo"
                                className="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                                maxLength={255}
                            />
                            {errors.razao_social && (
                                <p className="text-red-500 text-sm mt-1">{errors.razao_social}</p>
                            )}
                        </div>

                        {/* Disclaimer LGPD */}
                        <div className="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-3 text-xs text-blue-900 dark:text-blue-200">
                            <strong>Privacidade:</strong> os dados públicos da Receita Federal podem ser consultados via API. Os dados que você confirmar aqui ficam vinculados à sua conta e podem ser editados depois pelo administrador.
                        </div>

                        {/* Acoes */}
                        <div className="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
                            <button
                                type="button"
                                onClick={logout}
                                className="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                            >
                                <LogOut className="w-4 h-4" />
                                Sair
                            </button>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-400 dark:disabled:bg-slate-600 text-white rounded-lg text-sm font-semibold shadow-md transition-colors"
                            >
                                {processing ? (
                                    <>
                                        <Loader2 className="w-4 h-4 animate-spin" />
                                        Cadastrando...
                                    </>
                                ) : (
                                    <>
                                        Cadastrar empresa
                                        <CheckCircle2 className="w-4 h-4" />
                                    </>
                                )}
                            </button>
                        </div>
                    </form>
                </div>

                {/* Rodape de ajuda */}
                <p className="text-center text-xs text-slate-500 dark:text-slate-500 mt-6">
                    Precisa de ajuda? Entre em contato com o suporte do SGI.
                </p>
            </div>
        </div>
    );
}
