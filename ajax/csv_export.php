<?php

namespace Stanford\CorrelatedReport;

/** @var \Stanford\CorrelatedReport\CorrelatedReport $module */

use \REDCap;


try {
    if (!isset($_GET)) {
        throw new \LogicException('You cant be here');
    }

    $module->setInputFromSession(filter_var($_GET['session'], FILTER_SANITIZE_STRING));


    $module->csvExport();

    header('Location: ' . $_SERVER['HTTP_REFERER']);
} catch (\LogicException $e) {
    echo $e->getMessage();
}
?>