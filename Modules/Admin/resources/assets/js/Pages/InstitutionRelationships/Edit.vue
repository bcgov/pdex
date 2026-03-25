<template>
    <Authenticated>
        <Head :title="`Edit Relationship - ${institution.legal_operating_name}`" />
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
                        <li class="breadcrumb-item active" aria-current="page">Edit</li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 fw-bold text-bc-blue mb-0">Edit Relationship</h1>
                        <p class="text-muted">Modify relationship details</p>
                    </div>
                    <div>
                        <Link class="btn btn-outline-secondary" :href="`/admin/institutions/${institution.id}/relationships`">
                            <i class="bi bi-arrow-left me-2"></i>Back to Relationships
                        </Link>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit">
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
                                        <label class="form-label">Related Institution *</label>
                                        <select class="form-select" 
                                                :class="{ 'is-invalid': errors.related_institution_guid }"
                                                v-model="form.related_institution_guid" required>
                                            <option value="">Select Institution</option>
                                            <option v-for="inst in otherInstitutions" 
                                                    :key="inst.guid" 
                                                    :value="inst.guid">
                                                {{ inst.legal_operating_name }}
                                            </option>
                                        </select>
                                        <div class="invalid-feedback" v-if="errors.related_institution_guid">
                                            {{ errors.related_institution_guid }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Relationship Type *</label>
                                        <select class="form-select" 
                                                :class="{ 'is-invalid': errors.relationship_type }"
                                                v-model="form.relationship_type" required>
                                            <option value="">Select Type</option>
                                            <option v-for="(label, value) in relationshipTypes" 
                                                    :key="value" 
                                                    :value="value">
                                                {{ label }}
                                            </option>
                                        </select>
                                        <div class="invalid-feedback" v-if="errors.relationship_type">
                                            {{ errors.relationship_type }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Relationship Reason</label>
                                        <div v-if="useCustomReason">
                                            <input type="text" class="form-control" 
                                                   :class="{ 'is-invalid': errors.relationship_reason }"
                                                   v-model="form.relationship_reason" 
                                                   placeholder="Enter custom reason..."
                                                   maxlength="255">
                                            <div class="form-text">
                                                <button type="button" class="btn btn-link btn-sm p-0" @click="toggleReasonType">
                                                    Choose from predefined options
                                                </button>
                                            </div>
                                        </div>
                                        <div v-else>
                                            <select class="form-select" 
                                                    :class="{ 'is-invalid': errors.relationship_reason }"
                                                    v-model="form.relationship_reason">
                                                <option value="">Select Reason</option>
                                                <option v-for="(label, value) in relationshipReasons" 
                                                        :key="value" 
                                                        :value="value">
                                                    {{ label }}
                                                </option>
                                                <option value="custom">Other (Custom)</option>
                                            </select>
                                            <div class="form-text">
                                                <button type="button" class="btn btn-link btn-sm p-0" @click="toggleReasonType">
                                                    Enter custom reason
                                                </button>
                                            </div>
                                        </div>
                                        <div class="invalid-feedback" v-if="errors.relationship_reason">
                                            {{ errors.relationship_reason }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   v-model="form.is_active" id="is_active">
                                            <label class="form-check-label" for="is_active">
                                                Active Relationship
                                            </label>
                                        </div>
                                        <div class="form-text">Inactive relationships are not considered operational</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" 
                                                  :class="{ 'is-invalid': errors.description }"
                                                  v-model="form.description" 
                                                  rows="3"
                                                  placeholder="Optional description of the relationship..."></textarea>
                                        <div class="invalid-feedback" v-if="errors.description">
                                            {{ errors.description }}
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" 
                                                  :class="{ 'is-invalid': errors.notes }"
                                                  v-model="form.notes" 
                                                  rows="2"
                                                  placeholder="Internal notes..."></textarea>
                                        <div class="invalid-feedback" v-if="errors.notes">
                                            {{ errors.notes }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Date Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-calendar me-2"></i>Date Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Effective Date</label>
                                        <input type="date" class="form-control" 
                                               :class="{ 'is-invalid': errors.effective_date }"
                                               v-model="form.effective_date">
                                        <div class="form-text">When this relationship becomes effective</div>
                                        <div class="invalid-feedback" v-if="errors.effective_date">
                                            {{ errors.effective_date }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Expiry Date</label>
                                        <input type="date" class="form-control" 
                                               :class="{ 'is-invalid': errors.expiry_date }"
                                               v-model="form.expiry_date">
                                        <div class="form-text">When this relationship expires (optional)</div>
                                        <div class="invalid-feedback" v-if="errors.expiry_date">
                                            {{ errors.expiry_date }}
                                        </div>
                                    </div>
                                </div>
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
                                    <label class="form-label text-muted">Relationship ID</label>
                                    <p class="mb-0">
                                        <code class="small">{{ relationship.id }}</code>
                                    </p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted">Created</label>
                                    <p class="mb-0 small text-muted">{{ formatDate(relationship.created_at) }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted">Last Updated</label>
                                    <p class="mb-0 small text-muted">{{ formatDate(relationship.updated_at) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="card sticky-top" style="top: 80px;">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary" :disabled="processing">
                                        <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="bi bi-check-lg me-2"></i>
                                        {{ processing ? 'Saving...' : 'Save Changes' }}
                                    </button>
                                    <Link :href="`/admin/institutions/${institution.id}/relationships/${relationship.id}`" class="btn btn-outline-secondary">
                                        <i class="bi bi-eye me-2"></i>View Details
                                    </Link>
                                    <Link :href="`/admin/institutions/${institution.id}/relationships`" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Back to Relationships
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </Authenticated>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Authenticated from '../../Layouts/Authenticated.vue'
import { computed, ref, watch } from 'vue'

// Helper function to format date for HTML date input (YYYY-MM-DD)
function formatDateForInput(dateString) {
    if (!dateString) return '';

    // Keep date-only values timezone-safe (no JS Date conversion)
    if (typeof dateString === 'string') {
        const match = dateString.match(/^(\d{4}-\d{2}-\d{2})/);
        return match ? match[1] : '';
    }

    return '';
}

export default {
    name: 'InstitutionRelationshipsEdit',
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
        otherInstitutions: {
            type: Array,
            default: () => []
        },
        relationshipTypes: {
            type: Object,
            default: () => ({})
        },
        relationshipReasons: {
            type: Object,
            default: () => ({})
        },
        errors: {
            type: Object,
            default: () => ({})
        }
    },
    setup(props) {
        // Determine the related institution GUID
        const relatedInstitutionGuid = computed(() => {
            if (props.relationship.institution_a_guid === props.institution.guid) {
                return props.relationship.institution_b_guid;
            } else {
                return props.relationship.institution_a_guid;
            }
        });

        // Check if the current relationship reason is a custom one (not in predefined list)
        const isCustomReason = !Object.keys(props.relationshipReasons).includes(props.relationship.relationship_reason);
        const useCustomReason = ref(isCustomReason);

        const form = useForm({
            related_institution_guid: relatedInstitutionGuid.value,
            relationship_type: props.relationship.relationship_type || '',
            relationship_reason: props.relationship.relationship_reason || '',
            description: props.relationship.description || '',
            notes: props.relationship.notes || '',
            is_active: Boolean(props.relationship.is_active),
            effective_date: props.relationship.effective_date ? formatDateForInput(props.relationship.effective_date) : '',
            expiry_date: props.relationship.expiry_date ? formatDateForInput(props.relationship.expiry_date) : '',
        });

        const formatDate = (dateString) => {
            if (!dateString) return '';
            return new Date(dateString).toLocaleDateString('en-CA', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        };

        const toggleReasonType = () => {
            useCustomReason.value = !useCustomReason.value;
            if (!useCustomReason.value) {
                // Switching to dropdown, clear the field
                form.relationship_reason = '';
            }
        };

        // Watch for "custom" selection in dropdown
        watch(() => form.relationship_reason, (newValue) => {
            if (newValue === 'custom') {
                useCustomReason.value = true;
                form.relationship_reason = '';
            }
        });

        return { 
            form,
            formatDate,
            useCustomReason,
            toggleReasonType
        };
    },
    computed: {
        processing() {
            return this.form.processing;
        }
    },
    methods: {
        submit() {
            this.form.put(`/admin/institutions/${this.institution.id}/relationships/${this.relationship.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    // Redirect handled by controller
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
