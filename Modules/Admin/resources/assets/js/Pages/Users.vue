<template>
  <Authenticated>
    <Head title="User Management" />
    <div class="container-fluid py-4">
      <div class="row">

        <div class="col-lg-12">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">Admin User Management</h5>
            </div>
            <div class="card-body">

    <!-- Search and Filter Controls -->
    <form class="row mb-4" @submit.prevent="filterUsers">
      <div class="col-md-4">
        <input
          v-model="searchQuery"
          type="text"
          class="form-control"
          placeholder="Search users..."
        />
      </div>
      <div class="col-md-3">
        <select v-model="roleFilter" class="form-select">
          <option value="">All Roles</option>
          <option value="Super Admin">Super Admin</option>
          <option value="Admin Manager">Admin Manager</option>
          <option value="Application Manager">Application Manager</option>
          <option value="Security Officer">Security Officer</option>
          <option value="Privacy Officer">Privacy Officer</option>
          <option value="Admin Guest">Admin Guest</option>
          <option value="Institution User">Institution User</option>
          <option value="Student">Student</option>
        </select>
      </div>
      <div class="col-md-3">
        <select v-model="statusFilter" class="form-select">
          <option value="">All Users</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="deleted">Deleted</option>
        </select>
      </div>
      <div class="col-md-2">
        <button 
          type="submit" 
          class="btn btn-primary w-100" 
          :disabled="loading"
        >
          <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
          <i v-else class="bi bi-funnel me-2"></i>
          Filter
        </button>
      </div>
    </form>

    <!-- Users Table -->
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">Admin Users</h5>
      </div>
      <div class="card-body p-0">
        <div v-if="loading" class="text-center p-4">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
        
        <div v-else-if="filteredUsers.length === 0" class="text-center p-4 text-muted">
          No admin users found.
        </div>
        
        <div v-else class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr style="border-bottom: 2px solid #dee2e6; background-color: white;">
                <th>Name</th>
                <th>Email</th>
                <th>Organization</th>
                <th>Identity Provider</th>
                <th>GUID</th>
                <th>Roles</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="user in filteredUsers"
                :key="user.id"
                :class="{ 'table-secondary': user.deleted_at }"
              >
                <td>
                  <strong>{{ user.display_name || user.name }}</strong>
                  <div v-if="user.deleted_at" class="text-danger small">
                    <i class="bi bi-trash me-1"></i>Deleted
                  </div>
                </td>
                <td>{{ user.email }}</td>
                <td>{{ user.organization || '—' }}</td>
                <td>
                  <span v-if="user.identity_provider" class="badge" :class="getIdpBadgeClass(user.identity_provider)">
                    {{ user.identity_provider.toUpperCase() }}
                  </span>
                  <span v-else class="text-muted small">—</span>
                </td>
                <td>
                  <span class="font-monospace small text-muted" style="word-break: break-all;">{{ getUserGuid(user) || '—' }}</span>
                </td>
                <td>
                  <div class="d-flex flex-wrap gap-1">
                    <span
                      v-for="role in user.admin_roles"
                      :key="role"
                      class="badge"
                      :class="getRoleBadgeClass(role)"
                    >
                      {{ role }}
                    </span>
                  </div>
                </td>
                <td>
                  <span
                    v-if="!user.deleted_at"
                    class="badge"
                    :class="user.is_active ? 'bg-success' : 'bg-warning'"
                  >
                    {{ user.is_active ? 'Active' : 'Inactive' }}
                  </span>
                  <span v-else class="badge bg-danger">Deleted</span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm" role="group">
                    <!-- Role Management -->
                    <button
                      v-if="!user.deleted_at && canManageUsers && !isRestrictedUser(user)"
                      type="button"
                      class="btn btn-outline-primary"
                      @click="openRoleModal(user)"
                    >
                      <i class="bi bi-person-gear"></i>
                    </button>
                    
                    <!-- Status Toggle -->
                    <button
                      v-if="!user.deleted_at && canManageUsers"
                      type="button"
                      class="btn"
                      :class="user.is_active ? 'btn-outline-warning' : 'btn-outline-success'"
                      @click="toggleUserStatus(user)"
                      :disabled="processing === user.id"
                    >
                      <i :class="user.is_active ? 'bi bi-pause-fill' : 'bi bi-play-fill'"></i>
                    </button>
                    
                    <!-- Soft Delete -->
                    <button
                      v-if="!user.deleted_at && canManageUsers"
                      type="button"
                      class="btn btn-outline-danger"
                      @click="deleteUser(user)"
                      :disabled="processing === user.id"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                    
                    <!-- Restore -->
                    <button
                      v-if="user.deleted_at && canManageUsers"
                      type="button"
                      class="btn btn-outline-success"
                      @click="restoreUser(user)"
                      :disabled="processing === user.id"
                    >
                      <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    
                    <!-- Force Delete -->
                    <button
                      v-if="user.deleted_at && canManageUsers"
                      type="button"
                      class="btn btn-outline-dark"
                      @click="forceDeleteUser(user)"
                      :disabled="processing === user.id"
                    >
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Role Management Modal -->
    <div
      class="modal fade"
      id="roleModal"
      tabindex="-1"
      aria-labelledby="roleModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="roleModalLabel">
              Manage Roles - {{ selectedUser?.display_name || selectedUser?.name }}
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Admin Roles</label>
              <div class="form-check" v-for="role in availableRoles" :key="role">
                <input
                  class="form-check-input"
                  type="checkbox"
                  :id="'role-' + role.replace(' ', '-')"
                  :value="role"
                  v-model="selectedRoles"
                />
                <label class="form-check-label" :for="'role-' + role.replace(' ', '-')">
                  <span class="badge me-2" :class="getRoleBadgeClass(role)">
                    {{ role }}
                  </span>
                  {{ getRoleDescription(role) }}
                </label>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              Cancel
            </button>
            <button
              type="button"
              class="btn btn-primary"
              @click="updateUserRoles"
              :disabled="updatingRoles"
            >
              <span v-if="updatingRoles" class="spinner-border spinner-border-sm me-2"></span>
              Update Roles
            </button>
          </div>
        </div>
      </div>
    </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Authenticated>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import Authenticated from '../Layouts/Authenticated.vue';
