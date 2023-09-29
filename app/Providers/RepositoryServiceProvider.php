<?php 

namespace App\Providers;

use App\Repository\Country\CountryRepositoryInterface;
use App\Repository\Currency\CurrencyLocationRepositoryInterface;
use App\Repository\Currency\CurrencyRepositoryInterface;
use App\Repository\EloquentRepositoryInterface; 
use App\Repository\Eloquent\BaseRepository;
use App\Repository\Eloquent\Country\CountryRepository;
use App\Repository\Eloquent\Currency\CurrencyLocationRepository;
use App\Repository\Eloquent\Currency\CurrencyRepository;
use Illuminate\Support\ServiceProvider; 

/** 
* Class RepositoryServiceProvider 
* @package App\Providers 
*/ 
class RepositoryServiceProvider extends ServiceProvider 
{ 
   /** 
    * Register services. 
    * 
    * @return void  
    */ 
   public function register() 
   { 
       $this->app->bind(EloquentRepositoryInterface::class, BaseRepository::class);
       $this->app->bind(CurrencyRepositoryInterface::class, CurrencyRepository::class);
       $this->app->bind(CountryRepositoryInterface::class, CountryRepository::class);
   }
}