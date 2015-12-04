(function ($, undefined) {
    'use strict';

    // Make menu sticky
    var
        $window        = $(window),
        pageHeight     = $(document).outerHeight(),
        $frame         = $('#frame'),
        $footer        = $('#page-footer'),

        viewportHeight,
        framePadding,
        footerHeight,

        resizeViewport = function () {
            viewportHeight = $window.height();
            footerHeight   = $footer.outerHeight();
            framePadding   = ($frame.outerHeight() - $frame.innerHeight());
        },

        resetFooter = function () {
            if ($footer.hasClass('fixed')) {
                $footer.removeClass('fixed');
                $frame.css('padding-bottom', framePadding + 'px');
            }
        },

        moveFooter = function () {
            var 
                allowence = 39,

                viewportTopY,
                viewportBottomY;
            
            // Check if page == viewport
            if (pageHeight <= viewportHeight) {
                return;
            }

            // Check if viewport is at bottom of page, within reason
            viewportTopY    = $window.scrollTop();
            viewportBottomY = viewportTopY + viewportHeight;

            if (pageHeight - viewportBottomY < allowence) {
                resetFooter();
                return;
            }

            if (!$footer.hasClass('fixed')) {
                $frame.css('padding-bottom', (framePadding + footerHeight) + 'px');
                $footer.addClass('fixed');
            }
        };


    $window.on('scroll', moveFooter);
    $window.on('resize', resizeViewport);

    resizeViewport();
    moveFooter();

    window.setTimeout(function () {
        pageHeight = $(document).outerHeight();
    }, 100);

})(jQuery);

// JavaScript Document
(function ($) {
	'use strict';

	var
		$batteryLevel = $('#battery-level'),

		updateBattery = function (deviceStatus) {
			var level = !!deviceStatus && !!deviceStatus.battery ? parseInt(deviceStatus.battery, 10) : false,
				html  = '<div class="level_' + (Math.ceil(level / 10) || 0) + '">' +
					'	<div></div>' +
					'	<p>' + (level || '--') + '%</p>' +
					'</div>';

			$batteryLevel.html(html);
		},

		getDeviceStatus = function () {
			if (!!$batteryLevel.length) {
				$.ajax({
					cache: false,
					success: function (d) {
						updateBattery(d);
					},
					error: function () {
						updateBattery();
					},
					url: '../../device-status.json'
				});
			}
		},

		intervals = {
			device: setInterval(getDeviceStatus, 5000)
		};

	getDeviceStatus();
	
})(jQuery);

(function ($, undefined) {
    'use strict';

    // -- Group options into columns -- //
    var NUM_COLUMNS      = 2,
        COLUMN_STRUCTURE = '<div class="option-column-' + NUM_COLUMNS + '" />',

        columnWidth      = 100 / NUM_COLUMNS - 5; // percent of available width

    $('.options').each(function () {
        var $options      = $('.option', this),
            numOptions    = $options.length,
            rowsPerColumn = Math.ceil(numOptions / NUM_COLUMNS),
            $columnStructure = $(COLUMN_STRUCTURE),
            i;

        for (i = 0; i < NUM_COLUMNS; i+=1) {
            $options.slice(i * rowsPerColumn, (i + 1) * rowsPerColumn).wrapAll($columnStructure);
        }
    });


    // -- Override default tick/untick radio behaviour -- //
    //             so radios can be unticked
    var checkInputs = function (name) {
            $('input[name="' + name + '"]').removeClass('checked').each(function () {
                var $input = $(this);

                if ($input.prop('checked')) {
                    $input.addClass('checked');
                }
            });
        },
        names = {};

    $('input[type="radio"] + label, input[type="checkbox"] + label').on('click', function (e) {
        var $label  = $(this),
            $input  = $label.siblings('input[type="radio"], input[type="checkbox"]');
            //name    = $input.attr('name');
            //checked = $input.prop('checked');

        e.preventDefault();

        /*$('input[name="' + name + '"]').removeClass('checked');

        if (checked) {
            $input.prop('checked', false).removeClass('checked');
        } else {
            $input.prop('checked', true).addClass('checked');
        }

        $input.trigger('change');*/

        $input
            .prop('checked', !$input.prop('checked'))
            .trigger('change'); // onchange isn't automatically triggered when set with JavaScript

        /*if ($input.attr('type') === 'radio') {
            radioClasses(name);
        }*/
    });

    $('input[type="radio"], input[type="checkbox"]').each(function () {
        // Get a list of input group names on the page
        names[$(this).attr('name')] = true;
        
    }).on('change', function () {
        // Check input classes when a radio or checkbox is changed
        checkInputs($(this).attr('name'));
    });

    for (var n in names) {
        if (names.hasOwnProperty(n)) {
            checkInputs(n);
        }
    }

})(jQuery);

