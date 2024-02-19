import {PersistentStore} from "./store";

interface Auth extends Object {
    token: string,
    accessAreas: Array<string>,
    agent: object,
}

class AuthStore extends PersistentStore<Auth> {
    protected data(): Auth {
        return {
            token: '',
            accessAreas: [],
            agent: {},
        };
    }

    isLoggedIn() {
        return this.state.token.length > 0;
    }

    hasAccessToArea(area: string) {
        return this.state.accessAreas && Array.isArray(this.state.accessAreas) && this.state.accessAreas.includes(area);
    }

    hasAccessToAnyArea(areas: Array<string>) {
        return this.state.accessAreas && Array.isArray(this.state.accessAreas) && this.state.accessAreas.some(area => areas.includes(area));
    }

    setToken(token: string, accessAreas: Array<string>, agent: object) {
        this.state.token = token;
        this.state.accessAreas = accessAreas;
        this.state.agent = agent;
    }
}

export const authStore: AuthStore = new AuthStore("authStore");
