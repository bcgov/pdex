<template>
  <footer class="bcds-footer">
    <div v-if="!hideAcknowledgement" class="bcds-footer--acknowledgement">
      <div class="bcds-footer--acknowledgement-text">
        <slot name="acknowledgement">
          <p>
            Your personal information is collected, used and disclosed by the Ministry 
            of Post-Secondary Education and Future Skills under the Freedom of 
            Information and Protection of Privacy Act (FOIPPA) sections 26(c) and 
            26(e). The information you provide will be shared with the program 
            administration you choose to apply to through this portal. It will be used 
            for the purposes of confirming your identity and program eligibility, 
            administering financial assistance, and supporting program planning and 
            evaluation. If you have any questions about the collection of this 
            information, please contact: Ministry Privacy Officer, 
            PSFS.InformationPrivacy@gov.bc.ca
          </p>
          <br/>
          <p>
            The B.C. Public Service acknowledges the territories of First Nations
            around B.C. and is grateful to carry out our work on these lands. We
            acknowledge the rights, interests, priorities, and concerns of all
            Indigenous Peoples — First Nations, Métis, and Inuit — respecting and
            acknowledging their distinct cultures, histories, rights, laws, and
            governments.
          </p>
        </slot>
      </div>
    </div>
    <div class="bcds-footer--container">
      <div class="bcds-footer--container-content">
        <div
          v-if="!hideLogoAndLinks"
          :class="`bcds-footer--logo-links ${$slots.default ? 'vertical' : 'horizontal'}`"
        >
          <slot>
            <div class="bcds-footer--logo">
              <slot name="logo">
                <SvgBcLogo id="bcgov-logo-footer" />
              </slot>
              <slot name="contact">
                <p>
                  We can help in over 220 languages and through other accessible options.
                  <a href="https://www2.gov.bc.ca/gov/content?id=6A77C17D0CCB48F897F8598CCC019111">
                    Call, email or text us
                  </a>
                  , or
                  <a href="https://www2.gov.bc.ca/gov/content?id=FBC4210F6BC047A5884198F543C97D53">
                    find a service centre
                  </a>
                </p>
              </slot>
            </div>
            <slot name="links">
              <FooterLinks
                title="More Info"
                :links="defaultLinks"
              />
            </slot>
          </slot>
        </div>
        <hr v-if="!hideLogoAndLinks && !hideCopyright" />
        <p v-if="!hideCopyright" class="bcds-footer--copyright">
          {{ copyright || defaultCopyright }}
        </p>
      </div>
    </div>
  </footer>
</template>

<script>
import SvgBcLogo from '../SvgBcLogo/SvgBcLogo.vue'
import FooterLinks from './FooterLinks.vue'

export default {
  name: 'Footer',
  components: {
    SvgBcLogo,
    FooterLinks
  },
  props: {
    copyright: {
      type: String,
      default: null
    },
    hideAcknowledgement: {
      type: Boolean,
      default: false
    },
    hideLogoAndLinks: {
      type: Boolean,
      default: false
    },
    hideCopyright: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    defaultCopyright() {
      return `© ${new Date().getUTCFullYear()} Government of British Columbia.`
    },
    defaultLinks() {
      return [
        { href: 'https://www2.gov.bc.ca/gov/content/home', text: 'Home' },
        { href: 'https://www2.gov.bc.ca/gov/content?id=3C4F47288DFB454987435AB5EFEFBB7F', text: 'About gov.bc.ca' },
        { href: 'https://www2.gov.bc.ca/gov/content?id=79F93E018712422FBC8E674A67A70535', text: 'Disclaimer' },
        { href: 'https://www2.gov.bc.ca/gov/content?id=9E890E16955E4FF4BF3B0E07B4722932', text: 'Privacy' },
        { href: 'https://www2.gov.bc.ca/gov/content?id=E08E79740F9C41B9B0C484685CC5E412', text: 'Accessibility' },
        { href: 'https://www2.gov.bc.ca/gov/content?id=1AAACC9C65754E4D89A118B875E0FBDA', text: 'Copyright' },
        { href: 'https://www2.gov.bc.ca/gov/content?id=6A77C17D0CCB48F897F8598CCC019111', text: 'Contact us' }
      ]
    }
  }
}
</script>

<style scoped>
@import './Footer.css';
</style>