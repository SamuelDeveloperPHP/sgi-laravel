import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { Users, FolderLock, Plus, Trash2, UserPlus, Shield } from 'lucide-react';
import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Grupos({ grupos, users, pastas }) {
    const [grupoModalOpen, setGrupoModalOpen] = useState(false);
    const [newGrupoName, setNewGrupoName] = useState('');

    const [userModalOpen, setUserModalOpen] = useState(false);
    const [selectedGrupoId, setSelectedGrupoId] = useState(null);
    const [selectedUserId, setSelectedUserId] = useState('');

    const [permModalOpen, setPermModalOpen] = useState(false);
    const [selectedPastaId, setSelectedPastaId] = useState('');
    const [permView, setPermView] = useState(true);
    const [permUpload, setPermUpload] = useState(false);
    const [permDelete, setPermDelete] = useState(false);

    // -- Grupo
    const handleCreateGrupo = (e) => {
        e.preventDefault();
        router.post(route('file-manager.grupos.store'), { nome: newGrupoName }, {
            onSuccess: () => {
                setGrupoModalOpen(false);
                setNewGrupoName('');
            }
        });
    };

    const handleDeleteGrupo = (id) => {
        if (confirm('Excluir este grupo permanentemente?')) {
            router.delete(route('file-manager.grupos.destroy', id));
        }
    };

    // -- Usuários no Grupo
    const openUserModal = (grupoId) => {
        setSelectedGrupoId(grupoId);
        setSelectedUserId('');
        setUserModalOpen(true);
    };

    const handleAddUser = (e) => {
        e.preventDefault();
        router.post(route('file-manager.grupos.users.add', selectedGrupoId), {
            user_id: selectedUserId
        }, {
            onSuccess: () => setUserModalOpen(false)
        });
    };

    const handleRemoveUser = (grupoId, userId) => {
        if (confirm('Remover usuário deste grupo?')) {
            router.delete(route('file-manager.grupos.users.remove', { grupoId, userId }));
        }
    };

    // -- Permissões de Pasta
    const openPermModal = (grupoId) => {
        setSelectedGrupoId(grupoId);
        setSelectedPastaId('');
        setPermView(true);
        setPermUpload(false);
        setPermDelete(false);
        setPermModalOpen(true);
    };

    const handleSavePerm = (e) => {
        e.preventDefault();
        router.post(route('file-manager.grupos.permissoes', selectedGrupoId), {
            pasta_id: selectedPastaId,
            pode_visualizar: permView,
            pode_incluir: permUpload,
            pode_excluir: permDelete
        }, {
            onSuccess: () => setPermModalOpen(false)
        });
    };

    return (
        <AuthenticatedLayout header="Grupos de Acesso - Gerenciador de Arquivos">
            <Head title="Grupos de Acesso" />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Header Card */}
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg border border-gray-200 flex justify-between items-center">
                        <div>
                            <h2 className="text-xl font-semibold text-gray-800">Grupos de Acesso</h2>
                            <p className="text-sm text-gray-500 mt-1">Gerencie os grupos e defina quais pastas eles podem visualizar ou modificar.</p>
                        </div>
                        <button 
                            onClick={() => setGrupoModalOpen(true)}
                            className="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors"
                        >
                            <Plus className="w-4 h-4" /> Novo Grupo
                        </button>
                    </div>

                    {/* Lista de Grupos */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {grupos.map(grupo => (
                            <div key={grupo.id} className="bg-white border border-gray-200 shadow-sm rounded-lg overflow-hidden flex flex-col">
                                <div className="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                                    <h3 className="font-semibold text-gray-800 flex items-center gap-2">
                                        <Users className="w-5 h-5 text-indigo-500" />
                                        {grupo.nome}
                                    </h3>
                                    <button onClick={() => handleDeleteGrupo(grupo.id)} className="text-gray-400 hover:text-red-500">
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                </div>
                                
                                {/* Members Section */}
                                <div className="p-4 flex-1">
                                    <div className="flex justify-between items-center mb-3">
                                        <h4 className="text-xs font-semibold uppercase tracking-wider text-gray-500">Membros</h4>
                                        <button onClick={() => openUserModal(grupo.id)} className="text-xs text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                                            <UserPlus className="w-3 h-3" /> Add
                                        </button>
                                    </div>
                                    <div className="space-y-2 mb-4">
                                        {grupo.users.length === 0 && <p className="text-sm text-gray-400 italic">Sem membros</p>}
                                        {grupo.users.map(u => (
                                            <div key={u.id} className="flex justify-between items-center text-sm">
                                                <span className="text-gray-700">{u.name}</span>
                                                <button onClick={() => handleRemoveUser(grupo.id, u.id)} className="text-gray-400 hover:text-red-500"><XCircleIcon className="w-4 h-4" /></button>
                                            </div>
                                        ))}
                                    </div>

                                    {/* Permissions Section */}
                                    <div className="flex justify-between items-center mb-3 mt-6 pt-4 border-t border-gray-100">
                                        <h4 className="text-xs font-semibold uppercase tracking-wider text-gray-500">Acesso a Pastas</h4>
                                        <button onClick={() => openPermModal(grupo.id)} className="text-xs text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                                            <Shield className="w-3 h-3" /> Configurar
                                        </button>
                                    </div>
                                    <div className="space-y-2">
                                        {grupo.pastas.length === 0 && <p className="text-sm text-gray-400 italic">Sem permissões específicas</p>}
                                        {grupo.pastas.map(p => (
                                            <div key={p.id} className="text-sm bg-gray-50 p-2 rounded flex justify-between items-center">
                                                <span className="font-medium text-gray-700 flex items-center gap-1.5">
                                                    <FolderLock className="w-3.5 h-3.5 text-emerald-500" />
                                                    {p.nome}
                                                </span>
                                                <div className="flex gap-1">
                                                    {p.pivot.pode_visualizar && <span title="Visualizar" className="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded text-[10px] font-bold">V</span>}
                                                    {p.pivot.pode_incluir && <span title="Upload" className="px-1.5 py-0.5 bg-green-100 text-green-700 rounded text-[10px] font-bold">U</span>}
                                                    {p.pivot.pode_excluir && <span title="Excluir" className="px-1.5 py-0.5 bg-red-100 text-red-700 rounded text-[10px] font-bold">E</span>}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                </div>
            </div>

            {/* Modal Novo Grupo */}
            <Modal show={grupoModalOpen} onClose={() => setGrupoModalOpen(false)}>
                <form onSubmit={handleCreateGrupo} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 mb-4">Novo Grupo</h2>
                    <div className="mb-4">
                        <label className="block text-sm font-medium text-gray-700">Nome do grupo</label>
                        <TextInput
                            type="text"
                            value={newGrupoName}
                            onChange={e => setNewGrupoName(e.target.value)}
                            className="mt-1 block w-full"
                            required
                        />
                    </div>
                    <div className="flex justify-end gap-2">
                        <SecondaryButton onClick={() => setGrupoModalOpen(false)}>Cancelar</SecondaryButton>
                        <PrimaryButton type="submit">Criar Grupo</PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Modal Adicionar Usuário */}
            <Modal show={userModalOpen} onClose={() => setUserModalOpen(false)}>
                <form onSubmit={handleAddUser} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 mb-4">Adicionar Membro</h2>
                    <div className="mb-4">
                        <label className="block text-sm font-medium text-gray-700 mb-1">Selecione o usuário</label>
                        <select 
                            value={selectedUserId}
                            onChange={e => setSelectedUserId(e.target.value)}
                            className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full"
                            required
                        >
                            <option value="" disabled>-- Selecione --</option>
                            {users.map(u => <option key={u.id} value={u.id}>{u.name} ({u.email})</option>)}
                        </select>
                    </div>
                    <div className="flex justify-end gap-2">
                        <SecondaryButton onClick={() => setUserModalOpen(false)}>Cancelar</SecondaryButton>
                        <PrimaryButton type="submit">Adicionar</PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Modal Configurar Permissão */}
            <Modal show={permModalOpen} onClose={() => setPermModalOpen(false)}>
                <form onSubmit={handleSavePerm} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 mb-4">Configurar Acesso a Pasta</h2>
                    <p className="text-sm text-gray-500 mb-4">Defina o nível de acesso que este grupo terá na pasta selecionada.</p>
                    
                    <div className="mb-4">
                        <label className="block text-sm font-medium text-gray-700 mb-1">Selecione a pasta</label>
                        <select 
                            value={selectedPastaId}
                            onChange={e => setSelectedPastaId(e.target.value)}
                            className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full"
                            required
                        >
                            <option value="" disabled>-- Selecione --</option>
                            {pastas.map(p => <option key={p.id} value={p.id}>{p.nome}</option>)}
                        </select>
                    </div>

                    <div className="space-y-3 mb-6 bg-gray-50 p-4 rounded-md border border-gray-200">
                        <label className="flex items-center gap-2">
                            <input type="checkbox" checked={permView} onChange={e => setPermView(e.target.checked)} className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <span className="text-sm font-medium text-gray-700">Pode visualizar arquivos</span>
                        </label>
                        <label className="flex items-center gap-2">
                            <input type="checkbox" checked={permUpload} onChange={e => setPermUpload(e.target.checked)} className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <span className="text-sm font-medium text-gray-700">Pode fazer upload / criar subpastas</span>
                        </label>
                        <label className="flex items-center gap-2">
                            <input type="checkbox" checked={permDelete} onChange={e => setPermDelete(e.target.checked)} className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <span className="text-sm font-medium text-gray-700 text-red-600">Pode excluir arquivos / pastas</span>
                        </label>
                    </div>

                    <div className="flex justify-end gap-2">
                        <SecondaryButton onClick={() => setPermModalOpen(false)}>Cancelar</SecondaryButton>
                        <PrimaryButton type="submit">Salvar Permissões</PrimaryButton>
                    </div>
                </form>
            </Modal>

        </AuthenticatedLayout>
    );
}

// Icon helper since XCircleIcon isn't standard in lucide export from my memory
const XCircleIcon = ({className}) => (
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
        <circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>
    </svg>
);
