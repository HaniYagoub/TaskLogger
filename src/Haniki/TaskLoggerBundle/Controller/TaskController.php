<?php

namespace Haniki\TaskLoggerBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Config\Definition\Exception\Exception;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @PreAuthorize("isAuthenticated()")
 */
class TaskController extends Controller
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
        $user = $this->getUser();

        return new JsonResponse($taskRepository->getTasksByDate($user->getId(), $date));
    }

    /**
     * @Route("/create-task", name="create_task", options={"expose"=true})
     */
    public function createTaskAction()
    {
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            $taskRepository = $this->getRepository('Haniki\TaskLoggerBundle\Entity\Task');
            $task = $taskRepository->createTask(
                $this->getUser(),
                $request->get('description', '')
            );

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
                $workLog = $taskRepository->createWorkLog(
                    $this->getUser(),
                    $request->get('taskId', null)
                );
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
            $task = $taskRepository->stopTask($this->getUser(), $id);
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
                    $this->getUser(),
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
     * @Route("/merge-tasks", name="merge_tasks", options={"expose"=true})
     */
    public function mergeTasksAction()
    {
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            $tasksIds = $request->get('tasksIds', array());

            if (count($tasksIds) < 2) {
                return new JsonResponse(array('error' => 'Not enough tasks given for merge', 400));
            }

            $taskRepository = $this->getRepository('Haniki\TaskLoggerBundle\Entity\Task');
            $tasks = array();
            $user = $this->getUser();

            foreach ($tasksIds as $taskId) {
                $tasks[$taskId] = $taskRepository->getTaskById($taskId);
                if ($tasks[$taskId]->getUser()->getId() != $user->getId()) {
                    return new JsonResponse(array('error' => 'You have no right to access this resource', 403));
                }
            }

            /* @var $mergedTask \Haniki\TaskLoggerBundle\Entity\Task */
            $mergedTask = array_pop($tasks);
            if (null == $mergedTask) {
                return new JsonResponse(array('error' => 'Task not found', 400));
            }

            $em = $this->getDoctrine()->getManager();

            foreach($tasks as $taskId => $task) {
                /* @var $task \Haniki\TaskLoggerBundle\Entity\Task */
                $mergedTask->setDescription($mergedTask->getDescription() . ' + ' . $task->getDescription());
                foreach ($task->getWorkLogs() as $workLog) {
                    $mergedTask->addWorkLog($workLog);
                }
                $em->remove($task);
            }
            $em->flush();

            return new JsonResponse($taskRepository->getTaskAsArray($mergedTask->getId()));
        }

        return $this->redirect($this->generateUrl('show_tasks'));
    }
}
