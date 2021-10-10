<?php

namespace App\Service;

interface ObjectGetterInterface
{
    public function get(int $code): ?object;

    public function getFromApi(int $code): ?object;

    public function getFromDb(int $code): ?object;

    public function getFromCache(int $code): ?object;
}