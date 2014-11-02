<?php

namespace Haniki\TaskLoggerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Config\Definition\Exception\Exception;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class TaskLoggerController extends Controller
{
    /**
     * @Route("/", defaults={"date"=null})
     * @Route("/tasks", defaults={"date"=null})
     * @Route("/tasks/", defaults={"date"=null})
     * @Route("/tasks/{date}", name="show_tasks", options={"expose"=true})
     */
    public function showTasksAction($date = null)
    {
        $startedAt = new \DateTime();
        $startedAt->setTimestamp(strtotime($date == null ? 'now': $date));

        return $this->render('HanikiTaskLoggerBundle:TaskLogger:tasks.html.twig', array(
            'date' => $startedAt
        ));
    }

    /**
     * @Route("/get-tasks/{date}", name="get_tasks", options={"expose"=true})
     */
    public function getTasksAction($date = null)
    {
        $taskRepository = $this->getRepository('Haniki\TaskLoggerBundle\Entity\Task');

        return new JsonResponse($taskRepository->getTasksByDate($date));
    }

    /**
     * @Route("/create-task", name="create_task", options={"expose"=true})
     */
    public function createTaskAction()
    {
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            $taskRepository = $this->getRepository('Haniki\TaskLoggerBundle\Entity\Task');
            $task = $taskRepository->createTask($request->get('description', ''));

            return new JsonResponse($task->toArray());
        }

        return $this->redirect($this->generateUrl('show_tasks'));
    }

    /**
     * @Route("/start-work", name="start_work", options={"expose"=true})
     */
    public function startWorkAction()
    {
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            $taskRepository = $this->getRepository('Haniki\TaskLoggerBundle\Entity\Task');
            try {
                $workLog = $taskRepository->createWorkLog($request->get('taskId', null));
            } catch (Exception $e) {
                return new JsonResponse(array('error' => $e->getMessage(), 400));
            }

            return new JsonResponse($workLog->toArray());
        }

        return $this->redirect($this->generateUrl('show_tasks'));
    }

    /**
     * @Route("/stop-task/{id}", name="stop_task", options={"expose"=true})
     */
    public function stopTaskAction($id)
    {
        $taskRepository = $this->getRepository('Haniki\TaskLoggerBundle\Entity\Task');
        try {
            $task = $taskRepository->stopTask($id);
        } catch (Exception $e) {
            return new JsonResponse(array('error' => $e->getMessage(), 400));
        }

        return new JsonResponse($task);
    }

    /**
     * @Route("/update-task-description", name="update_task_description", options={"expose"=true})
     */
    public function updateTaskDescriptionAction()
    {
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            $taskRepository = $this->getRepository('Haniki\TaskLoggerBundle\Entity\Task');
            try {
                $task = $taskRepository->updateTaskDescription(
                    $request->get('pk', null),
                    $request->get('value', null)
                );
            } catch (Exception $e) {
                return new JsonResponse(array('error' => $e->getMessage(), 400));
            }

            return new JsonResponse($task->getDescription());
        }

        return $this->redirect($this->generateUrl('show_tasks'));
    }

    /**
     * @Route("/get-jira-issue", name="get_jira_issue", options={"expose"=true})
     */
    public function getJiraIssueAction()
    {
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            $task = $this->getRepository('Haniki\TaskLoggerBundle\Entity\Task')
                ->find($request->get('taskId', null));
            if ($task) {
                $matches = array();
                if (preg_match('/#[A-Za-z]+\-[0-9]+/', $task->getDescription(), $matches)) {
                    $api = $this->get('jira_api');
                    $issueKey = str_replace('#', '', $matches[0]);
                    $issue = $api->getIssue($issueKey);
                    return new JsonResponse($issue->getResult());
                }
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
            if ($task) {
                $matches = array();
                if (preg_match('/#[A-Za-z]+\-[0-9]+/', $task->getDescription(), $matches)) {
                    $api = $this->get('jira_api');
                    $issueKey = str_replace('#', '', $matches[0]);

                    $params = array(
                        "started" => str_replace('+', '.000+', $task->getWorkLogs()->last()->getStartedAt()->format(\DateTime::ISO8601)),
                        "comment" => $task->getDescription(),
                        "timeSpent" => $task->getDuration(),
                    );

                    //die(var_dump(json_encode($params)));
                    $issue = $api->addLog($issueKey, $params);
                    return new JsonResponse($issue->getResult());
                }
            }

            return new JsonResponse(array('error' => 'No Issue found'), 400);
        }

        return $this->redirect($this->generateUrl('show_tasks'));
    }

    /**
     * Shortcut method returnng a repository from a namespace
     *
     * @param string $namespace
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getRepository($namespace)
    {
        return $this->getDoctrine()->getManager()->getRepository($namespace);
    }
}
