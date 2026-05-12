(function () {
    var logoutKey = 'ppg:lastLogoutAt';
    var loginPath = '/login';

    window.notifyLogoutTabs = function () {
        try {
            localStorage.setItem(logoutKey, String(Date.now()));
        } catch (error) {
            // The current tab can still submit logout if localStorage is unavailable.
        }
    };

    window.addEventListener('storage', function (event) {
        if (event.key === logoutKey && window.location.pathname !== loginPath) {
            window.location.replace(loginPath);
        }
    });

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
})();
