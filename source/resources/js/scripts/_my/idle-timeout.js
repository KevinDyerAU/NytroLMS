/**
 * Idle Timeout Tracker
 * Tracks user activity and automatically logs out users after inactivity
 */
(function () {
    'use strict';

    // Configuration - get from Laravel config passed via window.idleTimeoutConfig
    const config = window.idleTimeoutConfig || {};
    const IDLE_TIMEOUT_MINUTES = config.timeoutMinutes || 120; // 2 hours in minutes
    const WARNING_TIME_MINUTES = config.warningMinutes || 5; // Show warning 5 minutes before timeout
    const ACTIVITY_CHECK_INTERVAL = config.checkIntervalMs || 60000; // Check every minute
    const ACTIVITY_UPDATE_INTERVAL = config.updateIntervalMs || 300000; // Update server every 5 minutes

    let idleTimer = null;
    let warningTimer = null;
    let activityCheckTimer = null;
    let countdownInterval = null;
    let lastActivityTime = Date.now();
    let isWarningShown = false;
    let isLoggedOut = false;

    // DOM elements
    let warningModal = null;
    let modalInstance = null;

    /**
     * Initialize the idle timeout tracker
     */
    function init() {
        if (!isAuthenticated()) {
            console.log('User not authenticated or laravel uninit');
            return; // Only initialize for authenticated users
        }

        // Set up activity tracking
        setupActivityTracking();

        // Start the idle timer
        resetIdleTimer();

        // Start periodic activity checks
        startActivityChecks();

        // Create warning modal
        createWarningModal();
    }

    function isAuthenticated() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        return csrfToken !== null && csrfToken.getAttribute('content') !== '';
    }

    /**
     * Set up activity tracking for various user interactions
     */
    function setupActivityTracking() {
        const events = [
            'mousedown',
            'mousemove',
            'keypress',
            'scroll',
            'touchstart',
            'click',
            'keydown',
            'keyup',
            'focus',
            'blur',
            'resize',
        ];

        events.forEach(event => {
            document.addEventListener(event, handleUserActivity, true);
        });

        // Track page visibility changes
        document.addEventListener('visibilitychange', handleVisibilityChange);

        // Track window focus/blur
        window.addEventListener('focus', handleUserActivity);
        window.addEventListener('blur', handleUserActivity);
    }

    /**
     * Handle user activity - reset the idle timer
     */
    function handleUserActivity() {
        if (isLoggedOut) return;

        lastActivityTime = Date.now();
        resetIdleTimer();

        // Hide warning if it's showing
        if (isWarningShown) {
            hideWarning();
        }
    }

    /**
     * Handle page visibility changes
     */
    function handleVisibilityChange() {
        if (document.hidden) {
            // Page is hidden, don't track activity
            return;
        } else {
            // Page is visible again, reset timer
            handleUserActivity();
        }
    }

    /**
     * Reset the idle timer
     */
    function resetIdleTimer() {
        // Clear existing timers
        if (idleTimer) clearTimeout(idleTimer);
        if (warningTimer) clearTimeout(warningTimer);

        // Set warning timer (5 minutes before timeout)
        const warningTime =
            (IDLE_TIMEOUT_MINUTES - WARNING_TIME_MINUTES) * 60 * 1000;
        warningTimer = setTimeout(showWarning, warningTime);

        // Set idle timeout timer
        const timeoutTime = IDLE_TIMEOUT_MINUTES * 60 * 1000;
        idleTimer = setTimeout(logoutUser, timeoutTime);
    }

    /**
     * Show warning modal before logout
     */
    function showWarning() {
        if (isWarningShown || isLoggedOut) return;

        isWarningShown = true;

        if (!warningModal) {
            createWarningModal();
        }

        // Clear any existing countdown
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }

        // Show modal - reuse existing instance
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            // Get existing instance or create new one only if needed
            if (!modalInstance) {
                modalInstance = new bootstrap.Modal(warningModal);
            }
            modalInstance.show();
        } else {
            warningModal.style.display = 'block';
        }

        // Update countdown after modal is shown
        let timeLeft = WARNING_TIME_MINUTES * 60;
        console.log('Starting countdown from:', timeLeft, 'seconds');
        updateCountdown(timeLeft);

        // Start countdown
        countdownInterval = setInterval(() => {
            timeLeft--;
            console.log('Countdown tick:', timeLeft);
            updateCountdown(timeLeft);

            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                countdownInterval = null;
                console.log('Countdown finished');
            }
        }, 1000);
    }

    /**
     * Hide warning modal
     */
    function hideWarning() {
        isWarningShown = false;

        // Clear countdown interval
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }

        if (warningModal) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                if (modalInstance) {
                    modalInstance.hide();
                }
            } else {
                warningModal.style.display = 'none';
            }
        }
    }

    /**
     * Update countdown display
     */
    function updateCountdown(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        const timeString = `${minutes}:${remainingSeconds
            .toString()
            .padStart(2, '0')}`;

        const countdownElement = warningModal?.querySelector('#countdown');
        if (countdownElement) {
            countdownElement.textContent = timeString;
        } else {
            console.warn('Countdown element not found in modal');
        }
    }

    /**
     * Create warning modal
     */
    function createWarningModal() {
        if (warningModal) return;

        warningModal = document.createElement('div');
        warningModal.className = 'modal fade';
        warningModal.id = 'idle-warning-modal';
        warningModal.setAttribute('data-bs-backdrop', 'static');
        warningModal.setAttribute('data-bs-keyboard', 'false');

        warningModal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title text-dark">
                            <i data-lucide="clock" class="me-1"></i>
                            Are you still there?
                        </h5>
                    </div>
                    <div class="modal-body text-center">
                        <h6 class="p-1">
                            <i data-lucide="alert-triangle" class="text-warning" style="font-size: 3rem;"></i>
                            Your session will expire due to inactivity
                        </h6>
                        <p class="text-muted">
                            You will be automatically logged out in <strong id="countdown">5:00</strong>.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" onclick="IdleTimeout.logout()">
                            Logout Now
                        </button>
                        <button type="button" class="btn btn-primary" onclick="IdleTimeout.stayLoggedIn()">
                            Stay Logged In
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(warningModal);
    }

    /**
     * Stay logged in - reset timer and hide warning
     */
    function stayLoggedIn() {
        handleUserActivity();
        hideWarning();
        updateServerActivity();
    }

    /**
     * Logout user due to inactivity
     */
    function logoutUser() {
        if (isLoggedOut) return;

        isLoggedOut = true;
        console.log('Logging out user due to inactivity');

        // Hide warning if showing
        hideWarning();

        // Show logout message
        if (typeof toastr !== 'undefined') {
            toastr.warning(
                'Your session has expired due to inactivity. Please log in again.',
                'Session Expired'
            );
        }

        // Properly logout via Laravel's logout endpoint
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/logout';

        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken.getAttribute('content');
            form.appendChild(csrfInput);
        }

        // Add redirect parameter to indicate timeout
        const redirectInput = document.createElement('input');
        redirectInput.type = 'hidden';
        redirectInput.name = 'timeout';
        redirectInput.value = '1';
        form.appendChild(redirectInput);

        document.body.appendChild(form);

        // Submit the form after a brief delay to show the message
        setTimeout(() => {
            form.submit();
        }, 1000);
    }

    /**
     * Start periodic activity checks
     */
    function startActivityChecks() {
        activityCheckTimer = setInterval(() => {
            if (isLoggedOut) return;

            // Check if user is still active
            const timeSinceLastActivity = Date.now() - lastActivityTime;
            const idleMinutes = Math.floor(timeSinceLastActivity / (60 * 1000));

            if (idleMinutes >= IDLE_TIMEOUT_MINUTES) {
                logoutUser();
            } else {
                // Update server with activity every 5 minutes
                if (
                    timeSinceLastActivity % ACTIVITY_UPDATE_INTERVAL <
                    ACTIVITY_CHECK_INTERVAL
                ) {
                    updateServerActivity();
                }
            }
        }, ACTIVITY_CHECK_INTERVAL);
    }

    /**
     * Update server with current activity
     */
    function updateServerActivity() {
        if (isLoggedOut) return;

        fetch('/api/v1/activity/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute('content'),
            },
            credentials: 'same-origin',
        })
            .then(response => {
                if (response.status === 419 || response.status === 401) {
                    // Session expired, logout immediately
                    console.warn('Session expired during activity update');
                    logoutUser();
                    return null;
                }
                if (!response.ok) {
                    throw new Error('Failed to update activity');
                }
                return response.json();
            })
            .then(data => {
                if (data && data.success) {
                    // Optionally refresh CSRF token if provided
                    if (data.csrf_token) {
                        const csrfMeta = document.querySelector(
                            'meta[name="csrf-token"]'
                        );
                        if (csrfMeta) {
                            csrfMeta.setAttribute('content', data.csrf_token);
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error updating activity:', error);
            });
    }

    /**
     * Get current session status
     */
    function getSessionStatus() {
        return fetch('/api/v1/activity/status', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute('content'),
            },
            credentials: 'same-origin',
        })
            .then(response => {
                if (response.status === 419 || response.status === 401) {
                    // Session expired
                    console.warn('Session expired during status check');
                    logoutUser();
                    return null;
                }
                return response.json();
            })
            .catch(error => {
                console.error('Error getting session status:', error);
                return null;
            });
    }

    /**
     * Clean up timers and event listeners
     */
    function destroy() {
        if (idleTimer) clearTimeout(idleTimer);
        if (warningTimer) clearTimeout(warningTimer);
        if (activityCheckTimer) clearInterval(activityCheckTimer);
        if (countdownInterval) clearInterval(countdownInterval);

        const events = [
            'mousedown',
            'mousemove',
            'keypress',
            'scroll',
            'touchstart',
            'click',
            'keydown',
            'keyup',
            'focus',
            'blur',
            'resize',
        ];

        events.forEach(event => {
            document.removeEventListener(event, handleUserActivity, true);
        });

        document.removeEventListener(
            'visibilitychange',
            handleVisibilityChange
        );
        window.removeEventListener('focus', handleUserActivity);
        window.removeEventListener('blur', handleUserActivity);

        // Dispose modal instance
        if (modalInstance) {
            modalInstance.dispose();
            modalInstance = null;
        }

        if (warningModal && warningModal.parentNode) {
            warningModal.parentNode.removeChild(warningModal);
            warningModal = null;
        }
    }

    /**
     * Get current idle timeout status (for debugging)
     */
    function getStatus() {
        const countdownElement = warningModal?.querySelector('#countdown');
        const countdownText = countdownElement?.textContent || 'N/A';

        return {
            isWarningShown: isWarningShown,
            isLoggedOut: isLoggedOut,
            countdown: countdownText,
            lastActivityTime: new Date(lastActivityTime).toLocaleTimeString(),
            idleMinutes: Math.floor(
                (Date.now() - lastActivityTime) / (60 * 1000)
            ),
            configTimeout: IDLE_TIMEOUT_MINUTES,
        };
    }

    // Public API
    window.IdleTimeout = {
        init: init,
        destroy: destroy,
        stayLoggedIn: stayLoggedIn,
        logout: logoutUser,
        getSessionStatus: getSessionStatus,
        updateActivity: updateServerActivity,
        showWarning: showWarning,
        getStatus: getStatus,
    };

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
