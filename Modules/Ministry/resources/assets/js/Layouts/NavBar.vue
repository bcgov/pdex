<script>
import { ref } from 'vue';
import BreezeApplicationLogo from '@/Components/ApplicationLogo.vue';
import BreezeNavLink from '@/Components/NavLink.vue';
import BreezeResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/Components/BCDesign/Button';

export default {
    name: 'NavBar',
    components: {
        BreezeApplicationLogo,
        BreezeNavLink,
        BreezeResponsiveNavLink,
        Link,
        Button
    },
    setup() {
        const showingNavigationDropdown = ref(false);
        const showUserDropdown = ref(false);
        
        return {
            showingNavigationDropdown,
            showUserDropdown
        };
    },
    computed: {
        hasMinistryRole() {
            const user = this.$page.props.auth?.user;
            if (!user || !user.roles) return false;
            return user.roles.some(role => role.name === 'Ministry_Admin');
        },
        logoutUrl() {
            return '/ministry/logout';
        }
    }
};
</script>
<style scoped>
/* BC Design System Navigation Styling - White Header Version */
.bc-nav {
    background-color: rgba(0, 51, 102, 0.05);
    border-radius: 8px;
    padding: 8px;
}

.bc-nav-link {
    color: var(--bc-blue);
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 4px;
    font-weight: 500;
    transition: all 0.2s ease;
    display: block;
}

.bc-nav-link:hover {
    background-color: rgba(0, 51, 102, 0.1);
    color: var(--bc-blue);
    text-decoration: none;
}

.bc-nav-link.active {
    background-color: var(--bc-gold);
    color: var(--bc-blue);
}

.bc-user-menu {
    position: relative;
}

.bc-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    min-width: 200px;
    z-index: 50;
    margin-top: 4px;
}

.bc-dropdown-item {
    padding: 12px 16px;
    color: var(--bc-grey-dark);
    text-decoration: none;
    display: block;
    transition: background-color 0.2s ease;
}

.bc-dropdown-item:hover {
    background-color: #f9fafb;
    color: var(--bc-grey-dark);
    text-decoration: none;
}

.bc-dropdown-divider {
    height: 1px;
    background-color: #e5e7eb;
    margin: 4px 0;
}

/* Mobile menu styling */
.bc-mobile-nav {
    background-color: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-top: 8px;
    padding: 16px;
    box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
}

.bc-mobile-nav .bc-nav-link {
    color: var(--bc-blue);
    margin-bottom: 8px;
}

.bc-mobile-nav .bc-nav-link:hover {
    background-color: rgba(0, 51, 102, 0.1);
}
</style>
<template>
    <div class="flex items-center gap-4">
        <!-- Desktop Navigation -->
        <nav class="hidden md:flex items-center gap-2 bc-nav">
            <!-- Ministry Links (only for ministry users) -->
            <template v-if="hasMinistryRole">
                <Link 
                    href="/ministry/reports" 
                    :class="['bc-nav-link', { 'active': $page.url.includes('/ministry/reports') }]"
                >
                    Reports
                </Link>
                <Link 
                    href="/ministry/institutions" 
                    :class="['bc-nav-link', { 'active': $page.url.includes('/ministry/institutions') }]"
                >
                    Institutions
                </Link>
                <Link 
                    href="/ministry/analytics" 
                    :class="['bc-nav-link', { 'active': $page.url.includes('/ministry/analytics') }]"
                >
                    Analytics
                </Link>
            </template>
        </nav>
    </div>
</template>
