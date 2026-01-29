<template>
  <AuthenticatedLayout>
    <div class="container-fluid py-4" style="min-height: 100vh; background-color: #f8f9fa;">
      <!-- Main Content -->
      <main class="container">
        <!-- Header Section -->
        <div class="text-center mb-5">
          <div class="d-inline-block position-relative">
            <h2 class="display-4 fw-bold text-success mb-3">
              Your Learning Gateway
            </h2>
            <div class="border-bottom border-success border-3 w-25 mx-auto mb-3"></div>
          </div>
          <p class="lead text-muted mb-4" style="max-width: 600px; margin: 0 auto;">
            Access your educational applications securely through BC Services Card authentication
          </p>
          <div class="d-inline-flex align-items-center px-3 py-2 bg-success bg-opacity-10 rounded-pill border border-success border-opacity-25">
            <i class="bi bi-shield-check text-success me-2"></i>
            <span class="small fw-medium text-success">Secured by BC Services Card</span>
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
                'border-warning border-opacity-25 opacity-50': app.status === 'offline'
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
                      'bg-danger bg-opacity-10 text-danger border-danger': app.status === 'offline'
                    }"
                  >
                    {{ app.status === 'offline' ? 'Offline' : (app.status === 'active' ? 'Available' : 'Unavailable') }}
                  </span>
                </div>
              </div>

              <div class="card-body">
                <p class="card-text text-muted">{{ app.description }}</p>

                <!-- Data Permissions Display -->
                <div v-if="app.profile_integration_ready == true && app.data_permission_groups && app.data_permission_groups.length > 0" class="mb-3">
                  <div class="small text-muted mb-2">
                    <i class="bi bi-shield-check me-1"></i>
                    <strong>Data Access Permissions:</strong>
                  </div>
                  <button 
                    type="button" 
                    class="btn btn-outline-success btn-sm"
                    data-bs-toggle="offcanvas" 
                    :data-bs-target="`#permissions-${app.id}`"
                    aria-controls="permissions"
                  >
                    <i class="bi bi-list-ul me-1"></i>
                    View {{ getTotalPermissions(app.data_permission_groups) }} permission{{ getTotalPermissions(app.data_permission_groups) !== 1 ? 's' : '' }}
                    <i class="bi bi-arrow-right ms-1"></i>
                  </button>
                </div>

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
                  <!-- Profile Complete or No Permissions - Show Launch Button -->
                  <div v-if="app.status === 'active' && (app.profile_complete || !app.has_permissions)" class="d-flex align-items-center">
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
                  
                  <!-- Profile Incomplete but has permissions - Show Modal Launch Button -->
                  <div v-else-if="app.status === 'active' && !app.profile_complete && app.has_permissions" class="d-flex align-items-center">
                    <a 
                      href="javascript:void(0)" 
                      @click="redirectToApp(app.id)"
                      class="text-decoration-none text-primary d-flex align-items-center"
                    >
                      <div class="bg-primary rounded-circle me-2" style="width: 8px; height: 8px;"></div>
                      <small class="fw-semibold">Click to Launch</small>
                      <i class="bi bi-box-arrow-up-right ms-2"></i>
                    </a>
                  </div>

                  <!-- Application Offline -->
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
              <div class="bg-success bg-opacity-10 rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                <i class="bi bi-mortarboard display-1 text-success"></i>
              </div>
            </div>
            
            <h3 class="fw-bold text-dark mb-3">Your Learning Journey Awaits</h3>
            <p class="text-muted lead mb-4">
              No BC Services Card applications are currently available, but new educational opportunities may become accessible soon.
            </p>
            
            <div class="row g-3">
              <div class="col-md-6">
                <div class="card border-success border-opacity-25 bg-success bg-opacity-5">
                  <div class="card-body text-center py-3">
                    <i class="bi bi-shield-check text-success fs-4 mb-2"></i>
                    <div class="fw-semibold text-success">Secure Authentication Ready</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card border-primary border-opacity-25 bg-primary bg-opacity-5">
                  <div class="card-body text-center py-3">
                    <i class="bi bi-lightning text-primary fs-4 mb-2"></i>
                    <div class="fw-semibold text-primary">Instant Access When Available</div>
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

    <!-- Application Launch Modal -->
    <div 
      v-if="selectedApp"
      class="modal fade" 
      :class="{ show: showModal }"
      :style="{ display: showModal ? 'block' : 'none' }"
      tabindex="-1" 
      aria-labelledby="applicationModalLabel" 
      aria-hidden="true"
      @click.self="closeModal"
    >
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header border-bottom">
            <h5 class="modal-title" id="applicationModalLabel">
              <i class="bi bi-rocket-takeoff me-2 text-primary"></i>
              Launch {{ selectedApp.name }}
            </h5>
            <button 
              type="button" 
              class="btn-close" 
              @click="closeModal"
              aria-label="Close"
            ></button>
          </div>
          
          <div class="modal-body">
            <!-- Info Message -->
            <div class="alert alert-info border-0 mb-4">
              <div class="d-flex align-items-start">
                <i class="bi bi-info-circle text-info me-3 mt-1 fs-5"></i>
                <div>
                  <h6 class="alert-heading mb-2">Application Data Requirements</h6>
                  <p class="mb-0">
                    <strong>{{ selectedApp.name }}</strong> will request the following information from you during the application process. 
                    You can choose to share your PDEX profile data to pre-fill these fields, or provide the information directly to the application.
                  </p>
                </div>
              </div>
            </div>

            <!-- Required Fields List -->
            <div v-if="getRequiredFields(selectedApp).length > 0" class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-danger mb-0">
                  <i class="bi bi-exclamation-triangle me-1"></i>
                  Required Information
                </h6>
                <div>
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-primary me-2" 
                    @click="selectAllRequired"
                  >
                    Select All
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-secondary" 
                    @click="deselectAllRequired"
                  >
                    Deselect All
                  </button>
                </div>
              </div>
              <div class="list-group list-group-flush border rounded">
                <div 
                  v-for="permission in getRequiredPermissions(selectedApp)" 
                  :key="`required-${permission.permission_id}`"
                  class="list-group-item d-flex justify-content-between align-items-center"
                >
                  <div class="d-flex align-items-center flex-grow-1">
                    <i class="bi bi-asterisk text-danger me-2 small"></i>
                    <span>{{ permission.display_name || getFieldLabel(permission.column_name) }}</span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span v-if="hasProfileValue(permission.column_name)" class="badge bg-success">
                      <i class="bi bi-check2 me-1"></i>Available
                    </span>
                    <span v-else class="badge bg-warning">
                      <i class="bi bi-exclamation-triangle me-1"></i>Missing
                    </span>
                    <div class="form-check">
                      <input
                        :id="`required-${permission.permission_id}`"
                        v-model="permissionSelections[permission.permission_id]"
                        class="form-check-input"
                        type="checkbox"
                      >
                      <label class="form-check-label" :for="`required-${permission.permission_id}`">
                        Share
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Optional Fields List -->
            <div v-if="getOptionalFields(selectedApp).length > 0" class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-primary mb-0">
                  <i class="bi bi-plus-circle me-1"></i>
                  Optional Information
                </h6>
                <div>
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-primary me-2" 
                    @click="selectAllOptional"
                  >
                    Select All
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-secondary" 
                    @click="deselectAllOptional"
                  >
                    Deselect All
                  </button>
                </div>
              </div>
              <div class="list-group list-group-flush border rounded">
                <div 
                  v-for="permission in getOptionalPermissions(selectedApp)" 
                  :key="`optional-${permission.permission_id}`"
                  class="list-group-item d-flex justify-content-between align-items-center"
                >
                  <div class="d-flex align-items-center flex-grow-1">
                    <i class="bi bi-plus text-primary me-2 small"></i>
                    <span>{{ permission.display_name || getFieldLabel(permission.column_name) }}</span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span v-if="hasProfileValue(permission.column_name)" class="badge bg-success">
                      <i class="bi bi-check2 me-1"></i>Available
                    </span>
                    <span v-else class="badge bg-secondary">
                      <i class="bi bi-dash me-1"></i>Not Set
                    </span>
                    <div class="form-check">
                      <input
                        :id="`optional-${permission.permission_id}`"
                        v-model="permissionSelections[permission.permission_id]"
                        class="form-check-input"
                        type="checkbox"
                      >
                      <label class="form-check-label" :for="`optional-${permission.permission_id}`">
                        Share
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Update Profile Section -->
            <div v-if="hasMissingRequiredFields(selectedApp)" class="alert alert-warning border-0 mb-4">
              <div class="d-flex align-items-start">
                <i class="bi bi-exclamation-triangle text-warning me-3 mt-1"></i>
                <div class="flex-grow-1">
                  <h6 class="alert-heading mb-2">Profile Update Recommended</h6>
                  <p class="mb-3">Some required fields are missing from your profile. You can update your profile now or provide this information directly to the application.</p>
                  <button type="button" class="btn btn-warning btn-sm" @click="updateProfileFirst">
                    <i class="bi bi-person-gear me-1"></i>
                    Update Profile First
                  </button>
                </div>
              </div>
            </div>

            <!-- No Fields Message -->
            <div v-if="getRequiredFields(selectedApp).length === 0 && getOptionalFields(selectedApp).length === 0" class="text-center py-4">
              <i class="bi bi-info-circle text-primary fs-2 mb-3"></i>
              <h6>No Additional Information Required</h6>
              <p class="text-muted">This application doesn't require any additional information from your profile.</p>
            </div>
          </div>
          
          <div class="modal-footer border-top">
            <button 
              type="button" 
              class="btn btn-secondary" 
              @click="closeModal"
            >
              <i class="bi bi-x-circle me-1"></i>
              Cancel
            </button>
            <button 
              type="button" 
              class="btn btn-primary"
              :disabled="form.processing"
              @click="launchApplication"
            >
              <span v-if="form.processing">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Launching...
              </span>
              <span v-else>
                <i class="bi bi-rocket-takeoff me-1"></i>
                Launch {{ selectedApp.name }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Backdrop -->
    <div 
      v-if="showModal" 
      class="modal-backdrop fade show"
      @click="closeModal"
    ></div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
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
  },
  profileData: {
    type: Object,
    default: null
  }
})

