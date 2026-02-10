<template>
    <nav class="navbar navbar-expand-lg sticky-top navbar-light">
        <div class="container-fluid">
            <Link class="navbar-brand" href="/admin">
                <img src="/images/bc_lg_logo.png" width="129" height="34" class="d-inline-block align-text-top me-3" alt="BC Government Logo" />
                <span class="d-none d-xl-inline fw-light">Post Secondary Data Exchange - Admin Portal</span>
            </Link>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar"
                    aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav flex-row flex-wrap ms-md-auto">
                    <!-- Ministry Dashboard Access Button -->
                    <li class="nav-item">
                        <Link class="nav-link ministry-portal-link" href="/admin/ministry-access" title="Access Ministry Dashboard">
                            <i class="bi bi-building me-1"></i>
                            Access Ministry Portal
                        </Link>
                    </li>

                    <li v-for="link in navigationLinks" :key="link.href" class="nav-item">
                        <NavLink class="nav-link" :href="link.href"
                                 :class="{ 'active': isActiveRoute(link.href) }">
                            {{ link.label }}
                        </NavLink>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminUserDropdown" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            Hello: {{ $page.props.auth.user.name || $page.props.auth.user.user_id }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminUserDropdown">
                            <li class="dropdown-item px-4">
                                <div class="fw-medium small text-muted">{{ $page.props.auth.user.email }}</div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li class="dropdown-item">
                                <div class="d-grid">
                                    <form @submit.prevent="submitLogout" style="display: contents;">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-box-arrow-right me-2"></i>Log Out
                                        </button>
                                    </form>
                                </div>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</template>

<script>
import NavLink from '@/Components/NavLink.vue'
import { Link, router } from '@inertiajs/vue3'

export default {
    name: 'AdminHeader',
    components: {
        NavLink,
        Link
    },
    setup() {
        // Admin-specific navigation links - Dashboard and Institutions moved to sidebar menu
        const navigationLinks = [
            // Navigation links removed - now in permanent sidebar menu
            // { label: 'Dashboard', href: '/admin' },
            // { label: 'Institutions', href: '/admin/institutions' },
        ]

        const isActiveRoute = (href) => {
            if (typeof window !== 'undefined') {
                return window.location.pathname === href || 
                       (href !== '/admin' && window.location.pathname.startsWith(href))
            }
            return false
        }

        const submitLogout = () => {
            router.post('/admin/logout')
        };

        return {
            navigationLinks,
            isActiveRoute,
            submitLogout
        }
    }
}
</script>

<style scoped>
nav.navbar {
    background-color: white;
    border-bottom: 1px solid #e5e7eb;
}

.ministry-portal-link {
    background-color: #2563eb;
    color: white !important;
    border-radius: 6px;
    padding: 0.5rem 1rem !important;
    margin-right: 1rem;
    transition: all 0.3s ease;
    font-weight: 500;
    text-decoration: none;
}

.ministry-portal-link:hover {
    background-color: #1d4ed8;
    color: white !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(37, 99, 235, 0.3);
}

.ministry-portal-link:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

.ministry-portal-link i {
    font-size: 0.9rem;
}
</style>