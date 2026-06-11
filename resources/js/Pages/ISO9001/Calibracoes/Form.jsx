import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { Save, ArrowLeft } from 'lucide-react';

export default function Form({ auth, calibracao, companyId }) {
    
    const { data, setData, post, processing, errors } = useForm({
        _method: calibracao ? 'put' : 'post',
        company_id: companyId,
        equipamento: calibracao ? calibracao.equipamento : '',
        local: calibracao ? (calibracao.local || '') : '',
        identificacao: calibracao ? (calibracao.identificacao || '') : '',
        certificado_numero: calibracao ? (calibracao.certificado_numero || '') : '',
        frequencia_meses: calibracao ? (calibracao.frequencia_meses || '') : '',
        data_ultima_calibracao: calibracao && calibracao.data_ultima_calibracao ? calibracao.data_ultima_calibracao.split('T')[0] : '',
        data_proxima_calibracao: calibracao && calibracao.data_proxima_calibracao ? calibracao.data_proxima_calibracao.split('T')[0] : '',
        observacoes: calibracao ? (calibracao.observacoes || '') : '',
        arquivo: null,
    });

    // Calcula a data da próxima calibração automaticamente se tivermos meses e data da última
    const calcularProxima = (ultimaData, meses) => {
        if (ultimaData && meses) {
            const date = new Date(ultimaData);
            date.setMonth(date.getMonth() + parseInt(meses));
            setData('data_proxima_calibracao', date.toISOString().split('T')[0]);
        }
    };

    const handleUltimaDataChange = (e) => {
        const val = e.target.value;
        setData('data_ultima_calibracao', val);
        calcularProxima(val, data.frequencia_meses);
    };

    const handleFrequenciaChange = (e) => {
        const val = e.target.value;
        setData('frequencia_meses', val);
        calcularProxima(data.data_ultima_calibracao, val);
    };

    const submit = (e) => {
        e.preventDefault();
        if (calibracao) {
            post(route('controle-calibracoes.update', calibracao.id)); // Using POST with _method=put for file upload
        } else {
            post(route('controle-calibracoes.store'));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {calibracao ? 'Editar Equipamento' : 'Novo Equipamento'}
            </h2>}
        >
            <Head title={calibracao ? 'Editar Equipamento' : 'Novo Equipamento'} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            
                            <form onSubmit={submit} className="space-y-6">
                                
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Equipamento *</label>
                                        <input
                                            type="text"
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={data.equipamento}
                                            onChange={e => setData('equipamento', e.target.value)}
                                            required
                                        />
                                        {errors.equipamento && <p className="mt-1 text-sm text-red-600">{errors.equipamento}</p>}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Identificação / Tag</label>
                                        <input
                                            type="text"
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={data.identificacao}
                                            onChange={e => setData('identificacao', e.target.value)}
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Localização</label>
                                        <input
                                            type="text"
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={data.local}
                                            onChange={e => setData('local', e.target.value)}
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Nº do Certificado</label>
                                        <input
                                            type="text"
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={data.certificado_numero}
                                            onChange={e => setData('certificado_numero', e.target.value)}
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Última Calibração</label>
                                        <input
                                            type="date"
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={data.data_ultima_calibracao}
                                            onChange={handleUltimaDataChange}
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Frequência (Meses)</label>
                                        <input
                                            type="number"
                                            min="1"
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={data.frequencia_meses}
                                            onChange={handleFrequenciaChange}
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Próxima Calibração</label>
                                        <input
                                            type="date"
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={data.data_proxima_calibracao}
                                            onChange={e => setData('data_proxima_calibracao', e.target.value)}
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Certificado (Upload)</label>
                                    <input
                                        type="file"
                                        className="block w-full text-sm text-gray-500 dark:text-gray-300
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-md file:border-0
                                          file:text-sm file:font-semibold
                                          file:bg-indigo-50 file:text-indigo-700
                                          hover:file:bg-indigo-100
                                          dark:file:bg-slate-700 dark:file:text-indigo-400"
                                        onChange={e => setData('arquivo', e.target.files[0])}
                                        accept=".pdf,.jpg,.jpeg,.png"
                                    />
                                    {calibracao?.arquivo_certificado && (
                                        <p className="mt-2 text-sm text-green-600 dark:text-green-400">
                                            Arquivo já anexado. Envie um novo apenas se quiser substituir.
                                        </p>
                                    )}
                                    {errors.arquivo && <p className="mt-1 text-sm text-red-600">{errors.arquivo}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                                    <textarea
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        rows="3"
                                        value={data.observacoes}
                                        onChange={e => setData('observacoes', e.target.value)}
                                    ></textarea>
                                </div>

                                <div className="flex items-center justify-end gap-4 mt-8">
                                    <Link
                                        href={route('controle-calibracoes.index')}
                                        className="flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-slate-600 transition"
                                    >
                                        <ArrowLeft className="w-4 h-4 mr-2" />
                                        Voltar
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-25 transition"
                                    >
                                        <Save className="w-4 h-4 mr-2" />
                                        {calibracao ? 'Salvar Alterações' : 'Cadastrar Equipamento'}
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
