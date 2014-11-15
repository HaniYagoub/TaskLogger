<?php

namespace Haniki\TaskLoggerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Task
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Haniki\TaskLoggerBundle\Entity\TaskRepository")
 */
class Task
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User $user
     *
     * @ORM\ManyToOne(
     *      targetEntity="Haniki\TaskLoggerBundle\Entity\User",
     *      inversedBy="tasks"
     * )
     * @ORM\JoinColumn(
     *      name="user_id",
     *      referencedColumnName="id"
     * )
     */
    protected $user;

    /**
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *      targetEntity="Haniki\TaskLoggerBundle\Entity\WorkLog",
     *      mappedBy="task",
     *      cascade={"persist"}
     * )
     */
    protected $workLogs;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    protected $updatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    protected $description;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->description = '';
        $this->workLogs = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return Task
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Task
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Task
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Refresh updatedAt date
     *
     * @return Task
     */
    public function update()
    {
        $this->updatedAt = new \DateTime();

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Task
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Add a workLog
     *
     * @param WorkLog $workLog
     */
    public function addWorkLog(WorkLog $workLog)
    {
        $workLog->setTask($this);
        $this->workLogs[] = $workLog;
        $this->update();
    }

    /**
     * Get workLogs
     *
     * @return WorkLog
     */
    public function getWorkLogs()
    {
        return $this->workLogs;
    }

    /**
     * Get the total duration of a task
     *
     * @return int the total duration in seconds
     */
    public function getDuration()
    {
        $duration = 0;
        foreach ($this->workLogs as $workLog) {
            if (null != $workLog->getDuration()) {
                eval('$duration += ' . $workLog->getDuration()->format('H*60*60+i*60+s') . ';');
            }
        }
        $d = date('G\h i\m', mktime(0, 0, $duration));
        return $d;
    }

    /**
     * Get array version of the object
     * @return array
     */
    public function toArray()
    {
        return array(
            'id' => $this->id,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'description' => $this->description,
            'user' => $this->user,
            'workLogs' => $this->workLogs->toArray(),
        );
    }
}
