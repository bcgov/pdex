import { computed, unref } from 'vue';

/**
 * Helper for reading student profile field configuration coming from the
 * database (ProfileFormField records keyed by field_id).
 *
 * The step components keep their hand-built layout but use these helpers to
 * drive labels, required flags, placeholders, options and visibility so admins
 * can manage the form from /admin/utils/student.
 *
 * @param {import('vue').Ref|Object} configSource - the formConfig prop (object keyed by field_id)
 */
export function useFieldConfig(configSource) {
  const map = computed(() => unref(configSource) || {});

  const get = (fieldId) => map.value?.[fieldId] || null;

  /** Label for a field, falling back to a provided default. */
  const label = (fieldId, fallback = '') => {
    const field = get(fieldId);
    return field && field.label != null ? field.label : fallback;
  };

  /**
   * Whether the field should be shown. Defaults to visible when the field is
   * not present in the config so the form never disappears if config fails to
   * load. A field explicitly marked is_active = false is hidden.
   */
  const isActive = (fieldId) => {
    const field = get(fieldId);
    if (!field) return true;
    return !!field.is_active;
  };

  /** Whether the field is required. */
  const isRequired = (fieldId, fallback = false) => {
    const field = get(fieldId);
    return field ? !!field.required : fallback;
  };

  /** Placeholder text for the field. */
  const placeholder = (fieldId, fallback = '') => {
    const field = get(fieldId);
    return field && field.placeholder != null ? field.placeholder : fallback;
  };

  /** Optional help text for the field. */
  const helpText = (fieldId, fallback = '') => {
    const field = get(fieldId);
    return field && field.help_text != null ? field.help_text : fallback;
  };

  /** Options array ({ value, label, is_default }) for select-like fields. */
  const options = (fieldId, fallback = []) => {
    const field = get(fieldId);
    return field && Array.isArray(field.options) ? field.options : fallback;
  };

  return { get, label, isActive, isRequired, placeholder, helpText, options };
}
