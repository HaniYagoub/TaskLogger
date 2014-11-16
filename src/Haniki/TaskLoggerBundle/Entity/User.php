<?php

namespace Haniki\TaskLoggerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use FOS\UserBundle\Model\User as FOSUser;

/**
 * User
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class User extends FOSUser
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *      targetEntity="Haniki\TaskLoggerBundle\Entity\Task",
     *      mappedBy="user",
     *      cascade={"persist"}
     * )
     */
    protected $tasks;

    /**
     * @var string jira url
     *
     * @ORM\Column(name="jira_url", type="string", length=64, nullable=true)
     */
    protected $jiraUrl;

    /**
     * @var string base64 encoded credentials
     *
     * @ORM\Column(name="jira_credentials", type="string", length=64, nullable=true)
     */
    protected $jiraCredentials;

    /*
     * @var string jira username
     */
    protected $jiraUsername;

    /*
     * @var $jira password
     */
    protected $jiraPassword;

    public function __construct()
    {
        parent::__construct();

        $this->tasks = new ArrayCollection();

        $this->initJiraUsernameAndPassword();
    }

    /**
     * Add task
     *
     * @param Task $task
     */
    public function addTask(Task $task)
    {
        $task->setUser($this);
        $this->tasks[] = $task;
    }

    /**
     * Get tasks
     *
     * @return Task
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * Set jira url
     *
     * @param string $jiraUrl
     * @return User
     */
    public function setJiraUrl($jiraUrl)
    {
        $this->jiraUrl = $jiraUrl;

        return $this;
    }

    /**
     * Get jira url
     *
     * @return string
     */
    public function getJiraUrl()
    {
        return $this->jiraUrl;
    }

    public function setJiraUsername($jiraUsername)
    {
        $this->jiraUsername = $jiraUsername;
        $this->generateJiraCredentials();
    }

    /**
     * Get jira username
     *
     * @return string
     */
    public function getJiraUsername()
    {
        if (is_null($this->jiraUsername)) {
            $this->initJiraUsernameAndPassword();
        }

        return $this->jiraUsername;
    }


    public function setJiraPassword($jiraPassword)
    {
        $this->jiraPassword = $jiraPassword;
        $this->generateJiraCredentials();
    }

    /**
     * Get jira password
     *
     * @return string
     */
    public function getJiraPassword()
    {
        if (is_null($this->jiraPassword)) {
            $this->initJiraUsernameAndPassword();
        }

        return $this->jiraPassword;
    }

    /**
     * Set jira credentials
     *
     * @param string $jiraCredentials
     * @return User
     */
    public function setJiraCredentials($jiraCredentials)
    {
        $this->jiraCredentials = $jiraCredentials;

        return $this;
    }

    /**
     * Get jira credentials
     *
     * @return string
     */
    public function getJiraCredentials()
    {
        return $this->jiraCredentials;
    }

    /**
     * Generate jira credentials
     *
     * @param string $jiraUsername
     * @param string $jiraPassword
     *
     * @return User
     */
    public function generateJiraCredentials()
    {
        if (!is_null($this->jiraUsername) && !is_null($this->jiraPassword)) {
            $this->jiraCredentials = base64_encode($this->jiraUsername . ':' . $this->jiraPassword);
            $this->addRole('ROLE_JIRA_USER');
        }

        return $this;
    }

    public function initJiraUsernameAndPassword()
    {
        if (!is_null($this->jiraCredentials)) {
            $credentials = explode(':', base64_decode($this->jiraCredentials));
            if (count($credentials) >= 2) {
                $this->jiraUsername = $credentials[0];
                $this->jiraPassword = $credentials[1];
            }
        }
    }
}
