import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { Save, ArrowLeft, Plus, Trash2, Upload } from 'lucide-react';
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css';

export default function Form({ auth, nc, isEdit }) {
    // Default structures
    const defaultDadosOrigem = {
        origem: 'Interna', // Interna, Fornecedor, Cliente, Auditoria, Outro
        area: '',
        data_ocorrencia: '',
        n_reclamacao: '',
        contato: '',
        processo_produto: ''
    };

    const defaultCincoPorques = {
        porques: [
            { id: 1, p: '1º Por quê', r: '' },
            { id: 2, p: '2º Por quê', r: '' },
            { id: 3, p: '3º Por quê', r: '' },
            { id: 4, p: '4º Por quê', r: '' },
            { id: 5, p: '5º Por quê', r: '' }
        ],
        causa_raiz: ''
    };

    const { data, setData, post, processing, errors } = useForm({
        _method: isEdit ? 'put' : 'post',
        dados_origem: nc.dados_origem || defaultDadosOrigem,
        descOcorrencia: nc.descOcorrencia || '',
        acao_contencao_grid: nc.acao_contencao_grid || [],
        cinco_porques: nc.cinco_porques || defaultCincoPorques,
        plano_acao_grid: nc.plano_acao_grid || [],
        evidencias: nc.evidencias || [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        const routeName = isEdit ? route('nao-conformidades.update', nc.id) : route('nao-conformidades.store');
        
        post(routeName, {
            forceFormData: true,
        });
    };

    // Helpers for Arrays
    const addContencao = () => setData('acao_contencao_grid', [...data.acao_contencao_grid, { acao: '', responsavel: '', prazo: '' }]);
    const removeContencao = (index) => {
        const arr = [...data.acao_contencao_grid];
        arr.splice(index, 1);
        setData('acao_contencao_grid', arr);
    };

    const addPlanoAcao = () => setData('plano_acao_grid', [...data.plano_acao_grid, { acao: '', responsavel: '', prazo: '' }]);
    const removePlanoAcao = (index) => {
        const arr = [...data.plano_acao_grid];
        arr.splice(index, 1);
        setData('plano_acao_grid', arr);
    };

    const addEvidencia = () => setData('evidencias', [...data.evidencias, { foto: null, descricao: '', url_existente: null }]);
    const removeEvidencia = (index) => {
        const arr = [...data.evidencias];
        arr.splice(index, 1);
        setData('evidencias', arr);
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                {isEdit ? `Editar RNC #${nc.id}` : 'Nova Não Conformidade'}
            </h2>}
        >
            <Head title={isEdit ? 'Editar RNC' : 'Nova RNC'} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit} className="bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg overflow-hidden">
                        
                        {/* HEADER ACTIONS */}
                        <div className="p-6 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
                            <Link 
                                href={route('nao-conformidades.index')}
                                className="flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 transition-colors"
                            >
                                <ArrowLeft size={20} /> Voltar
                            </Link>
                            <button 
                                type="submit" 
                                disabled={processing}
                                className="flex items-center gap-2 px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-medium transition-colors disabled:opacity-50"
                            >
                                <Save size={20} /> Salvar RNC
                            </button>
                        </div>

                        <div className="p-8 space-y-12">
                            
                            {/* SEÇÃO 1: DADOS */}
                            <section>
                                <h3 className="text-lg font-bold text-orange-600 dark:text-orange-500 uppercase border-b-2 border-orange-500 pb-2 mb-6">1. Dados da Não Conformidade</h3>
                                
                                <div className="grid grid-cols-1 md:grid-cols-5 gap-6 mb-4">
                                    <div className="col-span-5 md:col-span-2">
                                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Origem</label>
                                        <select 
                                            value={data.dados_origem.origem} 
                                            onChange={e => setData('dados_origem', {...data.dados_origem, origem: e.target.value})}
                                            className="w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 rounded-md"
                                        >
                                            <option value="Interna">Interna</option>
                                            <option value="Fornecedor">Fornecedor</option>
                                            <option value="Cliente">Cliente</option>
                                            <option value="Auditoria">Auditoria</option>
                                            <option value="Outro">Outro</option>
                                        </select>
                                    </div>
                                    <div className="col-span-5 md:col-span-2">
                                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Área / Fornecedor / Cliente</label>
                                        <input 
                                            type="text" 
                                            value={data.dados_origem.area} 
                                            onChange={e => setData('dados_origem', {...data.dados_origem, area: e.target.value})}
                                            className="w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 rounded-md"
                                        />
                                    </div>
                                    <div className="col-span-5 md:col-span-1">
                                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Data Ocorrência</label>
                                        <input 
                                            type="date" 
                                            value={data.dados_origem.data_ocorrencia} 
                                            onChange={e => setData('dados_origem', {...data.dados_origem, data_ocorrencia: e.target.value})}
                                            className="w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 rounded-md"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-4">
                                    <div className="col-span-1">
                                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nº Reclamação Cliente</label>
                                        <input 
                                            type="text" 
                                            value={data.dados_origem.n_reclamacao} 
                                            onChange={e => setData('dados_origem', {...data.dados_origem, n_reclamacao: e.target.value})}
                                            className="w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 rounded-md"
                                        />
                                    </div>
                                    <div className="col-span-1">
                                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Contato</label>
                                        <input 
                                            type="text" 
                                            value={data.dados_origem.contato} 
                                            onChange={e => setData('dados_origem', {...data.dados_origem, contato: e.target.value})}
                                            className="w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 rounded-md"
                                        />
                                    </div>
                                    <div className="col-span-2">
                                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Processo / Produto</label>
                                        <input 
                                            type="text" 
                                            value={data.dados_origem.processo_produto} 
                                            onChange={e => setData('dados_origem', {...data.dados_origem, processo_produto: e.target.value})}
                                            className="w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 rounded-md"
                                        />
                                    </div>
                                </div>
                            </section>

                            {/* SEÇÃO 2: DESCRIÇÃO */}
                            <section>
                                <h3 className="text-lg font-bold text-orange-600 dark:text-orange-500 uppercase border-b-2 border-orange-500 pb-2 mb-6">2. Descrição Detalhada da Não Conformidade ou Melhoria</h3>
                                <div className="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-md overflow-hidden">
                                    <ReactQuill
                                        theme="snow"
                                        value={data.descOcorrencia}
                                        onChange={(content) => setData('descOcorrencia', content)}
                                        className="h-64 mb-12"
                                    />
                                    <style jsx="true">{`
                                        .quill { height: 100%; display: flex; flex-direction: column; }
                                        .ql-container { flex-grow: 1; min-height: 250px; font-family: inherit; font-size: 1rem; }
                                        .dark .ql-toolbar { background-color: #334155; border-color: #475569; }
                                        .dark .ql-container { border-color: #475569; color: #f8fafc; }
                                        .dark .ql-editor.ql-blank::before { color: #94a3b8; }
                                    `}</style>
                                </div>
                            </section>

                            {/* SEÇÃO 3: CONTENÇÃO */}
                            <section>
                                <h3 className="text-lg font-bold text-orange-600 dark:text-orange-500 uppercase border-b-2 border-orange-500 pb-2 mb-6 flex justify-between items-center">
                                    <span>3. Ação de Contenção ou Melhoria</span>
                                    <button type="button" onClick={addContencao} className="text-sm bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 px-3 py-1 rounded flex items-center gap-1 hover:bg-orange-200 transition">
                                        <Plus size={16}/> Adicionar Linha
                                    </button>
                                </h3>
                                
                                <div className="overflow-x-auto border border-slate-300 dark:border-slate-700 rounded-md">
                                    <table className="min-w-full divide-y divide-slate-300 dark:divide-slate-700">
                                        <thead className="bg-slate-100 dark:bg-slate-800">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Ação</th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase w-48">Responsável</th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase w-40">Prazo</th>
                                                <th className="px-4 py-3 w-16"></th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-300 dark:divide-slate-700">
                                            {data.acao_contencao_grid.map((row, i) => (
                                                <tr key={i}>
                                                    <td className="p-2">
                                                        <input type="text" value={row.acao} onChange={e => {
                                                            const arr = [...data.acao_contencao_grid];
                                                            arr[i].acao = e.target.value;
                                                            setData('acao_contencao_grid', arr);
                                                        }} className="w-full border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 rounded text-sm"/>
                                                    </td>
                                                    <td className="p-2">
                                                        <input type="text" value={row.responsavel} onChange={e => {
                                                            const arr = [...data.acao_contencao_grid];
                                                            arr[i].responsavel = e.target.value;
                                                            setData('acao_contencao_grid', arr);
                                                        }} className="w-full border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 rounded text-sm"/>
                                                    </td>
                                                    <td className="p-2">
                                                        <input type="date" value={row.prazo} onChange={e => {
                                                            const arr = [...data.acao_contencao_grid];
                                                            arr[i].prazo = e.target.value;
                                                            setData('acao_contencao_grid', arr);
                                                        }} className="w-full border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 rounded text-sm"/>
                                                    </td>
                                                    <td className="p-2 text-center">
                                                        <button type="button" onClick={() => removeContencao(i)} className="text-red-500 hover:text-red-700"><Trash2 size={18}/></button>
                                                    </td>
                                                </tr>
                                            ))}
                                            {data.acao_contencao_grid.length === 0 && (
                                                <tr><td colSpan="4" className="p-4 text-center text-sm text-slate-500">Nenhuma ação adicionada.</td></tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </section>

                            {/* SEÇÃO 4: CAUSA RAIZ */}
                            <section>
                                <h3 className="text-lg font-bold text-orange-600 dark:text-orange-500 uppercase border-b-2 border-orange-500 pb-2 mb-6">4. Análise das Possíveis Causas ou Causa Raiz</h3>
                                
                                <div className="border border-slate-300 dark:border-slate-700 rounded-md overflow-hidden mb-6">
                                    <table className="min-w-full divide-y divide-slate-300 dark:divide-slate-700">
                                        <tbody className="divide-y divide-slate-300 dark:divide-slate-700">
                                            {data.cinco_porques.porques.map((pq, i) => (
                                                <tr key={pq.id}>
                                                    <td className="px-4 py-3 bg-slate-100 dark:bg-slate-800 w-32 font-medium text-slate-700 dark:text-slate-300">{pq.p}</td>
                                                    <td className="p-2">
                                                        <input type="text" value={pq.r} onChange={e => {
                                                            const arr = [...data.cinco_porques.porques];
                                                            arr[i].r = e.target.value;
                                                            setData('cinco_porques', {...data.cinco_porques, porques: arr});
                                                        }} className="w-full border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 rounded text-sm" placeholder="Escreva a resposta..."/>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="flex items-center">
                                    <div className="bg-slate-200 dark:bg-slate-700 px-6 py-3 rounded-l-md border border-slate-300 dark:border-slate-600 font-bold text-slate-800 dark:text-slate-200">
                                        Causa Raiz:
                                    </div>
                                    <input 
                                        type="text" 
                                        value={data.cinco_porques.causa_raiz} 
                                        onChange={e => setData('cinco_porques', {...data.cinco_porques, causa_raiz: e.target.value})}
                                        className="flex-1 border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 rounded-r-md border-l-0"
                                        placeholder="Descreva a causa raiz consolidada..."
                                    />
                                </div>
                            </section>

                            {/* SEÇÃO 5: PLANO DE AÇÃO */}
                            <section>
                                <h3 className="text-lg font-bold text-orange-600 dark:text-orange-500 uppercase border-b-2 border-orange-500 pb-2 mb-6 flex justify-between items-center">
                                    <span>5. Plano de Ação</span>
                                    <button type="button" onClick={addPlanoAcao} className="text-sm bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 px-3 py-1 rounded flex items-center gap-1 hover:bg-orange-200 transition">
                                        <Plus size={16}/> Adicionar Linha
                                    </button>
                                </h3>
                                
                                <div className="overflow-x-auto border border-slate-300 dark:border-slate-700 rounded-md">
                                    <table className="min-w-full divide-y divide-slate-300 dark:divide-slate-700">
                                        <thead className="bg-slate-100 dark:bg-slate-800">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Ação</th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase w-48">Responsável</th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase w-40">Prazo</th>
                                                <th className="px-4 py-3 w-16"></th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-300 dark:divide-slate-700">
                                            {data.plano_acao_grid.map((row, i) => (
                                                <tr key={i}>
                                                    <td className="p-2">
                                                        <input type="text" value={row.acao} onChange={e => {
                                                            const arr = [...data.plano_acao_grid];
                                                            arr[i].acao = e.target.value;
                                                            setData('plano_acao_grid', arr);
                                                        }} className="w-full border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 rounded text-sm"/>
                                                    </td>
                                                    <td className="p-2">
                                                        <input type="text" value={row.responsavel} onChange={e => {
                                                            const arr = [...data.plano_acao_grid];
                                                            arr[i].responsavel = e.target.value;
                                                            setData('plano_acao_grid', arr);
                                                        }} className="w-full border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 rounded text-sm"/>
                                                    </td>
                                                    <td className="p-2">
                                                        <input type="date" value={row.prazo} onChange={e => {
                                                            const arr = [...data.plano_acao_grid];
                                                            arr[i].prazo = e.target.value;
                                                            setData('plano_acao_grid', arr);
                                                        }} className="w-full border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 rounded text-sm"/>
                                                    </td>
                                                    <td className="p-2 text-center">
                                                        <button type="button" onClick={() => removePlanoAcao(i)} className="text-red-500 hover:text-red-700"><Trash2 size={18}/></button>
                                                    </td>
                                                </tr>
                                            ))}
                                            {data.plano_acao_grid.length === 0 && (
                                                <tr><td colSpan="4" className="p-4 text-center text-sm text-slate-500">Nenhum plano de ação adicionado.</td></tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </section>

                            {/* SEÇÃO 6: EVIDÊNCIAS */}
                            <section>
                                <h3 className="text-lg font-bold text-orange-600 dark:text-orange-500 uppercase border-b-2 border-orange-500 pb-2 mb-6 flex justify-between items-center">
                                    <span>6. Evidências</span>
                                    <button type="button" onClick={addEvidencia} className="text-sm bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 px-3 py-1 rounded flex items-center gap-1 hover:bg-orange-200 transition">
                                        <Plus size={16}/> Adicionar Foto
                                    </button>
                                </h3>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {data.evidencias.map((ev, i) => (
                                        <div key={i} className="border border-slate-300 dark:border-slate-700 rounded-md p-4 flex flex-col gap-3 relative bg-slate-50 dark:bg-slate-900/50">
                                            <button type="button" onClick={() => removeEvidencia(i)} className="absolute top-2 right-2 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/50 p-1 rounded transition">
                                                <Trash2 size={16}/>
                                            </button>
                                            
                                            <div className="flex-1 border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-md flex flex-col items-center justify-center bg-white dark:bg-slate-800 p-4 overflow-hidden relative" style={{ minHeight: '200px' }}>
                                                {ev.foto instanceof File ? (
                                                    <img src={URL.createObjectURL(ev.foto)} alt="Preview" className="max-h-full max-w-full object-contain" />
                                                ) : (ev.foto && typeof ev.foto === 'string') ? (
                                                    <img src={`/storage/${ev.foto}`} alt="Saved Preview" className="max-h-full max-w-full object-contain" />
                                                ) : (
                                                    <div className="text-slate-400 flex flex-col items-center">
                                                        <Upload size={32} className="mb-2" />
                                                        <span className="text-sm">Clique ou arraste a imagem</span>
                                                    </div>
                                                )}
                                                <input 
                                                    type="file" 
                                                    accept="image/*"
                                                    onChange={e => {
                                                        const arr = [...data.evidencias];
                                                        if (e.target.files && e.target.files[0]) {
                                                            arr[i].foto = e.target.files[0];
                                                            setData('evidencias', arr);
                                                        }
                                                    }}
                                                    className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                                />
                                            </div>

                                            <input 
                                                type="text" 
                                                value={ev.descricao} 
                                                onChange={e => {
                                                    const arr = [...data.evidencias];
                                                    arr[i].descricao = e.target.value;
                                                    setData('evidencias', arr);
                                                }} 
                                                className="w-full border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 rounded text-sm"
                                                placeholder="Descrição da foto..."
                                            />
                                        </div>
                                    ))}
                                    {data.evidencias.length === 0 && (
                                        <div className="col-span-1 md:col-span-2 text-center py-8 text-slate-500 border border-dashed border-slate-300 dark:border-slate-700 rounded-md">
                                            Nenhuma evidência adicionada. Clique em "Adicionar Foto" para anexar arquivos.
                                        </div>
                                    )}
                                </div>
                            </section>

                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
