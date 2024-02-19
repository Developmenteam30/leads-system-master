<template>
    <MDBContainer>
        <p>Logging you out ...</p>
    </MDBContainer>
</template>

<script>
import {authStore} from "@/store/auth-store.ts";
import router from "../../routes";
import {MDBContainer} from 'mdb-vue-ui-kit';

export default {
    components: {
        MDBContainer,
    },
    mounted() {
        authStore.setToken('', 0);
        router.push({name: 'dashboard'});
    },
}
</script>
