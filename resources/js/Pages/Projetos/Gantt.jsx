import { Head } from '@inertiajs/react';

export default function GanttPage({ projeto }) {
    const ganttUrl = typeof window !== 'undefined' && window.route
        ? window.route('projetos.gantt', projeto?.id)
        : `/projetos/${projeto?.id}/gantt`;

    return (
        <>
            <Head title={`Cronograma (Gantt) - ${projeto?.nomeProjeto || 'Projeto'}`} />
            <div style={{ width: '100vw', height: '100vh', margin: 0, padding: 0, overflow: 'hidden' }}>
                <iframe
                    src={ganttUrl}
                    style={{ width: '100%', height: '100%', border: 'none' }}
                    title={`Gantt - ${projeto?.nomeProjeto}`}
                />
            </div>
        </>
    );
}
