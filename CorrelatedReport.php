<?php

// Set the namespace defined in your config file
namespace Stanford\CorrelatedReport;

include_once "emLoggerTrait.php";
ini_set('max_execution_time', 0);
set_time_limit(0);
require_once(__DIR__ . "/utilities/RepeatingForms.php");


use REDCap;
use \Stanford\Utilities\RepeatingForms;

define('PRIMARY_INSTRUMENT', 'primary-instrument');
define('SECONDARY_INSTRUMENT', 'secondary-instrument');
define('MERGED_INSTRUMENT', 'merged-instrument');
define('DATATABLE_PAGE', 'datatable-page');
define('CLOSEST', 'closest');
define('FIELD', 'field');
define('LIMITER', 'limiter');
define('PRIMARY_FIELDS', 'primary_fields');
define('SECONDARY_FIELDS', 'secondary_fields');
define('ON', 'on');
define('OFF', 'off');
define('REPEATING_UTILITY', 'repeating_utility');
define('ROWS_PER_CALL', 1000);
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
 * @property array $patientFilter
 * @property string $patientFilterText
 * @property int $currentPageNumber
 * @property array $dataDictionary
 * @property array $mergedInstrument
 */
class CorrelatedReport extends \ExternalModules\AbstractExternalModule
{
    use emLoggerTrait;

    private $project;


    private $eventId;

    private $inputs;

    private $primaryData;

    private $repeatingUtility;

    private $representationArray = array();

    private $patientFilter = array();

    private $patientFilterText;

    private $currentPageNumber;

    private $dataDictionary = array();

