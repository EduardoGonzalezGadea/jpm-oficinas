(function () {
    'use strict';
    const DEFAULT_MESSAGE = 'Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.';
    function getLoginUrl() {
        const meta = document.querySelector('meta[name="login-url"]');
        return meta ? meta.getAttribute('content') : '/login';
    }
    function clearLocalTokens() {
        try {
            localStorage.removeItem('jwt_token');
            sessionStorage.removeItem('jwt_token');
            localStorage.removeItem('lastActivity');
        } catch (e) {}
    }
    function parseResponsePayload(response) {
        if (!response) return null;
        if (typeof response === 'string') {
            try { return JSON.parse(response); } catch (e) { return { message: response }; }
        }
        return response;
    }
    function isSessionExpiredStatus(statusCode) {
        return statusCode === 401 || statusCode === 419;
    }
    function isSessionExpiredPayload(payload, statusCode) {
        if (isSessionExpiredStatus(statusCode)) return true;
        if (!payload) return false;
        const message = String(payload.message || payload.error || '').toLowerCase();
        return message.includes('sesión') && (message.includes('expir') || message.includes('inválid') || message.includes('invalid') || message.includes('termin'));
    }
    let redirectScheduled = false;
    window.handleSessionExpired = function (options) {
        if (redirectScheduled) return;
        redirectScheduled = true;
        const opts = options || {};
        const loginUrl = opts.redirect || getLoginUrl();
        const message = opts.message || DEFAULT_MESSAGE;
        const delay = typeof opts.delay === 'number' ? opts.delay : 1500;
        clearLocalTokens();
        if (window.Swal) {
            Swal.fire({ icon: 'warning', title: 'Sesión expirada', text: message, timer: delay, timerProgressBar: true, showConfirmButton: false, allowOutsideClick: false, allowEscapeKey: false });
        }
        setTimeout(function () { window.location.href = loginUrl; }, delay);
    };
    window.isSessionExpiredResponse = function (statusCode, response) {
        return isSessionExpiredPayload(parseResponsePayload(response), statusCode);
    };
    window.installFetchSessionGuard = function () {
        if (window.__sessionFetchGuardInstalled || typeof window.fetch !== 'function') return;
        window.__sessionFetchGuardInstalled = true;
        const originalFetch = window.fetch.bind(window);
        window.fetch = function () {
            return originalFetch.apply(window, arguments).then(function (response) {
                if (isSessionExpiredStatus(response.status)) {
                    response.clone().json().then(function (payload) {
                        window.handleSessionExpired({ message: payload.message || payload.error || DEFAULT_MESSAGE, redirect: payload.redirect || getLoginUrl() });
                    }).catch(function () { window.handleSessionExpired(); });
                }
                return response;
            });
        };
    };
    document.addEventListener('DOMContentLoaded', function () {
        window.installFetchSessionGuard();
        window.addEventListener('redirect-to-login', function (event) {
            const detail = event.detail || {};
            window.handleSessionExpired({ message: detail.message || DEFAULT_MESSAGE, redirect: detail.redirect || getLoginUrl() });
        });
    });
})();
