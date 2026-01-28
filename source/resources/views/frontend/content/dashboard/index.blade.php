@extends('frontend/layouts/contentLayoutMaster')

@section('title', $title)

@section('page-style')
    {{-- Page Css files --}}
    <link rel="stylesheet" type="text/css"
        href="{{ asset('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/pages/page-blog.css') }}" />
@endsection

@section('content')
    {{-- LLND Status Banner - Dashboard Level --}}
    @if (isset($llnStatus) && $llnStatus['has_submitted'])
        @if (in_array($llnStatus['status'], ['SUBMITTED', 'REVIEWING']) && !$llnStatus['is_satisfactory'])
            {{-- Submitted and waiting for evaluation --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <div class="alert-body">
                            <p class="mb-2">Your Language, Literacy, and Numeracy Development (LLND) assessment has been
                                submitted and is currently being evaluated. Please wait for the results.</p>
                            <small class="text-muted">Status: {{ $llnStatus['status'] }}</small>
                        </div>
                    </div>
                </div>
            </div>
        @elseif(!$llnStatus['is_satisfactory'])
            {{-- Not satisfactory - needs re-attempt --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-warning">
                        <div class="alert-body">
                            <p class="mb-2">Your Language, Literacy, and Numeracy Development (LLND) assessment result is
                                <strong>Not Satisfactory</strong>. You need to re-attempt this assessment to access your
                                courses.</p>
                            <a href="{{ route('frontend.lms.quizzes.show', $llnStatus['quiz_id']) }}"
                                class="btn btn-warning btn-sm">
                                <i data-lucide="refresh-cw" class="me-1"></i>
                                Re-attempt LLND Assessment
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    @include('frontend.content.lms.course-list')
@endsection
