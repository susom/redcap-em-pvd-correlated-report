<?php

// Set the namespace defined in your config file
namespace Stanford\CorrelatedReport;

ini_set('max_execution_time', 0);
set_time_limit(0);
require_once(__DIR__ . "/utilities/RepeatingForms.php");

use REDCap;
use \Stanford\Utilities\RepeatingForms;

define('PRIMARY_INSTRUMENT', 'primary-instrument');
define('SECONDARY_INSTRUMENT', 'secondary-instrument');
define('CLOSEST', 'closest');
define('FIELD', 'field');
define('PRIMARY_FIELDS', 'primary_fields');
define('SECONDARY_FIELDS', 'secondary_fields');
define('ON', 'on');
define('OFF', 'off');
define('REPEATING_UTILITY', 'repeating_utility');
/**
 * this to save date field which will be used to filter data for secondary instruments.
 */
define('DATE_IDENTIFIER', 'date_identifier');

/**
 * Class CorrelatedReport
 * @package Stanford\CorrelatedReport
 * @property \Project $project
 * @property int $eventId
 * @property array $inputs
 * @property array $primaryData
 * @property \Stanford\Utilities\RepeatingForms $repeatingUtility
 * @property array $representationArray
 */
class CorrelatedReport extends \ExternalModules\AbstractExternalModule
{

    private $project;


    private $eventId;

    private $inputs;

    private $primaryData;

    private $repeatingUtility;

    private $representationArray = array();

    /**
     * Map for main instrument date field
     * @var array
     */
    private static $mainDateField = array(
        'echo' => 'echodate_echo',
        'ctangio' => 'ctangiodate_ctangio',
        'hosper' => 'hosperdate_hosper',
        'mri' => 'mridate_mri',
        'ptf' => 'pftdate_pft',
        'rhcath' => 'rhcathdate_rhcath',
        'laboratorydata' => 'labdate_laboratorydata',
        'sleepstudy' => 'sleepdate_sleepstudy',
        'walk' => 'walkdataentry_walk',
        'vqscan' => 'vqscandate_vqscan',
    );

