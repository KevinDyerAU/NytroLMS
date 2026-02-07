@extends('frontend.layouts.contentLayoutMaster')

@section('content')

    <div class="d-flex align-items-center">
        <div class="col-lg-8 col-md-10 col-12 mx-auto">
            <form method="POST" action="{{ route('account_manager.leaders.onboard-agreement') }}">
                @csrf
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h2 class="card-title text-center mb-0">Client User Policy</h2>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li>By accessing Key Institute's website and Learning Management System, you acknowledge and agree that all copyright, content, and material belong to Key Institute.</li>
                            <li>Without written consent from Key Institute, you will not print, copy, distribute, remove branding from, or provide copies of the content to anyone other than trainers who are also training in the unit or students who are enrolled in the unit.</li>
                            <li>You agree that you will not attempt to extract the source code of Key Institute's Learning Management System.</li>
                            <li>You agree not to allow any other person to access your account without written consent from Key Institute, which holds the right to collect data related to user access locations for security purposes.</li>
                            <li>You will use Key Institute's Learning Management System solely for the purpose of reviewing student completion of units and providing assistance and feedback to students, and you will not contact students for any other reason.</li>
                            <li>You agree that Key Institute may remove or alter your access to its Learning Management System at any time.</li>
                            <li>Key Institute has no obligation to provide uninterrupted access to its services, and although we will make every effort to provide continuous access, the Services may, from time to time, be inaccessible to users.</li>
                            <li>As a user of Key Institute's services, you agree not to use them for illegal or unlawful purposes or in a way that encourages criminal activity.</li>
                            <li>By registering a student within Key Institute's Learning Management System, you agree to our fees and conditions of service.</li>
                        </ul>
                        <div class="mb-3">
                            <div class="form-check form-check-inline @error('agreement') is-invalid @enderror">
                                <input class="form-check-input" type="checkbox" id="agreement" name='agreement' value='agree'>
                                <label class="form-check-label" for="agreement">I have read and agree to the Terms and Conditions</label>
                            </div>
                            @error('agreement')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="text-center">
                            <button class="btn btn-outline-success" type="submit">Submit</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
