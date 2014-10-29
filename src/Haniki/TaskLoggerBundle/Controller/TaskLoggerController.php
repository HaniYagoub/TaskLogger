<?php

namespace Haniki\TaskLoggerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Haniki\TaskLoggerBundle\Entity\Task;
use Haniki\TaskLoggerBundle\Entity\WorkLog;

class TaskLoggerController extends Controller
{
    /**
     * @Route("/", defaults={"date"=null})
     * @Route("/tasks", defaults={"date"=null})
     * @Route("/tasks/", defaults={"date"=null})
     * @Route("/tasks/{date}", name="show_tasks")
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
        $startedAt = new \DateTime();
        $startedAt->setTimestamp(strtotime($date == null ? 'now': $date));

        $taskRepository = $this->getDoctrine()->getRepository('Haniki\TaskLoggerBundle\Entity\Task');

        $tasks = $taskRepository
            ->createQueryBuilder('t')
            ->select('t, w')
            ->innerJoin('t.workLogs', 'w')
            ->where('w.startedAt >= :start')
            ->andWhere('w.startedAt <= :end')
            ->setParameter('start', $startedAt->format('Y-m-d 00:00:00'))
            ->setParameter('end', $startedAt->format('Y-m-d 24:59:59'))
            ->orderBy('t.updatedAt', 'desc')
            ->getQuery()
            ->getArrayResult();

        return new JsonResponse($tasks);
    }

    /**
     * @Route("/create-task", name="create_task", options={"expose"=true})
     */
    public function createTaskAction()
    {
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            $task = new Task();
            $task->setDescription($request->get('description', ''));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($task);
            $entityManager->flush();

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
            //Retrieving the task
            if(($taskId = $request->get('taskId', null)) != null) {
                /* @var $task Task */
                $task = $this->getDoctrine()
                    ->getRepository('Haniki\TaskLoggerBundle\Entity\Task')
                    ->find($taskId);

                if (!$task) {
                    return new JsonResponse(array(
                        'error' => 'Aucune tâche trouvée pour cet id : '.$taskId
                    ), 400);
                }

                $workLog = new WorkLog();
                $task->addWorkLog($workLog);
            } else {
                return new JsonResponse(array(
                    'error' => 'Aucune tâche ou log n\'a été spécifié'
                ), 400);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($task);
            $entityManager->flush();

            return new JsonResponse($workLog->toArray());
        }

        return $this->redirect($this->generateUrl('show_tasks'));
    }

    /**
     * @Route("/stop-task/{id}", name="stop_task", options={"expose"=true})
     */
    public function stopTaskAction($id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        /* @var \TaskLogger\TaskLoggerBundle\Entity\Task $task */
        $task = $entityManager->getRepository('Haniki\TaskLoggerBundle\Entity\Task')->find($id);

        if (!$task) {
            throw $this->createNotFoundException("Aucune tâche n'a été trouvée pour cet id : $id");
        }

        foreach ($task->getWorkLogs() as $workLog) {
            if (is_null($workLog->getDuration())) {
                $interval = $workLog->getStartedAt()->diff(new \DateTime());
                $workLog->setDuration((new \DateTime('midnight'))->add($interval));

                $entityManager->persist($workLog);
            }
        }
        $entityManager->flush();

        $task = $entityManager->getRepository('Haniki\TaskLoggerBundle\Entity\Task')
            ->createQueryBuilder('t')
            ->select('t, w')
            ->innerJoin('t.workLogs', 'w')
            ->where('t.id >= :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult();

        return new JsonResponse($task[0]);
    }

    /**
     * @Route("/update-task-description", name="update_task_description", options={"expose"=true})
     */
    public function updateTaskDescriptionAction()
    {
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            //Retrieving the task
            if(($taskId = $request->get('pk', null)) != null) {
                /* @var $task Task */
                $task = $this->getDoctrine()
                    ->getRepository('Haniki\TaskLoggerBundle\Entity\Task')
                    ->find($taskId);

                if (!$task) {
                    return new JsonResponse(array(
                        'error' => 'Aucune tâche trouvée pour cet id : '.$taskId
                    ), 400);
                }

                $description = strip_tags($request->get('value', ''));
                $task->setDescription($description);
                $task->update();

            } else {
                return new JsonResponse(array(
                    'error' => 'Aucune tâche n\'a été spécifiée'
                ), 400);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($task);
            $entityManager->flush();

            return new JsonResponse($task->getDescription());
        }

        return $this->redirect($this->generateUrl('show_tasks'));
    }
}