    /**
     * CorrelatedReport constructor.
     */
    public function __construct()
    {
        parent::__construct();

        try {
            if (isset($_GET['pid'])) {
                $this->setProject(new \Project(filter_var($_GET['pid'], FILTER_SANITIZE_NUMBER_INT)));

            }
            $this->setEventId($this->getFirstEventId());
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

    }

    /**
     * @return Utilities\RepeatingForms
     */
    public function getRepeatingUtility()
    {
        return $this->repeatingUtility;
    }

    /**
     * @param Utilities\RepeatingForms $repeatingUtility
     */
    public function setRepeatingUtility($repeatingUtility)
    {
        $this->repeatingUtility = $repeatingUtility;
    }


    /**
     * @return mixed
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * @param mixed $eventId
     */
    public function setEventId($eventId)
    {
        $this->eventId = $eventId;
    }

    /**
     * @return mixed
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param $project
     */
    public function setProject($project)
    {
        $this->project = $project;
        $this->setRepeatingFormsEvents();
    }

    private function setRepeatingFormsEvents()
    {
        $this->project->setRepeatingFormsEvents();
    }

    public function isRepeatingForm($key)
    {
        return $this->getProject()->isRepeatingForm($this->getEventId(), $key);
    }

    public function sanitizeInputs()
    {
        foreach ($_POST as $key => $input) {
            $_POST[$key] = preg_replace('/[^a-zA-Z0-9\=\>\>=\<\<=](.*)$/', '', $_POST[$key]);
        }
    }

    public function classifyInputs()
    {
        foreach ($_POST as $key => $input) {
            if ($key == PRIMARY_INSTRUMENT) {
                $this->inputs[PRIMARY_INSTRUMENT]['name'] = $input;
                //load utility for this instrument
                $this->inputs[PRIMARY_INSTRUMENT][REPEATING_UTILITY] = new RepeatingForms($this->getProject()->project_id,
                    $input);
                //also define main date field for primary instrument
                $this->inputs[PRIMARY_INSTRUMENT][DATE_IDENTIFIER] = self::$mainDateField[$input];
            } //this could be multiple values
            elseif ($key == SECONDARY_INSTRUMENT) {
                //I added   $input because we still need the type and value per secondary instrument
                $this->inputs[SECONDARY_INSTRUMENT][$input]['name'] = $input;
                //load utility for this instrument
                $this->inputs[SECONDARY_INSTRUMENT][$input][REPEATING_UTILITY] = new RepeatingForms($this->getProject()->project_id,
                    $input);

                //also define main date field for secondary instrument
                $this->inputs[SECONDARY_INSTRUMENT][$input][DATE_IDENTIFIER] = self::$mainDateField[$input];
            } elseif (strpos($key, CLOSEST) !== false) {
                $field = explode('-', $key);
                $name = end($field);
                if (array_search('value', $field) && is_numeric($input)) {
                    $this->inputs[SECONDARY_INSTRUMENT][$name]['value'] = $input;
                } elseif (array_search('type', $field) && in_array($input, array('>=', "<="))) {
                    $this->inputs[SECONDARY_INSTRUMENT][$name]['type'] = $input;
                }
            } elseif (preg_match('/' . FIELD . '$/', $key) && $input == ON) {
                //remove the last part of string
                $key = str_replace("_" . FIELD, '', $key);
                //now decide if input is for primary or secondary instrument
                if (strpos($key, $this->inputs[PRIMARY_INSTRUMENT]['name']) !== false) {
                    $this->inputs[PRIMARY_FIELDS][$key] = $key;
                } else {
                    //lets group fields based on the the instrument they below to.
                    $instrumentName = explode('_', $key);
                    $this->inputs[SECONDARY_FIELDS][end($instrumentName)][] = $key;
                }
            } else {
                throw new \LogicException('cant define input type');
            }
        }
    }

    public function verifySecondaryInstruments()
    {
        if (isset($this->inputs[SECONDARY_INSTRUMENT])) {
            foreach ($this->inputs[SECONDARY_INSTRUMENT] as $key => $input) {
                //array does not have correct number of elements
                if (count($input) != 5) {
                    throw new \LogicException("$key does not have correct information");
                } elseif (!isset($input['name'])) {
                    throw new \LogicException("$key missing name element");
                } elseif (!isset($input['value'])) {
                    throw new \LogicException("$key missing value element");
                } elseif (!isset($input['type'])) {
                    throw new \LogicException("$key missing type element");
                }
            }
        } else {
            return true;
        }
    }

    private function getPrimaryInstrumentsData()
    {

        //TODO process filters now we will consider all records
        $param = array(
            'fields' => $this->inputs[PRIMARY_FIELDS],
            'return_format' => 'array',
        );
        $data = REDCap::getData($param);

        foreach ($data as $id => $record) {
            /**
             * if record has not data ignore
             */
            if (!isset($record['repeat_instances'])) {
                continue;
            } else {
                $this->primaryData[$id]['primary'] = $record['repeat_instances'][$this->getProject()->firstEventId][$this->inputs[PRIMARY_INSTRUMENT]['name']];

                //TODO add demographic data to the array
                //$this->primaryData[$id]['demographics'] = $this->getRecordDataViaSecondaryFilter($id);
            }

        }
    }

    private function processSecondaryInstrumentsData()
    {
        foreach ($this->primaryData as $id => $record) {
            //we might multiple records from primary instruments
            $primaryRecords = $record['primary'];
            foreach ($primaryRecords as $key => $primaryRecord) {
                //from date identifier we will process query.
                $date = $primaryRecord[$this->inputs[PRIMARY_INSTRUMENT][DATE_IDENTIFIER]];

                //there might be multiple secondary instruments
                foreach ($this->inputs[SECONDARY_INSTRUMENT] as $name => $instrument) {
                    /**
                     * now attach resulted array from secondary into the primary one.
                     */
                    $this->primaryData[$id]['primary'][$key]['record_id'] = $id;
                    $secondary = $this->getSecondaryInstrumentData($date, $instrument, $id);
                    if (!empty($secondary)) {
                        //now lets flatten the final row for representation
                        foreach ($secondary as $row) {
                            $temp = array_merge($this->primaryData[$id]['primary'][$key], $row);
                            $this->representationArray['data'][] = $this->flattenArray($temp);
                            $this->saveArrayColumns(array_keys($temp));
                        }
                    } else {
                        //push primary to representation array
                        $this->representationArray['data'][] = $this->flattenArray($this->primaryData[$id]['primary'][$key]);
                        $this->saveArrayColumns(array_keys($this->primaryData[$id]['primary'][$key]));
                    }

                }
            }
        }
    }

    private function flattenArray($array)
    {
        foreach ($array as $key => $el) {
            if (is_array($el)) {
                $tempArr = array();
                array_walk_recursive($el, 'childArray', $tempArr);
                //todo get the option values
                $array[$key] = '';
            }
        }
        return $array;
    }

    function childArray($item, $key)
    {
        return "$key: $item\n";
    }

    private function saveArrayColumns($keys)
    {
        if (!isset($this->representationArray['columns'])) {
            $this->representationArray['columns'] = $keys;
        } else {
            $this->representationArray['columns'] = array_merge($keys, $this->representationArray['columns']);
            //make sure no duplication
            $this->representationArray['columns'] = array_unique($this->representationArray['columns']);
        }
    }

    private function getSecondaryInstrumentData($date, $instrument, $recordId)
    {
        $dateField = $this->inputs[SECONDARY_INSTRUMENT][$instrument['name']][DATE_IDENTIFIER];
        $operation = $instrument['type'];

        if (!isset($this->inputs[SECONDARY_INSTRUMENT][$instrument['name']]['data'])) {
            $param = array(
                'fields' => $this->inputs[SECONDARY_FIELDS][$instrument['name']],
                'return_format' => 'array',
            );
            $this->inputs[SECONDARY_INSTRUMENT][$instrument['name']]['data'] = REDCap::getData($param);
        }

        $result = array();
        list($start, $end) = $this->processSecondaryTimeFilter($operation, $date, $instrument['value']);
        //get from secondary the records for id we passed
        $records = $this->inputs[SECONDARY_INSTRUMENT][$instrument['name']]['data'][$recordId]['repeat_instances'][$this->getFirstEventId()][$instrument['name']];

        foreach ($records as $record) {
            if (strtotime($record[$dateField]) >= strtotime($start) && strtotime($record[$dateField]) <= strtotime($end)) {
                $result[] = $record;
            }
        }
        return $result;
    }


    private function processSecondaryTimeFilter($operation, $date, $value)
    {
        if ($operation == '>=') {
            $time = strtotime($date) + $value * 24 * 60 * 60;
            return array(date('Y-m-d H:i:s', strtotime($date)), date('Y-m-d H:i:s', $time));
        } elseif ($operation == '<=') {
            $time = strtotime($date) - $value * 24 * 60 * 60;
            return array(date('Y-m-d H:i:s', $time), date('Y-m-d H:i:s', strtotime($date)));

        } else {
            throw new \LogicException('Unknown operation');
        }
    }


    public function generateReport()
    {
        $this->getPrimaryInstrumentsData();
        //TODO check if secondary instrument required before process
        $this->processSecondaryInstrumentsData();

        //finally display content
        $this->displayContent();
    }

    private function displayContent()
    {
        echo json_encode($this->representationArray);
    }
}
