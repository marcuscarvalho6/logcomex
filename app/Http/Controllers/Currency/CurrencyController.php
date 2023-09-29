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
     * Busca informações de uma ou mais moedas
     * 
     * Esta função tem como objetivo, buscar as informações de uma ou mais moeda através do seu código ISO 4271 ou seu código numérico.
     * Por exemplo: <br>
     * Você pode buscar por BRL no campo code, e o sistema se encarregará de buscar as informações, como: nome, código, decimal,
     * e países que a utilizam, inclusive com suas bandeiras, caso estejam disponíveis. <br>
     * Você também será capaz de buscar a cotação das suas moedas em relação a uma outra que você informar (não obrigatório) no campo exchange. <br>
     * Obs: Você só poderá informar um dos campos: code, code_list, number ou number_list por requisição.
     * países que a utilizam e suas bandeiras
     *  
     *  @OA\Get(
     *      path="/api/currency",
     *      @OA\Response(response="200", description="successful operation"),
     *      @OA\Response(response="422", description="unprocessable entity"),
     *      @OA\Response(response="204", description="empty response"),
     *      @OA\Parameter(
     *          name="code",
     *          in="query",
     *          description="Buscar por um codigo ISO 4271 de moeda, exemplo: BRL",
     *          required=false,
     *      ),
     *      @OA\Parameter(
     *          name="number",
     *          in="query",
     *          description="Buscar por um codigo numérico de moeda, exemplo: 986",
     *          required=false,
     *      ),
     *      @OA\Parameter(
     *          name="code_list[]",
     *          in="query",
     *          description="Buscar por um array de codigos ISO 4271 de moeda, exemplo: ['BRL', 'USD']",
     *          required=false,
     *          @OA\Schema(
     *              type="array",
     *              @OA\Items(type="string")
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="number_list[]",
     *          in="query",
     *          description="Buscar por um array de codigos numericos de moeda, exemplo: [986, 978]",
     *          required=false,
     *          @OA\Schema(
     *              type="array",
     *              @OA\Items(type="integer")
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="exchange",
     *          in="query",
     *          description="Se informado, retornará a cotação das moedas buscadas de acordo com o valor informado, ou seja, se você informar USD todas as moedas buscadas retornarão o campo exchange_rate com a cotação em relação a moeda informada nesse campo",
     *          required=false,
     *      ),
     *  ),
     * 
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
