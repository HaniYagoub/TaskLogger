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
    private $id;

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
    private $user;

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
    private $workLogs;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    private $description;

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
     *
     * @return type
     */
    public function getDuration()
    {
        $duration = 0;
        foreach ($this->workLogs as $workLog) {
            $duration += eval($workLog->getDuration('H*60*60+i*60+m'));
        }

        return $duration;
    }

    /**
     * Get array version of the object
     * @return array
     */
    public function toArray()
    {
        return array(
            'id' => $this->id,
            'createdAt' => $this->createdAt->format('H:i'),
            'updatedAt' => $this->updatedAt->format('H:i'),
            'description' => $this->description,
            'user' => $this->user,
            'workLogs' => $this->workLogs->toArray(),
        );
    }
}
