<?php

namespace Stanford\CorrelatedReport;

ini_set('max_execution_time', 0);
set_time_limit(0);

/** @var \Stanford\CorrelatedReport\CorrelatedReport $module */

use \REDCap;


try {
    if (!isset($_GET)) {
        throw new \LogicException('You cant be here');
    }

    //load saved content from temp csv file saved when we generated the report
    $module->getCachedResults(filter_var($_GET['session'], FILTER_SANITIZE_STRING));


    $module->csvExport();

    header('Location: ' . $_SERVER['HTTP_REFERER']);
} catch (\LogicException $e) {
    echo $e->getMessage();
}
?>