// Reactive state
const selectedApp = ref(null)
const showModal = ref(false)
const permissionSelections = ref({})

// Form state using Inertia's useForm
const form = useForm({
  permission_selections: {}
})

// Watch for changes to help debug
watch(selectedApp, (newApp) => {
  if (newApp) {
    console.log('Selected app changed:', newApp)
    console.log('App data_permission_groups:', newApp.data_permission_groups)
    
    // Debug the computed fields
    const required = getRequiredFields(newApp)
    const optional = getOptionalFields(newApp)
    console.log('Computed required fields:', required)
    console.log('Computed optional fields:', optional)
  }
})

watch(() => props.profileData, (newData) => {
  console.log('Profile data changed:', newData)
}, { deep: true })

const redirectToApp = (appId) => {
  const app = props.applications.find(a => a.id === appId)
  
  if (!app) return
  
  // If application has permissions, show modal instead of direct redirect
  if (app.profile_integration_ready == true && app.has_permissions) {
    selectedApp.value = app
    prepareFormData(app)
    showModal.value = true
  } else {
    // Direct redirect for apps without permissions
    window.open(`/gateway/${appId}`, '_blank')
  }
}

const prepareFormData = (app) => {
  // Initialize permission selections based on app permissions
  const selections = {}
  
  if (app.data_permission_groups) {
    app.data_permission_groups.forEach(group => {
      group.permissions.forEach(permission => {
        if (permission.permission_id) {
          // Use existing selection if available, otherwise default to required status
          selections[permission.permission_id] = permission.is_selected !== undefined 
            ? permission.is_selected 
            : permission.is_required
        }
      })
    })
  }
  
  permissionSelections.value = selections
  form.permission_selections = selections
  
  // Debug: log the app data and what fields we're getting
  // console.log('App data:', app)
  // console.log('Permission selections initialized:', selections)
  // console.log('Required fields:', getRequiredFields(app))
  // console.log('Optional fields:', getOptionalFields(app))
  // console.log('Profile data:', props.profileData)
}

