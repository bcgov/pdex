<template>
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">
        <i class="bi bi-person me-2"></i>
        General Information
      </h5>
      <p class="text-muted mb-0 small">Personal details and contact information</p>
    </div>
    <div class="card-body">
      
      <!-- Name Information -->
      <div class="mb-4">
        <h6 class="border-bottom pb-2 mb-3">
          <i class="bi bi-person-badge me-2"></i>Name Information
        </h6>
        <div class="row">
          <div v-if="cfg.isActive('first_name')" class="col-md-6 mb-3">
            <label for="first_name" class="form-label">{{ cfg.label('first_name', 'First Name') }} <span v-if="cfg.isRequired('first_name', true)" class="text-danger">*</span></label>
            <input
              id="first_name"
              v-model="form.first_name"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('first_name') }"
              :required="cfg.isRequired('first_name', true)"
            />
            <div v-if="hasFieldError('first_name')" class="invalid-feedback">
              {{ getFieldError('first_name') }}
            </div>
          </div>
          <div v-if="cfg.isActive('middle_name')" class="col-md-6 mb-3">
            <label for="middle_name" class="form-label">{{ cfg.label('middle_name', 'Middle Name') }} <span v-if="cfg.isRequired('middle_name')" class="text-danger">*</span></label>
            <input
              id="middle_name"
              v-model="form.middle_name"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('middle_name') }"
              :required="cfg.isRequired('middle_name')"
            />
            <div v-if="hasFieldError('middle_name')" class="invalid-feedback">
              {{ getFieldError('middle_name') }}
            </div>
          </div>
        </div>
        <div class="row">
          <div v-if="cfg.isActive('last_name')" class="col-md-6 mb-3">
            <label for="last_name" class="form-label">{{ cfg.label('last_name', 'Last Name') }} <span v-if="cfg.isRequired('last_name', true)" class="text-danger">*</span></label>
            <input
              id="last_name"
              v-model="form.last_name"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('last_name') }"
              :required="cfg.isRequired('last_name', true)"
            />
            <div v-if="hasFieldError('last_name')" class="invalid-feedback">
              {{ getFieldError('last_name') }}
            </div>
          </div>
          <div v-if="cfg.isActive('preferred_name')" class="col-md-6 mb-3">
            <label for="preferred_name" class="form-label">{{ cfg.label('preferred_name', 'Preferred Name') }} <span v-if="cfg.isRequired('preferred_name')" class="text-danger">*</span></label>
            <input
              id="preferred_name"
              v-model="form.preferred_name"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('preferred_name') }"
              :required="cfg.isRequired('preferred_name')"
            />
            <div v-if="hasFieldError('preferred_name')" class="invalid-feedback">
              {{ getFieldError('preferred_name') }}
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
          <div v-if="cfg.isActive('date_of_birth')" class="col-md-3 mb-3">
            <label for="date_of_birth" class="form-label">{{ cfg.label('date_of_birth', 'Date of Birth') }} <span v-if="cfg.isRequired('date_of_birth', true)" class="text-danger">*</span></label>
            <input
              id="date_of_birth"
              v-model="form.date_of_birth"
              type="date"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('date_of_birth') }"
              :required="cfg.isRequired('date_of_birth', true)"
            />
            <div v-if="hasFieldError('date_of_birth')" class="invalid-feedback">
              {{ getFieldError('date_of_birth') }}
            </div>
          </div>
          <div v-if="cfg.isActive('gender')" class="col-md-3 mb-3">
            <label for="gender" class="form-label">{{ cfg.label('gender', 'Gender') }} <span v-if="cfg.isRequired('gender')" class="text-danger">*</span></label>
            <select
              id="gender"
              v-model="form.gender"
              class="form-select"
              :class="{ 'is-invalid': hasFieldError('gender') }"
              :required="cfg.isRequired('gender')"
            >
              <option value="">{{ cfg.placeholder('gender', 'Select Gender') }}</option>
              <option v-for="opt in cfg.options('gender')" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <div v-if="hasFieldError('gender')" class="invalid-feedback">
              {{ getFieldError('gender') }}
            </div>
          </div>
          <div v-if="cfg.isActive('sex')" class="col-md-3 mb-3">
            <label for="sex" class="form-label">{{ cfg.label('sex', 'Sex') }} <span v-if="cfg.isRequired('sex')" class="text-danger">*</span></label>
            <select
              id="sex"
              v-model="form.sex"
              class="form-select"
              :class="{ 'is-invalid': hasFieldError('sex') }"
              :required="cfg.isRequired('sex')"
            >
              <option value="">{{ cfg.placeholder('sex', 'Select Sex') }}</option>
              <option v-for="opt in cfg.options('sex')" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <div v-if="hasFieldError('sex')" class="invalid-feedback">
              {{ getFieldError('sex') }}
            </div>
          </div>
          <div v-if="cfg.isActive('preferred_pronouns')" class="col-md-3 mb-3">
            <label for="preferred_pronouns" class="form-label">{{ cfg.label('preferred_pronouns', 'Preferred Pronouns') }} <span v-if="cfg.isRequired('preferred_pronouns')" class="text-danger">*</span></label>
            <input
              id="preferred_pronouns"
              v-model="form.preferred_pronouns"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('preferred_pronouns') }"
              :placeholder="cfg.placeholder('preferred_pronouns', 'e.g., he/him, she/her, they/them')"
              :required="cfg.isRequired('preferred_pronouns')"
            />
            <div v-if="hasFieldError('preferred_pronouns')" class="invalid-feedback">
              {{ getFieldError('preferred_pronouns') }}
            </div>
          </div>
        </div>
      </div>

      <!-- Contact Information -->
      <div class="mb-4">
        <h6 class="border-bottom pb-2 mb-3">
          <i class="bi bi-telephone me-2"></i>Contact Information
        </h6>
        <div class="row">
          <div v-if="cfg.isActive('email_address')" class="col-md-4 mb-3">
            <label for="email" class="form-label">{{ cfg.label('email_address', 'Email Address') }} <span v-if="cfg.isRequired('email_address', true)" class="text-danger">*</span></label>
            <input
              id="email"
              v-model="form.email_address"
              type="email"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('email_address') }"
              :required="cfg.isRequired('email_address', true)"
            />
            <div v-if="hasFieldError('email_address')" class="invalid-feedback">
              {{ getFieldError('email_address') }}
            </div>
          </div>
          <div v-if="cfg.isActive('phone_number')" class="col-md-4 mb-3">
            <label for="phone" class="form-label">{{ cfg.label('phone_number', 'Phone Number') }} <span v-if="cfg.isRequired('phone_number')" class="text-danger">*</span></label>
            <input
              id="phone"
              v-model="form.phone_number"
              type="tel"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('phone_number') }"
              :placeholder="cfg.placeholder('phone_number', '(000) 000-0000')"
              :required="cfg.isRequired('phone_number')"
            />
            <div v-if="hasFieldError('phone_number')" class="invalid-feedback">
              {{ getFieldError('phone_number') }}
            </div>
          </div>
          <div v-if="cfg.isActive('alternate_phone_number')" class="col-md-4 mb-3">
            <label for="alternate_phone" class="form-label">{{ cfg.label('alternate_phone_number', 'Alternate Phone Number') }} <span v-if="cfg.isRequired('alternate_phone_number')" class="text-danger">*</span></label>
            <input
              id="alternate_phone"
              v-model="form.alternate_phone_number"
              type="tel"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('alternate_phone_number') }"
              :placeholder="cfg.placeholder('alternate_phone_number', '(000) 000-0000')"
              :required="cfg.isRequired('alternate_phone_number')"
            />
            <div v-if="hasFieldError('alternate_phone_number')" class="invalid-feedback">
              {{ getFieldError('alternate_phone_number') }}
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
          <div v-if="cfg.isActive('social_insurance_number')" class="col-md-4 mb-3">
            <label for="sin" class="form-label">{{ cfg.label('social_insurance_number', 'Social Insurance Number (SIN)') }} <span v-if="cfg.isRequired('social_insurance_number')" class="text-danger">*</span></label>
            <input
              id="sin"
              v-model="form.social_insurance_number"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('social_insurance_number') }"
              :placeholder="cfg.placeholder('social_insurance_number', '000-000-000')"
              maxlength="11"
              :required="cfg.isRequired('social_insurance_number')"
            />
            <div v-if="hasFieldError('social_insurance_number')" class="invalid-feedback">
              {{ getFieldError('social_insurance_number') }}
            </div>
          </div>
          <div v-if="cfg.isActive('provincial_education_number')" class="col-md-4 mb-3">
            <label for="pen_number" class="form-label">{{ cfg.label('provincial_education_number', 'Provincial Education Number (PEN)') }} <span v-if="cfg.isRequired('provincial_education_number')" class="text-danger">*</span></label>
            <input
              id="pen_number"
              v-model="form.provincial_education_number"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('provincial_education_number') }"
              :placeholder="cfg.placeholder('provincial_education_number', 'e.g., BC Student Number')"
              :required="cfg.isRequired('provincial_education_number')"
            />
            <div v-if="hasFieldError('provincial_education_number')" class="invalid-feedback">
              {{ getFieldError('provincial_education_number') }}
            </div>
          </div>
          <div v-if="cfg.isActive('government_issued_id')" class="col-md-4 mb-3">
            <label for="government_id_number" class="form-label">{{ cfg.label('government_issued_id', 'Government ID Number') }} <span v-if="cfg.isRequired('government_issued_id')" class="text-danger">*</span></label>
            <input
              id="government_id_number"
              v-model="form.government_issued_id"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('government_issued_id') }"
              :placeholder="cfg.placeholder('government_issued_id', 'License, Health Card, etc.')"
              :required="cfg.isRequired('government_issued_id')"
            />
            <div v-if="hasFieldError('government_issued_id')" class="invalid-feedback">
              {{ getFieldError('government_issued_id') }}
            </div>
          </div>
        </div>
      </div>

      <!-- Accessibility -->
      <div class="mb-4">
        <h6 class="border-bottom pb-2 mb-3">
          <i class="bi bi-universal-access me-2"></i>Accessibility & Accommodation
        </h6>
        <div class="row">
          <div v-if="cfg.isActive('disability_status')" class="col-md-6 mb-3">
            <label for="disability_status" class="form-label">
              {{ cfg.label('disability_status', 'Disability Status') }}
              <span v-if="cfg.isRequired('disability_status')" class="text-danger">*</span>
            </label>
            <select
              id="disability_status"
              v-model="form.disability_status"
              class="form-select"
              :class="{ 'is-invalid': hasFieldError('disability_status') }"
              :required="cfg.isRequired('disability_status')"
            >
              <option value="">{{ cfg.placeholder('disability_status', 'Select an answer') }}</option>
              <option v-for="opt in cfg.options('disability_status')" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <div v-if="hasFieldError('disability_status')" class="invalid-feedback">
              {{ getFieldError('disability_status') }}
            </div>
          </div>
        </div>
        <div v-if="form.disability_status === 'yes' && cfg.isActive('accommodation_needs')" class="row">
          <div class="col-md-12 mb-3">
            <label for="accommodation_needs" class="form-label">{{ cfg.label('accommodation_needs', 'Accommodation Needs') }}</label>
            <textarea
              id="accommodation_needs"
              v-model="form.accommodation_needs"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('accommodation_needs') }"
              rows="3"
              :placeholder="cfg.placeholder('accommodation_needs', 'Please describe any accommodations you require')"
            ></textarea>
            <div v-if="hasFieldError('accommodation_needs')" class="invalid-feedback">
              {{ getFieldError('accommodation_needs') }}
            </div>
          </div>
        </div>
      </div>

      <!-- Additional Information (admin-managed fields) -->
      <div v-if="extraFields.length" class="mb-4">
        <h6 class="border-bottom pb-2 mb-3">
          <i class="bi bi-plus-square me-2"></i>Additional Information
        </h6>
        <div class="row">
          <div v-for="field in extraFields" :key="field.field_id" class="col-md-6 mb-3">
            <DynamicField
              :field="field"
              :model-value="form[field.field_id]"
              :error="getFieldError(field.field_id)"
              @update:model-value="(val) => (form[field.field_id] = val)"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, watchEffect, toRef, computed } from 'vue'
