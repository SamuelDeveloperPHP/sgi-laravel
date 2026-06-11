import { Fragment, useState, useEffect, useRef } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { X, MessageSquare, Paperclip, CheckCircle2, Circle, MoreHorizontal, Send, Info, Tag, Users } from 'lucide-react';
import { router, usePage } from '@inertiajs/react';
import dayjs from 'dayjs';
import axios from 'axios';

export default function TaskModal({ isOpen, onClose, task, columns }) {
    const { auth } = usePage().props;
    const [activeTab, setActiveTab] = useState('details');
    const [data, setData] = useState(null);
    const [newMessage, setNewMessage] = useState('');
    const [newChecklist, setNewChecklist] = useState('');
    
    // Sync local state with prop
    useEffect(() => {
        if (task) {
            setData(task);
        }
    }, [task]);

    if (!data) return null;

    const handleUpdateField = (field, value) => {
        setData(prev => ({ ...prev, [field]: value }));
        // Auto save field
        axios.put(route('tarefas.update', data.id), {
            [field]: value,
            _method: 'PUT'
        }).catch(err => console.error(err));
    };

    const handleAddComment = (e) => {
        e.preventDefault();
        if (!newMessage.trim()) return;

        router.post(route('tarefas.comentarios.store', data.id), {
            mensagem: newMessage
        }, {
            preserveScroll: true,
            onSuccess: () => setNewMessage('')
        });
    };

    const handleAddChecklist = (e) => {
        if (e.key === 'Enter' && newChecklist.trim()) {
            e.preventDefault();
            router.post(route('tarefas.checklists.store', data.id), {
                descricao: newChecklist
            }, {
                preserveScroll: true,
                onSuccess: () => setNewChecklist('')
            });
        }
    };

    const toggleChecklist = (checklist) => {
        router.put(route('tarefas.checklists.update', checklist.id), {
            concluido: !checklist.concluido
        }, { preserveScroll: true });
    };

    const handleDeleteChecklist = (id) => {
        router.delete(route('tarefas.checklists.destroy', id), { preserveScroll: true });
    };

    const handleFileUpload = (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);
        
        router.post(route('tarefas.anexos.store', data.id), formData, {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    return (
        <Transition appear show={isOpen} as={Fragment}>
            <Dialog as="div" className="relative z-50" onClose={onClose}>
                <Transition.Child
                    as={Fragment}
                    enter="ease-out duration-300"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in duration-200"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="fixed inset-0 bg-black/60 backdrop-blur-sm" />
                </Transition.Child>

                <div className="fixed inset-0 overflow-y-auto">
                    <div className="flex min-h-full items-center justify-center p-4 text-center">
                        <Transition.Child
                            as={Fragment}
                            enter="ease-out duration-300"
                            enterFrom="opacity-0 scale-95"
                            enterTo="opacity-100 scale-100"
                            leave="ease-in duration-200"
                            leaveFrom="opacity-100 scale-100"
                            leaveTo="opacity-0 scale-95"
                        >
                            <Dialog.Panel className="w-full max-w-5xl transform overflow-hidden rounded-2xl bg-[#1c1c1c] text-left align-middle shadow-2xl transition-all flex flex-col md:flex-row min-h-[700px] border border-gray-800">
                                
                                {/* Header Mobile só pra fechar */}
                                <div className="absolute top-4 right-4 z-10">
                                    <button
                                        type="button"
                                        className="rounded-md bg-[#2d2d2d] p-2 text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none transition-colors"
                                        onClick={onClose}
                                    >
                                        <X className="h-5 w-5" aria-hidden="true" />
                                    </button>
                                </div>

                                {/* Coluna Esquerda: Detalhes da Tarefa */}
                                <div className="flex-1 p-8 overflow-y-auto border-r border-gray-800">
                                    <div className="flex items-start gap-3 mb-6">
                                        <button onClick={() => handleUpdateField('status', data.status === 'completed' ? 'pending' : 'completed')} className="mt-1 flex-shrink-0 text-gray-400 hover:text-indigo-400 transition-colors">
                                            {data.status === 'completed' ? <CheckCircle2 className="w-6 h-6 text-indigo-500" /> : <Circle className="w-6 h-6" />}
                                        </button>
                                        <div>
                                            <input 
                                                className="bg-transparent border-none text-2xl font-bold text-white p-0 focus:ring-0 w-full placeholder-gray-600 mb-2"
                                                value={data.nome || ''}
                                                onChange={(e) => handleUpdateField('nome', e.target.value)}
                                                placeholder="Nome da Tarefa"
                                            />
                                            <div className="flex items-center gap-1 text-xs text-gray-500">
                                                <span>Criado em {dayjs(data.created).format('DD/MM/YYYY')}</span>
                                                <Info className="w-3.5 h-3.5 ml-2 cursor-pointer hover:text-gray-300" />
                                            </div>
                                        </div>
                                    </div>

                                    {/* Badges/Tags (Mock) */}
                                    <div className="flex items-center gap-4 mb-8 text-sm">
                                        <div className="flex items-center gap-2">
                                            <Tag className="w-4 h-4 text-gray-500" />
                                            <span className="bg-red-500/20 text-red-400 px-2 py-0.5 rounded flex items-center gap-1">URG <X className="w-3 h-3 cursor-pointer"/></span>
                                            <span className="bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded flex items-center gap-1">TEC <X className="w-3 h-3 cursor-pointer"/></span>
                                        </div>
                                        <div className="flex items-center gap-2 ml-4">
                                            <Users className="w-4 h-4 text-gray-500" />
                                            <div className="flex -space-x-2">
                                                <div className="w-6 h-6 rounded-full bg-indigo-600 flex items-center justify-center text-[10px] text-white border border-[#1c1c1c]">SM</div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Tabs */}
                                    <div className="flex items-center gap-2 mb-8">
                                        <button 
                                            onClick={() => setActiveTab('details')}
                                            className={`px-4 py-1.5 rounded-md text-sm font-medium flex items-center gap-2 transition-colors ${activeTab === 'details' ? 'bg-indigo-600 text-white' : 'bg-[#2d2d2d] text-gray-300 hover:bg-gray-700'}`}
                                        >
                                            Detalhes da tarefa
                                        </button>
                                        <button 
                                            onClick={() => setActiveTab('attachments')}
                                            className={`px-4 py-1.5 rounded-md text-sm font-medium flex items-center gap-2 transition-colors ${activeTab === 'attachments' ? 'bg-indigo-600 text-white' : 'bg-[#2d2d2d] text-gray-300 hover:bg-gray-700'}`}
                                        >
                                            <Paperclip className="w-4 h-4" /> Anexos ({data.anexos?.length || 0})
                                        </button>
                                    </div>

                                    {activeTab === 'details' && (
                                        <div className="space-y-6">
                                            {/* Grid de Propriedades */}
                                            <div className="grid grid-cols-2 gap-x-6 gap-y-5">
                                                <div>
                                                    <label className="block text-xs text-gray-400 mb-1.5">Status</label>
                                                    <select 
                                                        value={data.status || 'pending'}
                                                        onChange={(e) => handleUpdateField('status', e.target.value)}
                                                        className="w-full bg-[#2d2d2d] border border-gray-700 rounded-md text-sm text-gray-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-1.5 px-3"
                                                    >
                                                        <option value="pending">Em andamento</option>
                                                        <option value="completed">Concluída</option>
                                                        <option value="blocked">Bloqueada</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label className="block text-xs text-gray-400 mb-1.5">Prioridade</label>
                                                    <select 
                                                        value={data.relevancia || 'medium'}
                                                        onChange={(e) => handleUpdateField('relevancia', e.target.value)}
                                                        className="w-full bg-[#2d2d2d] border border-gray-700 rounded-md text-sm text-gray-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-1.5 px-3"
                                                    >
                                                        <option value="high">Urgente</option>
                                                        <option value="medium">Média</option>
                                                        <option value="low">Baixa</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label className="block text-xs text-gray-400 mb-1.5">Data de início</label>
                                                    <input 
                                                        type="date"
                                                        value={data.dt_inicio || ''}
                                                        onChange={(e) => handleUpdateField('dt_inicio', e.target.value)}
                                                        className="w-full bg-[#2d2d2d] border border-gray-700 rounded-md text-sm text-gray-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-1.5 px-3 [color-scheme:dark]"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="block text-xs text-gray-400 mb-1.5">Data de conclusão</label>
                                                    <input 
                                                        type="date"
                                                        value={data.dt_fim || ''}
                                                        onChange={(e) => handleUpdateField('dt_fim', e.target.value)}
                                                        className="w-full bg-[#2d2d2d] border border-gray-700 rounded-md text-sm text-gray-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-1.5 px-3 [color-scheme:dark]"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="block text-xs text-gray-400 mb-1.5">Repetir <Info className="inline w-3 h-3 ml-1 text-gray-500"/></label>
                                                    <select 
                                                        value={data.repetir || ''}
                                                        onChange={(e) => handleUpdateField('repetir', e.target.value)}
                                                        className="w-full bg-[#2d2d2d] border border-gray-700 rounded-md text-sm text-gray-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-1.5 px-3"
                                                    >
                                                        <option value="">Não se repete</option>
                                                        <option value="daily">Diariamente</option>
                                                        <option value="weekly">Semanalmente</option>
                                                        <option value="monthly">Mensalmente</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label className="block text-xs text-gray-400 mb-1.5">Bucket <Info className="inline w-3 h-3 ml-1 text-gray-500"/></label>
                                                    <select 
                                                        value={data.kanban_coluna_id || ''}
                                                        onChange={(e) => handleUpdateField('kanban_coluna_id', e.target.value)}
                                                        className="w-full bg-[#2d2d2d] border border-gray-700 rounded-md text-sm text-gray-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-1.5 px-3"
                                                    >
                                                        {columns.map(c => (
                                                            <option key={c.id} value={c.id}>{c.nome}</option>
                                                        ))}
                                                    </select>
                                                </div>
                                            </div>

                                            {/* Checklist */}
                                            <div className="pt-4">
                                                <h4 className="text-sm font-bold text-white mb-3">Lista de verificação</h4>
                                                <div className="space-y-2 mb-3">
                                                    {data.checklists?.map(item => (
                                                        <div key={item.id} className="flex items-center gap-3 group">
                                                            <button onClick={() => toggleChecklist(item)} className="text-gray-400 hover:text-indigo-400 transition-colors">
                                                                {item.concluido ? <CheckCircle2 className="w-5 h-5 text-indigo-500" /> : <Circle className="w-5 h-5" />}
                                                            </button>
                                                            <span className={`text-sm flex-1 ${item.concluido ? 'line-through text-gray-500' : 'text-gray-300'}`}>
                                                                {item.descricao}
                                                            </span>
                                                            <button onClick={() => handleDeleteChecklist(item.id)} className="text-gray-600 hover:text-red-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                                                <X className="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    ))}
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    <Circle className="w-5 h-5 text-gray-600" />
                                                    <input 
                                                        type="text"
                                                        value={newChecklist}
                                                        onChange={(e) => setNewChecklist(e.target.value)}
                                                        onKeyDown={handleAddChecklist}
                                                        placeholder="Adicione etapas para concluir esta tarefa. Pressione Enter."
                                                        className="bg-transparent border-none text-sm text-gray-300 p-0 focus:ring-0 w-full placeholder-gray-600"
                                                    />
                                                </div>
                                            </div>

                                            {/* Notes */}
                                            <div className="pt-4">
                                                <h4 className="text-sm font-bold text-white mb-3">Anotações</h4>
                                                <textarea 
                                                    rows={4}
                                                    value={data.descricao || ''}
                                                    onChange={(e) => handleUpdateField('descricao', e.target.value)}
                                                    placeholder="Digite uma descrição ou adicione anotações aqui"
                                                    className="w-full bg-[#2d2d2d] border border-gray-700 rounded-md text-sm text-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-3 resize-none"
                                                />
                                            </div>
                                        </div>
                                    )}

                                    {activeTab === 'attachments' && (
                                        <div className="space-y-4">
                                            <div className="flex items-center justify-between">
                                                <h4 className="text-sm font-bold text-white">Anexos da Tarefa</h4>
                                                <label className="cursor-pointer bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                                    Adicionar Arquivo
                                                    <input type="file" className="hidden" onChange={handleFileUpload} />
                                                </label>
                                            </div>
                                            
                                            {data.anexos?.length === 0 ? (
                                                <div className="text-center py-12 bg-[#2d2d2d] rounded-lg border border-gray-700 border-dashed">
                                                    <Paperclip className="w-8 h-8 text-gray-500 mx-auto mb-3" />
                                                    <p className="text-gray-400 text-sm">Nenhum arquivo anexado a esta tarefa.</p>
                                                </div>
                                            ) : (
                                                <div className="space-y-3">
                                                    {data.anexos?.map(anexo => (
                                                        <div key={anexo.id} className="flex items-center justify-between p-3 bg-[#2d2d2d] rounded-lg border border-gray-700">
                                                            <div className="flex items-center gap-3">
                                                                <div className="p-2 bg-gray-800 rounded">
                                                                    <Paperclip className="w-4 h-4 text-gray-400" />
                                                                </div>
                                                                <div>
                                                                    <a href={`/storage/${anexo.file_path}`} target="_blank" className="text-sm font-medium text-indigo-400 hover:underline">{anexo.file_name}</a>
                                                                    <p className="text-xs text-gray-500">Adicionado em {dayjs(anexo.created_at).format('DD/MM/YYYY')}</p>
                                                                </div>
                                                            </div>
                                                            <button 
                                                                onClick={() => {
                                                                    router.delete(route('tarefas.anexos.destroy', anexo.id), { preserveScroll: true });
                                                                }}
                                                                className="text-gray-500 hover:text-red-400 p-1"
                                                            >
                                                                <X className="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>

                                {/* Coluna Direita: Chat / Histórico */}
                                <div className="w-full md:w-[350px] bg-[#1a1a1a] flex flex-col">
                                    <div className="p-4 border-b border-gray-800 flex justify-between items-center">
                                        <h3 className="font-semibold text-white">Chat da tarefa</h3>
                                        <div className="flex items-center gap-2 text-gray-400">
                                            <button className="hover:text-white transition-colors p-1"><MoreHorizontal className="w-5 h-5"/></button>
                                        </div>
                                    </div>
                                    
                                    <div className="flex-1 overflow-y-auto p-4 flex flex-col gap-4">
                                        {data.comentarios?.length === 0 ? (
                                            <div className="flex-1 flex flex-col items-center justify-center text-center mt-8">
                                                <div className="w-24 h-24 bg-gray-800 rounded-full flex items-center justify-center mb-4">
                                                    <MessageSquare className="w-10 h-10 text-indigo-500" />
                                                </div>
                                                <h4 className="text-white font-bold mb-2">Iniciar a conversa</h4>
                                                <p className="text-sm text-gray-400 max-w-[200px]">
                                                    Use mensagens para incluir colegas de equipe e compartilhar rapidamente o que você pensa.
                                                </p>
                                            </div>
                                        ) : (
                                            data.comentarios?.map(comment => (
                                                <div key={comment.id} className="flex gap-3">
                                                    <div className="w-8 h-8 rounded-full bg-indigo-600 flex-shrink-0 flex items-center justify-center text-white text-xs font-bold uppercase">
                                                        {comment.user?.name?.substring(0, 2) || 'US'}
                                                    </div>
                                                    <div>
                                                        <div className="flex items-baseline gap-2">
                                                            <span className="font-medium text-sm text-white">{comment.user?.name}</span>
                                                            <span className="text-xs text-gray-500">{dayjs(comment.created_at).format('DD/MM HH:mm')}</span>
                                                        </div>
                                                        <p className="text-sm text-gray-300 mt-1 bg-[#2d2d2d] p-3 rounded-lg rounded-tl-none">{comment.mensagem}</p>
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>

                                    <div className="p-4 border-t border-gray-800 bg-[#1c1c1c]">
                                        <form onSubmit={handleAddComment}>
                                            <div className="relative">
                                                <textarea
                                                    rows={1}
                                                    placeholder="Digite uma mensagem"
                                                    value={newMessage}
                                                    onChange={e => setNewMessage(e.target.value)}
                                                    onKeyDown={(e) => {
                                                        if (e.key === 'Enter' && !e.shiftKey) {
                                                            e.preventDefault();
                                                            handleAddComment(e);
                                                        }
                                                    }}
                                                    className="w-full bg-[#2d2d2d] border border-gray-700 rounded-lg pl-4 pr-12 py-3 text-sm text-white placeholder-gray-500 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                                                />
                                                <button 
                                                    type="submit"
                                                    disabled={!newMessage.trim()}
                                                    className="absolute right-2 bottom-2 p-1.5 text-gray-400 hover:text-indigo-400 disabled:opacity-50 transition-colors"
                                                >
                                                    <Send className="w-5 h-5" />
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                            </Dialog.Panel>
                        </Transition.Child>
                    </div>
                </div>
            </Dialog>
        </Transition>
    );
}
