<div class="modal fade modal-info text-start" id="avatarUpdateForm" tabindex="-1" aria-labelledby="avatarUpdateModal"
    style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="avatarUpdateModal">Update Profile Picture</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{ $user->name }}
            </div>
            <div class="modal-footer">
                <button type="button" class="me-auto btn btn-flat-danger waves-effect waves-float waves-light"
                    href="javascript:void(0);">
                    <i data-lucide="x-square" class="me-25"></i>
                    <span>Remove</span>
                </button>
                <button type="button" class="btn btn-info waves-effect waves-float waves-light"
                    href="javascript:void(0);">
                    <i data-lucide="check" class="me-25"></i>
                    <span>Confirm</span>
                </button>
            </div>
        </div>
    </div>
</div>
@section('page-script')
    <script></script>
@endsection
