(function () {
    var script = document.currentScript;
    var targetSelector = script && script.dataset.target ? script.dataset.target : '#submissions-documents';
    var interval = Number(script && script.dataset.interval ? script.dataset.interval : 5000);
    var isLoading = false;

    if (!Number.isFinite(interval) || interval <= 0) {
        return;
    }

    window.setInterval(function () {
        var target = document.querySelector(targetSelector);

        if (!target || document.hidden || isLoading || target.querySelector('button:disabled')) {
            return;
        }

        isLoading = true;

        fetch(window.location.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            cache: 'no-store'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Refresh failed');
                }

                return response.text();
            })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var nextTarget = doc.querySelector(targetSelector);

                if (nextTarget) {
                    target.innerHTML = nextTarget.innerHTML;
                }
            })
            .catch(function () {
                // Keep the current list visible if a background refresh fails.
            })
            .finally(function () {
                isLoading = false;
            });
    }, interval);
})();
