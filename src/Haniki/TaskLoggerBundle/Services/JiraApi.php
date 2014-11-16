<?php

namespace Haniki\TaskLoggerBundle\Services;

use JiraRestClient\Api;
use JiraRestClient\Api\Authentication\Basic;

use Symfony\Component\Security\Core\SecurityContext;

class JiraApi extends Api
{
    protected $context;

    public function __construct(SecurityContext $context)
    {
        $this->context = $context;

        /* @var $user Haniki\TaskLoggerBundle\Entity\User */
        $user = $this->context->getToken()->getUser();
        $credentials = explode(':', base64_decode($user->getJiraCredentials()));
        $auth = new Basic($credentials[0], $credentials[1]);

        parent::__construct($user->getJiraUrl(), $auth);
    }
}
