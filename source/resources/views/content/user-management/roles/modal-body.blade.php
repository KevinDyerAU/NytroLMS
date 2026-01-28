<div class='row'>
    <div class='col-12'>
        <div class="mb-1">
            <label class="form-label" for="name">Role Name</label>
            @if( !empty( $role ) && $role->name && !isset( $isClone ) )
                <h3>{{ $role->name }}</h3>
            @else
                <x-forms.input name="name" input-class="" label-class="required" placeholder="Role Name" type="text"
                    value="{{ old( 'name' ) ?? ( isset( $isClone ) ? $role->name . ' (Copy)' : $role->name ?? '' ) }}"
                    autofocus></x-forms.input>
            @endif
        </div>
    </div>
    <div class='col-12'>
        <div class="mb-1">
            <label class="form-label required" for="select-permissions-multi">Attach Permissions</label>
            <select class="select2 form-select @error( 'permissions' ) is-invalid @enderror" id="select-permissions-multi"
                name='permissions[]' multiple required>
                @foreach(App\Models\Permission::all() as $permission)
                    <option value="{{ $permission->id }}" {{ ( !empty( $role ) && in_array( $permission->id, $role->permissions->pluck( 'id' )->toArray() ) ) ? 'selected' : '' }}>{{ ucwords( $permission->name ) }}
                    </option>
                @endforeach
            </select>
            @error( 'permissions' )
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class='col-12'>
        <button type="submit"
            class="btn btn-primary me-1 waves-effect waves-float waves-light">{{ $action[ 'name' ] ?? 'Submit' }}</button>
        <button type="reset" class="btn btn-outline-secondary waves-effect" id='cancel'
            data-bs-dismiss="modal">Cancel</button>
    </div>
</div>
