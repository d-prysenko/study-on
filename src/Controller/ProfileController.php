<?php

namespace App\Controller;

use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Security\User;

class ProfileController extends AbstractController
{

    public function index(Request $request, BillingClient $billingClient): Response
    {
        $appUser = $this->getUser();
        $user = null;
        if ($appUser !== null) {
            $user = $billingClient->getCurrentUser();
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user
        ]);
    }
}
