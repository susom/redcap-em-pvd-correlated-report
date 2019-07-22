<?php

// Set the namespace defined in your config file
namespace Stanford\CorrelatedReport;

// Declare your module class, which must extend AbstractExternalModule
class CorrelatedReport extends \ExternalModules\AbstractExternalModule
{

    private $project;


    private $eventId;
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
}
