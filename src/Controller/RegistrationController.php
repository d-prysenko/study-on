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

class RegistrationController extends AbstractController
{
    public function register(
        Request $request,
        BillingAuthenticationManager $authenticator,
        BillingAuthenticator $billingAuthenticator,
        BillingClient $billingClient
    ): Response {

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        $error = "";

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // calls /api/v1/register via curl
                $user = $billingClient->register($user);
                return $authenticator->authenticateUser($user, $billingAuthenticator, $request);
            } catch (BillingUserAlreadyExists $e) {
                $form->get('email')->addError(new FormError('Пользователь с таким email уже существует'));
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'error' => $error
        ]);
    }
}
