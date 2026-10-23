<template>
  <AuthenticatedLayout>
    <div class="container-fluid py-4">
      <div class="row justify-content-center">
        <div class="col-lg-10">
          <div class="card shadow">
            <div class="card-header bg-primary text-light">
              <h4 class="mb-0">
                <i class="bi bi-person-gear me-2"></i>
                Edit Profile
              </h4>
            </div>
            
            <!-- Progress Bar -->
            <div class="card-body py-3 border-bottom">
              <div class="progress mb-3" style="height: 8px;">
                <div 
                  class="progress-bar bg-primary" 
                  role="progressbar" 
                  :style="{ width: progressPercentage + '%' }"
                  :aria-valuenow="progressPercentage" 
                  aria-valuemin="0" 
                  aria-valuemax="100"
                ></div>
              </div>
              
              <div class="row text-center">
                <div class="col-3">
                  <div class="step" :class="{ 'active': currentStep >= 1, 'completed': currentStep > 1 }">
                    <div class="step-icon">
                      <i class="bi bi-person"></i>
                    </div>
                    <div class="step-title">General Info</div>
                  </div>
                </div>
                <div class="col-3">
                  <div class="step" :class="{ 'active': currentStep >= 2, 'completed': currentStep > 2 }">
                    <div class="step-icon">
                      <i class="bi bi-house"></i>
                    </div>
                    <div class="step-title">Address</div>
                  </div>
                </div>
                <div class="col-3">
                  <div class="step" :class="{ 'active': currentStep >= 3, 'completed': currentStep > 3 }">
                    <div class="step-icon">
                      <i class="bi bi-briefcase"></i>
                    </div>
                    <div class="step-title">Employment</div>
                  </div>
                </div>
                <div class="col-3">
                  <div class="step" :class="{ 'active': currentStep >= 4, 'completed': currentStep > 4 }">
                    <div class="step-icon">
                      <i class="bi bi-person-badge"></i>
                    </div>
                    <div class="step-title">Identity</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Form Content -->
            <form @submit.prevent="submitForm">
              <!-- Error Alert -->
              <div v-if="Object.keys(form.errors).length > 0" class="alert alert-danger mx-3 mt-3" role="alert">
                <div class="d-flex align-items-center">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>
                  <div>
                    <strong>Please correct the following errors:</strong>
                    <ul class="mb-0 mt-1">
                      <li v-for="(error, field) in form.errors" :key="field">
                        {{ error }}
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <div class="card-body">
                <!-- Step 1: General Information -->
                <GeneralInformationStep 
                  v-if="currentStep === 1"
                  :form="form.general"
                  :errors="form.errors"
                  :countries="countries"
                  :config="formConfig"
                  @update:form="(data) => form.general = data"
                />

                <!-- Step 2: Address Information -->
                <AddressInformationStep 
                  v-if="currentStep === 2"
                  :form="form.addresses"
                  :errors="form.errors"
                  :countries="countries"
                  :config="formConfig"
                  @update:form="(data) => form.addresses = data"
                />

                <!-- Step 3: Employment Information -->
                <EmploymentInformationStep 
                  v-if="currentStep === 3"
                  :form="form.employments"
                  :errors="form.errors"
                  :config="formConfig"
                  @update:form="(data) => form.employments = data"
                />

                <!-- Step 4: Identity Information -->
                <IdentityInformationStep 
                  v-if="currentStep === 4"
                  :form="form.identities"
                  :errors="form.errors"
                  :config="formConfig"
                  @update:form="(data) => form.identities = data"
                />
              </div>

              <!-- Navigation Buttons -->
              <div class="card-footer bg-light">
                <div class="d-flex justify-content-between">
                  <button 
                    type="button" 
                    class="btn btn-outline-secondary"
                    @click="previousStep"
                    :disabled="currentStep === 1"
                  >
                    <i class="bi bi-arrow-left me-2"></i>
                    Previous
                  </button>

                  <div class="text-muted">
                    Step {{ currentStep }} of 4
                  </div>

                  <button 
                    v-if="currentStep < 4"
                    type="button" 
                    class="btn btn-primary"
                    @click="nextStep"
                    :disabled="!canProceedToNextStep"
                  >
                    Next
                    <i class="bi bi-arrow-right ms-2"></i>
                  </button>

                  <button 
                    v-else
                    type="submit" 
                    class="btn btn-success"
                    :disabled="form.processing || !canSubmitForm"
                  >
                    <i class="bi bi-save me-2"></i>
                    Update Profile
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '../../Layouts/Authenticated.vue'
import GeneralInformationStep from './Steps/GeneralInformationStep.vue'
import AddressInformationStep from './Steps/AddressInformationStep.vue'
import EmploymentInformationStep from './Steps/EmploymentInformationStep.vue'
import IdentityInformationStep from './Steps/IdentityInformationStep.vue'

