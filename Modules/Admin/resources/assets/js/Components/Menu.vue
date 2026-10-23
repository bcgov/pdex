<template>
    <nav class="list-group list-group-flush">
        <Link href="/admin" class="list-group-item list-group-item-action d-flex align-items-center py-3 border-0" :class="isActive('admin.dashboard') ? 'active' : ''">
            <i class="bi bi-speedometer2 me-3"></i>
            Dashboard
        </Link>
        <Link href="/admin/applications" class="list-group-item list-group-item-action d-flex align-items-center py-3 border-0" :class="isActive('admin.applications') ? 'active' : ''">
            <i class="bi bi-briefcase me-3"></i>
            Applications
        </Link>
        <Link href="/admin/institutions" class="list-group-item list-group-item-action d-flex align-items-center py-3 border-0" :class="isActive('admin.institutions') ? 'active' : ''">
            <i class="bi bi-bank2 me-3"></i>
            Institutions
        </Link>
        <Link href="/admin/users" class="list-group-item list-group-item-action d-flex align-items-center py-3 border-0" :class="isActive('admin.users') ? 'active' : ''">
            <i class="bi bi-people me-3"></i>
            Users
        </Link>
        <Link href="/admin/utils/student" class="list-group-item list-group-item-action d-flex align-items-center py-3 border-0" :class="isActive('admin.utils') ? 'active' : ''">
            <i class="bi bi-ui-checks me-3"></i>
            Form Fields
        </Link>
        <!-- <Link href="/admin/settings" class="list-group-item list-group-item-action d-flex align-items-center py-3 border-0" :class="isActive('admin.settings') ? 'active' : ''">
            <i class="bi bi-gear me-3"></i>
            Settings
        </Link> -->
    </nav>
</template>

<style scoped>
.list-group-item {
    transition: all 0.2s ease;
    cursor: pointer;
}

.list-group-item:hover {
    background-color: #f8f9fa;
    transform: translateX(2px);
}

.list-group-item.active {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

.list-group-item.active:hover {
    background-color: #0b5ed7;
    transform: translateX(2px);
}

.list-group-item.active i {
    color: white;
}

.list-group-item i {
    width: 20px;
    text-align: center;
}
</style>

<script>
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';

export default {
    name: 'AdminMenu',
    components: {
        Link
    },
    setup() {
        const page = usePage();
        function isActive(routeName) {
            const currentUrl = window.location.pathname;
            
            // Handle specific route patterns
            switch (routeName) {
                case 'admin.dashboard':
                    return currentUrl === '/admin/dashboard' || currentUrl === '/admin';
                case 'admin.institutions':
                    return currentUrl.startsWith('/admin/institutions');
                case 'admin.users':
                    return currentUrl.startsWith('/admin/users');
                case 'admin.applications':
                    return currentUrl.startsWith('/admin/applications');
                case 'admin.utils':
                    return currentUrl.startsWith('/admin/utils');
                case 'admin.settings':
                    return currentUrl.startsWith('/admin/settings');
                default:
                    return false;
            }
        }
        return { isActive };
    }
}
</script>

