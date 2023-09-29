<?php

namespace App\Http\Controllers\Currency;

use App\Http\Controllers\Controller;
use App\Http\Requests\CurrencyRequest;
use App\Repository\Currency\CurrencyRepositoryInterface;
use App\Services\Currency\CurrencyCrawlerService;
use Exception;

class CurrencyController extends Controller
{
    private $currencyRepository;
    private $currencyCrawlerService;
    
    /**
     * @return void
     */
    public function __construct(CurrencyRepositoryInterface $currencyRepository, CurrencyCrawlerService $currencyCrawlerService)
    {
       $this->currencyRepository = $currencyRepository;
       $this->currencyCrawlerService = $currencyCrawlerService;
    }

    /**
     * Função principal responsável por retornar os dados desejados
     * @param CurrencyRequest $request
     * @return array
     */
    public function index(CurrencyRequest $request)
    {
        $filters = $request->all();
        // Convertemos todos os dados de entrada do tipo string para maiúsculo
        if(!empty($filters['code'])) {
            $filters['code'] = strtoupper($filters['code']);
        }
        if(!empty($filters['code_list'])) {
            foreach($filters['code_list'] as &$code) {
                $code = strtoupper($code);
            }
        }
        if(!empty($filters['exchange'])) {
            $filters['exchange'] = strtoupper($filters['exchange']);
        }
        // Pega a lista de currencies de acordo com o que foi enviado no payload
        $currencies = $this->currencyRepository->findByFilter($filters);

        // Verifica se houve ocorrencias
        if (!count($currencies)) {
            throw new Exception("Nenhum dado foi encontrado", 204);
        }

        // Retorna os dados tratados e de acordo com o modelo sugerido
        return $this->currencyCrawlerService->getCurrencyLocations($currencies, $filters);
    }
}
