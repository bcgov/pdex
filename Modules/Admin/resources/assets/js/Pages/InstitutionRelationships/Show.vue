<template>
    <Authenticated>
        <Head :title="`Relationship Details - ${institution.legal_operating_name}`" />
        <div class="container-fluid px-4 py-4">
            <div class="mb-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <Link href="/admin/institutions">Institutions</Link>
                        </li>
                        <li class="breadcrumb-item">
                            <Link :href="`/admin/institutions/${institution.id}`">{{ institution.legal_operating_name }}</Link>
                        </li>
                        <li class="breadcrumb-item">
                            <Link :href="`/admin/institutions/${institution.id}/relationships`">Relationships</Link>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Details</li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 fw-bold text-bc-blue mb-0">Relationship Details</h1>
                        <p class="text-muted">View relationship information</p>
                    </div>
                    <div>
                        <Link class="btn btn-outline-secondary" :href="`/admin/institutions/${institution.id}/relationships`">
                            <i class="bi bi-arrow-left me-2"></i>Back to Relationships
                        </Link>
                    </div>

                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Relationship Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-diagram-3 me-2"></i>Relationship Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted">Related Institution</label>
                                    <div>
                                        <h6 class="mb-1">{{ relatedInstitution.legal_operating_name }}</h6>
                                        <small class="text-muted">{{ relatedInstitution.institution_type }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted">Relationship Type</label>
                                    <div>
                                        <span class="badge bg-info fs-6">{{ relationship.relationship_type }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted">Reason</label>
                                    <p class="mb-0">{{ relationship.relationship_reason || 'Not specified' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted">Status</label>
                                    <div>
                                        <span :class="getStatusBadgeClass(relationship.is_active)">
                                            {{ relationship.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-12" v-if="relationship.description">
                                    <label class="form-label text-muted">Description</label>
                                    <p class="mb-0">{{ relationship.description }}</p>
                                </div>
                                <div class="col-12" v-if="relationship.notes">
                                    <label class="form-label text-muted">Notes</label>
                                    <p class="mb-0">{{ relationship.notes }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Date Information -->
                    <div class="card mb-4" v-if="relationship.effective_date || relationship.expiry_date">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-calendar me-2"></i>Date Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6" v-if="relationship.effective_date">
                                    <label class="form-label text-muted">Effective Date</label>
                                    <p class="mb-0">{{ formatDate(relationship.effective_date) }}</p>
                                </div>
                                <div class="col-md-6" v-if="relationship.expiry_date">
                                    <label class="form-label text-muted">Expiry Date</label>
                                    <p class="mb-0">{{ formatDate(relationship.expiry_date) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Metadata -->
                    <div class="card mb-4" v-if="relationship.metadata && Object.keys(relationship.metadata).length > 0">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-info-circle me-2"></i>Additional Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <pre class="small text-muted">{{ JSON.stringify(relationship.metadata, null, 2) }}</pre>
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
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <span class="text-muted">Relationship ID:</span>
                                <code class="small">{{ relationship.id }}</code>
                            </div>
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <span class="text-muted">Created:</span>
                                <span class="small text-muted">{{ formatDate(relationship.created_at) }}</span>
                            </div>
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <span class="text-muted">Last Updated:</span>
                                <span class="small text-muted">{{ formatDate(relationship.updated_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <Link 
                                    v-if="canManageInstitutions"
                                    :href="`/admin/institutions/${institution.id}/relationships/${relationship.id}/edit`" 
                                    class="btn btn-primary"
                                >
                                    <i class="bi bi-pencil me-2"></i>Edit Relationship
                                </Link>
                                <button 
                                    v-if="canManageInstitutions"
                                    @click="toggleStatus"
                                    :class="relationship.is_active ? 'btn btn-outline-warning' : 'btn btn-outline-success'"
                                >
                                    <i :class="relationship.is_active ? 'bi bi-pause me-2' : 'bi bi-play me-2'"></i>
                                    {{ relationship.is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                                <Link :href="`/admin/institutions/${institution.id}/relationships`" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Relationships
                                </Link>
                                <Link :href="`/admin/institutions/${institution.id}`" class="btn btn-outline-secondary">
                                    <i class="bi bi-building me-2"></i>Back to Institution
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Authenticated>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Authenticated from '../../Layouts/Authenticated.vue'
import { computed } from 'vue'

export default {
    name: 'InstitutionRelationshipsShow',
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
        relationship: {
            type: Object,
            required: true
        },
        canManageInstitutions: {
            type: Boolean,
            default: false
        }
    },
    setup(props) {
        const relatedInstitution = computed(() => {
            if (props.relationship.institution_a_guid === props.institution.guid) {
                return props.relationship.institution_b;
            } else {
                return props.relationship.institution_a;
            }
        });

        const formatDate = (dateString) => {
            if (!dateString) return '';

            if (typeof dateString === 'string') {
                const match = dateString.match(/^(\d{4})-(\d{2})-(\d{2})/);
                if (match) {
                    const [, year, month, day] = match;
                    const safeDate = new Date(Number(year), Number(month) - 1, Number(day));
                    return safeDate.toLocaleDateString('en-CA', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                }
            }

            return new Date(dateString).toLocaleDateString('en-CA', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        };

        const getStatusBadgeClass = (isActive) => {
            return isActive ? 'badge bg-success' : 'badge bg-secondary';
        };

        const toggleStatus = () => {
            if (confirm('Are you sure you want to change the status of this relationship?')) {
                router.patch(`/admin/institutions/${props.institution.id}/relationships/${props.relationship.id}/toggle-status`, {}, {
                    preserveState: false,
                    onSuccess: () => {
                        // Success handled by controller
                    },
                    onError: (errors) => {
                        console.log('Toggle status errors:', errors);
                    }
                });
            }
        };

        return {
            relatedInstitution,
            formatDate,
            getStatusBadgeClass,
            toggleStatus
        };
    }
}
</script>

<style scoped>
.text-bc-blue {
    color: #003366 !important;
}
</style>
