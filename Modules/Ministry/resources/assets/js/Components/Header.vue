<template>
    <nav class="navbar navbar-expand-lg sticky-top navbar-light">
        <div class="container-fluid">
            <Link class="navbar-brand" href="/ministry">
                <img src="/images/bc_lg_logo.png" width="129" height="34" class="d-inline-block align-text-top me-3" alt="BC Government Logo" />
                <span class="d-none d-xl-inline fw-light">Post Secondary Data Exchange - Ministry Portal</span>
            </Link>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#ministryNavbar"
                    aria-controls="ministryNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="ministryNavbar">
                <ul class="navbar-nav flex-row flex-wrap ms-md-auto">
                    <li v-for="link in navigationLinks" :key="link.href" class="nav-item">
                        <NavLink class="nav-link" :href="link.href"
                                 :class="{ 'active': isActiveRoute(link.href) }">
                            {{ link.label }}
                        </NavLink>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="ministryUserDropdown" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            {{ $page.props.auth.user.name || $page.props.auth.user.user_id }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="ministryUserDropdown">
                            <li class="dropdown-item px-4">
                                <div class="fw-medium small text-muted">{{ $page.props.auth.user.email }}</div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li class="dropdown-item">
                                <div class="d-grid">
                                    <form @submit.prevent="submitLogout" style="display: contents;">
                                        <button type="submit" class="text-start text-muted text-decoration-none btn btn-sm">
                                            Log Out
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
import { Link } from '@inertiajs/vue3'

export default {
    name: 'MinistryHeader',
    components: {
        NavLink,
        Link
    },
    setup() {
        // Ministry-specific navigation links
        const navigationLinks = [
            { label: 'Dashboard', href: '/ministry' },
            // { label: 'Institutions', href: '/ministry/institutions' },
            // { label: 'Students', href: '/ministry/students' },
            // { label: 'Claims', href: '/ministry/claims' },
            // { label: 'Reports', href: '/ministry/reports' },
            // { label: 'Maintenance', href: '/ministry/maintenance' }
        ]

        const isActiveRoute = (href) => {
            if (typeof window !== 'undefined') {
                return window.location.pathname === href || 
                       (href !== '/ministry' && window.location.pathname.startsWith(href))
            }
            return false
        }

        const submitLogout = () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/ministry/logout';
            document.body.appendChild(form);
            form.submit();
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
</style>
