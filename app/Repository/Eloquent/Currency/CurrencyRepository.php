<?php

namespace App\Repository\Eloquent\Currency;

use App\Models\Currency\Currency;
use App\Repository\Currency\CurrencyRepositoryInterface;
use App\Repository\Eloquent\BaseRepository;
use Illuminate\Support\Collection;

class CurrencyRepository extends BaseRepository implements CurrencyRepositoryInterface
{

    /**
    * CurrencyRepository constructor.
    *
    * @param Currency $model
    */
    public function __construct(Currency $model)
    {
        parent::__construct($model);
    }

    /**
    * @return Collection
    */
    public function all(): Collection
    {
        return $this->model->all();    
    }

    /**
    * @return Currency
    */
    public function updateOrCreate($where, $data): Currency
    {
        return $this->model->updateOrCreate($where, $data);    
    }

    /**
    * @return Collection
    */
    public function findByFilter($filters): Collection
    {
        $query = $this->model;
        
        if (!empty($filters['code']) && !is_array($filters['code'])) {
            $query = $query->where('code', $filters['code']);
        }

        if (!empty($filters['code_list']) && is_array($filters['code_list'])) {
            $query = $query->whereIn('code', $filters['code_list']);
        }

        if (!empty($filters['number']) && !is_array($filters['number'])) {
            $query = $query->where('number', $filters['number']);
        }

        if (!empty($filters['number_list']) && is_array($filters['number_list'])) {
            $query = $query->whereIn('number', $filters['number_list']);
        }

        return $query->get();
    }
}