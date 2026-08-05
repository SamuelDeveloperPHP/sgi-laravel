import ApplicationLogo from '@/Components/ApplicationLogo';
import { Head, router } from '@inertiajs/react';
import { Clock3, LogOut, ShieldCheck, XCircle } from 'lucide-react';

export default function Pending({ companyName, registrationStatus, reviewReason }) {
    const rejected = registrationStatus === 'rejected';

    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4 dark:bg-slate-900">
            <Head title="Cadastro em análise" />
            <main className="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-xl dark:border-slate-700 dark:bg-slate-800">
                <ApplicationLogo className="mx-auto mb-8" />
                <div className={`mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full ${rejected ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'}`}>
                    {rejected ? <XCircle className="h-8 w-8" aria-hidden="true" /> : <Clock3 className="h-8 w-8" aria-hidden="true" />}
                </div>
                <h1 className="text-2xl font-semibold text-slate-900 dark:text-white">
                    {rejected ? 'Pré-cadastro não aprovado' : 'Pré-cadastro em análise'}
                </h1>
                <p className="mt-3 text-slate-600 dark:text-slate-300">
                    {rejected
                        ? <>O cadastro de <strong>{companyName || 'sua empresa'}</strong> precisa de correções antes que o acesso seja liberado.</>
                        : <>Recebemos o cadastro de <strong>{companyName || 'sua empresa'}</strong>. Os módulos permanecerão bloqueados até validarmos o CNPJ.</>}
                </p>
                {rejected && reviewReason && (
                    <div className="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-left text-sm text-rose-900 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100">
                        <p className="font-semibold">Justificativa</p>
                        <p className="mt-1 whitespace-pre-wrap">{reviewReason}</p>
                    </div>
                )}
                <div className="mt-6 flex items-start gap-3 rounded-xl bg-teal-50 p-4 text-left text-sm text-teal-900 dark:bg-teal-950/40 dark:text-teal-100">
                    <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                    <p>{rejected
                        ? 'Entre em contato com o responsável pela demonstração para corrigir os dados e solicitar uma nova análise.'
                        : 'Você receberá uma confirmação no e-mail corporativo do administrador quando o acesso for liberado.'}</p>
                </div>
                <button
                    type="button"
                    onClick={() => router.post(route('logout'))}
                    className="mt-7 inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
                >
                    <LogOut className="h-4 w-4" aria-hidden="true" />
                    Sair
                </button>
            </main>
        </div>
    );
}