const closeModal = () => {
  showModal.value = false
  selectedApp.value = null
  permissionSelections.value = {}
  form.reset()
  form.clearErrors()
}

const canSubmit = () => {
  // Always allow submission since we're not requiring form completion
  return !form.processing
}

// Watch for permission selection changes and update form
watch(permissionSelections, (newSelections) => {
  form.permission_selections = newSelections
}, { deep: true })

const selectAllPermissions = () => {
  if (!selectedApp.value || !selectedApp.value.data_permission_groups) return
  
  const selections = {}
  selectedApp.value.data_permission_groups.forEach(group => {
    group.permissions.forEach(permission => {
      if (permission.permission_id) {
        selections[permission.permission_id] = true
      }
    })
  })
  
  permissionSelections.value = selections
}

const deselectAllPermissions = () => {
  if (!selectedApp.value || !selectedApp.value.data_permission_groups) return
  
  const selections = {}
  selectedApp.value.data_permission_groups.forEach(group => {
    group.permissions.forEach(permission => {
      if (permission.permission_id) {
        // Allow deselecting all permissions - even required ones
        selections[permission.permission_id] = false
      }
    })
  })
  
  permissionSelections.value = selections
}

const selectAllRequired = () => {
  if (!selectedApp.value) return
  
  const requiredPermissions = getRequiredPermissions(selectedApp.value)
  const selections = { ...permissionSelections.value }
  
  requiredPermissions.forEach(permission => {
    if (permission.permission_id) {
      selections[permission.permission_id] = true
    }
  })
  
  permissionSelections.value = selections
}

