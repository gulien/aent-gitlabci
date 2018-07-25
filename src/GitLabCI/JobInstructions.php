<?php


namespace TheAentMachine\AentGitLabCI\GitLabCI;

use TheAentMachine\AentGitLabCI\Exception\JobInstructionsException;

abstract class JobInstructions
{
    /** @var string */
    protected $jobName;

    /** @var string */
    protected $image;

    /** @var string */
    protected $stage;

    /** @var string[] */
    protected $services;

    /** @var array<string,string> */
    protected $variables = [];

    /** @var string[] */
    protected $beforeScript = [];

    /** @var string[] */
    protected $script = [];

    /** @var array<string,string> */
    protected $environment = [];

    /** @var string[] */
    protected $only = [];

    /** @var string[] */
    protected $except = [];

    /** @var bool */
    protected $manual = false;

    /**
     * @return mixed[]
     */
    public function dump(): array
    {
        $obj = [
            $this->jobName => [
                'image' => $this->image,
                'stage' => $this->stage,
            ]
        ];

        if ($this->hasServices()) {
            $obj[$this->jobName]['services'] = $this->services;
        }

        if ($this->hasVariables()) {
            $obj[$this->jobName]['variables'] = $this->variables;
        }

        if ($this->hasBeforeScript()) {
            $obj[$this->jobName]['before_script'] = $this->beforeScript;
        }

        if ($this->hasScript()) {
            $obj[$this->jobName]['script'] = $this->script;
        }

        if ($this->hasEnvironment()) {
            $obj[$this->jobName]['environment'] = $this->environment;
        }

        if ($this->hasOnly()) {
            $obj[$this->jobName]['only'] = $this->environment;
        }

        if ($this->hasExcept()) {
            $obj[$this->jobName]['except'] = $this->except;
        }

        if ($this->manual) {
            $obj[$this->jobName]['when'] = 'manual';
        }

        return $obj;
    }

    /**
     * @param string $branch
     * @throws JobInstructionsException
     */
    public function addOnly(string $branch): void
    {
        if (in_array($branch, $this->only)) {
            return;
        }
        if (in_array($branch, $this->except)) {
            throw JobInstructionsException::cannotAddOnly($branch);
        }
        $this->except[] = $branch;
    }

    /**
     * @param string $branch
     * @throws JobInstructionsException
     */
    public function addExcept(string $branch): void
    {
        if (in_array($branch, $this->except)) {
            return;
        }
        if (in_array($branch, $this->only)) {
            throw JobInstructionsException::cannotAddExcept($branch);
        }
        $this->except[] = $branch;
    }

    private function hasServices(): bool
    {
        return !empty($this->services);
    }

    private function hasVariables(): bool
    {
        return !empty($this->variables);
    }

    private function hasBeforeScript(): bool
    {
        return !empty($this->beforeScript);
    }

    private function hasScript(): bool
    {
        return !empty($this->script);
    }

    private function hasEnvironment(): bool
    {
        return !empty($this->environment);
    }

    private function hasOnly(): bool
    {
        return !empty($this->only);
    }

    private function hasExcept(): bool
    {
        return !empty($this->except);
    }
}