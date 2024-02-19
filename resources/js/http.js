import axios from "axios";
import {authStore} from "@/store/auth-store.ts";
import router from "./routes";
import {toast} from "vue3-toastify";

const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL,
    headers: {
        "Content-type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
    },
});

const axiosRequestHandler = (request) => {
    if (authStore.getState().token) {
        request.headers['Authorization'] = "Bearer " + authStore.getState().token;
    }
    return request;
};

apiClient.interceptors.request.use(
    request => axiosRequestHandler(request)
);

const axiosSuccessHandler = (response) => {
    return response;
};

const axiosErrorHandler = (error) => {
    if (error.response?.status === 401) {
        authStore.setToken('');
        router.push({name: 'login'});
    } else if (error.response && error.response.data) {
        // If an expected Blob download throws a JSON error, detect and parse it
        if (
            error.request.responseType === 'blob' &&
            error.response.data instanceof Blob &&
            error.response.data.type &&
            error.response.data.type?.toLowerCase()?.indexOf('json') !== -1
        ) {
            error.response.data.text().then(text => {
                let result = JSON.parse(text);
                toast.error(result.message || 'Unknown error');
            });
        } else if (error.response.data.message) {
            toast.error(error.response.data.message);
        }
    }

    // Not an authentication error; allow normal request failure process
    return Promise.reject(error);
};

apiClient.interceptors.response.use(
    response => axiosSuccessHandler(response),
    error => axiosErrorHandler(error)
);

export default apiClient;