const deselectAllRequired = () => {
  if (!selectedApp.value) return
  
  const requiredPermissions = getRequiredPermissions(selectedApp.value)
  const selections = { ...permissionSelections.value }
  
  requiredPermissions.forEach(permission => {
    if (permission.permission_id) {
      selections[permission.permission_id] = false
    }
  })
  
  permissionSelections.value = selections
}

const selectAllOptional = () => {
  if (!selectedApp.value) return
  
  const optionalPermissions = getOptionalPermissions(selectedApp.value)
  const selections = { ...permissionSelections.value }
  
  optionalPermissions.forEach(permission => {
    if (permission.permission_id) {
      selections[permission.permission_id] = true
    }
  })
  
  permissionSelections.value = selections
}

const deselectAllOptional = () => {
  if (!selectedApp.value) return
  
  const optionalPermissions = getOptionalPermissions(selectedApp.value)
  const selections = { ...permissionSelections.value }
  
  optionalPermissions.forEach(permission => {
    if (permission.permission_id) {
      selections[permission.permission_id] = false
    }
  })
  
  permissionSelections.value = selections
}

const hasProfileValue = (field) => {
  if (!props.profileData) return false
  
  // Define field mappings to profile sections
  const fieldMappings = {
    // General/Individual fields
    'first_name': 'general',
    'last_name': 'general', 
    'middle_name': 'general',
    'preferred_first_name': 'general',
    'preferred_last_name': 'general',
    'preferred_name': 'general',
    'date_of_birth': 'general',
    'gender': 'general',
    'sex': 'general',
    'preferred_pronouns': 'general',
    'phone': 'general',
    'phone_number': 'general',
    'alternate_phone': 'general',
    'alternate_phone_number': 'general',
    'email': 'general',
    'email_address': 'general',
    'alternate_email': 'general',
    'government_issued_id': 'general',
    'social_insurance_number': 'general',
    'provincial_education_number': 'general',
    'disability_status': 'general',
    'accommodation_needs': 'general',
    
    // Address fields - check if any address exists
    'street_address_1': 'addresses',
    'address_line1': 'addresses',
    'street_address_2': 'addresses', 
    'address_line2': 'addresses',
    'city': 'addresses',
    'province_state': 'addresses',
    'province': 'addresses',
    'postal_zip_code': 'addresses',
    'postal_code': 'addresses',
    'country': 'addresses',
    
    // Employment fields - check if any employment exists  
    'employer_name': 'employments',
    'position_title': 'employments',
    'job_title': 'employments',
    'industry': 'employments',
    'employer_industry': 'employments',
    'employment_status': 'employments',
    'start_date': 'employments',
    'employment_start_date': 'employments',
    'end_date': 'employments',
    'employment_end_date': 'employments',
    'is_current': 'employments',
    'is_receiving_employment_insurance': 'employments',
    'is_participating_in_work_study_program': 'employments',
    'is_looking_for_work': 'employments',
    'work_hours_per_week': 'employments',
    'monthly_income': 'employments',
    'is_job_related_to_program': 'employments',
    'has_career_plan': 'employments',
    
    // Identity fields - check if any identity exists
    'identity_type': 'identities',
    'identity_number': 'identities',
    'issuing_country': 'identities',
    'issuing_province_state': 'identities',
    'issue_date': 'identities',
    'expiry_date': 'identities',
    'citizenship_status': 'identities',
    'country_of_birth': 'identities',
    'indigenous_status': 'identities',
    'racial_identity': 'identities'
  }
  
  const section = fieldMappings[field]
  
  if (!section) {
    // Field not mapped, try direct access as fallback
    const value = props.profileData[field]
    return value !== null && value !== undefined && value !== ''
  }
  
  if (section === 'general') {
    const value = props.profileData.general?.[field]
    return value !== null && value !== undefined && value !== ''
  } else if (section === 'addresses') {
    // Check if any address has this field with a value
    const addresses = props.profileData.addresses
    if (!addresses || !Array.isArray(addresses) || addresses.length === 0) return false
    return addresses.some(addr => {
      const value = addr[field]
      return value !== null && value !== undefined && value !== ''
    })
  } else if (section === 'employments') {
    // Check if any employment has this field with a value
    const employments = props.profileData.employments
    if (!employments || !Array.isArray(employments) || employments.length === 0) return false
    return employments.some(emp => {
      const value = emp[field]
      return value !== null && value !== undefined && value !== ''
    })
  } else if (section === 'identities') {
    // Check if any identity has this field with a value
    const identities = props.profileData.identities
    if (!identities || !Array.isArray(identities) || identities.length === 0) return false
    return identities.some(id => {
      const value = id[field]
      return value !== null && value !== undefined && value !== ''
    })
  }
  
  return false
}

