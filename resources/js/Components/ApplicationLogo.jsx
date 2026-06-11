export default function ApplicationLogo(props) {
    return (
        <div {...props} className={"flex items-center gap-2 " + (props.className || '')}>
            <div className="w-12 h-12 bg-gradient-to-br from-teal-500 to-emerald-600 rounded-lg flex items-center justify-center text-white font-bold text-2xl shadow-lg">
                SGI
            </div>
            <span className="font-bold text-2xl tracking-tight text-slate-900 dark:text-white">
                QSMS
            </span>
        </div>
    );
}
