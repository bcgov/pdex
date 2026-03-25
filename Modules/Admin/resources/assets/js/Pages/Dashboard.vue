<template>
    <Authenticated>
        <Head title="Admin Dashboard" />
        <div class="container-fluid px-4 py-4">
            <div class="mb-4">
                <h1 class="h2 fw-bold text-bc-blue">Admin Dashboard</h1>
                <p class="text-muted">Manage users, applications, and system settings</p>
            </div>
            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <p class="card-text text-muted small mb-1">Users</p>
                                    <h4 class="card-title mb-0">{{ stats.totalUsers || 0 }}</h4>
                                </div>
                                <div class="p-3 rounded-circle bg-primary bg-opacity-10 text-primary">
                                    <i class="bi bi-people" style="font-size: 1.25rem;"></i>
                                </div>
                            </div>
                            <div class="d-flex align-items-center text-muted small">
                                <i class="bi bi-person-check me-2"></i>
                                <span>Active: {{ stats.activeUsers || 0 }}</span>
                            </div>
                            <div class="mt-2">
                                <Link href="/admin/users" class="text-decoration-none small text-primary">
                                    View users <i class="bi bi-arrow-right"></i>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <p class="card-text text-muted small mb-1">Applications</p>
                                    <h4 class="card-title mb-0">{{ stats.totalApplications || 0 }}</h4>
                                </div>
                                <div class="p-3 rounded-circle bg-info bg-opacity-10 text-info">
                                    <i class="bi bi-folder-check" style="font-size: 1.25rem;"></i>
                                </div>
                            </div>
                            <div class="d-flex align-items-center text-muted small">
                                <i class="bi bi-clock me-2"></i>
                                <span>Pending approvals: {{ stats.pendingApprovals || 0 }}</span>
                            </div>
                            <div class="mt-2">
                                <Link href="/admin/applications" class="text-decoration-none small text-info">
                                    View applications <i class="bi bi-arrow-right"></i>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <p class="card-text text-muted small mb-1">Institutions</p>
                                    <h4 class="card-title mb-0">{{ stats.totalInstitutions || 0 }}</h4>
                                </div>
                                <div class="p-3 rounded-circle bg-success bg-opacity-10 text-success">
                                    <i class="bi bi-bank2" style="font-size: 1.25rem;"></i>
                                </div>
                            </div>
                            <div class="d-flex align-items-center text-muted small">
                                <i class="bi bi-check-circle me-2"></i>
                                <span>Active: {{ stats.activeInstitutions || 0 }}</span>
                            </div>
                            <div class="mt-2">
                                <Link href="/admin/institutions" class="text-decoration-none small text-success">
                                    View institutions <i class="bi bi-arrow-right"></i>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4 mb-4">
                <div class="col-12 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title text-bc-blue mb-3">Applications Overview</h5>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Pending security</span>
                                <span class="fw-semibold">{{ stats.pendingSecurityApprovals || 0 }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Pending privacy</span>
                                <span class="fw-semibold">{{ stats.pendingPrivacyApprovals || 0 }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Offline</span>
                                <span class="fw-semibold">{{ stats.offlineApplications || 0 }}</span>
                            </div>
                            <div class="mt-3">
                                <div class="text-muted small mb-2">Status breakdown</div>
                                <div v-if="stats.applicationStatusBreakdown && Object.keys(stats.applicationStatusBreakdown).length">
                                    <div v-for="(count, status) in stats.applicationStatusBreakdown" :key="status" class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted text-capitalize">{{ status }}</span>
                                        <span class="small fw-semibold">{{ count }}</span>
                                    </div>
                                </div>
                                <div v-else class="text-muted small">No status data available.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title text-bc-blue mb-3">Institutions Overview</h5>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Active</span>
                                <span class="fw-semibold">{{ stats.activeInstitutions || 0 }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Inactive</span>
                                <span class="fw-semibold">{{ stats.inactiveInstitutions || 0 }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">With DLI</span>
                                <span class="fw-semibold">{{ stats.institutionsWithDli || 0 }}</span>
                            </div>
                            <div class="mt-3">
                                <div class="text-muted small mb-2">By type</div>
                                <div v-if="stats.institutionTypeBreakdown && Object.keys(stats.institutionTypeBreakdown).length">
                                    <div v-for="(count, type) in stats.institutionTypeBreakdown" :key="type" class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted">{{ type }}</span>
                                        <span class="small fw-semibold">{{ count }}</span>
                                    </div>
                                </div>
                                <div v-else class="text-muted small">No type data available.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title text-bc-blue mb-3">System Health</h5>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Failed jobs</span>
                                <span class="fw-semibold">{{ stats.failedJobs || 0 }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Alerting apps</span>
                                <span class="fw-semibold">{{ stats.alertingApplications || 0 }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Offline apps</span>
                                <span class="fw-semibold">{{ stats.offlineApplications || 0 }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="text-muted">System alerts</span>
                                <span class="fw-semibold">{{ stats.systemAlerts || 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="g-4">
                <div class="">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-bc-blue mb-3">Recent Activities</h5>
                            <div v-if="recentActivities && recentActivities.length > 0">
                                <div v-for="activity in recentActivities" :key="activity.id" class="d-flex align-items-start p-3 bg-light rounded mb-3">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="rounded-circle bg-bc-blue d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="bi bi-info-circle text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="fw-medium mb-1">{{ activity.title }}</p>
                                        <p class="text-muted small mb-1">{{ activity.description }}</p>
                                        <p class="text-muted small mb-0">{{ formatDate(activity.created_at) }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-5">
                                <i class="bi bi-clipboard-data text-muted mb-3" style="font-size: 3rem;"></i>
                                <p class="text-muted">No recent activities to show</p>
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
import AdminMenu from '../Components/Menu.vue';

export default {
    name: 'AdminHome',
    components: {
        Head,
        Link,
        Authenticated,
        AdminMenu
    },
    props: {
        stats: {
            type: Object,
            default: () => ({
                totalUsers: 0,
                activeUsers: 0,
                totalInstitutions: 0,
                activeInstitutions: 0,
                inactiveInstitutions: 0,
                institutionsWithDli: 0,
                institutionTypeBreakdown: {},
                totalApplications: 0,
                applicationStatusBreakdown: {},
                pendingApprovals: 0,
                pendingSecurityApprovals: 0,
                pendingPrivacyApprovals: 0,
                offlineApplications: 0,
                alertingApplications: 0,
                failedJobs: 0,
                systemAlerts: 0
            })
        },
        recentActivities: {
            type: Array,
            default: () => []
        }
    },
    methods: {
        formatDate(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleDateString('en-CA', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
}
</script>

<style scoped>
.text-bc-blue {
    color: #003366 !important;
}
.bg-bc-blue {
    background-color: #003366 !important;
}
</style>