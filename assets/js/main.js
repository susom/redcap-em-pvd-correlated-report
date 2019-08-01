CorrelatedReportConfig = {
    init: function () {
        /**
         * track drag and drop to append field filter inputs
         */
        $(".instruments-fields").sortable({
            connectWith: ".connectedSortable",
            stop: function (event, ui) {
                var $element = $(ui.item[0]);
            },
            remove: function (event, ui) {
                var $newELem = ui.item.clone();
                CorrelatedReportConfig.appendInputs($newELem);
                $newELem.appendTo('.filters-fields');
                $(this).sortable('cancel');
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
        $('.secondary-instrument').change(function () {
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


        /**
         * submit form
         */
        $("#correlated-report-submit").click(function (e) {
            e.preventDefault();
            var data = $("#correlated-report").serializeArray();
            CorrelatedReportConfig.submitReport(data);
        });
    },
    submitReport: function (data) {
        $.ajax({
            url: $("#report-submit").val(),
            data: data,
            timeout: 60000000,
            type: 'POST',
            dataType: 'json',
            success: function (response) {

                var data = response.data;
                var columns = response.columns;
                columns.defaultContent = '';
                $("#filters-row").slideUp();
                $('#report-result').DataTable({
                    dom: 'Bfrtip',
                    data: data,
                    columns: CorrelatedReportConfig.prepareTableHeaders(columns),
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
                });
            },
            error: function (request, error) {
                alert("Request: " + JSON.stringify(request));
            }
        });
    },
    prepareTableHeaders: function (columns) {
        var arr = [];
        for (var i = 0; i < columns.length; i++) {
            arr.push(
                {
                    data: columns[i],
                    defaultContent: "<i>Not set</i>",
                    title: columns[i],
                }
            );
        }
        return arr;
        ;
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
    appendInputs: function (element) {
        $.ajax({
            url: $("#base-url").val(),
            data: {field_name: element.data('field'), redcap_csrf_token: $("#redcap_csrf_token").val()},
            type: 'POST',
            success: function (data) {
                data = ' ' + data + CorrelatedReportConfig.appendContactInput();
                element.append(data);
                //TODO APPEND DELETE BUTTON
            },
            error: function (request, error) {
                alert("Request: " + JSON.stringify(request));
            }
        });
    },
    appendContactInput: function () {
        return '<select name="limiter_connector[]"><option value="AND">AND</option><option value="OR">OR</option></select>'
    }
};

CorrelatedReportConfig.init();