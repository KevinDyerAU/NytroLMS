require('../../js/app');
require('./custom');

// import Vue from 'vue';
//
// Vue.component('student-admin', require('./components/StudentAdmin').default);
// Vue.component('student-enrolment', require('./components/StudentEnrolment').default);
// Vue.component('student-documents', require('./components/StudentDocuments').default);
//
// const app = new Vue({
//     el: '#app',
// });
String.prototype.toProperCase = function () {
    let i, frags = this.split('_');
    for (i=0; i<frags.length; i++) {
        frags[i] = frags[i].charAt(0).toUpperCase() + frags[i].slice(1);
    }
    return frags.join(' ');
};
