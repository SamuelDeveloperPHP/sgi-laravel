import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import RichTextEditor from '@/Components/RichTextEditor';
import { Head, useForm } from '@inertiajs/react';
import { History, Pencil, Printer, Save, Trash2 } from 'lucide-react';

export default function Index({ auth, historia, companies, company, currentCompanyId }) {
    const userPermissions = auth.user?.permissions || [];
    const canManage = auth.user?.is_master_admin || userPermissions.includes('manage-nossa-historia');
    const [isEditing, setIsEditing] = useState(!historia?.conteudo);

    const { data, setData, post, processing, errors } = useForm({
        conteudo: historia?.conteudo || '',
        company_id: currentCompanyId || '',
    });

    const handleSave = (event) => {
        event.preventDefault();
        post(route('nossa-historia.salvar'), {
            preserveScroll: true,
            onSuccess: () => setIsEditing(false),
        });
    };

    const handleDelete = () => {
        if (window.confirm('Tem certeza que deseja excluir o conteúdo?')) {
            setData('conteudo', '');
            post(route('nossa-historia.salvar'), { preserveScroll: true });
        }
    };

    const handlePrint = () => window.print();
    const companyName = company?.razao_social || company?.nome_fantasia || 'Empresa';
    const printedAt = new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date());

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-200">Nossa História</h2>
                    {companies?.length > 0 && (
                        <div className="flex items-center gap-2">
                            <span className="text-sm text-slate-500">Empresa:</span>
                            <select
                                aria-label="Empresa"
                                className="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                value={currentCompanyId}
                                onChange={(event) => {
                                    window.location.href = route('nossa-historia.index', { company_id: event.target.value });
                                }}
                            >
                                {companies.map((item) => (
                                    <option key={item.id} value={item.id}>{item.nome_fantasia}</option>
                                ))}
                            </select>
                        </div>
                    )}
                </div>
            }
        >
            <Head title="Nossa História" />

            <style>{`
                @page { size: A4; margin: 15mm; }
                @media print {
                    html, body { background: #fff !important; color: #111827 !important; }
                    .nossa-historia-shell { padding: 0 !important; }
                    .nossa-historia-card { border: 0 !important; box-shadow: none !important; padding: 0 !important; }
                    .nossa-historia-document { width: auto !important; min-height: auto !important; margin: 0 !important; padding: 0 !important; box-shadow: none !important; }
                    .nossa-historia-document a { color: #111827 !important; text-decoration: underline; }
                }
            `}</style>

            <div className="nossa-historia-shell w-full px-4 py-8 sm:px-6 lg:px-8">
                <div className="nossa-historia-card mx-auto max-w-6xl rounded-lg bg-white p-6 shadow-sm dark:bg-slate-800">
                    <div className="mb-6 flex flex-wrap items-center justify-between gap-3 print:hidden">
                        <div className="flex items-center gap-2">
                            <History className="h-6 w-6 text-emerald-500" />
                            <h3 className="text-lg font-medium text-slate-900 dark:text-white">A História da Empresa</h3>
                        </div>

                        {!isEditing && historia?.conteudo && (
                            <div className="flex flex-wrap gap-2">
                                <button type="button" onClick={handlePrint} className="inline-flex items-center rounded-md bg-slate-700 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-slate-800">
                                    <Printer className="mr-2 h-4 w-4" />
                                    Imprimir
                                </button>
                                {canManage && (
                                    <>
                                        <button type="button" onClick={() => setIsEditing(true)} className="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 transition hover:bg-slate-50 dark:border-slate-500 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                            <Pencil className="mr-2 h-4 w-4" />
                                            Editar
                                        </button>
                                        <button type="button" onClick={handleDelete} disabled={processing} className="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-red-700 disabled:opacity-25">
                                            <Trash2 className="mr-2 h-4 w-4" />
                                            Excluir
                                        </button>
                                    </>
                                )}
                            </div>
                        )}
                    </div>

                    {isEditing ? (
                        <form onSubmit={handleSave} className="space-y-4 print:hidden">
                            <RichTextEditor
                                value={data.conteudo}
                                onChange={(html) => setData('conteudo', html)}
                            />
                            {errors.conteudo && <p className="mt-1 text-sm text-red-600">{errors.conteudo}</p>}

                            <div className="flex justify-end gap-2 pt-4">
                                {historia?.conteudo && (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setData('conteudo', historia.conteudo);
                                            setIsEditing(false);
                                        }}
                                        className="rounded-md border border-slate-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 transition hover:bg-slate-50"
                                    >
                                        Cancelar
                                    </button>
                                )}
                                <button type="submit" disabled={processing} className="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-emerald-700 disabled:opacity-25">
                                    <Save className="mr-2 h-4 w-4" />
                                    Salvar Nossa História
                                </button>
                            </div>
                        </form>
                    ) : (
                        <article className="nossa-historia-document mx-auto min-h-[297mm] w-full max-w-[210mm] bg-white px-[15mm] py-[12mm] text-slate-900 shadow-lg print:min-h-0">
                            <header className="mb-10 border-b border-slate-300 pb-5 text-center">
                                <p className="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-700">{companyName}</p>
                                <h1 className="mt-3 text-3xl font-bold text-slate-900">Nossa História</h1>
                            </header>

                            <div
                                className="rich-text-output max-w-none text-justify"
                                dangerouslySetInnerHTML={{ __html: historia?.conteudo || '<p>Nenhum conteúdo definido ainda.</p>' }}
                            />

                            <footer className="mt-16 border-t border-slate-300 pt-4 text-center text-xs text-slate-500">
                                Documento impresso em {printedAt}
                            </footer>
                        </article>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
