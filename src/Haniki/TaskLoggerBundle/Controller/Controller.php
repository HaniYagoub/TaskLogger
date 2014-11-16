<?php

namespace Haniki\TaskLoggerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as SymfonyController;

class Controller extends SymfonyController
{
    /**
     * Shortcut method returnng a repository from a namespace
     *
     * @param string $namespace
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getRepository($namespace)
    {
        return $this->getDoctrine()->getManager()->getRepository($namespace);
    }
}
