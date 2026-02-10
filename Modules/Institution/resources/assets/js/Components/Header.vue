<template>
    <nav class="navbar navbar-expand-lg sticky-top navbar-light">
        <div class="container-fluid">
            <Link class="navbar-brand" href="/institution">
                <img src="/images/bc_lg_logo.png" width="129" height="34" class="d-inline-block align-text-top me-3" alt="BC Government Logo" />
                <span class="d-none d-xl-inline fw-light">Post Secondary Data Exchange - Institution Portal</span>
            </Link>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#institutionNavbar"
                    aria-controls="institutionNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="institutionNavbar">
                <ul class="navbar-nav flex-row flex-wrap ms-md-auto">
                    <li v-for="link in navigationLinks" :key="link.href" class="nav-item">
                        <NavLink class="nav-link" :href="link.href"
                                 :class="{ 'active': isActiveRoute(link.href) }">
                            {{ link.label }}
                        </NavLink>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="institutionUserDropdown" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            {{ $page.props.auth.user.first_name + ' ' + $page.props.auth.user.last_name || $page.props.auth.user.display_name || $page.props.auth.user.user_id }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="institutionUserDropdown">
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
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export default {
    name: 'InstitutionHeader',
    components: {
        NavLink,
        Link
    },
    setup() {
        const page = usePage()

        // Base navigation links available to all institution users
        const baseNavigationLinks = [
            { label: 'Dashboard', href: '/institution' },
            // { label: 'Students', href: '/institution/students' },
            // { label: 'Claims', href: '/institution/claims' },
            // { label: 'Reports', href: '/institution/reports' },
        ]

        // Check if user has INSTITUTION_ADMIN role
        const hasInstitutionAdminRole = computed(() => {
            const user = page.props.auth?.user
            if (!user || !user.roles) return false
            return user.roles.some(role => role.name === 'Institution Admin')
        })

        // Combine navigation links based on user role
        const navigationLinks = computed(() => {
            const links = [...baseNavigationLinks]
            
            // Add admin-only links if user has INSTITUTION_ADMIN role
            if (hasInstitutionAdminRole.value) {
                links.push({ label: 'Settings', href: '/institution/settings' })
            }
            
            return links
        })

        const isActiveRoute = (href) => {
            if (typeof window !== 'undefined') {
                return window.location.pathname === href || 
                       (href !== '/institution' && window.location.pathname.startsWith(href))
            }
            return false
        }

        const submitLogout = () => {
            router.post('/institution/logout')
        };

        return {
            navigationLinks,
            isActiveRoute,
            hasInstitutionAdminRole,
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
