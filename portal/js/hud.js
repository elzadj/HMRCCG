(function ($, vex, undefined) {
    'use strict';

    var
        // Vars
        intervals = {},

        $connectivity = $('.connectivity'),
        $batteryLevel = $('.battery-level'),
        $uploadStatus = $('.upload-status'),


        // Functions
        databaseErrorDialog = function () {
            vex.dialog.confirm({
                contentCSS: { 'min-width': '500px' },
                message: '<p><strong>Cannot connect to database</strong></p>\n<p>There seems to be a problem connecting to the database.</p>\n<p>This means <strong>new survey responses will not be saved</strong> and older responses cannot be uploaded.</p>\n<p>Restarting the device may solve the issue, but if not and this message shows again, please contact Elephant Kiosks on 01223 812737 for further support.</p>',
                buttons: [
                    $.extend({}, vex.dialog.buttons.YES, {
                        text: 'Shut down',
                        click: shutdown
                    }),
                    $.extend({}, vex.dialog.buttons.YES, {
                        text: 'Restart',
                        click: restart
                    }),
                    $.extend({}, vex.dialog.buttons.NO, {
                        text: 'Cancel'
                    })
                ]
            });
        },


        init = function () {
            intervals = {
                device: setInterval(getDeviceStatus, 5000)
            };

            getDeviceStatus();
            
            if ($uploadStatus.length) {
                getDatabaseStatus();
                intervals.database = setInterval(getDatabaseStatus, 5000);
            }
        },

        getDatabaseStatus = function () {
            //console.log('func: getDatabaseStatus()');
            
            $.ajax({
                cache: false,
                dataType: 'json',
                success: function (d) {
                    updateDatabase(d);
                },
                error: function () {
                    updateDatabase();
                },
                url: '-dbstatus.php'
            });
        },

        getDeviceStatus = function () {
            //console.log('func: getDeviceStatus()');

            $.ajax({
                cache: false,
                dataType: 'json',
                success: function (d) {
                    updateBattery(d);
                    updateConnectivity(d);
                },
                error: function () {
                    updateBattery();
                    updateConnectivity();
                },
                url: '../device-status.json'
            });
        },

        restart = function () {
            try {
                window.external.InitScriptInterface();
                SiteKiosk.RebootWindows();
            } catch(e) {
                alert('Could not restart device');
            }
        },

        shutdown = function () {
            try {
                window.external.InitScriptInterface();
                SiteKiosk.ShutdownWindows();
            } catch(e) {
                alert('Could not shut down device');
            }
        },

        shutdownDialog = function () {
            vex.dialog.confirm({
                message: 'Do you really want to shut down the device?',
                buttons: [
                    $.extend({}, vex.dialog.buttons.YES, {
                        text: 'Shut down'
                    }),
                    $.extend({}, vex.dialog.buttons.NO, {
                        text: 'Cancel'
                    })
                ],
                callback: function(value) {
                    if (!!value) {
                        shutdown();
                    }
                }
            });
        },


        updateBattery = function (deviceStatus) {
            var level = !!deviceStatus && !!deviceStatus.battery ? parseInt(deviceStatus.battery, 10) : false,
                html;

            if (!!level) {
                html = '<div class="level_' + (Math.ceil(level / 10) || 0) + '">' +
                    '   <div></div>' +
                    '   <p>' + level + '%</p>' +
                    '</div>';

                $batteryLevel.html(html);
            }
        },

        updateConnectivity = function (deviceStatus) {
            var connectivity = !!deviceStatus && !!deviceStatus.connectivity ? deviceStatus.connectivity : false,
                status       = !!connectivity ? (connectivity === 2 ? 'on' : 'partial') : 'off',
                html         = '<div class="connectivity_' + status + '"></div>';

            $connectivity.html(html);
        },

        updateDatabase = function (dbStatus) {
            var error = !!dbStatus.error ? dbStatus.error : false,
                ready = !!dbStatus && !!dbStatus.ready ? dbStatus.ready : 0,
                html  = 'Waiting to upload: ' + ready;

            if (error) {
                databaseErrorDialog();
                $('.upload-status').html('<img src="img/cross.png" alt="">Database error');
                window.clearInterval(intervals.database);

            } else {
                $uploadStatus.html(html);
            }
        };


    // Events
    $('#btn-shutdown').on('click', shutdownDialog);


    // Do once
    init();
    
})(jQuery, vex);
