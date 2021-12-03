<?php

namespace App\Controller;

use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Security\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ProfileController extends AbstractController
{

    public function index(Request $request, BillingClient $billingClient): Response
    {
        try {
            $user = $billingClient->getCurrentUser();
        } catch (\JsonException $ex) {
            throw new ServiceUnavailableHttpException();
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user
        ]);
    }
}
