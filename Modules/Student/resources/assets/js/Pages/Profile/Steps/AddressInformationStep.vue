<template>
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">
        <i class="bi bi-house me-2"></i>
        Address Information
      </h5>
      <p class="text-muted mb-0 small">Current and mailing address details</p>
    </div>
    <div class="card-body">
      <!-- Current Address -->
      <div class="mb-4">
        <h6 class="border-bottom pb-2 mb-3">
          <i class="bi bi-geo-alt me-2"></i>Current Address
        </h6>
        <div class="row">
          <div class="col-md-7 mb-3">
            <label for="current_address_line_1" class="form-label">Address Line 1 <span class="text-danger">*</span></label>
            <input
              id="current_address_line_1"
              v-model="currentAddressProxy.address_line1"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('address_line1') }"
              placeholder="Street number and name"
              required
            />
            <div v-if="hasFieldError('address_line1')" class="invalid-feedback">
              {{ getFieldError('address_line1') }}
            </div>
          </div>
          <div class="col-md-5 mb-3">
            <label for="current_address_line_2" class="form-label">Address Line 2</label>
            <input
              id="current_address_line_2"
              v-model="currentAddressProxy.address_line2"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('address_line2') }"
              placeholder="Apt, Suite, Unit"
            />
            <div v-if="hasFieldError('address_line2')" class="invalid-feedback">
              {{ getFieldError('address_line2') }}
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3 mb-3">
            <label for="current_city" class="form-label">City</label>
            <input
              id="current_city"
              v-model="currentAddressProxy.city"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('city') }"
            />
            <div v-if="hasFieldError('city')" class="invalid-feedback">
              {{ getFieldError('city') }}
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <label for="current_province" class="form-label">{{ isCanada(currentAddress.country) ? 'Province' : 'Province/State' }}</label>
            <select
              v-if="isCanada(currentAddress.country)"
              id="current_province"
              v-model="currentAddressProxy.province"
              class="form-select"
              :class="{ 'is-invalid': hasFieldError('province') }"
            >
              <option value="">Select Province</option>
              <option value="AB">Alberta</option>
              <option value="BC">British Columbia</option>
              <option value="MB">Manitoba</option>
              <option value="NB">New Brunswick</option>
              <option value="NL">Newfoundland and Labrador</option>
              <option value="NS">Nova Scotia</option>
              <option value="ON">Ontario</option>
              <option value="PE">Prince Edward Island</option>
              <option value="QC">Quebec</option>
              <option value="SK">Saskatchewan</option>
              <option value="NT">Northwest Territories</option>
              <option value="NU">Nunavut</option>
              <option value="YT">Yukon</option>
            </select>
            <input
              v-else
              id="current_province_text"
              v-model="currentAddressProxy.province"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('province') }"
              placeholder="Enter province or state"
            />
            <div v-if="hasFieldError('province')" class="invalid-feedback">
              {{ hasFieldError('province') }}
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <label for="current_postal_code" class="form-label">{{ isCanada(currentAddress.country) ? 'Postal Code' : 'Postal/ZIP Code' }}</label>
            <input
              id="current_postal_code"
              v-model="currentAddressProxy.postal_code"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('postal_code') }"
              :placeholder="isCanada(currentAddress.country) ? 'A1A 1A1' : 'Enter postal or ZIP code'"
              :maxlength="isCanada(currentAddress.country) ? 7 : 10"
            />
            <div v-if="hasFieldError('postal_code')" class="invalid-feedback">
              {{ hasFieldError('postal_code') }}
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <CountryAutocomplete
              id="current_country"
              label="Country"
              v-model="currentAddressProxy.country"
              :countries="countries"
              :required="false"
              :error="getFieldError('country')"
              placeholder="Type to search countries..."
            />
          </div>
        </div>
      </div>

      <!-- Mailing Address Toggle -->
      <div class="mb-4">
        <div class="row">
          <div class="col-md-12 mb-3">
            <div class="form-check">
              <input
                id="use_different_mailing_address"
                v-model="useDifferentMailing"
                class="form-check-input"
                type="checkbox"
              />
              <label class="form-check-label" for="use_different_mailing_address">
                My mailing address is different from my current address
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Mailing Address Section (conditional) -->
      <div v-if="useDifferentMailing" class="mb-4">
        <h6 class="border-bottom pb-2 mb-3">
          <i class="bi bi-envelope me-2"></i>Mailing Address
        </h6>
        <div class="row">
          <div class="col-md-7 mb-3">
            <label for="mailing_address_line_1" class="form-label">Address Line 1</label>
            <input
              id="mailing_address_line_1"
              v-model="mailingAddressProxy.address_line1"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('mailing_address_line1') }"
              placeholder="Street number and name"
            />
            <div v-if="hasFieldError('mailing_address_line1')" class="invalid-feedback">
              {{ getFieldError('mailing_address_line1') }}
            </div>
          </div>
          <div class="col-md-5 mb-3">
            <label for="mailing_address_line_2" class="form-label">Address Line 2</label>
            <input
              id="mailing_address_line_2"
              v-model="mailingAddressProxy.address_line2"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('mailing_address_line2') }"
              placeholder="Apt, Suite, Unit"
            />
            <div v-if="hasFieldError('mailing_address_line2')" class="invalid-feedback">
              {{ getFieldError('mailing_address_line2') }}
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3 mb-3">
            <label for="mailing_city" class="form-label">City</label>
            <input
              id="mailing_city"
              v-model="mailingAddressProxy.city"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('mailing_city') }"
            />
            <div v-if="hasFieldError('mailing_city')" class="invalid-feedback">
              {{ getFieldError('mailing_city') }}
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <label for="mailing_province" class="form-label">{{ isCanada(mailingAddress.country) ? 'Province' : 'Province/State' }}</label>
            <select
              v-if="isCanada(mailingAddress.country)"
              id="mailing_province"
              v-model="mailingAddressProxy.province"
              class="form-select"
              :class="{ 'is-invalid': hasFieldError('mailing_province') }"
            >
              <option value="">Select Province</option>
              <option value="AB">Alberta</option>
              <option value="BC">British Columbia</option>
              <option value="MB">Manitoba</option>
              <option value="NB">New Brunswick</option>
              <option value="NL">Newfoundland and Labrador</option>
              <option value="NS">Nova Scotia</option>
              <option value="ON">Ontario</option>
              <option value="PE">Prince Edward Island</option>
              <option value="QC">Quebec</option>
              <option value="SK">Saskatchewan</option>
              <option value="NT">Northwest Territories</option>
              <option value="NU">Nunavut</option>
              <option value="YT">Yukon</option>
            </select>
            <input
              v-else
              id="mailing_province_text"
              v-model="mailingAddressProxy.province"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('mailing_province') }"
              placeholder="Enter province or state"
            />
            <div v-if="hasFieldError('mailing_province')" class="invalid-feedback">
              {{ hasFieldError('mailing_province') }}
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <label for="mailing_postal_code" class="form-label">{{ isCanada(mailingAddress.country) ? 'Postal Code' : 'Postal/ZIP Code' }}</label>
            <input
              id="mailing_postal_code"
              v-model="mailingAddressProxy.postal_code"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': hasFieldError('mailing_postal_code') }"
              :placeholder="isCanada(mailingAddress.country) ? 'A1A 1A1' : 'Enter postal or ZIP code'"
              :maxlength="isCanada(mailingAddress.country) ? 7 : 10"
            />
            <div v-if="hasFieldError('mailing_postal_code')" class="invalid-feedback">
              {{ hasFieldError('mailing_postal_code') }}
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <CountryAutocomplete
              id="mailing_country"
              label="Country"
              v-model="mailingAddressProxy.country"
              :countries="countries"
              :required="false"
              :error="getFieldError('mailing_country')"
              placeholder="Type to search countries..."
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import CountryAutocomplete from '@/Components/CountryAutocomplete.vue'

