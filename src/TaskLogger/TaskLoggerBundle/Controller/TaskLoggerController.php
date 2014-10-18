<?php

namespace TaskLogger\TaskLoggerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use TaskLogger\TaskLoggerBundle\Entity\Task;
use TaskLogger\TaskLoggerBundle\Entity\WorkLog;

class TaskLoggerController extends Controller
{
    /**
     * @Route("/", name="index")
     */
    public function indexAction()
    {
        return $this->render('TaskLoggerTaskLoggerBundle:TaskLogger:index.html.twig');
    }

    /**
     * @Route("/tasks", defaults={"date"=null})
     * @Route("/tasks/", defaults={"date"=null})
     * @Route("/tasks/{date}", name="show_tasks")
     */
    public function showTasksAction($date = null)
    {
        $startedAt = new \DateTime();
        $startedAt->setTimestamp(strtotime($date == null ? 'now': $date));

        $entityManager = $this->getDoctrine()->getEntityManager();
        $taskRepository = $entityManager->getRepository('TaskLogger\TaskLoggerBundle\Entity\Task');

        $tasks = $taskRepository
            ->createQueryBuilder('t')
            ->join('t.workLogs', 'w')
            ->where('w.startedAt >= :start')
            ->andWhere('w.startedAt <= :end')
            ->setParameter('start', $startedAt->format('Y-m-d 00:00:00'))
            ->setParameter('end', $startedAt->format('Y-m-d 24:59:59'))
            ->orderBy('t.updatedAt', 'desc')
            ->getQuery()
            ->getResult();

        return $this->render('TaskLoggerTaskLoggerBundle:TaskLogger:tasks.html.twig', array(
            'tasks' => $tasks,
            'date' => $startedAt
        ));
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

            $entityManager = $this->getDoctrine()->getEntityManager();
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
                    ->getRepository('TaskLogger\TaskLoggerBundle\Entity\Task')
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

            $entityManager = $this->getDoctrine()->getEntityManager();
            $entityManager->persist($task);
            $entityManager->flush();

            return new JsonResponse(array(
                'workLogId' => $workLog->getId()
            ));
        }

        return $this->redirect($this->generateUrl('show_tasks'));
    }

    /**
     * @Route("/stop-task/{id}", name="stop_task", options={"expose"=true})
     */
    public function stopTaskAction($id)
    {
        $entityManager = $this->getDoctrine()->getEntityManager();
        /* @var $task \TaskLogger\TaskLoggerBundle\Entity\Task */
        $task = $entityManager->getRepository('TaskLogger\TaskLoggerBundle\Entity\Task')->find($id);

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

        return new JsonResponse($task->getWorkLogs()->toArray());
    }
}
