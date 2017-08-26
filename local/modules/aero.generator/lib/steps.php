<?php

namespace Aero\Generator;

class Steps
{
    protected $step;
    protected $stepSize;
    protected $stepCount = 10;

    public function __construct(){
        $this->stepSize = 1;
    }

    /**
     * Makes a generation step.
     * Returns 0 if there is nothing to do.
     *
     * @return int
     */
    public function createNext(){

        if(!isset($this->step))
            $this->step = 1;
        else
            $this->step += $this->stepSize;

        if($this->step > $this->stepCount)
            return 0;

        $stepsComplete = $this->stepSize;

        return $stepsComplete;
    }

    /**
     * Returns total steps count
     *
     * @return int
     */
    public function getTotal(){
        return $this->stepCount;
    }

    public function setConfig(){}
}