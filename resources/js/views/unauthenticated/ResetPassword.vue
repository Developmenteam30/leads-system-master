<template>
    <Suspense>
        <MDBCard class="mt-3 m-auto login-box">
            <MDBCardImg
                src="/storage/images/logo-main.svg"
                top
                :alt="systemName"
            />
            <MDBCardBody class="p-2 p-sm-4">
                <MDBCardText>
                    <form>
                        <h2 class="text-center">Password Reset</h2>
                        <template v-if="isReset">
                            <MDBAlert color="success" static>Your password has been reset. You may now login with the new password.</MDBAlert>

                            <!-- 2 column grid layout for inline styling -->
                            <div class="row mt-4">
                                <div class="col">
                                    <router-link :to="{ name: 'login'}">Return to Login page</router-link>
                                </div>
                            </div>
                        </template>
                        <template v-else-if="isValidated">
                            <p>Please choose a new password that is between 8 and 64 characters.</p>
                            <MDBInput label="Password" class="my-4" v-model="password" type="password" autocomplete="new-password"/>

                            <MDBInput label="Password Confirmation" class="my-4" v-model="passwordConfirmation" type="password" autocomplete="new-password"/>

                            <!-- Submit button -->
                            <div class="d-grid gap-2">
                                <MDBBtn color="primary" :disabled="isLoading" @click.prevent="submit">
                                    <MDBSpinner tag="span" size="sm" v-if="isLoading"/>
                                    Save
                                </MDBBtn>
                            </div>

                            <!-- 2 column grid layout for inline styling -->
                            <div class="row mt-4">
                                <div class="col">
                                    <router-link :to="{ name: 'login'}">Already Have an Account?</router-link>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <MDBAlert :color="errorColor" static v-if="errorMessage">{{ errorMessage }}</MDBAlert>

                            <WaitingSpinner v-else></WaitingSpinner>

                            <!-- 2 column grid layout for inline styling -->
                            <div class="row mt-4">
                                <div class="col">
                                    <router-link :to="{ name: 'forgot-password'}">Forgot Your Password?</router-link>
                                </div>
                                <div class="col">
                                    <router-link :to="{ name: 'login'}">Already Have an Account?</router-link>
                                </div>
                            </div>
                        </template>
                    </form>
                </MDBCardText>
            </MDBCardBody>
        </MDBCard>
    </Suspense>
</template>

<script setup>

import apiClient from "../../http";
import {authStore} from "@/store/auth-store";
import {
    MDBAlert,
    MDBBtn,
    MDBCard,
    MDBCardBody,
    MDBCardImg,
    MDBCardText,
    MDBInput,
    MDBSpinner,
} from "mdb-vue-ui-kit";
import {toast} from "vue3-toastify";
import {computed, onMounted, ref} from "vue";
import {onBeforeRouteUpdate, useRoute} from "vue-router";
import WaitingSpinner from "@/components/WaitingSpinner.vue";

const isLoading = ref(false);
const isValidated = ref(false);
const isReset = ref(false);
const token = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const route = useRoute();
const errorMessage = ref('');
const errorColor = ref('danger');

onMounted(() => {
    token.value = route.query.token;
    validate();
});

onBeforeRouteUpdate((to, from, next) => {
    if (authStore.isLoggedIn()) {
        next({name: 'dashboard'});
    } else {
        next();
    }
});

const validate = () => {
    isValidated.value = false;
    isLoading.value = true;
    errorMessage.value = '';

    apiClient.post('forgot/validate', {
            token: token.value,
        }
    ).then(({data}) => {
        isValidated.value = true;
    }).catch((error) => {
        errorMessage.value = error.response?.data.message || 'Unknown error';
        errorColor.value = 'danger';
    }).finally(() => {
        isLoading.value = false;
    });
};

const submit = () => {
    isLoading.value = true;
    isReset.value = false;
    errorMessage.value = '';

    apiClient.post('forgot/reset', {
            token: token.value,
            password: password.value,
            passwordConfirmation: passwordConfirmation.value,
        }
    ).then(({data}) => {
        isReset.value = true;
    }).catch((error) => {
    }).finally(() => {
        isLoading.value = false;
    });
};

const systemName = computed(() =>
    import.meta.env.VITE_APP_NAME
);
</script>

<style scoped lang="scss">
.login-box {
    max-width: 500px;
}
</style>
