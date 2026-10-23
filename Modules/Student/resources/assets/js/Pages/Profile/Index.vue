<template>
  <AuthenticatedLayout>
    <div class="container-fluid py-4">
      <div class="row">
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="text-muted small">
              <h1 class="h3 mb-0">My Profile</h1>
              <div v-if="individual"><strong>Last Updated:</strong> {{ formatDate(individual.updated_at) }}</div>
            </div>
            <div class="btn-group">
              <Link
                href="/student/profile/edit"
                class="btn btn-primary"
                v-if="individual"
              >
                <i class="bi bi-pencil-square me-2"></i>
                Edit Profile
              </Link>
              <Link
                href="/student/profile/create"
                class="btn btn-success"
                v-else
              >
                <i class="bi bi-plus-lg me-2"></i>
                Create Profile
              </Link>
            </div>
          </div>

          <!-- Profile Status Alert -->
          <div class="alert alert-warning mb-4" v-if="!individual">
            <div class="d-flex align-items-center">
              <i class="bi bi-exclamation-triangle me-2"></i>
              <div>
                <h5 class="alert-heading mb-1">Profile Not Found</h5>
                <p class="mb-0">You haven't created your profile yet. Please create your profile to access all features.</p>
              </div>
            </div>
          </div>

          <!-- Profile Content -->
          <div v-if="individual">
            <div class="row">
              
              <!-- Personal Information -->
              <div class="col-lg-6 mb-4">
                <div class="card h-100">
                  <div class="card-header">
                    <h5 class="mb-0">Personal Information</h5>
                  </div>
                  <div class="card-body">
                    <div class="row mb-3">
                      <div class="col-sm-6">
                        <strong>First Name:</strong>
                        <div>{{ individual.first_name || 'Not provided' }}</div>
                      </div>
                      <div class="col-sm-6">
                        <strong>Last Name:</strong>
                        <div>{{ individual.last_name || 'Not provided' }}</div>
                      </div>
                    </div>
                    <div class="row mb-3" v-if="individual.middle_name">
                      <div class="col-sm-6">
                        <strong>Middle Name:</strong>
                        <div>{{ individual.middle_name }}</div>
                      </div>
                    </div>
                    <div class="row mb-3" v-if="individual.preferred_name">
                      <div class="col-sm-6">
                        <strong>Preferred Name:</strong>
                        <div>{{ individual.preferred_name }}</div>
                      </div>
                    </div>
                    <div class="row mb-3" v-if="individual.date_of_birth">
                      <div class="col-sm-6">
                        <strong>Date of Birth:</strong>
                        <div>{{ formatDate(individual.date_of_birth) }}</div>
                      </div>
                    </div>
                    <div class="row mb-3" v-if="individual.gender">
                      <div class="col-sm-6">
                        <strong>Gender:</strong>
                        <div>{{ individual.gender }}</div>
                      </div>
                    </div>
                    <div class="row mb-3" v-if="individual.preferred_pronouns">
                      <div class="col-sm-6">
                        <strong>Preferred Pronouns:</strong>
                        <div>{{ individual.preferred_pronouns }}</div>
                      </div>
                    </div>
                    <div class="row mb-3" v-if="individual.disability_status === 'yes'">
                      <div class="col-sm-12">
                        <strong>Accessibility Needs:</strong>
                        <div class="mb-2">
                          <span class="badge bg-info">Has accessibility needs</span>
                        </div>
                        <div v-if="individual.accommodation_needs">{{ individual.accommodation_needs }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Identity Information -->
              <div class="col-lg-6 mb-4">
                <div class="card h-100">
                  <div class="card-header">
                    <h5 class="mb-0">Identity Information</h5>
                  </div>
                  <div class="card-body">
                    <div class="row mb-3" v-if="individual.social_insurance_number">
                      <div class="col-sm-6">
                        <strong>Social Insurance Number:</strong>
                        <div>{{ individual.social_insurance_number }}</div>
                      </div>
                    </div>
                    <div class="row mb-3" v-if="individual.government_issued_id">
                      <div class="col-sm-6">
                        <strong>Government Issued ID:</strong>
                        <div>{{ individual.government_issued_id }}</div>
                      </div>
                    </div>
                    <div class="row mb-3" v-if="individual.identity?.citizenship_status">
                      <div class="col-sm-6">
                        <strong>Citizenship Status:</strong>
                        <div>{{ individual.identity.citizenship_status }}</div>
                      </div>
                    </div>
                    <div class="row mb-3" v-if="individual.identity?.country_of_birth">
                      <div class="col-sm-6">
                        <strong>Country of Birth:</strong>
                        <div>{{ individual.identity.country_of_birth }}</div>
                      </div>
                    </div>
                    <div class="row mb-3" v-if="individual.identity?.language_spoken_at_home">
                      <div class="col-sm-6">
                        <strong>Language Spoken at Home:</strong>
                        <div>{{ individual.identity.language_spoken_at_home }}</div>
                      </div>
                    </div>
                    <div class="row mb-3" v-if="individual.identity?.racial_identity">
                      <div class="col-sm-6">
                        <strong>Racial Identity:</strong>
                        <div>{{ individual.identity.racial_identity }}</div>
                      </div>
                    </div>
                    <div class="row mb-3" v-if="individual.identity?.indigenous_status === 'yes'">
                      <div class="col-sm-12">
                        <strong>Indigenous Identity:</strong>
                        <div class="mb-2">
                          <span class="badge bg-success">Indigenous</span>
                        </div>
                        <div v-if="individual.identity.indigenous_group" class="mb-1">
                          <strong>Group:</strong> {{ individual.identity.indigenous_group }}
                        </div>
                        <div v-if="individual.identity.band_affiliation" class="mb-1">
                          <strong>Band/Nation:</strong> {{ individual.identity.band_affiliation }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Contact Information -->
              <div class="col-lg-6 mb-4">
                <div class="card h-100">
                  <div class="card-header">
                    <h5 class="mb-0">Contact Information</h5>
                  </div>
                  <div class="card-body">
                    <div class="mb-3">
                      <strong>Email:</strong>
                      <div class="d-flex align-items-center">
                        <span>{{ individual.email_address || 'Not provided' }}</span>
                        <span v-if="individual.email_verified_at" class="badge bg-success ms-2">
                          <i class="bi bi-check-circle me-1"></i>Verified
                        </span>
                        <span v-else class="badge bg-warning ms-2">
                          <i class="bi bi-exclamation-circle me-1"></i>Unverified
                        </span>
                      </div>
                    </div>
                    <div class="mb-3" v-if="individual.phone_number">
                      <strong>Phone:</strong>
                      <div>{{ individual.phone_number }}</div>
                    </div>
                    <div class="mb-3" v-if="individual.alternate_phone_number">
                      <strong>Alternate Phone:</strong>
                      <div>{{ individual.alternate_phone_number }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Current Address -->
              <div class="col-lg-6 mb-4" v-if="hasCurrentAddress">
                <div class="card h-100">
                  <div class="card-header">
                    <h5 class="mb-0">Current Address</h5>
                  </div>
                  <div class="card-body">
                    <div class="mb-3" v-if="individual.current_address?.street_address">
                      <strong>Street Address:</strong>
                      <div>{{ individual.current_address.street_address }}</div>
                    </div>
                    <div class="mb-3" v-if="individual.current_address?.apartment_unit">
                      <strong>Apartment/Unit:</strong>
                      <div>{{ individual.current_address.apartment_unit }}</div>
                    </div>
                    <div class="row mb-3">
                      <div class="col-sm-6" v-if="individual.current_address?.city">
                        <strong>City:</strong>
                        <div>{{ individual.current_address.city }}</div>
                      </div>
                      <div class="col-sm-6" v-if="individual.current_address?.province_state">
                        <strong>Province/State:</strong>
                        <div>{{ individual.current_address.province_state }}</div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <div class="col-sm-6" v-if="individual.current_address?.postal_code">
                        <strong>Postal Code:</strong>
                        <div>{{ individual.current_address.postal_code }}</div>
                      </div>
                      <div class="col-sm-6" v-if="individual.current_address?.country">
                        <strong>Country:</strong>
                        <div>{{ individual.current_address.country }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Mailing Address -->
              <div class="col-lg-6 mb-4" v-if="hasMailingAddress">
                <div class="card h-100">
                  <div class="card-header">
                    <h5 class="mb-0">Mailing Address</h5>
                  </div>
                  <div class="card-body">
                    <div class="mb-3" v-if="individual.mailing_street_address">
                      <strong>Street Address:</strong>
                      <div>{{ individual.mailing_street_address }}</div>
                    </div>
                    <div class="mb-3" v-if="individual.mailing_apartment_unit">
                      <strong>Apartment/Unit:</strong>
                      <div>{{ individual.mailing_apartment_unit }}</div>
                    </div>
                    <div class="row mb-3">
                      <div class="col-sm-6" v-if="individual.mailing_city">
                        <strong>City:</strong>
                        <div>{{ individual.mailing_city }}</div>
                      </div>
                      <div class="col-sm-6" v-if="individual.mailing_province_state">
                        <strong>Province/State:</strong>
                        <div>{{ individual.mailing_province_state }}</div>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <div class="col-sm-6" v-if="individual.mailing_postal_code">
                        <strong>Postal Code:</strong>
                        <div>{{ individual.mailing_postal_code }}</div>
                      </div>
                      <div class="col-sm-6" v-if="individual.mailing_country">
                        <strong>Country:</strong>
                        <div>{{ individual.mailing_country }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Employment Information -->
              <div class="col-lg-6 mb-4" v-if="hasEmploymentInfo">
                <div class="card h-100">
                  <div class="card-header">
                    <h5 class="mb-0">Employment Information</h5>
                  </div>
                  <div class="card-body">
                    <div class="mb-3" v-if="individual.current_employment?.employment_status">
                      <strong>Employment Status:</strong>
                      <div>{{ individual.current_employment.employment_status }}</div>
                    </div>
                    <div class="mb-3" v-if="individual.current_employment?.job_title">
                      <strong>Job Title:</strong>
                      <div>{{ individual.current_employment.job_title }}</div>
                    </div>
                    <div class="mb-3" v-if="individual.current_employment?.employer_name">
                      <strong>Employer:</strong>
                      <div>{{ individual.current_employment.employer_name }}</div>
                    </div>
                    <div class="mb-3" v-if="individual.current_employment?.employer_industry">
                      <strong>Industry:</strong>
                      <div>{{ individual.current_employment.employer_industry }}</div>
                    </div>
                    <div class="row mb-3" v-if="individual.current_employment?.work_hours_per_week || individual.current_employment?.monthly_income">
                      <div class="col-sm-6" v-if="individual.current_employment?.work_hours_per_week">
                        <strong>Hours per Week:</strong>
                        <div>{{ individual.current_employment.work_hours_per_week }}</div>
                      </div>
                      <div class="col-sm-6" v-if="individual.current_employment?.monthly_income">
                        <strong>Monthly Income:</strong>
                        <div>${{ individual.current_employment.monthly_income }}</div>
                      </div>
                    </div>
                    <div class="mb-3" v-if="individual.current_employment?.career_interest_area">
                      <strong>Career Interest Area:</strong>
                      <div>{{ individual.current_employment.career_interest_area }}</div>
                    </div>
                    <div class="mb-3" v-if="individual.current_employment?.desired_job_title">
                      <strong>Desired Job Title:</strong>
                      <div>{{ individual.current_employment.desired_job_title }}</div>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script>
import { Link } from '@inertiajs/vue3'
import { format } from 'date-fns'
import AuthenticatedLayout from '../../Layouts/Authenticated.vue'

export default {
  name: 'StudentProfileIndex',
  components: {
    Link,
    AuthenticatedLayout
  },
  props: {
    individual: {
      type: Object,
      default: null
    }
  },
  computed: {
    hasCurrentAddress() {
      return this.individual?.current_address?.street_address ||
             this.individual?.current_address?.city ||
             this.individual?.current_address?.province_state ||
             this.individual?.current_address?.postal_code ||
             this.individual?.current_address?.country
    },
    hasMailingAddress() {
      return this.individual?.use_different_mailing_address && (
        this.individual?.mailing_street_address ||
        this.individual?.mailing_city ||
        this.individual?.mailing_province_state ||
        this.individual?.mailing_postal_code ||
        this.individual?.mailing_country
      )
    },
    hasEmploymentInfo() {
      return this.individual?.current_employment?.employment_status ||
             this.individual?.current_employment?.job_title ||
             this.individual?.current_employment?.employer_name ||
             this.individual?.current_employment?.employer_industry ||
             this.individual?.current_employment?.career_interest_area ||
             this.individual?.current_employment?.desired_job_title
    }
  },
  methods: {
    formatDate(date) {
      if (!date) return 'N/A'
      return format(new Date(date), 'MMM d, yyyy')
    }
  }
}
</script>
