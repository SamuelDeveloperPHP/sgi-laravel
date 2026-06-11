export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded border-slate-300 text-teal-600 shadow-sm focus:ring-teal-500 dark:border-slate-700 dark:bg-slate-900 dark:focus:ring-teal-600 dark:focus:ring-offset-slate-800 transition-colors cursor-pointer ' +
                className
            }
        />
    );
}
