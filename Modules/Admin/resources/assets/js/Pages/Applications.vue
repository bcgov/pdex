<template>
  <Authenticated>
    <Head title="Applications Management" />
    <div class="container-fluid px-4 py-6">
      <div class="row">

        
        <!-- Main Content Column -->
        <div class="col-lg-12">
          <div class="card shadow-sm">
            <div class="card-header">
              <div class="d-flex justify-content-between align-items-center">
                <span>External Applications</span>
                <div class="d-flex gap-2 align-items-center">
                  <div class="form-check form-switch">
                    <input v-model="showDeleted" class="form-check-input" type="checkbox" id="showDeleted">
                    <label class="form-check-label" for="showDeleted">Show Deleted</label>
                  </div>
                  <Link v-if="userCanCreate" href="/admin/applications/create" class="btn btn-link btn-sm">
                    <i class="bi bi-plus-lg me-2"></i>Add Application
                  </Link>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered mb-0">
        <thead class="table-light">
          <tr>
            <th @click="sortBy('name')" class="sortable-header" :class="getSortClass('name')">
              Name
              <i :class="getSortIcon('name')"></i>
            </th>
            <th @click="sortBy('contact_name')" class="sortable-header" :class="getSortClass('contact_name')">
              Contact
              <i :class="getSortIcon('contact_name')"></i>
            </th>
            <th>IDPs Enabled</th>
            <th @click="sortBy('security_approval_status')" class="sortable-header" :class="getSortClass('security_approval_status')">
              Security Approval
              <i :class="getSortIcon('security_approval_status')"></i>
            </th>
            <th @click="sortBy('privacy_approval_status')" class="sortable-header" :class="getSortClass('privacy_approval_status')">
              Privacy Approval
              <i :class="getSortIcon('privacy_approval_status')"></i>
            </th>
            <th @click="sortBy('status')" class="sortable-header" :class="getSortClass('status')">
              Status
              <i :class="getSortIcon('status')"></i>
            </th>
            <th>STRA/PIA</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="app in filteredApplications" :key="app.id" :class="{'table-secondary': app.deleted_at}">
            <td>
              <div class="d-flex align-items-center">
                <div>
                    <Link :href="`/admin/applications/edit/${app.guid}`" class="">
                      <strong>{{ app.name }}</strong>
                    </Link>

                  
                  <span v-if="app.deleted_at" class="badge bg-danger ms-2">Deleted</span>
                  <br><small class="text-muted">{{ app.description || 'No description' }}</small>
                </div>
              </div>
            </td>
            <td>
              <div>{{ app.contact_name }}</div>
              <small class="text-muted">{{ app.contact_email }}</small>
              <div v-if="app.contact_phone">
                <small class="text-muted">{{ app.contact_phone }}</small>
              </div>
            </td>
            <td>
              <div class="d-flex gap-1">
                <span v-if="app.bcsc_enabled" class="badge bg-success">BCSC</span>
                <span v-if="app.idir_enabled" class="badge bg-primary">IDIR</span>
                <span v-if="app.bceid_enabled" class="badge bg-info">BCeID</span>
              </div>
            </td>
            <td>
              <span :class="`badge bg-${getApprovalBadgeColor(app.security_approval_status)}`">
                {{ (app.security_approval_status || 'pending').toUpperCase() }}
              </span>
              <div v-if="app.security_approved_at && app.securityApprover" class="mt-1">
                <small class="text-muted">
                  by {{ app.securityApprover.name }}<br>
                  {{ formatDate(app.security_approved_at) }}
                </small>
              </div>
            </td>
            <td>
              <span :class="`badge bg-${getApprovalBadgeColor(app.privacy_approval_status)}`">
                {{ (app.privacy_approval_status || 'pending').toUpperCase() }}
              </span>
              <div v-if="app.privacy_approved_at && app.privacyApprover" class="mt-1">
                <small class="text-muted">
                  by {{ app.privacyApprover.name }}<br>
                  {{ formatDate(app.privacy_approved_at) }}
                </small>
              </div>
            </td>
            <td>
              <span :class="`badge bg-${getStatusBadgeColor(app.status)}`">
                {{ (app.status || 'inactive').toUpperCase() }}
              </span>
            </td>
            <td>
              <div class="d-flex gap-1">
                <span v-if="app.stra_provided" class="badge bg-success">STRA</span>
                <span v-if="app.pia_provided" class="badge bg-success">PIA</span>
                <span v-if="!app.stra_provided && !app.pia_provided" class="text-muted">None</span>
              </div>
            </td>
            <td>
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" :id="`actions-${app.id}`" data-bs-toggle="dropdown">
                  Actions
                </button>
                <ul class="dropdown-menu" :aria-labelledby="`actions-${app.id}`">
                  <li v-if="!app.deleted_at && (app.security_approval_status === 'pending' || app.privacy_approval_status === 'pending')">
                    <button @click="showSecurityApproveModal(app)" class="dropdown-item" v-if="app.security_approval_status === 'pending'">
                      <i class="bi bi-shield-check me-2"></i>Approve Security
                    </button>
                  </li>
                  <li v-if="!app.deleted_at && (app.security_approval_status === 'pending' || app.privacy_approval_status === 'pending')">
                    <button @click="showPrivacyApproveModal(app)" class="dropdown-item" v-if="app.privacy_approval_status === 'pending'">
                      <i class="bi bi-person-check me-2"></i>Approve Privacy
                    </button>
                  </li>
                  <li v-if="!app.deleted_at && (app.security_approval_status === 'pending' || app.privacy_approval_status === 'pending')">
                    <button @click="showRejectModal(app)" class="dropdown-item text-warning">
                      <i class="bi bi-x-lg me-2"></i>Reject
                    </button>
                  </li>
                  <li v-if="!app.deleted_at && app.security_approval_status === 'approved' && app.privacy_approval_status === 'approved'">
                    <button @click="toggleStatus(app)" class="dropdown-item">
                      <i class="bi bi-toggle-on me-2"></i>
                      Change Status ({{ getNextStatus(app.status) }})
                    </button>
                  </li>
                  <li v-if="app.deleted_at">
                    <button @click="restoreApp(app)" class="dropdown-item text-success">
                      <i class="bi bi-arrow-clockwise me-2"></i>Restore
                    </button>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li v-if="!app.deleted_at">
                    <button @click="deleteApp(app)" class="dropdown-item text-danger">
                      <i class="bi bi-trash me-2"></i>Delete
                    </button>
                  </li>
                  <li v-if="app.deleted_at">
                    <button @click="forceDeleteApp(app)" class="dropdown-item text-danger">
                      <i class="bi bi-trash me-2"></i>Delete Permanently
                    </button>
                  </li>
                </ul>
              </div>
            </td>
          </tr>
        </tbody>
                </table>
              </div>
            </div>
          </div>

    <!-- Approval Modal -->
    <div v-if="showModal" class="modal d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ modalAction }} Application</h5>
            <button @click="closeModal" type="button" class="btn-close"></button>
          </div>
          <form @submit.prevent="submitApproval">
            <div class="modal-body">
              <p>Are you sure you want to {{ modalAction.toLowerCase() }} the application <strong>{{ selectedApp?.name }}</strong>?</p>
              <div class="mb-3">
                <label class="form-label">Notes (optional)</label>
                <textarea v-model="approvalForm.approval_notes" class="form-control" rows="3" 
                  :placeholder="`Add notes about your ${modalAction.toLowerCase()} decision...`"></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button @click="closeModal" type="button" class="btn btn-secondary">Cancel</button>
              <button type="submit" :class="`btn btn-${modalAction === 'Approve' ? 'success' : 'warning'}`">
                {{ modalAction }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
        </div>
      </div>
    </div>
  </Authenticated>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Authenticated from '../Layouts/Authenticated.vue';
import AdminMenu from '../Components/Menu.vue';

export default {
  name: 'ApplicationsIndex',
  components: {
    Head,
    Link,
    Authenticated,
    AdminMenu
  },
  props: {
    applications: {
      type: Array,
      required: true
    },
    userCanCreate: {
      type: Boolean,
      default: false
    },
    filters: {
      type: Object,
      default: () => ({})
    }
  },
  setup(props) {
    const showModal = ref(false);
    const selectedApp = ref(null);
    const modalAction = ref('');
    const approvalType = ref(''); // 'security' or 'privacy'
    const showDeleted = ref(false);

    const filteredApplications = computed(() => {
      if (showDeleted.value) {
        return props.applications;
      }
      return props.applications.filter(app => !app.deleted_at);
    });

    const approvalForm = useForm({
      approval_status: '',
      approval_notes: '',
    });

    function showSecurityApproveModal(app) {
      selectedApp.value = app;
      modalAction.value = 'Approve Security';
      approvalType.value = 'security';
      approvalForm.approval_status = 'approved';
      approvalForm.approval_notes = '';
      showModal.value = true;
    }

    function showPrivacyApproveModal(app) {
      selectedApp.value = app;
      modalAction.value = 'Approve Privacy';
      approvalType.value = 'privacy';
      approvalForm.approval_status = 'approved';
      approvalForm.approval_notes = '';
      showModal.value = true;
    }

    function showRejectModal(app) {
      selectedApp.value = app;
      modalAction.value = 'Reject';
      approvalType.value = 'both'; // Can reject both at once
      approvalForm.approval_status = 'rejected';
      approvalForm.approval_notes = '';
      showModal.value = true;
    }

    function closeModal() {
      showModal.value = false;
      selectedApp.value = null;
      modalAction.value = '';
      approvalType.value = '';
      approvalForm.clearErrors();
    }

    function submitApproval() {
      let endpoint = '';
      if (approvalType.value === 'security') {
        endpoint = `/admin/applications/${selectedApp.value.guid}/security-approval`;
      } else if (approvalType.value === 'privacy') {
        endpoint = `/admin/applications/${selectedApp.value.guid}/privacy-approval`;
      } else {
        // For reject, we might want to reject both
        endpoint = `/admin/applications/${selectedApp.value.guid}/security-approval`;
      }
      
      approvalForm.patch(endpoint, {
        onSuccess: closeModal,
      });
    }

    function toggleStatus(app) {
      // Cycle through statuses: inactive -> active -> offline -> inactive
      const statusCycle = {
        'inactive': 'active',
        'active': 'offline', 
        'offline': 'inactive'
      };
      
      const nextStatus = statusCycle[app.status] || 'active';
      const statusLabels = {
        'active': 'activate',
        'inactive': 'deactivate', 
        'offline': 'set offline'
      };
      
      const action = statusLabels[nextStatus] || 'change status';
      
      if (confirm(`Are you sure you want to ${action} this application?`)) {
        router.patch(`/admin/applications/toggle-status/${app.guid}`);
      }
    }

    function deleteApp(app) {
      if (confirm('Are you sure you want to delete this application? It can be restored later.')) {
        router.delete(`/admin/applications/${app.guid}`);
      }
    }

    function restoreApp(app) {
      if (confirm('Are you sure you want to restore this application?')) {
        router.patch(`/admin/applications/restore/${app.id}`);
      }
    }

    function forceDeleteApp(app) {
      if (confirm('Are you sure you want to permanently delete this application? This action cannot be undone.')) {
        router.delete(`/admin/applications/force-delete/${app.id}`);
      }
    }

    function getApprovalBadgeColor(status) {
      const colors = {
        approved: 'success',
        rejected: 'danger',
        pending: 'warning'
      };
      return colors[status] || 'secondary';
    }

    function getStatusBadgeColor(status) {
      const colors = {
        active: 'success',
        inactive: 'secondary',
        offline: 'danger'
      };
      return colors[status] || 'secondary';
    }

    function getNextStatus(currentStatus) {
      const statusCycle = {
        'inactive': 'Active',
        'active': 'Offline', 
        'offline': 'Inactive'
      };
      return statusCycle[currentStatus] || 'Active';
    }

    function formatDate(dateString) {
      return new Date(dateString).toLocaleDateString();
    }

    function sortBy(field) {
      const currentSort = props.filters.sort;
      const currentDirection = props.filters.direction;
      
      let newDirection = 'asc';
      if (currentSort === field && currentDirection === 'asc') {
        newDirection = 'desc';
      }
      
      router.get('/admin/applications', {
        sort: field,
        direction: newDirection
      }, {
        preserveState: true,
        replace: true
      });
    }

    function getSortClass(field) {
      return props.filters.sort === field ? 'sort-active' : '';
    }

    function getSortIcon(field) {
      if (props.filters.sort !== field) {
        return 'bi bi-arrow-down-up text-muted';
      }
      return props.filters.direction === 'asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down';
    }

    return {
      showModal,
      selectedApp,
      modalAction,
      approvalType,
      showDeleted,
      filteredApplications,
      approvalForm,
      showSecurityApproveModal,
      showPrivacyApproveModal,
      showRejectModal,
      closeModal,
      submitApproval,
      toggleStatus,
      deleteApp,
      restoreApp,
      forceDeleteApp,
      getApprovalBadgeColor,
      getStatusBadgeColor,
      getNextStatus,
      formatDate,
      sortBy,
      getSortClass,
      getSortIcon
    };
  }
}
</script>

<style scoped>
.sortable-header {
  cursor: pointer;
  user-select: none;
  position: relative;
  padding-right: 2rem !important;
}

.sortable-header:hover {
  background-color: #e9ecef;
}

.sortable-header i {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.8rem;
}

.sort-active {
  background-color: #dee2e6;
}

.sort-active i {
  color: #0d6efd !important;
}
</style>
