import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="Verificação de E-mail" />

            <div className="mb-4 text-sm text-slate-600 dark:text-slate-400">
                Obrigado por se inscrever! Antes de começar, você poderia verificar seu endereço de e-mail clicando no link que acabamos de enviar para você? Se você não recebeu o e-mail, teremos prazer em lhe enviar outro.
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                    Um novo link de verificação foi enviado para o endereço de e-mail que você forneceu durante o registro.
                </div>
            )}

            <form onSubmit={submit}>
                <div className="mt-6 flex items-center justify-between">
                    <PrimaryButton disabled={processing}>
                        Reenviar E-mail
                    </PrimaryButton>

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="rounded-md text-sm text-slate-600 underline hover:text-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:text-slate-400 dark:hover:text-teal-400 dark:focus:ring-offset-slate-800 transition-colors"
                    >
                        Sair
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
