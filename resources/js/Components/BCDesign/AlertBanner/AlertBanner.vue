<template>
  <div
    :class="`bc-gov-alertbanner bc-gov-alertbanner-${variant}`"
    role="alert"
    :aria-labelledby="labelId"
    :aria-describedby="descId"
  >
    <p :id="descId">
      <slot />
    </p>
  </div>
</template>

<script>
export default {
  name: 'AlertBanner',
  props: {
    /**
     * Alert banner variant - determines color and icon
     */
    variant: {
      type: String,
      default: 'info',
      validator: (value) => ['error', 'warning', 'info', 'success'].includes(value)
    },
    /**
     * ID for accessibility labeling
     */
    labelId: {
      type: String,
      default: null
    },
    /**
     * ID for accessibility description
     */
    descId: {
      type: String,
      default: null
    }
  },
  computed: {
    generatedLabelId() {
      return this.labelId || `alert-${this.variant}-${this._uid}`
    },
    generatedDescId() {
      return this.descId || `alert-desc-${this.variant}-${this._uid}`
    }
  }
}
</script>

<style>
@import './AlertBanner.css';
</style>