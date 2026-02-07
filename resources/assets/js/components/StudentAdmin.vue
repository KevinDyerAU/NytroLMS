<template>
    <div class='row'>
        <div class='col'>
            <h2>{{ name }} {{ studentId }}</h2>
        </div>
        <b-card no-body>
            <b-tabs content-class='mt-3' pills card>
                <b-tab title='Student Enrolment' active>
                    <StudentEnrolment v-bind:student-id="studentId"></StudentEnrolment>
                </b-tab>
                <b-tab title='Student Documents' lazy>
                    <StudentDocuments v-bind:student-id="studentId"></StudentDocuments>
                </b-tab>
            </b-tabs>
        </b-card>
    </div>
</template>

<script>
import StudentEnrolment from './StudentEnrolment';
import StudentDocuments from './StudentDocuments';
export default {
    props: ['studentId'],
    components:{
        StudentEnrolment,
        StudentDocuments
    },
    data: function() {
        return {
            name: 'Student Admin'
        };
    }
};
</script>

<style scoped>

</style>
