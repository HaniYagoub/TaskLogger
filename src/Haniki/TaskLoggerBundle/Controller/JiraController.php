<?php

namespace Haniki\TaskLoggerBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/jira")
 */
class JiraController extends Controller
{
    /**
     * @Route("/get-jira-issue", name="get_jira_issue", options={"expose"=true})
     */
    public function getJiraIssueAction()
    {
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            $task = $this->getRepository('Haniki\TaskLoggerBundle\Entity\Task')
                ->getTaskById($request->get('taskId', null));
            if (!$task) {
                return new JsonResponse(array('error' => 'Task not found', 400));
            } elseif ($task->getUser()->getId() != $this->getUser()->getId()) {
                return new JsonResponse(array('error' => 'You have no right to access this resource', 403));
            }
            $matches = array();
            if (preg_match('/#[A-Za-z]+\-[0-9]+/', $task->getDescription(), $matches)) {
                $api = $this->get('jira_api');
                $issueKey = str_replace('#', '', $matches[0]);
                $issue = $api->getIssue($issueKey);
                return new JsonResponse($issue->getResult());
            }

            return new JsonResponse(array('error' => 'No Issue found'), 400);
        }

        return $this->redirect($this->generateUrl('show_tasks'));
    }

    /**
     * @Route("/log-work-jira", name="log_work_jira", options={"expose"=true})
     */
    public function logWorkJiraAction()
    {
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            $taskId = $request->get('taskId', null);
            /* @var $task \Haniki\TaskLoggerBundle\Entity\Task */
            $task = $this->getRepository('Haniki\TaskLoggerBundle\Entity\Task')
                ->getTaskById($taskId);

            if (!$task) {
                return new JsonResponse(array('error' => 'Task not found', 400));
            } elseif ($task->getUser()->getId() != $this->getUser()->getId()) {
                return new JsonResponse(array('error' => 'You have no right to access this resource', 403));
            }

            $matches = array();
            if (preg_match('/#[A-Za-z]+\-[0-9]+/', $task->getDescription(), $matches)) {
                $api = $this->get('jira_api');
                $issueKey = str_replace('#', '', $matches[0]);
                $comment = $request->get('comment', $task->getDescription());
                $params = array(
                    "started" => str_replace('+', '.000+', $task->getWorkLogs()->last()->getStartedAt()->format(\DateTime::ISO8601)),
                    "comment" => $comment,
                    "timeSpent" => $task->getDuration(),
                );

                try {
                    $issue = $api->addWorkLog($issueKey, $params);
                } catch (\JiraRestClient\Api\Exception $e) {
                    //Dirty fix to ignore the CURLE_RECV_ERROR
                    if ($e->getCode() == 56) {
                        return new JsonResponse(array('warning' => 'Exception raised ['.$e->getMessage().']'));
                    }

                    return new JsonResponse(array('error' => 'Exception raised ['.$e->getMessage().']'), 400);
                }

                return new JsonResponse($issue->getResult());
            }

            return new JsonResponse(array('error' => 'No Issue found'), 400);
        }

        return $this->redirect($this->generateUrl('show_tasks'));
    }
}
