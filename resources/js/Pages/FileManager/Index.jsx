import React, { useState, useRef } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { 
    Folder, 
    File, 
    FileText, 
    Image as ImageIcon, 
    Upload, 
    FolderPlus, 
    MoreVertical, 
    Trash2, 
    Star, 
    Download,
    Search,
    ChevronRight,
    CornerDownRight
} from 'lucide-react';
import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Index({ 
    company, companies, root, pastaAtual, subpastas, arquivos, 
    breadcrumb, tree, storageUsed, secao, canUpload, canDelete 
}) {
    const { auth } = usePage().props;
    const [uploadModalOpen, setUploadModalOpen] = useState(false);
    const [folderModalOpen, setFolderModalOpen] = useState(false);
    const [newFolderName, setNewFolderName] = useState('');
    const [uploading, setUploading] = useState(false);
    
    // File upload ref
    const fileInputRef = useRef(null);

    const formatBytes = (bytes) => {
        if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return bytes + ' B';
    };

    const getFileIcon = (ext) => {
        const iconClasses = "w-10 h-10 mx-auto mb-2 opacity-80";
        switch(ext) {
            case 'pdf': return <FileText className={`${iconClasses} text-red-500`} />;
            case 'doc':
            case 'docx': return <FileText className={`${iconClasses} text-blue-500`} />;
            case 'xls':
            case 'xlsx': return <FileText className={`${iconClasses} text-green-500`} />;
            case 'jpg':
            case 'jpeg':
            case 'png': return <ImageIcon className={`${iconClasses} text-yellow-500`} />;
            default: return <File className={`${iconClasses} text-gray-400`} />;
        }
    };

    const handleCreateFolder = (e) => {
        e.preventDefault();
        router.post(route('file-manager.pastas.store'), {
            nome: newFolderName,
            parent_id: pastaAtual.is_root ? null : pastaAtual.id
        }, {
            onSuccess: () => {
                setFolderModalOpen(false);
                setNewFolderName('');
            }
        });
    };

    const handleFileUpload = (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);
        if (!pastaAtual.is_root) {
            formData.append('pasta_id', pastaAtual.id);
        }

        setUploading(true);
        router.post(route('file-manager.upload'), formData, {
            onFinish: () => setUploading(false)
        });
    };

    const handleDeleteFile = (id) => {
        if(confirm('Excluir este arquivo?')) {
            router.delete(route('file-manager.arquivos.destroy', id));
        }
    };

    const handleDeleteFolder = (id) => {
        if(confirm('Excluir esta pasta?')) {
            router.delete(route('file-manager.pastas.destroy', id));
        }
    };

    // Recursive Tree Renderer
    const renderTree = (nodes, level = 0) => {
        return nodes.map(node => (
            <div key={node.id}>
                <Link 
                    href={route('file-manager.index', { pasta_id: node.id })}
                    className={`flex items-center gap-2 py-1.5 px-3 text-sm rounded-md transition-colors ${pastaAtual.id === node.id ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-600 hover:bg-gray-50'}`}
                    style={{ paddingLeft: `${(level * 1) + 0.75}rem` }}
                >
                    {level > 0 && <CornerDownRight className="w-3 h-3 text-gray-300" />}
                    <Folder className={`w-4 h-4 ${pastaAtual.id === node.id ? 'text-indigo-500' : 'text-gray-400'}`} />
                    {node.nome}
                </Link>
                {node.children && node.children.length > 0 && (
                    <div className="mt-1">
                        {renderTree(node.children, level + 1)}
                    </div>
                )}
            </div>
        ));
    };

    return (
        <AuthenticatedLayout header="Gerenciador de Arquivos">
            <Head title="Arquivos" />

            {/* Navbar superior para empresas (se master admin) */}
            {auth.user.is_master_admin && companies && (
                <div className="bg-white dark:bg-gray-800 p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <div className="text-sm text-gray-500 dark:text-gray-400">
                        Visualizando empresa:
                    </div>
                    <select 
                        value={company.id}
                        onChange={e => router.get(route('file-manager.index', { company_id: e.target.value }))}
                        className="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                    >
                        {companies.map(c => (
                            <option key={c.id} value={c.id}>{c.nome_fantasia}</option>
                        ))}
                    </select>
                </div>
            )}

            <div className="flex h-[calc(100vh-65px)] bg-white dark:bg-gray-900">
                
                {/* Sidebar File Manager (Estilo Gentelella) */}
                <div className="w-64 border-r border-gray-200 dark:border-gray-800 flex flex-col hidden md:flex shrink-0">
                    <div className="p-4 border-b border-gray-200 dark:border-gray-800">
                        <h2 className="font-semibold text-lg text-gray-800 dark:text-gray-200">Files</h2>
                    </div>

                    <div className="flex-1 overflow-y-auto p-4 space-y-6">
                        {/* Seções */}
                        <div className="space-y-1">
                            <Link href={route('file-manager.index')} className={`flex items-center gap-2 py-2 px-3 text-sm rounded-md transition-colors ${secao === 'drive' ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-600 hover:bg-gray-50'}`}>
                                <Folder className="w-4 h-4" /> Minha Empresa
                            </Link>
                            
                            {/* Árvore de Pastas */}
                            {secao === 'drive' && (
                                <div className="mt-2 ml-2 space-y-1 border-l-2 border-gray-100 dark:border-gray-800">
                                    {renderTree(tree)}
                                </div>
                            )}

                            <Link href={route('file-manager.index', { secao: 'favoritos' })} className={`flex items-center gap-2 py-2 px-3 text-sm rounded-md transition-colors ${secao === 'favoritos' ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-600 hover:bg-gray-50'}`}>
                                <Star className="w-4 h-4" /> Favoritos
                            </Link>
                            
                            <Link href={route('file-manager.index', { secao: 'lixeira' })} className={`flex items-center gap-2 py-2 px-3 text-sm rounded-md transition-colors ${secao === 'lixeira' ? 'bg-indigo-50 text-indigo-600 font-medium' : 'text-gray-600 hover:bg-gray-50'}`}>
                                <Trash2 className="w-4 h-4" /> Lixeira
                            </Link>
                            
                            {/* Gerenciamento de Grupos e Empresas */}
                            {(auth.user.is_master_admin || canUpload) && (
                                <div className="pt-4 mt-4 border-t border-gray-100 dark:border-gray-800">
                                    <p className="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Administração</p>
                                    <Link href={route('file-manager.grupos.index')} className="flex items-center gap-2 py-2 px-3 text-sm text-gray-600 hover:bg-gray-50 rounded-md">
                                        Grupos de Acesso
                                    </Link>
                                    {auth.user.is_master_admin && (
                                        <Link href={route('file-manager.empresa-acesso.index')} className="flex items-center gap-2 py-2 px-3 text-sm text-gray-600 hover:bg-gray-50 rounded-md">
                                            Acesso por Empresa
                                        </Link>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Storage Info */}
                    <div className="p-4 border-t border-gray-200 dark:border-gray-800">
                        <div className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Storage</div>
                        <div className="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700 mb-2">
                            {/* Exemplo visual, sem limite estrito ainda */}
                            <div className="bg-emerald-500 h-1.5 rounded-full" style={{ width: '45%' }}></div>
                        </div>
                        <div className="text-sm text-gray-500">{formatBytes(storageUsed)} used</div>
                    </div>
                </div>

                {/* Main Content Area */}
                <div className="flex-1 flex flex-col overflow-hidden">
                    
                    {/* Toolbar */}
                    <div className="p-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center bg-white dark:bg-gray-900">
                        
                        {/* Breadcrumbs */}
                        <div className="flex items-center text-sm text-gray-500">
                            {breadcrumb && breadcrumb.map((crumb, idx) => (
                                <React.Fragment key={crumb.id}>
                                    {idx > 0 && <ChevronRight className="w-4 h-4 mx-1" />}
                                    <Link 
                                        href={route('file-manager.index', { pasta_id: crumb.is_root ? null : crumb.id })}
                                        className="hover:text-indigo-600 transition-colors"
                                    >
                                        {crumb.nome}
                                    </Link>
                                </React.Fragment>
                            ))}
                            {secao === 'favoritos' && <span className="font-semibold text-gray-800 dark:text-gray-200">Favoritos</span>}
                            {secao === 'lixeira' && <span className="font-semibold text-gray-800 dark:text-gray-200">Lixeira</span>}
                        </div>

                        {/* Actions */}
                        <div className="flex items-center gap-3">
                            <div className="relative">
                                <Search className="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                                <input type="text" placeholder="Search files..." className="pl-9 pr-4 py-1.5 text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" />
                            </div>
                            
                            {secao === 'drive' && canUpload && (
                                <>
                                    <button onClick={() => setFolderModalOpen(true)} className="flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <FolderPlus className="w-4 h-4" /> New folder
                                    </button>
                                    <input 
                                        type="file" 
                                        ref={fileInputRef} 
                                        className="hidden" 
                                        onChange={handleFileUpload} 
                                    />
                                    <button 
                                        onClick={() => fileInputRef.current?.click()} 
                                        disabled={uploading}
                                        className="flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-white bg-emerald-500 border border-transparent rounded-md hover:bg-emerald-600 disabled:opacity-50"
                                    >
                                        <Upload className="w-4 h-4" /> {uploading ? 'Uploading...' : 'Upload'}
                                    </button>
                                </>
                            )}
                        </div>
                    </div>

                    {/* Grid */}
                    <div className="flex-1 overflow-y-auto p-6 bg-gray-50/50 dark:bg-gray-900/50">
                        {subpastas?.length === 0 && arquivos?.length === 0 && (
                            <div className="text-center py-20 text-gray-500">
                                <Folder className="w-16 h-16 mx-auto text-gray-300 mb-4" />
                                <p>Esta pasta está vazia.</p>
                            </div>
                        )}

                        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                            
                            {/* Renderizar Subpastas */}
                            {subpastas && subpastas.map(pasta => (
                                <div key={`p-${pasta.id}`} className="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow group relative">
                                    <Link href={route('file-manager.index', { pasta_id: pasta.id })} className="block text-center">
                                        <Folder className="w-12 h-12 mx-auto text-emerald-400 mb-3 opacity-80" />
                                        <h3 className="text-sm font-medium text-gray-800 dark:text-gray-200 truncate" title={pasta.nome}>{pasta.nome}</h3>
                                        <p className="text-xs text-gray-400 mt-1">—</p>
                                    </Link>
                                    
                                    {canDelete && (
                                        <button onClick={() => handleDeleteFolder(pasta.id)} className="absolute top-2 right-2 p-1 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity bg-white/80 rounded">
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    )}
                                </div>
                            ))}

                            {/* Renderizar Arquivos */}
                            {arquivos && arquivos.map(arq => (
                                <div key={`a-${arq.id}`} className="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow group relative flex flex-col">
                                    <div className="flex-1 flex items-center justify-center mb-3">
                                        {getFileIcon(arq.extensao)}
                                    </div>
                                    <div className="mt-auto">
                                        <h3 className="text-sm font-medium text-gray-800 dark:text-gray-200 truncate" title={arq.nome_original}>{arq.nome_original}</h3>
                                        <p className="text-xs text-gray-400 mt-1">{arq.tamanho_formatado}</p>
                                    </div>

                                    {/* Actions overlay */}
                                    <div className="absolute top-2 right-2 flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href={route('file-manager.download', arq.id)} className="p-1.5 text-gray-500 hover:text-indigo-600 bg-white shadow rounded-full">
                                            <Download className="w-3.5 h-3.5" />
                                        </a>
                                        <button onClick={() => router.patch(route('file-manager.arquivos.star', arq.id))} className={`p-1.5 shadow rounded-full bg-white ${arq.is_starred ? 'text-yellow-500' : 'text-gray-500 hover:text-yellow-500'}`}>
                                            <Star className="w-3.5 h-3.5" />
                                        </button>
                                        {secao === 'lixeira' ? (
                                            <button onClick={() => router.patch(route('file-manager.arquivos.restore', arq.id))} className="p-1.5 text-gray-500 hover:text-emerald-600 bg-white shadow rounded-full">
                                                <Upload className="w-3.5 h-3.5" /> {/* Restore icon idea */}
                                            </button>
                                        ) : (
                                            canDelete && (
                                                <button onClick={() => handleDeleteFile(arq.id)} className="p-1.5 text-gray-500 hover:text-red-500 bg-white shadow rounded-full">
                                                    <Trash2 className="w-3.5 h-3.5" />
                                                </button>
                                            )
                                        )}
                                    </div>
                                </div>
                            ))}

                        </div>
                    </div>
                </div>

            </div>

            {/* Modal Nova Pasta */}
            <Modal show={folderModalOpen} onClose={() => setFolderModalOpen(false)}>
                <form onSubmit={handleCreateFolder} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 mb-4">Nova Pasta</h2>
                    <div className="mb-4">
                        <label className="block text-sm font-medium text-gray-700">Nome da pasta</label>
                        <TextInput
                            type="text"
                            value={newFolderName}
                            onChange={e => setNewFolderName(e.target.value)}
                            className="mt-1 block w-full"
                            required
                            autoFocus
                        />
                    </div>
                    <div className="flex justify-end gap-2">
                        <SecondaryButton onClick={() => setFolderModalOpen(false)}>Cancelar</SecondaryButton>
                        <PrimaryButton type="submit">Criar</PrimaryButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
