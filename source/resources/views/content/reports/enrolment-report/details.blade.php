@extends('layouts/contentLayoutMaster')

@section('title','Report Details')

@section('content')
    <div class='row'>
        <div class='col-12 mx-auto'>
            <div class='card'>
                <div class='card-body'>
                    <div class='row mb-2'>
                        @if(!empty($report->basic))
                            @foreach( $report->basic as $key=> $value)
                                <div
                                    class='col-4 my-50 py-50  border-top-1 border-bottom-1 border-bottom-light border-top-light'>
                                    <span
                                        class='fw-bolder me-25 col-sm-4 text-end'>{{ Str::replace('_',' ',Str::title($key)) }}: </span>
                                    <span class='col-sm-6'>{{ $value }}</span>
                                </div>
                            @endforeach
                        @endif
                        @if(!empty($report->onboard))
                            @foreach( $report->onboard as $step)
                                @if(is_iterable($step))
                                    @foreach( $step as $key=> $value)
                                        <div
                                            class='col-4 my-50 py-50 border-top-1 border-bottom-1 border-bottom-light border-top-light'>
                                            <span
                                                class='fw-bolder me-25 col-sm-4 text-end'>{{ Str::replace('_',' ',Str::title($key)) }}: </span>
                                            <span class='col-sm-6'>{{ $value }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
