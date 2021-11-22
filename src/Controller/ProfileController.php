<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Annotation\Route;
use App\Security\User;

class ProfileController extends AbstractController
{

    public function index(Request $request, BillingClient $billingClient): Response
    {
        $appUser = $this->getUser();
        $user = null;
        if ($appUser !== null) {
            try {
                $user = $billingClient->getUser($appUser->getUserIdentifier());
            } catch (BillingUnavailableException $e) {
                throw new ServiceUnavailableHttpException();
            }
        }
        //dd($user2);
        return $this->render('profile/index.html.twig', [
            'user' => $user
        ]);
    }
}
