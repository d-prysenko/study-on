<?php

namespace App\Controller;

use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthenticationController extends AbstractController
{

//    public function register(): Response
//    {
//        $user = new User();
//        $form = $this->createForm(UserType::class, $user);
//        return $this->renderForm('security/register.html.twig', [
//            'form' => $form,
//        ]);
//    }

    public function login(AuthenticationUtils $authenticationUtils): Response
    {
//        if ($this->isGranted('ROLE_USER'))
//        {
//            return $this->redirectToRoute('profile');
//        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
