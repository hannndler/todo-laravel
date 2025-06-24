import { Head } from '@inertiajs/react';

import AppearanceTabs from '@/components/appearance-tabs';
import { AuthenticatedLayout } from '@/layouts/authenticated-layout';

export default function Appearance() {
    return (
        <AuthenticatedLayout>
            <Head title="Configuración de apariencia" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                        Configuración de apariencia
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400">
                        Actualiza la configuración de apariencia de tu cuenta
                    </p>
                </div>
                <AppearanceTabs />
            </div>
        </AuthenticatedLayout>
    );
}
