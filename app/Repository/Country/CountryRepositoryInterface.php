<?php
namespace App\Repository\Country;

use App\Models\Country\Country;

interface CountryRepositoryInterface
{
   public function updateOrCreate($where, $data): Country;
   public function findFlagByCountry($filters): Country;
}