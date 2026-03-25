<template>
  <AuthenticatedLayout>
    <div class="container-fluid py-4" style="min-height: 100vh; background-color: #f8f9fa;">
      <!-- Main Content -->
      <main class="container">
        <!-- Header Section with Organization Theme (Orange) -->
        <div class="text-center mb-5">
          <div class="d-inline-block position-relative">
            <h2 class="display-4 fw-bold mb-3 text-warning">
              Organization Portal
            </h2>
            <div class="border-bottom border-warning border-3 w-25 mx-auto mb-3"></div>
          </div>
          <p class="lead text-muted mb-4" style="max-width: 600px; margin: 0 auto;">
            Access organizational applications and educational services securely<br/>through Post Secondary Data Exchange
          </p>
          <div class="d-inline-flex align-items-center px-3 py-2 bg-warning bg-opacity-10 rounded-pill border border-warning border-opacity-25">
            <i class="bi bi-buildings text-warning me-2"></i>
            <span class="small fw-medium text-warning">{{ user.organization }}</span>
          </div>
        </div>

        <!-- Applications Grid -->
        <div v-if="applications.length > 0" class="row g-4">
          <div 
            v-for="app in applications" 
            :key="app.id"
            class="col-12 col-md-6 col-xl-4 mb-4"
          >
            <div 
              class="card h-100 border-0 shadow-sm application-card"
              :class="{
                'border-success border-opacity-25': app.status === 'active',
                'border-warning border-opacity-25  opacity-50': app.status === 'offline'
              }"
            >
              <div class="card-header bg-opacity-10 border-0 border-top border-3"
              :class="{
                'bg-success border-success': app.status === 'active',
                'bg-secondary border-danger': app.status === 'offline'
              }"
              >
                <div class="d-flex justify-content-between align-items-start">
                  <h5 class="card-title text-dark mb-2">{{ app.name }}</h5>
                  
                  <!-- Status Badge -->
                  <span 
                    class="badge rounded-pill border"
                    :class="{
                      'bg-success bg-opacity-10 text-success border-success': app.status === 'active',
                      'bg-danger bg-opacity-10 text-danger border-danger': app.status === 'offline',
                    }"
                  >
                    {{ app.status === 'offline' ? 'Offline' : (app.status === 'active' ? 'Active' : 'Unavailable') }}
                  </span>
                </div>
              </div>

              <div class="card-body">
                <p class="card-text text-muted">{{ app.description }}</p>

                <!-- Data Permissions Display - relative only to students -->
                <!-- <div v-if="app.data_permission_groups && app.data_permission_groups.length > 0" class="mb-3">
                  <div class="small text-muted mb-2">
                    <i class="bi bi-shield-check me-1"></i>
                    <strong>Data Access Permissions:</strong>
                  </div>
                  <button 
                    type="button" 
                    class="btn btn-outline-warning btn-sm"
                    data-bs-toggle="offcanvas" 
                    :data-bs-target="`#permissions-${app.id}`"
                    aria-controls="permissions"
                  >
                    <i class="bi bi-list-ul me-1"></i>
                    View {{ getTotalPermissions(app.data_permission_groups) }} permission{{ getTotalPermissions(app.data_permission_groups) !== 1 ? 's' : '' }}
                    <i class="bi bi-arrow-right ms-1"></i>
                  </button>
                </div> -->

                <!-- Alert Message -->
                <div v-if="app.alert_message" class="alert border-0 mb-3"
                     :class="{
                       'alert-success bg-success bg-opacity-10': app.status === 'active',
                       'alert-warning bg-warning bg-opacity-10': app.status === 'offline'
                     }">
                  <div class="d-flex align-items-start">
                    <i 
                      class="me-2 mt-1"
                      :class="{
                        'bi bi-info-circle text-success': app.status === 'active',
                        'bi bi-exclamation-triangle text-warning': app.status === 'offline'
                      }"
                    ></i>
                    <small class="fw-medium">{{ app.alert_message }}</small>
                  </div>
                </div>
              </div>

              <div class="card-footer bg-transparent border-0 pt-0">
                <!-- Info Label (if available) -->
                <div v-if="app.info_label && app.info_url" class="mb-3">
                  <a 
                    :href="app.info_url" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    class="text-decoration-none text-primary"
                  >
                    <i class="bi bi-info-circle me-2"></i>
                    <small class="fw-medium">{{ app.info_label }}</small>
                  </a>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                  <div v-if="app.status === 'active'" class="d-flex align-items-center">
                    <a 
                      href="javascript:void(0)" 
                      @click="redirectToApp(app.id)"
                      class="text-decoration-none text-success d-flex align-items-center"
                    >
                      <div class="bg-success rounded-circle me-2" style="width: 8px; height: 8px;"></div>
                      <small class="fw-semibold">Click to Launch</small>
                      <i class="bi bi-box-arrow-up-right ms-2"></i>
                    </a>
                  </div>
                  <div v-else class="d-flex align-items-center text-muted">
                    <i class="bi bi-lock me-2"></i>
                    <small class="fw-medium">Currently Unavailable</small>
                  </div>
                  
                  <!-- BC Services Card Badge -->
                  <!-- <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                    <i class="bi bi-person-badge me-1"></i>BCSC
                  </span> -->
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- No Applications State -->
        <div v-else class="text-center py-5">
          <div class="mx-auto" style="max-width: 500px;">
            <div class="mb-4">
              <div class="bg-warning bg-opacity-10 rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                <i class="bi bi-bank display-1 text-warning"></i>
              </div>
            </div>
            
            <h3 class="fw-bold text-dark mb-3">Organization Services</h3>
            <p class="text-muted lead mb-4">
              No organizational applications are currently available for your access level.
            </p>
            
            <div class="row g-3">
              <div class="col-md-6">
                <div class="card border-warning border-opacity-25 bg-warning bg-opacity-5">
                  <div class="card-body text-center py-3">
                    <i class="bi bi-shield-check text-warning fs-4 mb-2"></i>
                    <div class="fw-semibold text-warning">Organization Authentication</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card border-primary border-opacity-25 bg-primary bg-opacity-5">
                  <div class="card-body text-center py-3">
                    <i class="bi bi-lightning text-primary fs-4 mb-2"></i>
                    <div class="fw-semibold text-primary">Service Access Ready</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Data Permissions Offcanvas Components -->
    <DataPermissionsOffcanvas
      v-for="app in applications"
      :key="`permissions-${app.id}`"
      :offcanvas-id="`permissions-${app.id}`"
      :application-name="app.name"
      :permission-groups="app.data_permission_groups || []"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue'
import AuthenticatedLayout from '../Layouts/Authenticated.vue'
import DataPermissionsOffcanvas from '@/Components/DataPermissionsOffcanvas.vue'

// Props
const props = defineProps({
  applications: {
    type: Array,
    required: true
  },
  user: {
    type: Object,
    required: true
  }
})

// Methods
const redirectToApp = (appId) => {
  if (appId) {
    // Use the centralized gateway route and open in new tab
    const app = props.applications.find(a => a.id === appId)
    if (app) {
      window.open(`/gateway/${app.guid}`, '_blank')
    }
  }
}

const getTotalPermissions = (permissionGroups) => {
  return permissionGroups.reduce((total, group) => total + group.permissions.length, 0)
}
</script>

<style scoped>
.application-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.application-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.cursor-pointer {
  cursor: pointer;
}
</style>
