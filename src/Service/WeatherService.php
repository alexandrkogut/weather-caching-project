<?php

namespace App\Service;

use App\Entity\Weather;
use App\Repository\WeatherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService implements ObjectGetterInterface
{
    public function __construct(
        protected HttpClientInterface       $httpClient,
        protected WeatherRepository         $weatherRepository,
        protected AdapterInterface          $adapter,
        protected EntityManagerInterface    $entityManager
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function get(int $code): ?Weather
    {
        $cache = $this->getFromCache($code);

        if (null === $cache) {
            $db = $this->getFromDb($code);

            if (null === $db) {
                $api = $this->getFromApi($code);

                if (null === $api) {
                    return null;
                } else {
                    $this->setToDb($api);
                    $this->setToCache($api);

                    $api->setSource('API');
                    return $api;
                }
            }

            $this->setToCache($db);

            $db->setSource('Postgres');
            return $db;
        }

        $cache->setSource('Redis');
        return $cache;
    }

    public function getFromApi(int $code): ?Weather
    {
        try {
            $json = $this->httpClient->request(
                'GET',
                $_ENV['API_URL'].'?id='.$code.'&appid='.$_ENV['API_KEY'].'&units=metric'
            )->getContent();

            $object = json_decode($json);
        } catch (
        ClientExceptionInterface
        | TransportExceptionInterface
        | ServerExceptionInterface
        | RedirectionExceptionInterface
        ) {
            return null;
        }

        return (new Weather())
            ->setCode($code)
            ->setName($object->name)
            ->setTemperature($object->main->temp)
            ;
    }

    public function getFromDb(int $code): ?Weather
    {
        return $this->weatherRepository->findOneBy(['code' => $code]);
    }

    public function setToDb(Weather $weather): Weather
    {
        $this->entityManager->persist($weather);
        $this->entityManager->flush();

        return $weather;
    }

    public function getFromCache(int $code): ?Weather
    {
        try {
            $item = $this->adapter->getItem(md5($code));
        } catch (InvalidArgumentException) {
            return null;
        }

        if (!$item->isHit()) {
            return null;
        }

        return $item->get();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setToCache(Weather $weather): Weather
    {
        $item = $this->adapter->getItem(md5($weather->getCode()));
        $item->set($weather);

        $this->adapter->save($item);

        return $weather;
    }
}