<?php

namespace TheAentMachine\AentGitLabCI\Command;

use TheAentMachine\AentGitLabCI\Aenthill\Metadata;
use TheAentMachine\AentGitLabCI\Exception\GitLabCIFileException;
use TheAentMachine\AentGitLabCI\Exception\JobException;
use TheAentMachine\AentGitLabCI\Exception\PayloadException;
use TheAentMachine\AentGitLabCI\GitLabCI\GitLabCIFile;
use TheAentMachine\AentGitLabCI\GitLabCI\Job\CleanupKubernetesJob;
use TheAentMachine\AentGitLabCI\GitLabCI\Job\DeployKubernetesJob;
use TheAentMachine\AentGitLabCI\Question\GitLabCICommonQuestions;
use TheAentMachine\Aenthill\CommonEvents;
use TheAentMachine\Aenthill\CommonMetadata;
use TheAentMachine\Aenthill\Manifest;
use TheAentMachine\Command\AbstractEventCommand;
use TheAentMachine\Exception\ManifestException;
use TheAentMachine\Exception\MissingEnvironmentVariableException;

final class NewDeployKubernetesJobEventCommand extends AbstractEventCommand
{
    /** @var string */
    private $envName;

    /** @var string */
    private $registryDomainName;

    /** @var string */
    private $k8sPathname;

    protected function getEventName(): string
    {
        return CommonEvents::NEW_DEPLOY_KUBERNETES_JOB_EVENT;
    }

    /**
     * @param null|string $payload
     * @return null|string
     * @throws GitLabCIFileException
     * @throws JobException
     * @throws ManifestException
     * @throws MissingEnvironmentVariableException
     * @throws PayloadException
     */
    protected function executeEvent(?string $payload): ?string
    {
        $aentHelper = $this->getAentHelper();

        $aentHelper->title('GitLab CI: adding a deploy stage');

        if (empty($payload)) {
            throw PayloadException::missingKubernetesPathname();
        }

        $this->envName = Manifest::mustGetMetadata(CommonMetadata::ENV_NAME_KEY);
        $this->registryDomainName = Manifest::mustGetMetadata(Metadata::REGISTRY_DOMAIN_NAME_KEY);
        $this->k8sPathname = $payload;

        $this->output->writeln("🦊×☸️ Kubernetes path: <info>$this->k8sPathname</info>");
        $aentHelper->spacer();

        $deployJob = $this->askForDeployType();
        $cleanUpJob = $this->createCleanupOnGCloud();

        $file = new GitLabCIFile();
        $file->findOrCreate();
        $file->addDeploy($deployJob);
        $file->addCleanUp($cleanUpJob);


        $this->output->writeln('🦊 <info>' . GitLabCIFile::DEFAULT_FILENAME . '</info> has been successfully updated!');

        return null;
    }

    /**
     * @return DeployKubernetesJob
     * @throws JobException
     */
    private function askForDeployType(): DeployKubernetesJob
    {
        $deployType = Manifest::getMetadata(Metadata::DEPLOY_TYPE_KEY);

        if (null === $deployType) {
            $deployType = $this->getAentHelper()
                ->choiceQuestion('Select on which provider you want to deploy your stack', [
                    Metadata::DEPLOY_TYPE_GCLOUD
                ])
                ->ask();
        }

        switch ($deployType) {
            case Metadata::DEPLOY_TYPE_GCLOUD:
                return $this->createDeployOnGCloud();
            default:
                throw JobException::unknownDeployType($deployType);
        }
    }

    /**
     * @return DeployKubernetesJob
     * @throws JobException
     */
    private function createDeployOnGCloud(): DeployKubernetesJob
    {
        Manifest::addMetadata(Metadata::DEPLOY_TYPE_KEY, Metadata::DEPLOY_TYPE_GCLOUD);

        $gitlabCICommonQuestions = new GitLabCICommonQuestions($this->getAentHelper());

        $remoteBasePath = $gitlabCICommonQuestions->askForRemoteBasePath();
        $isManual = $gitlabCICommonQuestions->askForManual();

        return DeployKubernetesJob::newDeployOnGCloud(
            $this->envName,
            $remoteBasePath,
            $isManual
        );
    }

    /**
     * @return CleanupKubernetesJob
     * @throws JobException
     * @throws ManifestException
     */
    private function createCleanupOnGCloud(): CleanupKubernetesJob
    {
        $gitlabCICommonQuestions = new GitLabCICommonQuestions($this->getAentHelper());
        $projectGroup = Manifest::mustGetMetadata(Metadata::PROJECT_GROUP_KEY);
        $projectName = Manifest::mustGetMetadata(Metadata::PROJECT_NAME_KEY);
        $isManual = $gitlabCICommonQuestions->askForManual();

        return CleanupKubernetesJob::newCleanup(
            $this->envName,
            $projectGroup,
            $projectName,
            $isManual
        );
    }
}
