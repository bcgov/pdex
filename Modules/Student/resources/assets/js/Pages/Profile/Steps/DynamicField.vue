<template>
  <div>
    <!-- Checkbox (single boolean) -->
    <div v-if="field.type === 'checkbox' && !field.multi_select" class="form-check">
      <input
        :id="field.field_id"
        v-model="localValue"
        class="form-check-input"
        type="checkbox"
      />
      <label class="form-check-label" :for="field.field_id">
        {{ field.label }}
      </label>
      <div v-if="field.help_text" class="form-text">{{ field.help_text }}</div>
    </div>

    <!-- Multi-select (checkbox group) -->
    <template v-else-if="field.multi_select">
      <label class="form-label d-block">
        {{ field.label }}
        <span v-if="field.required" class="text-danger">*</span>
      </label>
      <div v-for="opt in options" :key="opt.value" class="form-check">
        <input
          :id="`${field.field_id}_${opt.value}`"
          class="form-check-input"
          type="checkbox"
          :value="opt.value"
          :checked="Array.isArray(localValue) && localValue.includes(opt.value)"
          @change="toggleMulti(opt.value, $event.target.checked)"
        />
        <label class="form-check-label" :for="`${field.field_id}_${opt.value}`">{{ opt.label }}</label>
      </div>
      <div v-if="field.help_text" class="form-text">{{ field.help_text }}</div>
      <div v-if="error" class="text-danger small mt-1">{{ error }}</div>
    </template>

    <!-- Everything else has a label + control -->
    <template v-else>
      <label :for="field.field_id" class="form-label">
        {{ field.label }}
        <span v-if="field.required" class="text-danger">*</span>
      </label>

      <!-- Textarea -->
      <textarea
        v-if="field.type === 'textarea'"
        :id="field.field_id"
        v-model="localValue"
        class="form-control"
        :class="{ 'is-invalid': error }"
        rows="3"
        :placeholder="field.placeholder || ''"
      ></textarea>

      <!-- Select / radio-as-select -->
      <select
        v-else-if="field.type === 'select' || field.type === 'radio'"
        :id="field.field_id"
        v-model="localValue"
        class="form-select"
        :class="{ 'is-invalid': error }"
      >
        <option value="">{{ field.placeholder || 'Select...' }}</option>
        <option v-for="opt in options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>

      <!-- Generic input (text, email, tel, number, date, autocomplete) -->
      <input
        v-else
        :id="field.field_id"
        v-model="localValue"
        :type="inputType"
        class="form-control"
        :class="{ 'is-invalid': error }"
        :placeholder="field.placeholder || ''"
      />

      <div v-if="field.help_text" class="form-text">{{ field.help_text }}</div>
      <div v-if="error" class="invalid-feedback d-block">{{ error }}</div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'

/**
 * Renders a single admin-managed profile field from its ProfileFormField
 * configuration. Used to display fields that are defined under
 * /admin/utils/student but do not have a bespoke control in the step forms.
 */
const props = defineProps({
  field: {
    type: Object,
    required: true,
  },
  modelValue: {
    default: '',
  },
  error: {
    type: [String, null],
    default: null,
  },
})

const emit = defineEmits(['update:modelValue'])

const localValue = computed({
  get() {
    return props.modelValue
  },
  set(value) {
    emit('update:modelValue', value)
  },
})

const options = computed(() => (Array.isArray(props.field.options) ? props.field.options : []))

// Map the configured field type to a native input type.
const inputType = computed(() => {
  switch (props.field.type) {
    case 'email':
      return 'email'
    case 'tel':
      return 'tel'
    case 'number':
      return 'number'
    case 'date':
      return 'date'
    default:
      return 'text'
  }
})

const toggleMulti = (value, checked) => {
  const current = Array.isArray(props.modelValue) ? [...props.modelValue] : []
  const index = current.indexOf(value)

  if (checked && index === -1) {
    current.push(value)
  } else if (!checked && index !== -1) {
    current.splice(index, 1)
  }

  emit('update:modelValue', current)
}
</script>