// Props
const props = defineProps({
  individual: {
    type: Object,
    required: true
  },
  countries: {
    type: Array,
    default: () => []
  },
  formConfig: {
    type: Object,
    default: () => ({})
  }
})

// Helper function to format date for input[type="date"]
const formatDateForInput = (dateString) => {
  if (!dateString) return ''
  // Extract just the date part (YYYY-MM-DD) from ISO timestamp
  return dateString.split('T')[0]
}

// Current step tracking
const currentStep = ref(1)

// Prepare individual data with formatted dates
const prepareIndividualData = (data) => {
  const prepared = { ...data }
  
  // Format date_of_birth in general section if it exists
  if (prepared.general?.date_of_birth) {
    prepared.general.date_of_birth = formatDateForInput(prepared.general.date_of_birth)
  }
  
  return prepared
}

// Form data with existing individual data
const form = useForm(prepareIndividualData(props.individual))

// Set mailing address flag if mailing address exists
onMounted(() => {

})

// Computed properties
const progressPercentage = computed(() => {
  return (currentStep.value / 4) * 100
})

const canProceedToNextStep = computed(() => {
  switch (currentStep.value) {
    case 1:
      return form.general.first_name && 
             form.general.last_name && 
             form.general.email_address
    case 2:
      return true // Employment is optional
      // return form.current_address.address_line1 && 
      //        form.current_address.city && 
      //        form.current_address.province && 
      //        form.current_address.postal_code &&
      //        form.current_address.country
    case 3:
      return true // Employment is optional
    case 4:
      return true // Identity is optional
    default:
      return false
  }
})

const canSubmitForm = computed(() => {
  return canProceedToNextStep.value
})

