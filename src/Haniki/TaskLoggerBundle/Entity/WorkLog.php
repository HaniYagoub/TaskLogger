<?php

namespace Haniki\TaskLoggerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WorkLog
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Haniki\TaskLoggerBundle\Entity\WorkLogRepository")
 */
class WorkLog
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
     * @var Task
     *
     * @ORM\ManyToOne(
     *      targetEntity="Haniki\TaskLoggerBundle\Entity\Task",
     *      inversedBy="workLogs"
     * )
     * @ORM\JoinColumn(
     *      name="task_id",
     *      referencedColumnName="id"
     * )
     */
    protected $task;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startedAt", type="datetime")
     */
    protected $startedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="duration", type="time", nullable=true)
     */
    protected $duration;

    public function __construct()
    {
        $this->startedAt = new \DateTime();
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
     * Set task
     *
     * @param Task $task
     * @return WorkLog
     */
    public function setTask($task)
    {
        $this->task = $task;
        $this->task->update();

        return $this;
    }

    /**
     * Get task
     *
     * @return Task
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * Set startedAt
     *
     * @param \DateTime $startedAt
     * @return WorkLog
     */
    public function setStartedAt($startedAt)
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    /**
     * Get startedAt
     *
     * @return \DateTime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * Set duration
     *
     * @param \DateTime $duration
     * @return WorkLog
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
        $this->task->update();

        return $this;
    }

    /**
     * Get duration
     *
     * @return \DateTime
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Get array version of the object
     * @return array
     */
    public function toArray()
    {
        return array(
            'id' => $this->id,
            'startedAt' => $this->startedAt,
            'duration' => $this->duration,
        );
    }
}
