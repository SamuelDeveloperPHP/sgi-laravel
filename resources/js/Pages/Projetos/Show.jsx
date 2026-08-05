import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Trash2, Search, MoreHorizontal, CalendarRange } from 'lucide-react';
import { useState, useEffect } from 'react';
import { DragDropContext, Droppable, Draggable } from '@hello-pangea/dnd';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import TaskModal from './Components/TaskModal';
import axios from 'axios';
import dayjs from 'dayjs';

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
    
    // State to track which column is showing the "Add Task" input
    const [addingTaskColId, setAddingTaskColId] = useState(null);
    const [newTaskNames, setNewTaskNames] = useState({});
    const [searchTerm, setSearchTerm] = useState('');

    const handleAddColumn = (e) => {
        e.preventDefault();
        if(!newColName.trim()) return;
        router.post('/kanban-colunas', {
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
            router.delete(`/kanban-colunas/${id}`, { preserveScroll: true });
        }
    };

    const saveEditColumn = (col) => {
        if(editColName.trim() && editColName !== col.nome) {
            router.put(`/kanban-colunas/${col.id}`, { nome: editColName }, { preserveScroll: true });
        }
        setEditingColId(null);
    };
    
    const handleAddTask = (e, colId) => {
        e.preventDefault();
        const nome = newTaskNames[colId];
        if(!nome || !nome.trim()) return;
        
        router.post('/tarefas', {
            projeto_id: projeto.id,
            kanban_coluna_id: colId,
            nome: nome
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setNewTaskNames(prev => ({...prev, [colId]: ''}));
                setAddingTaskColId(null);
            }
        });
    };

    const handleDeleteTask = (id) => {
        if(confirm('Excluir tarefa?')) {
            router.delete(`/tarefas/${id}`, { preserveScroll: true });
        }
    };

    const handleUpdateTaskLocally = (updatedTask) => {
        setColumns(prevCols => prevCols.map(col => {
            const containsTask = col.tarefas.some(t => String(t.id) === String(updatedTask.id));
            const isTargetColumn = String(col.id) === String(updatedTask.kanban_coluna_id);

            if (isTargetColumn) {
                if (containsTask) {
                    return {
                        ...col,
                        tarefas: col.tarefas.map(t => String(t.id) === String(updatedTask.id) ? updatedTask : t)
                    };
                } else {
                    return {
                        ...col,
                        tarefas: [...col.tarefas, updatedTask].sort((a, b) => a.ordem - b.ordem)
                    };
                }
            } else {
                if (containsTask) {
                    return {
                        ...col,
                        tarefas: col.tarefas.filter(t => String(t.id) !== String(updatedTask.id))
                    };
                }
            }
            return col;
        }));
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

            axios.post('/kanban-colunas/reorder', {
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

            axios.post('/tarefas/reorder', {
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

            axios.post('/tarefas/reorder', {
                tasks: [
                    ...updatedStart.map(t => ({ id: t.id, ordem: t.ordem, kanban_coluna_id: startCol.id })),
                    ...updatedFinish.map(t => ({ id: t.id, ordem: t.ordem, kanban_coluna_id: finishCol.id }))
                ]
            });
        }
    };

    const getColumnDotColor = (index) => {
        const colors = [
            'bg-gray-400',    // 0 To do
            'bg-blue-500',    // 1 In progress
            'bg-amber-500',   // 2 Review
            'bg-emerald-500', // 3 Done
            'bg-purple-500',  // 4
        ];
        return colors[index % colors.length];
    };

    const getFakeTags = (id) => {
        const tags = [];
        if (id % 5 === 0) tags.push({ label: 'GERÊNCIA', class: 'text-emerald-600 bg-emerald-50 border-emerald-100' });
        else if (id % 2 === 0) tags.push({ label: 'DEV', class: 'text-blue-600 bg-blue-50 border-blue-100' });
        else if (id % 3 === 0) tags.push({ label: 'DESIGN', class: 'text-purple-600 bg-purple-50 border-purple-100' });
        else if (id % 7 === 0) tags.push({ label: 'DOCS', class: 'text-amber-600 bg-amber-50 border-amber-100' });
        else tags.push({ label: 'ERRO', class: 'text-red-600 bg-red-50 border-red-100' });
        
        if (id % 4 === 0 && tags.length < 2) tags.push({ label: 'DEV', class: 'text-blue-600 bg-blue-50 border-blue-100' });
        
        return tags;
    };

    const filteredColumns = columns.map(col => ({
        ...col,
        tarefas: (col.tarefas || []).filter(t => 
            searchTerm === '' || 
            (t.nome && t.nome.toLowerCase().includes(searchTerm.toLowerCase())) || 
            (t.descricao && t.descricao.toLowerCase().includes(searchTerm.toLowerCase()))
        )
    }));

    return (
        <AuthenticatedLayout
            header={null} // Removemos o header padrão para criar o customizado
            fullWidth={true}
        >
            <Head title={`Board: ${projeto.nomeProjeto}`} />

            <div className="h-screen flex flex-col bg-[#f5f7fb] dark:bg-gray-900 font-sans">
                {/* Gentelella Style Header */}
                <div className="flex-none px-6 py-4 flex justify-between items-center bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 z-10">
                    <div className="flex flex-col">
                        <span className="text-[10px] uppercase tracking-wider text-gray-500 font-semibold mb-0.5">Módulos</span>
                        <h1 className="text-xl font-bold text-gray-800 dark:text-gray-100">Quadro Kanban</h1>
                    </div>
                    
                    <div className="flex items-center gap-3">
                        <div className="relative hidden sm:block">
                            <Search className="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                            <input 
                                type="text" 
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                placeholder="Filtrar cartões..." 
                                className="pl-9 pr-4 py-1.5 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md focus:ring-0 focus:border-gray-300 w-60 text-gray-700 dark:text-gray-200 placeholder-gray-400"
                            />
                        </div>
                        <button className="px-3 py-1.5 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:hover:bg-gray-800">
                            Filtros
                        </button>
                        <button className="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-medium rounded-md flex items-center gap-1 shadow-sm transition-colors">
                            <Plus className="w-4 h-4" /> Novo cartão
                        </button>
                    </div>
                </div>

                {errors.message && (
                    <div className="mx-6 mt-4 p-3 rounded-md bg-red-50 text-red-800 flex items-center gap-2 border border-red-200">
                        <AlertCircle className="w-5 h-5" />
                        <span className="text-sm font-medium">{errors.message}</span>
                    </div>
                )}

                <div className="flex-1 overflow-x-auto overflow-y-hidden p-6 bg-[#f5f7fb] dark:bg-gray-900">
                    <DragDropContext onDragEnd={onDragEnd}>
                        <Droppable droppableId="board" type="column" direction="horizontal">
                            {(provided) => (
                                <div 
                                    className="grid items-start gap-4 h-full"
                                    style={{ gridTemplateColumns: 'repeat(4, minmax(260px, 1fr))' }}
                                    ref={provided.innerRef}
                                    {...provided.droppableProps}
                                >
                                    {filteredColumns.map((col, index) => (
                                        <Draggable key={col.id.toString()} draggableId={col.id.toString()} index={index} isDragDisabled={searchTerm.trim() !== ''}>
                                            {(provided, snapshot) => (
                                                <div
                                                    ref={provided.innerRef}
                                                    {...provided.draggableProps}
                                                    className="w-full max-h-full flex flex-col bg-gray-50/30 dark:bg-gray-800/30 rounded-xl shadow-[0_2px_20px_-4px_rgba(0,0,0,0.04)] border border-gray-200/30 p-2 transition-all hover:bg-gray-50/50"
                                                >
                                                    {/* Column Header */}
                                                    <div 
                                                        {...provided.dragHandleProps}
                                                        className="py-1 mb-3 flex items-center justify-between group cursor-grab active:cursor-grabbing px-1"
                                                    >
                                                        {editingColId === col.id ? (
                                                            <TextInput 
                                                                autoFocus
                                                                className="h-8 text-sm px-2 w-full font-semibold"
                                                                value={editColName}
                                                                onChange={e => setEditColName(e.target.value)}
                                                                onBlur={() => saveEditColumn(col)}
                                                                onKeyDown={e => e.key === 'Enter' && saveEditColumn(col)}
                                                            />
                                                        ) : (
                                                            <div className="flex items-center gap-2 flex-1 min-w-0">
                                                                <div className={`w-2 h-2 rounded-full ${getColumnDotColor(index)}`}></div>
                                                                <h3 
                                                                    className="text-[15px] font-bold text-gray-800 dark:text-gray-100 cursor-text hover:text-gray-600"
                                                                    onClick={() => { setEditingColId(col.id); setEditColName(col.nome); }}
                                                                >
                                                                    {col.nome}
                                                                </h3>
                                                                <span className="text-[13px] text-gray-400 font-medium ml-1">
                                                                    {col.tarefas.length}
                                                                </span>
                                                            </div>
                                                        )}
                                                        <div className="flex items-center gap-1">
                                                            <button 
                                                                onClick={() => setAddingTaskColId(col.id)}
                                                                className="text-gray-400 hover:text-gray-600 transition-colors"
                                                            >
                                                                <Plus className="w-4 h-4" />
                                                            </button>
                                                            <button 
                                                                onClick={() => handleDeleteColumn(col.id)}
                                                                className="text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"
                                                            >
                                                                <Trash2 className="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </div>

                                                    {/* Droppable Tasks Area */}
                                                    <Droppable droppableId={col.id.toString()} type="task">
                                                        {(provided, snapshot) => (
                                                            <div 
                                                                ref={provided.innerRef}
                                                                {...provided.droppableProps}
                                                                className={`flex-1 overflow-y-auto space-y-3 min-h-[50px] pb-2 ${snapshot.isDraggingOver ? 'bg-gray-100 rounded-lg' : ''}`}
                                                            >
                                                                {col.tarefas.map((tarefa, tIndex) => {
                                                                    const tags = tarefa.tags || [];
                                                                    return (
                                                                    <Draggable key={tarefa.id.toString()} draggableId={`task-${tarefa.id}`} index={tIndex} isDragDisabled={searchTerm.trim() !== ''}>
                                                                        {(provided, snapshot) => (
                                                                            <div
                                                                                ref={provided.innerRef}
                                                                                {...provided.draggableProps}
                                                                                {...provided.dragHandleProps}
                                                                                onClick={() => setSelectedTask(tarefa)}
                                                                                className={`bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-100 dark:border-gray-700 shadow-sm group cursor-grab active:cursor-grabbing hover:border-gray-300 transition-all ${snapshot.isDragging ? 'shadow-xl rotate-2 ring-1 ring-gray-300 z-50' : ''}`}
                                                                            >
                                                                                {/* Tags */}
                                                                                <div className="flex flex-wrap gap-1.5 mb-3">
                                                                                    {tags.map((tag, i) => (
                                                                                        <span key={i} className={`text-[10px] px-1.5 py-[2px] rounded border font-bold uppercase tracking-wide bg-indigo-500/10 text-indigo-600 border-indigo-500/20 dark:text-indigo-400`}>
                                                                                            {tag}
                                                                                        </span>
                                                                                    ))}
                                                                                </div>
                                                                                
                                                                                <h4 className="text-[14px] text-gray-800 dark:text-gray-100 font-semibold leading-snug mb-1 pr-4 relative">
                                                                                    {tarefa.nome}
                                                                                    <button 
                                                                                        onClick={(e) => { e.stopPropagation(); handleDeleteTask(tarefa.id); }}
                                                                                        className="absolute right-0 top-0 text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"
                                                                                    >
                                                                                        <Trash2 className="w-3.5 h-3.5" />
                                                                                    </button>
                                                                                </h4>

                                                                                {/* Fake Description */}
                                                                                <p className="text-[13px] text-gray-500 dark:text-gray-400 leading-snug mb-4 line-clamp-2">
                                                                                    {tarefa.descricao || "A descrição detalhada vai aqui para o usuário ler."}
                                                                                </p>

                                                                                <div className="flex items-center justify-between mt-auto">
                                                                                    {/* Datas início → fim */}
                                                                                    {(tarefa.dt_inicio || tarefa.dt_fim) ? (() => {
                                                                                        const inicio = tarefa.dt_inicio ? dayjs(tarefa.dt_inicio) : null;
                                                                                        const fim    = tarefa.dt_fim    ? dayjs(tarefa.dt_fim)    : null;
                                                                                        const atrasada = fim && fim.isBefore(dayjs(), 'day');
                                                                                        const fmt = (d) => d.format('DD/MM');
                                                                                        return (
                                                                                            <div className={`flex items-center gap-1 text-[11px] font-medium px-1.5 py-0.5 rounded ${
                                                                                                atrasada
                                                                                                    ? 'bg-red-500/10 text-red-500'
                                                                                                    : 'bg-gray-100 dark:bg-gray-700/60 text-gray-500 dark:text-gray-400'
                                                                                            }`}>
                                                                                                <CalendarRange className="w-3 h-3 flex-shrink-0" />
                                                                                                {inicio && fim
                                                                                                    ? <span>{fmt(inicio)} → {fmt(fim)}</span>
                                                                                                    : inicio
                                                                                                        ? <span>Início: {fmt(inicio)}</span>
                                                                                                        : <span>Até {fmt(fim)}</span>
                                                                                                }
                                                                                            </div>
                                                                                        );
                                                                                    })()
                                                                                    : <span />}
                                                                                    
                                                                                    {/* Avatares dos membros */}
                                                                                    <div className="flex -space-x-1">
                                                                                        {(tarefa.users || []).slice(0, 3).map((user, idx) => (
                                                                                            <div key={user.id} className={`flex items-center justify-center w-6 h-6 rounded-full text-white text-[10px] font-bold ring-2 ring-white dark:ring-gray-800 ${idx % 2 === 0 ? 'bg-indigo-500' : 'bg-emerald-500'}`} title={user.name}>
                                                                                                {user.name.substring(0, 2).toUpperCase()}
                                                                                            </div>
                                                                                        ))}
                                                                                        {(tarefa.users?.length || 0) > 3 && (
                                                                                            <div className="flex items-center justify-center w-6 h-6 rounded-full bg-gray-500 text-white text-[10px] font-bold ring-2 ring-white dark:ring-gray-800">
                                                                                                +{(tarefa.users.length - 3)}
                                                                                            </div>
                                                                                        )}
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        )}
                                                                    </Draggable>
                                                                )})}
                                                                {provided.placeholder}
                                                                
                                                                {/* Add Task Input / Button */}
                                                                <div className="pt-1">
                                                                    {addingTaskColId === col.id ? (
                                                                        <form onSubmit={(e) => handleAddTask(e, col.id)} className="flex flex-col gap-2 p-2 bg-white rounded-lg border border-gray-200 shadow-sm">
                                                                            <TextInput
                                                                                autoFocus
                                                                                type="text"
                                                                                className="w-full text-sm h-9 border-none focus:ring-0 px-2"
                                                                                placeholder="Título da tarefa..."
                                                                                value={newTaskNames[col.id] || ''}
                                                                                onChange={e => setNewTaskNames(prev => ({...prev, [col.id]: e.target.value}))}
                                                                            />
                                                                            <div className="flex justify-end gap-1 border-t border-gray-100 pt-2">
                                                                                 <button 
                                                                                    type="button"
                                                                                    onClick={() => setAddingTaskColId(null)}
                                                                                    className="px-2 py-1 text-xs text-gray-500 hover:bg-gray-100 rounded"
                                                                                >
                                                                                    Cancelar
                                                                                </button>
                                                                                <button 
                                                                                    type="submit"
                                                                                    disabled={!newTaskNames[col.id]?.trim()}
                                                                                    className="px-3 py-1 bg-emerald-500 hover:bg-emerald-600 text-white disabled:opacity-50 text-xs font-medium rounded transition-colors"
                                                                                >
                                                                                    Adicionar
                                                                                </button>
                                                                            </div>
                                                                        </form>
                                                                    ) : (
                                                                        <button 
                                                                            onClick={() => setAddingTaskColId(col.id)}
                                                                            className="text-[13px] text-gray-400 hover:text-gray-800 text-left py-1 w-full pl-1 transition-colors"
                                                                        >
                                                                            + Adicionar cartão
                                                                        </button>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        )}
                                                    </Droppable>
                                                </div>
                                            )}
                                        </Draggable>
                                    ))}
                                    {provided.placeholder}

                                    {/* Add Column Button */}
                                    {columns.length < 10 && (
                                        <div className="w-full h-fit mt-2">
                                            {addingCol ? (
                                                <form onSubmit={handleAddColumn} className="bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                                                    <TextInput
                                                        autoFocus
                                                        className="w-full text-sm mb-2"
                                                        placeholder="Nome da coluna..."
                                                        value={newColName}
                                                        onChange={e => setNewColName(e.target.value)}
                                                    />
                                                    <div className="flex justify-end gap-2">
                                                        <button 
                                                            type="button" 
                                                            onClick={() => { setAddingCol(false); setNewColName(''); }}
                                                            className="px-2 py-1 text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors"
                                                        >
                                                            Cancelar
                                                        </button>
                                                        <button type="submit" disabled={!newColName.trim()} className="px-3 py-1 bg-emerald-500 text-white rounded text-xs">
                                                            Adicionar
                                                        </button>
                                                    </div>
                                                </form>
                                            ) : (
                                                <button 
                                                    onClick={() => setAddingCol(true)}
                                                    className="text-[13px] text-gray-400 hover:text-gray-800 text-left py-1 w-full pl-1 transition-colors"
                                                >
                                                    + Adicionar coluna
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
                task={selectedTask ? columns.flatMap(c => c.tarefas || []).find(t => t.id === selectedTask.id) : null} 
                columns={columns} 
                onUpdateTask={handleUpdateTaskLocally}
            />
        </AuthenticatedLayout>
    );
}
