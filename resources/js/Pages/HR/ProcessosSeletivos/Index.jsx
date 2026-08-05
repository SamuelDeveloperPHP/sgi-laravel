import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { Search, Plus, Edit2, Trash2 } from 'lucide-react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Swal from 'sweetalert2';
import dayjs from 'dayjs';
import { DragDropContext, Droppable, Draggable } from '@hello-pangea/dnd';

export default function Index({ auth, processos, filters }) {
    const [search, setSearch] = useState(filters?.search || '');
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingProcesso, setEditingProcesso] = useState(null);
    const [localProcessos, setLocalProcessos] = useState(processos.data);

    React.useEffect(() => {
        setLocalProcessos(processos.data);
    }, [processos.data]);

    const { data, setData, post, put, delete: destroy, processing, errors, reset, clearErrors } = useForm({
        nome: '',
        status: 'Em Andamento',
        data_inicio: '',
        data_fim: '',
        custo_planejado: '',
        custo_realizado: '',
    });

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('processos-seletivos.index'), { search }, { preserveState: true });
    };

    const openModal = (processo = null) => {
        clearErrors();
        if (processo) {
            setEditingProcesso(processo);
            setData({
                nome: processo.nome,
                status: processo.status,
                data_inicio: processo.data_inicio,
                data_fim: processo.data_fim || '',
                custo_planejado: processo.custo_planejado,
                custo_realizado: processo.custo_realizado,
            });
        } else {
            setEditingProcesso(null);
            reset();
        }
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setEditingProcesso(null);
        reset();
    };

    const submit = (e) => {
        e.preventDefault();
        if (editingProcesso) {
            put(route('processos-seletivos.update', editingProcesso.id), {
                onSuccess: () => {
                    closeModal();
                    Swal.fire({ title: 'Sucesso!', text: 'Processo atualizado.', icon: 'success', timer: 2000, showConfirmButton: false });
                },
            });
        } else {
            post(route('processos-seletivos.store'), {
                onSuccess: () => {
                    closeModal();
                    Swal.fire({ title: 'Sucesso!', text: 'Processo criado.', icon: 'success', timer: 2000, showConfirmButton: false });
                },
            });
        }
    };

    const handleDelete = (processo) => {
        Swal.fire({
            title: 'Você tem certeza?',
            text: "Esta ação não poderá ser revertida!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                destroy(route('processos-seletivos.destroy', processo.id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire('Excluído!', 'O registro foi excluído.', 'success');
                    }
                });
            }
        });
    };

    const handleDragEnd = (result) => {
        if (!result.destination) return;

        const { source, destination, draggableId } = result;

        if (source.droppableId !== destination.droppableId) {
            const pIndex = localProcessos.findIndex(p => p.id.toString() === draggableId);
            if (pIndex === -1) return;
            
            const processo = localProcessos[pIndex];
            const originalStatus = processo.status;
            const newStatus = destination.droppableId;
            
            // Optimistic update
            const newProcessos = [...localProcessos];
            newProcessos[pIndex] = { ...processo, status: newStatus };
            setLocalProcessos(newProcessos);
            
            router.put(route('processos-seletivos.update', processo.id), {
                ...processo,
                status: newStatus
            }, {
                preserveScroll: true,
                preserveState: true,
                onError: () => {
                    // Revert on error
                    const reverted = [...localProcessos];
                    reverted[pIndex].status = originalStatus;
                    setLocalProcessos(reverted);
                    Swal.fire('Erro!', 'Não foi possível atualizar o status.', 'error');
                }
            });
        }
    };

    const statuses = ['Em Andamento', 'Concluído', 'Cancelado'];
    const groupedProcessos = statuses.reduce((acc, status) => {
        acc[status] = localProcessos.filter(p => p.status === status);
        return acc;
    }, {});

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Processos Seletivos</h2>}>
            <Head title="Processos Seletivos" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            
                            <div className="flex justify-between items-center mb-6">
                                <form onSubmit={handleSearch} className="flex gap-2">
                                    <TextInput
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="Buscar processo..."
                                        className="w-64"
                                    />
                                    <PrimaryButton type="submit">
                                        <Search className="w-4 h-4 mr-2" /> Buscar
                                    </PrimaryButton>
                                </form>
                                <PrimaryButton onClick={(e) => { e.currentTarget.blur(); openModal(); }}>
                                    <Plus className="w-4 h-4 mr-2" /> Novo Processo
                                </PrimaryButton>
                            </div>

                            <DragDropContext onDragEnd={handleDragEnd}>
                                <div className="flex gap-6 overflow-x-auto pb-6 min-h-[60vh]">
                                    {statuses.map(status => (
                                        <Droppable droppableId={status} key={status}>
                                            {(provided, snapshot) => (
                                                <div 
                                                    ref={provided.innerRef}
                                                    {...provided.droppableProps}
                                                    className={`flex-1 min-w-[320px] max-w-sm rounded-lg border flex flex-col transition-colors ${
                                                        snapshot.isDraggingOver 
                                                        ? 'bg-slate-50/80 dark:bg-slate-800/80 border-slate-300 dark:border-slate-600' 
                                                        : 'bg-slate-100/50 dark:bg-slate-800/30 border-slate-200 dark:border-slate-700'
                                                    }`}
                                                >
                                                    <div className="p-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-white dark:bg-slate-800 rounded-t-lg">
                                                        <h3 className="font-semibold text-slate-800 dark:text-slate-200">
                                                            {status}
                                                        </h3>
                                                        <span className={`py-0.5 px-2.5 rounded-full text-xs font-bold ${
                                                            status === 'Em Andamento' ? 'bg-blue-100 text-blue-800' : 
                                                            status === 'Concluído' ? 'bg-emerald-100 text-emerald-800' : 
                                                            'bg-red-100 text-red-800'
                                                        }`}>
                                                            {groupedProcessos[status].length}
                                                        </span>
                                                    </div>
                                                    <div className="p-3 flex-1 overflow-y-auto space-y-3">
                                                        {groupedProcessos[status].map((p, index) => (
                                                            <Draggable key={p.id} draggableId={p.id.toString()} index={index}>
                                                                {(provided, snapshot) => (
                                                                    <div
                                                                        ref={provided.innerRef}
                                                                        {...provided.draggableProps}
                                                                        {...provided.dragHandleProps}
                                                                        className={`bg-white dark:bg-slate-900 p-4 rounded-lg border shadow-sm transition-all ${
                                                                            snapshot.isDragging
                                                                            ? 'border-indigo-500 shadow-md rotate-1'
                                                                            : 'border-slate-200 dark:border-slate-700 hover:border-indigo-400 hover:shadow-md'
                                                                        }`}
                                                                    >
                                                                        <div className="flex justify-between items-start mb-2">
                                                                            <a href={route('processos-seletivos.show', p.id)} className="font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 line-clamp-2">
                                                                                {p.nome}
                                                                            </a>
                                                                            <div className="flex ml-2 shrink-0 space-x-1">
                                                                                <button onClick={(e) => { e.currentTarget.blur(); openModal(p); }} className="p-1 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                                                                    <Edit2 className="w-4 h-4" />
                                                                                </button>
                                                                                <button onClick={(e) => { e.currentTarget.blur(); handleDelete(p); }} className="p-1 text-slate-400 hover:text-red-600 dark:hover:text-red-400 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                                                                    <Trash2 className="w-4 h-4" />
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <div className="text-sm text-slate-500 dark:text-slate-400 mb-3">
                                                                            <p><strong>Início:</strong> {dayjs(p.data_inicio).format('DD/MM/YYYY')}</p>
                                                                            <div className="grid grid-cols-2 gap-2 mt-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                                                                <div>
                                                                                    <span className="block text-xs uppercase tracking-wider text-slate-400">Planejado</span>
                                                                                    <span className="font-medium text-slate-700 dark:text-slate-300">R$ {Number(p.custo_planejado).toLocaleString('pt-BR')}</span>
                                                                                </div>
                                                                                <div>
                                                                                    <span className="block text-xs uppercase tracking-wider text-slate-400">Real</span>
                                                                                    <span className="font-medium text-slate-700 dark:text-slate-300">R$ {Number(p.custo_realizado).toLocaleString('pt-BR')}</span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <div className="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                                                                            <a href={route('processos-seletivos.show', p.id)} className="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 flex items-center justify-center w-full bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 py-1.5 rounded transition-colors">
                                                                                Gerenciar Candidatos
                                                                            </a>
                                                                        </div>
                                                                    </div>
                                                                )}
                                                            </Draggable>
                                                        ))}
                                                        {provided.placeholder}
                                                        {groupedProcessos[status].length === 0 && !snapshot.isDraggingOver && (
                                                            <div className="text-center py-8 text-sm text-slate-400 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-lg">
                                                                Nenhum processo aqui
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
                </div>
            </div>

            <Modal show={isModalOpen} onClose={closeModal} maxWidth="md">
                <form onSubmit={submit} className="p-6">
                    <h2 className="text-lg font-medium text-slate-900 dark:text-slate-100 mb-6">
                        {editingProcesso ? 'Editar Processo' : 'Novo Processo Seletivo'}
                    </h2>

                    <div className="space-y-4">
                        <div>
                            <InputLabel htmlFor="nome" value="Nome do Processo/Vaga" />
                            <TextInput
                                id="nome"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.nome}
                                onChange={e => setData('nome', e.target.value)}
                            />
                            <InputError message={errors.nome} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="status" value="Status" />
                            <select
                                id="status"
                                className="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                value={data.status}
                                onChange={e => setData('status', e.target.value)}
                            >
                                <option value="Em Andamento">Em Andamento</option>
                                <option value="Concluído">Concluído</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                            <InputError message={errors.status} className="mt-2" />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel htmlFor="data_inicio" value="Data de Início" />
                                <TextInput
                                    id="data_inicio"
                                    type="date"
                                    className="mt-1 block w-full"
                                    value={data.data_inicio}
                                    onChange={e => setData('data_inicio', e.target.value)}
                                />
                                <InputError message={errors.data_inicio} className="mt-2" />
                            </div>
                            <div>
                                <InputLabel htmlFor="data_fim" value="Data do Fim" />
                                <TextInput
                                    id="data_fim"
                                    type="date"
                                    className="mt-1 block w-full"
                                    value={data.data_fim}
                                    onChange={e => setData('data_fim', e.target.value)}
                                />
                                <InputError message={errors.data_fim} className="mt-2" />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel htmlFor="custo_planejado" value="Custo Planejado (R$)" />
                                <TextInput
                                    id="custo_planejado"
                                    type="number"
                                    step="0.01"
                                    className="mt-1 block w-full"
                                    value={data.custo_planejado}
                                    onChange={e => setData('custo_planejado', e.target.value)}
                                />
                                <InputError message={errors.custo_planejado} className="mt-2" />
                            </div>
                            <div>
                                <InputLabel htmlFor="custo_realizado" value="Custo Realizado (R$)" />
                                <TextInput
                                    id="custo_realizado"
                                    type="number"
                                    step="0.01"
                                    className="mt-1 block w-full"
                                    value={data.custo_realizado}
                                    onChange={e => setData('custo_realizado', e.target.value)}
                                />
                                <InputError message={errors.custo_realizado} className="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={closeModal}>Cancelar</SecondaryButton>
                        <PrimaryButton disabled={processing}>Salvar</PrimaryButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
