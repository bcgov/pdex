<template>
  <AuthenticatedLayout>
    <div class="container-fluid py-4">
      <div class="row justify-content-center">
        <div class="col-lg-10">
          <div class="card shadow">
            <div class="card-header bg-primary text-white">
              <h4 class="mb-0">
                <i class="bi bi-person-plus me-2"></i>
                Create Profile
              </h4>
            </div>
            
            <!-- Progress Bar -->
            <div class="card-body py-3 border-bottom">
              <div class="progress mb-3" style="height: 8px;">
                <div 
                  class="progress-bar bg-success" 
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
                  :form="form"
                  :errors="form.errors"
                  :countries="countries"
                  :config="formConfig"
                />

                <!-- Step 2: Address Information -->
                <AddressInformationStep 
                  v-if="currentStep === 2"
                  :current-address="form.current_address"
                  :mailing-address="form.mailing_address"
                  :use-different-mailing="form.use_different_mailing_address"
                  :countries="countries"
                  :errors="form.errors"
                  :config="formConfig"
                  @update:current-address="form.current_address = $event"
                  @update:mailing-address="form.mailing_address = $event"
                  @update:use-different-mailing="form.use_different_mailing_address = $event"
                />

                <!-- Step 3: Employment Information -->
                <EmploymentInformationStep 
                  v-if="currentStep === 3"
                  :form="form.current_employment"
                  :errors="form.errors"
                  :config="formConfig"
                />

                <!-- Step 4: Identity Information -->
                <IdentityInformationStep 
                  v-if="currentStep === 4"
                  :form="form.identity"
                  :errors="form.errors"
                  :config="formConfig"
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
                    Create Profile
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

// Initialize nested form structure with empty objects for each step
const form = useForm({
  // General information
  social_insurance_number: '',
  government_issued_id: '',
  provincial_education_number: '',
  first_name: '',
  middle_name: '',
  last_name: '',
  preferred_name: '',
  email_address: '',
  phone_number: '',
  alternate_phone_number: '',
  date_of_birth: '',
  gender: '',
  sex: '',
  preferred_pronouns: '',
  disability_status: '',
  accommodation_needs: '',

  // Address Information
  current_address: {
    address_line1: '',
    address_line2: '',
    city: '',
    province: '',
    postal_code: '',
    country: 'Canada'
  },
  use_different_mailing_address: false,
  mailing_address: {
    address_line1: '',
    address_line2: '',
    city: '',
    province: '',
    postal_code: '',
    country: ''
  },

  // Employment Information
  current_employment: {
    employment_status: '',
    is_looking_for_work: false,
    job_title: '',
    employer_name: '',
    employer_industry: '',
    employment_start_date: '',
    employment_end_date: '',
    work_hours_per_week: '',
    monthly_income: '',
    is_job_related_to_program: false,
    previous_job_title: '',
    previous_employer_name: '',
    previous_employment_start_date: '',
    previous_employment_end_date: '',
    reason_for_leaving: '',
    career_interest_area: '',
    desired_job_title: '',
    career_readiness_level: '',
    has_career_plan: false,
    is_receiving_employment_insurance: false,
    is_participating_in_work_study_program: false,
    barriers_to_employment: ''
  },

  // Identity Information
  identity: {
    citizenship_status: '',
    country_of_birth: '',
    language_spoken_at_home: '',
    years_in_country: '',
    refugee_status: false,
    immigration_status: '',
    indigenous_status: '',
    indigenous_group: '',
    band_affiliation: '',
    indigenous_status_card_number: '',
    is_registered_with_band: false,
    on_reserve_resident: false,
    racial_identity: '',
    is_visible_minority: '',
    receives_indigenous_support_services: false,
    receives_minority_support_services: false
  }
})

// Current step tracking
const currentStep = ref(1)


// Computed properties
const progressPercentage = computed(() => {
  return (currentStep.value / 4) * 100
})

const canProceedToNextStep = computed(() => {
  switch (currentStep.value) {
    case 1:
      return form.first_name && 
             form.last_name && 
             form.email_address
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

// Helper function to check if a step has validation errors
const hasStepErrors = (step, errors) => {
  switch (step) {
    case 1:
      return Object.keys(errors).some(key => 
        key.includes('social_insurance_number') || 
        key.includes('first_name') || 
        key.includes('last_name') || 
        key.includes('email_address') ||
        key.includes('government_issued_id') ||
        key.includes('date_of_birth') ||
        key.includes('gender') ||
        key.includes('disability_status') ||
        key.includes('accommodation_needs')
      )
    case 2:
      return Object.keys(errors).some(key => 
        key.includes('current_address') ||
        key.includes('mailing_address')
      )
    case 3:
      return Object.keys(errors).some(key => 
        key.includes('current_employment') ||
        key.includes('career_goals') ||
        key.includes('preferred_work_location')
      )
    case 4:
      return Object.keys(errors).some(key => 
        key.includes('identity.')
      )
    default:
      return false
  }
}

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

  // Transform nested form data to match validation expectations
  const formData = {
    // General fields (flat)
    social_insurance_number: form.social_insurance_number,
    government_issued_id: form.government_issued_id,
    first_name: form.first_name,
    middle_name: form.middle_name,
    last_name: form.last_name,
    preferred_name: form.preferred_name,
    email_address: form.email_address,
    phone_number: form.phone_number,
    alternate_phone_number: form.alternate_phone_number,
    date_of_birth: form.date_of_birth,
    gender: form.gender,
    preferred_pronouns: form.preferred_pronouns,
    disability_status: form.disability_status,
    accommodation_needs: form.accommodation_needs,
    
    // Address fields (nested under current_address)
    current_address: {
      street_address: form.current_address.address_line1,
      apartment_unit: form.current_address.address_line2,
      city: form.current_address.city,
      province_state: form.current_address.province,
      postal_code: form.current_address.postal_code,
      country: form.current_address.country
    },

    // Mailing address fields
    use_different_mailing_address: form.use_different_mailing_address,
    mailing_street_address: form.mailing_address.address_line1,
    mailing_apartment_unit: form.mailing_address.address_line2,
    mailing_city: form.mailing_address.city,
    mailing_province_state: form.mailing_address.province,
    mailing_postal_code: form.mailing_address.postal_code,
    mailing_country: form.mailing_address.country,
    
    // Employment fields (nested under current_employment)
    current_employment: {
      ...form.current_employment
    },
    
    // Identity fields (nested under identity)
    identity: {
      ...form.identity
    }
  }

  // Submit the transformed data
  form.transform(() => formData).post('/student/profile', {
    onSuccess: () => {
      // Success handled by redirect
    },
    onError: (errors) => {
      console.error('Form submission errors:', errors)
      
      // If there are validation errors, jump to the first step that has errors
      if (errors) {
        // Check which step has errors and navigate to it
        if (hasStepErrors(1, errors)) {
          currentStep.value = 1
        } else if (hasStepErrors(2, errors)) {
          currentStep.value = 2
        } else if (hasStepErrors(3, errors)) {
          currentStep.value = 3
        } else if (hasStepErrors(4, errors)) {
          currentStep.value = 4
        }
      }
    }
  })
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
  color: white;
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
