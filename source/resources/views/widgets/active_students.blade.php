<div class="card py-1 bg-light-success">
    <div class="card-header flex-row align-items-start pb-0">
        <div class='col-2'>
            <div class="avatar bg-success p-50 m-0">
                <a href="{{ route('account_manager.students.index', ['status' => 'active']) }}" class="avatar-content">
                    <i data-lucide="activity" class="font-medium-5"></i>
                </a>
            </div>
        </div>
        <div class='col-8 d-flex flex-row align-items-center align-self-center'>
            <h3 class="card-text flex-wrap pe-1">Active</h3>
            <h2 class="fw-bolder ms-1 ">{{ $data['count'] }}</h2>
        </div>
    </div>
    {{--    <div id="active-students-chart"></div> --}}
</div>

<script>
    (function(window, document, $) {
        $(window).on('load', function() {
            'use strict';
            var $avgSessionStrokeColor2 = '#ebf0f7';
            var $white = '#fff';
            var $barColor = '#f3f3f3';
            var $trackBgColor = '#EBEBEB';
            var $textMutedColor = '#b9b9c3';
            var $budgetStrokeColor2 = '#dcdae3';
            var $goalStrokeColor2 = '#51e5a8';
            var $strokeColor = '#ebe9f1';
            var $textHeadingColor = '#5e5873';
            var $earningsStrokeColor2 = '#28c76f66';
            var $earningsStrokeColor3 = '#28c76f33';
            let $chart = document.querySelector('#active-students-chart');
            let chartOptions = {
                chart: {
                    height: 100,
                    width: '100%',
                    type: 'area',
                    animations: {
                        initialAnimation: {
                            enabled: false
                        }
                    },
                    toolbar: {
                        show: false
                    },
                    grid: {
                        show: false,
                        padding: {
                            left: 0,
                            right: 0
                        }
                    }
                },
                colors: [window.colors.solid.success],
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2.5
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 0.9,
                        opacityFrom: 0.7,
                        opacityTo: 0.5,
                        stops: [0, 80, 100]
                    }
                },
                series: [{
                    name: 'Total',
                    data: {!! $data['dataset'] !!}
                }],

                xaxis: {
                    labels: {
                        show: false
                    },
                    axisBorder: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        show: false
                    },
                    axisBorder: {
                        show: false
                    }
                },
                tooltip: {
                    x: {
                        show: false
                    }
                }
            };
            // let chart = new ApexCharts($chart, chartOptions);
            // chart.render();
        });
    })(window, document, jQuery);
</script>