import { useFieldConfig } from '../../../composables/useFieldConfig'
import DynamicField from './DynamicField.vue'

const props = defineProps({
  form: Object,
  errors: Object,
  config: {
    type: Object,
    default: () => ({})
  }
})

// DB-driven field configuration (labels, required, options, placeholders, visibility)
const cfg = useFieldConfig(toRef(props, 'config'))

// Fields already rendered above with bespoke controls.
const KNOWN_FIELDS = [
  'first_name', 'middle_name', 'last_name', 'preferred_name',
  'date_of_birth', 'gender', 'sex', 'preferred_pronouns',
  'email_address', 'phone_number', 'alternate_phone_number',
  'social_insurance_number', 'provincial_education_number', 'government_issued_id',
  'disability_status', 'accommodation_needs'
]

// Admin-managed fields for this tab that have no bespoke control here.
const extraFields = computed(() =>
  Object.values(props.config || {})
    .filter((f) => f && f.tab === 'general' && f.is_active && !KNOWN_FIELDS.includes(f.field_id))
    .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
)

const emit = defineEmits(['update:form'])

// Helper function to update a field
const updateField = (field, value) => {
  const updatedForm = { ...props.form, [field]: value }
  emit('update:form', updatedForm)
}

// Create a reactive proxy object that syncs with props and emits changes
const form = reactive(new Proxy({}, {
  get(target, property) {
    return props.form?.[property] ?? (typeof props.form?.[property] === 'boolean' ? false : '')
  },
  set(target, property, value) {
    updateField(property, value)
    return true
  },
  has(target, property) {
    return property in (props.form || {})
  },
  ownKeys(target) {
    return Object.keys(props.form || {})
  }
}))

// Watch for prop changes and sync them to our reactive object
watchEffect(() => {
  // This ensures the proxy stays in sync with prop changes
  if (props.form) {
    Object.keys(props.form).forEach(key => {
      // Trigger reactivity by accessing the property
      form[key]
    })
  }
})

// Helper function to check for errors in multiple formats
const getFieldError = (fieldName) => {
  return props.errors[fieldName] || 
         props.errors[`general.${fieldName}`] || 
         null
}

const hasFieldError = (fieldName) => {
  return getFieldError(fieldName) !== null
}
</script>
