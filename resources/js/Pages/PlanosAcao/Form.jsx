import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Save, ArrowLeft } from 'lucide-react';

export default function Form({ auth, plano, isEdit }) {
    const { data, setData, post, put, processing, errors } = useForm({
        adms_sit_id: plano.adms_sit_id || 3, // Defaulting to 3 as legacy did
        adms_usuario_id: plano.adms_usuario_id || 1, // Will map to auth user later
        data_cad: plano.data_cad ? plano.data_cad.split('T')[0] : '',
        status: plano.status || '', // This maps to "% Concluída"
        o_q_aconteceu: plano.o_q_aconteceu || '', // "O que fazer?"
        responsaveis: plano.responsaveis || '',
        dt_prazo: plano.dt_prazo ? plano.dt_prazo.split('T')[0] : '',
        onde_ocorreu: plano.onde_ocorreu || '', // "Onde fazer?"
        porque_ocorreu: plano.porque_ocorreu || '', // "Por que?"
        como_resolver: plano.como_resolver || '', // "Como resolver?"
        custo: plano.custo || '', // "Quanto custará?"
        data_concluido: plano.data_concluido ? plano.data_concluido.split('T')[0] : '',
        observacoes: plano.observacoes || '',
    });

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route('planos-acao.update', plano.id));
        } else {
            post(route('planos-acao.store'));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                {isEdit ? 'Editar Plano de Ação' : 'Cadastrar Plano de Ação'}
            </h2>}
        >
            <Head title={isEdit ? 'Editar PA' : 'Novo PA'} />

            <div className="py-12">
                <div className="max-w-6xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700/50 overflow-hidden">
                        
                        <div className="p-6 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/50">
                            <h3 className="text-lg font-bold text-slate-900 dark:text-white">
                                Formulário (Layout Legado Mapeado)
                            </h3>
                            <div className="flex gap-4">
                                <span className="text-sm text-red-500">* Campos obrigatórios</span>
                                <Link href={route('planos-acao.index')} className="text-sm font-medium text-slate-500 hover:text-slate-700 flex items-center gap-1">
                                    <ArrowLeft className="w-4 h-4" /> Voltar
                                </Link>
                            </div>
                        </div>

                        <form onSubmit={submit} className="p-6 space-y-8">
                            
                            {/* Bloco 1: Cabecalho do PA */}
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"><span className="text-red-500">*</span> Situação</label>
                                    <select disabled className="w-full rounded-lg border-slate-300 shadow-sm bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-300 dark:border-slate-700 font-bold uppercase">
                                        <option value="3">Em Andamento</option>
                                    </select>
                                    <input type="hidden" value={data.adms_sit_id} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Autor</label>
                                    <input type="text" readOnly value="Usuário Atual (Simulado)" className="w-full rounded-lg border-slate-300 shadow-sm bg-slate-100 text-slate-500 dark:bg-slate-800 dark:border-slate-700" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Data de elaboração</label>
                                    <input type="date" value={data.data_cad} onChange={e => setData('data_cad', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">% Concluída</label>
                                    <input type="text" placeholder="Ex.: 10%" value={data.status} onChange={e => setData('status', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                </div>
                            </div>

                            {/* Bloco 2: O que fazer / Responsaveis / Prazo */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">O que fazer?</label>
                                    <textarea rows="4" placeholder="Descreva aqui o que deve ser feito" value={data.o_q_aconteceu} onChange={e => setData('o_q_aconteceu', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white"></textarea>
                                    {errors.o_q_aconteceu && <p className="text-rose-500 text-sm mt-1">{errors.o_q_aconteceu}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Responsável(eis)</label>
                                    <textarea rows="4" placeholder="Insira o(s) responsável(eis) por executar este plano de ação" value={data.responsaveis} onChange={e => setData('responsaveis', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white"></textarea>
                                    {errors.responsaveis && <p className="text-rose-500 text-sm mt-1">{errors.responsaveis}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"><span className="text-red-500">*</span> Quando será concluído:</label>
                                    <input type="date" value={data.dt_prazo} onChange={e => setData('dt_prazo', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                    {errors.dt_prazo && <p className="text-rose-500 text-sm mt-1">{errors.dt_prazo}</p>}
                                </div>
                            </div>

                            {/* Bloco 3: Onde / Por que / Como */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Onde fazer?</label>
                                    <textarea rows="4" placeholder="Descreva aqui onde será implementado a ação" value={data.onde_ocorreu} onChange={e => setData('onde_ocorreu', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white"></textarea>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"><span className="text-red-500">*</span> Por que?</label>
                                    <textarea rows="4" placeholder="Descreva aqui porque será implementado a ação" value={data.porque_ocorreu} onChange={e => setData('porque_ocorreu', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white"></textarea>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Como Resolver?</label>
                                    <textarea rows="4" placeholder="Descreva aqui como será implementado a ação" value={data.como_resolver} onChange={e => setData('como_resolver', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white"></textarea>
                                </div>
                            </div>

                            {/* Bloco 4: Custo / Conclusao / Observacoes */}
                            <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Quanto custará?</label>
                                    <input type="text" value={data.custo} onChange={e => setData('custo', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                </div>
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"><span className="text-red-500">*</span> Data da Conclusão:</label>
                                    <input type="date" value={data.data_concluido} onChange={e => setData('data_concluido', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white" />
                                </div>
                                <div className="md:col-span-8">
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"><span className="text-red-500">*</span> Observações do Plano de Ação</label>
                                    <textarea rows="4" placeholder="Insira as observaçãos do plano de ação" value={data.observacoes} onChange={e => setData('observacoes', e.target.value)} className="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-700 dark:text-white"></textarea>
                                </div>
                            </div>

                            <hr className="border-slate-200 dark:border-slate-700" />

                            <div className="flex justify-end pt-4">
                                <button type="submit" disabled={processing} className="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-6 py-2.5 rounded-xl transition-colors font-medium text-sm shadow-sm disabled:opacity-75">
                                    <Save className="w-4 h-4" /> Salvar PA
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
