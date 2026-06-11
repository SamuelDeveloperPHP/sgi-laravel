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
                `inline-flex items-center justify-center rounded-lg border border-transparent bg-gradient-to-r from-teal-600 to-emerald-600 px-6 py-2.5 text-sm font-medium text-white shadow-md transition-all hover:from-teal-700 hover:to-emerald-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 ${
                    disabled ? 'opacity-50 cursor-not-allowed hover:from-teal-600 hover:to-emerald-600 hover:shadow-md hover:translate-y-0' : 'hover:-translate-y-0.5'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