    private $mergedInstrument = array();
    /**
     * Map for main instrument date field
     * @var array
     */
    public static $mainDateField = array(
        'echo' => 'echodate_echo',
        'ctangio' => 'ctangiodate_ctangio',
        'hosper' => 'hosperdate_hosper',
        'mri' => 'mridate_mri',
        'pft' => 'pftdate_pft',
        'rhcath' => 'rhcathdate_rhcath',
        'laboratorydata' => 'labdate_laboratorydata',
        'sleepstudy' => 'sleepdate_sleepstudy',
        'walk' => 'walkdate_walk',
        'vqscan' => 'vqscandate_vqscan',
        'visit' => 'visitdate_visit',
        'whodx' => 'visitdate_whodx',
        'workingdx' => 'visitdate_workingdx',
        'specificmed' => 'visitdate_specificmed',
        //Latestdiagnosis has no DATE TODO Ask Susan about that
        //Socialhx has no DATE TODO Ask Susan about that
        'priorsurgery' => 'surgerydate_priorsurgery'

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
                $this->setEventId($this->getFirstEventId());

                $this->setDataDictionary(REDCap::getDataDictionary($this->getProject()->project_id, 'array'));

                $temp = json_decode($this->getProjectSetting("dates_identifiers"), true);
                if (!empty($temp)) {
                    self::$mainDateField = $temp;
                }


            }

        } catch (\Exception $e) {
            echo $e->getMessage();
        }

    }

    /**
     * @return array
     */
    public function getDataDictionary()
    {
        return $this->dataDictionary;
    }

    /**
     * @param array $dataDictionary
     */
    public function setDataDictionary($dataDictionary)
    {
        $this->dataDictionary = $dataDictionary;
    }

    /**
     * @return array
     */
    public function getDataDictionaryProp($prop)
    {
        return $this->dataDictionary[$prop];
    }
    /**
     * @return string
     */
    public function getPatientFilterText()
    {
        return $this->patientFilterText;
    }

    /**
     * @param string $patientFilterText
     */
    public function setPatientFilterText($patientFilterText)
    {
        $this->patientFilterText = $patientFilterText;
    }

    /**
     * @return mixed
     */
    public function getCurrentPageNumber()
    {
        return $this->currentPageNumber;
    }

    /**
     * @param mixed $currentPageNumber
     */
    public function setCurrentPageNumber($currentPageNumber)
    {
        $this->currentPageNumber = $currentPageNumber;
    }

    /**
     * @return mixed
     */
    public function getPatientFilter()
    {
        return $this->patientFilter;
    }

    /**
     * @param mixed $patientFilter
     */
    public function setPatientFilter($patientFilter)
    {
        $this->patientFilter = $patientFilter;
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

    public function sanitizeInputs($type = array())
    {
        if (empty($type)) {
            $type = $_POST;
        }
        foreach ($type as $key => $input) {
            $type[$key] = preg_replace('/[^a-zA-Z0-9\_\=\>\>=\<\<=](.*)$/', '', $type[$key]);
        }
    }

    private function defineSecondaryInstrument($input)
    {
        if (is_array($input)) {
            foreach ($input as $value) {
                if (!isset($this->inputs[SECONDARY_INSTRUMENT][$value]['name'])) {
                    $this->inputs[SECONDARY_INSTRUMENT][$value]['name'] = $value;

                    //also define main date field for secondary instrument
                    $this->inputs[SECONDARY_INSTRUMENT][$value][DATE_IDENTIFIER] = self::$mainDateField[$value];
                }
            }
        } else {
            if (!isset($this->inputs[SECONDARY_INSTRUMENT][$input]['name'])) {
                $this->inputs[SECONDARY_INSTRUMENT][$input]['name'] = $input;
                //load utility for this instrument
                $this->inputs[SECONDARY_INSTRUMENT][$input][REPEATING_UTILITY] = new RepeatingForms($this->getProject()->project_id,
                    $input);

                //also define main date field for secondary instrument
                $this->inputs[SECONDARY_INSTRUMENT][$input][DATE_IDENTIFIER] = self::$mainDateField[$input];
            }
        }
    }

    /**
     * @param array $names
     * @param array $operators
     * @param array $values
     * @param array $connectors
     */
    private function processPatientFilters($names, $operators, $values, $connectors)
    {
        for ($i = 0; $i < count($names); $i++) {
            //in case there are multiple conditions for same field.
            $parameters = array('value' => $values[$i], 'operator' => $operators[$i], 'connector' => $connectors[$i]);
            $this->patientFilter[$names[$i]][] = $parameters;
        }
        $this->convertPatientFilterToText();
    }

    private function convertPatientFilterToText()
    {
        $text = '';
        $header = '';
        $operator = '';
        foreach ($this->patientFilter as $name => $field) {
            //if not the first but change name lets close parentheses
            if ($header != '' && $header != $name) {
                $text .= " " . $operator;
            }
            //now if name changed open parentheses and flag header to be name
            if ($header != $name) {
                $header = $name;
            }
            $pointer = 0;
            foreach ($field as $filter) {
                // this to now we are at the end of internal array to add operator within or after closing parentheses
                $pointer++;
                if ($pointer == count($field)) {
                    $operator = $filter['connector'];
                    $text .= " [$name] " . $this->processFilterOperation($filter['operator'], $filter['value']);
                } else {
                    $text .= " [$name] " . $this->processFilterOperation($filter['operator'],
                            $filter['value']) . ' ' . $filter['connector'];
                }
            }
        }

        //do not forgot close parentheses for last operator
        $this->setPatientFilterText($text);
    }

    private function processFilterOperation($operator, $value)
    {
        switch ($operator) {
            case 'E':
                return " = '$value'";
                break;
            case 'NE':
                return " != '$value'";
                break;
            case 'LT':
                return " < '$value'";
                break;
            case 'LTE':
                return " <= '$value'";
                break;
            case 'GT':
                return " > '$value'";
                break;
            case 'GTE':
                return " >= '$value'";
                break;
            case 'CONTAINS':
                return " LIKE '%$value%'";
                break;
            case 'NOT_CONTAIN':
                return " NOT LIKE '%$value%'";
                break;
            case 'STARTS_WITH':
                return " NOT LIKE '$value%'";
                break;
            case 'ENDS_WITH':
                return " NOT LIKE '%$value'";
                break;
            default :
                throw new \LogicException('Operator not identified');
        }
    }

    /**
     * load temp csv file of the generated report
     * @param string $session
     */
    public function getCachedResults($session)
    {
        $filename = APP_PATH_TEMP . $session;
        if (file_exists(strtolower($filename))) {
            $handle = fopen($filename, 'r');
            $contents = fread($handle, filesize($filename));
            fclose($handle);
            $this->representationArray = unserialize($contents);
        }
    }

    /**
     * parse POST variables and categorize them to primary, secondary, or field
     * @param array $type
     */
    public function classifyInputs($type = array())
    {
        if (empty($type)) {
            $type = $_POST;
        }
        foreach ($type as $key => $input) {
            if ($key == PRIMARY_INSTRUMENT) {
                $this->inputs[PRIMARY_INSTRUMENT]['name'] = $input;
                //load utility for this instrument
                $this->inputs[PRIMARY_INSTRUMENT][REPEATING_UTILITY] = new RepeatingForms($this->getProject()->project_id,
                    $input);
                //also define main date field for primary instrument
                $this->inputs[PRIMARY_INSTRUMENT][DATE_IDENTIFIER] = self::$mainDateField[$input];
            } //this could be multiple values
            elseif ($key == SECONDARY_INSTRUMENT) {
                $this->defineSecondaryInstrument($input);
            } elseif (strpos($key, CLOSEST) !== false) {
                $field = explode('-', $key);
                $name = end($field);
                if (array_search('before', $field) && is_numeric($input)) {
                    $this->inputs[SECONDARY_INSTRUMENT][$name]['before'] = $input;
                    $this->defineSecondaryInstrument($name);
                } elseif (array_search('after', $field) && is_numeric($input)) {
                    $this->inputs[SECONDARY_INSTRUMENT][$name]['after'] = $input;
                    $this->defineSecondaryInstrument($name);
                }
            } elseif (strpos($key, LIMITER) !== false) {
                if (empty($this->patientFilter)) {
                    $this->processPatientFilters($type['limiter_name'], $type['limiter_operator'],
                        $type['limiter_value'], $type['limiter_connector']);
                } else {
                    continue;
                }
            } elseif (preg_match('/' . FIELD . '$/', $key) && $input == ON) {
                //remove the last part of string
                $key = str_replace("_" . FIELD, '', $key);
                //now decide if input is for primary or secondary instrument
                if ($this->isFieldInPrimaryInstrument($key)) {
                    $this->inputs[PRIMARY_FIELDS][$key] = $key;
                } else {
                    //lets group fields based on the the instrument they below to.
                    $instrumentName = $this->getFieldInstrument($key);
                    $this->inputs[SECONDARY_FIELDS][$instrumentName][] = $key;
                }
            } elseif ($key == DATATABLE_PAGE) {
                $this->setCurrentPageNumber($input);
            } elseif ($type == $_POST) {
                throw new \LogicException('cant define input type');
            }
        }
    }

    private function getFieldInstrument($field)
    {
        return $this->getProject()->metadata[$field]['form_name'];
    }

    private function isFieldInPrimaryInstrument($field)
    {
        if ($this->getProject()->metadata[$field]['form_name'] == $this->inputs[PRIMARY_INSTRUMENT]['name']) {
            return true;
        }
        return false;
    }

    private function isFieldInSecondaryInstrument($field)
    {
        if ($this->getProject()->metadata[$field]['form_name'] == $this->inputs[SECONDARY_INSTRUMENT]['name']) {
            return true;
        }
        return false;
    }

    /**
     * this function return list of records ids that satisfy the main search criteria.
     * @return array
     */
    private function getSearchCriteriaRecords()
    {
        $primary = \REDCap::getRecordIdField();
        $param = array(
            'filterLogic' => $this->getPatientFilterText(),
            'fields' => array($primary),
            'return_format' => 'array',
        );
        return REDCap::getData($param);
    }

    private function getPrimaryInstrumentsData()
    {


        $searchRecords = $this->getSearchCriteriaRecords();
        $param = array(
            'fields' => $this->inputs[PRIMARY_FIELDS],
            'return_format' => 'array',
        );
        $data = REDCap::getData($param);
        //keep only the records exist on both arrays;
        if (!empty($searchRecords)) {
            $data = array_intersect_key($data, $searchRecords);
        }

        foreach ($data as $id => $record) {
            /**
             * if record has not data ignore
             */
            if (!isset($record['repeat_instances'])) {
                continue;
            } else {
                if ($this->isMainInstrument($this->inputs[PRIMARY_INSTRUMENT]['name'])) {
                    $mainField = $this->getMergeInstrumentField($this->inputs[PRIMARY_INSTRUMENT]['name']);
                    foreach ($record['repeat_instances'][$this->getProject()->firstEventId][$this->inputs[PRIMARY_INSTRUMENT]['name']] as $k => $r) {
                        $temp = $r;
                        $temp = array_merge($temp, $this->getMergedRecordDataForID($id, $r[$mainField],
                            $this->inputs[PRIMARY_INSTRUMENT]['name']));
                        $record['repeat_instances'][$this->getProject()->firstEventId][$this->inputs[PRIMARY_INSTRUMENT]['name']][$k] = $temp;
                    }
                }
                $this->primaryData[$id]['primary'] = $record['repeat_instances'][$this->getProject()->firstEventId][$this->inputs[PRIMARY_INSTRUMENT]['name']];

                //TODO add demographic data to the array
                //$this->primaryData[$id]['demographics'] = $this->getRecordDataViaSecondaryFilter($id);
            }
        }
    }

    private function getMergedRecordDataForID($recordId, $mainValue, $instrument)
    {
        $result = array();
        foreach ($this->inputs[MERGED_INSTRUMENT][$instrument] as $subInstrument => $value) {
            if (isset($value['data'][$recordId])) {
                $secondaryField = $this->getMergeInstrumentField($subInstrument, false);
                foreach ($value['data'][$recordId] as $record) {
                    foreach ($record[$this->getProject()->firstEventId][$subInstrument] as $k => $r) {
                        if ($r[$secondaryField] == $mainValue) {
                            # remove the secondary field because its redundant in the view.
                            unset($r[$secondaryField]);
                            foreach ($r as $k => $v) {

                                # if the field is REDCap complete auto-generated field then ignore it
                                if ($this->endsWith($k, '_complete')) {
                                    unset($r[$k]);
                                    continue;
                                }

                                //check if element type from DataDictionary is checkbox or dropdown then get value label instead
                                $prop = $this->getDataDictionaryProp($k);

                                //if not defined in data dictionary then do not display it.
                                if (is_null($prop)) {
                                    //unset($array[$field]);
                                    $prop['field_label'] = $k;
                                }

                                //if dropdown or checkbox get the label instead of numeric value.
                                if ($prop['field_type'] == 'checkbox' || $prop['field_type'] == 'dropdown') {
                                    $r[$k] = $this->getValueLabel($v, $prop);
                                }
                            }
                            $result[$subInstrument] .= implode('-', $r);
                        }
                    }

                }
            }
        }
        return $result;
    }

    private function getMergeInstrumentField($instrument, $main = true)
    {
        if ($main) {
            foreach ($this->mergedInstrument as $record) {
                if ($record['main-instrument'] == $instrument) {
                    return $record['main-instrument-field'];
                }
            }
        } else {
            foreach ($this->mergedInstrument as $record) {
                foreach ($record[$record['main-instrument']] as $subRecord) {
                    if ($subRecord['secondary-instrument'] == $instrument) {
                        return $subRecord['secondary-instrument-field'];
                    }
                }
            }
        }
    }


    /**
     * check if instrument has configured instrument to be attache to it
     * @param $instrument
     * @return bool
     */
    private function isMainInstrument($instrument)
    {
        if (empty($this->mergedInstrument)) {
            return false;
        }
        foreach ($this->mergedInstrument as $value) {
            if ($value['main-instrument'] == $instrument) {
                return true;
            }
        }
        return false;
    }
    private function processSecondaryInstrumentsData()
    {
        foreach ($this->primaryData as $id => $record) {
            //we might multiple records from primary instruments
            $primaryRecords = $record['primary'];
            foreach ($primaryRecords as $key => $primaryRecord) {
                //from date identifier we will process query.
                $date = $primaryRecord[$this->inputs[PRIMARY_INSTRUMENT][DATE_IDENTIFIER]];
                $this->primaryData[$id]['primary'][$key]['record_id'] = $id;
                if (isset($this->inputs[SECONDARY_INSTRUMENT])) {
                    //there might be multiple secondary instruments
                    foreach ($this->inputs[SECONDARY_INSTRUMENT] as $name => $instrument) {
                        /**
                         * now attach resulted array from secondary into the primary one.
                         */

                        $secondary = $this->getSecondaryInstrumentData($date, $instrument, $id);
                        if (!empty($secondary)) {
                            //now lets flatten the final row for representation
                            foreach ($secondary as $row) {
                                if ($this->isMainInstrument($instrument['name'])) {
                                    $mainField = $this->getMergeInstrumentField($instrument['name']);
                                    $temp = $row;
                                    $temp = array_merge($temp,
                                        $this->getMergedRecordDataForID($id, $row[$mainField], $instrument['name']));
                                    $row = $temp;
                                }
                                $temp = array_merge($this->primaryData[$id]['primary'][$key], $row);
                                //get columns first so we can delete no needed based on the values.
                                $this->saveArrayColumns(array_keys($temp));

                                //if  secondary instrument is not repeating EG patient data then make it part of the primary data so it will show up in every row and do not save it to represented data
                                $this->primaryData[$id]['primary'][$key] = $temp;

                            }
                        }

                    }
                }
                //get columns first so we can delete no needed based on the values.
                $this->saveArrayColumns(array_keys($this->primaryData[$id]['primary'][$key]));

                $this->representationArray['data'][] = $this->flattenArray($this->primaryData[$id]['primary'][$key]);
            }
        }
    }

    /**
     * @param $array
     * @return mixed
     */
    private function flattenArray($array)
    {
        foreach ($array as $field => $el) {
            //check if element type from DataDictionary is checkbox or dropdown then get value label instead
            $prop = $this->getDataDictionaryProp($field);

            //if not defined in data dictionary then do not display it.
            if (is_null($prop)) {
                //unset($array[$field]);
                $prop['field_label'] = $field;
            }

            if (is_null($el) || $field == '') {
                unset($array[$field]);
                $key = array_search($field, $this->representationArray['columns']);
                unset($this->representationArray['columns'][$key]);
                array_filter($this->representationArray['columns']);
            }
            //if dropdown or checkbox get the label instead of numeric value.
            if ($prop['field_type'] == 'checkbox' || $prop['field_type'] == 'dropdown') {
                $array[$field] = $this->getValueLabel($el, $prop);
            }

            //change columns labels
            $value = $array[$field];
            $key = array_search($field, $this->representationArray['columns']);
            unset($this->representationArray['columns'][$key]);
            unset($array[$field]);
            $array[$prop['field_label']] = $value;
            $this->representationArray['columns'][] = $prop['field_label'];
        }
        return array_filter($array);
    }

    private function getValueLabel($value, $prop)
    {
        $group = $prop['select_choices_or_calculations'];
        $choices = explode('|', $group);
        $result = '';
        foreach ($choices as $choice) {
            $components = explode(",", $choice);
            if ($prop['field_type'] == 'checkbox') {
                foreach ($value as $k => $v) {
                    //make sure the option selected is same as in the loop
                    if ($k != $components[0]) {
                        continue;
                    }
                    //checkbox is checked
                    if ($v == "1") {
                        $result .= ' ' . end($components) . ' => Yes,';
                    } else {
                        $result .= ' ' . end($components) . ' => No,';
                    }
                }
                $result = ltrim($result, ",");
            } else {
                if ($value == $components[0]) {
                    $result = end($components);
                }
            }
        }
        return $result;
    }

    /**
     * this function will make sure columns $representationArray is up to date
     * @param array $keys
     */
    private function saveArrayColumns($keys)
    {
        if (!isset($this->representationArray['columns'])) {
            $this->representationArray['columns'] = $keys;
        } else {
            $this->representationArray['columns'] = array_merge($keys, $this->representationArray['columns']);
            //make sure no duplication
            $this->cleanColumns();
        }
        $this->setRecordIdFirst();
    }

    /**
     * get associated secondary instrument data for specific record id
     * @param string $date
     * @param string $instrument
     * @param int $recordId
     * @return array
     */
    private function getSecondaryInstrumentData($date, $instrument, $recordId)
    {
        $dateField = $this->inputs[SECONDARY_INSTRUMENT][$instrument['name']][DATE_IDENTIFIER];
        //is secondary instrument data not loaded yet load it now for one time.
        if (!isset($this->inputs[SECONDARY_INSTRUMENT][$instrument['name']]['data'])) {
            $param = array(
                //'filterLogic' => $this->getPatientFilterText(),
                'fields' => $this->inputs[SECONDARY_FIELDS][$instrument['name']],
                'return_format' => 'array',
            );
            $this->inputs[SECONDARY_INSTRUMENT][$instrument['name']]['data'] = REDCap::getData($param);
        }

        $temp = array();
        $result = array();
        $timeFilters = $this->processSecondaryTimeFilter($date, $instrument);
        //if repeating instrument
        if (array_key_exists('repeat_instances',
            $this->inputs[SECONDARY_INSTRUMENT][$instrument['name']]['data'][$recordId])) {
            //get from secondary the records for id we passed
            $records = $this->inputs[SECONDARY_INSTRUMENT][$instrument['name']]['data'][$recordId]['repeat_instances'][$this->getFirstEventId()][$instrument['name']];
            foreach ($records as $record) {
                if ($timeFilters) {

                    //now loop over before/after time filters for secondary records
                    foreach ($timeFilters as $filter) {
                        // if secondary report within the range on the primary record.
                        if (strtotime($record[$dateField]) >= strtotime($filter['start']) && strtotime($record[$dateField]) <= strtotime($filter['end'])) {
                            // if within the range compare with

                            //
                            $start = strtotime($record[$dateField]) - strtotime($filter['start']);
                            $end = strtotime($filter['end']) - strtotime($record[$dateField]);

                            $temp[min($start, $end)] = $record;
                        }
                    }
                } else {
                    // if no time define just add record to the result
                    $temp[] = $record;
                }

            }
        } elseif (array_key_exists($recordId, $this->inputs[SECONDARY_INSTRUMENT][$instrument['name']]['data'])) {
            //get from secondary the records for id we passed
            $result[] = $this->inputs[SECONDARY_INSTRUMENT][$instrument['name']]['data'][$recordId][$this->getFirstEventId()];
        }

        // if multiple secondary records exist get the closest one to primary based on the array keys and return that.
        if (!empty($temp)) {
            $result[] = $temp[array_pop(array_keys($temp, min($temp)))];
        }
        return $result;
    }

    /**
     * define the secondary instrument date search criteria.
     * @param string $date
     * @param array $instrument
     * @return array
     */
    private function processSecondaryTimeFilter($date, $instrument)
    {
        $result = array();
        if (isset($instrument['before'])) {
            $time = strtotime($date) - $instrument['before'] * 24 * 60 * 60;
            $result['before'] = array(
                'start' => date('Y-m-d H:i:s', $time),
                'end' => date('Y-m-d H:i:s', strtotime($date))
            );
        }
        if (isset($instrument['after'])) {
            $time = strtotime($date) + $instrument['after'] * 24 * 60 * 60;
            $result['after'] = array(
                'start' => date('Y-m-d H:i:s', strtotime($date)),
                'end' => date('Y-m-d H:i:s', $time)
            );
        }
        return $result;
    }

    private function getMergedInstrumentsData()
    {
        $instances = $this->prepareMergedInstrumentsSettings();
        foreach ($instances as $instance) {

            // get the data for merged instrument assigned to main one.
            foreach ($instance[$instance['main-instrument']] as $subInstance) {
                if (!isset($this->inputs[MERGED_INSTRUMENT][$instance['main-instrument']][$subInstance['secondary-instrument']]['data'])) {
                    $param = array(
                        //'filterLogic' => $this->getPatientFilterText(),
                        'fields' => REDCap::getFieldNames($subInstance['secondary-instrument']),
                        'exportAsLabels' => true,
                        'return_format' => 'array',
                    );
                    $this->inputs[MERGED_INSTRUMENT][$instance['main-instrument']][$subInstance['secondary-instrument']]['data'] = REDCap::getData($param);
                }
            }

        }
    }

    /**
     * process primary and secondary instruments
     */
    public function generateReport()
    {

        /**
         * this will check if we want to merge other records from other instruments
         */
        if ($this->getProjectSetting('allow-merged-instruments')) {
            $this->getMergedInstrumentsData();
        }

        $this->getPrimaryInstrumentsData();

        $this->processSecondaryInstrumentsData();

        //
        $this->cacheReport();

        //finally display content
        $this->displayContent();
    }

    public function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * this function will save generated report into temp csv file that will be cleaned by REDCap in 12 minutes.
     */
    private function cacheReport()
    {
        $string = strtolower($this->generateRandomString());
        $filename = APP_PATH_TEMP . date("YmdHis") . '_' . $string . '_correlated_report' . '.csv';
        file_put_contents($filename, serialize($this->representationArray));
        $this->representationArray['session'] = date("YmdHis") . '_' . $string . '_correlated_report' . '.csv';
    }

    /**
     * csv export
     */
    public function csvExport()
    {
        $this->cleanColumns();
        //finally display content
        $this->downloadCSVFile('correlated-report.csv',
            $this->prepareDataForExport());
    }

    /**
     * loop over representationArray to get records into text rows
     * @return array
     */
    private function prepareDataForExport()
    {
        //remove duplication
        $columns = $this->representationArray['columns'];
        $result[] = implode(",", $columns);
        foreach ($this->representationArray['data'] as $row) {
            //create empty array based on number of columns
            $temp = array_fill(0, count($columns), null);
            foreach ($row as $field => $value) {
                //search for header index
                $index = array_search($field, $columns);

                //put the values on correct index to its under correct header
                $temp[$index] = '"' . $value . '"';
            }
            //once we are done with filling temp array add it to main result
            $result[] = implode(",", $temp);
        }
        return $result;
    }

    private function downloadCSVFile($filename, $data)
    {
        $data = implode("\n", $data);
        // Download file and then delete it from the server
        header('Pragma: anytextexeptno-cache', true);
        header('Content-Type: application/octet-stream"');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $data;
        exit();
    }
    /**
     * compress and display json of  $representationArray
     */
    private function displayContent()
    {
        $supportsGzip = strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false;

        //clean for last time for before display
        $this->cleanColumns();


        if ($supportsGzip) {
            $output = gzencode(trim(preg_replace('/\s+/', ' ',
                json_encode($this->representationArray, JSON_UNESCAPED_UNICODE))), 9);
            header("content-encoding: gzip");
            ob_start("ob_gzhandler");
        } else {
            $output = json_encode($this->representationArray);
        }
        $offset = 60 * 60;
        $expire = "expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
        header("content-type: application/json");
        header("cache-control: must-revalidate");
        header($expire);
        header('Content-Length: ' . strlen($output));
        header('Vary: Accept-Encoding');
        echo $output;
        ob_end_flush();
    }


    /**
     * manipulate columns array to make record_id first
     */
    private function setRecordIdFirst()
    {
        $key = array_search('Record ID', $this->representationArray['columns']);
        unset($this->representationArray['columns'][$key]);
        array_unshift($this->representationArray['columns'], 'Record ID');
    }

    private function cleanColumns()
    {
        $this->representationArray['columns'] = array_filter(array_values(array_unique($this->representationArray['columns'])));
    }

    /**
     *
     * override main method to get sub-sub_instance!!!
     * @param $key
     * @param null $pid
     * @return array
     */
    function prepareMergedInstrumentsSettings()
    {
        $rawSettings = $this->getProjectSettings($this->getProjectId());
        foreach ($rawSettings['instance']['value'] as $key => $value) {
            $this->mergedInstrument[$key] = array(
                'main-instrument' => $rawSettings['main-instrument']['value'][$key],
                'main-instrument-field' => $rawSettings['main-instrument-field']['value'][$key]
            );
            foreach ($rawSettings['sub_instance']['value'][$key] as $mkey => $mvalue) {
                $this->mergedInstrument[$key][$rawSettings['main-instrument']['value'][$key]][] = array(
                    'secondary-instrument' => $rawSettings['secondary-instrument']['value'][$key][$mkey],
                    'secondary-instrument-field' => $rawSettings['secondary-instrument-field']['value'][$key][$mkey]
                );
            }

        }
        return $this->mergedInstrument;
    }

    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }
}