import AdminMenu from '../Components/Menu.vue';

export default {
  name: 'UsersIndex',
  components: {
    Head,
    Authenticated,
    AdminMenu
  },
  props: {
    users: {
      type: Array,
      default: () => []
    },
    filters: {
      type: Object,
      default: () => ({})
    }
  },
  setup(props) {
    const { props: pageProps } = usePage()

    const users = ref(props.users || [])
    const loading = ref(false)
    const processing = ref(null)
    const updatingRoles = ref(false)
    const canManageUsers = ref(pageProps.canManageUsers || false)

    // Search and filter
    const searchQuery = ref(props.filters?.search || '')
    const roleFilter = ref(props.filters?.role || '')
    const statusFilter = ref(props.filters?.status || '')

    // Role management
    const selectedUser = ref(null)
    const selectedRoles = ref([])
    const availableRoles = [
      'Super Admin',
      'Admin Manager', 
      'Application Manager', 
      'Security Officer',
      'Privacy Officer',
      'Admin Guest'
    ]

    const RESTRICTED_ROLES = ['Institution User', 'Student']

    const isRestrictedUser = (user) => {
      const roles = user.admin_roles || []
      return roles.some(role => RESTRICTED_ROLES.includes(role))
    }

    // Computed properties
    const filteredUsers = computed(() => {
      // Since we're doing server-side filtering now, just return the users as-is
      return users.value
    })

    const filterUsers = () => {
      loading.value = true
      
      router.get('/admin/users', {
        search: searchQuery.value,
        role: roleFilter.value,
        status: statusFilter.value,
      }, {
        preserveState: true,
        preserveScroll: true,
        only: ['users'],
        onSuccess: (page) => {
          users.value = page.props.users
        },
        onFinish: () => {
          loading.value = false
        }
      })
    }

    const getUserGuid = (user) => {
      switch (user.identity_provider) {
        case 'idir':  return user.idir_user_guid
        case 'bcsc':  return user.bcsc_user_guid
        case 'bceid': return user.bceid_user_guid
        default:      return user.guid
      }
    }

    const getIdpBadgeClass = (idp) => {
      const classes = {
        'bceid': 'bg-warning text-dark',
        'idir':  'bg-primary',
        'bcsc':  'bg-success',
      }
      return classes[idp] || 'bg-secondary'
    }

    const getRoleBadgeClass = (role) => {
      const classes = {
        'Super Admin': 'bg-danger',
        'Admin Manager': 'bg-primary',
        'Application Manager': 'bg-warning text-dark',
        'Security Officer': 'bg-info',
        'Privacy Officer': 'bg-success',
        'Admin Guest': 'bg-secondary',
        'Institution User': 'bg-secondary',
        'Student': 'bg-secondary'
      }
      return classes[role] || 'bg-secondary'
    }

    const getRoleDescription = (role) => {
      const descriptions = {
        'Super Admin': 'Full system access and user management',
        'Admin Manager': 'Manage admin users and permissions',
        'Application Manager': 'Manage applications',
        'Security Officer': 'Security oversight and application approvals',
        'Privacy Officer': 'Privacy compliance and data protection',
        'Admin Guest': 'Limited admin access',
        'Institution User': 'BCeID Institutional user access',
        'Student': 'BCSC Student access'
      }
      return descriptions[role] || ''
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-CA', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const openRoleModal = (user) => {
      selectedUser.value = user
      selectedRoles.value = [...(user.admin_roles || [])]
      
      const modal = new bootstrap.Modal(document.getElementById('roleModal'))
      modal.show()
    }

    const updateUserRoles = () => {
      if (!selectedUser.value) return
      
      updatingRoles.value = true
      
      router.patch(`/admin/users/update-roles/${selectedUser.value.id}`, {
        admin_roles: selectedRoles.value
      }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          // Update the user in our local array
          const userIndex = users.value.findIndex(u => u.id === selectedUser.value.id)
          if (userIndex !== -1) {
            users.value[userIndex].admin_roles = [...selectedRoles.value]
          }
          
          // Close modal
          const modal = bootstrap.Modal.getInstance(document.getElementById('roleModal'))
          modal.hide()
          
          selectedUser.value = null
          selectedRoles.value = []
        },
        onFinish: () => {
          updatingRoles.value = false
        }
      })
    }

    const toggleUserStatus = (user) => {
      processing.value = user.id
      
      router.patch(`/admin/users/toggle-status/${user.id}`, {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          // Update the user in our local array
          const userIndex = users.value.findIndex(u => u.id === user.id)
          if (userIndex !== -1) {
            users.value[userIndex].is_active = !users.value[userIndex].is_active
          }
        },
        onFinish: () => {
          processing.value = null
        }
      })
    }

    const deleteUser = (user) => {
      if (!confirm('Are you sure you want to delete this user? This action can be undone.')) {
        return
      }
      
      processing.value = user.id
      
      router.delete(`/admin/users/${user.id}`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          // Update the user in our local array
          const userIndex = users.value.findIndex(u => u.id === user.id)
          if (userIndex !== -1) {
            users.value[userIndex].deleted_at = new Date().toISOString()
          }
        },
        onFinish: () => {
          processing.value = null
        }
      })
    }

    const restoreUser = (user) => {
      processing.value = user.id
      
      router.patch(`/admin/users/restore/${user.id}`, {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          // Update the user in our local array
          const userIndex = users.value.findIndex(u => u.id === user.id)
          if (userIndex !== -1) {
            users.value[userIndex].deleted_at = null
          }
        },
        onFinish: () => {
          processing.value = null
        }
      })
    }

    const forceDeleteUser = (user) => {
      if (!confirm('Are you sure you want to permanently delete this user? This action cannot be undone.')) {
        return
      }
      
      processing.value = user.id
      
      router.delete(`/admin/users/force-delete/${user.id}`, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          // Remove the user from our local array
          const userIndex = users.value.findIndex(u => u.id === user.id)
          if (userIndex !== -1) {
            users.value.splice(userIndex, 1)
          }
        },
        onFinish: () => {
          processing.value = null
        }
      })
    }

    onMounted(() => {
      // Any initialization code here
    })

    return {
      users,
      loading,
      processing,
      updatingRoles,
      searchQuery,
      roleFilter,
      statusFilter,
      selectedUser,
      selectedRoles,
      availableRoles,
      isRestrictedUser,
      getUserGuid,
      getIdpBadgeClass,
      canManageUsers,
      filteredUsers,
      filterUsers,
      getRoleBadgeClass,
      getRoleDescription,
      formatDate,
      openRoleModal,
      updateUserRoles,
      toggleUserStatus,
      deleteUser,
      restoreUser,
      forceDeleteUser
    }
  }
}
</script>

<style scoped>
.table thead th {
  border-bottom: 2px solid #dee2e6;
  background-color: white;
}

.btn-group-sm > .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

.badge {
  font-size: 0.7em;
}

.table-secondary {
  opacity: 0.7;
}

.form-check-label {
  display: flex;
  align-items: center;
}

.form-check-label .badge {
  margin-right: 0.5rem;
}
</style>
