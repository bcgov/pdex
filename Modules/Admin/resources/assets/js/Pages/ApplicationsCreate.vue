<template>
  <Authenticated>
    <Head title="Create Application" />
    <div class="container-fluid px-4 py-6">
      <div class="row">

        
        <!-- Main Content Column -->
        <div class="col-lg-12">
          <div class="card shadow-sm">
            <div class="card-header">Add New Application</div>
            <div class="card-body">
    
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
                <div class="row">
                  <div class="col-12">
                    <div class="mb-3">
                      <label class="form-label">Initial Status</label>
                      <select v-model="form.status" :class="{'is-invalid': form.errors.status}" class="form-select">
                        <option value="inactive">Inactive (Recommended)</option>
                        <option value="active">Active</option>
                        <option value="offline">Offline</option>
                      </select>
                      <div v-if="form.errors.status" class="invalid-feedback">{{ form.errors.status }}</div>
                      <div class="form-text">
                        <small class="text-muted">New applications typically start as "Inactive" until approvals are complete.</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

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

              <!-- Compliance -->
              <div class="mb-4">
                <h5 class="card-title">Compliance Documentation</h5>
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

              <!-- Comments -->
              <div class="mb-4">
                <h5 class="card-title">Additional Comments</h5>
                <textarea v-model="form.comments" :class="{'is-invalid': form.errors.comments}" class="form-control" rows="4" placeholder="Any additional notes or comments about this application..."></textarea>
                <div v-if="form.errors.comments" class="invalid-feedback">{{ form.errors.comments }}</div>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" :disabled="form.processing" class="btn btn-primary">
                  <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                  Create Application
                </button>
                <Link href="/admin/applications" class="btn btn-outline-secondary">Cancel</Link>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <div class="col-lg-4">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Important Notes</h6>
            <ul class="small text-muted">
              <li>New applications start in <strong>pending</strong> approval status</li>
              <li>Applications must be approved before they can be activated</li>
              <li>API credentials will be automatically generated</li>
              <li>At least one identity provider must be enabled</li>
              <li>Redirect URLs are required for enabled identity providers</li>
              <li>STRA and PIA documentation are recommended for compliance</li>
            </ul>
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
import Authenticated from '../Layouts/Authenticated.vue';
import AdminMenu from '../Components/Menu.vue';

export default {
  name: 'ApplicationCreate',
  components: {
    Head,
    Link,
    Authenticated,
    AdminMenu
  },
  setup() {
    const form = useForm({
      name: '',
      description: '',
      info_url: '',
      info_label: '',
      status: 'inactive', // Default to inactive
      contact_name: '',
      contact_email: '',
      contact_phone: '',
      bcsc_enabled: false,
      bcsc_redirect_url: '',
      idir_enabled: false,
      idir_redirect_url: '',
      bceid_enabled: false,
      bceid_redirect_url: '',
      comments: '',
      stra_provided: false,
      pia_provided: false,
      profile_integration_ready: false,
    });

    function submit() {
      form.post('/admin/applications');
    }

    return {
      form,
      submit
    };
  }
}
</script>