// **************** //
// ** reveals.js ** //
// **************** //

(function ($, survey, undefined) {
    'use strict';

    var
        playAudio = function (id) {
            //console.log('play audio ' + id);
        },

        playVideo = function (id) {
            //console.log('play video ' + id);
        },

        playMedia = function (e) {
            var $button = $(this);
            //console.log('play media ' + $button.data('textcode'));
        };
    
    
    // Create media buttons
    if (survey.configVarExists('settings', 'media') && !!survey.config.settings.media) {
        $('[data-textcode]').each(function () {
            var $this        = $(this),
                textcode     = $this.data('textcode'),
                $mediaButton = $('<button class="media-button" data-textcode="' + textcode + '" type="button"></button>');
            
            $this.append($mediaButton);

            $mediaButton.on('mousedown', playMedia);
        });

    }
})(jQuery, survey);

// **************** //
// ** reveals.js ** //
// **************** //

(function ($, Modernizr) {
    'use strict';

    $('.radio').each(function () {
        var $option = $('input[data-special="other"]', this);

        if ($option.length) {
            var questionID = $option.attr('name'),
                $specify   = $('#' + questionID + '-specific'),

                checkOptions = function () {
                    if ($option.is(':checked') ) {
                        $specify.slideDown();
                    } else {
                        $specify.slideUp();
                    }
                };

            $('input[name="' + questionID + '"]').on('change', function () {
                checkOptions();

                var $input = $('input', $specify);
                if (!!$input.val()) {
                    $input.select();
                } else if (!!Modernizr.input.placeholder) {
                    $input.focus();
                }
            });
            
            $specify.hide();
            checkOptions();
        }
    });

    $('.select').each(function () {
        var $option  = $('option[data-special="other"]', this);

        if ($option.length) {
            var $select    = $('select', this),
                questionID = $select.attr('name'),
                $specify   = $('#' + questionID + '-specific'),

                checkOptions = function () {
                    if ($option.is(':selected') ) {
                        $specify.slideDown();
                    } else {
                        $specify.slideUp();
                    }
                };

            $select.on('change', function () {
                checkOptions();
                
                var $input = $('input', $specify);
                if (!!$input.val()) {
                    $input.select();
                } else if (!!Modernizr.input.placeholder) {
                    $input.focus();
                }
            });
            
            $specify.hide();
            checkOptions();
        }
    });


    $('.time').each(function () {
        var $this     = $(this),
            $hours    = $('select[data-unit="hours"]', $this),
            $mins     = $('select[data-unit="mins"]',  $this),
            $input    = $('input', $this),
            delimiter = $this.attr('data-delimiter'),

            getTime = function () {
                var str   = '',
                    hours = $hours.val(),
                    mins  = $mins.val();
                
                if (hours !== '--' && mins !== '--') {
                    str = hours + delimiter + mins;
                }
                
                $input.val(str);
            };
        
        $hours.on('change', getTime);
        $mins.on('change', getTime);
    });

})(jQuery, Modernizr);

