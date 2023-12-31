<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserRegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

class RegistrationController extends AbstractController
{
    #[Route('/registration', name: 'app_registration', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserRegistrationFormType::class, $user);

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

            $this->addFlash('success', 'Registration successful!');

            return $this->redirectToRoute('app_login'); // Adjust the route as needed
        }

        return $this->render('registration/register.html.twig', [
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
