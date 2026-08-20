<template>
    <Authenticated>
        <Head title="Add Institution" />
        <div class="container-fluid px-4 py-4">
            <div class="mb-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <Link href="/institution">Dashboard</Link>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Create Institution</li>
                    </ol>
                </nav>
                <h1 class="h2 fw-bold text-bc-blue mb-0">Create Institution</h1>
                <p class="text-muted mb-0">Submit your institution information for review.</p>
            </div>

            <div v-if="$page.props.flash?.error" class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                {{ $page.props.flash.error }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
                                        <input
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': getError('legal_operating_name') }"
                                            v-model="form.legal_operating_name"
                                            @input="clearFieldError('legal_operating_name')"
                                            maxlength="255"
                                            required
                                        >
                                        <div class="invalid-feedback" v-if="getError('legal_operating_name')">
                                            {{ getError('legal_operating_name') }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Institution Type *</label>
                                        <select
                                            class="form-select"
                                            :class="{ 'is-invalid': getError('institution_type') }"
                                            v-model="form.institution_type"
                                            required
                                        >
                                            <option value="">Select Institution Type</option>
                                            <option v-for="type in institutionTypes" :key="type" :value="type">
                                                {{ type }}
                                            </option>
                                        </select>
                                        <div class="invalid-feedback" v-if="getError('institution_type')">
                                            {{ getError('institution_type') }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">DLI Number *</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': getError('dli') }"
                                            v-model="form.dli"
                                            @input="clearFieldError('dli')"
                                            placeholder="O12345678"
                                            maxlength="20"
                                            required
                                        >
                                        <div class="form-text">Designated Learning Institution number (if applicable)</div>
                                        <div class="invalid-feedback" v-if="getError('dli')">
                                            {{ getError('dli') }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status</label>
                                        <div>
                                            <span class="badge bg-warning text-dark">Pending Review</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Institution Sites -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Institution Sites</h5>
                                <button type="button" class="btn btn-sm btn-outline-primary" @click="addSite">
                                    <i class="bi bi-plus-lg me-1"></i>Add Site
                                </button>
                            </div>
                            <div class="card-body">
                                <div v-if="getError('sites')" class="alert alert-danger">
                                    {{ getError('sites') }}
                                </div>

                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
                                    <ul class="nav nav-pills gap-2" role="tablist">
                                        <li
                                            v-for="(site, index) in form.sites"
                                            :key="index"
                                            class="nav-item"
                                            role="presentation"
                                        >
                                            <button
                                                type="button"
                                                class="nav-link"
                                                :class="{ active: activeSiteIndex === index }"
                                                role="tab"
                                                :aria-selected="activeSiteIndex === index"
                                                @click="selectSite(index)"
                                            >
                                                Site {{ index + 1 }}
                                                <span v-if="hasSiteError(index)" class="badge bg-danger rounded-pill ms-1">!</span>
                                            </button>
                                        </li>
                                    </ul>

                                    <button
                                        v-if="form.sites.length > 1"
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        @click="removeSite(activeSiteIndex)"
                                    >
                                        <i class="bi bi-trash me-1"></i>Remove
                                    </button>
                                </div>

                                <div v-if="activeSite">
                                    <!-- Site Information -->
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Site Information</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Site Name *</label>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'operating_name') }"
                                                        v-model="activeSite.operating_name"
                                                        @input="clearSiteError(activeSiteIndex, 'operating_name')"
                                                        maxlength="255"
                                                        required
                                                    >
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'operating_name')">
                                                        {{ getSiteError(activeSiteIndex, 'operating_name') }}
                                                    </div>
                                                    <div class="form-text">This will be the name for this specific site/campus</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Website</label>
                                                    <input
                                                        type="url"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'website') }"
                                                        v-model="activeSite.website"
                                                        @input="clearSiteError(activeSiteIndex, 'website')"
                                                        placeholder="https://example.com"
                                                        maxlength="255"
                                                    >
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'website')">
                                                        {{ getSiteError(activeSiteIndex, 'website') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contact Information -->
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Contact Information</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                
                                                <div class="col-md-6">
                                                    <label class="form-label">Contact First Name *</label>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'contact_first_name') }"
                                                        v-model="activeSite.contact_first_name"
                                                        @input="clearSiteError(activeSiteIndex, 'contact_first_name')"
                                                        maxlength="100"
                                                        required
                                                    >
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'contact_first_name')">
                                                        {{ getSiteError(activeSiteIndex, 'contact_first_name') }}
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Contact Last Name *</label>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'contact_last_name') }"
                                                        v-model="activeSite.contact_last_name"
                                                        @input="clearSiteError(activeSiteIndex, 'contact_last_name')"
                                                        maxlength="100"
                                                        required
                                                    >
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'contact_last_name')">
                                                        {{ getSiteError(activeSiteIndex, 'contact_last_name') }}
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Contact Email *</label>
                                                    <input
                                                        type="email"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'contact_email') }"
                                                        v-model="activeSite.contact_email"
                                                        @input="clearSiteError(activeSiteIndex, 'contact_email')"
                                                        maxlength="255"
                                                        required
                                                    >
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'contact_email')">
                                                        {{ getSiteError(activeSiteIndex, 'contact_email') }}
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Contact Phone *</label>
                                                    <input
                                                        type="tel"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'contact_phone') }"
                                                        v-model="activeSite.contact_phone"
                                                        @input="clearSiteError(activeSiteIndex, 'contact_phone')"
                                                        placeholder="(555) 123-4567"
                                                        maxlength="20"
                                                        required
                                                    >
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'contact_phone')">
                                                        {{ getSiteError(activeSiteIndex, 'contact_phone') }}
                                                    </div>
                                                    <div class="form-text">Format: (555) 123-4567</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Primary Phone *</label>
                                                    <input
                                                        type="tel"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'primary_phone') }"
                                                        v-model="activeSite.primary_phone"
                                                        @input="clearSiteError(activeSiteIndex, 'primary_phone')"
                                                        placeholder="(555) 123-4567"
                                                        maxlength="20"
                                                        required
                                                    >
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'primary_phone')">
                                                        {{ getSiteError(activeSiteIndex, 'primary_phone') }}
                                                    </div>
                                                    <div class="form-text">Format: (555) 123-4567</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Primary Email *</label>
                                                    <input
                                                        type="email"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'primary_email') }"
                                                        v-model="activeSite.primary_email"
                                                        @input="clearSiteError(activeSiteIndex, 'primary_email')"
                                                        maxlength="255"
                                                        required
                                                    >
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'primary_email')">
                                                        {{ getSiteError(activeSiteIndex, 'primary_email') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Address Information -->
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Address Information</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-12">
                                                    <label class="form-label">Address Line 1 *</label>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'address_line_1') }"
                                                        v-model="activeSite.address_line_1"
                                                        @input="clearSiteError(activeSiteIndex, 'address_line_1')"
                                                        maxlength="255"
                                                        required
                                                    >
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'address_line_1')">
                                                        {{ getSiteError(activeSiteIndex, 'address_line_1') }}
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <label class="form-label">Address Line 2</label>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'address_line_2') }"
                                                        v-model="activeSite.address_line_2"
                                                        @input="clearSiteError(activeSiteIndex, 'address_line_2')"
                                                        maxlength="255"
                                                    >
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'address_line_2')">
                                                        {{ getSiteError(activeSiteIndex, 'address_line_2') }}
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">City *</label>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'city') }"
                                                        v-model="activeSite.city"
                                                        @input="clearSiteError(activeSiteIndex, 'city')"
                                                        maxlength="100"
                                                        required
                                                    >
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'city')">
                                                        {{ getSiteError(activeSiteIndex, 'city') }}
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Province/State *</label>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'province_state') }"
                                                        v-model="activeSite.province_state"
                                                        @input="clearSiteError(activeSiteIndex, 'province_state')"
                                                        maxlength="100"
                                                        required
                                                    >
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'province_state')">
                                                        {{ getSiteError(activeSiteIndex, 'province_state') }}
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Postal Code *</label>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'postal_code') }"
                                                        v-model="activeSite.postal_code"
                                                        placeholder="A1A 1A1"
                                                        maxlength="10"
                                                        @input="clearSiteError(activeSiteIndex, 'postal_code')"
                                                        required
                                                    >
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'postal_code')">
                                                        {{ getSiteError(activeSiteIndex, 'postal_code') }}
                                                    </div>
                                                    <div class="form-text">Format: A1A 1A1</div>
                                                </div>
                                                <div class="col-md-12">
                                                    <label class="form-label">Country *</label>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'country') }"
                                                        v-model="activeSite.country"
                                                        maxlength="100"
                                                        @input="clearSiteError(activeSiteIndex, 'country')"
                                                        required
                                                    >
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'country')">
                                                        {{ getSiteError(activeSiteIndex, 'country') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Operational Information -->
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">Operational Information</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Regulating Body *</label>
                                                    <select
                                                        class="form-select"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'regulating_body') }"
                                                        v-model="activeSite.regulating_body"
                                                        @change="clearSiteError(activeSiteIndex, 'regulating_body')"
                                                        required
                                                    >
                                                        <option value="">Select Regulating Body</option>
                                                        <option v-for="body in regulatingBodies" :key="body" :value="body">
                                                            {{ body }}
                                                        </option>
                                                    </select>
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'regulating_body')">
                                                        {{ getSiteError(activeSiteIndex, 'regulating_body') }}
                                                    </div>
                                                </div>
                                                <div class="col-md-6" v-if="activeSite.regulating_body === 'Other'">
                                                    <label class="form-label">Other Regulating Body</label>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'other_regulating_body') }"
                                                        @input="clearSiteError(activeSiteIndex, 'other_regulating_body')"
                                                        v-model="activeSite.other_regulating_body"
                                                        maxlength="255"
                                                    >
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'other_regulating_body')">
                                                        {{ getSiteError(activeSiteIndex, 'other_regulating_body') }}
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Economic Region</label>
                                                    <select
                                                        class="form-select"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'economic_region') }"
                                                        v-model="activeSite.economic_region"
                                                        @change="clearSiteError(activeSiteIndex, 'economic_region')"
                                                    >
                                                        <option value="">Select Economic Region</option>
                                                        <option v-for="region in economicRegions" :key="region" :value="region">
                                                            {{ region }}
                                                        </option>
                                                    </select>
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'economic_region')">
                                                        {{ getSiteError(activeSiteIndex, 'economic_region') }}
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Standing Status</label>
                                                    <select
                                                        class="form-select"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'standing_status') }"
                                                        v-model="activeSite.standing_status"
                                                    >
                                                        <option value="">Select Standing Status</option>
                                                        <option v-for="status in standingStatuses" :key="status" :value="status">
                                                            {{ status }}
                                                        </option>
                                                    </select>
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'standing_status')">
                                                        {{ getSiteError(activeSiteIndex, 'standing_status') }}
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Established Date</label>
                                                    <input
                                                        type="date"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': getSiteError(activeSiteIndex, 'established_date') }"
                                                        v-model="activeSite.established_date"
                                                    >
                                                    <div class="invalid-feedback" v-if="getSiteError(activeSiteIndex, 'established_date')">
                                                        {{ getSiteError(activeSiteIndex, 'established_date') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card sticky-top" style="top: 80px;">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary" :disabled="processing">
                                        <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="bi bi-plus-lg me-2"></i>
                                        {{ processing ? 'Creating...' : 'Create Institution' }}
                                    </button>
                                    <Link href="/institution" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Cancel
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
import { Head, Link, useForm } from '@inertiajs/vue3';
import Authenticated from '../Layouts/Authenticated.vue';

const emptySite = () => ({
    operating_name: '',
    primary_phone: '',
    primary_email: '',
    website: '',
    regulating_body: '',
    other_regulating_body: '',
    established_date: '',
    standing_status: '',
    economic_region: '',
    contact_first_name: '',
    contact_last_name: '',
    contact_email: '',
    contact_phone: '',
    address_line_1: '',
    address_line_2: '',
    city: '',
    province_state: 'British Columbia',
    country: 'Canada',
    postal_code: ''
})

export default {
    name: 'InstitutionsCreate',
    components: {
        Head,
        Link,
        Authenticated
    },
    props: {
        institutionTypes: {
            type: Array,
            default: () => []
        },
        regulatingBodies: {
            type: Array,
            default: () => []
        },
        standingStatuses: {
            type: Array,
            default: () => []
        },
        economicRegions: {
            type: Array,
            default: () => []
        },
        errors: {
            type: Object,
            default: () => ({})
        }
    },
    setup() {
        const form = useForm({
            legal_operating_name: '',
            institution_type: '',
            dli: '',
            sites: [emptySite()]
        });

        return { form };
    },
    data() {
        return {
            activeSiteIndex: 0,
            clearedErrors: {}
        };
    },
    computed: {
        activeSite() {
            return this.form.sites[this.activeSiteIndex];
        },
        processing() {
            return this.form.processing;
        }
    },
    methods: {
        addSite() {
            this.form.sites.push(emptySite());
            this.activeSiteIndex = this.form.sites.length - 1;
        },
        removeSite(index) {
            if (this.form.sites.length > 1) {
                this.form.sites.splice(index, 1);
                this.activeSiteIndex = Math.min(index, this.form.sites.length - 1);
                this.clearSiteErrors();
            }
        },
        selectSite(index) {
            this.activeSiteIndex = index;
        },
        getError(field) {
            if (this.clearedErrors[field]) {
                return null;
            }
            return this.form.errors[field] || this.errors[field];
        },
        clearFieldError(field) {
            this.clearedErrors[field] = true;
            this.form.clearErrors(field);
        },
        getSiteError(index, field) {
            return this.getError(`sites.${index}.${field}`);
        },
        clearSiteError(index, field) {
            const errorField = `sites.${index}.${field}`;
            this.clearFieldError(errorField);
        },
        clearSiteErrors() {
            Object.keys({ ...this.errors, ...this.form.errors }).forEach((field) => {
                if (field.startsWith('sites.')) {
                    this.clearedErrors[field] = true;
                    this.form.clearErrors(field);
                }
            })
        },
        hasSiteError(index) {
            return Object.keys({ ...this.errors, ...this.form.errors }).some((field) => {
                return field.startsWith(`sites.${index}.`) && !this.clearedErrors[field];
            });
        },
        submit() {
            this.clearedErrors = {};
            this.form.post('/institution/store', {
                preserveScroll: true
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

