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
            <th scope="col">Closest Type</th>
            <th scope="col">Days</th>
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
                                       name="secondary-instrument"></th>
                <td><?php echo $instrument ?></td>
                <td>
                    <select name="closest-type-<?php echo $key ?>" id="closest-type-<?php echo $key ?>">
                        <option value="c">Closest</option>
                        <option value="<=">Closest Before <=</option>
                        <option value=">=">Closest After >=</option>
                    </select>
                </td>
                <td><input type="text" name="closest-value-<?php echo $key ?>" id="closest-value-<?php echo $key ?>"/>
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>

</div>
