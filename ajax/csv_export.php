<?php

namespace Stanford\CorrelatedReport;

/** @var \Stanford\CorrelatedReport\CorrelatedReport $module */

use \REDCap;


try {
    if (!isset($_GET)) {
        throw new \LogicException('You cant be here');
    }

    /**
     * first make sure all input are clean
     */
    $module->sanitizeInputs($_GET);

    /**
     * organize all inputs into array
     */
    $module->classifyInputs($_GET);


    $module->csvExport();

    header('Location: ' . $_SERVER['HTTP_REFERER']);
} catch (\LogicException $e) {
    echo $e->getMessage();
}
?>