<div class="mb-1">
    <label class="form-label {{$labelClass}}" for="{{ $name }}">{{ $title }}</label>
    <textarea
        {{ $attributes }}
        class="form-control {{$inputClass}} @error($name) is-invalid @enderror"
        id="{{ $name }}"
        name="{{ $name }}"
        aria-label="{{ $title }}"
        value='{{ old($name) }}'>
    </textarea>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
