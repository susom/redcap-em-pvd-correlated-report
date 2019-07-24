<!doctype html>
<html lang="en">
<head>
    <title>React PHP starter Kit</title>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
          integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo $module->getUrl('assets/css/main.css') ?>">
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"
            integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN"
            crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"
            integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q"
            crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"
            integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
            crossorigin="anonymous"></script>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <style>
        body {
            word-wrap: break-word;
        }
    </style>
</head>
<body>

<div id="app" class="container">
    <input type="hidden" name="base-url" id="base-url"
           value="<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . SERVER_NAME . APP_PATH_WEBROOT . 'DataExport/report_filter_ajax.php?pid=' . PROJECT_ID ?>">
    <input type="hidden" name="instrument-fields" id="instrument-fields"
           value="<?php echo $module->getUrl("ajax/fields.php") ?>">
    <input type="hidden" name="report-submit" id="report-submit"
           value="<?php echo $module->getUrl("ajax/submit.php") ?>">
    <input type="hidden" name="redcap_csrf_token" id="redcap_csrf_token" value="<?php echo System::getCsrfToken() ?>">
    <div class="row p-1">
        <h1>PVD Report</h1>
    </div>
    <div class="row p-1">
        <div class="col-lg-4">
            <?php
            require_once($module->getModulePath() . "view/instruments.php");
            ?>
        </div>
        <div class="col-lg-8">
            <!-- Correlated Report form -->
            <form name="correlated-report" id="correlated-report">
                <div class="row p-1">
                    <?php
                    require_once($module->getModulePath() . "view/filters.php");
                    ?>
                </div>
                <div class="row p-1">
                    <?php
                    require_once($module->getModulePath() . "view/procedures.php");
                    ?>
                </div>
                <div class="row p-1">
                    <?php
                    require_once($module->getModulePath() . "view/fields.php");
                    ?>
                </div>
                <div class="row p-1">
                    <div class="col text-center">
                        <button type="submit" name="correlated-report-submit" class="btn btn-primary"
                                id="correlated-report-submit">Generate
                        </button>
                    </div>
                </div>
            </form>
            <!-- END Correlated Report form -->
        </div>
    </div>
</div>

<script src="<?php echo $module->getUrl('assets/js/main.js') ?>"></script>
</body>
</html>