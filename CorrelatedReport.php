<?php

// Set the namespace defined in your config file
namespace Stanford\CorrelatedReport;


define('PRIMARY_INPUT', 'primary_input');
define('SECONDARY_INPUT', 'secondary_input');
define('CLOSEST', 'closest');

/**
 * Class CorrelatedReport
 * @package Stanford\CorrelatedReport
 * @property \Project $project
 * @property int $eventId
 * @property array $inputs
 */
class CorrelatedReport extends \ExternalModules\AbstractExternalModule
{

    private $project;


    private $eventId;

    private $inputs;
    /**
     * IhabModule constructor.
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
            $_POST[$key] = filter_var($_POST[$key], FILTER_SANITIZE_STRING);
        }
    }

    public function classifyInputs()
    {
        foreach ($_POST as $key => $input) {
            if ($key == PRIMARY_INPUT) {
                $this->inputs[PRIMARY_INPUT] = $input;
            } //this could be multiple values
            elseif ($key == SECONDARY_INPUT) {
                //I added the key because we still need the type and value for the secondary instrument
                $this->inputs[SECONDARY_INPUT][$input]['name'] = $input;
            } elseif (strpos($key, CLOSEST) !== false) {
                $field = explode('-', $key);
                $name = end($field);
                if (array_search('value', $field)) {
                    $this->inputs[SECONDARY_INPUT][$name]['value'] = $input;
                } elseif (array_search('type', $field)) {
                    $this->inputs[SECONDARY_INPUT][$name]['type'] = $input;
                } else {
                    throw new \LogicException('wrong input');
                }
            } else {
                throw new \LogicException('cant define input type');
            }
        }
    }

    public function verifySecondaryInstruments()
    {

    }
}
