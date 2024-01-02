<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ProjectSearchType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/project')]
class ProjectController extends AbstractController
{
    #[Route('/', name: 'app_project_index', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $form = $this->createForm(ProjectSearchType::class);
        $form->handleRequest($request);

        $filterData = $form->isSubmitted() ? $form->getData() : [];

        $projects = $entityManager
            ->getRepository(Project::class)
            ->findByFilter($filterData);

        // Paginate the results
        $projects = $paginator->paginate(
            $projects,
            $request->query->getInt('page', 1),
            8
        );

        return $this->render('project/index.html.twig', [
            'projects' => $projects,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    #[Security("is_granted('ROLE_ADMIN')")]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $project);
            $entityManager->persist($project);
            $entityManager->flush();

            return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_project_show', methods: ['GET'])]
    public function show(Project $project): Response
    {
        return $this->render('project/show.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_project_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Project $project, EntityManagerInterface $entityManager, MailerInterface $mailer, UserRepository $userRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. You do not have the required role.');
        }
        $originalStatus = $project->getStatus(); // Store the original status before form submission

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form['imageFile']->getData();
            if ($file) {
                $this->handleImageUpload($form, $project);
            }
            $entityManager->flush();
            if ($originalStatus !== $project->getStatus()) {
                $users = $userRepository->findByRole('ROLE_ADMIN');
                $this->sendEmailAction($mailer, $users, $project);
            }
            return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_project_delete', methods: ['POST'])]
    public function delete(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied. You do not have the required role.');
        }
        if ($this->isCsrfTokenValid('delete' . $project->getId(), $request->request->get('_token'))) {
            $entityManager->remove($project);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
    }
    private function handleImageUpload($form, $project): void
    {
        $imageFile = $form->get('imageFile')->getData();

        if ($imageFile) {
            $newFilename = md5(uniqid()) . '.' . $imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('project_images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                // Handle file upload error
            }

            $project->setImage($newFilename);
        }
    }
    private function sendEmailAction(MailerInterface $mailer, array $adminUsers, Project $project)
    {
        $email = (new Email())
            ->from('admin@example.com');

        foreach ($adminUsers as $adminUser) {
            $email->addTo($adminUser->getEmail());
        }

        $email
            ->subject('Project Status Change Notification')
            ->html(
                $this->renderView(
                    'emails/status_change_notification.html.twig',
                    ['project' => $project]
                )
            );

        $mailer->send($email);
    }
}
