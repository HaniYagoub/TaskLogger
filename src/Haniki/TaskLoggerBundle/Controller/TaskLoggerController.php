<?php

namespace Haniki\TaskLoggerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
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
