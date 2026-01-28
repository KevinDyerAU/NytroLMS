<div class="mb-1">
    <label class="form-label {{$labelClass}}" for="{{ $name }}">{{ $title }}</label>
    <input
        {{ $attributes }}
        class="form-control {{$inputClass}} @error($name) is-invalid @enderror"
        id="{{ $name }}"
        name="{{ $name }}"
        aria-label="{{ $title }}"
        value='{{ old($name) }}'
    />
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
