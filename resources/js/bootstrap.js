window._ = require('lodash');
window.DOMPurify = require('dompurify');
/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */
// import moment from 'moment';
// const moment = require('moment');
// const moment = require('moment-timezone');

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.timeout = 100000;

const axiosRetry = require('axios-retry');
axiosRetry(window.axios, {
    retries: 3,
    shouldResetTimeout: true,
    // retryCondition: (_error) => true // retry no matter what
});

var blockUISection = $('.blockUI');
window.axios.interceptors.request.use(
    config => {
        config.timeout = 100000;
        blockUISection.block({
            message:
                '<div class="d-flex justify-content-center align-items-center"><p class="me-50 mb-0">Please wait...</p><div class="spinner-grow spinner-grow-sm text-white" role="status"></div> </div>',
            css: {
                backgroundColor: 'transparent',
                color: '#fff',
                border: '0',
            },
            overlayCSS: {
                opacity: 0.5,
            },
        });
        return config;
    },
    error => {
        console.log('req', error);
        // window.jsErrorCall(error, error.errorLine);
        return Promise.reject(error);
    }
);

window.axios.interceptors.response.use(
    response => {
        blockUISection.unblock();
        return response;
    },
    error => {
        blockUISection.unblock();
        console.log('res', error);
        // window.jsErrorCall(error, error.errorLine);
        return Promise.reject(error);
    }
);

// window.axios.interceptors.response.use((response) => {
//     blockUISection.unblock();
//     return response;
// },
//     function axiosRetryInterceptor(err) {
//         blockUISection.unblock();
//
//         var config = err.config;
//         // If config does not exist or the retry option is not set, reject
//         if (!config || !config.retry) return Promise.reject(err);
//
//         // Set the variable for keeping track of the retry count
//         config.__retryCount = config.__retryCount || 0;
//
//         // Check if we've maxed out the total number of retries
//         if (config.__retryCount >= config.retry) {
//             // Reject with the error
//             return Promise.reject(err);
//         }
//
//         // Increase the retry count
//         config.__retryCount += 1;
//
//         // Create new promise to handle exponential backoff
//         var backoff = new Promise(function (resolve) {
//             setTimeout(function () {
//                 resolve();
//             }, config.retryDelay || 1);
//         });
//
//         // Return the promise in which recalls axios to retry the request
//         return backoff.then(function () {
//             return axios(config);
//         });
//     }
// );
/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// window.Pusher = require('pusher-js');

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: process.env.MIX_PUSHER_APP_KEY,
//     cluster: process.env.MIX_PUSHER_APP_CLUSTER,
//     forceTLS: true
// });
/*window.jsErrorCall = function (err, line, error = null) {
    if( err && typeof err != "undefined" && err.includes("null is not an object") || err.includes("classList")|| err.includes("googletag")|| err.includes("is null")){
        return false;
    }
    const apiUrl = '/log/errors/js';
    $.ajax({
        url: apiUrl,
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
            errorMsg: err,
            errorLine: line,
            queryString: document.location.search,
            url: document.location.pathname,
            referrer: document.referrer,
            userAgent: navigator.userAgent,
            error: error
        }
    });
}
window.captureError = function (ex) {
    var errorData = {
        name: ex.name, // e.g. ReferenceError
        message: ex.line, // e.g. x is undefined
        url: document.location.href,
        stack: ex.stack // stacktrace string; remember, different per-browser!
    };
    const apiUrl = '/log/errors/js';
    $.ajax({
        url: apiUrl,
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: errorData
    });
}
try {
    window.onerror = function (err, url, line, colno, error) {
        //suppress browser error messages
        let suppressErrors = true;
        jsErrorCall(err, line, error);
        return suppressErrors;
    };
} catch (e) {
}*/
