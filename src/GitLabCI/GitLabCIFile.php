<?php

namespace TheAentMachine\AentGitLabCI\GitLabCI;

use Safe\Exceptions\FilesystemException;
use Symfony\Component\Filesystem\Filesystem;
use TheAentMachine\AentGitLabCI\Exception\GitLabCIFileException;
use TheAentMachine\AentGitLabCI\GitLabCI\Job\AbstractBuildJob;
use TheAentMachine\AentGitLabCI\GitLabCI\Job\AbstractCleanupJob;
use TheAentMachine\AentGitLabCI\GitLabCI\Job\AbstractDeployJob;
use TheAentMachine\Aenthill\Pheromone;
use TheAentMachine\Exception\MissingEnvironmentVariableException;
use TheAentMachine\YamlTools\YamlTools;
use function Safe\chown;
use function Safe\chgrp;
use function Safe\file_put_contents;

final class GitLabCIFile
{
    public const DEFAULT_FILENAME = '.gitlab-ci.yml';

    /** @var string */
    private $path;

    /** @var \SplFileInfo */
    private $file;

    /**
     * GitLabCIFile constructor.
     * @throws MissingEnvironmentVariableException
     */
    public function __construct()
    {
        $this->path = Pheromone::getContainerProjectDirectory() . '/' . self::DEFAULT_FILENAME;
    }

    /**
     * @return bool
     */
    public function exist(): bool
    {
        return \file_exists($this->path);
    }

    /**
     * @return self
     * @throws GitLabCIFileException
     * @throws FilesystemException
     */
    public function findOrCreate(): self
    {
        if (!$this->exist()) {
            return $this->create()->addStages();
        }
        $this->file = new \SplFileInfo($this->path);
        return $this;
    }

    /**
     * @return self
     * @throws FilesystemException
     */
    private function create(): self
    {
        if ($this->exist()) {
            return $this;
        }
        $fileSystem = new Filesystem();
        $fileSystem->dumpFile($this->path, '');
        $containerProjectDirInfo = new \SplFileInfo(\dirname($this->path));
        chown($this->path, $containerProjectDirInfo->getOwner());
        chgrp($this->path, $containerProjectDirInfo->getGroup());
        $this->file = new \SplFileInfo($this->path);
        return $this;
    }

    /**
     * @return self
     * @throws GitLabCIFileException
     * @throws FilesystemException
     */
    private function addStages(): self
    {
        if (!$this->exist()) {
            throw GitLabCIFileException::missingFile();
        }
        $stages = [
            'stages' => [
                'test',
                'build',
                'deploy',
                'cleanup',
            ],
        ];
        $yaml = YamlTools::dump($stages);
        file_put_contents($this->path, $yaml);
        return $this;
    }

    /**
     * @param AbstractBuildJob $job
     * @return self
     * @throws GitLabCIFileException
     * @throws FilesystemException
     */
    public function addBuild(AbstractBuildJob $job): self
    {
        if (!$this->exist()) {
            throw GitLabCIFileException::missingFile();
        }
        $yaml = YamlTools::dump($job->dump());
        YamlTools::mergeContentIntoFile($yaml, $this->path);
        return $this;
    }

    /**
     * @param AbstractDeployJob $job
     * @return self
     * @throws GitLabCIFileException
     * @throws FilesystemException
     */
    public function addDeploy(AbstractDeployJob $job): self
    {
        if (!$this->exist()) {
            throw GitLabCIFileException::missingFile();
        }
        $yaml = YamlTools::dump($job->dump());
        YamlTools::mergeContentIntoFile($yaml, $this->path);
        return $this;
    }

    /**
     * @param AbstractCleanupJob $job
     * @return self
     * @throws GitLabCIFileException
     * @throws FilesystemException
     */
    public function addCleanUp(AbstractCleanupJob $job): self
    {
        if (!$this->exist()) {
            throw GitLabCIFileException::missingFile();
        }
        $yaml = YamlTools::dump($job->dump());
        YamlTools::mergeContentIntoFile($yaml, $this->path);
        return $this;
    }
}
