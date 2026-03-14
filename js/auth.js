/**
 * Gestion du token JWT côté client (localStorage) et helpers pour les appels API.
 */
(function () {
    const TOKEN_KEY = 'mm_token';
    const USER_KEY = 'mm_user';

    window.auth = {
        getToken: function () {
            return localStorage.getItem(TOKEN_KEY);
        },
        setToken: function (token) {
            if (token) localStorage.setItem(TOKEN_KEY, token);
            else localStorage.removeItem(TOKEN_KEY);
        },
        getUser: function () {
            try {
                return JSON.parse(localStorage.getItem(USER_KEY) || 'null');
            } catch (e) {
                return null;
            }
        },
        setUser: function (user) {
            if (user) localStorage.setItem(USER_KEY, JSON.stringify(user));
            else localStorage.removeItem(USER_KEY);
        },
        login: function (token, user) {
            this.setToken(token);
            this.setUser(user);
        },
        logout: function () {
            this.setToken(null);
            this.setUser(null);
            window.location.href = '/connexion.html';
        },
        isLoggedIn: function () {
            return !!this.getToken();
        },
        /** Redirige vers connexion si pas de token */
        requireAuth: function () {
            if (!this.isLoggedIn()) {
                window.location.href = '/connexion.html?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
                return false;
            }
            return true;
        },
        /** fetch avec Authorization: Bearer <token> */
        fetch: function (path, options) {
            options = options || {};
            options.headers = options.headers || {};
            if (!options.headers['Content-Type'] && typeof options.body === 'string') {
                options.headers['Content-Type'] = 'application/json';
            }
            var token = this.getToken();
            if (token) options.headers['Authorization'] = 'Bearer ' + token;
            var url = (window.API_BASE_URL || '').replace(/\/$/, '') + path;
            return window.fetch(url, options);
        }
    };
})();
