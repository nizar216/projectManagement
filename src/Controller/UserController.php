<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Form\UserProfileType;
use Doctrine\ORM\EntityManagerInterface;

class UserController extends AbstractController
{
    #[Route('/user/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function editProfile(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $user = $this->getUser();
        $form = $this->createForm(UserProfileType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->handleImageUpload($form, $user);
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('password')->getData()
            );
            $user->setPassword($hashedPassword);
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Profile updated successfully');

            return $this->redirectToRoute('app_project_index');
        }

        return $this->render('user/edit_profile.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
    private function handleImageUpload($form, $user): void
    {
        $pictureFile = $form->get('pictureFile')->getData();

        if ($pictureFile) {
            $newFilename = md5(uniqid()) . '.' . $pictureFile->guessExtension();

            try {
                $pictureFile->move(
                    $this->getParameter('user_images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
            }

            $user->setPicture($newFilename);
        }
    }
}
