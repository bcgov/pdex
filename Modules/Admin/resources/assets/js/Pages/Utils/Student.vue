<template>
  <Authenticated>
    <Head title="Student Profile Form Fields" />
    <div class="container-fluid py-4">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div>
                <h5 class="card-title mb-0">Student Profile Form Fields</h5>
                <small class="text-muted">Manage the fields, attributes and options shown on the student profile form.</small>
              </div>
              <button type="button" class="btn btn-primary" @click="openCreate">
                <i class="bi bi-plus-lg me-1"></i> Add Field
              </button>
            </div>

            <div class="card-body">
              <!-- Flash message -->
              <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
                {{ flashSuccess }}
                <button type="button" class="btn-close" @click="flashSuccess = null"></button>
              </div>

              <!-- Tab navigation -->
              <ul class="nav nav-tabs mb-3">
                <li v-for="tab in tabs" :key="tab" class="nav-item">
                  <button
                    type="button"
                    class="nav-link"
                    :class="{ active: activeTab === tab }"
                    @click="activeTab = tab"
                  >
                    {{ tabLabel(tab) }}
                    <span class="badge bg-secondary ms-1">{{ (grouped[tab]?.count) || 0 }}</span>
                  </button>
                </li>
              </ul>

              <!-- Fields grouped by section for the active tab -->
              <div v-if="!grouped[activeTab] || grouped[activeTab].count === 0" class="text-muted text-center py-4">
                No fields defined for this tab yet.
              </div>

              <div v-for="(sectionFields, section) in (grouped[activeTab]?.sections || {})" :key="section" class="mb-4">
                <h6 class="text-uppercase text-muted border-bottom pb-1">{{ section || 'Uncategorized' }}</h6>
                <div class="table-responsive">
                  <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 60px">Order</th>
                        <th>Label</th>
                        <th>Field ID</th>
                        <th>Type</th>
                        <th class="text-center">Required</th>
                        <th class="text-center">Active</th>
                        <th class="text-center">API</th>
                        <th>Options</th>
                        <th class="text-end" style="width: 130px">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="field in sectionFields" :key="field.id">
                        <td>{{ field.sort_order }}</td>
                        <td>
                          <span class="fw-medium">{{ field.label }}</span>
                          <div v-if="field.help_text" class="small text-muted">{{ field.help_text }}</div>
                        </td>
                        <td><code>{{ field.field_id }}</code></td>
                        <td><span class="badge bg-light text-dark">{{ field.type }}</span></td>
                        <td class="text-center">
                          <i v-if="field.required" class="bi bi-check-lg text-success"></i>
                          <span v-else class="text-muted">—</span>
                        </td>
                        <td class="text-center">
                          <span :class="field.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                            {{ field.is_active ? 'Yes' : 'No' }}
                          </span>
                        </td>
                        <td class="text-center">
                          <span :class="field.api_enabled ? 'badge bg-info' : 'badge bg-secondary'">
                            {{ field.api_enabled ? 'Yes' : 'No' }}
                          </span>
                        </td>
                        <td>
                          <span v-if="hasOptions(field)" class="small text-muted">
                            {{ field.options.length }} option(s)
                          </span>
                          <span v-else class="text-muted">—</span>
                        </td>
                        <td class="text-end">
                          <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="openEdit(field)">
                            <i class="bi bi-pencil"></i>
                          </button>
                          <button type="button" class="btn btn-sm btn-outline-danger" @click="confirmDelete(field)">
                            <i class="bi bi-trash"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <div v-if="showModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5)">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <form @submit.prevent="submit">
            <div class="modal-header">
              <h5 class="modal-title">{{ isEditing ? 'Edit Field' : 'Add Field' }}</h5>
              <button type="button" class="btn-close" @click="closeModal"></button>
            </div>
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Tab <span class="text-danger">*</span></label>
                  <select v-model="form.tab" class="form-select" :class="{ 'is-invalid': form.errors.tab }">
                    <option v-for="tab in tabs" :key="tab" :value="tab">{{ tabLabel(tab) }}</option>
                  </select>
                  <div class="invalid-feedback">{{ form.errors.tab }}</div>
                </div>
                <div class="col-md-8">
                  <label class="form-label">Section</label>
                  <input v-model="form.section" type="text" class="form-control" :class="{ 'is-invalid': form.errors.section }" placeholder="e.g., Personal Details" />
                  <div class="invalid-feedback">{{ form.errors.section }}</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Label <span class="text-danger">*</span></label>
                  <input v-model="form.label" type="text" class="form-control" :class="{ 'is-invalid': form.errors.label }" />
                  <div class="invalid-feedback">{{ form.errors.label }}</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Field ID <span class="text-danger">*</span></label>
                  <input v-model="form.field_id" type="text" class="form-control" :class="{ 'is-invalid': form.errors.field_id }" placeholder="lowercase_with_underscores" />
                  <div class="form-text">Must match the form model key. Lowercase letters, numbers, underscores.</div>
                  <div class="invalid-feedback">{{ form.errors.field_id }}</div>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Type <span class="text-danger">*</span></label>
                  <select v-model="form.type" class="form-select" :class="{ 'is-invalid': form.errors.type }">
                    <option v-for="type in types" :key="type" :value="type">{{ type }}</option>
                  </select>
                  <div class="invalid-feedback">{{ form.errors.type }}</div>
                </div>
                <div class="col-md-8">
                  <label class="form-label">Placeholder</label>
                  <input v-model="form.placeholder" type="text" class="form-control" :class="{ 'is-invalid': form.errors.placeholder }" />
                  <div class="invalid-feedback">{{ form.errors.placeholder }}</div>
                </div>

                <div class="col-12">
                  <label class="form-label">Help Text</label>
                  <input v-model="form.help_text" type="text" class="form-control" :class="{ 'is-invalid': form.errors.help_text }" placeholder="Optional hint shown near the field" />
                  <div class="invalid-feedback">{{ form.errors.help_text }}</div>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Sort Order</label>
                  <input v-model.number="form.sort_order" type="number" min="0" class="form-control" :class="{ 'is-invalid': form.errors.sort_order }" />
                  <div class="invalid-feedback">{{ form.errors.sort_order }}</div>
                </div>
                <div class="col-md-8 d-flex align-items-end">
                  <div class="d-flex gap-4 flex-wrap">
                    <div class="form-check">
                      <input v-model="form.required" class="form-check-input" type="checkbox" id="field-required" />
                      <label class="form-check-label" for="field-required">Required</label>
                    </div>
                    <div class="form-check">
                      <input v-model="form.is_active" class="form-check-input" type="checkbox" id="field-active" />
                      <label class="form-check-label" for="field-active">Active</label>
                    </div>
                    <div class="form-check">
                      <input v-model="form.api_enabled" class="form-check-input" type="checkbox" id="field-api" />
                      <label class="form-check-label" for="field-api">API enabled</label>
                    </div>
                    <div v-if="supportsOptions" class="form-check">
                      <input v-model="form.multi_select" class="form-check-input" type="checkbox" id="field-multi" />
                      <label class="form-check-label" for="field-multi">Allow multiple selections</label>
                    </div>
                  </div>
                </div>

                <!-- Options editor -->
                <div v-if="supportsOptions" class="col-12">
                  <hr />
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0 fw-medium">Options</label>
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addOption">
                      <i class="bi bi-plus-lg"></i> Add Option
                    </button>
                  </div>
                  <div v-if="form.options.length === 0" class="text-muted small mb-2">No options yet.</div>
                  <div v-for="(option, index) in form.options" :key="index" class="row g-2 mb-2 align-items-center">
                    <div class="col-md-4">
                      <input v-model="option.value" type="text" class="form-control form-control-sm" placeholder="value" />
                    </div>
                    <div class="col-md-5">
                      <input v-model="option.label" type="text" class="form-control form-control-sm" placeholder="Label" />
                    </div>
                    <div class="col-md-2">
                      <div class="form-check">
                        <input v-model="option.is_default" class="form-check-input" type="checkbox" :id="`opt-default-${index}`" />
                        <label class="form-check-label small" :for="`opt-default-${index}`">Default</label>
                      </div>
                    </div>
                    <div class="col-md-1 text-end">
                      <button type="button" class="btn btn-sm btn-outline-danger" @click="removeOption(index)">
                        <i class="bi bi-x-lg"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-primary" :disabled="form.processing">
                {{ isEditing ? 'Update Field' : 'Create Field' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delete confirmation modal -->
    <div v-if="deleteTarget" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5)">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Delete Field</h5>
            <button type="button" class="btn-close" @click="deleteTarget = null"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to delete <strong>{{ deleteTarget.label }}</strong> (<code>{{ deleteTarget.field_id }}</code>)? This cannot be undone.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="deleteTarget = null">Cancel</button>
            <button type="button" class="btn btn-danger" @click="performDelete">Delete</button>
          </div>
        </div>
      </div>
    </div>
  </Authenticated>
</template>

<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Authenticated from '../../Layouts/Authenticated.vue';

const props = defineProps({
  fields: { type: Array, default: () => [] },
  tabs: { type: Array, default: () => [] },
  types: { type: Array, default: () => [] },
});

const page = usePage();

const activeTab = ref(props.tabs[0] || 'general');
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const deleteTarget = ref(null);
const flashSuccess = ref(page.props?.flash?.success || null);

watch(() => page.props?.flash?.success, (value) => {
  if (value) flashSuccess.value = value;
});

const optionTypes = ['select', 'radio', 'checkbox'];

const tabLabels = {
  general: 'General Information',
  address: 'Address',
  employment: 'Employment',
  identity: 'Identity',
};

const tabLabel = (tab) => tabLabels[tab] || tab;

const hasOptions = (field) => Array.isArray(field.options) && field.options.length > 0;

// Group fields by tab then section
const grouped = computed(() => {
  const result = {};
  for (const tab of props.tabs) {
    result[tab] = { count: 0, sections: {} };
  }
  for (const field of props.fields) {
    if (!result[field.tab]) {
      result[field.tab] = { count: 0, sections: {} };
    }
    const section = field.section || '';
    if (!result[field.tab].sections[section]) {
      result[field.tab].sections[section] = [];
    }
    result[field.tab].sections[section].push(field);
    result[field.tab].count += 1;
  }
  return result;
});

const form = useForm({
  tab: activeTab.value,
  section: '',
  field_id: '',
  label: '',
  type: 'text',
  required: false,
  placeholder: '',
  multi_select: false,
  help_text: '',
  sort_order: 0,
  is_active: true,
  api_enabled: true,
  options: [],
});

const supportsOptions = computed(() => optionTypes.includes(form.type));

const openCreate = () => {
  isEditing.value = false;
  editingId.value = null;
  form.clearErrors();
  form.defaults({
    tab: activeTab.value,
    section: '',
    field_id: '',
    label: '',
    type: 'text',
    required: false,
    placeholder: '',
    multi_select: false,
    help_text: '',
    sort_order: 0,
    is_active: true,
    api_enabled: true,
    options: [],
  });
  form.reset();
  showModal.value = true;
};

const openEdit = (field) => {
  isEditing.value = true;
  editingId.value = field.id;
  form.clearErrors();
  form.tab = field.tab;
  form.section = field.section || '';
  form.field_id = field.field_id;
  form.label = field.label;
  form.type = field.type;
  form.required = !!field.required;
  form.placeholder = field.placeholder || '';
  form.multi_select = !!field.multi_select;
  form.help_text = field.help_text || '';
  form.sort_order = field.sort_order ?? 0;
  form.is_active = !!field.is_active;
  form.api_enabled = field.api_enabled === undefined ? true : !!field.api_enabled;
  form.options = (field.options || []).map((o) => ({
    value: o.value,
    label: o.label,
    is_default: !!o.is_default,
  }));
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const addOption = () => {
  form.options.push({ value: '', label: '', is_default: false });
};

const removeOption = (index) => {
  form.options.splice(index, 1);
};

const submit = () => {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      showModal.value = false;
    },
  };

  if (isEditing.value) {
    form.put(`/admin/utils/student/fields/${editingId.value}`, options);
  } else {
    form.post('/admin/utils/student/fields', options);
  }
};

const confirmDelete = (field) => {
  deleteTarget.value = field;
};

const performDelete = () => {
  const id = deleteTarget.value.id;
  router.delete(`/admin/utils/student/fields/${id}`, {
    preserveScroll: true,
    onFinish: () => {
      deleteTarget.value = null;
    },
  });
};
</script>
