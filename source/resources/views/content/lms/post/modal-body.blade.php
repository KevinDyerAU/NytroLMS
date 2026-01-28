<div class='row'>
    <div class='col-12'>
        <x-forms.input name="title" input-class="" label-class="required" type="text"
            value="{{ old('title') ?? ($post->title ?? '') }}" tabindex="1" autofocus></x-forms.input>
    </div>
    <div class="col-12">
        <div class="mb-2">
            <label class="form-label required" for="_content">Content</label>
            <textarea class="form-control @error('_content') is-invalid @enderror" name="_content" id="_content" tabindex="2"
                hidden>{!! old('_content') ?? (!empty($post) ? $post->getRawContent() : '') !!}</textarea>
            <div class="mb-1">
                @error('_content')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
    <div class="col-12 mb-2">
        <div class="border rounded p-2">
            <h4 class="mb-1">Featured Image</h4>
            <div class="d-flex flex-column flex-md-row">
                <img src="{{ $post && !empty($post->featuredImage()) ? Storage::url($post->featuredImage()->file_path) : asset('images/profile/no-image.jpg') }}"
                    id="feature-image" class="rounded me-2 mb-1 mb-md-0" width="170" height="110"
                    alt="Featured Image" />
                <div class="featured-info">
                    <small class="text-muted">Recommended Image Resolution: <strong>1150x400</strong> <br />
                        Image Size: <strong>1MB</strong></small>
                    <div class="my-50">
                        <x-forms.input name="featured_image" input-class="" label-class="" type="file"
                            value="{{ old('featured_image') }}" tabindex="3"></x-forms.input>
                    </div>
                    @if ($post && !empty($post->featuredImage()))
                        <p class="d-inline-block">
                            <button type='button' class='btn btn-danger btn-sm waves-effect'
                                onclick='Utils.removeImage("feature-image", {{ $post->featuredImage()->id }})'
                                id="removeImage">Remove
                            </button>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @if ($type === 'course')
        @include('content.lms.partials.modal.course')
    @elseif($type === 'lesson')
        @include('content.lms.partials.modal.lesson')
    @elseif($type === 'topic')
        @include('content.lms.partials.modal.topic')
    @elseif($type === 'quiz')
        @include('content.lms.partials.modal.quiz')
    @endif
    <div class='col-12'>
        <button type="submit"
            class="btn btn-primary me-1 waves-effect waves-float waves-light">{{ $action['name'] ?? 'Submit' }}</button>
        <button type="reset" class="btn btn-outline-secondary waves-effect" id='cancel' data-bs-dismiss="modal">
            Cancel
        </button>
    </div>
</div>
