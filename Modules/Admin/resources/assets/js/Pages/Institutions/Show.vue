<template>
    <Authenticated>
        <Head :title="`${institution.legal_operating_name} - Institution Details`" />
        <div class="container-fluid px-4 py-4">
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <Link href="/admin/institutions">Institutions</Link>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Institution Details</li>
                            </ol>
                        </nav>
                        <h1 class="h2 fw-bold text-bc-blue mb-0">{{ institution.legal_operating_name }}</h1>
                        <p class="text-muted">
                            <span class="badge bg-info-subtle text-info-emphasis me-2">{{ institution.institution_type }}</span>
                            <span v-if="institution.dli" class="badge bg-secondary">DLI: {{ institution.dli }}</span>
                        </p>
                    </div>
                    <div v-if="canManageInstitutions">
                        <Link class="btn btn-primary" :href="`/admin/institutions/${institution.id}/edit`">
                            <i class="bi bi-pencil me-2"></i>Edit Institution
                        </Link>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Basic Information -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Institution Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted">Legal Operating Name</label>
                                    <p class="mb-0">{{ institution.legal_operating_name }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted">Institution Type</label>
                                    <p class="mb-0">
                                        <span class="badge bg-info-subtle text-info-emphasis">{{ institution.institution_type }}</span>
                                    </p>
                                </div>
                                <div class="col-md-6" v-if="institution.dli">
                                    <label class="form-label text-muted">DLI Number</label>
                                    <p class="mb-0">
                                        <code>{{ institution.dli }}</code>
                                    </p>
                                </div>
                                <div class="col-12" v-if="institution.notes">
                                    <label class="form-label text-muted">Notes</label>
                                    <p class="mb-0">{{ institution.notes }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted">Status</label>
                                    <p class="mb-0">
                                        <span :class="institution.active_status ? 'badge bg-success' : 'badge bg-secondary'">
                                            {{ institution.active_status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Institution Sites -->
                    <div class="card mb-4" v-if="institution.sites && institution.sites.length > 0">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Institution Sites ({{ institution.sites.length }})</h5>
                            <Link v-if="canManageInstitutions" class="btn btn-sm btn-primary" :href="`/admin/institutions/${institution.id}/sites/create`">
                                <i class="bi bi-plus-lg me-1"></i>Add Site
                            </Link>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Site Name</th>
                                            <th>Location</th>
                                            <th>Contact</th>
                                            <th>Status</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="site in institution.sites" :key="site.id">
                                            <td>
                                                <div>
                                                    <div class="fw-medium">{{ site.operating_name || 'Main Campus' }}</div>
                                                    <small class="text-muted" v-if="site.website">
                                                        <a :href="site.website" target="_blank" class="text-decoration-none">
                                                            <i class="bi bi-globe me-1"></i>Website
                                                        </a>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div>{{ site.city }}, {{ site.province_state }}</div>
                                                    <small class="text-muted">{{ site.address_line_1 }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="small">{{ site.contact_first_name }} {{ site.contact_last_name }}</div>
                                                    <small class="text-muted">
                                                        <a :href="`tel:${site.primary_phone}`" class="text-decoration-none me-2">
                                                            <i class="bi bi-telephone"></i>
                                                        </a>
                                                        <a :href="`mailto:${site.primary_email}`" class="text-decoration-none">
                                                            <i class="bi bi-envelope"></i>
                                                        </a>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <span :class="site.active_status ? 'badge bg-success' : 'badge bg-secondary'">
                                                    {{ site.active_status ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <Link class="btn btn-outline-primary" :href="`/admin/institutions/${institution.id}/sites/${site.id}`">
                                                        <i class="bi bi-eye"></i>
                                                    </Link>
                                                    <Link v-if="canManageInstitutions" class="btn btn-outline-secondary" :href="`/admin/institutions/${institution.id}/sites/${site.id}/edit`">
                                                        <i class="bi bi-pencil"></i>
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- No Sites Message -->
                    <div class="card mb-4" v-else>
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Institution Sites</h5>
                            <Link v-if="canManageInstitutions" class="btn btn-sm btn-primary" :href="`/admin/institutions/${institution.id}/sites/create`">
                                <i class="bi bi-plus-lg me-1"></i>Add First Site
                            </Link>
                        </div>
                        <div class="card-body text-center py-5">
                            <i class="bi bi-building text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2 mb-0">No sites configured for this institution yet.</p>
                        </div>
                    </div>

                    <!-- Institution Users -->
                    <div class="card mb-4" v-if="institutionUsers && institutionUsers.length > 0">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Institution Users ({{ institutionUsers.length }})</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Joined</th>
                                            <th class="text-center" v-if="canManageInstitutions">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="user in institutionUsers" :key="user.id">
                                            <td>
                                                <div>
                                                    <div class="fw-medium">{{ user.name || `${user.first_name} ${user.last_name}` }}</div>
                                                    <small class="text-muted">BCeID: {{ user.bceid_user_guid }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <a :href="`mailto:${user.email}`" class="text-decoration-none">
                                                    {{ user.email }}
                                                </a>
                                            </td>
                                            <td>
                                                <span v-if="user.institution_role" 
                                                      :class="user.institution_role === 'Institution Admin' ? 'badge bg-primary' : 'badge bg-info'">
                                                    {{ user.institution_role }}
                                                </span>
                                                <span v-else class="badge bg-secondary">No Institution Role</span>
                                            </td>
                                            <td>
                                                <span :class="user.is_active ? 'badge bg-success' : 'badge bg-danger'">
                                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ formatDate(user.created_at) }}</small>
                                            </td>
                                            <td class="text-center" v-if="canManageInstitutions">
                                                <div class="btn-group btn-group-sm">
                                                    <!-- Toggle User Status -->
                                                    <form @submit.prevent="toggleUserStatus(user.id)" style="display: inline;">
                                                        <button type="submit" 
                                                                :class="user.is_active ? 'btn btn-outline-danger' : 'btn btn-outline-success'"
                                                                :title="user.is_active ? 'Deactivate User' : 'Activate User'">
                                                            <i :class="user.is_active ? 'bi bi-person-x' : 'bi bi-person-check'"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <!-- Toggle User Role -->
                                                    <form v-if="user.has_institution_role" 
                                                          @submit.prevent="toggleUserRole(user.id)" 
                                                          style="display: inline;">
                                                        <button type="submit" 
                                                                class="btn btn-outline-primary"
                                                                :title="user.institution_role === 'Institution Admin' ? 'Make Institution User' : 'Make Institution Admin'">
                                                            <i :class="user.institution_role === 'Institution Admin' ? 'bi bi-person-down' : 'bi bi-person-up'"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- No Users Message -->
                    <div class="card mb-4" v-else>
                        <div class="card-header">
                            <h5 class="card-title mb-0">Institution Users</h5>
                        </div>
                        <div class="card-body text-center py-5">
                            <i class="bi bi-people text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2 mb-0">No users associated with this institution yet.</p>
                        </div>
                    </div>

                    <!-- Institution Relationships -->
                    <div class="card mb-4" v-if="relationships && relationships.length > 0">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Institution Relationships ({{ relationships.length }})</h5>
                            <div class="d-flex gap-2">
                                <Link v-if="canManageInstitutions" class="btn btn-sm btn-outline-primary" :href="`/admin/institutions/${institution.id}/relationships`">
                                    <i class="bi bi-list me-1"></i>View All
                                </Link>
                                <Link v-if="canManageInstitutions" class="btn btn-sm btn-primary" :href="`/admin/institutions/${institution.id}/relationships/create`">
                                    <i class="bi bi-plus-lg me-1"></i>Add Relationship
                                </Link>
                            </div>
                        </div>
                        <div class="card-body" style="max-height: 350px; overflow-y: auto;">
                            <div v-for="relationship in relationships" :key="relationship.id" class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <i class="bi bi-arrow-left-right text-primary me-2"></i>
                                            {{ getRelatedInstitutionName(relationship) }}
                                        </h6>
                                        <div class="small text-muted mb-2">
                                            <span class="badge bg-primary-subtle text-primary-emphasis me-2">{{ relationship.relationship_type }}</span>
                                            {{ relationship.relationship_reason }}
                                        </div>
                                        <p class="mb-0 small" v-if="relationship.description">{{ relationship.description }}</p>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span :class="relationship.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                            {{ relationship.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        <div class="btn-group btn-group-sm">
                                            <Link class="btn btn-outline-primary" :href="`/admin/institutions/${institution.id}/relationships/${relationship.id}`">
                                                <i class="bi bi-eye"></i>
                                            </Link>
                                            <Link v-if="canManageInstitutions" class="btn btn-outline-secondary" :href="`/admin/institutions/${institution.id}/relationships/${relationship.id}/edit`">
                                                <i class="bi bi-pencil"></i>
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Relationships Message -->
                    <div class="card mb-4" v-else>
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Institution Relationships</h5>
                            <Link v-if="canManageInstitutions" class="btn btn-sm btn-primary" :href="`/admin/institutions/${institution.id}/relationships/create`">
                                <i class="bi bi-plus-lg me-1"></i>Add First Relationship
                            </Link>
                        </div>
                        <div class="card-body text-center py-5">
                            <i class="bi bi-diagram-3 text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2 mb-0">No relationships configured for this institution yet.</p>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">System Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label text-muted">Institution GUID</label>
                                <p class="mb-0">
                                    <code class="small">{{ institution.guid }}</code>
                                </p>
                            </div>
                            <div class="mb-3" v-if="institution.bceid_business_guid">
                                <label class="form-label text-muted">BCeID Business GUID</label>
                                <p class="mb-0">
                                    <code class="small">{{ institution.bceid_business_guid }}</code>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">BCeID Business Name</label>
                                <p class="mb-0 small text-muted">{{ organization || 'Not available' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Created</label>
                                <p class="mb-0 small text-muted">{{ formatDate(institution.created_at) }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Last Updated</label>
                                <p class="mb-0 small text-muted">{{ formatDate(institution.updated_at) }}</p>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <label class="form-label text-muted">Submitted By</label>
                                <p v-if="!submittedBy" class="mb-0">Not recorded</p>
                            </div>
                            <template v-if="submittedBy">
                                <div class="mb-3">
                                    <label class="form-label text-muted">BCeID Username</label>
                                    <p class="mb-0">
                                        <code>{{ submittedBy.bceid_username }}</code>
                                    </p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted">BCeID User GUID</label>
                                    <p class="mb-0">
                                        <code class="small">{{ submittedBy.bceid_user_guid }}</code>
                                    </p>
                                </div>
                                <div>
                                    <label class="form-label text-muted">Email</label>
                                    <p class="mb-0">
                                        <a :href="`mailto:${submittedBy.email}`">{{ submittedBy.email }}</a>
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Quick Stats</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 text-center">
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h4 mb-0 text-primary">{{ institution.sites ? institution.sites.length : 0 }}</div>
                                        <small class="text-muted">Sites</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h4 mb-0 text-success">{{ relationships ? relationships.length : 0 }}</div>
                                        <small class="text-muted">Relationships</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h4 mb-0 text-info">{{ institutionUsers ? institutionUsers.length : 0 }}</div>
                                        <small class="text-muted">Users</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Authenticated>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Authenticated from '../../Layouts/Authenticated.vue'

export default {
    name: 'InstitutionsShow',
    components: {
        Head,
        Link,
        Authenticated
    },
    props: {
        institution: {
            type: Object,
            required: true
        },
        relationships: {
            type: Array,
            default: () => []
        },
        institutionUsers: {
            type: Array,
            default: () => []
        },
        canManageInstitutions: {
            type: Boolean,
            default: false
        },
        submittedBy: {
            type: Object,
            default: null
        },
        organization: {
            type: String,
            default: ''
        }
    },
    methods: {
        formatDate(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleDateString('en-CA', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },
        getRelatedInstitutionName(relationship) {
            // Determine which institution is the "other" one based on current institution's GUID
            if (relationship.institution_a_guid === this.institution.guid) {
                return relationship.institution_b?.legal_operating_name || 'Unknown Institution';
            } else {
                return relationship.institution_a?.legal_operating_name || 'Unknown Institution';
            }
        },
        toggleUserStatus(userId) {
            this.$inertia.patch(`/admin/institutions/${this.institution.id}/users/${userId}/toggle-status`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    // Success message will be handled by flash message
                }
            });
        },
        toggleUserRole(userId) {
            this.$inertia.patch(`/admin/institutions/${this.institution.id}/users/${userId}/toggle-role`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    // Success message will be handled by flash message
                }
            });
        }
    }
}
</script>

<style scoped>
.text-bc-blue {
    color: #003366 !important;
}
</style>
