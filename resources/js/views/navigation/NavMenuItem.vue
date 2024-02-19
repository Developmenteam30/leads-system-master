<template>
    <MDBNavbarItem v-if="authStore.hasAccessToAnyArea(childAccessAreas)">
        <!-- Navbar dropdown -->
        <MDBDropdown
            class="nav-item"
            :dropend="nested"
            v-model="dropdown"
            @mouseover="dropdown = true"
            @mouseleave="dropdown = false"
        >
            <MDBDropdownToggle
                :id="menuId"
                tag="a"
                class="nav-link"
                @click="dropdown = true"
            >{{ menuItem.title }}
            </MDBDropdownToggle>
            <MDBDropdownMenu :aria-labelledby="menuId">
                <template v-for="child in menuItem.children">
                    <MDBDropdownItem
                        v-if="!child.children && (!child.access_area || authStore.hasAccessToArea(child.access_area))"
                        :to="{ name: child.route_name}"
                        :active="currentRoute === child.route_name">{{ child.title }}
                    </MDBDropdownItem>
                    <MDBDropdownItem
                        v-else-if="child.children"
                    >
                        <NavMenuItem
                            :menu-item="child"
                            :nested="true"
                        ></NavMenuItem>
                    </MDBDropdownItem>
                </template>
            </MDBDropdownMenu>
        </MDBDropdown>
    </MDBNavbarItem>
</template>

<script setup>
import {
    MDBDropdown,
    MDBDropdownItem,
    MDBDropdownMenu,
    MDBDropdownToggle,
    MDBNavbarItem,
} from "mdb-vue-ui-kit";
import {authStore} from "@/store/auth-store";
import {uuid} from 'vue-uuid'
import {computed, ref, watch} from "vue";
import {useRoute} from "vue-router";

const props = defineProps({
    menuItem: {
        type: Object,
        required: true
    },
    nested: {
        type: Boolean,
        default: false,
    }
});

const menuId = uuid.v1()
const currentRoute = computed(() => {
    return useRoute().name
});

const dropdown = ref(false);
const route = useRoute();
watch(() => route.name, (newRoute) => {
    dropdown.value = false;
})

const childAccessAreas = computed(() => {
    return props.menuItem.children.map(child => child.access_area);
});
</script>

<style scoped lang="scss">

</style>
