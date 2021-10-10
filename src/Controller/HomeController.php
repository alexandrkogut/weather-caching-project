<?php

namespace App\Controller;

use App\Entity\Weather;
use App\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'codes' => [
                Weather::KYIV_CODE,
                Weather::MINSK_CODE,
                Weather::LONDON_CODE,
                Weather::SAN_FRANCISCO_CODE,
            ],
        ]);
    }

    #[Route('/{code}', name: 'weather')]
    public function weather(int $code, WeatherService $weatherService): Response
    {
        $weather = $weatherService->get($code);

        if (null === $weather) {
            throw new NotFoundHttpException('Code was not founded');
        }

        return $this->render('home/weather.html.twig', [
            'codes' => [
                Weather::KYIV_CODE,
                Weather::MINSK_CODE,
                Weather::LONDON_CODE,
                Weather::SAN_FRANCISCO_CODE,
            ],
            'weather' => $weather,
        ]);
    }
}
