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
                        <h2 class="text-center">Login</h2>
                        <MDBInput label="Username" class="my-4" v-model="username" type="text" autocomplete="username"/>

                        <MDBInput label="Password" class="my-4" v-model="password" type="password" v-on:keyup.enter="login" autocomplete="current-password"/>

                        <SmartSelectAjax v-if="isDevelopment" label="Access Role" class="my-4" ajax-url="options/access_roles" v-model:selected="access_role_id"/>

                        <MDBAlert color="danger" static v-if="error">{{ error }}</MDBAlert>

                        <!-- Submit button -->
                        <div class="d-grid gap-2">
                            <MDBBtn color="primary" :disabled="isLoading" @click.prevent="login">
                                <MDBSpinner tag="span" size="sm" v-if="isLoading"/>
                                Sign In
                            </MDBBtn>
                        </div>

                        <!-- 2 column grid layout for inline styling -->
                        <div class="row mt-4">
                            <div class="col">
                                <router-link :to="{ name: 'forgot-password'}">Forgot Your Password?</router-link>
                            </div>
                        </div>
                    </form>
                </MDBCardText>
            </MDBCardBody>
        </MDBCard>
    </Suspense>
</template>

<script>

import apiClient from "../../http";
import {authStore} from "@/store/auth-store";
import {
    MDBAlert,
    MDBBtn,
    MDBCard,
    MDBCardBody,
    MDBCardImg,
    MDBCardText,
    MDBCardTitle,
    MDBCheckbox,
    MDBInput,
    MDBSpinner,
} from "mdb-vue-ui-kit";
import SmartSelectAjax from "@/components/SmartSelectAjax.vue";

export default {
    name: "Login",

    components: {
        MDBAlert,
        MDBBtn,
        MDBCard,
        MDBCardBody,
        MDBCardImg,
        MDBCardTitle,
        MDBCardText,
        MDBCheckbox,
        MDBInput,
        MDBSpinner,
        SmartSelectAjax,
    },

    data() {
        return {
            isLoading: false,
            error: '',
            username: '',
            password: '',
            remember: true,
            access_role_id: '',
        }
    },

    beforeRouteEnter: (to, from, next) => {
        if (authStore.isLoggedIn()) {
            next({name: 'dashboard'});
        } else {
            next();
        }
    },
    beforeRouteUpdate: (to, from, next) => {
        if (authStore.isLoggedIn()) {
            next({name: 'dashboard'});
        } else {
            next();
        }
    },

    methods: {
        login() {
            this.isLoading = true;
            this.error = null;

            apiClient.post('login', {
                    username: this.username,
                    password: this.password,
                    remember: this.remember,
                    access_role_id: this.access_role_id,
                }
            ).then(({data}) => {
                if (data.token) {
                    authStore.setToken(data.token, data.accessAreas ?? [], data.agent ?? {});
                    this.$router.push({name: 'dashboard'});
                }
            }).catch((error) => {
                this.error = error.response.data.message || 'Unknown error';
            }).finally(() => {
                this.isLoading = false;
            });
        },
    },

    computed: {
        systemName: function () {
            return import.meta.env.VITE_APP_NAME;
        },

        isDevelopment: function () {
            let queryString = window.location.search;
            let urlParams = new URLSearchParams(queryString);

            return 'development' === import.meta.env.VITE_APP_ENV && urlParams.has('flags');
        }
    },
};
</script>

<style scoped lang="scss">
.login-box {
    max-width: 500px;
}
</style>
