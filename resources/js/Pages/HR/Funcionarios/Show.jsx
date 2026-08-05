import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, User, Building, Calendar, Mail, Phone, CreditCard, Briefcase, CalendarDays, Receipt } from 'lucide-react';

export default function Show({ auth, funcionario }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Detalhes do Funcionário</h2>}
        >
            <Head title={`Funcionário - ${funcionario.nome}`} />

            <div className="w-full sm:px-6 lg:px-8 space-y-6">
                
                <div className="flex items-center gap-4">
                    <Link 
                        href={route('admin.funcionarios.index')}
                        className="p-2 text-slate-500 hover:text-slate-700 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 transition-colors"
                    >
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Perfil: {funcionario.nome}</h1>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Cartão de Informações Pessoais */}
                    <div className="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/50 p-6">
                        <div className="flex items-center gap-4 mb-6 pb-6 border-b border-slate-100 dark:border-slate-700">
                            <div className="w-16 h-16 bg-teal-100 text-teal-600 dark:bg-teal-900/50 dark:text-teal-400 rounded-full flex items-center justify-center text-2xl font-bold">
                                {funcionario.nome.charAt(0)}
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-slate-900 dark:text-white">{funcionario.nome}</h3>
                                <span className={`inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                    funcionario.status === 'Ativo' ? 'bg-emerald-100 text-emerald-700' : 
                                    funcionario.status === 'Férias' ? 'bg-amber-100 text-amber-700' : 
                                    'bg-slate-100 text-slate-700'
                                }`}>
                                    {funcionario.status}
                                </span>
                            </div>
                        </div>

                        <div className="space-y-4">
                            <div className="flex items-start gap-3">
                                <CreditCard className="w-5 h-5 text-slate-400 mt-0.5" />
                                <div>
                                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">CPF</p>
                                    <p className="text-slate-900 dark:text-slate-200">{funcionario.cpf || 'Não informado'}</p>
                                </div>
                            </div>
                            <div className="flex items-start gap-3">
                                <Briefcase className="w-5 h-5 text-slate-400 mt-0.5" />
                                <div>
                                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">Matrícula</p>
                                    <p className="text-slate-900 dark:text-slate-200">{funcionario.matricula || 'Não informado'}</p>
                                </div>
                            </div>
                            <div className="flex items-start gap-3">
                                <Building className="w-5 h-5 text-slate-400 mt-0.5" />
                                <div>
                                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">Empresa Vinculada</p>
                                    <p className="text-slate-900 dark:text-slate-200">{funcionario.company?.razao_social}</p>
                                </div>
                            </div>
                            <div className="flex items-start gap-3">
                                <Calendar className="w-5 h-5 text-slate-400 mt-0.5" />
                                <div>
                                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">Admissão</p>
                                    <p className="text-slate-900 dark:text-slate-200">
                                        {funcionario.data_admissao ? new Date(funcionario.data_admissao).toLocaleDateString('pt-BR') : 'Não informado'}
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-start gap-3">
                                <Mail className="w-5 h-5 text-slate-400 mt-0.5" />
                                <div>
                                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">E-mail</p>
                                    <p className="text-slate-900 dark:text-slate-200">{funcionario.email || 'Não informado'}</p>
                                </div>
                            </div>
                            <div className="flex items-start gap-3">
                                <Phone className="w-5 h-5 text-slate-400 mt-0.5" />
                                <div>
                                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">Telefone</p>
                                    <p className="text-slate-900 dark:text-slate-200">{funcionario.telefone || 'Não informado'}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Histórico de Férias */}
                    <div className="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/50 p-6 flex flex-col">
                        <div className="flex items-center justify-between mb-6 pb-4 border-b border-slate-100 dark:border-slate-700">
                            <h3 className="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <CalendarDays className="w-5 h-5 text-indigo-500" />
                                Histórico de Férias
                            </h3>
                            <Link 
                                href={route('admin.ferias.index')}
                                className="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                            >
                                Gerenciar Férias
                            </Link>
                        </div>

                        <div className="flex-1 overflow-y-auto">
                            {funcionario.ferias && funcionario.ferias.length > 0 ? (
                                <div className="space-y-4">
                                    {funcionario.ferias.map((f) => (
                                        <div key={f.id} className="border border-slate-200 dark:border-slate-700 rounded-lg p-4 bg-slate-50 dark:bg-slate-900/50 hover:border-indigo-300 transition-colors">
                                            <div className="flex justify-between items-start mb-3">
                                                <div>
                                                    <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Período Aquisitivo</p>
                                                    <p className="text-slate-800 dark:text-slate-200 font-medium">
                                                        {f.periodo_aquisitivo_inicio ? new Date(f.periodo_aquisitivo_inicio).toLocaleDateString('pt-BR') : '-'} a {f.periodo_aquisitivo_fim ? new Date(f.periodo_aquisitivo_fim).toLocaleDateString('pt-BR') : '-'}
                                                    </p>
                                                </div>
                                                <span className={`px-2.5 py-1 rounded-md text-xs font-bold ${
                                                    f.status === 'Programada' ? 'bg-blue-100 text-blue-700' : 
                                                    f.status === 'Em Gozo' ? 'bg-amber-100 text-amber-700' : 
                                                    f.status === 'Concluída' ? 'bg-emerald-100 text-emerald-700' : 
                                                    'bg-slate-100 text-slate-700'
                                                }`}>
                                                    {f.status}
                                                </span>
                                            </div>

                                            <div className="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                                                <div className="bg-white dark:bg-slate-800 p-2 rounded shadow-sm border border-slate-100 dark:border-slate-700">
                                                    <span className="block text-xs text-slate-400">Dias de Direito</span>
                                                    <span className="font-semibold text-slate-700 dark:text-slate-300">{f.dias_direito}</span>
                                                </div>
                                                <div className="bg-white dark:bg-slate-800 p-2 rounded shadow-sm border border-slate-100 dark:border-slate-700">
                                                    <span className="block text-xs text-slate-400">1º Gozo</span>
                                                    <span className="font-semibold text-slate-700 dark:text-slate-300">
                                                        {f.gozo_1_inicio ? new Date(f.gozo_1_inicio).toLocaleDateString('pt-BR') : '-'}
                                                    </span>
                                                </div>
                                                <div className="bg-white dark:bg-slate-800 p-2 rounded shadow-sm border border-slate-100 dark:border-slate-700">
                                                    <span className="block text-xs text-slate-400">Abono?</span>
                                                    <span className="font-semibold text-slate-700 dark:text-slate-300">
                                                        {f.opcao_abono ? `Sim (${f.dias_abono}d)` : 'Não'}
                                                    </span>
                                                </div>
                                                <div className="flex items-center justify-end">
                                                    <Link 
                                                        href={route('admin.ferias.show', f.id)}
                                                        className="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:hover:bg-indigo-500/20 px-3 py-1.5 rounded-lg transition-colors"
                                                    >
                                                        <Receipt className="w-4 h-4" /> Detalhes
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-10 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-dashed border-slate-200 dark:border-slate-700">
                                    <CalendarDays className="w-10 h-10 text-slate-300 mx-auto mb-3" />
                                    <p className="text-slate-500 font-medium">Nenhum registro de férias para este funcionário.</p>
                                </div>
                            )}
                        </div>
                    </div>
                    {/* Detalhes Expandidos */}
                    <div className="lg:col-span-3 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700/50 p-6 mt-6">
                        <h3 className="text-lg font-bold text-slate-900 dark:text-white mb-6 pb-4 border-b border-slate-100 dark:border-slate-700 flex items-center gap-2">
                            <User className="w-5 h-5 text-indigo-500" /> Ficha Completa
                        </h3>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 text-sm">
                            {/* Pessoais */}
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Data de Nascimento / Idade</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">
                                    {funcionario.data_nascimento ? new Date(funcionario.data_nascimento).toLocaleDateString('pt-BR') : '-'} 
                                    {funcionario.idade ? ` (${funcionario.idade} anos)` : ''}
                                </p>
                            </div>
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Gênero</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">{funcionario.genero || '-'}</p>
                            </div>
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Estado Civil</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">{funcionario.estado_civil || '-'}</p>
                            </div>
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Dependentes</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">{funcionario.dependentes || 0}</p>
                            </div>
                            
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Nacionalidade / Naturalidade</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">
                                    {funcionario.nacionalidade || '-'} / {funcionario.naturalidade || '-'}
                                </p>
                            </div>
                            <div className="lg:col-span-2">
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Filiação</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">
                                    Mãe: {funcionario.nome_mae || '-'} <br /> 
                                    Pai: {funcionario.nome_pai || '-'}
                                </p>
                            </div>
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Celular</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">{funcionario.celular || '-'}</p>
                            </div>

                            {/* Documentos */}
                            <div className="lg:col-span-4 mt-2">
                                <h4 className="font-bold text-slate-700 dark:text-slate-300 border-b border-slate-100 dark:border-slate-700 pb-1 mb-3">Documentos</h4>
                            </div>
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">RG</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">{funcionario.rg || '-'}</p>
                            </div>
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">CTPS</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">{funcionario.ctps || '-'}</p>
                            </div>
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">PIS</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">{funcionario.pis || '-'}</p>
                            </div>
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Título de Eleitor</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">{funcionario.titulo_eleitor || '-'}</p>
                            </div>

                            {/* Funcionais */}
                            <div className="lg:col-span-4 mt-2">
                                <h4 className="font-bold text-slate-700 dark:text-slate-300 border-b border-slate-100 dark:border-slate-700 pb-1 mb-3">Contrato e Função</h4>
                            </div>
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Departamento / Área</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">{funcionario.area?.nome || '-'}</p>
                            </div>
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Cargo</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">{funcionario.cargo?.nome || '-'}</p>
                            </div>
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Salário Bruto</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">
                                    {funcionario.salario_bruto ? `R$ ${funcionario.salario_bruto}` : '-'}
                                </p>
                            </div>
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Data de Demissão</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">
                                    {funcionario.data_demissao ? new Date(funcionario.data_demissao).toLocaleDateString('pt-BR') : '-'}
                                </p>
                            </div>
                            
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Carga Horária Mensal</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">{funcionario.carga_horaria_mensal ? `${funcionario.carga_horaria_mensal}h` : '-'}</p>
                            </div>
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Horário de Trabalho Padrão</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">{funcionario.horario_trabalho || '-'}</p>
                            </div>
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Férias Pagas em Parcelas</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">{funcionario.parcelas_ferias ? `${funcionario.parcelas_ferias}x` : '-'}</p>
                            </div>
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">13º Salário (Datas / Parcelas)</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">
                                    {funcionario.data_decimo_terceiro || '-'} {funcionario.parcelas_decimo_terceiro ? `(${funcionario.parcelas_decimo_terceiro}x)` : ''}
                                </p>
                            </div>

                            {/* Bancarios e Outros */}
                            <div className="lg:col-span-4 mt-2">
                                <h4 className="font-bold text-slate-700 dark:text-slate-300 border-b border-slate-100 dark:border-slate-700 pb-1 mb-3">Dados Bancários e Endereço</h4>
                            </div>
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Banco e Agência</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">
                                    {funcionario.banco || '-'} / {funcionario.agencia || '-'}
                                </p>
                            </div>
                            <div>
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Conta Corrente</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">{funcionario.conta_corrente || '-'}</p>
                            </div>
                            <div className="lg:col-span-2">
                                <p className="text-slate-400 font-medium text-xs uppercase mb-1">Endereço</p>
                                <p className="font-semibold text-slate-800 dark:text-slate-200">
                                    {funcionario.logradouro ? `${funcionario.logradouro}, ${funcionario.numero} ${funcionario.complemento ? ` - ${funcionario.complemento}` : ''} - ${funcionario.bairro}. ${funcionario.cidade}/${funcionario.estado} - CEP: ${funcionario.cep}` : 'Não cadastrado'}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
