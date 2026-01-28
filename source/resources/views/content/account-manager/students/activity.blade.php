
    <div id="student_activity">
        {{ $dataTable->table() }}
    </div>


@push('scripts')
    {{ $dataTable->scripts() }}
@endpush
