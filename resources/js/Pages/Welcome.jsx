import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth, laravelVersion, phpVersion }) {
    return (
        <>
            <Head title="SGI - Qualidade, Segurança, Meio Ambiente e Saúde" />
            
            <div className="min-h-screen bg-slate-50 text-slate-800 font-sans selection:bg-teal-500 selection:text-white">
                {/* Navbar */}
                <header className="fixed w-full top-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-200 shadow-sm">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center h-20">
                            <div className="flex-shrink-0 flex items-center gap-2">
                                <div className="w-10 h-10 bg-gradient-to-br from-teal-500 to-emerald-600 rounded-lg flex items-center justify-center text-white font-bold text-xl shadow-lg">
                                    SGI
                                </div>
                                <span className="font-bold text-xl tracking-tight text-slate-900">
                                    QSMS
                                </span>
                            </div>
                            <nav className="hidden md:flex space-x-8">
                                <a href="#sobre" className="text-slate-600 hover:text-teal-600 font-medium transition-colors">Sobre</a>
                                <a href="#recursos" className="text-slate-600 hover:text-teal-600 font-medium transition-colors">Recursos</a>
                                <a href="#modulos" className="text-slate-600 hover:text-teal-600 font-medium transition-colors">Módulos</a>
                            </nav>
                            <div className="flex items-center space-x-4">
                                {auth?.user ? (
                                    <Link
                                        href={route('dashboard')}
                                        className="inline-flex items-center justify-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-teal-600 hover:bg-teal-700 shadow-md hover:shadow-lg transition-all"
                                    >
                                        Acessar Painel
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href={route('login')}
                                            className="text-slate-600 hover:text-teal-600 font-medium transition-colors px-4 py-2"
                                        >
                                            Entrar
                                        </Link>
                                        <Link
                                            href={route('register')}
                                            className="inline-flex items-center justify-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all"
                                        >
                                            Cadastre-se
                                        </Link>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                </header>

                {/* Hero Section */}
                <section className="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
                    <div className="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-5"></div>
                    <div className="absolute -top-24 -right-24 w-96 h-96 bg-teal-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
                    <div className="absolute top-48 -left-24 w-72 h-72 bg-emerald-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
                    
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
                        <span className="inline-block py-1 px-3 rounded-full bg-teal-50 border border-teal-100 text-teal-600 text-sm font-semibold tracking-wide mb-6">
                            Gestão Inteligente e Integrada
                        </span>
                        <h1 className="text-5xl md:text-6xl lg:text-7xl font-extrabold text-slate-900 tracking-tight leading-tight mb-8">
                            Transforme sua gestão de <br className="hidden md:block"/>
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-teal-600 to-emerald-500">
                                Qualidade, Segurança e Meio Ambiente
                            </span>
                        </h1>
                        <p className="mt-4 max-w-3xl mx-auto text-xl text-slate-600 mb-10 leading-relaxed">
                            Controle processos, documentos, auditorias e conformidade legal em uma única plataforma. 
                            O SGI apoia a sua empresa na busca pela excelência operacional.
                        </p>
                        <div className="flex justify-center gap-4">
                            <a href="#modulos" className="px-8 py-4 bg-slate-900 text-white rounded-xl font-medium hover:bg-slate-800 shadow-xl hover:shadow-2xl transition-all hover:-translate-y-1">
                                Conheça os Módulos
                            </a>
                            {auth?.user ? null : (
                                <Link href={route('register')} className="px-8 py-4 bg-white text-slate-900 rounded-xl font-medium border border-slate-200 hover:border-teal-500 hover:text-teal-600 shadow-sm transition-all">
                                    Começar Agora
                                </Link>
                            )}
                        </div>
                    </div>
                </section>

                {/* Pilares Section */}
                <section id="sobre" className="py-20 bg-white">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-16">
                            <h2 className="text-3xl font-bold text-slate-900">Os Pilares do SGI</h2>
                            <p className="mt-4 text-slate-600 max-w-2xl mx-auto">Nossa plataforma foi desenhada para abranger todas as áreas críticas da conformidade corporativa.</p>
                        </div>
                        <div className="grid md:grid-cols-4 gap-8">
                            {[
                                { title: 'SGQ', desc: 'Sistema de Gestão da Qualidade', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', color: 'text-blue-500', bg: 'bg-blue-50' },
                                { title: 'SST', desc: 'Segurança e Saúde do Trabalho', icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', color: 'text-amber-500', bg: 'bg-amber-50' },
                                { title: 'SGA', desc: 'Sistema de Gestão Ambiental', icon: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z', color: 'text-emerald-500', bg: 'bg-emerald-50' },
                                { title: 'QSMS', desc: 'Qualidade, Segurança, Meio Ambiente e Saúde', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', color: 'text-indigo-500', bg: 'bg-indigo-50' }
                            ].map((pilar, i) => (
                                <div key={i} className="p-8 rounded-2xl bg-white border border-slate-100 shadow-sm hover:shadow-xl transition-shadow group">
                                    <div className={`w-14 h-14 ${pilar.bg} rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform`}>
                                        <svg className={`w-7 h-7 ${pilar.color}`} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                            <path strokeLinecap="round" strokeLinejoin="round" d={pilar.icon} />
                                        </svg>
                                    </div>
                                    <h3 className="text-xl font-bold text-slate-900 mb-2">{pilar.title}</h3>
                                    <p className="text-slate-600">{pilar.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Features Highlights */}
                <section id="recursos" className="py-20 bg-slate-50">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="grid lg:grid-cols-2 gap-16 items-center">
                            <div>
                                <h2 className="text-3xl font-bold text-slate-900 mb-6">Tudo o que você precisa em uma única plataforma</h2>
                                <p className="text-lg text-slate-600 mb-8">
                                    Diga adeus às planilhas soltas e controle tudo com eficiência, rastreabilidade e segurança.
                                </p>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    {[
                                        'Controle de documentos e revisões',
                                        'Gestão de não conformidades',
                                        'Planos de ação (5W2H)',
                                        'Auditorias internas e externas',
                                        'Inspeções de segurança',
                                        'APR, PT, DDS, EPC, EPI',
                                        'Gestão de riscos',
                                        'Evidências fotográficas',
                                        'Fluxos de aprovação',
                                        'Notificações automatizadas',
                                        'Histórico e rastreabilidade',
                                        'Multiempresa e perfis'
                                    ].map((feature, i) => (
                                        <div key={i} className="flex items-center gap-3">
                                            <div className="flex-shrink-0 w-6 h-6 rounded-full bg-teal-100 flex items-center justify-center">
                                                <svg className="w-4 h-4 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            <span className="text-slate-700 font-medium">{feature}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                            <div className="relative">
                                <div className="absolute inset-0 bg-gradient-to-r from-teal-500 to-emerald-500 rounded-2xl transform rotate-3 opacity-20"></div>
                                <div className="bg-white rounded-2xl shadow-2xl p-8 relative z-10 border border-slate-100">
                                    <div className="space-y-6">
                                        <div className="flex items-center justify-between pb-6 border-b border-slate-100">
                                            <div>
                                                <h4 className="font-bold text-slate-900">Indicadores de Desempenho</h4>
                                                <p className="text-sm text-slate-500">Métricas em tempo real</p>
                                            </div>
                                            <span className="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">Ao vivo</span>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="p-4 bg-slate-50 rounded-xl">
                                                <p className="text-sm text-slate-500 mb-1">Ações Concluídas</p>
                                                <p className="text-2xl font-bold text-slate-900">87%</p>
                                                <div className="w-full bg-slate-200 h-2 mt-2 rounded-full overflow-hidden">
                                                    <div className="bg-emerald-500 h-full w-[87%]"></div>
                                                </div>
                                            </div>
                                            <div className="p-4 bg-slate-50 rounded-xl">
                                                <p className="text-sm text-slate-500 mb-1">NCs Abertas</p>
                                                <p className="text-2xl font-bold text-rose-600">12</p>
                                                <p className="text-xs text-slate-400 mt-2">↓ 3 desde o último mês</p>
                                            </div>
                                            <div className="p-4 bg-slate-50 rounded-xl col-span-2">
                                                <p className="text-sm text-slate-500 mb-1">Documentos Vigentes</p>
                                                <p className="text-2xl font-bold text-slate-900">1,240</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Modules Grid */}
                <section id="modulos" className="py-20 bg-white">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-16">
                            <h2 className="text-3xl font-bold text-slate-900">Módulos do Sistema</h2>
                            <p className="mt-4 text-slate-600 max-w-2xl mx-auto">Conheça detalhadamente as ferramentas que irão transformar a gestão da sua empresa.</p>
                        </div>
                        
                        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                            {/* Module 1 */}
                            <div className="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-xl transition-all hover:-translate-y-1">
                                <div className="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center mb-6">
                                    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                </div>
                                <h3 className="text-xl font-bold text-slate-900 mb-4">Gestão de Documentos</h3>
                                <ul className="space-y-2 text-sm text-slate-600">
                                    <li>• Controle de versão e revisões</li>
                                    <li>• Upload de PDF, Word, Imagens</li>
                                    <li>• Status: Rascunho, Aprovado, Obsoleto</li>
                                    <li>• Aprovação por responsáveis</li>
                                    <li>• Notificações de vencimento</li>
                                </ul>
                            </div>

                            {/* Module 2 */}
                            <div className="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-xl transition-all hover:-translate-y-1">
                                <div className="w-12 h-12 bg-rose-100 text-rose-600 rounded-lg flex items-center justify-center mb-6">
                                    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                </div>
                                <h3 className="text-xl font-bold text-slate-900 mb-4">Não Conformidades</h3>
                                <ul className="space-y-2 text-sm text-slate-600">
                                    <li>• Registro por origem e classificação</li>
                                    <li>• Análise de Causa Raiz</li>
                                    <li>• Ações imediatas e corretivas</li>
                                    <li>• Validação de eficácia</li>
                                    <li>• Histórico de alterações</li>
                                </ul>
                            </div>

                            {/* Module 3 */}
                            <div className="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-xl transition-all hover:-translate-y-1">
                                <div className="w-12 h-12 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center mb-6">
                                    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                </div>
                                <h3 className="text-xl font-bold text-slate-900 mb-4">Planos de Ação</h3>
                                <ul className="space-y-2 text-sm text-slate-600">
                                    <li>• Metodologia 5W2H</li>
                                    <li>• Gestão de prazos e prioridades</li>
                                    <li>• Controle de custos previstos</li>
                                    <li>• Evidências de conclusão</li>
                                    <li>• Justificativas e reprogramações</li>
                                </ul>
                            </div>

                            {/* Module 4 */}
                            <div className="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-xl transition-all hover:-translate-y-1">
                                <div className="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center mb-6">
                                    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                                </div>
                                <h3 className="text-xl font-bold text-slate-900 mb-4">Auditorias</h3>
                                <ul className="space-y-2 text-sm text-slate-600">
                                    <li>• Planos anuais de auditoria</li>
                                    <li>• Checklist e itens auditados</li>
                                    <li>• Auditorias internas e externas</li>
                                    <li>• Relatórios e constatações</li>
                                    <li>• Vínculo automático com NCs</li>
                                </ul>
                            </div>

                            {/* Module 5 */}
                            <div className="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-xl transition-all hover:-translate-y-1">
                                <div className="w-12 h-12 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center mb-6">
                                    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                </div>
                                <h3 className="text-xl font-bold text-slate-900 mb-4">Segurança do Trabalho</h3>
                                <ul className="space-y-2 text-sm text-slate-600">
                                    <li>• Inspeções, APR, PT e DDS</li>
                                    <li>• Gestão e entrega de EPIs</li>
                                    <li>• Controle de treinamentos</li>
                                    <li>• Investigação de acidentes</li>
                                    <li>• Matriz de riscos</li>
                                </ul>
                            </div>

                            {/* Module 6 */}
                            <div className="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-xl transition-all hover:-translate-y-1">
                                <div className="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center mb-6">
                                    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <h3 className="text-xl font-bold text-slate-900 mb-4">Meio Ambiente</h3>
                                <ul className="space-y-2 text-sm text-slate-600">
                                    <li>• Ocorrências ambientais</li>
                                    <li>• Controle e destinação de resíduos</li>
                                    <li>• Gestão de licenças e condicionantes</li>
                                    <li>• Monitoramentos ambientais</li>
                                    <li>• Planos de ação específicos</li>
                                </ul>
                            </div>
                        </div>

                        {/* Dashboard Feature Highlight */}
                        <div className="mt-12 bg-slate-900 rounded-3xl p-8 lg:p-12 text-white overflow-hidden relative">
                            <div className="absolute top-0 right-0 w-64 h-64 bg-teal-500 rounded-full mix-blend-screen filter blur-3xl opacity-20"></div>
                            <div className="relative z-10 grid lg:grid-cols-2 gap-12 items-center">
                                <div>
                                    <h3 className="text-3xl font-bold mb-6">Indicadores e Dashboards</h3>
                                    <p className="text-slate-300 mb-8 text-lg">
                                        Tenha a visão macro do seu negócio. Acompanhe não conformidades abertas, ações atrasadas, taxas de reincidência e muito mais em painéis interativos.
                                    </p>
                                    <ul className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 text-slate-300">
                                        <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-teal-400"></div> Gráficos dinâmicos</li>
                                        <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-teal-400"></div> Filtros por período/setor</li>
                                        <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-teal-400"></div> Relatórios gerenciais</li>
                                        <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-teal-400"></div> Exportação de dados</li>
                                    </ul>
                                </div>
                                <div className="bg-slate-800 border border-slate-700 rounded-xl p-6 shadow-2xl">
                                    <div className="flex gap-4 mb-6">
                                        <div className="flex-1 h-24 bg-slate-700 rounded-lg p-3">
                                            <div className="w-8 h-2 bg-slate-600 rounded mb-2"></div>
                                            <div className="w-16 h-6 bg-teal-500 rounded mb-2"></div>
                                            <div className="w-full h-1 bg-slate-600 rounded"></div>
                                        </div>
                                        <div className="flex-1 h-24 bg-slate-700 rounded-lg p-3">
                                            <div className="w-8 h-2 bg-slate-600 rounded mb-2"></div>
                                            <div className="w-16 h-6 bg-rose-500 rounded mb-2"></div>
                                            <div className="w-full h-1 bg-slate-600 rounded"></div>
                                        </div>
                                    </div>
                                    <div className="h-40 bg-slate-700 rounded-lg flex items-end p-4 gap-2">
                                        {[40, 70, 45, 90, 65, 80, 50].map((h, i) => (
                                            <div key={i} className="flex-1 bg-teal-500/50 hover:bg-teal-400 rounded-t-sm transition-all" style={{height: `${h}%`}}></div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="bg-white border-t border-slate-200 pt-16 pb-8">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                        <div className="inline-flex items-center gap-2 mb-6">
                            <div className="w-8 h-8 bg-gradient-to-br from-teal-500 to-emerald-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">
                                SGI
                            </div>
                            <span className="font-bold text-lg text-slate-900">QSMS</span>
                        </div>
                        <p className="text-slate-500 mb-8 max-w-lg mx-auto">
                            A solução definitiva para apoiar a gestão integrada da sua empresa. Transformando processos através da tecnologia.
                        </p>
                        <div className="flex justify-center gap-6 mb-8">
                            <a href="#" className="text-slate-400 hover:text-teal-600">Termos de Uso</a>
                            <a href="#" className="text-slate-400 hover:text-teal-600">Privacidade</a>
                            <a href="#" className="text-slate-400 hover:text-teal-600">Contato</a>
                        </div>
                        <p className="text-sm text-slate-400">
                            &copy; {new Date().getFullYear()} SGI QSMS. Todos os direitos reservados. <br/>
                            <span className="text-xs mt-2 inline-block">Laravel v{laravelVersion} (PHP v{phpVersion})</span>
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
