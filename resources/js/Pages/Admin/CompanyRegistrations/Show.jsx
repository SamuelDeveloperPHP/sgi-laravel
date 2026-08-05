import InputError from '@/Components/InputError';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Building2, CheckCircle2, Clock3, MapPin, ShieldAlert, UserCircle, XCircle } from 'lucide-react';

const formatCnpj = (value) => String(value || '').replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
const formatDate = (value) => value ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '-';

function Detail({ label, value }) {
    return <div><dt className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</dt><dd className="mt-1 break-words text-sm text-slate-900 dark:text-slate-100">{value || '-'}</dd></div>;
}

export default function Show({ auth, company }) {
    const { data, setData, post, processing, errors } = useForm({ reason: '' });
    const pending = company.registration_status === 'pending';

    const approve = () => {
        if (confirm(`Aprovar o acesso da empresa ${company.nome_fantasia}?`)) {
            post(route('admin.company-registrations.approve', company.id));
        }
    };

    const reject = () => {
        if (!data.reason.trim()) return;
        if (confirm(`Rejeitar o pré-cadastro da empresa ${company.nome_fantasia}?`)) {
            post(route('admin.company-registrations.reject', company.id));
        }
    };

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="text-xl font-semibold text-slate-800 dark:text-slate-200">Análise de pré-cadastro</h2>}>
            <Head title={`Analisar ${company.nome_fantasia}`} />

            <div className="space-y-5 sm:px-6 lg:px-8">
                <Link href={route('admin.company-registrations.index')} className="inline-flex items-center gap-2 text-sm text-slate-600 transition hover:text-teal-600 dark:text-slate-300"><ArrowLeft className="h-4 w-4" /> Voltar às pendências</Link>

                <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                        <div className="flex gap-4"><div className="rounded-xl bg-teal-50 p-3 text-teal-700 dark:bg-teal-950 dark:text-teal-300"><Building2 className="h-7 w-7" /></div><div><h1 className="text-2xl font-semibold text-slate-900 dark:text-white">{company.nome_fantasia}</h1><p className="text-slate-500">{company.razao_social}</p></div></div>
                        <span className={`inline-flex items-center gap-2 self-start rounded-full px-3 py-1 text-sm font-medium ${pending ? 'bg-amber-100 text-amber-800' : company.registration_status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'}`}>{pending ? <Clock3 className="h-4 w-4" /> : company.registration_status === 'approved' ? <CheckCircle2 className="h-4 w-4" /> : <XCircle className="h-4 w-4" />}{pending ? 'Pendente' : company.registration_status === 'approved' ? 'Aprovado' : 'Rejeitado'}</span>
                    </div>

                    <dl className="mt-7 grid gap-5 border-t border-slate-200 pt-6 sm:grid-cols-2 lg:grid-cols-4 dark:border-slate-700">
                        <Detail label="CNPJ" value={formatCnpj(company.cnpj)} />
                        <Detail label="Domínio corporativo" value={company.dominio_corporativo} />
                        <Detail label="E-mail oficial" value={company.email_corporativo} />
                        <Detail label="Consulta do CNPJ" value={company.cnpj_verificado_em ? `Confirmada em ${formatDate(company.cnpj_verificado_em)}` : 'Não confirmada automaticamente'} />
                    </dl>
                </section>

                <div className="grid gap-5 lg:grid-cols-2">
                    <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <h2 className="flex items-center gap-2 font-semibold text-slate-900 dark:text-white"><UserCircle className="h-5 w-5 text-teal-600" /> Administrador e recuperação</h2>
                        <dl className="mt-5 grid gap-5 sm:grid-cols-2"><Detail label="Administrador" value={company.nome_administrador} /><Detail label="E-mail do administrador" value={company.email_administrador} /><Detail label="Recuperação secundária" value={company.email_recuperacao_secundario} /><Detail label="Conta verificada" value={company.users?.[0]?.email_verified_at ? `Sim, em ${formatDate(company.users[0].email_verified_at)}` : 'Ainda não'} /></dl>
                    </section>
                    <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <h2 className="flex items-center gap-2 font-semibold text-slate-900 dark:text-white"><MapPin className="h-5 w-5 text-teal-600" /> Endereço e contato</h2>
                        <dl className="mt-5 grid gap-5 sm:grid-cols-2"><Detail label="Endereço" value={[company.logradouro, company.numero, company.complemento].filter(Boolean).join(', ')} /><Detail label="Bairro" value={company.bairro} /><Detail label="Cidade/UF" value={[company.cidade, company.estado].filter(Boolean).join(' / ')} /><Detail label="CEP" value={company.cep} /><Detail label="Telefone" value={company.telefone} /><Detail label="Criado em" value={formatDate(company.created_at)} /></dl>
                    </section>
                </div>

                {pending && (
                    <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <h2 className="flex items-center gap-2 font-semibold text-slate-900 dark:text-white"><ShieldAlert className="h-5 w-5 text-amber-600" /> Decisão do Administrador Master</h2>
                        <p className="mt-2 text-sm text-slate-600 dark:text-slate-300">A justificativa é obrigatória para rejeitar e ficará visível para o administrador da empresa.</p>
                        <textarea value={data.reason} onChange={(event) => setData('reason', event.target.value)} rows="4" maxLength="2000" placeholder="Registre a justificativa ou observações da análise" className="mt-4 w-full rounded-lg border-slate-300 focus:border-teal-500 focus:ring-teal-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                        <InputError message={errors.reason || errors.company} className="mt-2" />
                        <div className="mt-5 flex flex-col justify-end gap-3 sm:flex-row">
                            <button type="button" onClick={reject} disabled={processing || !data.reason.trim()} className="inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-5 py-2.5 font-medium text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"><XCircle className="h-5 w-5" /> Rejeitar</button>
                            <button type="button" onClick={approve} disabled={processing} className="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 font-medium text-white transition hover:bg-emerald-700 disabled:opacity-50"><CheckCircle2 className="h-5 w-5" /> Aprovar e liberar</button>
                        </div>
                    </section>
                )}

                {company.registration_reviews?.length > 0 && (
                    <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800"><h2 className="font-semibold text-slate-900 dark:text-white">Histórico de análise</h2><div className="mt-4 space-y-3">{company.registration_reviews.map((review) => <div key={review.id} className="rounded-lg border border-slate-200 p-4 text-sm dark:border-slate-700"><div className="flex flex-wrap justify-between gap-2"><span className="font-medium text-slate-900 dark:text-white">{review.decision === 'approved' ? 'Aprovado' : 'Rejeitado'} por {review.reviewer?.name || 'Administrador removido'}</span><span className="text-slate-500">{formatDate(review.created_at)}</span></div>{review.reason && <p className="mt-2 whitespace-pre-wrap text-slate-600 dark:text-slate-300">{review.reason}</p>}</div>)}</div></section>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
