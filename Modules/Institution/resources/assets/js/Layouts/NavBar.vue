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
        
        const submitLogout = () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/institution/logout';
            document.body.appendChild(form);
            form.submit();
        };
        
        return {
            showingNavigationDropdown,
            showUserDropdown,
            submitLogout
        };
    },
    computed: {
        hasInstitutionRole() {
            const user = this.$page.props.auth?.user;
            if (!user || !user.roles) return false;
            return user.roles.some(role => role.name === 'Institution_User');
        },
        logoutUrl() {
            return '/institution/logout';
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
            <!-- Institution Dashboard -->
            <Link 
                href="/institution" 
                :class="['bc-nav-link', { 'active': $page.url === '/institution' }]"
            >
                Dashboard
            </Link>
            
            <!-- Institution Links (only for institution users) -->
            <template v-if="hasInstitutionRole">
                <Link 
                    href="/institution/applications" 
                    :class="['bc-nav-link', { 'active': $page.url.includes('/institution/applications') }]"
                >
                    Applications
                </Link>
                <Link 
                    href="/institution/students" 
                    :class="['bc-nav-link', { 'active': $page.url.includes('/institution/students') }]"
                >
                    Students
                </Link>
                <Link 
                    href="/institution/settings" 
                    :class="['bc-nav-link', { 'active': $page.url.includes('/institution/settings') }]"
                >
                    Settings
                </Link>
            </template>
        </nav>

        <!-- Direct Logout Button -->
        <button
            @click="submitLogout"
            class="bc-nav-link text-sm"
        >
            Logout
        </button>

        <!-- User Menu -->
        <div class="bc-user-menu">
            <button
                @click="showUserDropdown = !showUserDropdown"
                class="bc-nav-link flex items-center gap-2"
            >
                <span>{{ $page.props.auth.user.user_id }}</span>
                <svg 
                    class="w-4 h-4 transition-transform duration-200"
                    :class="{ 'rotate-180': showUserDropdown }"
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- User Dropdown -->
            <div v-if="showUserDropdown" class="bc-dropdown">
                <div class="bc-dropdown-item">
                    <div class="font-medium text-sm text-gray-500">
                        {{ $page.props.auth.user.email }}
                    </div>
                </div>
                <div class="bc-dropdown-divider"></div>
                <button
                    @click="submitLogout"
                    class="bc-dropdown-item w-full text-left"
                >
                    Log Out
                </button>
            </div>
        </div>

        <!-- Mobile Menu Button -->
        <button
            @click="showingNavigationDropdown = !showingNavigationDropdown"
            class="md:hidden bc-nav-link"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path 
                    v-if="!showingNavigationDropdown"
                    stroke-linecap="round" 
                    stroke-linejoin="round" 
                    stroke-width="2" 
                    d="M4 6h16M4 12h16M4 18h16"
                />
                <path 
                    v-else
                    stroke-linecap="round" 
                    stroke-linejoin="round" 
                    stroke-width="2" 
                    d="M6 18L18 6M6 6l12 12"
                />
            </svg>
        </button>

        <!-- Mobile Navigation -->
        <div v-if="showingNavigationDropdown" class="absolute top-full left-0 right-0 bc-mobile-nav md:hidden">
            <!-- Institution Dashboard -->
            <Link href="/institution" class="bc-nav-link">Dashboard</Link>
            
            <!-- Institution Links (only for institution users) -->
            <template v-if="hasInstitutionRole">
                <Link href="/institution/applications" class="bc-nav-link">Applications</Link>
                <Link href="/institution/students" class="bc-nav-link">Students</Link>
                <Link href="/institution/settings" class="bc-nav-link">Settings</Link>
            </template>
            
            <div class="bc-dropdown-divider"></div>
            <div class="bc-dropdown-item">
                <div class="font-medium text-sm text-gray-500">
                    {{ $page.props.auth.user.email }}
                </div>
            </div>
            <Link 
                :href="logoutUrl" 
                method="post" 
                as="button"
                class="bc-nav-link w-full text-left"
            >
                Log Out
            </Link>
        </div>
    </div>
</template>
