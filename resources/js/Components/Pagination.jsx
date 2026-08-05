import { Link } from '@inertiajs/react';
import React from 'react';

export default function Pagination({ links }) {
    if (!links || links.length === 0) return null;

    return (
        <div className="flex justify-center mt-4 pb-4">
            <div className="flex gap-1 flex-wrap">
                {links.map((link, i) => (
                    link.url ? (
                        <Link
                            key={i}
                            href={link.url}
                            className={`px-3 py-1 rounded text-sm ${
                                link.active 
                                    ? 'bg-indigo-600 text-white' 
                                    : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            key={i}
                            className="px-3 py-1 rounded text-sm text-gray-400 border border-gray-200 cursor-not-allowed"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    )
                ))}
            </div>
        </div>
    );
}
