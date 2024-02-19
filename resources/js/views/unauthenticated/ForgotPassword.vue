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
                        <h2 class="text-center">Forgot Password</h2>

                        <template v-if="isSuccess">
                            <MDBAlert color="info" static>If an account exists with that email address, an email will be sent momentarily.</MDBAlert>
                        </template>
                        <template v-else>

                            <p>Please enter your email address below. If an account is found with that email address, we'll send a password reset link via email.</p>
                            <MDBInput label="Email" class="my-4" v-model="email" type="email" autocomplete="email"/>

                            <!-- Submit button -->
                            <div class="d-grid gap-2">
                                <MDBBtn color="primary" :disabled="isLoading" @click.prevent="login">
                                    <MDBSpinner tag="span" size="sm" v-if="isLoading"/>
                                    Reset Password
                                </MDBBtn>
                            </div>
                        </template>
                        <!-- 2 column grid layout for inline styling -->
                        <div class="row mt-4">
                            <div class="col">
                                <router-link :to="{ name: 'login'}">Already Have an Account?</router-link>
                            </div>
                        </div>
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
import {computed, ref} from "vue";
import {onBeforeRouteUpdate} from "vue-router";

const isLoading = ref(false);
const isSuccess = ref(false);
const email = ref('');

onBeforeRouteUpdate((to, from, next) => {
    if (authStore.isLoggedIn()) {
        next({name: 'dashboard'});
    } else {
        next();
    }
});

const login = () => {
    isLoading.value = true;
    isSuccess.value = false;

    apiClient.post('forgot', {
            email: email.value,
        }
    ).then(({data}) => {
        isSuccess.value = true;
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
