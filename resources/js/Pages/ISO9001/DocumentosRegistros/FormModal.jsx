import React, { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function FormModal({ show, onClose, documento, currentCompanyId }) {
    const isEditing = !!documento;

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        company_id: currentCompanyId || '',
        codigo: '',
        identificacao: '',
        area: '',
        tipo_documento: '',
        revisao_atual: '',
        ano_ultima_revisao: '',
        meio: '',
        local_arquivo: '',
        indexacao: '',
        protecao: '',
        tempo_arquivamento: '',
        destino_apos_prazo: '',
    });

    useEffect(() => {
        if (show) {
            if (isEditing) {
                setData({
                    ...documento
                });
            } else {
                reset();
                setData('company_id', currentCompanyId);
            }
            clearErrors();
        }
    }, [show, documento]);

    const submit = (e) => {
        e.preventDefault();
        
        if (isEditing) {
            put(route('controle-documentos.update', documento.id), {
                onSuccess: () => onClose(),
            });
        } else {
            post(route('controle-documentos.store'), {
                onSuccess: () => onClose(),
            });
        }
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="4xl">
            <div className="p-6">
                <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6">
                    {isEditing ? 'Editar Documento' : 'Novo Documento'}
                </h2>

                <form onSubmit={submit} className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel htmlFor="codigo" value="Código" />
                            <TextInput
                                id="codigo"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.codigo || ''}
                                onChange={(e) => setData('codigo', e.target.value)}
                            />
                            <InputError message={errors.codigo} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="identificacao" value="Identificação *" />
                            <TextInput
                                id="identificacao"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.identificacao || ''}
                                onChange={(e) => setData('identificacao', e.target.value)}
                                required
                            />
                            <InputError message={errors.identificacao} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="area" value="Área" />
                            <TextInput
                                id="area"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.area || ''}
                                onChange={(e) => setData('area', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="tipo_documento" value="Tipo de Documento" />
                            <TextInput
                                id="tipo_documento"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.tipo_documento || ''}
                                onChange={(e) => setData('tipo_documento', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="revisao_atual" value="Revisão Atual" />
                            <TextInput
                                id="revisao_atual"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.revisao_atual || ''}
                                onChange={(e) => setData('revisao_atual', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="ano_ultima_revisao" value="Ano da Última Revisão" />
                            <TextInput
                                id="ano_ultima_revisao"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.ano_ultima_revisao || ''}
                                onChange={(e) => setData('ano_ultima_revisao', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="meio" value="Meio" />
                            <TextInput
                                id="meio"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.meio || ''}
                                onChange={(e) => setData('meio', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="local_arquivo" value="Local de Arquivo" />
                            <TextInput
                                id="local_arquivo"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.local_arquivo || ''}
                                onChange={(e) => setData('local_arquivo', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="indexacao" value="Indexação" />
                            <TextInput
                                id="indexacao"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.indexacao || ''}
                                onChange={(e) => setData('indexacao', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="protecao" value="Proteção" />
                            <TextInput
                                id="protecao"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.protecao || ''}
                                onChange={(e) => setData('protecao', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="tempo_arquivamento" value="Tempo de Arquivamento" />
                            <TextInput
                                id="tempo_arquivamento"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.tempo_arquivamento || ''}
                                onChange={(e) => setData('tempo_arquivamento', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="destino_apos_prazo" value="Destino Após Prazo" />
                            <TextInput
                                id="destino_apos_prazo"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.destino_apos_prazo || ''}
                                onChange={(e) => setData('destino_apos_prazo', e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="flex items-center justify-end mt-6">
                        <SecondaryButton onClick={onClose} className="mr-3">
                            Cancelar
                        </SecondaryButton>
                        <PrimaryButton disabled={processing}>
                            {isEditing ? 'Atualizar' : 'Salvar'}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    );
}
