import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2, Edit2, GripHorizontal, MoreHorizontal, Clock, AlertCircle } from 'lucide-react';
import { useState, useEffect } from 'react';
import { DragDropContext, Droppable, Draggable } from '@hello-pangea/dnd';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import TaskModal from './Components/TaskModal';
import axios from 'axios';

export default function Show({ projeto }) {
    const { errors } = usePage().props;
    const [columns, setColumns] = useState([]);

    useEffect(() => {
        const sortedCols = [...(projeto.kanban_colunas || [])].sort((a, b) => a.ordem - b.ordem);
        setColumns(sortedCols.map(col => ({
            ...col,
            tarefas: [...(col.tarefas || [])].sort((a, b) => a.ordem - b.ordem)
        })));
    }, [projeto]);

    const [newColName, setNewColName] = useState('');
    const [addingCol, setAddingCol] = useState(false);
    const [editingColId, setEditingColId] = useState(null);
    const [editColName, setEditColName] = useState('');
    const [selectedTask, setSelectedTask] = useState(null);

    const handleAddColumn = (e) => {
        e.preventDefault();
        if(!newColName.trim()) return;
        router.post(route('kanban-colunas.store'), {
            projeto_id: projeto.id,
            nome: newColName
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setNewColName('');
                setAddingCol(false);
            }
        });
    };

    const handleDeleteColumn = (id) => {
        if(confirm('Tem certeza que deseja excluir esta coluna e todas as suas tarefas?')) {
            router.delete(route('kanban-colunas.destroy', id), { preserveScroll: true });
        }
    };

    const saveEditColumn = (col) => {
        if(editColName.trim() && editColName !== col.nome) {
            router.put(route('kanban-colunas.update', col.id), { nome: editColName }, { preserveScroll: true });
        }
        setEditingColId(null);
    };

    const [newTaskNames, setNewTaskNames] = useState({});
    
    const handleAddTask = (e, colId) => {
        e.preventDefault();
        const nome = newTaskNames[colId];
        if(!nome || !nome.trim()) return;
        
        router.post(route('tarefas.store'), {
            projeto_id: projeto.id,
            kanban_coluna_id: colId,
            nome: nome
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setNewTaskNames(prev => ({...prev, [colId]: ''}));
            }
        });
    };

    const handleDeleteTask = (id) => {
        if(confirm('Excluir tarefa?')) {
            router.delete(route('tarefas.destroy', id), { preserveScroll: true });
        }
    };

    const onDragEnd = (result) => {
        const { destination, source, type } = result;
        if (!destination) return;
        if (destination.droppableId === source.droppableId && destination.index === source.index) return;

        if (type === 'column') {
            const newCols = Array.from(columns);
            const [movedCol] = newCols.splice(source.index, 1);
            newCols.splice(destination.index, 0, movedCol);
            
            const updatedCols = newCols.map((c, idx) => ({ ...c, ordem: idx + 1 }));
            setColumns(updatedCols);

            axios.post(route('kanban-colunas.reorder'), {
                columns: updatedCols.map(c => ({ id: c.id, ordem: c.ordem }))
            });
            return;
        }

        const startColIndex = columns.findIndex(c => c.id.toString() === source.droppableId);
        const finishColIndex = columns.findIndex(c => c.id.toString() === destination.droppableId);
        
        if (startColIndex === -1 || finishColIndex === -1) return;

        const startCol = columns[startColIndex];
        const finishCol = columns[finishColIndex];

        if (startCol.id === finishCol.id) {
            const newTasks = Array.from(startCol.tarefas);
            const [movedTask] = newTasks.splice(source.index, 1);
            newTasks.splice(destination.index, 0, movedTask);

            const updatedTasks = newTasks.map((t, idx) => ({ ...t, ordem: idx + 1 }));
            const newCols = Array.from(columns);
            newCols[startColIndex] = { ...startCol, tarefas: updatedTasks };
            setColumns(newCols);

            axios.post(route('tarefas.reorder'), {
                tasks: updatedTasks.map(t => ({ id: t.id, ordem: t.ordem, kanban_coluna_id: startCol.id }))
            });
        } else {
            const startTasks = Array.from(startCol.tarefas);
            const finishTasks = Array.from(finishCol.tarefas);
            const [movedTask] = startTasks.splice(source.index, 1);
            
            movedTask.kanban_coluna_id = finishCol.id;
            finishTasks.splice(destination.index, 0, movedTask);

            const updatedStart = startTasks.map((t, idx) => ({ ...t, ordem: idx + 1 }));
            const updatedFinish = finishTasks.map((t, idx) => ({ ...t, ordem: idx + 1 }));

            const newCols = Array.from(columns);
            newCols[startColIndex] = { ...startCol, tarefas: updatedStart };
            newCols[finishColIndex] = { ...finishCol, tarefas: updatedFinish };
            setColumns(newCols);

            axios.post(route('tarefas.reorder'), {
                tasks: [
                    ...updatedStart.map(t => ({ id: t.id, ordem: t.ordem, kanban_coluna_id: startCol.id })),
                    ...updatedFinish.map(t => ({ id: t.id, ordem: t.ordem, kanban_coluna_id: finishCol.id }))
                ]
            });
        }
    };

    // Gera uma prioridade fake baseada no ID para efeito visual (High, Medium, Low)
    const getFakePriority = (id) => {
        if (id % 3 === 0) return { label: 'High', class: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' };
        if (id % 2 === 0) return { label: 'Medium', class: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' };
        return { label: 'Low', class: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' };
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Board: {projeto.nomeProjeto}</h2>}
            fullWidth={true}
        >
            <Head title={`Board: ${projeto.nomeProjeto}`} />

            <div className="h-[calc(100vh-130px)] flex flex-col bg-[#f3f6f9] dark:bg-gray-900">
                <div className="flex-none px-6 py-4 flex justify-between items-center bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 z-10">
                    <div>
                        <div className="flex items-center gap-3">
                            <Link href={route('projetos.index')} className="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                                <ArrowLeft className="w-5 h-5" />
                            </Link>
                            <h1 className="text-xl font-bold text-gray-900 dark:text-white leading-none">{projeto.nomeProjeto}</h1>
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">Kanban Board</span>
                        </div>
                    </div>
                </div>

                {errors.message && (
                    <div className="mx-6 mt-4 p-3 rounded-md bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-400 flex items-center gap-2 border border-red-200 dark:border-red-800">
                        <AlertCircle className="w-5 h-5" />
                        <span className="text-sm font-medium">{errors.message}</span>
                    </div>
                )}

                <div className="flex-1 overflow-x-auto overflow-y-hidden p-6">
                    <DragDropContext onDragEnd={onDragEnd}>
                        <Droppable droppableId="board" type="column" direction="horizontal">
                            {(provided) => (
                                <div 
                                    className="flex h-full items-start gap-6"
                                    ref={provided.innerRef}
                                    {...provided.droppableProps}
                                >
                                    {columns.map((col, index) => (
                                        <Draggable key={col.id.toString()} draggableId={col.id.toString()} index={index}>
                                            {(provided, snapshot) => (
                                                <div
                                                    ref={provided.innerRef}
                                                    {...provided.draggableProps}
                                                    className={`flex-none w-[320px] max-h-full flex flex-col bg-gray-100/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-lg shadow-sm border border-gray-200/50 dark:border-gray-700/50 transition-all ${snapshot.isDragging ? 'opacity-90 ring-2 ring-indigo-500 scale-[1.02]' : ''}`}
                                                >
                                                    {/* Column Header */}
                                                    <div 
                                                        {...provided.dragHandleProps}
                                                        className="px-4 py-3.5 flex items-center justify-between group cursor-grab active:cursor-grabbing"
                                                    >
                                                        {editingColId === col.id ? (
                                                            <TextInput 
                                                                autoFocus
                                                                className="h-8 text-sm px-2 w-full font-semibold bg-white dark:bg-gray-900"
                                                                value={editColName}
                                                                onChange={e => setEditColName(e.target.value)}
                                                                onBlur={() => saveEditColumn(col)}
                                                                onKeyDown={e => e.key === 'Enter' && saveEditColumn(col)}
                                                            />
                                                        ) : (
                                                            <div className="flex items-center gap-2 flex-1 min-w-0">
                                                                <h3 
                                                                    className="text-[15px] font-semibold text-gray-800 dark:text-gray-100 cursor-text truncate hover:text-indigo-600 transition-colors"
                                                                    onClick={() => { setEditingColId(col.id); setEditColName(col.nome); }}
                                                                >
                                                                    {col.nome}
                                                                </h3>
                                                                <span className="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 shadow-sm border border-gray-200 dark:border-gray-600">
                                                                    {col.tarefas.length}
                                                                </span>
                                                            </div>
                                                        )}
                                                        <button 
                                                            onClick={() => handleDeleteColumn(col.id)}
                                                            className="text-gray-400 hover:text-red-500 p-1 rounded-md hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors ml-2 opacity-0 group-hover:opacity-100"
                                                            title="Excluir Coluna"
                                                        >
                                                            <Trash2 className="w-4 h-4" />
                                                        </button>
                                                    </div>

                                                    {/* Droppable Tasks Area */}
                                                    <Droppable droppableId={col.id.toString()} type="task">
                                                        {(provided, snapshot) => (
                                                            <div 
                                                                ref={provided.innerRef}
                                                                {...provided.droppableProps}
                                                                className={`flex-1 overflow-y-auto px-3 pb-3 space-y-3 min-h-[150px] transition-colors rounded-b-lg ${snapshot.isDraggingOver ? 'bg-gray-200/50 dark:bg-gray-700/30' : ''}`}
                                                            >
                                                                {col.tarefas.map((tarefa, tIndex) => {
                                                                    const priority = getFakePriority(tarefa.id);
                                                                    return (
                                                                    <Draggable key={tarefa.id.toString()} draggableId={`task-${tarefa.id}`} index={tIndex}>
                                                                        {(provided, snapshot) => (
                                                                            <div
                                                                                ref={provided.innerRef}
                                                                                {...provided.draggableProps}
                                                                                {...provided.dragHandleProps}
                                                                                onClick={() => setSelectedTask(tarefa)}
                                                                                className={`bg-white dark:bg-gray-800 p-3.5 rounded-md shadow-[0_1px_3px_rgba(0,0,0,0.1)] border border-gray-200/60 dark:border-gray-700 group cursor-grab active:cursor-grabbing hover:border-indigo-300 dark:hover:border-indigo-500 transition-all ${snapshot.isDragging ? 'shadow-xl rotate-2 ring-2 ring-indigo-500 z-50' : 'hover:-translate-y-0.5 hover:shadow-md'}`}
                                                                            >
                                                                                <div className="flex justify-between items-start mb-2">
                                                                                    <span className="text-[11px] font-semibold text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-900 px-1.5 py-0.5 rounded">
                                                                                        #VLZ-{tarefa.id.toString().padStart(3, '0')}
                                                                                    </span>
                                                                                    <div className="flex items-center gap-2">
                                                                                        <span className={`text-[10px] px-1.5 py-0.5 rounded font-semibold ${priority.class}`}>
                                                                                            {priority.label}
                                                                                        </span>
                                                                                        <button 
                                                                                            onClick={() => handleDeleteTask(tarefa.id)}
                                                                                            className="text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"
                                                                                        >
                                                                                            <Trash2 className="w-3.5 h-3.5" />
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                                
                                                                                <p className="text-[14px] text-gray-800 dark:text-gray-200 leading-snug font-medium mb-3">
                                                                                    {tarefa.nome}
                                                                                </p>

                                                                                <div className="flex items-center justify-between mt-auto pt-3 border-t border-gray-100 dark:border-gray-700/50">
                                                                                    <div className="flex items-center text-gray-400 text-xs gap-1">
                                                                                        <Clock className="w-3.5 h-3.5" />
                                                                                        <span>22 Dec</span>
                                                                                    </div>
                                                                                    <div className="flex -space-x-1.5">
                                                                                        <img className="inline-block h-6 w-6 rounded-full ring-2 ring-white dark:ring-gray-800" src={`https://ui-avatars.com/api/?name=User+${tarefa.id}&background=random&color=fff&size=24`} alt="Avatar"/>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        )}
                                                                    </Draggable>
                                                                )})}
                                                                {provided.placeholder}
                                                            </div>
                                                        )}
                                                    </Droppable>

                                                    {/* Add Task Input */}
                                                    <div className="p-3 bg-white/50 dark:bg-gray-800/50 rounded-b-lg border-t border-gray-200/60 dark:border-gray-700/50 mt-auto">
                                                        <form onSubmit={(e) => handleAddTask(e, col.id)} className="flex gap-2">
                                                            <TextInput
                                                                type="text"
                                                                className="flex-1 text-sm h-9 bg-white dark:bg-gray-900 shadow-sm"
                                                                placeholder="Adicionar tarefa..."
                                                                value={newTaskNames[col.id] || ''}
                                                                onChange={e => setNewTaskNames(prev => ({...prev, [col.id]: e.target.value}))}
                                                            />
                                                            <button 
                                                                type="submit"
                                                                disabled={!newTaskNames[col.id]?.trim()}
                                                                className="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 dark:text-indigo-400 disabled:opacity-50 text-sm font-medium rounded-md transition-colors"
                                                            >
                                                                <Plus className="w-4 h-4" />
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            )}
                                        </Draggable>
                                    ))}
                                    {provided.placeholder}

                                    {/* Add Column Button */}
                                    {columns.length < 6 && (
                                        <div className="flex-none w-[320px] h-fit">
                                            {addingCol ? (
                                                <form onSubmit={handleAddColumn} className="bg-white dark:bg-gray-800 p-3.5 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                                                    <TextInput
                                                        autoFocus
                                                        className="w-full text-sm mb-3"
                                                        placeholder="Nome da lista..."
                                                        value={newColName}
                                                        onChange={e => setNewColName(e.target.value)}
                                                    />
                                                    <div className="flex justify-end gap-2">
                                                        <button 
                                                            type="button" 
                                                            onClick={() => { setAddingCol(false); setNewColName(''); }}
                                                            className="px-3 py-1.5 text-sm font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                                                        >
                                                            Cancelar
                                                        </button>
                                                        <PrimaryButton disabled={!newColName.trim()} className="py-1.5 text-xs">Adicionar Lista</PrimaryButton>
                                                    </div>
                                                </form>
                                            ) : (
                                                <button 
                                                    onClick={() => setAddingCol(true)}
                                                    className="w-full py-3.5 px-4 flex items-center justify-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400 bg-gray-100/50 dark:bg-gray-800/50 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 transition-all"
                                                >
                                                    <Plus className="w-4 h-4" /> Adicionar nova lista
                                                </button>
                                            )}
                                        </div>
                                    )}
                                </div>
                            )}
                        </Droppable>
                    </DragDropContext>
                </div>
            </div>

            <TaskModal 
                isOpen={!!selectedTask} 
                onClose={() => setSelectedTask(null)} 
                task={selectedTask ? columns.flatMap(c => c.tarefas).find(t => t.id === selectedTask.id) : null} 
                columns={columns} 
            />
        </AuthenticatedLayout>
    );
}