const hasMissingRequiredFields = (app) => {
  const requiredFields = getRequiredFields(app)
  return requiredFields.some(field => !hasProfileValue(field))
}

const updateProfileFirst = () => {
  // Redirect to profile edit page
  router.visit('/student/profile/edit')
}

const getRequiredFields = (app) => {
  const requiredFields = []
  
  if (app.data_permission_groups) {
    app.data_permission_groups.forEach(group => {
      group.permissions.forEach(permission => {
        if (permission.is_required === true) {
          requiredFields.push(permission.column_name)
        }
      })
    })
  }
  
  return requiredFields
}

const getRequiredPermissions = (app) => {
  const requiredPermissions = []
  
  if (app.data_permission_groups) {
    app.data_permission_groups.forEach(group => {
      group.permissions.forEach(permission => {
        if (permission.is_required === true) {
          requiredPermissions.push(permission)
        }
      })
    })
  }
  
  return requiredPermissions
}

const getOptionalFields = (app) => {
  const optionalFields = []
  
  if (app.data_permission_groups) {
    app.data_permission_groups.forEach(group => {
      group.permissions.forEach(permission => {
        if (permission.is_required === false && permission.can_read) {
          optionalFields.push(permission.column_name)
        }
      })
    })
  }
  
  return optionalFields
}

const getOptionalPermissions = (app) => {
  const optionalPermissions = []
  
  if (app.data_permission_groups) {
    app.data_permission_groups.forEach(group => {
      group.permissions.forEach(permission => {
        if (permission.is_required === false && permission.can_read) {
          optionalPermissions.push(permission)
        }
      })
    })
  }
  
  return optionalPermissions
}

const getFieldLabel = (field) => {
  // Convert snake_case to Title Case
  return field.replace(/_/g, ' ')
    .replace(/\b\w/g, l => l.toUpperCase())
}

const launchApplication = async () => {
  if (!canSubmit()) return
  
  // console.log('Launching application:', selectedApp.value.id)
  // console.log('Permission selections:', permissionSelections.value)
  // console.log('Profile data available:', props.profileData)
  
  // Update form data before submission
  form.permission_selections = permissionSelections.value
  
  // Submit form using Inertia
  form.post(`/student/launch-application/${selectedApp.value.id}`, {
    onSuccess: (page) => {
      // console.log('Success response:', page)
      // console.log('selectedApp:', selectedApp)
      
      // Check for launch URL in flash data or redirect directly to gateway
      const launchUrl = page.props.flash?.launch_url || `/gateway/${selectedApp.value.id}`
      
      // Redirect to gateway route in same tab to handle the token
      // window.location.href = launchUrl
      window.open(`/gateway/${selectedApp.value.id}`, '_blank')
      closeModal()

    },
    onError: (errors) => {
      console.error('Launch errors:', errors)
      if (errors.message) {
        alert('Error: ' + errors.message)
      } else {
        alert('Failed to launch application. Please try again.')
      }
    }
  })
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

.application-card a:hover {
  text-decoration: underline !important;
}

.cursor-pointer {
  cursor: pointer;
}

/* Modal styling */
.modal.show {
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-backdrop {
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
  border: none;
  border-radius: 0.75rem;
  box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175);
}

.modal-header {
  background-color: #f8f9fa;
  border-radius: 0.75rem 0.75rem 0 0;
}

.modal-footer {
  background-color: #f8f9fa;
  border-radius: 0 0 0.75rem 0.75rem;
}

.form-control:focus,
.form-select:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.is-invalid {
  border-color: #dc3545;
}

.is-invalid:focus {
  border-color: #dc3545;
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
</style>
