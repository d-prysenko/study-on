<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Exception\BillingUserAlreadyExists;
use App\Security\BillingAuthenticator;
use App\Security\User;
use App\Form\RegistrationFormType;
use App\Service\BillingAuthenticationManager;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\AuthenticationTokenCreatedEvent;

class RegistrationController extends AbstractController
{
    public function register(Request $request, BillingAuthenticationManager $authenticator, BillingAuthenticator $formAuthenticator, BillingClient $billingClient): Response
    {
//        if ($this->isGranted('ROLE_USER'))
//        {
//           return $this->redirectToRoute('profile');
//        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        $error = "";

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $billingClient->register($user);
                return $authenticator->authenticateUser($user, $formAuthenticator, $request);
            }
            catch (BillingUserAlreadyExists $e) {
                $form->get('email')->addError(new FormError('Пользователь с таким email уже существует'));
            }
            catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'error' => $error
        ]);
    }
}
