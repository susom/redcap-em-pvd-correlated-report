<?php

namespace Stanford\CorrelatedReport;

/** @var \Stanford\CorrelatedReport\CorrelatedReport $module */

use \REDCap;


try {
    if (!isset($_POST)) {
        throw new \LogicException('You cant be here');
    }

    /**
     * first make sure all input are clean
     */
    $module->sanitizeInputs();

    /**
     * organize all inputs into array
     */
    $module->classifyInputs();

    /**
     * verify secondary instruments has all details
     */
    $module->verifySecondaryInstruments();

    $module->generateReport();
} catch (\LogicException $e) {
    echo $e->getMessage();
}
?>