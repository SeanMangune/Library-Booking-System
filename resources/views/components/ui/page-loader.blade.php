<div id="ss-page-loader" class="ss-page-loader is-active" aria-hidden="false">
    <div class="ss-loader-scene" role="status" aria-live="polite" aria-label="SmartSpace is loading">
        <span class="ss-loader-aura" aria-hidden="true"></span>
        <span class="ss-loader-ring-soft" aria-hidden="true"></span>
        <span class="ss-loader-ring" aria-hidden="true"></span>

        <div class="ss-loader-logo-wrap">
            <img src="{{ asset('images/smartspace-logo.png') }}" alt="SmartSpace" class="ss-loader-logo">
        </div>

        <p class="ss-loader-text">Loading SmartSpace</p>
    </div>
</div>

<style>
    /* Keep logo spin animation resilient even when bundled CSS is stale on deployment. */
    #ss-page-loader .ss-loader-logo {
        transform-origin: center;
        animation: ss-loader-domain-spin 1.45s linear infinite !important;
    }

    @keyframes ss-loader-domain-spin {
        0% {
            transform: rotate(0deg) scale(0.95);
        }
        50% {
            transform: rotate(180deg) scale(1.04);
        }
        100% {
            transform: rotate(360deg) scale(0.95);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        #ss-page-loader .ss-loader-logo {
            animation-duration: 2.4s !important;
        }
    }
</style>

<noscript>
    <style>
        #ss-page-loader {
            display: none !important;
        }
    </style>
</noscript>
