export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            type={type}
            className={
                `inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-soft transition-all duration-200 hover:bg-slate-50 hover:text-indigo-600 hover:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white dark:hover:border-slate-500 dark:focus:ring-offset-slate-800 ${
                    disabled ? 'cursor-not-allowed' : 'hover:-translate-y-0.5 active:translate-y-0 active:scale-95'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
