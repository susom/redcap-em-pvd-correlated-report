<?php

namespace Stanford\CorrelatedReport;

/** @var \Stanford\CorrelatedReport\CorrelatedReport $module */

use \REDCap;

?>
<div class="accordion" id="accordionExample">
    <h2>Search Criteria</h2>
    <?php
    $instruments = REDCap::getInstrumentNames();
    $event = $module->getFirstEventId();
    foreach ($instruments as $key => $instrument) {

        ?>
        <div class="card">
            <div class="card-header" id="<?php echo $key ?>-parent">
                <h5 class="mb-0">
                    <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#<?php echo $key ?>"
                            aria-expanded="true" aria-controls="<?php echo $key ?>">
                        <?php echo $instrument ?>
                    </button>
                </h5>
            </div>

            <div id="<?php echo $key ?>" class="collapse" aria-labelledby="<?php echo $key ?>-parent"
                 data-parent="#accordionExample">
                <div class="card-body">
                    <ul class="list-group instruments-fields connectedSortable">
                        <?php
                        $fields = REDCap::getFieldNames($key);
                        foreach ($fields as $field) {
                            ?>
                            <li class="list-group-item" data-instrument="<?php echo $key ?>"
                                data-field="<?php echo $field ?>"
                                data-type="<?php echo REDCap::getFieldType($field) ?>"><?php echo $field ?>
                                <input type="hidden" name="limiter_name[]" value="<?php echo $field ?>">
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
</div>