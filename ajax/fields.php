<?php

namespace Stanford\CorrelatedReport;

/** @var \Stanford\CorrelatedReport\CorrelatedReport $module */

use \REDCap;


try {
    if (!isset($_POST)) {
        throw new \LogicException('You cant be here');
    }

    $instrument = filter_var($_POST['key'], FILTER_SANITIZE_STRING);
    $fields = REDCap::getFieldNames($instrument);
    ?>
    <h5><?php echo $instrument ?></h5>
    <div class="container" style="border:1px solid #cecece;">
        <?php
        foreach ($fields as $field) {
            ?>
            <div class="col-2"><?php echo $field ?></div>
            <?php
        }
        ?>
    </div>
    <?php
} catch (\LogicException $e) {

}
?>