(function ($, survey, undefined) {
    'use strict';

    var
        //requiredLabel = '<span class="mark-required">*</span>',

        $matrix    = $('.matrix'),
        $checktrix = $('.checktrix'),
        $progress  = $('#progress'),
        $progressbar = $progress.children('div').eq(0),

        progress  = parseInt($progress.attr('data-value'), 10);
    

    // -- Mark each required answer -- //
    if (!!survey.config.settings.markRequiredAnswers) {
        $('.question').each(function () {
            // Check if required
            var $question = $(this),
                required = false;

            $('input', this).each(function () {
                if ($(this).prop('required')) {
                    required = true;
                }
            });
            
            // Apply label
            if (required) {
                //$('.fieldset .title', this).append(' ' + requiredLabel);
                $question.addClass('required');
            }
        });
    }


    // -- Find alternate options -- //
    if (!!survey.config.alternatives) {
        $.each(survey.config.alternatives, function (was, now) {
            var checkReplace = function () {
                var $this = $(this);
                if ( $this.html().toLowerCase() === was.toLowerCase() ) {
                    $this.html(now);
                }
            };
            $('.options').children('li').children('label').each(checkReplace);
            $matrix.find('th').each(checkReplace);
            $checktrix.find('th').each(checkReplace);
        });
    }

    // -- Autofocus the first textual input -- //
    //$('input[type="text"]:visible, input[type="number"]:visible, textarea:visible').eq(0).focus();


    // -- Plugins -- //
    /*$inputs.boxUpInputs({
        deselect_radios: true
    });

    $matrix.find('input').formControls({
        deselect_radios: true
    });

    $checktrix.find('input').formControls({
        deselect_radios: true
    });

    $('.date').find('input').eq(0).datepicker({
        dateFormat: 'dd/mm/yy',
        changeMonth: true,
        changeYear: true,
        showAnim: 'slideDown'
    });
    
    $('select').selectBoxIt({
        theme: 'jqueryui'
    }).data('selectBoxIt');

    $progressbar.progressbar({
        value: progress
    });*/

})(jQuery, survey);

// ***************** //
// ** timeouts.js ** //
// ***************** //

(function ($, survey, undefined) {
    'use strict';

    if (survey.config.timeouts && survey.config.timeouts.type !== 'none') {
        var $dialogTimeout = $('#dialogTimeout'),
            t              = 0,
            action         = survey.config.timeouts.type,
            dialogTime     = survey.config.timeouts.warningTime || 0,
            actionTime     = survey.config.timeouts.actionTime || 0,
            mins           = Math.floor(dialogTime / 60),
            strDialogTime  = '',

            dialogButtons = {
                'Exit survey without submitting': function () {
                    exit_survey();
                }
            },

            submit_survey = function () {
                window.location = 'process_response.php?sid=' + survey.surveyID + '&did=' + survey.deviceID;
            },

            exit_survey = function () {
                window.location = survey.config.urls.menu;
            },

            closeDialog = function () {
                $(this).dialog('close');
            };


        if (action === 'submit') {
            dialogButtons['Submit now and exit'] = submit_survey;
        }
        dialogButtons['Continue survey'] = closeDialog;

        $dialogTimeout.dialog({
            autoOpen: false,
            modal: true,
            resizable: false,
            draggable: false,
            width: $(window).width() / 100 * 70,
            buttons: dialogButtons,
            close: function () {
                t = 0;
            }
        });

        if (dialogTime > 0) {
            setInterval(function () {
                t += 1;

                if (t - dialogTime === actionTime) {
                    switch (action) {
                        case 'exit':
                            exit_survey();
                            break;

                        case 'submit':
                            submit_survey();
                            break;
                    }

                } else if (t === dialogTime) {
                    $dialogTimeout.dialog('open');

                }

                $('.secondsLeft', $dialogTimeout).text(dialogTime + actionTime - t);
            }, 1000);


            $('.container')
                .on('mousemove', function () {
                    t = 0;
                })
                .on('keydown', function () {
                    t = 0;
                });


            if (mins === 0) {
                strDialogTime = dialogTime + ' seconds';
            } else if (mins === 1) {
                strDialogTime = 'a minute';
            } else {
                strDialogTime = mins + ' minutes';
            }

            $('.dialogTime', $dialogTimeout).text(strDialogTime);
        }

        switch (action) {
            case 'exit':
                $('.timeoutAction').text('finish without submitting your responses');
                break;

            case 'submit':
                $('.timeoutAction').text('automatically submit your responses and exit');
                break;
        }
    }

})(jQuery, survey);

