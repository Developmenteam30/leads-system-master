// https://medium.com/@mario.brendel1990/vue-3-the-new-store-a7569d4a546f

import {reactive, readonly, ref, Ref, watch} from 'vue';

export abstract class Store<T extends Object> {
    protected state: T;

    constructor(readonly storeName: string) {
        let data = this.data();
        this.state = reactive(data) as T;
    }

    protected abstract data(): T

    public getState(): T {
        return readonly(this.state) as T
    }
}

export abstract class PersistentStore<T extends Object> extends Store<T> {

    private isInitialized = ref(false);

    constructor(readonly storeName: string) {
        super(storeName);
    }

    async init() {
        if (this.isInitialized) {
            let state = {};

            try {
                let stateFromLocalStorage = await localStorage.getItem(this.storeName);
                if (stateFromLocalStorage) {
                    Object.assign(this.state, JSON.parse(stateFromLocalStorage))
                }
            } catch (e) {
                // console.log(e)
            }
            watch(() => this.state, (val) => localStorage.setItem(this.storeName, JSON.stringify(val)), {deep: true})

            this.isInitialized.value = true;
        }
    }

    getIsInitialized(): Ref<boolean> {
        return this.isInitialized
    }
}
