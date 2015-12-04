function logf(str) {
    //console.log('function: ' + str);
}


(function ($, undefined) {
    'use strict';

    var
        // -- Variables
        results = {},
        timer   = 0,
        triggerRedraw = false,

        // Inputs
        $from  = $('#from'),
        $to    = $('#to'),

        // Output
        $chart = $('#chart').text('Generating chart…'),


        // -- Functions

        escapeHTML = function (str) {
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },


        getChart = function () {
            logf('getChart');

            results = {
                    xlikely   : parseInt($('#xlikely').text(), 10),
                    likely    : parseInt($('#likely').text(), 10),
                    neither   : parseInt($('#neither').text(), 10),
                    unlikely  : parseInt($('#unlikely').text(), 10),
                    xunlikely : parseInt($('#xunlikely').text(), 10),
                    unsure    : parseInt($('#unsure').text(), 10)
                };

            updateChart();
        },


        init = function () {
            logf('init');

            getChart();

            // Setup datepickers
            $from.datepicker({
                changeMonth: true,
                changeYear: true,
                dateFormat: 'dd/mm/yy',
                defaultDate: '-1m',
                maxDate: -1,
                minDate: minDate ? minDate : null,
                numberOfMonths: 1,
                onClose: function (d) {
                    if (!!d) {
                        $to.datepicker('option', 'minDate', d);
                    }
                }
            });

            $to.datepicker({
                changeMonth: true,
                changeYear: true,
                dateFormat: 'dd/mm/yy',
                defaultDate: null,
                maxDate: -1,
                minDate: minDate ? minDate : null,
                numberOfMonths: 1,
                onClose: function (d) {
                    if (!!d) {
                        $from.datepicker('option', 'maxDate', d);
                    }
                }
            });
        },


        updateChart = function () {
            logf('updateChart');

            setTimeout(function () { // Timeout is a workaround, to prevent google.load from overwriting HTML
                google.load('visualization', 1, { callback: function () {
                    var
                        data = google.visualization.arrayToDataTable([
                            ['Answer',                      'Frequency', { role: 'style' }],
                            ['Extremely likely',            results.xlikely,   '#14b800'],
                            ['Likely',                      results.likely,    '#3c8f00'],
                            ['Neither likely nor unlikely', results.neither,   '#656600'],
                            ['Unlikely',                    results.unlikely,  '#903d00'],
                            ['Extremely unlikely',          results.xunlikely, '#b81400'],
                            ['Don\'t know',                 results.unsure,    '#aaa']
                        ]),

                        options = {
                            //title: 'FFT data',
                            bar: { groupWidth: '90%' },
                            vAxis: {title: 'Responses',  titleTextStyle: {color: 'grey'}},
                            //vAxis: {title: 'Answer',  titleTextStyle: {color: 'red'}},
                            legend: { position: 'none' },
                            height: 400
                        },
                                
                        view  = new google.visualization.DataView(data),
                        //chart = new google.visualization.BarChart(document.getElementById('chart'));
                        chart = new google.visualization.ColumnChart(document.getElementById('chart'));

                    view.setColumns([0, 1, {
                            calc:         'stringify',
                            sourceColumn: 1,
                            type:         'string',
                            role:         'annotation'
                        }, 2]);

                    chart.draw(view, options);
                }, packages:['corechart'] });
            }, 1);
        };


    // -- Events
    $(window).on('resize', function () {
        timer = 0;
        triggerRedraw = true;
    });
    window.setInterval(function () {
        timer += 1;

        if (triggerRedraw && timer > 10) {
            triggerRedraw = false;
            $('#chart').html('');
            updateChart();
        }
    }, 10);


    // -- Do once
    init();

})(jQuery);
