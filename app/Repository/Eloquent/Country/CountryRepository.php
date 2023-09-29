<?php

namespace App\Repository\Eloquent\Country;

use App\Models\Country\Country;
use App\Models\Currency\Currency;
use App\Repository\Country\CountryRepositoryInterface;
use App\Repository\Eloquent\BaseRepository;
use Illuminate\Support\Collection;

class CountryRepository extends BaseRepository implements CountryRepositoryInterface
{

    /**
    * CurrencyRepository constructor.
    *
    * @param Country $model
    */
    public function __construct(Country $model)
    {
        parent::__construct($model);
    }

    /**
    * @return Country
    */
    public function updateOrCreate($where, $data): Country
    {
        return $this->model->updateOrCreate($where, $data);    
    }

    /**
     * @var string $country ex: Brasil
     * @return Country 
     **/
    public function findFlagByCountry($country): Country
    {
        return $this->model
            ->select('flag', 'width', 'height')
            ->where('name', $country)
            ->first();
    }
}