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

    public function __construct()
    {
        parent::__construct();

        $this->tasks = new ArrayCollection();
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
}
