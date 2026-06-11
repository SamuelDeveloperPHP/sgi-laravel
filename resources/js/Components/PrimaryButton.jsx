export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `inline-flex items-center justify-center rounded-lg border border-transparent bg-gradient-to-r from-indigo-600 to-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-soft transition-all duration-200 hover:from-indigo-700 hover:to-blue-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 ${
                    disabled ? 'opacity-50 cursor-not-allowed' : 'hover:-translate-y-0.5 active:translate-y-0 active:scale-95'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
