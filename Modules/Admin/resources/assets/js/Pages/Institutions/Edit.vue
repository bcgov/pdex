<template>
    <Authenticated>
        <Head :title="`Edit ${institution.legal_operating_name}`" />
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
                        <li class="breadcrumb-item active" aria-current="page">Edit</li>
                    </ol>
                </nav>
                <h1 class="h2 fw-bold text-bc-blue mb-0">Edit Institution</h1>
                <p class="text-muted">{{ institution.legal_operating_name }}</p>
            </div>

            <form @submit.prevent="submit">
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Institution Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Institution Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Legal Operating Name *</label>
                                        <input type="text" class="form-control" 
                                               :class="{ 'is-invalid': errors.legal_operating_name }"
                                               v-model="form.legal_operating_name" 
                                               maxlength="255" required>
                                        <div class="invalid-feedback" v-if="errors.legal_operating_name">
                                            {{ errors.legal_operating_name }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Institution Type *</label>
                                        <select class="form-select" 
                                                :class="{ 'is-invalid': errors.institution_type }"
                                                v-model="form.institution_type" required>
                                            <option value="">Select Institution Type</option>
                                            <option v-for="type in institutionTypes" :key="type" :value="type">
                                                {{ type }}
                                            </option>
                                        </select>
                                        <div class="invalid-feedback" v-if="errors.institution_type">
                                            {{ errors.institution_type }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">DLI Number</label>
                                        <input type="text" class="form-control" 
                                               :class="{ 'is-invalid': errors.dli }"
                                               v-model="form.dli" 
                                               placeholder="O12345678"
                                               maxlength="20">
                                        <div class="form-text">Designated Learning Institution number (if applicable)</div>
                                        <div class="invalid-feedback" v-if="errors.dli">
                                            {{ errors.dli }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">BCeID Business GUID</label>
                                        <input type="text" class="form-control" 
                                               :class="{ 'is-invalid': errors.bceid_business_guid }"
                                               v-model="form.bceid_business_guid" 
                                               placeholder="Enter BCeID Business GUID">
                                        <div class="form-text">Business GUID from BCeID system</div>
                                        <div class="invalid-feedback" v-if="errors.bceid_business_guid">
                                            {{ errors.bceid_business_guid }}
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="notes" class="form-label">Notes</label>
                                        <textarea
                                            class="form-control"
                                            :class="{ 'is-invalid': errors.notes }"
                                            id="notes"
                                            v-model="form.notes"
                                            rows="3"
                                            placeholder="Additional notes or comments about this institution..."
                                        ></textarea>
                                        <div class="invalid-feedback" v-if="errors.notes">
                                            {{ errors.notes }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   v-model="form.active_status" id="active_status">
                                            <label class="form-check-label" for="active_status">
                                                Active Institution
                                            </label>
                                        </div>
                                        <div class="form-text">Inactive institutions cannot receive applications</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Related Information Alert -->
                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="bi bi-info-circle me-2"></i>Related Information
                            </h6>
                            <p class="mb-2">Detailed information such as contact details, addresses, and operational data are managed through <strong>Institution Sites</strong>.</p>
                            <div class="d-flex gap-2">
                                <Link :href="`/admin/institutions/${institution.id}`" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>View Institution Sites
                                </Link>
                                <Link :href="`/admin/institutions/${institution.id}/sites/create`" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-lg me-1"></i>Add New Site
                                </Link>
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
                                <div class="mb-3">
                                    <label class="form-label text-muted">Created</label>
                                    <p class="mb-0 small text-muted">{{ formatDate(institution.created_at) }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted">Last Updated</label>
                                    <p class="mb-0 small text-muted">{{ formatDate(institution.updated_at) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="card">
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
                                    <Link :href="`/admin/institutions/${institution.id}`" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Back to Institution
                                    </Link>
                                    <Link href="/admin/institutions" class="btn btn-outline-secondary">
                                        <i class="bi bi-list me-2"></i>Back to List
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

export default {
    name: 'InstitutionsEdit',
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
        institutionTypes: {
            type: Array,
            default: () => []
        },
        errors: {
            type: Object,
            default: () => ({})
        }
    },
    setup(props) {
        const form = useForm({
            legal_operating_name: props.institution.legal_operating_name || '',
            institution_type: props.institution.institution_type || '',
            dli: props.institution.dli || '',
            bceid_business_guid: props.institution.bceid_business_guid || '',
            notes: props.institution.notes || '',
            active_status: Boolean(props.institution.active_status)
        })

        return { form }
    },
    computed: {
        processing() {
            return this.form.processing
        }
    },
    methods: {
        submit() {
            this.form.put(`/admin/institutions/${this.institution.id}`, {
                preserveScroll: true
            })
        },
        formatDate(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleDateString('en-CA', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
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
