<?php

// Set the namespace defined in your config file
namespace Stanford\CorrelatedReport;

// Declare your module class, which must extend AbstractExternalModule
class CorrelatedReport extends \ExternalModules\AbstractExternalModule
{

    private $project;

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
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

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
    }
}