(function ($, survey, undefined) {
    'use strict';

    var
        // == Vars == //

        submitForm    = false, //Control conditions of form submission
        submitted     = false,

        $form      = $('form'),
        $inputs    = $('input'),

        $btnPrev  = $('#btnPrev'),
        $btnNext  = $('#btnNext'),


        // == Functions == //

        //Validate all questions (necessary to update 'next' button status)
        isValid = function (showWarnings) {
            var valid = true;   //innocent until proven guilty
            
            //Loop through each question to check if it's valid
            $('.question').each(function () {
                
                //Default vars
                var $inputerrors = $('.input-errors', this),
                    required     = false,
                    answered     = true,
                    $fieldset    = $('.fieldset', this),
                    $inputNode,
                    inputName;
                
                //Change vars for some question types
                if ($fieldset.hasClass('multiline')) {
                    $inputNode = $('textarea', this).eq(0);
                    inputName  = $inputNode.attr('name');
                    required   = $inputNode.attr('required') === 'required';
                    answered   = $('textarea[name="' + inputName + '"]').val().length > 0;
                    
                } else if ($fieldset.hasClass('radio') || $fieldset.hasClass('checkbox')) {
                    $inputNode = $('input', this).eq(0);
                    inputName  = $inputNode.attr('name');
                    required   = $inputNode.attr('required') === 'required';
                    answered   = $('input[name="' + inputName + '"]:checked').val() !== undefined;
                
                } else if ($fieldset.hasClass('select')) {
                    $inputNode = $('select', this).eq(0);
                    inputName  = $inputNode.attr('name');
                    required   = $inputNode.attr('required') === 'required';
                    answered   = $('select').val() !== undefined && $('select').val() !== '';
                
                } else if ($fieldset.hasClass('matrix') || $fieldset.hasClass('checktrix')) {
                    $('tbody', this).children('tr').each(function () {
                        $inputNode = $('input', this).eq(0);
                        inputName  = $inputNode.attr('name');
                        required   = $inputNode.attr('required') === 'required';
                        answered   = $('input[name="' + inputName + '"]:checked').val() !== undefined;
                        if (required && !answered) {
                            return false;   //Break out of each loop
                        }
                    });
                } else {
                    $inputNode = $('input', this).eq(0);
                    inputName  = $inputNode.attr('name');
                    required   = $inputNode.attr('required') === 'required';
                    answered   = $('input[name="' + inputName + '"]').val().length > 0;
                }
                
                //Check if answer is valid
                if (required && !answered) {
                    if (!!showWarnings) {
                        //Ask the user to go back to this question
                        //if ($inputerrors.css('display') === 'table') {
                        if ($inputerrors.is(':visible')) {
                            // Flash user
                            $inputerrors.fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(100);
                        } else {
                            // Reveal
                            $inputerrors.slideDown();
                        }
                    }
                    valid = false;
                } else {
                    //Clear all errors
                    if ($inputerrors.html() !== '') {
                        $inputerrors.slideUp();
                    }
                }
            });
            
            //Change the button status if necessary
            if (valid) {
                //$btnNext.prop('disabled', false); // Actually disabling the button will mean notices won't be shown
                $btnNext.removeClass('disabled');
            } else {
                //$btnNext.prop('disabled', true);
                $btnNext.addClass('disabled');
            }
            
            return valid;
        };


    // == Events == //

    //Only allow form to be submitted once
    $form.on('submit', function () {
        if (!submitted) {
            //Only allow form to be submitted if valid
            if (submitForm) {
                submitted = true;
                return true;
            }
        }
        return false;
                
    });

    //Navigation button events
    $btnPrev.on('click', function () {
        $('input[name="sc_dir"]').val('bwd');
        submitForm = true;
        $form.submit();
    });

    $btnNext.on('click', function () {
        if (isValid(true)) {
            $('input[name="sc_dir"]').val('fwd');
            submitForm = true;
            $form.submit();
        }
    });

    //Input events
    $inputs.on('change', function () {
        isValid(false);
        return true;
    });
    $('select').on('change', function () {
        isValid(false);
        return true;
    });



    // -- DO ONCE -- //

    // -- Disable 'next' button if necessary -- //
    isValid(false);

})(jQuery, survey);
