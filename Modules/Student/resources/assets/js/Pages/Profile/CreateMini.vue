<template>
  <AuthenticatedLayout>
    <div class="container-fluid py-4">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card shadow">
            <div class="card-header bg-primary text-light">
              <h4 class="mb-0">
                <i class="bi bi-person-plus me-2"></i>
                Create Student Profile - Essential Information
              </h4>
            </div>

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
                <!-- Name Information -->
                <div class="mb-4">
                  <h6 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-person-badge me-2"></i>Name Information
                  </h6>
                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                      <input
                        id="first_name"
                        v-model="form.first_name"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.first_name }"
                        required
                      />
                      <div v-if="form.errors.first_name" class="invalid-feedback">
                        {{ form.errors.first_name }}
                      </div>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label for="middle_name" class="form-label">Middle Name</label>
                      <input
                        id="middle_name"
                        v-model="form.middle_name"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.middle_name }"
                      />
                      <div v-if="form.errors.middle_name" class="invalid-feedback">
                        {{ form.errors.middle_name }}
                      </div>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                      <input
                        id="last_name"
                        v-model="form.last_name"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.last_name }"
                        required
                      />
                      <div v-if="form.errors.last_name" class="invalid-feedback">
                        {{ form.errors.last_name }}
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Personal Details -->
                <div class="mb-4">
                  <h6 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-calendar me-2"></i>Personal Details
                  </h6>
                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                      <input
                        id="date_of_birth"
                        v-model="form.date_of_birth"
                        type="date"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.date_of_birth }"
                        required
                      />
                      <div v-if="form.errors.date_of_birth" class="invalid-feedback">
                        {{ form.errors.date_of_birth }}
                      </div>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label for="gender" class="form-label">Gender</label>
                      <select
                        id="gender"
                        v-model="form.gender"
                        class="form-select"
                        :class="{ 'is-invalid': form.errors.gender }"
                      >
                        <option v-if="form.gender == ''" value="">Select Gender</option>
                        <option value="man">Man/Boy</option>
                        <option value="woman">Woman/Girl</option>
                        <option value="non-binary">Non-binary</option>
                        <option value="unknown">Prefer not to answer</option>
                      </select>
                      <div v-if="form.errors.gender" class="invalid-feedback">
                        {{ form.errors.gender }}
                      </div>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label for="sex" class="form-label">Sex</label>
                      <select
                        id="sex"
                        v-model="form.sex"
                        class="form-select"
                        :class="{ 'is-invalid': form.errors.sex }"
                      >
                        <option v-if="form.sex == ''" value="">Select Sex</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="indeterminate">Indeterminate</option>
                        <option value="unknown">Prefer not to answer</option>
                      </select>
                      <div v-if="form.errors.sex" class="invalid-feedback">
                        {{ form.errors.sex }}
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Contact Information -->
                <div class="mb-4">
                  <h6 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-envelope me-2"></i>Contact Information
                  </h6>
                  <div class="row">
                    <div class="col-md-12 mb-3">
                      <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                      <input
                        id="email"
                        v-model="form.email_address"
                        type="email"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.email_address }"
                        required
                      />
                      <div v-if="form.errors.email_address" class="invalid-feedback">
                        {{ form.errors.email_address }}
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Identity Numbers -->
                <div class="mb-4">
                  <h6 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-card-text me-2"></i>Identity Numbers
                  </h6>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label for="sin" class="form-label">Social Insurance Number (SIN)</label>
                      <div class="input-group">
                        <input
                          id="sin"
                          v-model="form.social_insurance_number"
                          :type="showSIN ? 'text' : 'password'"
                          class="form-control"
                          :class="{ 'is-invalid': form.errors.social_insurance_number }"
                          placeholder="000-000-000"
                          maxlength="11"
                          @input="formatSIN"
                        />
                        <button 
                          class="btn btn-outline-secondary" 
                          type="button" 
                          @click="showSIN = !showSIN"
                          :aria-label="showSIN ? 'Hide SIN' : 'Show SIN'"
                        >
                          <i :class="showSIN ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                        </button>
                      </div>
                      <div v-if="form.errors.social_insurance_number" class="invalid-feedback d-block">
                        {{ form.errors.social_insurance_number }}
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label for="pen_number" class="form-label">Provincial Education Number (PEN)</label>
                      <input
                        id="pen_number"
                        v-model="form.provincial_education_number"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.provincial_education_number }"
                        placeholder="e.g., BC Student Number"
                      />
                      <div v-if="form.errors.provincial_education_number" class="invalid-feedback">
                        {{ form.errors.provincial_education_number }}
                      </div>
                    </div>
                  </div>
                </div>

              </div>

              <!-- Form Actions -->
              <div class="card-footer bg-light">
                <div class="d-flex justify-content-between align-items-center">
                  <Link :href="backUrl" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Cancel
                  </Link>
                  <button type="submit" class="btn btn-primary" :disabled="form.processing">
                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="bi bi-check-lg me-2"></i>
                    {{ form.processing ? 'Creating...' : 'Create Profile' }}
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
import { ref, watch } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '../../Layouts/Authenticated.vue'

const props = defineProps({
  backUrl: {
    type: String,
    default: '/student'
  }
})

// Toggle for showing/hiding SIN
const showSIN = ref(false)

// Initialize form with empty values
const form = useForm({
  first_name: '',
  middle_name: '',
  last_name: '',
  date_of_birth: '',
  gender: '',
  sex: '',
  email_address: '',
  social_insurance_number: '',
  provincial_education_number: '',
})


// Format SIN as 000-000-000 (only digits, max 9)
const formatSIN = (event) => {
  let value = event.target.value
  // Remove all non-digit characters
  let digits = value.replace(/\D/g, '')
  // Limit to 9 digits
  digits = digits.substring(0, 9)
  
  // Add dashes: 000-000-000
  if (digits.length > 6) {
    value = digits.substring(0, 3) + '-' + digits.substring(3, 6) + '-' + digits.substring(6)
  } else if (digits.length > 3) {
    value = digits.substring(0, 3) + '-' + digits.substring(3)
  } else {
    value = digits
  }
  
  form.social_insurance_number = value
}

const submitForm = () => {
  // Restructure data to match controller expectations
  const submitData = {
    first_name: form.first_name,
    middle_name: form.middle_name,
    last_name: form.last_name,
    date_of_birth: form.date_of_birth,
    gender: form.gender,
    sex: form.sex,
    email_address: form.email_address,
    social_insurance_number: form.social_insurance_number,
    provincial_education_number: form.provincial_education_number,
  }

  form.post('/student/profile', {
    data: submitData,
    onSuccess: () => {
      // Success handled by redirect
    },
    onError: (errors) => {
      console.error('Form submission errors:', errors)
    }
  })
}
</script>

<style scoped>
.card {
  border: none;
  border-radius: 0.5rem;
}

.card-header {
  border-top-left-radius: 0.5rem;
  border-top-right-radius: 0.5rem;
}

.border-bottom {
  border-bottom: 2px solid #dee2e6 !important;
}

h6 i {
  color: #6c757d;
}
</style>