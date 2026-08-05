import { useEffect } from 'react';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';

const baseButtonClass = 'rounded border px-2 py-1 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-40';

function ToolbarButton({ active = false, children, ...props }) {
    const stateClass = active
        ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
        : 'border-slate-300 text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700';

    return (
        <button type="button" className={`${baseButtonClass} ${stateClass}`} {...props}>
            {children}
        </button>
    );
}

export default function RichTextEditor({ value = '', onChange, className = '', style }) {
    const editor = useEditor({
        extensions: [
            StarterKit.configure({
                link: {
                    openOnClick: false,
                    autolink: true,
                    defaultProtocol: 'https',
                    protocols: ['http', 'https', 'mailto', 'tel'],
                    HTMLAttributes: {
                        rel: 'noopener noreferrer',
                    },
                },
                underline: {},
            }),
        ],
        content: value,
        editorProps: {
            attributes: {
                class: 'rich-text-content max-w-none min-h-[420px] p-5 focus:outline-none',
            },
        },
        onUpdate: ({ editor: instance }) => onChange?.(instance.getHTML()),
    });

    useEffect(() => {
        if (editor && value !== editor.getHTML()) {
            editor.commands.setContent(value || '', { emitUpdate: false });
        }
    }, [editor, value]);

    if (!editor) return null;

    const setLink = () => {
        const current = editor.getAttributes('link').href || '';
        const href = window.prompt('URL do link', current);
        if (href === null) return;
        if (href === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
            return;
        }
        editor.chain().focus().extendMarkRange('link').setLink({ href }).run();
    };

    const blockType = editor.isActive('heading', { level: 1 }) ? 'h1'
        : editor.isActive('heading', { level: 2 }) ? 'h2'
            : editor.isActive('heading', { level: 3 }) ? 'h3'
                : 'paragraph';

    const changeBlockType = (event) => {
        const type = event.target.value;
        if (type === 'paragraph') {
            editor.chain().focus().setParagraph().run();
            return;
        }
        editor.chain().focus().setHeading({ level: Number(type.replace('h', '')) }).run();
    };

    return (
        <div className={`rich-text-editor overflow-hidden rounded-md border border-slate-300 bg-white dark:border-slate-600 dark:bg-slate-900 ${className}`} style={style}>
            <div className="flex flex-wrap items-center gap-1 border-b border-slate-300 bg-slate-50 p-2 dark:border-slate-600 dark:bg-slate-800" role="toolbar" aria-label="Formatação de texto">
                <select
                    aria-label="Formato do parágrafo"
                    value={blockType}
                    onChange={changeBlockType}
                    className="rounded border-slate-300 bg-white py-1 pl-2 pr-8 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                >
                    <option value="paragraph">Parágrafo</option>
                    <option value="h1">Título 1</option>
                    <option value="h2">Título 2</option>
                    <option value="h3">Título 3</option>
                </select>

                <span className="mx-1 h-6 w-px bg-slate-300 dark:bg-slate-600" aria-hidden="true" />

                <ToolbarButton active={editor.isActive('bold')} onClick={() => editor.chain().focus().toggleBold().run()} aria-label="Negrito"><strong>B</strong></ToolbarButton>
                <ToolbarButton active={editor.isActive('italic')} onClick={() => editor.chain().focus().toggleItalic().run()} aria-label="Itálico"><em>I</em></ToolbarButton>
                <ToolbarButton active={editor.isActive('underline')} onClick={() => editor.chain().focus().toggleUnderline().run()} aria-label="Sublinhado"><u>U</u></ToolbarButton>
                <ToolbarButton active={editor.isActive('strike')} onClick={() => editor.chain().focus().toggleStrike().run()} aria-label="Tachado"><s>S</s></ToolbarButton>

                <span className="mx-1 h-6 w-px bg-slate-300 dark:bg-slate-600" aria-hidden="true" />

                <ToolbarButton active={editor.isActive('bulletList')} onClick={() => editor.chain().focus().toggleBulletList().run()}>Lista</ToolbarButton>
                <ToolbarButton active={editor.isActive('orderedList')} onClick={() => editor.chain().focus().toggleOrderedList().run()}>Numerada</ToolbarButton>
                <ToolbarButton active={editor.isActive('blockquote')} onClick={() => editor.chain().focus().toggleBlockquote().run()}>Citação</ToolbarButton>
                <ToolbarButton active={editor.isActive('codeBlock')} onClick={() => editor.chain().focus().toggleCodeBlock().run()}>Código</ToolbarButton>
                <ToolbarButton onClick={() => editor.chain().focus().setHorizontalRule().run()}>Linha</ToolbarButton>
                <ToolbarButton active={editor.isActive('link')} onClick={setLink}>Link</ToolbarButton>

                <span className="mx-1 h-6 w-px bg-slate-300 dark:bg-slate-600" aria-hidden="true" />

                <ToolbarButton disabled={!editor.can().chain().focus().undo().run()} onClick={() => editor.chain().focus().undo().run()}>Desfazer</ToolbarButton>
                <ToolbarButton disabled={!editor.can().chain().focus().redo().run()} onClick={() => editor.chain().focus().redo().run()}>Refazer</ToolbarButton>
                <ToolbarButton onClick={() => editor.chain().focus().unsetAllMarks().clearNodes().run()}>Limpar</ToolbarButton>
            </div>
            <EditorContent editor={editor} />
        </div>
    );
}
