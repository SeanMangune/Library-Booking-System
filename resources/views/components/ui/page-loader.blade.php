<div id="ss-page-loader" class="ss-page-loader is-active" aria-hidden="false">
    <div class="ss-loader-scene" role="status" aria-live="polite" aria-label="SmartSpace is loading">
        <span class="ss-loader-aura" aria-hidden="true"></span>
        <span class="ss-loader-ring-soft" aria-hidden="true"></span>
        <span class="ss-loader-ring" aria-hidden="true"></span>
        <span class="ss-loader-led-track" aria-hidden="true"></span>
        <span class="ss-loader-led-track-soft" aria-hidden="true"></span>

        <div class="ss-loader-logo-wrap">
            <img src="{{ asset('images/smartspace-logo.png') }}" alt="SmartSpace" class="ss-loader-logo">
        </div>

        <p class="ss-loader-text">Loading SmartSpace</p>
    </div>
</div>

<style>
    #ss-page-loader .ss-loader-logo {
        transform: scale(1) !important;
        animation: none !important;
    }

    #ss-page-loader .ss-loader-led-track,
    #ss-page-loader .ss-loader-led-track-soft {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        border-radius: 999px;
        pointer-events: none;
    }

    #ss-page-loader .ss-loader-led-track {
        width: 142px;
        height: 142px;
        background: repeating-conic-gradient(
            from -6deg,
            rgba(59, 130, 246, 0.96) 0deg 7deg,
            rgba(99, 102, 241, 0.96) 7deg 11deg,
            rgba(147, 197, 253, 0.16) 11deg 17deg,
            transparent 17deg 22deg
        );
        -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 8px), #000 calc(100% - 7px));
        mask: radial-gradient(farthest-side, transparent calc(100% - 8px), #000 calc(100% - 7px));
        filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.45));
        animation: ss-loader-led-spin 1.8s linear infinite, ss-loader-led-pulse 1.6s ease-in-out infinite;
    }

    #ss-page-loader .ss-loader-led-track-soft {
        width: 164px;
        height: 164px;
        background: repeating-conic-gradient(
            from 180deg,
            rgba(56, 189, 248, 0.45) 0deg 10deg,
            rgba(56, 189, 248, 0.08) 10deg 21deg,
            transparent 21deg 30deg
        );
        -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 12px), #000 calc(100% - 11px));
        mask: radial-gradient(farthest-side, transparent calc(100% - 12px), #000 calc(100% - 11px));
        filter: blur(0.7px) drop-shadow(0 0 14px rgba(99, 102, 241, 0.35));
        opacity: 0.76;
        animation: ss-loader-led-spin-reverse 3.1s linear infinite;
    }

    @keyframes ss-loader-led-spin {
        from {
            transform: translate(-50%, -50%) rotate(0deg);
        }
        to {
            transform: translate(-50%, -50%) rotate(360deg);
        }
    }

    @keyframes ss-loader-led-spin-reverse {
        from {
            transform: translate(-50%, -50%) rotate(360deg);
        }
        to {
            transform: translate(-50%, -50%) rotate(0deg);
        }
    }

    @keyframes ss-loader-led-pulse {
        0%,
        100% {
            opacity: 0.82;
            filter: drop-shadow(0 0 9px rgba(59, 130, 246, 0.4));
        }
        50% {
            opacity: 1;
            filter: drop-shadow(0 0 13px rgba(99, 102, 241, 0.6));
        }
    }

    @media (prefers-reduced-motion: reduce) {
        #ss-page-loader .ss-loader-led-track {
            animation-duration: 2.4s, 2.4s;
        }

        #ss-page-loader .ss-loader-led-track-soft {
            animation-duration: 4.2s;
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
