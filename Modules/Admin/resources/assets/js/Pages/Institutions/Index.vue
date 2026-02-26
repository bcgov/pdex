<template>
    <Authenticated>
        <Head title="Institutions Management" />
        <div class="container-fluid px-4 py-4">
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 fw-bold text-bc-blue">Institutions Management</h1>
                        <p class="text-muted">Manage post-secondary institutions in the system</p>
                    </div>
                    <div v-if="canManageInstitutions">
                        <Link class="btn btn-primary" href="/admin/institutions/create">
                            <i class="bi bi-plus-lg me-2"></i>Add Institution
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="p-3 rounded-circle bg-primary bg-opacity-10 text-primary me-3">
                                    <i class="bi bi-bank2 fs-4"></i>
                                </div>
                                <div>
                                    <p class="card-text text-muted small mb-1">Total Institutions</p>
                                    <h4 class="card-title mb-0">{{ stats.total || 0 }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="p-3 rounded-circle bg-success bg-opacity-10 text-success me-3">
                                    <i class="bi bi-check-circle fs-4"></i>
                                </div>
                                <div>
                                    <p class="card-text text-muted small mb-1">Active Institutions</p>
                                    <h4 class="card-title mb-0">{{ stats.active || 0 }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="p-3 rounded-circle bg-info bg-opacity-10 text-info me-3">
                                    <i class="bi bi-building fs-4"></i>
                                </div>
                                <div>
                                    <p class="card-text text-muted small mb-1">Total Sites</p>
                                    <h4 class="card-title mb-0">{{ stats.total_sites || 0 }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="p-3 rounded-circle bg-warning bg-opacity-10 text-warning me-3">
                                    <i class="bi bi-award fs-4"></i>
                                </div>
                                <div>
                                    <p class="card-text text-muted small mb-1">With DLI</p>
                                    <h4 class="card-title mb-0">{{ stats.with_dli || 0 }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <form @submit.prevent="applyFilters">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" v-model="filters.search" placeholder="Search institutions...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Type</label>
                                <select class="form-select" v-model="filters.type">
                                    <option value="">All Types</option>
                                    <option v-for="type in filterOptions.types" :key="type" :value="type">{{ type }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" v-model="filters.active_status">
                                    <option value="">All Statuses</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">DLI Only</label>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" v-model="filters.has_dli">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="bi bi-funnel me-1"></i>Filter
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Institutions Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Institutions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Institution</th>
                                    <th>Type</th>
                                    <th>DLI</th>
                                    <th>Sites</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="institution in institutions.data" :key="institution.id">
                                    <td>
                                        <div>
                                            <div class="fw-medium">
                                                <Link :href="`/admin/institutions/${institution.id}`" class="text-decoration-none">
                                                    {{ institution.legal_operating_name }}
                                                </Link>
                                            </div>
                                            <small class="text-muted">{{ institution.institution_type }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info-emphasis">{{ institution.institution_type }}</span>
                                    </td>
                                    <td>
                                        <span v-if="institution.dli" class="badge bg-secondary">{{ institution.dli }}</span>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary-emphasis">
                                            {{ institution.sites_count || 0 }} sites
                                        </span>
                                    </td>
                                    <td>
                                        <span :class="getStatusBadgeClass(institution.active_status)">
                                            {{ institution.active_status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Empty State -->
                    <div v-if="institutions.data && institutions.data.length === 0" class="text-center py-5">
                        <i class="bi bi-bank2 text-muted mb-3" style="font-size: 3rem;"></i>
                        <p class="text-muted">No institutions found</p>
                    </div>
                </div>
                
                <!-- Pagination -->
                <div class="card-footer" v-if="institutions.links">
                    <nav>
                        <ul class="pagination pagination-sm mb-0 justify-content-center">
                            <li v-for="link in institutions.links" :key="link.label" :class="['page-item', { active: link.active, disabled: !link.url }]">
                                <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label"></Link>
                                <span v-else class="page-link" v-html="link.label"></span>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </Authenticated>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Authenticated from '../../Layouts/Authenticated.vue'

export default {
    name: 'InstitutionsIndex',
    components: {
        Head,
        Link,
        Authenticated
    },
    props: {
        institutions: {
            type: Object,
            required: true
        },
        stats: {
            type: Object,
            default: () => ({})
        },
        filterOptions: {
            type: Object,
            default: () => ({ types: [], regions: [], standings: [] })
        },
        canManageInstitutions: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            filters: {
                search: '',
                type: '',
                active_status: '',
                has_dli: false
            }
        }
    },
    methods: {
        applyFilters() {
            this.$inertia.get('/admin/institutions', this.filters, {
                preserveState: true,
                replace: true
            })
        },
        getStatusBadgeClass(isActive) {
            return isActive ? 'badge bg-success' : 'badge bg-secondary'
        }
    }
}
</script>

<style scoped>
.text-bc-blue {
    color: #003366 !important;
}
</style>