const props = defineProps({
  form: Array, // Array of addresses
  countries: Array,
  errors: Object
})

const emit = defineEmits(['update:form'])

// Reactive state for mailing address toggle
// Initialize based on whether mailing address already exists
const useDifferentMailing = ref((props.form || []).some(address => !address.is_primary))

// Get current and mailing addresses
const currentAddress = computed(() => {
  return (props.form || []).find(address => address.is_primary) || {}
})

const mailingAddress = computed(() => {
  const existing = (props.form || []).find(address => !address.is_primary)
  if (existing) {
    return existing
  }
  
  // Return default mailing address structure when none exists
  return {
    address_line1: '',
    address_line2: '',
    city: '',
    province: '',
    postal_code: '',
    country: '',
    is_primary: false,
    is_active: true
  }
})

// Helper function to update addresses array
const updateAddresses = (newAddresses) => {
  emit('update:form', newAddresses)
}

// Helper function to update current address field
const updateCurrentField = (field, value) => {
  const addresses = [...(props.form || [])]
  const currentIndex = addresses.findIndex(addr => addr.is_primary)
  
  if (currentIndex !== -1) {
    addresses[currentIndex] = { ...addresses[currentIndex], [field]: value }
  } else {
    // Create new current address
    addresses.push({
      [field]: value,
      is_primary: true,
      is_active: true
    })
  }
  
  updateAddresses(addresses)
}

// Helper function to update mailing address field  
const updateMailingField = (field, value) => {
  const addresses = [...(props.form || [])]
  let mailingIndex = addresses.findIndex(addr => !addr.is_primary)
  
  if (mailingIndex === -1) {
    // Create new mailing address and append to array
    const newMailingAddress = {
      address_line1: '',
      address_line2: '',
      city: '',
      province: '',
      postal_code: '',
      country: '',
      is_primary: false,
      is_active: true,
      [field]: value  // Set the specific field being updated
    }
    addresses.push(newMailingAddress)
  } else {
    // Update existing mailing address
    addresses[mailingIndex] = { ...addresses[mailingIndex], [field]: value }
  }
  
  updateAddresses(addresses)
}

// Create proxy objects for v-model compatibility
const currentAddressProxy = computed(() => {
  return new Proxy(currentAddress.value || {}, {
    get(target, prop) {
      return target[prop] || ''
    },
    set(target, prop, value) {
      updateCurrentField(prop, value)
      return true
    }
  })
})

const mailingAddressProxy = computed(() => {
  return new Proxy(mailingAddress.value || {}, {
    get(target, prop) {
      return target[prop] || ''
    },
    set(target, prop, value) {
      updateMailingField(prop, value)
      return true
    }
  })
})

// Watch for changes to useDifferentMailing checkbox
watch(useDifferentMailing, (newValue) => {
  if (!newValue) {
    // Remove mailing address when checkbox is unchecked
    const addresses = (props.form || []).filter(addr => addr.is_primary)
    updateAddresses(addresses)
  }
})

// Helper function to check if country is Canada
const isCanada = (country) => {
  if (!country) return true // Default to Canada format when no country selected
  return country.toLowerCase() === 'canada'
}


// Helper function to check for errors in multiple formats
const getFieldError = (fieldName) => {
  return props.errors[fieldName] || 
         props.errors[`current_address.${fieldName}`] || 
         props.errors[`mailing_address.${fieldName}`] ||
         props.errors[`address.${fieldName}`] ||
         null
}

const hasFieldError = (fieldName) => {
  return getFieldError(fieldName) !== null
}
</script>
