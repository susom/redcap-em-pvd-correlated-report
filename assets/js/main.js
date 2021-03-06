CorrelatedReportConfig = {
    data: {},
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
            CorrelatedReportConfig.data = $("#correlated-report").serializeArray();
            CorrelatedReportConfig.submitReport();
        });

        /**
         * export csv
         */
        $("#csv-export").click(function (e) {
            e.preventDefault();
            var link = $("#csv-export-url").val() + "&session=" + $("#inputs-name").val();
            window.open(link, '_blank');
        });
        /**
         * Show filters
         */
        $("#show-filters").click(function (e) {
            $("#filters-row").slideDown();
            CorrelatedReportConfig.datatable.destroy();
            $("#report-result").html('');
            $("#buttons-area").addClass('d-none');
        });


        /**
         * Delete filter
         */
        $(document).on('click', '.delete-criteria', function () {
            $(this).closest('.list-group-item').remove();
        });



        $body = $("body");

        /**
         * add loader in ajax
         */
        $(document).on({
            ajaxStart: function () {
                $body.addClass("loading");
            },
            ajaxStop: function () {
                $body.removeClass("loading");
            }
        });
    },
    submitReport: function () {
        $.ajax({
            url: $("#report-submit").val(),
            data: CorrelatedReportConfig.data,
            timeout: 60000000,
            type: 'POST',
            dataType: 'json',
            success: function (response) {

                var data = response.data;
                var columns = response.columns;
                //to export large data we saved input into session then pass its name to be used in export
                $("#inputs-name").val(response.session);

                columns.defaultContent = '';
                $("#filters-row").slideUp();
                $("#buttons-area").hide().removeClass('d-none').slideDown();
                CorrelatedReportConfig.datatable = $('#report-result').DataTable({
                    dom: 'Bfrtip',
                    data: data,
                    pageLength: 50,
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
        return '<select name="limiter_connector[]"><option value="AND">AND</option><option value="OR">OR</option></select><button type="button" class="delete-criteria close" aria-label="Close">\n' +
            '  <span aria-hidden="true">&times;</span>\n' +
            '</button>'
    }
};

CorrelatedReportConfig.init();

CorrelatedReportConfig.datatable = null;