// Methods
const nextStep = () => {
  if (currentStep.value < 4 && canProceedToNextStep.value) {
    currentStep.value++
  }
}

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const submitForm = () => {
  if (!canSubmitForm.value) return

  console.log('Original form data:', form) // Debug log

  // Transform the form data to match backend expectations
  const transformedData = {
    // General information fields (flatten to root level, excluding nested objects)
    ...Object.fromEntries(
      Object.entries(form.general || {}).filter(([key]) => 
        !['addresses', 'current_address', 'current_employment', 'identity', 'employments', 'identities', 'isDirty', 'errors', 'hasErrors', 'processing', 'progress', 'wasSuccessful', 'recentlySuccessful', '__rememberable'].includes(key)
      )
    )
  }

  // Remove system fields from individual data
  delete transformedData.id
  delete transformedData.guid
  delete transformedData.user_guid
  delete transformedData.status
  delete transformedData.verification_status
  delete transformedData.metadata
  delete transformedData.notes
  delete transformedData.email_verified_at
  delete transformedData.last_login_at
  delete transformedData.created_at
  delete transformedData.updated_at
  delete transformedData.deleted_at
  delete transformedData.version_number

  // Add address data
  const currentAddr = form.addresses?.find(addr => addr.is_primary)
  if (currentAddr) {
    // Clean address data - remove system fields
    const { id, individual_id, user_id, address_type, created_at, updated_at, version_number, latest_version, ...cleanCurrentAddr } = currentAddr
    transformedData.current_address = cleanCurrentAddr
  }

  const mailingAddr = form.addresses?.find(addr => !addr.is_primary)
  if (mailingAddr) {
    // Clean address data - remove system fields
    const { id, individual_id, user_id, address_type, created_at, updated_at, version_number, latest_version, ...cleanMailingAddr } = mailingAddr
    transformedData.mailing_address = cleanMailingAddr
    transformedData.use_different_mailing_address = true
  } else {
    transformedData.use_different_mailing_address = false
  }

  // Add employment data - handle the mixed object structure
  let employment = null
  if (form.employments) {
    if (Array.isArray(form.employments)) {
      employment = form.employments[0]
    } else if (typeof form.employments === 'object') {
      // Handle mixed object with array element and individual fields
      const baseEmployment = form.employments['0'] || {}
      const individualFields = Object.fromEntries(
        Object.entries(form.employments).filter(([key]) => key !== '0')
      )
      employment = { ...baseEmployment, ...individualFields }
    }
  }
  
  if (employment) {
    // Clean employment data - remove system fields
    const { id, individual_id, user_id, is_current, created_at, updated_at, version_number, latest_version, ...cleanEmployment } = employment
    transformedData.current_employment = cleanEmployment
  }

  // Add identity data - handle the mixed object structure  
  let identity = null
  if (form.identities) {
    if (Array.isArray(form.identities)) {
      identity = form.identities[0]
    } else if (typeof form.identities === 'object') {
      // Handle mixed object with array element and individual fields
      const baseIdentity = form.identities['0'] || {}
      const individualFields = Object.fromEntries(
        Object.entries(form.identities).filter(([key]) => key !== '0')
      )
      identity = { ...baseIdentity, ...individualFields }
    }
  }
  
  if (identity) {
    // Clean identity data - remove system fields
    const { id, individual_id, user_id, created_at, updated_at, version_number, latest_version, ...cleanIdentity } = identity
    transformedData.identity = cleanIdentity
  }

  console.log('Transformed data:', transformedData) // Debug log

  // Create new form with transformed data for submission
  const submissionForm = useForm(transformedData)
  
  submissionForm.put('/student/profile', {
      onSuccess: () => {
        // Success handled by redirect
      },
      onError: (errors) => {
        console.error('Form submission errors:', errors)
        
        // Copy errors back to original form
        form.errors = errors
        
        // Navigate to the first step with errors
        if (hasStepErrors(1)) {
          currentStep.value = 1
        } else if (hasStepErrors(2)) {
          currentStep.value = 2
        } else if (hasStepErrors(3)) {
          currentStep.value = 3
        } else if (hasStepErrors(4)) {
          currentStep.value = 4
        }
      }
    })
}

// Helper function to check if a step has errors
const hasStepErrors = (step) => {
  const errorKeys = Object.keys(form.errors)
  
  switch (step) {
    case 1: // General Information
      return errorKeys.some(key => 
        key.includes('first_name') || 
        key.includes('last_name') || 
        key.includes('email_address')
      )

    default:
      return false
  }
}
</script>

<style scoped>
.step {
  position: relative;
  padding: 10px 0;
}

.step-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: #e9ecef;
  color: #6c757d;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 8px;
  transition: all 0.3s ease;
}

.step-title {
  font-size: 0.875rem;
  color: #6c757d;
  font-weight: 500;
}

.step.active .step-icon {
  background-color: #0d6efd;
  color: #ffffff;
}

.step.active .step-title {
  color: #0d6efd;
  font-weight: 600;
}

.step.completed .step-icon {
  background-color: #198754;
  color: white;
}

.step.completed .step-title {
  color: #198754;
}



@media (max-width: 768px) {
  .step-title {
    font-size: 0.75rem;
  }
  
  .step-icon {
    width: 32px;
    height: 32px;
    font-size: 0.875rem;
  }
}
</style>
