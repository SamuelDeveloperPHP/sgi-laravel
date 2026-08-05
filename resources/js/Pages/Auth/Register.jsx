import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Building2, CheckCircle2, Loader2, Search, ShieldCheck } from 'lucide-react';
import { useState } from 'react';

const formatCnpj = (value) => {
    const digits = String(value).replace(/\D/g, '').slice(0, 14);
    return digits
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d)/, '$1-$2');
};

const formatCep = (value) => String(value).replace(/\D/g, '').slice(0, 8).replace(/(\d{5})(\d)/, '$1-$2');

function Field({ id, label, error, required = false, hint, children }) {
    return (
        <div>
            <InputLabel htmlFor={id} value={`${label}${required ? ' *' : ''}`} />
            {children}
            <InputError message={error} className="mt-1" />
            {hint && !error && <p className="mt-1 text-xs text-slate-500">{hint}</p>}
        </div>
    );
}

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        registration_type: 'company',
        cnpj: '', nome_fantasia: '', razao_social: '',
        cep: '', logradouro: '', numero: '', complemento: '', bairro: '', cidade: '', estado: '',
        telefone: '', dominio_corporativo: '', email_corporativo: '',
        name: '', email: '', email_recuperacao_secundario: '',
        password: '', password_confirmation: '',
    });
    const [lookup, setLookup] = useState({ loading: false, success: false, message: '' });

    const inputClass = 'mt-1 block w-full';

    const lookupCnpj = async () => {
        const cnpj = data.cnpj.replace(/\D/g, '');
        if (cnpj.length !== 14) {
            setLookup({ loading: false, success: false, message: 'Informe os 14 dígitos do CNPJ. CPF não é aceito.' });
            return;
        }

        setLookup({ loading: true, success: false, message: '' });
        try {
            const response = await fetch(route('register.lookup-cnpj'), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ cnpj }),
            });
            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                setLookup({ loading: false, success: false, message: result.error || 'Não foi possível consultar agora. Preencha manualmente.' });
                return;
            }

            setData((current) => ({
                ...current,
                nome_fantasia: result.nome_fantasia || current.nome_fantasia,
                razao_social: result.razao_social || current.razao_social,
                cep: result.cep ? formatCep(result.cep) : current.cep,
                logradouro: result.logradouro || current.logradouro,
                numero: result.numero || current.numero,
                complemento: result.complemento || current.complemento,
                bairro: result.bairro || current.bairro,
                cidade: result.cidade || current.cidade,
                estado: result.estado || current.estado,
                telefone: result.telefone || current.telefone,
                email_corporativo: result.email || current.email_corporativo,
                dominio_corporativo: result.dominio_corporativo || current.dominio_corporativo,
            }));
            setLookup({ loading: false, success: true, message: 'CNPJ localizado. Confirme os dados preenchidos.' });
        } catch {
            setLookup({ loading: false, success: false, message: 'Consulta indisponível. Preencha manualmente; o cadastro ficará em análise.' });
        }
    };

    const submit = (event) => {
        event.preventDefault();
        post(route('register'), { onFinish: () => reset('password', 'password_confirmation') });
    };

    return (
        <GuestLayout wide>
            <Head title="Pré-cadastro da empresa" />

            <div className="mb-7 text-center">
                <h1 className="text-2xl font-semibold text-slate-900 dark:text-white">Pré-cadastro da empresa</h1>
                <p className="mt-2 text-sm text-slate-600 dark:text-slate-300">Informe o CNPJ e os contatos corporativos para acessar a demonstração.</p>
            </div>

            <form onSubmit={submit} className="space-y-8">
                <section className="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                    <InputLabel value="Tipo de cadastro" />
                    <div className="mt-3 grid gap-3 md:grid-cols-2">
                        <button
                            type="button"
                            onClick={() => setData('registration_type', 'company')}
                            className={`rounded-lg border px-4 py-3 text-left text-sm transition ${data.registration_type === 'company' ? 'border-teal-500 bg-teal-50 text-teal-900' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'}`}
                        >
                            <span className="block font-semibold">Empresa com CNPJ</span>
                            <span className="mt-1 block text-xs">Valida dominio corporativo e cria o administrador da empresa.</span>
                        </button>
                        <button
                            type="button"
                            onClick={() => setData('registration_type', 'public')}
                            className={`rounded-lg border px-4 py-3 text-left text-sm transition ${data.registration_type === 'public' ? 'border-teal-500 bg-teal-50 text-teal-900' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'}`}
                        >
                            <span className="block font-semibold">Acesso publico temporario</span>
                            <span className="mt-1 block text-xs">Permite e-mail pessoal e dispensa CNPJ.</span>
                        </button>
                    </div>
                    <InputError message={errors.registration_type} className="mt-2" />
                </section>

                {data.registration_type === 'company' && <section>
                    <h2 className="mb-4 flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-100"><Building2 className="h-5 w-5 text-teal-600" /> Empresa</h2>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="md:col-span-2">
                            <InputLabel htmlFor="cnpj" value="CNPJ *" />
                            <div className="mt-1 flex gap-2">
                                <TextInput id="cnpj" value={data.cnpj} className="block w-full" inputMode="numeric" autoComplete="off" onChange={(e) => { setData('cnpj', formatCnpj(e.target.value)); setLookup({ loading: false, success: false, message: '' }); }} required />
                                <button type="button" onClick={lookupCnpj} disabled={lookup.loading} className="inline-flex min-w-32 items-center justify-center gap-2 rounded-lg bg-slate-800 px-4 text-sm font-medium text-white transition hover:bg-slate-700 disabled:opacity-60">
                                    {lookup.loading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Search className="h-4 w-4" />} Buscar
                                </button>
                            </div>
                            <InputError message={errors.cnpj} className="mt-1" />
                            {lookup.message && <p className={`mt-2 flex items-center gap-1 text-sm ${lookup.success ? 'text-emerald-700' : 'text-amber-700'}`}>{lookup.success && <CheckCircle2 className="h-4 w-4" />}{lookup.message}</p>}
                        </div>
                        <Field id="nome_fantasia" label="Nome fantasia" error={errors.nome_fantasia} required><TextInput id="nome_fantasia" value={data.nome_fantasia} className={inputClass} onChange={(e) => setData('nome_fantasia', e.target.value)} required /></Field>
                        <Field id="razao_social" label="Razão social" error={errors.razao_social} required><TextInput id="razao_social" value={data.razao_social} className={inputClass} onChange={(e) => setData('razao_social', e.target.value)} required /></Field>
                    </div>
                </section>}

                {data.registration_type === 'company' && <section>
                    <h2 className="mb-4 font-semibold text-slate-800 dark:text-slate-100">Endereço da empresa</h2>
                    <div className="grid gap-4 md:grid-cols-3">
                        <Field id="cep" label="CEP" error={errors.cep}><TextInput id="cep" value={data.cep} className={inputClass} inputMode="numeric" onChange={(e) => setData('cep', formatCep(e.target.value))} /></Field>
                        <div className="md:col-span-2"><Field id="logradouro" label="Logradouro" error={errors.logradouro}><TextInput id="logradouro" value={data.logradouro} className={inputClass} onChange={(e) => setData('logradouro', e.target.value)} /></Field></div>
                        <Field id="numero" label="Número" error={errors.numero}><TextInput id="numero" value={data.numero} className={inputClass} onChange={(e) => setData('numero', e.target.value)} /></Field>
                        <Field id="complemento" label="Complemento" error={errors.complemento}><TextInput id="complemento" value={data.complemento} className={inputClass} onChange={(e) => setData('complemento', e.target.value)} /></Field>
                        <Field id="bairro" label="Bairro" error={errors.bairro}><TextInput id="bairro" value={data.bairro} className={inputClass} onChange={(e) => setData('bairro', e.target.value)} /></Field>
                        <div className="md:col-span-2"><Field id="cidade" label="Cidade" error={errors.cidade}><TextInput id="cidade" value={data.cidade} className={inputClass} onChange={(e) => setData('cidade', e.target.value)} /></Field></div>
                        <Field id="estado" label="UF" error={errors.estado}><TextInput id="estado" value={data.estado} maxLength={2} className={inputClass} onChange={(e) => setData('estado', e.target.value.toUpperCase())} /></Field>
                    </div>
                </section>}

                <section>
                    <h2 className="mb-4 flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-100"><ShieldCheck className="h-5 w-5 text-teal-600" /> Contatos e administrador</h2>
                    <div className="grid gap-4 md:grid-cols-2">
                        {data.registration_type === 'company' ? <>
                        <Field id="dominio_corporativo" label="Domínio oficial da empresa" error={errors.dominio_corporativo} required hint="Exemplo: engetecnica.com.br"><TextInput id="dominio_corporativo" value={data.dominio_corporativo} className={inputClass} placeholder="empresa.com.br" onChange={(e) => setData('dominio_corporativo', e.target.value)} required /></Field>
                        <Field id="telefone" label="Telefone" error={errors.telefone}><TextInput id="telefone" value={data.telefone} className={inputClass} onChange={(e) => setData('telefone', e.target.value)} /></Field>
                        <Field id="email_corporativo" label="E-mail geral da empresa" error={errors.email_corporativo} required><TextInput id="email_corporativo" type="email" value={data.email_corporativo} className={inputClass} onChange={(e) => setData('email_corporativo', e.target.value)} required /></Field>
                        <Field id="name" label="Nome do administrador" error={errors.name} required><TextInput id="name" value={data.name} className={inputClass} autoComplete="name" onChange={(e) => setData('name', e.target.value)} required /></Field>
                        <Field id="email" label="E-mail do administrador e recuperação principal" error={errors.email} required hint="Deve pertencer exatamente ao domínio oficial informado."><TextInput id="email" type="email" value={data.email} className={inputClass} autoComplete="username" onChange={(e) => setData('email', e.target.value)} required /></Field>
                        <Field id="email_recuperacao_secundario" label="Segundo e-mail de recuperação" error={errors.email_recuperacao_secundario} required hint="Deve ser corporativo e diferente do administrador."><TextInput id="email_recuperacao_secundario" type="email" value={data.email_recuperacao_secundario} className={inputClass} onChange={(e) => setData('email_recuperacao_secundario', e.target.value)} required /></Field>
                        </> : <>
                        <Field id="name" label="Nome" error={errors.name} required><TextInput id="name" value={data.name} className={inputClass} autoComplete="name" onChange={(e) => setData('name', e.target.value)} required /></Field>
                        <Field id="email" label="E-mail" error={errors.email} required hint="Pode ser e-mail pessoal. O acesso sera temporario."><TextInput id="email" type="email" value={data.email} className={inputClass} autoComplete="username" onChange={(e) => setData('email', e.target.value)} required /></Field>
                        </>}
                        <Field id="password" label="Senha" error={errors.password} required><TextInput id="password" type="password" value={data.password} className={inputClass} autoComplete="new-password" onChange={(e) => setData('password', e.target.value)} required /></Field>
                        <Field id="password_confirmation" label="Confirmar senha" error={errors.password_confirmation} required><TextInput id="password_confirmation" type="password" value={data.password_confirmation} className={inputClass} autoComplete="new-password" onChange={(e) => setData('password_confirmation', e.target.value)} required /></Field>
                    </div>
                </section>

                <div className="flex flex-col-reverse items-center justify-between gap-4 border-t border-slate-200 pt-6 sm:flex-row dark:border-slate-700">
                    <Link href={route('login')} className="text-sm text-slate-600 underline transition hover:text-teal-600 dark:text-slate-300">Já possui conta?</Link>
                    <PrimaryButton disabled={processing}>{processing ? 'Enviando...' : 'Enviar pré-cadastro'}</PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
