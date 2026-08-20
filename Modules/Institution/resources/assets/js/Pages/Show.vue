<template>
    <Authenticated>
        <Head title="Institution Details" />
        <div class="container-fluid px-4 py-4">
            <div class="mb-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <Link href="/institution">Dashboard</Link>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Institution Details</li>
                    </ol>
                </nav>
                <h1 class="h2 fw-bold text-bc-blue mb-0">{{ institution.legal_operating_name }}</h1>
            </div>

            <div v-if="$page.props.flash?.success" class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                {{ $page.props.flash.success }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <div v-if="$page.props.flash?.error" class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                {{ $page.props.flash.error }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <div class="row">
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
                                <div class="col-md-6">
                                    <label class="form-label text-muted">DLI Number</label>
                                    <p class="mb-0">
                                        <code v-if="institution.dli">{{ institution.dli }}</code>
                                        <span v-else class="text-muted">Not provided</span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted">Status</label>
                                    <p class="mb-0">
                                        <span :class="statusBadgeClass">
                                            {{ statusLabel }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Institution Sites ({{ sites.length }})</h5>
                        </div>
                        <div class="card-body">
                            <div v-if="sites.length === 0" class="text-center py-5">
                                <i class="bi bi-building text-muted" style="font-size: 2rem;"></i>
                                <p class="text-muted mt-2 mb-0">No sites have been submitted for this institution.</p>
                            </div>

                            <!-- Site Information -->
                            <div v-for="site in sites" :key="site.id" class="border rounded p-3 mb-3">
                                <h6 class="text-dark fw-semibold mb-3">Site Information</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Site Name</label>
                                        <p class="mb-0">{{ site.operating_name || 'Main Campus' }}</p>
                                    </div>
                                    <div class="col-md-6" v-if="site.website">
                                        <label class="form-label text-muted">Website</label>
                                        <p class="mb-0">
                                            <a :href="site.website" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                                                <i class="bi bi-globe me-1"></i>{{ site.website }}
                                            </a>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Status</label>
                                        <p class="mb-0">
                                            <span :class="site.active_status ? 'badge bg-success' : 'badge bg-warning text-dark'">
                                                {{ site.active_status ? 'Active' : 'Under Review' }}
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Contact Information -->
                                <h6 class="text-dark fw-semibold mb-3">Contact Information</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Primary Contact</label>
                                        <p class="mb-0">{{ site.contact_first_name }} {{ site.contact_last_name }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Contact Email</label>
                                        <p class="mb-0">
                                            <a :href="`mailto:${site.contact_email}`" class="text-decoration-none">
                                                {{ site.contact_email }}
                                            </a>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Contact Phone</label>
                                        <p class="mb-0">
                                            <a :href="`tel:${site.contact_phone}`" class="text-decoration-none">
                                                {{ site.contact_phone }}
                                            </a>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Primary Phone</label>
                                        <p class="mb-0">
                                            <a :href="`tel:${site.primary_phone}`" class="text-decoration-none">
                                                {{ site.primary_phone }}
                                            </a>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Primary Email</label>
                                        <p class="mb-0">
                                            <a :href="`mailto:${site.primary_email}`" class="text-decoration-none">
                                                {{ site.primary_email }}
                                            </a>
                                        </p>
                                    </div>
                                </div>

                                <!-- Address Information -->
                                <h6 class="text-dark fw-semibold mb-3">Address Information</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-12">
                                        <label class="form-label text-muted">Address</label>
                                        <p class="mb-0">
                                            {{ site.address_line_1 }}<br>
                                            <template v-if="site.address_line_2">
                                                {{ site.address_line_2 }}<br>
                                            </template>
                                            {{ site.city }}, {{ site.province_state }} {{ site.postal_code }}<br>
                                            {{ site.country }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Operational Information -->
                                <h6 class="text-dark fw-semibold mb-3">Operational Information</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Regulating Body</label>
                                        <p class="mb-0">{{ site.regulating_body }}</p>
                                        <p class="mb-0 small text-muted" v-if="site.other_regulating_body">
                                            {{ site.other_regulating_body }}
                                        </p>
                                    </div>
                                    <div class="col-md-6" v-if="site.economic_region">
                                        <label class="form-label text-muted">Economic Region</label>
                                        <p class="mb-0">{{ site.economic_region }}</p>
                                    </div>
                                    <div class="col-md-6" v-if="site.standing_status">
                                        <label class="form-label text-muted">Standing Status</label>
                                        <p class="mb-0">
                                            <span :class="site.standing_status === 'Good Standing' ? 'badge bg-success' : 'badge bg-warning'">
                                                {{ site.standing_status }}
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6" v-if="site.established_date">
                                        <label class="form-label text-muted">Established Date</label>
                                        <p class="mb-0">{{ formatDate(site.established_date) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Submission Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label text-muted">Submitted</label>
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
                                <Link href="/institution" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
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
import { Head, Link } from '@inertiajs/vue3';
import Authenticated from '../Layouts/Authenticated.vue';

export default {
    name: 'InstitutionShow',
    components: {
        Head,
        Link,
        Authenticated
    },
    props: {
        institution: {
            type: Object,
            required: true
        }
    },
    computed: {
        sites() {
            return this.institution.sites || [];
        },
        statusLabel() {
            return this.institution.active_status ? 'Active' : 'Under Review';
        },
        statusBadgeClass() {
            return this.institution.active_status ? 'badge bg-success' : 'badge bg-warning text-dark';
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
        }
    }
}
</script>

<style scoped>
.text-bc-blue {
    color: #003366 !important;
}
</style>
