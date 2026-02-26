<template>
  <Authenticated>
    <Head title="Edit Application" />
    <div class="container-fluid px-4 py-6">
      <div class="row">

        
        <!-- Main Content Column -->
        <div class="col-lg-12">
          <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
              <div class="d-flex align-items-center">
                <Link href="/admin/applications" class="btn btn-outline-secondary me-3">
                  <i class="bi bi-arrow-left me-2"></i>Back
                </Link>
                <h1 class="h4 mb-0">Edit Application: {{ application.name }}</h1>
              </div>
            </div>
            <div class="card-body">

              <!-- Success Message -->
              <div v-if="$page.props.flash?.success" class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                {{ $page.props.flash.success }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>

              <!-- Error Message -->
              <div v-if="$page.props.flash?.error" class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                {{ $page.props.flash.error }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>

              <!-- General Form Errors -->
              <div v-if="form.errors && Object.keys(form.errors).length > 0 && !Object.keys(form.errors).some(key => ['name', 'description', 'info_url', 'info_label', 'contact_name', 'contact_email', 'contact_phone'].includes(key))" class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Please check the following errors:</strong>
                <ul class="mb-0 mt-2">
                  <li v-for="(error, field) in form.errors" :key="field" v-if="!['name', 'description', 'info_url', 'info_label', 'contact_name', 'contact_email', 'contact_phone'].includes(field)">
                    {{ field.replace('_', ' ') }}: {{ Array.isArray(error) ? error[0] : error }}
                  </li>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
    
    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-body">
            <form @submit.prevent="submit">
              <!-- Basic Information -->
              <div class="mb-4">
                <h5 class="card-title">Basic Information</h5>
                <div class="row">
                  <div class="col-12">
                    <div class="mb-3">
                      <label class="form-label">Application Name <span class="text-danger">*</span></label>
                      <input v-model="form.name" :class="{'is-invalid': form.errors.name}" class="form-control" required />
                      <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="mb-3">
                      <label class="form-label">Description</label>
                      <input v-model="form.description" :class="{'is-invalid': form.errors.description}" class="form-control" />
                      <div v-if="form.errors.description" class="invalid-feedback">{{ form.errors.description }}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">Info URL</label>
                      <input v-model="form.info_url" :class="{'is-invalid': form.errors.info_url}" class="form-control" type="url" placeholder="https://example.com/more-info" />
                      <div v-if="form.errors.info_url" class="invalid-feedback">{{ form.errors.info_url }}</div>
                      <div class="form-text">
                        <small class="text-muted">Optional link for additional information about this application</small>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">Info Label</label>
                      <input v-model="form.info_label" :class="{'is-invalid': form.errors.info_label}" class="form-control" placeholder="e.g., Learn More, User Guide" />
                      <div v-if="form.errors.info_label" class="invalid-feedback">{{ form.errors.info_label }}</div>
                      <div class="form-text">
                        <small class="text-muted">Display text for the info link (required if URL is provided)</small>
                      </div>
                    </div>
                  </div>
                </div>
                
              </div>

              <hr/>

              <!-- Contact Information -->
              <div class="mb-4">
                <h5 class="card-title">Contact Information</h5>
                <div class="row">
                  <div class="col-md-4">
                    <div class="mb-3">
                      <label class="form-label">Contact Name <span class="text-danger">*</span></label>
                      <input v-model="form.contact_name" :class="{'is-invalid': form.errors.contact_name}" class="form-control" required />
                      <div v-if="form.errors.contact_name" class="invalid-feedback">{{ form.errors.contact_name }}</div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="mb-3">
                      <label class="form-label">Contact Email <span class="text-danger">*</span></label>
                      <input v-model="form.contact_email" :class="{'is-invalid': form.errors.contact_email}" type="email" class="form-control" required />
                      <div v-if="form.errors.contact_email" class="invalid-feedback">{{ form.errors.contact_email }}</div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="mb-3">
                      <label class="form-label">Contact Phone</label>
                      <input v-model="form.contact_phone" :class="{'is-invalid': form.errors.contact_phone}" class="form-control" />
                      <div v-if="form.errors.contact_phone" class="invalid-feedback">{{ form.errors.contact_phone }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <hr/>

              <!-- Identity Providers & Redirect URLs -->
              <div class="mb-4">
                <h5 class="card-title">Identity Providers & Redirect URLs</h5>
                <p class="text-muted small">Select which identity providers this application supports and provide the corresponding redirect URLs.</p>
                
                <div class="row">
                  <div class="col-md-4">
                    <div class="mb-3">
                      <div class="form-check">
                        <input v-model="form.bcsc_enabled" :class="{'is-invalid': form.errors.bcsc_enabled}" class="form-check-input" type="checkbox" id="bcsc_enabled" />
                        <label class="form-check-label fw-bold" for="bcsc_enabled">BC Services Card (BCSC)</label>
                      </div>
                      <div v-if="form.bcsc_enabled" class="mt-2">
                        <label class="form-label small">BCSC Redirect URL <span class="text-danger">*</span></label>
                        <input v-model="form.bcsc_redirect_url" :class="{'is-invalid': form.errors.bcsc_redirect_url}" type="url" class="form-control form-control-sm" />
                        <div v-if="form.errors.bcsc_redirect_url" class="invalid-feedback">{{ form.errors.bcsc_redirect_url }}</div>
                      </div>
                    </div>
                  </div>
                  
                  <div class="col-md-4">
                    <div class="mb-3">
                      <div class="form-check">
                        <input v-model="form.idir_enabled" :class="{'is-invalid': form.errors.idir_enabled}" class="form-check-input" type="checkbox" id="idir_enabled" />
                        <label class="form-check-label fw-bold" for="idir_enabled">IDIR</label>
                      </div>
                      <div v-if="form.idir_enabled" class="mt-2">
                        <label class="form-label small">IDIR Redirect URL <span class="text-danger">*</span></label>
                        <input v-model="form.idir_redirect_url" :class="{'is-invalid': form.errors.idir_redirect_url}" type="url" class="form-control form-control-sm" />
                        <div v-if="form.errors.idir_redirect_url" class="invalid-feedback">{{ form.errors.idir_redirect_url }}</div>
                      </div>
                    </div>
                  </div>
                  
                  <div class="col-md-4">
                    <div class="mb-3">
                      <div class="form-check">
                        <input v-model="form.bceid_enabled" :class="{'is-invalid': form.errors.bceid_enabled}" class="form-check-input" type="checkbox" id="bceid_enabled" />
                        <label class="form-check-label fw-bold" for="bceid_enabled">BCeID</label>
                      </div>
                      <div v-if="form.bceid_enabled" class="mt-2">
                        <label class="form-label small">BCeID Redirect URL <span class="text-danger">*</span></label>
                        <input v-model="form.bceid_redirect_url" :class="{'is-invalid': form.errors.bceid_redirect_url}" type="url" class="form-control form-control-sm" />
                        <div v-if="form.errors.bceid_redirect_url" class="invalid-feedback">{{ form.errors.bceid_redirect_url }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <hr/>

              <!-- Compliance -->
              <div class="mb-4">
                <h5 class="card-title">Compliance Documentation</h5>
                <div class="row">
                  <div class="col-12">
                    <div class="mb-3">
                      <label class="form-label">Application Status <span class="text-danger">*</span></label>
                      <select v-model="form.status" :class="{'is-invalid': form.errors.status}" class="form-select" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="offline">Offline</option>
                          
                        <option value="draft">Draft</option>
                        <option value="submitted">Submitted</option>
                        <option value="under_review">Under Review</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>  
                      </select>
                      <div v-if="form.errors.status" class="invalid-feedback">{{ form.errors.status }}</div>
                      <div class="form-text">
                        <small class="text-muted">
                          <strong>Active:</strong> Application is fully operational and available to users.<br>
                          <strong>Inactive:</strong> Application is temporarily disabled and not visible to users.<br>
                          <strong>Offline:</strong> Application is down for maintenance or scheduled outage.
                        </small>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-check">
                      <input v-model="form.profile_integration_ready" class="form-check-input" type="checkbox" id="profile_integration_ready" />
                      <label class="form-check-label" for="profile_integration_ready">
                        Profile Integration Ready (if yes, the user would be prompted the popup dialog to agree to share their profile information)
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-check">
                      <input v-model="form.stra_provided" class="form-check-input" type="checkbox" id="stra_provided" />
                      <label class="form-check-label" for="stra_provided">
                        Security Threat Risk Assessment (STRA) Provided
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-check">
                      <input v-model="form.pia_provided" class="form-check-input" type="checkbox" id="pia_provided" />
                      <label class="form-check-label" for="pia_provided">
                        Privacy Impact Assessment (PIA) Provided
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Alert Messages -->
              <div class="mb-4">
                <h5 class="card-title">Alert Messages</h5>
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">Active Alert Message</label>
                      <textarea v-model="form.active_alert_message" :class="{'is-invalid': form.errors.active_alert_message}" class="form-control" rows="3" placeholder="Message shown when application is active..."></textarea>
                      <div v-if="form.errors.active_alert_message" class="invalid-feedback">{{ form.errors.active_alert_message }}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">Offline Alert Message</label>
                      <textarea v-model="form.offline_alert_message" :class="{'is-invalid': form.errors.offline_alert_message}" class="form-control" rows="3" placeholder="Message shown when application is offline..."></textarea>
                      <div v-if="form.errors.offline_alert_message" class="invalid-feedback">{{ form.errors.offline_alert_message }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Offline Schedule -->
              <div class="mb-4">
                <h5 class="card-title">Offline Schedule</h5>
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">Offline Start Time</label>
                      <input v-model="form.offline_start_time" :class="{'is-invalid': form.errors.offline_start_time}" type="datetime-local" class="form-control" />
                      <div v-if="form.errors.offline_start_time" class="invalid-feedback">{{ form.errors.offline_start_time }}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">Offline End Time</label>
                      <input v-model="form.offline_end_time" :class="{'is-invalid': form.errors.offline_end_time}" type="datetime-local" class="form-control" />
                      <div v-if="form.errors.offline_end_time" class="invalid-feedback">{{ form.errors.offline_end_time }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Comments -->
              <div class="mb-4">
                <h5 class="card-title">Additional Comments</h5>
                <textarea v-model="form.comments" :class="{'is-invalid': form.errors.comments}" class="form-control" rows="4" placeholder="Any additional notes or comments about this application..."></textarea>
                <div v-if="form.errors.comments" class="invalid-feedback">{{ form.errors.comments }}</div>
              </div>

              <hr/>

              <!-- Data Access Permissions -->
              <div class="mb-4">
                <h5 class="card-title">
                  <i class="bi bi-database me-1"></i>
                  Individual Data Access Permissions
                </h5>
                <p class="text-muted small mb-3">
                  The student/individual would be prompted to approve sharing the selected fields.<br/>
                  Configure which individual data fields this application can access. 
                  <br/><span class="text-warning">⚠️</span> indicates personally identifiable information (PII).
                </p>
                <div class="accordion" id="dataPermissionsAccordion">
                  <div v-for="table in Object.values(availableDataTables)" :key="table.name" class="accordion-item">
                    <h2 class="accordion-header">
                      <button 
                        class="accordion-button collapsed" 
                        type="button" 
                        :data-bs-toggle="`collapse`" 
                        :data-bs-target="`#collapse-${table.name}`"
                        :aria-expanded="false" 
                        :aria-controls="`collapse-${table.name}`"
                      >
                        <strong>{{ table.label }}</strong>
                        <span class="badge bg-secondary ms-2">{{ Object.keys(table.columns).length }} fields</span>
                        <span v-if="getTablePermissionCount(table.name) > 0" class="badge bg-primary ms-2">
                          {{ getTablePermissionCount(table.name) }} selected
                        </span>
                      </button>
                    </h2>
                    <div 
                      :id="`collapse-${table.name}`" 
                      class="accordion-collapse collapse" 
                      data-bs-parent="#dataPermissionsAccordion"
                    >
                      <div class="accordion-body">
                        <div class="row">
                          <div class="col-12 mb-2">
                            <div class="d-flex gap-2">
                              <button 
                                type="button" 
                                class="btn btn-sm btn-outline-primary"
                                @click="selectAllTableColumns(table.name)"
                              >
                                Select All as Optional
                              </button>
                              <button 
                                type="button" 
                                class="btn btn-sm btn-outline-secondary"
                                @click="clearAllTableColumns(table.name)"
                              >
                                Clear All
                              </button>
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div v-for="column in Object.values(table.columns)" :key="`${table.name}.${column.name}`" class="col-md-6 col-lg-4 mb-3">
                            <div class="card card-sm">
                              <div class="card-body p-2">
                                <div class="d-flex align-items-start justify-content-between">
                                  <div class="flex-grow-1">
                                    <h6 class="card-title mb-1 small d-flex align-items-center">
                                      <code class="me-1">{{ column.name }}</code>
                                      <span v-if="column.is_pii" class="text-warning" title="Personally Identifiable Information">⚠️</span>
                                      <span v-if="column.is_sensitive" class="text-danger" title="Sensitive Data">🔒</span>
                                    </h6>
                                    <p class="card-text text-muted small mb-2">{{ column.description }}</p>
                                    <!-- Display Name Input -->
                                    <div class="mb-2">
                                      <label :for="`display-name-${table.name}-${column.name}`" class="form-label small">Display Name</label>
                                      <input 
                                        :id="`display-name-${table.name}-${column.name}`"
                                        v-model="dataPermissions[`${table.name}.${column.name}`].display_name"
                                        class="form-control form-control-sm"
                                        type="text"
                                        :placeholder="column.default_display_name"
                                      >
                                    </div>
                                    <div class="mb-2">
                                      <label class="form-label small">Access Level</label>
                                      <select 
                                        v-model="dataPermissions[`${table.name}.${column.name}`].access_level"
                                        class="form-select form-select-sm"
                                        :class="{
                                          'border-success': dataPermissions[`${table.name}.${column.name}`].access_level === 'required',
                                          'border-warning': dataPermissions[`${table.name}.${column.name}`].access_level === 'optional'
                                        }"
                                      >
                                        <option value="none">No Access</option>
                                        <option value="optional">Optional</option>
                                        <option value="required">Required</option>
                                      </select>
                                      <div v-if="dataPermissions[`${table.name}.${column.name}`].access_level === 'required'" class="small text-success mt-1">
                                        <i class="bi bi-check-circle me-1"></i>Required field
                                      </div>
                                      <div v-else-if="dataPermissions[`${table.name}.${column.name}`].access_level === 'optional'" class="small text-warning mt-1">
                                        <i class="bi bi-info-circle me-1"></i>Optional field
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <hr/>
              
              <!-- Application Manager Controls Accordion -->
              <div class="mb-4">
                <h5 class="card-title">
                  <i class="bi bi-gear me-1"></i>
                  Application Manager Controls
                </h5>
                <p class="text-muted small mb-3">
                  Configure API access for institutions and individual data fields this application can access. 
                </p>

                <div class="accordion" id="managerControlsAccordion">
                  <div class="accordion-item">
                    <h2 class="accordion-header" id="managerControlsHeading">
                      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#managerControlsCollapse" aria-expanded="false" aria-controls="managerControlsCollapse">
                        <strong>API and Application Status Settings</strong>
                      </button>
                    </h2>
                    <div id="managerControlsCollapse" class="accordion-collapse collapse" aria-labelledby="managerControlsHeading" data-bs-parent="#managerControlsAccordion">
                      <div class="accordion-body">
                        <!-- API Credentials Section -->
                        <div class="mb-3">
                          <label class="form-label fw-bold">
                            <i class="bi bi-key me-1"></i>
                            API Credentials
                          </label>
                          <!-- Client ID -->
                          <div class="mb-2">
                            <label for="client_id" class="form-label">Client ID</label>
                            <div class="input-group">
                              <input 
                                v-model="form.client_id" 
                                type="text"
                                id="client_id"
                                class="form-control font-monospace"
                                :class="{ 'is-invalid': form.errors.client_id }"
                                placeholder="Auto-generated client ID"
                                readonly
                              >
                              <button 
                                type="button" 
                                class="btn btn-outline-secondary"
                                @click="generateClientId"
                                title="Generate new Client ID"
                              >
                                <i class="bi bi-arrow-clockwise"></i>
                              </button>
                            </div>
                            <div v-if="form.errors.client_id" class="invalid-feedback">
                              {{ form.errors.client_id }}
                            </div>
                          </div>
                          <!-- API Key -->
                          <div class="mb-2">
                            <label for="api_key" class="form-label">API Key</label>
                            <div class="input-group">
                              <input 
                                v-model="form.api_key" 
                                type="text"
                                id="api_key"
                                class="form-control font-monospace"
                                :class="{ 'is-invalid': form.errors.api_key }"
                                placeholder="Auto-generated API key"
                                readonly
                              >
                              <button 
                                type="button" 
                                class="btn btn-outline-secondary"
                                @click="generateApiKey"
                                title="Generate new API Key"
                              >
                                <i class="bi bi-arrow-clockwise"></i>
                              </button>
                            </div>
                            <div v-if="form.errors.api_key" class="invalid-feedback">
                              {{ form.errors.api_key }}
                            </div>
                          </div>
                          <!-- Client Secret -->
                          <div class="mb-2">
                            <label for="client_secret" class="form-label">Client Secret</label>
                            <div class="input-group">
                              <input 
                                v-model="form.client_secret" 
                                :type="showSecret ? 'text' : 'password'"
                                id="client_secret"
                                class="form-control font-monospace"
                                :class="{ 'is-invalid': form.errors.client_secret }"
                                placeholder="Auto-generated client secret"
                                readonly
                              >
                              <button 
                                type="button" 
                                class="btn btn-outline-secondary"
                                @click="showSecret = !showSecret"
                                title="Toggle visibility"
                              >
                                <i :class="showSecret ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                              </button>
                              <button 
                                type="button" 
                                class="btn btn-outline-secondary"
                                @click="generateClientSecret"
                                title="Generate new Client Secret"
                              >
                                <i class="bi bi-arrow-clockwise"></i>
                              </button>
                            </div>
                            <div v-if="form.errors.client_secret" class="invalid-feedback">
                              {{ form.errors.client_secret }}
                            </div>
                          </div>
                          
                          <div class="alert alert-warning mt-2">
                            <small>
                              <i class="bi bi-exclamation-triangle me-1"></i>
                              <strong>Warning:</strong> Regenerating credentials will invalidate existing API access.
                            </small>
                          </div>
                        </div>

                        <!-- Approval Actions -->
                        <div class="mb-3">
                          <label class="form-label fw-bold">Approval Notes</label>
                          <textarea 
                            v-model="form.approval_notes" 
                            class="form-control"
                            rows="3"
                            placeholder="Add notes about your approval decision..."
                          ></textarea>
                        </div>

                        <!-- API Access Permissions Section -->
                        <div class="mb-3">
                          <label class="form-label fw-bold">
                            <i class="bi bi-database me-1"></i>
                            API Access Permissions
                          </label>
                          <p class="text-muted small mb-3">
                            Configure which data fields this application's API can access. This includes individual data, institutional information, staff details, site information, and program data.
                          </p>
                          
                          <div class="accordion" id="apiAccessAccordion">
                            <div v-for="(table, tableName) in apiAccessTables" :key="tableName" class="accordion-item">
                              <h2 class="accordion-header" :id="`api-heading-${tableName}`">
                                <button 
                                  class="accordion-button collapsed" 
                                  type="button" 
                                  data-bs-toggle="collapse" 
                                  :data-bs-target="`#api-collapse-${tableName}`" 
                                  :aria-expanded="false" 
                                  :aria-controls="`api-collapse-${tableName}`"
                                >
                                  <div class="d-flex justify-content-between align-items-center w-100 me-2">
                                    <span>
                                      <strong>{{ table.label }}</strong>
                                      <span v-if="table.description" class="text-muted ms-2">{{ table.description }}</span>
                                    </span>
                                    <span class="badge bg-secondary me-2">{{ getApiTablePermissionCount(tableName) }} fields</span>
                                  </div>
                                </button>
                              </h2>
                              <div 
                                :id="`api-collapse-${tableName}`" 
                                class="accordion-collapse collapse" 
                                :aria-labelledby="`api-heading-${tableName}`" 
                                data-bs-parent="#apiAccessAccordion"
                              >
                                <div class="accordion-body">
                                  <div class="row mb-3">
                                    <div class="col-auto">
                                      <button 
                                        type="button" 
                                        class="btn btn-sm btn-outline-info"
                                        @click="selectAllApiTableColumns(tableName)"
                                      >
                                        <i class="bi bi-eye me-1"></i>
                                        All Read
                                      </button>
                                    </div>
                                    <div class="col-auto">
                                      <button 
                                        type="button" 
                                        class="btn btn-sm btn-outline-warning"
                                        @click="setAllApiTableColumnsWrite(tableName)"
                                      >
                                        <i class="bi bi-pencil me-1"></i>
                                        All Write
                                      </button>
                                    </div>
                                    <div class="col-auto">
                                      <button 
                                        type="button" 
                                        class="btn btn-sm btn-outline-success"
                                        @click="setAllApiTableColumnsReadWrite(tableName)"
                                      >
                                        <i class="bi bi-check-all me-1"></i>
                                        All Read & Write
                                      </button>
                                    </div>
                                    <div class="col-auto">
                                      <button 
                                        type="button" 
                                        class="btn btn-sm btn-outline-secondary"
                                        @click="clearAllApiTableColumns(tableName)"
                                      >
                                        <i class="bi bi-x-circle me-1"></i>
                                        Clear All
                                      </button>
                                    </div>
                                  </div>
                                  
                                  <div class="row">
                                    <div 
                                      v-for="(column, columnName) in table.columns" 
                                      :key="columnName" 
                                      class="col-md-6 col-lg-4 mb-3"
                                    >
                                      <div class="card h-100 border">
                                        <div class="card-body p-3">
                                          <div class="d-flex align-items-start justify-content-between mb-2">
                                            <div class="flex-grow-1">
                                              <h6 class="card-subtitle mb-1 text-primary">
                                                {{ column.label }}
                                                <span v-if="column.sensitive" class="badge bg-warning ms-1" title="Sensitive Information">
                                                  <i class="bi bi-exclamation-triangle"></i>
                                                </span>
                                                <span v-if="column.pii" class="badge bg-danger ms-1" title="Personally Identifiable Information">
                                                  PII
                                                </span>
                                              </h6>
                                              <p class="card-text small text-muted mb-2">{{ column.description }}</p>
                                              <small class="text-muted">{{ tableName }}.{{ columnName }}</small>
                                            </div>
                                          </div>
                                          
                                          <div class="mb-2">
                                            <label class="form-label small">Display Name</label>
                                            <input 
                                              v-model="apiAccessPermissions[`${table.name}.${column.name}`].display_name"
                                              type="text"
                                              class="form-control form-control-sm"
                                              placeholder="Field display name"
                                            >
                                          </div>
                                          
                                          <div class="mb-2">
                                            <label class="form-label small">Access Level</label>
                                            <select 
                                              v-model="apiAccessPermissions[`${table.name}.${column.name}`].access_level"
                                              class="form-select form-select-sm"
                                              :class="{
                                                'border-success': apiAccessPermissions[`${table.name}.${column.name}`].access_level === 'read_write',
                                                'border-info': apiAccessPermissions[`${table.name}.${column.name}`].access_level === 'read',
                                                'border-warning': apiAccessPermissions[`${table.name}.${column.name}`].access_level === 'write'
                                              }"
                                            >
                                              <option value="none">No Access</option>
                                              <option value="read">Read</option>
                                              <option value="write">Write</option>
                                              <option value="read_write">Read and Write</option>
                                            </select>
                                            <div v-if="apiAccessPermissions[`${table.name}.${column.name}`].access_level === 'read_write'" class="small text-success mt-1">
                                              <i class="bi bi-check-all me-1"></i>Full access (read and write)
                                            </div>
                                            <div v-else-if="apiAccessPermissions[`${table.name}.${column.name}`].access_level === 'read'" class="small text-info mt-1">
                                              <i class="bi bi-eye me-1"></i>Read access only
                                            </div>
                                            <div v-else-if="apiAccessPermissions[`${table.name}.${column.name}`].access_level === 'write'" class="small text-warning mt-1">
                                              <i class="bi bi-pencil me-1"></i>Write access only
                                            </div>
                                          </div>
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>

                        
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" :disabled="form.processing" class="btn btn-primary">
                  <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                  Update Application
                </button>
                <Link href="/admin/applications" class="btn btn-outline-secondary">Cancel</Link>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <!-- Final Approval Warning -->
        <div v-if="isFinallyApproved" class="alert alert-success mb-3">
          <div class="d-flex align-items-center">
            <i class="bi bi-lock me-2"></i>
            <div>
              <strong class="d-block">Application Security &amp; Privacy Approved</strong>
              <small>Both security and privacy approvals are complete. Changes to approvals are no longer permitted.</small>
            </div>
          </div>
        </div>

        <!-- Security Officer Controls -->
        <div v-if="canApproveSecurity" class="card mb-3">
          <div class="card-header bg-info text-white">
            <h6 class="card-title mb-0">
              <i class="bi bi-shield-check me-2"></i>
              Security Approval
            </h6>
          </div>
          <div class="card-body">
            
            <!-- Security Approval Status -->
            <div class="mb-3">
              <label for="security_approval_status" class="form-label fw-bold">
                <i class="bi bi-check-circle me-1"></i>
                Security Approval Status
              </label>
              <select 
                v-model="securityForm.security_approval_status" 
                id="security_approval_status"
                class="form-select"
                :class="{ 'is-invalid': securityForm.errors.security_approval_status }"
                :disabled="!canModifySecurityStatus || !canApproveSecurity"
              >
                <option value="pending">Pending Review</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
              </select>
              <div v-if="securityForm.errors.security_approval_status" class="invalid-feedback">
                {{ securityForm.errors.security_approval_status }}
              </div>
              <div v-if="!canModifySecurityStatus" class="form-text text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Status is locked after approval. You can only update notes.
              </div>
              <div v-if="!canApproveSecurity" class="form-text text-danger">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Only Security Officers can modify security approvals.
              </div>
            </div>

            <!-- Security Approval Notes -->
            <div class="mb-3">
              <label class="form-label fw-bold">Security Approval Notes</label>
              <textarea 
                v-model="securityForm.security_approval_notes" 
                class="form-control"
                rows="3"
                placeholder="Add notes about your security approval decision..."
                :disabled="!canApproveSecurity"
              ></textarea>
              <div v-if="!canApproveSecurity" class="form-text text-danger">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Only Security Officers can modify security approval notes.
              </div>
            </div>

            <div class="d-grid gap-2">
              <button 
                type="button" 
                :class="canModifySecurityStatus ? 'btn btn-success' : 'btn btn-info'"
                @click="saveSecurityApproval"
                :disabled="securityForm.processing || !canApproveSecurity"
              >
                <span v-if="securityForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                <i :class="canModifySecurityStatus ? 'bi bi-check-lg me-2' : 'bi bi-pencil-square me-2'"></i>
                {{ canModifySecurityStatus ? 'Save Security Approval' : 'Update Security Notes' }}
              </button>
            </div>

          </div>
        </div>

        <!-- Security Approval Read-Only (for non-Security Officers) -->
        <div v-else-if="application.security_approval_status" class="card mb-3">
          <div class="card-header bg-light border">
            <h6 class="card-title mb-0 text-muted">
              <i class="bi bi-shield-check me-2"></i>
              Security Approval (Read Only)
            </h6>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label fw-bold text-muted">Security Approval Status</label>
              <div>
                <span :class="`badge bg-${getSecurityApprovalBadgeColor(application.security_approval_status)} me-2`">
                  {{ (application.security_approval_status || 'pending').toUpperCase() }}
                </span>
              </div>
            </div>
            <div v-if="application.security_approval_notes" class="mb-3">
              <label class="form-label fw-bold text-muted">Security Approval Notes</label>
              <div class="p-2 bg-light border rounded">
                {{ application.security_approval_notes }}
              </div>
            </div>
            <div v-if="application.security_approved_at && application.security_approver" class="mb-3">
              <label class="form-label fw-bold text-muted">Approved By</label>
              <div class="small">
                {{ application.security_approver.name }}<br>
                <span class="text-muted">{{ formatDate(application.security_approved_at) }}</span>
              </div>
            </div>
            <div class="alert alert-info small mb-0">
              <i class="bi bi-info-circle me-1"></i>
              Only Security Officers can modify security approvals.
            </div>
          </div>
        </div>

        <!-- Privacy Officer Controls -->
        <div v-if="canApprovePrivacy" class="card mb-3">
          <div class="card-header bg-purple text-white">
            <h6 class="card-title mb-0">
              <i class="bi bi-person-check me-2"></i>
              Privacy Approval
            </h6>
          </div>
          <div class="card-body">
            
            <!-- Privacy Approval Status -->
            <div class="mb-3">
              <label for="privacy_approval_status" class="form-label fw-bold">
                <i class="bi bi-check-circle me-1"></i>
                Privacy Approval Status
              </label>
              <select 
                v-model="privacyForm.privacy_approval_status" 
                id="privacy_approval_status"
                class="form-select"
                :class="{ 'is-invalid': privacyForm.errors.privacy_approval_status }"
                :disabled="!canModifyPrivacyStatus || !canApprovePrivacy"
              >
                <option value="pending">Pending Review</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
              </select>
              <div v-if="privacyForm.errors.privacy_approval_status" class="invalid-feedback">
                {{ privacyForm.errors.privacy_approval_status }}
              </div>
              <div v-if="!canModifyPrivacyStatus" class="form-text text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Status is locked after approval. You can only update notes.
              </div>
              <div v-if="!canApprovePrivacy" class="form-text text-danger">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Only Privacy Officers can modify privacy approvals.
              </div>
            </div>

            <!-- Privacy Approval Notes -->
            <div class="mb-3">
              <label class="form-label fw-bold">Privacy Approval Notes</label>
              <textarea 
                v-model="privacyForm.privacy_approval_notes" 
                class="form-control"
                rows="3"
                placeholder="Add notes about your privacy approval decision..."
                :disabled="!canApprovePrivacy"
              ></textarea>
              <div v-if="!canApprovePrivacy" class="form-text text-danger">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Only Privacy Officers can modify privacy approval notes.
              </div>
            </div>

            <div class="d-grid gap-2">
              <button 
                type="button" 
                :class="canModifyPrivacyStatus ? 'btn btn-success' : 'btn btn-info'"
                @click="savePrivacyApproval"
                :disabled="privacyForm.processing || !canApprovePrivacy"
              >
                <span v-if="privacyForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                <i :class="canModifyPrivacyStatus ? 'bi bi-check-lg me-2' : 'bi bi-pencil-square me-2'"></i>
                {{ canModifyPrivacyStatus ? 'Save Privacy Approval' : 'Update Privacy Notes' }}
              </button>
            </div>

          </div>
        </div>

        <!-- Privacy Approval Read-Only (for non-Privacy Officers) -->
        <div v-else-if="application.privacy_approval_status" class="card mb-3">
          <div class="card-header bg-light border">
            <h6 class="card-title mb-0 text-muted">
              <i class="bi bi-person-check me-2"></i>
              Privacy Approval (Read Only)
            </h6>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label fw-bold text-muted">Privacy Approval Status</label>
              <div>
                <span :class="`badge bg-${getPrivacyApprovalBadgeColor(application.privacy_approval_status)} me-2`">
                  {{ (application.privacy_approval_status || 'pending').toUpperCase() }}
                </span>
              </div>
            </div>
            <div v-if="application.privacy_approval_notes" class="mb-3">
              <label class="form-label fw-bold text-muted">Privacy Approval Notes</label>
              <div class="p-2 bg-light border rounded">
                {{ application.privacy_approval_notes }}
              </div>
            </div>
            <div v-if="application.privacy_approved_at && application.privacy_approver" class="mb-3">
              <label class="form-label fw-bold text-muted">Approved By</label>
              <div class="small">
                {{ application.privacy_approver.name }}<br>
                <span class="text-muted">{{ formatDate(application.privacy_approved_at) }}</span>
              </div>
            </div>
            <div class="alert alert-info small mb-0">
              <i class="bi bi-info-circle me-1"></i>
              Only Privacy Officers can modify privacy approvals.
            </div>
          </div>
        </div>

        <!-- Regular Status Display -->
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Application Status</h6>
            <div class="mb-3">
              <span :class="`badge bg-${getStatusBadgeColor(application.status)} me-2`">
                {{ application.status.toUpperCase() }}
              </span>
            </div>
            
            <h6 class="card-title mt-4">Approval Status</h6>
            <div class="mb-3">
              <div class="mb-2">
                <strong class="small text-muted">Security:</strong>
                <span :class="`badge bg-${getSecurityApprovalBadgeColor(application.security_approval_status)} ms-2`">
                  {{ application.security_approval_status.toUpperCase() }}
                </span>
              </div>
              <div class="mb-2">
                <strong class="small text-muted">Privacy:</strong>
                <span :class="`badge bg-${getPrivacyApprovalBadgeColor(application.privacy_approval_status)} ms-2`">
                  {{ application.privacy_approval_status.toUpperCase() }}
                </span>
              </div>
            </div>
            
            <div v-if="application.security_approved_at && application.security_approver" class="mb-3">
              <h6 class="small text-muted">Security Approved By:</h6>
              <p class="small">{{ application.security_approver.name }}<br>
              <span class="text-muted">{{ formatDate(application.security_approved_at) }}</span></p>
            </div>
            
            <div v-if="application.privacy_approved_at && application.privacy_approver" class="mb-3">
              <h6 class="small text-muted">Privacy Approved By:</h6>
              <p class="small">{{ application.privacy_approver.name }}<br>
              <span class="text-muted">{{ formatDate(application.privacy_approved_at) }}</span></p>
            </div>
            
            <div v-if="application.security_approval_notes" class="mb-3">
              <h6 class="small text-muted">Security Notes:</h6>
              <p class="small">{{ application.security_approval_notes }}</p>
            </div>
            
            <div v-if="application.privacy_approval_notes" class="mb-3">
              <h6 class="small text-muted">Privacy Notes:</h6>
              <p class="small">{{ application.privacy_approval_notes }}</p>
            </div>
            
            <div v-if="!canManageApplication">
              <h6 class="card-title mt-4">API Credentials</h6>
              <div class="mb-2">
                <label class="form-label small">API Key</label>
                <div class="input-group input-group-sm">
                  <input :value="application.api_key || 'Not generated'" class="form-control" readonly />
                  <button v-if="application.api_key" @click="copyToClipboard(application.api_key)" class="btn btn-outline-secondary" type="button">
                    <i class="bi bi-clipboard"></i>
                  </button>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label small">API Secret</label>
                <div class="input-group input-group-sm">
                  <input :value="application.api_secret ? (showSecret ? application.api_secret : '••••••••••••••••') : 'Not generated'" class="form-control" readonly />
                  <button v-if="application.api_secret" @click="showSecret = !showSecret" class="btn btn-outline-secondary" type="button">
                    <i :class="`bi bi-eye${showSecret ? '-slash' : ''}`"></i>
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
        </div>
      </div>
    </div>
  </Authenticated>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Authenticated from '../Layouts/Authenticated.vue';
import AdminMenu from '../Components/Menu.vue';

export default {
  name: 'ApplicationEdit',
  components: {
    Head,
    Link,
    Authenticated,
    AdminMenu
  },
  props: {
    application: {
      type: Object,
      required: true
    },
    auth: {
      type: Object,
      default: () => ({ user: { roles: [] } })
    },
    availableDataTables: {
      type: Object,
      default: () => ({})
    },
    apiAccessTables: {
      type: Object,
      default: () => ({})
    }
  },
  setup(props) {
    const showSecret = ref(false);

    // Permission checks
    const canApproveSecurity = computed(() => {
      const userRoles = props.auth?.user?.roles || [];
      
      // More robust role name extraction
      let roleNames = [];
      if (Array.isArray(userRoles)) {
        roleNames = userRoles.map(role => {
          if (typeof role === 'string') {
            return role;
          } else if (role && role.name) {
            return role.name;
          } else {
            return null;
          }
        }).filter(name => name !== null);
      }
      
      return roleNames.includes('Super Admin') || roleNames.includes('Security Officer');
    });

    const canApprovePrivacy = computed(() => {
      const userRoles = props.auth?.user?.roles || [];
      
      // More robust role name extraction
      let roleNames = [];
      if (Array.isArray(userRoles)) {
        roleNames = userRoles.map(role => {
          if (typeof role === 'string') {
            return role;
          } else if (role && role.name) {
            return role.name;
          } else {
            return null;
          }
        }).filter(name => name !== null);
      }
      
      return roleNames.includes('Super Admin') || roleNames.includes('Privacy Officer');
    });

    const canManageApplication = computed(() => {
      const userRoles = props.auth?.user?.roles || [];
      
      // More robust role name extraction
      let roleNames = [];
      if (Array.isArray(userRoles)) {
        roleNames = userRoles.map(role => {
          if (typeof role === 'string') {
            return role;
          } else if (role && role.name) {
            return role.name;
          } else {
            return null;
          }
        }).filter(name => name !== null);
      }
      
      return roleNames.includes('Super Admin') || roleNames.includes('Application Manager');
    });

    // Check if both approvals are complete
    const isFinallyApproved = computed(() => {
      return props.application.security_approval_status === 'approved' && 
             props.application.privacy_approval_status === 'approved';
    });

    // Check if security approval can be modified (status can only be changed if not yet approved)
    const canModifySecurityStatus = computed(() => {
      return props.application.security_approval_status !== 'approved';
    });

    // Check if privacy approval can be modified (status can only be changed if not yet approved)
    const canModifyPrivacyStatus = computed(() => {
      return props.application.privacy_approval_status !== 'approved';
    });

    // Check if approvals can be modified (legacy - keeping for backward compatibility)
    const canModifyApprovals = computed(() => {
      return !isFinallyApproved.value;
    });

    const form = useForm({
      name: props.application.name,
      description: props.application.description,
      info_url: props.application.info_url || '',
      info_label: props.application.info_label || '',
      status: props.application.status || 'active',
      contact_name: props.application.contact_name,
      contact_email: props.application.contact_email,
      contact_phone: props.application.contact_phone,
      bcsc_enabled: props.application.bcsc_enabled,
      bcsc_redirect_url: props.application.bcsc_redirect_url,
      idir_enabled: props.application.idir_enabled,
      idir_redirect_url: props.application.idir_redirect_url,
      bceid_enabled: props.application.bceid_enabled,
      bceid_redirect_url: props.application.bceid_redirect_url,
      active_alert_message: props.application.active_alert_message,
      offline_alert_message: props.application.offline_alert_message,
      offline_start_time: props.application.offline_start_time ? props.application.offline_start_time.slice(0, 16) : '',
      offline_end_time: props.application.offline_end_time ? props.application.offline_end_time.slice(0, 16) : '',
      comments: props.application.comments,
      stra_provided: props.application.stra_provided,
      pia_provided: props.application.pia_provided,
      profile_integration_ready: props.application.profile_integration_ready,
      data_permissions: [],
      // Add manager fields
      client_id: props.application.client_id || '',
      client_secret: props.application.client_secret || '',
      api_key: props.application.api_key || '',
      approval_notes: props.application.approval_notes || ''
    });

    // Security approval form
    const securityForm = useForm({
      security_approval_status: props.application.security_approval_status || 'pending',
      security_approval_notes: props.application.security_approval_notes || '',
    });

    // Privacy approval form
    const privacyForm = useForm({
      privacy_approval_status: props.application.privacy_approval_status || 'pending',
      privacy_approval_notes: props.application.privacy_approval_notes || '',
    });

        // Remove approverForm, add fields to main form

    // Data permissions management
    const dataPermissions = ref({});

    // API access permissions management
    const apiAccessPermissions = ref({});

    // Initialize data permissions
    function initializeDataPermissions() {
      const permissions = {};
      
      // Initialize all available permissions
      Object.values(props.availableDataTables).forEach(table => {
        Object.values(table.columns).forEach(column => {
          const key = `${table.name}.${column.name}`;
          permissions[key] = {
            table_name: table.name,
            column_name: column.name,
            display_name: column.default_display_name || '',
            access_level: 'none'
          };
        });
      });

      // Set existing permissions
      if (props.application.data_permissions) {
        props.application.data_permissions.forEach(permission => {
          const key = `${permission.table_name}.${permission.column_name}`;
          if (permissions[key]) {
            // Map existing permission data to access level
            if (permission.is_required) {
              permissions[key].access_level = 'required';
            } else if (permission.can_read || permission.can_write) {
              permissions[key].access_level = 'optional';
            } else {
              permissions[key].access_level = 'none';
            }
            permissions[key].display_name = permission.display_name || permissions[key].display_name;
          }
        });
      }

      dataPermissions.value = permissions;
    }

    // Initialize API access permissions
    function initializeApiAccessPermissions() {
      const permissions = {};
      
      // Initialize all available API access permissions
      Object.values(props.apiAccessTables).forEach(table => {
        Object.values(table.columns).forEach(column => {
          const key = `${table.name}.${column.name}`;
          permissions[key] = {
            table_name: table.name,
            column_name: column.name,
            display_name: column.default_display_name || '',
            access_level: 'none'
          };
        });
      });

      // Set existing permissions (same data source as data permissions)
      if (props.application.data_permissions) {
        props.application.data_permissions.forEach(permission => {
          const key = `${permission.table_name}.${permission.column_name}`;
          if (permissions[key]) {
            // Map existing permission data to access level
            if (permission.can_read && permission.can_write) {
              permissions[key].access_level = 'read_write';
            } else if (permission.can_read) {
              permissions[key].access_level = 'read';
            } else if (permission.can_write) {
              permissions[key].access_level = 'write';
            } else {
              permissions[key].access_level = 'none';
            }
            permissions[key].display_name = permission.display_name || permissions[key].display_name;
          }
        });
      }

      apiAccessPermissions.value = permissions;
    }

    // Get count of permissions for a table (required + optional)
    function getTablePermissionCount(tableName) {
      return Object.values(dataPermissions.value)
        .filter(permission => 
          permission.table_name === tableName && 
          permission.access_level !== 'none'
        ).length;
    }

    // Get count of API access permissions for a table (required + optional)
    function getApiTablePermissionCount(tableName) {
      return Object.values(apiAccessPermissions.value)
        .filter(permission => 
          permission.table_name === tableName && 
          permission.access_level !== 'none'
        ).length;
    }

    // Select all columns for a table as optional
    function selectAllTableColumns(tableName) {
      Object.keys(dataPermissions.value).forEach(key => {
        if (dataPermissions.value[key].table_name === tableName) {
          dataPermissions.value[key].access_level = 'optional';
        }
      });
    }

    // Select all API access columns for a table as read access
    function selectAllApiTableColumns(tableName) {
      Object.keys(apiAccessPermissions.value).forEach(key => {
        if (apiAccessPermissions.value[key].table_name === tableName) {
          apiAccessPermissions.value[key].access_level = 'read';
        }
      });
    }

    // Clear all columns for a table
    function clearAllTableColumns(tableName) {
      Object.keys(dataPermissions.value).forEach(key => {
        if (dataPermissions.value[key].table_name === tableName) {
          dataPermissions.value[key].access_level = 'none';
        }
      });
    }

    // Clear all API access columns for a table
    function clearAllApiTableColumns(tableName) {
      Object.keys(apiAccessPermissions.value).forEach(key => {
        if (apiAccessPermissions.value[key].table_name === tableName) {
          apiAccessPermissions.value[key].access_level = 'none';
        }
      });
    }

    // Set all API access columns for a table to write access
    function setAllApiTableColumnsWrite(tableName) {
      Object.keys(apiAccessPermissions.value).forEach(key => {
        if (apiAccessPermissions.value[key].table_name === tableName) {
          apiAccessPermissions.value[key].access_level = 'write';
        }
      });
    }

    // Set all API access columns for a table to read and write access
    function setAllApiTableColumnsReadWrite(tableName) {
      Object.keys(apiAccessPermissions.value).forEach(key => {
        if (apiAccessPermissions.value[key].table_name === tableName) {
          apiAccessPermissions.value[key].access_level = 'read_write';
        }
      });
    }

    // Initialize data permissions on component mount
    initializeDataPermissions();
    initializeApiAccessPermissions();

    function submit() {
      // Prepare data permissions for submission (from Data Access Permissions section)
      const dataPermissionsArray = Object.values(dataPermissions.value)
        .filter(permission => permission.access_level !== 'none')
        .map(permission => ({
          table_name: permission.table_name,
          column_name: permission.column_name,
          display_name: permission.display_name,
          can_read: permission.access_level === 'required' || permission.access_level === 'optional',
          can_write: false, // Data permissions are typically read-only
          is_required: permission.access_level === 'required'
        }));

      // Prepare API access permissions for submission (from API Access Permissions section)
      const apiPermissionsArray = Object.values(apiAccessPermissions.value)
        .filter(permission => permission.access_level !== 'none')
        .map(permission => ({
          table_name: permission.table_name,
          column_name: permission.column_name,
          display_name: permission.display_name,
          can_read: permission.access_level === 'read' || permission.access_level === 'read_write',
          can_write: permission.access_level === 'write' || permission.access_level === 'read_write',
          // Don't include is_required for API permissions - this distinguishes them from data permissions
        }));

      // Combine both permission types
      const allPermissions = [...dataPermissionsArray, ...apiPermissionsArray];
      
      form.data_permissions = allPermissions;
      form.put(`/admin/applications/${props.application.guid}`, {
        onSuccess: () => {
          // Flash message will be handled by the backend redirect
          console.log('Application updated successfully');
        },
        onError: (errors) => {
          // Handle validation errors - they'll be displayed in the form
          console.error('Validation errors:', errors);
          
          // Show a general error message if there are server errors
          if (errors.message) {
            alert('Error: ' + errors.message);
          } else if (Object.keys(errors).length > 0) {
            alert('Please check the form for validation errors and try again.');
          }
        },
        onFinish: () => {
          // Optional: Could add loading state management here
          console.log('Form submission finished');
        }
      });
    }

    function saveSecurityApproval() {
      // Check if user has permission to approve security
      if (!canApproveSecurity.value) {
        alert('You do not have permission to modify security approvals. Only Security Officers can perform this action.');
        return;
      }
      
      securityForm.patch(`/admin/applications/${props.application.guid}/security-approval`, {
        onSuccess: () => {
          // Handle success
        },
        onError: () => {
          // Handle error
        }
      });
    }

    function savePrivacyApproval() {
      // Check if user has permission to approve privacy
      if (!canApprovePrivacy.value) {
        alert('You do not have permission to modify privacy approvals. Only Privacy Officers can perform this action.');
        return;
      }
      
      privacyForm.patch(`/admin/applications/${props.application.guid}/privacy-approval`, {
        onSuccess: () => {
          // Handle success
        },
        onError: () => {
          // Handle error
        }
      });
    }

    // Generate functions for API credentials
    const SECURE_CHARSET = 'abcdefghijklmnopqrstuvwxyz0123456789';

    function generateSecureRandomString(length) {
      const cryptoObj = (typeof window !== 'undefined' && window.crypto) || (typeof self !== 'undefined' && self.crypto) || (typeof globalThis !== 'undefined' && globalThis.crypto);
      if (!cryptoObj || !cryptoObj.getRandomValues) {
        throw new Error('Secure random number generator not available.');
      }

      const randomValues = new Uint32Array(length);
      cryptoObj.getRandomValues(randomValues);

      let result = '';
      const charsetLength = SECURE_CHARSET.length;
      for (let i = 0; i < length; i++) {
        const index = randomValues[i] % charsetLength;
        result += SECURE_CHARSET.charAt(index);
      }
      return result;
    }

    function generateClientId() {
      form.client_id = 'client_' + generateSecureRandomString(16) + Date.now().toString(36);
    }

    function generateClientSecret() {
      form.client_secret = 'secret_' + generateSecureRandomString(32) + Date.now().toString(36);
    }

    function generateApiKey() {
      form.api_key = 'key_' + generateSecureRandomString(24) + Date.now().toString(36);
    }

    function getSecurityApprovalBadgeColor(status) {
      const colors = {
        approved: 'success',
        rejected: 'danger',
        pending: 'warning'
      };
      return colors[status] || 'secondary';
    }

    function getPrivacyApprovalBadgeColor(status) {
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

    function formatDate(dateString) {
      return new Date(dateString).toLocaleDateString();
    }

    function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(() => {
        // You could add a toast notification here
        console.log('Copied to clipboard');
      });
    }

    return {
      showSecret,
      canApproveSecurity,
      canApprovePrivacy,
      canManageApplication,
      isFinallyApproved,
      canModifyApprovals,
      canModifySecurityStatus,
      canModifyPrivacyStatus,
      form,
      securityForm,
      privacyForm,
      dataPermissions,
      apiAccessPermissions,
      submit,
      saveSecurityApproval,
      savePrivacyApproval,
      generateClientId,
      generateClientSecret,
      generateApiKey,
      getSecurityApprovalBadgeColor,
      getPrivacyApprovalBadgeColor,
      getStatusBadgeColor,
      formatDate,
      copyToClipboard,
      getTablePermissionCount,
      getApiTablePermissionCount,
      selectAllTableColumns,
      selectAllApiTableColumns,
      setAllApiTableColumnsWrite,
      setAllApiTableColumnsReadWrite,
      clearAllTableColumns,
      clearAllApiTableColumns
    };
  }
}
</script>

<style scoped>
hr {
  margin: 3rem 0;
}

.font-monospace {
  font-family: 'Courier New', monospace;
}

.bg-purple {
  background-color: #6f42c1 !important;
}

.btn-purple {
  background-color: #6f42c1;
  border-color: #6f42c1;
}

.btn-purple:hover {
  background-color: #5a359a;
  border-color: #5a359a;
}

.text-white {
  color: white !important;
}
</style>
