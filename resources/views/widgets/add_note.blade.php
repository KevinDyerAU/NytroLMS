<div class="card blockUI">
    <div class="card-header">
        <h2 class='fw-bolder text-primary mx-auto'>{{ \Str::title($config['subject_type']) }} Note</h2>
    </div>
    <div class="card-body">
        <div class="clearfix divider divider-secondary divider-start-center ">
            <span class="divider-text text-dark">Add Note</span>
        </div>
        <div class='row mb-2'>
            <div class='col-12'>
                <div class='mb-1' id='add_note_input'>
                            <textarea name='note_body[]' id='{{ $config['input_id'] }}' class='form-control content-tinymce' tabindex='0'
                                      autofocus></textarea>
                </div>
            </div>
            <div class='col-12'>
                <button type="submit" class="btn btn-primary me-1 waves-effect waves-float waves-light"
                        onclick="Tabs.saveNote('{{ $config['subject_type'] }}', {{ $config['subject_id'] }},'{{ $config['input_id'] }}')">
                    Save
                </button>
            </div>
        </div>
    </div>
</div>
