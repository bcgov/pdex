<template>
    <nav class="navbar navbar-expand-lg sticky-top navbar-light">
        <div class="container-fluid">
            <Link class="navbar-brand" href="/student">
                <img src="/images/bc_lg_logo.png" width="129" height="34" class="d-inline-block align-text-top me-3" alt="BC Government Logo" />
                <span class="d-none d-xl-inline fw-light">Post Secondary Data Exchange - Student Portal</span>
            </Link>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#studentNavbar"
                    aria-controls="studentNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="studentNavbar">
                <ul class="navbar-nav flex-row flex-wrap ms-md-auto">
                    <li v-for="link in navigationLinks" :key="link.href" class="nav-item">
                        <NavLink class="nav-link" :href="link.href"
                                 :class="{ 'active': isActiveRoute(link.href) }">
                            {{ link.label }}
                        </NavLink>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="studentUserDropdown" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            {{ $page.props.auth.user.first_name }} {{ $page.props.auth.user.last_name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="studentUserDropdown">
                            <li class="dropdown-item px-4">
                                <div class="fw-medium small text-muted">{{ $page.props.auth.user.email }}</div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li class="dropdown-item">
                                <div class="d-grid">
                                    <Link 
                                        href="/student/logout" 
                                        method="post" 
                                        as="button"
                                        class="text-start text-muted text-decoration-none"
                                    >
                                        Log Out
                                    </Link>
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
    name: 'StudentHeader',
    components: {
        NavLink,
        Link
    },
    setup() {
        // Student-specific navigation links
        const navigationLinks = [
            { label: 'Dashboard', href: '/student' },
            { label: 'Profile', href: '/student/profile' },
        ]

        const isActiveRoute = (href) => {
            if (typeof window !== 'undefined') {
                return window.location.pathname === href || 
                       (href !== '/student' && window.location.pathname.startsWith(href))
            }
            return false
        }

        return {
            navigationLinks,
            isActiveRoute
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
