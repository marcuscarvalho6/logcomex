<?php
namespace App\Repository\Currency;

use App\Models\Currency\Currency;
use Illuminate\Support\Collection;

interface CurrencyRepositoryInterface
{
   public function all(): Collection;
   public function updateOrCreate($where, $data): Currency;
   public function findByFilter($filters): Collection;
}