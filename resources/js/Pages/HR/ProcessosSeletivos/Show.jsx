import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { ArrowLeft, Plus, Edit2, Trash2, User, Phone, Mail, GraduationCap, Briefcase, MapPin } from 'lucide-react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Swal from 'sweetalert2';
import { DragDropContext, Droppable, Draggable } from '@hello-pangea/dnd';

export default function Show({ auth, processo }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingCandidato, setEditingCandidato] = useState(null);

    const { data, setData, post, put, delete: destroy, processing, errors, reset, clearErrors } = useForm({
        processo_seletivo_id: processo.id,
        nome: '',
        email: '',
        telefone: '',
        idade: '',
        endereco: '',
        bairro: '',
        cidade_estado: '',
        nivel_ensino: '',
        faculdade: '',
        experiencia_anos: '',
        ultima_empresa: '',
        cargo: '',
        tempo_ultimo_emprego: '',
        avaliacao_geral: '',
        referencias: '',
        etapa_atual: 'Triagem de Currículo',
    });

    const etapas = [
        'Triagem de Currículo', 
        'Teste Prático', 
        'Dinâmica de Grupo', 
        'Entrevista Inicial', 
        'Entrevista com Gerentes', 
        'Entrevista Final', 
        'Aprovado', 
        'Reprovado'
    ];

    const openModal = (candidato = null) => {
        clearErrors();
        if (candidato) {
            setEditingCandidato(candidato);
            setData({
                processo_seletivo_id: processo.id,
                nome: candidato.nome,
                email: candidato.email || '',
                telefone: candidato.telefone || '',
                idade: candidato.idade || '',
                endereco: candidato.endereco || '',
                bairro: candidato.bairro || '',
                cidade_estado: candidato.cidade_estado || '',
                nivel_ensino: candidato.nivel_ensino || '',
                faculdade: candidato.faculdade || '',
                experiencia_anos: candidato.experiencia_anos || '',
                ultima_empresa: candidato.ultima_empresa || '',
                cargo: candidato.cargo || '',
                tempo_ultimo_emprego: candidato.tempo_ultimo_emprego || '',
                avaliacao_geral: candidato.avaliacao_geral || '',
                referencias: candidato.referencias || '',
                etapa_atual: candidato.etapa_atual,
            });
        } else {
            setEditingCandidato(null);
            reset();
            setData('processo_seletivo_id', processo.id);
        }
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setEditingCandidato(null);
        reset();
    };

    const submit = (e) => {
        e.preventDefault();
        if (editingCandidato) {
            put(route('candidatos.update', editingCandidato.id), {
                onSuccess: () => {
                    closeModal();
                    Swal.fire({ title: 'Sucesso!', text: 'Dados atualizados.', icon: 'success', timer: 2000, showConfirmButton: false });
                },
            });
        } else {
            post(route('candidatos.store'), {
                onSuccess: () => {
                    closeModal();
                    Swal.fire({ title: 'Sucesso!', text: 'Candidato adicionado.', icon: 'success', timer: 2000, showConfirmButton: false });
                },
            });
        }
    };

    const handleDelete = (candidato) => {
        Swal.fire({
            title: 'Você tem certeza?',
            text: "Remover este candidato do processo?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                destroy(route('candidatos.destroy', candidato.id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire('Excluído!', 'O candidato foi removido.', 'success');
                    }
                });
            }
        });
    };

    const handleDragEnd = (result) => {
        if (!result.destination) return;

        const { source, destination, draggableId } = result;

        if (source.droppableId !== destination.droppableId) {
            const candidato = processo.candidatos.find(c => c.id.toString() === draggableId);
            const newEtapa = destination.droppableId;
            
            router.put(route('candidatos.update', candidato.id), {
                ...candidato,
                etapa_atual: newEtapa
            }, {
                preserveScroll: true,
            });
        }
    };

    // Group candidates by stage
    const groupedCandidates = etapas.reduce((acc, etapa) => {
        acc[etapa] = processo.candidatos.filter(c => c.etapa_atual === etapa);
        return acc;
    }, {});

    return (
        <AuthenticatedLayout user={auth.user} header={
            <div className="flex items-center">
                <button onClick={() => window.history.back()} className="mr-4 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                    <ArrowLeft className="w-5 h-5" />
                </button>
                <h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                    Candidatos: {processo.nome}
                </h2>
            </div>
        }>
            <Head title={`Candidatos - ${processo.nome}`} />

            <div className="py-6">
                <div className="max-w-full mx-auto sm:px-6 lg:px-8">
                    
                    <div className="flex justify-between items-center mb-6">
                        <div className="text-sm text-slate-500 dark:text-slate-400">
                            Total de Candidatos: <span className="font-semibold text-slate-900 dark:text-slate-100">{processo.candidatos.length}</span>
                        </div>
                        <PrimaryButton onClick={(e) => { e.currentTarget.blur(); openModal(); }}>
                            <Plus className="w-4 h-4 mr-2" /> Adicionar Candidato
                        </PrimaryButton>
                    </div>

                    <DragDropContext onDragEnd={handleDragEnd}>
                        <div className="flex gap-4 overflow-x-auto pb-6 snap-x min-h-[70vh]">
                            {etapas.map(etapa => (
                                <Droppable droppableId={etapa} key={etapa}>
                                    {(provided, snapshot) => (
                                        <div 
                                            ref={provided.innerRef}
                                            {...provided.droppableProps}
                                            className={`flex-none w-80 rounded-lg shadow-sm border flex flex-col snap-start transition-colors ${
                                                snapshot.isDraggingOver 
                                                ? 'bg-indigo-50/50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-800' 
                                                : 'bg-slate-100 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700'
                                            }`}
                                        >
                                            <div className="p-3 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800 rounded-t-lg">
                                                <h3 className="font-medium text-slate-800 dark:text-slate-200 text-sm truncate" title={etapa}>
                                                    {etapa}
                                                </h3>
                                                <span className="bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 py-0.5 px-2 rounded-full text-xs font-semibold">
                                                    {groupedCandidates[etapa].length}
                                                </span>
                                            </div>
                                            <div className="p-3 flex-1 overflow-y-auto space-y-3">
                                                {groupedCandidates[etapa].map((candidato, index) => (
                                                    <Draggable key={candidato.id} draggableId={candidato.id.toString()} index={index}>
                                                        {(provided, snapshot) => (
                                                            <div
                                                                ref={provided.innerRef}
                                                                {...provided.draggableProps}
                                                                {...provided.dragHandleProps}
                                                                className={`bg-white dark:bg-slate-900 p-3 rounded border shadow-sm cursor-pointer transition-colors ${
                                                                    snapshot.isDragging
                                                                    ? 'border-indigo-500 shadow-lg'
                                                                    : 'border-slate-200 dark:border-slate-700 hover:border-indigo-500'
                                                                }`}
                                                                onClick={() => openModal(candidato)}
                                                            >
                                                                <div className="font-medium text-sm text-slate-900 dark:text-slate-100 flex justify-between items-start">
                                                                    <span className="truncate">{candidato.nome}</span>
                                                                </div>
                                                                
                                                                <div className="mt-2 space-y-1">
                                                                    {candidato.cargo && (
                                                                        <div className="flex items-center text-xs text-slate-500 dark:text-slate-400">
                                                                            <Briefcase className="w-3 h-3 mr-1" />
                                                                            <span className="truncate">{candidato.cargo}</span>
                                                                        </div>
                                                                    )}
                                                                    {candidato.telefone && (
                                                                        <div className="flex items-center text-xs text-slate-500 dark:text-slate-400">
                                                                            <Phone className="w-3 h-3 mr-1" />
                                                                            {candidato.telefone}
                                                                        </div>
                                                                    )}
                                                                </div>

                                                                <div className="mt-3 flex justify-end">
                                                                    <div className="flex ml-2 shrink-0 space-x-1">
                                                                        <button onClick={(e) => { e.currentTarget.blur(); openModal(candidato); }} className="p-1 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                                                            <Edit2 className="w-4 h-4" />
                                                                        </button>
                                                                        <button onClick={(e) => { e.currentTarget.blur(); handleDelete(candidato); }} className="p-1 text-slate-400 hover:text-red-600 dark:hover:text-red-400 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                                                            <Trash2 className="w-4 h-4" />
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        )}
                                                    </Draggable>
                                                ))}
                                                {provided.placeholder}
                                                {groupedCandidates[etapa].length === 0 && !snapshot.isDraggingOver && (
                                                    <div className="text-center py-6 text-sm text-slate-400 dark:text-slate-500 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-lg">
                                                        Vazio
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </Droppable>
                            ))}
                        </div>
                    </DragDropContext>

                </div>
            </div>

            <Modal show={isModalOpen} onClose={closeModal} maxWidth="2xl">
                <form onSubmit={submit} className="p-6">
                    <h2 className="text-lg font-medium text-slate-900 dark:text-slate-100 mb-6">
                        {editingCandidato ? 'Detalhes do Candidato' : 'Adicionar Candidato'}
                    </h2>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto pr-2">
                        {/* Pessoal */}
                        <div className="col-span-full mb-2 border-b dark:border-slate-700 pb-2"><h3 className="font-semibold text-slate-700 dark:text-slate-300">Dados Pessoais</h3></div>
                        
                        <div>
                            <InputLabel htmlFor="nome" value="Nome do Candidato *" />
                            <TextInput id="nome" type="text" className="mt-1 block w-full" value={data.nome} onChange={e => setData('nome', e.target.value)} required />
                            <InputError message={errors.nome} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="etapa_atual" value="Etapa Atual *" />
                            <select id="etapa_atual" className="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" value={data.etapa_atual} onChange={e => setData('etapa_atual', e.target.value)} required>
                                {etapas.map(e => <option key={e} value={e}>{e}</option>)}
                            </select>
                            <InputError message={errors.etapa_atual} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="email" value="E-mail" />
                            <TextInput id="email" type="email" className="mt-1 block w-full" value={data.email} onChange={e => setData('email', e.target.value)} />
                        </div>
                        <div>
                            <InputLabel htmlFor="telefone" value="Telefone" />
                            <TextInput id="telefone" type="text" className="mt-1 block w-full" value={data.telefone} onChange={e => setData('telefone', e.target.value)} />
                        </div>
                        
                        <div className="col-span-full grid grid-cols-3 gap-4">
                            <div>
                                <InputLabel htmlFor="idade" value="Idade" />
                                <TextInput id="idade" type="number" className="mt-1 block w-full" value={data.idade} onChange={e => setData('idade', e.target.value)} />
                            </div>
                            <div className="col-span-2">
                                <InputLabel htmlFor="cidade_estado" value="Cidade - Estado" />
                                <TextInput id="cidade_estado" type="text" className="mt-1 block w-full" value={data.cidade_estado} onChange={e => setData('cidade_estado', e.target.value)} />
                            </div>
                        </div>

                        {/* Acadêmico e Profissional */}
                        <div className="col-span-full mt-4 mb-2 border-b dark:border-slate-700 pb-2"><h3 className="font-semibold text-slate-700 dark:text-slate-300">Acadêmico e Profissional</h3></div>

                        <div>
                            <InputLabel htmlFor="nivel_ensino" value="Nível de Ensino" />
                            <TextInput id="nivel_ensino" type="text" className="mt-1 block w-full" value={data.nivel_ensino} onChange={e => setData('nivel_ensino', e.target.value)} />
                        </div>
                        <div>
                            <InputLabel htmlFor="faculdade" value="Faculdade / Instituição" />
                            <TextInput id="faculdade" type="text" className="mt-1 block w-full" value={data.faculdade} onChange={e => setData('faculdade', e.target.value)} />
                        </div>

                        <div>
                            <InputLabel htmlFor="ultima_empresa" value="Última Empresa" />
                            <TextInput id="ultima_empresa" type="text" className="mt-1 block w-full" value={data.ultima_empresa} onChange={e => setData('ultima_empresa', e.target.value)} />
                        </div>
                        <div>
                            <InputLabel htmlFor="cargo" value="Cargo" />
                            <TextInput id="cargo" type="text" className="mt-1 block w-full" value={data.cargo} onChange={e => setData('cargo', e.target.value)} />
                        </div>

                        <div>
                            <InputLabel htmlFor="experiencia_anos" value="Exp. na área (anos)" />
                            <TextInput id="experiencia_anos" type="number" className="mt-1 block w-full" value={data.experiencia_anos} onChange={e => setData('experiencia_anos', e.target.value)} />
                        </div>
                        <div>
                            <InputLabel htmlFor="tempo_ultimo_emprego" value="Tempo último emprego (anos)" />
                            <TextInput id="tempo_ultimo_emprego" type="number" className="mt-1 block w-full" value={data.tempo_ultimo_emprego} onChange={e => setData('tempo_ultimo_emprego', e.target.value)} />
                        </div>

                        {/* Avaliação */}
                        <div className="col-span-full mt-4 mb-2 border-b dark:border-slate-700 pb-2"><h3 className="font-semibold text-slate-700 dark:text-slate-300">Avaliação do RH</h3></div>

                        <div className="col-span-full">
                            <InputLabel htmlFor="avaliacao_geral" value="Avaliação Geral" />
                            <textarea id="avaliacao_geral" className="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="3" value={data.avaliacao_geral} onChange={e => setData('avaliacao_geral', e.target.value)}></textarea>
                        </div>
                        <div className="col-span-full">
                            <InputLabel htmlFor="referencias" value="Referências" />
                            <textarea id="referencias" className="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="2" value={data.referencias} onChange={e => setData('referencias', e.target.value)}></textarea>
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <SecondaryButton onClick={closeModal}>Cancelar</SecondaryButton>
                        <PrimaryButton disabled={processing}>Salvar</PrimaryButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
