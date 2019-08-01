<?php

namespace Stanford\CorrelatedReport;

/** @var \Stanford\CorrelatedReport\CorrelatedReport $module */

use \REDCap;

?>
<div class="container" style="border:1px solid #cecece;">
    <?php
    $instruments = REDCap::getInstrumentNames();
    ?>
    <h4>Primary Instrument</h4>
    <select name="primary-instrument" id="primary-instrument" class="custom-select" required>
        <option value="">Select Instrument</option>
        <?php
        foreach ($instruments as $key => $instrument) {
            /**
             * if not repeated then we do not want it here
             */
            if (!$module->isRepeatingForm($key)) {
                continue;
            }
            ?>
            <option value="<?php echo $key ?>"><?php echo $instrument ?></option>
            <?php
        }
        ?>
    </select>

    <h5>Select a secondary test/visit</h5>
    <table class="table">
        <thead class="thead-dark">
        <tr>
            <th scope="col">Select</th>
            <th scope="col">Name</th>
            <th scope="col">After/Days</th>
            <th scope="col">Before/Days</th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($instruments as $key => $instrument) {
            /**
             * if not repeated then we do not want it here
             */
            if (!$module->isRepeatingForm($key)) {
                continue;
            }
            ?>
            <tr>
                <th scope="row"><input type="checkbox" data-key="<?php echo $key ?>" value="<?php echo $key ?>"
                                       class="secondary-instrument" name="secondary-instrument[]"></th>
                <td><?php echo $instrument ?></td>
                <td>
                    <input type="text" name="closest-after-<?php echo $key ?>" id="closest-after-<?php echo $key ?>"/>
                </td>
                <td>
                    <input type="text" name="closest-before-<?php echo $key ?>" id="closest-before-<?php echo $key ?>"/>
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>

</div>
