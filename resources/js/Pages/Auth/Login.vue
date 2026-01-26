<script>
import GuestLayout from '@/Layouts/Guest.vue';
import { Head } from '@inertiajs/vue3';
import { Button } from '@/Components/BCDesign/Button';
import ApplicationLoginLogo from '@/Components/ApplicationLoginLogo.vue';

export default {
    name: 'Login',
    components: {
        GuestLayout, 
        Head,
        ApplicationLoginLogo,
        Button
    },
    props: {
        loginAttempt: Boolean,
        hasAccess: Boolean,
        status: String,
        errors: Object,
    },
    computed: {
        errorMessage() {
            // Check for errors from middleware redirects
            if (this.$page.props.errors && this.$page.props.errors.error) {
                return this.$page.props.errors.error;
            }
            return null;
        }
    }
}
</script>

<style scoped>
/* .login-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 2rem 1rem;
} */

/* Custom primary color for BC Government branding */
.btn-primary {
    background-color: #003366;
    border-color: #003366;
}

.btn-primary:hover,
.btn-primary:focus {
    background-color: #002244;
    border-color: #002244;
}

.text-primary {
    color: #003366 !important;
}

.link-primary {
    color: #003366;
}

.link-primary:hover {
    color: #002244;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-title {
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.card-text {
    margin-bottom: 1rem;
    line-height: 1.5;
}
</style>
<template>
    <GuestLayout>
        <Head title="Log in" />

        <div class="login-container">
            <!-- Main Login Card -->
            <div class="card border-0 shadow-lg">
                <div class="card-body p-5 text-center">

                    <!-- Ministry Logo/Icon -->
                    <div class="text-center mb-8">
                        <a href="/login">
                            <ApplicationLoginLogo class="w-full max-w-md mx-auto" style="max-width: 500px; width: 100%;" />
                        </a>
                    </div>

                    <!-- Error Alert -->
                    <div v-if="errorMessage" class="alert alert-danger mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            <strong>{{ errorMessage }}</strong>
                        </div>
                    </div>

                    <!-- Status Alert -->
                    <div v-if="status" class="alert alert-success mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle me-2"></i>
                            <span>{{ status }}</span>
                        </div>
                    </div>

                    <!-- Main Heading -->
                    <h2 class="text-primary fw-bold mb-3">Welcome to PDEX</h2>
                    <p class="text-muted mb-5 lead">Post Secondary Data Exchange Portal</p>

                    <!-- Login Buttons for Different Identity Providers -->
                    <div class="d-grid gap-3 mb-4">
                        <!-- BCSC Login -->
                        <a href="/bcsc-login" class="btn btn-primary btn-lg px-5 py-3 shadow-sm">
                            <i class="bi bi-mortarboard me-2"></i>
                            Sign In as Learner (BC Services Card Account)
                        </a>
                        
                        <!-- BCeID Login -->
                        <a href="/bceid-login" class="btn btn-outline-primary btn-lg px-5 py-3 shadow-sm">
                            <i class="bi bi-bank me-2"></i>
                            Sign In as Institution (BCeID Business)
                        </a>
                        
                        <!-- IDIR Login -->
                        <a href="/idir-login" class="btn btn-outline-primary btn-lg px-5 py-3 shadow-sm">
                            <i class="bi bi-building me-2"></i>
                            Sign In as Ministry Staff (IDIR)
                        </a>
                    </div>

                    <!-- Access Types Information -->
                    <div class="row g-3 mt-4">
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light h-100">
                                <div class="text-primary mb-2">
                                    <i class="bi bi-mortarboard fs-4"></i>
                                </div>
                                <h6 class="fw-semibold mb-2">Learners</h6>
                                <small class="text-muted">BC Services Card Account authentication for learner portal access</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light h-100">
                                <div class="text-primary mb-2">
                                    <i class="bi bi-bank fs-4"></i>
                                </div>
                                <h6 class="fw-semibold mb-2">Institutions</h6>
                                <small class="text-muted">BCeID business authentication for institutional access</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light h-100">
                                <div class="text-primary mb-2">
                                    <i class="bi bi-building fs-4"></i>
                                </div>
                                <h6 class="fw-semibold mb-2">Ministry</h6>
                                <small class="text-muted">IDIR authentication for government staff access</small>
                            </div>
                        </div>
                    </div>

                    <!-- Help Links -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <a href="https://id.gov.bc.ca/account/" class="link-primary small text-decoration-none">
                                    <i class="bi bi-question-circle me-1"></i>
                                    BC Services Card Help
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="https://www.bceid.ca/register/" class="link-primary small text-decoration-none">
                                    <i class="bi bi-person-plus me-1"></i>
                                    Register for BCeID
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="#" class="link-primary small text-decoration-none">
                                    <i class="bi bi-info-circle me-1"></i>
                                    About PDEX
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="text-center mt-4">
                <div class="d-inline-flex align-items-center px-3 py-2 bg-success bg-opacity-10 rounded-pill border border-success border-opacity-25">
                    <i class="bi bi-patch-check text-success me-2"></i>
                    <small class="text-success fw-medium">Secured by BC Government Pathfinder SSO</small>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
