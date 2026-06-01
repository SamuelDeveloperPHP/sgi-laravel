import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Save, ArrowLeft, UploadCloud, X } from 'lucide-react';
import { useState } from 'react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css';

export default function Form({ projeto, users = [] }) {
    const isEditing = !!projeto?.id;
    
    const { data, setData, post, processing, errors } = useForm({
        _method: isEditing ? 'put' : 'post',
        nomeProjeto: projeto?.nomeProjeto || '',
        descricao: projeto?.descricao || '',
        data_inicio: projeto?.data_inicio || '',
        data_fim: projeto?.data_fim || '',
        porc_concluido: projeto?.porc_concluido || 0,
        ativo: projeto?.ativo !== undefined ? projeto.ativo : 1,
        privacidade: projeto?.privacidade || 'Private',
        tags: projeto?.tags || [],
        responsavel_id: projeto?.responsavel_id || '',
        membros: projeto?.membros ? projeto.membros.map(m => m.id) : [],
        imagem_capa: null,
        arquivos_anexos: [],
    });

    const [newTag, setNewTag] = useState('');

    const handleAddTag = (e) => {
        if (e.key === 'Enter' && newTag.trim() !== '') {
            e.preventDefault();
            if (!data.tags.includes(newTag.trim())) {
                setData('tags', [...data.tags, newTag.trim()]);
            }
            setNewTag('');
        }
    };

    const removeTag = (tagToRemove) => {
        setData('tags', data.tags.filter(t => t !== tagToRemove));
    };

    const toggleMember = (userId) => {
        if (data.membros.includes(userId)) {
            setData('membros', data.membros.filter(id => id !== userId));
        } else {
            setData('membros', [...data.membros, userId]);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        if (isEditing) {
            post(route('projetos.update', projeto.id), {
                forceFormData: true
            });
        } else {
            post(route('projetos.store'), {
                forceFormData: true
            });
        }
    };

    const getAvatarName = (userId) => {
        const u = users.find(u => u.id === userId);
        return u ? u.name.substring(0, 1) : '?';
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-4">
                    <Link href={route('projetos.index')} className="text-gray-400 hover:text-indigo-600 transition-colors">
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {isEditing ? 'Editar Projeto' : 'Criar Projeto'}
                    </h2>
                </div>
            }
            fullWidth={true}
        >
            <Head title={isEditing ? 'Editar Projeto' : 'Criar Projeto'} />

            <div className="w-full mx-auto pb-12 bg-[#f3f6f9] dark:bg-gray-900 pt-6 px-4 sm:px-6 lg:px-8">
                
                <div className="flex items-center justify-between mb-6">
                    <h4 className="text-[15px] font-semibold text-gray-700 uppercase tracking-wider dark:text-gray-300">
                        {isEditing ? 'Editar Projeto' : 'Criar Projeto'}
                    </h4>
                    <div className="flex items-center gap-3">
                        <Link
                            href={route('projetos.index')}
                            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        >
                            Cancelar
                        </Link>
                        <PrimaryButton onClick={submit} disabled={processing} className="px-5 py-2">
                            {isEditing ? 'Salvar Alterações' : 'Criar Projeto'}
                        </PrimaryButton>
                    </div>
                </div>

                <form onSubmit={submit} className="flex flex-col xl:flex-row gap-6">
                    
                    {/* LEFT COLUMN */}
                    <div className="flex-1 space-y-6">
                        <div className="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-md">
                            <div className="p-5 space-y-6">
                                
                                <div>
                                    <InputLabel htmlFor="nomeProjeto" value="Título do Projeto" className="text-gray-700 dark:text-gray-300 font-medium mb-1.5" />
                                    <TextInput
                                        id="nomeProjeto"
                                        type="text"
                                        className="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm rounded-md"
                                        placeholder="Digite o título do projeto"
                                        value={data.nomeProjeto}
                                        onChange={(e) => setData('nomeProjeto', e.target.value)}
                                        required
                                    />
                                    {errors.nomeProjeto && <div className="mt-1.5 text-sm text-red-600">{errors.nomeProjeto}</div>}
                                </div>

                                <div>
                                    <InputLabel value="Imagem de Capa" className="text-gray-700 dark:text-gray-300 font-medium mb-1.5" />
                                    <div className="flex items-center border border-gray-300 dark:border-gray-600 rounded-md overflow-hidden bg-white dark:bg-gray-900 shadow-sm relative focus-within:ring-2 focus-within:ring-indigo-500">
                                        <input 
                                            type="file" 
                                            className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                            onChange={e => setData('imagem_capa', e.target.files[0])}
                                            accept="image/*"
                                        />
                                        <button type="button" className="px-4 py-2 bg-gray-100 dark:bg-gray-700 border-r border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors pointer-events-none">
                                            Escolher arquivo
                                        </button>
                                        <span className="px-3 text-sm text-gray-500 dark:text-gray-400 truncate">
                                            {data.imagem_capa ? data.imagem_capa.name : 'Nenhum arquivo escolhido'}
                                        </span>
                                    </div>
                                    {errors.imagem_capa && <div className="mt-1.5 text-sm text-red-600">{errors.imagem_capa}</div>}
                                </div>

                                <div>
                                    <InputLabel htmlFor="descricao" value="Descrição do Projeto" className="text-gray-700 dark:text-gray-300 font-medium mb-1.5" />
                                    <div className="border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-900 overflow-hidden [&_.quill]:border-0 [&_.ql-toolbar]:border-x-0 [&_.ql-toolbar]:border-t-0 [&_.ql-toolbar]:border-b-gray-300 dark:[&_.ql-toolbar]:border-b-gray-600 [&_.ql-container]:border-0 [&_.ql-editor]:min-h-[250px] dark:[&_.ql-editor]:text-gray-300">
                                        <ReactQuill 
                                            theme="snow" 
                                            value={data.descricao} 
                                            onChange={(val) => setData('descricao', val)}
                                            modules={{
                                                toolbar: [
                                                    [{ 'header': [1, 2, 3, false] }],
                                                    ['bold', 'italic', 'underline', 'link'],
                                                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                                    ['clean']
                                                ]
                                            }}
                                        />
                                    </div>
                                    {errors.descricao && <div className="mt-1.5 text-sm text-red-600">{errors.descricao}</div>}
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <InputLabel value="Prioridade" className="text-gray-700 dark:text-gray-300 font-medium mb-1.5" />
                                        <select className="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm rounded-md">
                                            <option>Alta</option>
                                            <option>Média</option>
                                            <option>Baixa</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel value="Status" className="text-gray-700 dark:text-gray-300 font-medium mb-1.5" />
                                        <select 
                                            className="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm rounded-md"
                                            value={data.ativo}
                                            onChange={e => setData('ativo', e.target.value)}
                                        >
                                            <option value={1}>Em Andamento</option>
                                            <option value={0}>Concluído</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="data_fim" value="Prazo de Entrega" className="text-gray-700 dark:text-gray-300 font-medium mb-1.5" />
                                        <TextInput
                                            id="data_fim"
                                            type="date"
                                            className="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm rounded-md text-gray-500"
                                            placeholder="Digite o prazo de entrega"
                                            value={data.data_fim || ''}
                                            onChange={(e) => setData('data_fim', e.target.value)}
                                        />
                                    </div>
                                </div>

                            </div>
                        </div>

                        {/* Attached Files */}
                        <div className="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-md">
                            <div className="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h4 className="text-[15px] font-semibold text-gray-800 dark:text-gray-200">Arquivos Anexados</h4>
                            </div>
                            <div className="p-5">
                                <p className="text-sm text-gray-500 mb-4">Adicione arquivos de referência aqui.</p>
                                <div className="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-md p-10 flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900/50 hover:bg-gray-100 dark:hover:bg-gray-800/80 transition-colors relative cursor-pointer">
                                    <input 
                                        type="file" 
                                        multiple
                                        className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                        onChange={e => setData('arquivos_anexos', Array.from(e.target.files))}
                                    />
                                    <div className="h-16 w-16 bg-blue-50 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-3 pointer-events-none">
                                        <UploadCloud className="w-8 h-8 text-blue-500" />
                                    </div>
                                    <p className="text-sm font-medium text-gray-900 dark:text-gray-200 pointer-events-none">Arraste os arquivos aqui ou clique para enviar.</p>
                                    {data.arquivos_anexos.length > 0 && (
                                        <p className="mt-2 text-sm text-blue-600 dark:text-blue-400 font-medium">{data.arquivos_anexos.length} arquivo(s) selecionado(s)</p>
                                    )}
                                </div>
                                {errors.arquivos_anexos && <div className="mt-1.5 text-sm text-red-600">{errors.arquivos_anexos}</div>}
                            </div>
                        </div>
                    </div>

                    {/* RIGHT COLUMN */}
                    <div className="w-full xl:w-[350px] space-y-6">
                        
                        {/* Privacy */}
                        <div className="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-md">
                            <div className="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h4 className="text-[15px] font-semibold text-gray-800 dark:text-gray-200">Privacidade</h4>
                            </div>
                            <div className="p-5">
                                <InputLabel value="Visibilidade" className="text-gray-700 dark:text-gray-300 font-medium mb-1.5" />
                                <select 
                                    className="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm rounded-md"
                                    value={data.privacidade}
                                    onChange={e => setData('privacidade', e.target.value)}
                                >
                                    <option value="Private">Privado</option>
                                    <option value="Public">Público</option>
                                    <option value="Team">Apenas Equipe</option>
                                </select>
                            </div>
                        </div>

                        {/* Tags */}
                        <div className="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-md">
                            <div className="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h4 className="text-[15px] font-semibold text-gray-800 dark:text-gray-200">Tags</h4>
                            </div>
                            <div className="p-5 space-y-5">
                                <div>
                                    <InputLabel value="Categorias" className="text-gray-700 dark:text-gray-300 font-medium mb-1.5" />
                                    <select className="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm rounded-md">
                                        <option>Design</option>
                                        <option>Desenvolvimento</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel value="Habilidades (Enter p/ adicionar)" className="text-gray-700 dark:text-gray-300 font-medium mb-2" />
                                    <TextInput
                                        type="text"
                                        className="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm rounded-md mb-3"
                                        placeholder="Adicionar tag..."
                                        value={newTag}
                                        onChange={(e) => setNewTag(e.target.value)}
                                        onKeyDown={handleAddTag}
                                    />
                                    <div className="flex flex-wrap gap-2">
                                        {data.tags.map(skill => (
                                            <span key={skill} className="inline-flex items-center px-2 py-1 rounded bg-[#405189] text-white text-[12px] font-medium tracking-wide">
                                                {skill}
                                                <button type="button" onClick={() => removeTag(skill)} className="ml-1 hover:text-red-300">
                                                    <X className="w-3 h-3" />
                                                </button>
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Members */}
                        <div className="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-md">
                            <div className="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h4 className="text-[15px] font-semibold text-gray-800 dark:text-gray-200">Membros</h4>
                            </div>
                            <div className="p-5 space-y-5">
                                <div>
                                    <InputLabel value="Líder do Projeto" className="text-gray-700 dark:text-gray-300 font-medium mb-1.5" />
                                    <select 
                                        className="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm rounded-md"
                                        value={data.responsavel_id}
                                        onChange={e => setData('responsavel_id', e.target.value)}
                                    >
                                        <option value="">Selecione um usuário...</option>
                                        {users.map(u => (
                                            <option key={u.id} value={u.id}>{u.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <InputLabel value="Membros da Equipe" className="text-gray-700 dark:text-gray-300 font-medium mb-2" />
                                    
                                    {/* Avatars */}
                                    <div className="flex items-center -space-x-2 mb-4">
                                        {data.membros.map(userId => (
                                            <img 
                                                key={`av-${userId}`}
                                                className="inline-block h-8 w-8 rounded-full ring-2 ring-white dark:ring-gray-800" 
                                                src={`https://ui-avatars.com/api/?name=${getAvatarName(userId)}&background=random`} 
                                                title={getAvatarName(userId)}
                                            />
                                        ))}
                                        {data.membros.length === 0 && (
                                            <span className="text-sm text-gray-500">Nenhum membro selecionado</span>
                                        )}
                                    </div>

                                    {/* Multi-select check list */}
                                    <div className="max-h-40 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-md p-2 space-y-1 bg-gray-50 dark:bg-gray-900">
                                        {users.map(u => (
                                            <label key={`chk-${u.id}`} className="flex items-center p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded cursor-pointer">
                                                <input 
                                                    type="checkbox"
                                                    className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 mr-2"
                                                    checked={data.membros.includes(u.id)}
                                                    onChange={() => toggleMember(u.id)}
                                                />
                                                <span className="text-sm text-gray-700 dark:text-gray-300">{u.name}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
