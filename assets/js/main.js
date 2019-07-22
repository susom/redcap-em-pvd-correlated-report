CorrelatedReportConfig = {
    init: function () {
        /**
         * track drag and drop to append field filter inputs
         */
        $(".instruments-fields").sortable({
            connectWith: ".connectedSortable",
            stop: function (event, ui) {
                var $element = $(ui.item[0]);
                var type = $element.data('type');
                CorrelatedReportConfig.appendInputs($element, type);
            }
        });

        /**
         * remove input if we drag field to main list
         */
        $(".filters-fields").sortable({
            connectWith: ".connectedSortable",
            stop: function (event, ui) {
                //TODO strip LI from inputs
            }
        });

        /**
         * if you check instrument lets load all its fields as checkboxes to select report headers.
         */
        $('input[name=secondary-instrument]').change(function () {
            var $element = $(this);
            if ($element.prop('checked') == true) {
                CorrelatedReportConfig.getInstrumentFields($element.data('key'), false);
            } else {
                CorrelatedReportConfig.removeInstrumentFields($element.data('key'));
            }
        });

        /**
         * if you check instrument lets load all its fields as checkboxes to select report headers.
         */
        $('#primary-instrument').change(function () {
            var $element = $(this);
            CorrelatedReportConfig.getInstrumentFields($element.find(":selected").val(), true);
        });
    },
    removeInstrumentFields: function (key) {
        $("#" + key + '-fields').remove();
    },
    getInstrumentFields: function (key, primary) {
        $.ajax({
            url: $("#instrument-fields").val(),
            data: {key: key, primary: primary, redcap_csrf_token: $("#redcap_csrf_token").val()},
            type: 'POST',
            success: function (data) {
                if (primary) {
                    $('#primary-fields').html(data);
                } else {
                    $('#instruments-fields').append(data);
                }

            },
            error: function (request, error) {
                alert("Request: " + JSON.stringify(request));
            }
        });
    },
    appendInputs: function (element, type) {
        $.ajax({
            url: $("#base-url").val(),
            data: {field_name: element.text(), redcap_csrf_token: $("#redcap_csrf_token").val()},
            type: 'POST',
            success: function (data) {
                element.append(data);
                //TODO APPEND DELETE BUTTON
            },
            error: function (request, error) {
                alert("Request: " + JSON.stringify(request));
            }
        });
    }
};

CorrelatedReportConfig.init();