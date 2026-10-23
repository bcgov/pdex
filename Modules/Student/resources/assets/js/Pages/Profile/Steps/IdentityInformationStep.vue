<template>
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">
        <i class="bi bi-person-badge me-2"></i>
        Identity & Cultural Information
      </h5>
      <p class="text-muted mb-0 small">Cultural identity, citizenship, and minority information</p>
    </div>
    <div class="card-body">
      <!-- Citizenship & Immigration -->
      <div class="mb-4">
        <h6 class="border-bottom pb-2 mb-3">
          <i class="bi bi-flag me-2"></i>Citizenship & Immigration Status
        </h6>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="citizenship_status" class="form-label">{{ cfg.label('citizenship_status', 'Citizenship Status') }} <span v-if="cfg.isRequired('citizenship_status')" class="text-danger">*</span></label>
            <select
              id="citizenship_status"
              v-model="form.citizenship_status"
              class="form-select"
              :class="{ 'is-invalid': hasFieldError('citizenship_status') }"
            >
              <option value="">{{ cfg.placeholder('citizenship_status', 'Select Status') }}</option>
              <option v-for="opt in cfg.options('citizenship_status')" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <div v-if="hasFieldError('citizenship_status')" class="invalid-feedback">
              {{ hasFieldError('citizenship_status') }}
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <label for="country_of_birth" class="form-label">{{ cfg.label('country_of_birth', 'Country of Birth') }}</label>
            <input
              id="country_of_birth"
              v-model="form.country_of_birth"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('country_of_birth') }"
              placeholder="e.g., Canada, Philippines, India"
            />
            <div v-if="hasFieldError('country_of_birth')" class="invalid-feedback">
              {{ hasFieldError('country_of_birth') }}
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="immigration_status" class="form-label">{{ cfg.label('immigration_status', 'Immigration Status') }} <span v-if="cfg.isRequired('immigration_status')" class="text-danger">*</span></label>
            <select
              id="immigration_status"
              v-model="form.immigration_status"
              class="form-select"
              :class="{ 'is-invalid': hasFieldError('immigration_status') }"
            >
              <option value="">{{ cfg.placeholder('immigration_status', 'Select Status') }}</option>
              <option v-for="opt in cfg.options('immigration_status')" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <div v-if="hasFieldError('immigration_status')" class="invalid-feedback">
              {{ hasFieldError('immigration_status') }}
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <label for="years_in_country" class="form-label">{{ cfg.label('years_in_country', 'Years in Canada') }}</label>
            <input
              id="years_in_country"
              v-model="form.years_in_country"
              type="number"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('years_in_country') }"
              min="0"
              max="100"
              placeholder="Years living in Canada"
            />
            <div v-if="hasFieldError('years_in_country')" class="invalid-feedback">
              {{ hasFieldError('years_in_country') }}
            </div>
          </div>
        </div>
      </div>

      <!-- Language & Cultural Background -->
      <div class="mb-4">
        <h6 class="border-bottom pb-2 mb-3">
          <i class="bi bi-translate me-2"></i>Language & Cultural Background
        </h6>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="language_spoken_at_home" class="form-label">{{ cfg.label('language_spoken_at_home', 'Language Spoken at Home') }}</label>
            <input
              id="language_spoken_at_home"
              v-model="form.language_spoken_at_home"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('language_spoken_at_home') }"
              placeholder="e.g., English, French, Tagalog, Mandarin"
            />
            <div v-if="hasFieldError('language_spoken_at_home')" class="invalid-feedback">
              {{ hasFieldError('language_spoken_at_home') }}
            </div>
          </div>
        </div>
      </div>

      <!-- Racial & Diversity Information -->
      <div class="mb-4">
        <h6 class="border-bottom pb-2 mb-3">
          <i class="bi bi-people me-2"></i>Racial & Diversity Information
        </h6>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="racial_identity" class="form-label">{{ cfg.label('racial_identity', 'Racial Identity') }}</label>
            <select
              id="racial_identity"
              v-model="form.racial_identity"
              class="form-select"
              :class="{ 'is-invalid': hasFieldError('racial_identity') }"
            >
              <option value="">{{ cfg.placeholder('racial_identity', 'Select Identity') }}</option>
              <option v-for="opt in cfg.options('racial_identity')" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <div v-if="hasFieldError('racial_identity')" class="invalid-feedback">
              {{ hasFieldError('racial_identity') }}
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <label for="is_visible_minority" class="form-label">
              {{ cfg.label('is_visible_minority', 'I identify as a visible minority') }}
            </label>
            <select
              id="is_visible_minority"
              v-model="form.is_visible_minority"
              class="form-select"
              :class="{ 'is-invalid': hasFieldError('is_visible_minority') }"
            >
              <option value="">{{ cfg.placeholder('is_visible_minority', 'Select an answer') }}</option>
              <option v-for="opt in cfg.options('is_visible_minority')" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <div v-if="hasFieldError('is_visible_minority')" class="invalid-feedback">
              {{ getFieldError('is_visible_minority') }}
            </div>
          </div>
        </div>
      </div>

      <!-- Indigenous Identity -->
      <div class="mb-4">
        <h6 class="border-bottom pb-2 mb-3">
          <i class="bi bi-tree me-2"></i>Indigenous Identity
        </h6>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="is_indigenous" class="form-label">
              {{ cfg.label('indigenous_status', 'I identify as Indigenous') }}
            </label>
            <select
              id="is_indigenous"
              v-model="form.indigenous_status"
              class="form-select"
              :class="{ 'is-invalid': hasFieldError('indigenous_status') }"
            >
              <option value="">{{ cfg.placeholder('indigenous_status', 'Select an answer') }}</option>
              <option v-for="opt in cfg.options('indigenous_status')" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <div v-if="hasFieldError('indigenous_status')" class="invalid-feedback">
              {{ hasFieldError('indigenous_status') }}
            </div>
          </div>
        </div>
        <div v-if="form.indigenous_status === 'yes'" class="row">
          <div class="col-md-6 mb-3">
            <label for="indigenous_group" class="form-label">{{ cfg.label('indigenous_group', 'Indigenous Group') }}</label>
            <select
              id="indigenous_group"
              v-model="form.indigenous_group"
              class="form-select"
              :class="{ 'is-invalid': hasFieldError('indigenous_group') }"
            >
              <option value="">{{ cfg.placeholder('indigenous_group', 'Select Group') }}</option>
              <option v-for="opt in cfg.options('indigenous_group')" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <div v-if="hasFieldError('indigenous_group')" class="invalid-feedback">
              {{ hasFieldError('indigenous_group') }}
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <label for="band_affiliation" class="form-label">{{ cfg.label('band_affiliation', 'Band/Nation Affiliation') }}</label>
            <input
              id="band_affiliation"
              v-model="form.band_affiliation"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('band_affiliation') }"
              placeholder="Enter band or nation name"
            />
            <div v-if="hasFieldError('band_affiliation')" class="invalid-feedback">
              {{ hasFieldError('band_affiliation') }}
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="indigenous_status_card_number" class="form-label">{{ cfg.label('indigenous_status_card_number', 'Status Card Number') }}</label>
            <input
              id="indigenous_status_card_number"
              v-model="form.indigenous_status_card_number"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('indigenous_status_card_number') }"
              placeholder="Status card number (if applicable)"
            />
            <div v-if="hasFieldError('indigenous_status_card_number')" class="invalid-feedback">
              {{ hasFieldError('indigenous_status_card_number') }}
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <div class="form-check mt-4">
              <input
                id="is_registered_with_band"
                v-model="form.is_registered_with_band"
                class="form-check-input"
                type="checkbox"
              />
              <label class="form-check-label" for="is_registered_with_band">
                {{ cfg.label('is_registered_with_band', 'Registered with band/nation') }}
              </label>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <div class="form-check">
              <input
                id="on_reserve_resident"
                v-model="form.on_reserve_resident"
                class="form-check-input"
                type="checkbox"
              />
              <label class="form-check-label" for="on_reserve_resident">
                {{ cfg.label('on_reserve_resident', 'Currently living on reserve') }}
              </label>
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <div class="form-check">
              <input
                id="receives_indigenous_support_services"
                v-model="form.receives_indigenous_support_services"
                class="form-check-input"
                type="checkbox"
              />
              <label class="form-check-label" for="receives_indigenous_support_services">
                {{ cfg.label('receives_indigenous_support_services', 'Receiving Indigenous support services') }}
              </label>
            </div>
          </div>
        </div>
        </div>

      <!-- Additional Support Services -->
      <div class="mb-4">
        <h6 class="border-bottom pb-2 mb-3">
          <i class="bi bi-hand-thumbs-up me-2"></i>Support Services
        </h6>
        <div class="row">
          <div class="col-md-6 mb-3">
            <div class="form-check">
              <input
                id="receives_minority_support_services"
                v-model="form.receives_minority_support_services"
                class="form-check-input"
                type="checkbox"
              />
              <label class="form-check-label" for="receives_minority_support_services">
                {{ cfg.label('receives_minority_support_services', 'Receiving minority support services') }}
              </label>
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <div class="form-check mt-4">
              <input
                id="refugee_status"
                v-model="form.refugee_status"
                class="form-check-input"
                type="checkbox"
              />
              <label class="form-check-label" for="refugee_status">
                {{ cfg.label('refugee_status', 'I am a refugee or protected person') }}
              </label>
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
  'citizenship_status', 'country_of_birth', 'immigration_status', 'years_in_country',
  'language_spoken_at_home', 'racial_identity', 'racial_identity_other_text',
  'is_visible_minority', 'indigenous_status', 'indigenous_group', 'band_affiliation',
  'indigenous_status_card_number', 'is_registered_with_band', 'on_reserve_resident',
  'receives_indigenous_support_services', 'receives_minority_support_services',
  'refugee_status'
]

// Admin-managed fields for this tab that have no bespoke control here.
const extraFields = computed(() =>
  Object.values(props.config || {})
    .filter((f) => f && f.tab === 'identity' && f.is_active && !KNOWN_FIELDS.includes(f.field_id))
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
         props.errors[`identity.${fieldName}`] || 
         null
}

const hasFieldError = (fieldName) => {
  return getFieldError(fieldName) !== null
}
</script>
