<script>

export default {
    name: 'FormSubmitAlert',
    components: {
    },
    props: {
        formState: Boolean|String,
        successMsg: {
            type: [String, null],
            default: null,
        },
        failMsg: {
            type: [String, null],
            default: null,
        },
    },
    data() {
        return {
            showHide: false,
            successMessage: '',
            failMessage: '',
        }
    },
    mounted() {
        this.successMessage = this.successMsg == null ? 'Form was submitted successfully.' : this.successMsg;
        this.failMessage = this.failMsg == null ? 'There was an error submitting this form.' : this.failMsg;
    },
    watch: {
        formState: function (newVal, oldVal) {
            if (newVal != null) {
                this.showHide = true;
                setTimeout(() => {
                    this.showHide = false;
                }, 2500);
            }
        },
        failMsg: function (newVal, oldVal) {
            this.failMessage = newVal == null ? 'There was an error submitting this form.' : newVal;
        }
    },

}
</script>

<template>
    <div v-if="showHide">
        <!-- Success Alert -->
        <div v-if="formState" class="fixed bottom-4 right-4 z-50">
            <div class="bg-green-50 border border-green-200 rounded-md p-4 shadow-lg max-w-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-green-800">
                            {{ successMessage }}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button 
                            @click="showHide = false"
                            class="bg-green-50 rounded-md inline-flex text-green-400 hover:text-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Error Alert -->
        <div v-if="formState === false" class="fixed bottom-4 right-4 z-50">
            <div class="bg-red-50 border border-red-200 rounded-md p-4 shadow-lg max-w-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-red-800">
                            {{ failMessage }}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button 
                            @click="showHide = false"
                            class="bg-red-50 rounded-md inline-flex text-red-400 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
