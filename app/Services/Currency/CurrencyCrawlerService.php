<?php

namespace App\Services\Currency;

use App\Repository\Country\CountryRepositoryInterface;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Exception\RequestException;
use App\Repository\Currency\CurrencyRepositoryInterface;

class CurrencyCrawlerService
{
    private $currencyRepository;
    private $countryRepository;
    
    /**
     * @return void
     */
    public function __construct(
        CurrencyRepositoryInterface $currencyRepository, 
        CountryRepositoryInterface $countryRepository)
    {
       $this->currencyRepository = $currencyRepository;
       $this->countryRepository = $countryRepository;
    }

    /**
     * @param boolean $command
     * @return void
     */
    public function execute($command = false)
    {
        try {
            // Instancia o método local para retornar a tabela que precisamos para extrair os dados
            $table = $this->getPageTable();

            // Retorna um json com os dados extraidos
            $data = $this->getTableJson($table, $command);

            // Salva os dados na tabela currencies
            $this->saveData($data);
            
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param Crawler $table
     * @param boolean $command
     * @return array
     */
    private function getTableJson($table, $command = false)
    {
        try {

            // Criar um array que irá montar o resultado
            $data = [];

            // Loop pelas linhas da tabela
            $table->filter('tr')->each(function ($row) use (&$data, $command) {

                // Cria uma variável tmp que vai armazenar os dados temporáriamente
                $tmp = [];
                
                // Loop pelas células da linha
                $row->filter('td')->each(function ($cell, $tdIndex) use (&$tmp, $command) {
                    // Verifica qual a coluna que está sendo lida
                    switch ($tdIndex) {
                        case 0:
                            // O código encontra-se na primeira coluna
                            $tmp['code'] = $cell->text();
                            break;
                        case 1:
                            // O number encontra-se na segunda coluna
                            $tmp['number'] = $cell->text();
                            break;
                        case 2:
                            // O decinal encontra-se na terceira coluna
                            $tmp['decimal'] = $cell->text();
                            break;
                        case 3:
                            // O nome da moeda encontra-se na quarta coluna
                            $tmp['currency'] = $cell->text();
                            break;
                        case 4:
                            // Criamos um array onde as locations dessa currency será carregada
                            $tmp['locations'] = [];

                            // Os dados que vão ser pupulados precisam ser passados por referência pois estarão fora do seu escopo
                            $cell->filter('a')->each(function($a) use (&$tmp, $command) {
                                $country = [];
                                // Quando existe uma observação na coluna no elemento <a> ela possui uma classe new, portanto não precisamos dela
                                if ($a->attr('class') != 'new') {
                                    $country['country'] = $a->attr('title');
                                    if($country['country']) {
                                        // Procura através de um outro crawler a imagem do país apenas se estiver sendo chamado através do Command Crawler
                                        if($command) {
                                            $flag = explode('/', $a->attr('href'));
                                            if ($flag[2]) {
                                                $flagData = $this->getCountryFlag($flag[2]);
                                                $flagData['country'] = $country['country'];
                                                $country['flag'] = $flagData;
                                            }
                                        }
                                        array_push($tmp['locations'], $country);
                                    }
                                }
                            });
                            break;
                    }
                });

                if (count($tmp)) {
                    if ($command) {
                        echo "\e[1;37;40m\e[0m\e[0;32;40mCurrency: {$tmp['code']}\e[0m\n";
                    }
                    $data[] = $tmp;
                }
            });
            return $data;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }


    /**
     * Faz a leitura dos dados extraídos para salvar no banco
     * @param $currencies array
     * @return void
     */
    private function saveData($currencies)
    {
        try {

            echo "\e[1;37;40m\e[0m\e[0;32;40mInício do processo de salvar em: " . Carbon::now() . "\e[0m\n";
            foreach($currencies as $currency) {

                echo "\e[1;37;40m\e[0m\e[0;32;40mSalvando Currency: {$currency['code']}\e[0m\n";

                // Salva a currency, verificando se já existe, caso exista, somente atualizar
                $this->currencyRepository->updateOrCreate([
                    'code' => $currency['code'],
                ], [
                    'code' => $currency['code'],
                    'number' => intval($currency['number']) > 0 ? intval($currency['number']) : null,
                    'decimal' => $currency['decimal'],
                    'currency' => $currency['currency'],
                ]);

                foreach($currency['locations'] as $location) {
                    if(empty($location['flag']['source'])) {
                        continue;
                    }
                    $this->countryRepository->updateOrCreate([
                        'name' => $location['country'],
                    ], [
                        'name' => $location['country'],
                        'flag' => $location['flag']['source'],
                        'width' => $location['flag']['width'],
                        'height' => $location['flag']['height'],
                    ]);
                }
            }
            echo "\e[1;37;40m\e[0m\e[0;32;nFim do processo de salvar em: " . Carbon::now() . "\e[0m\n";
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Salva as informações da currency no Redis para depois extrairmos
     * @return void
     */
    private function saveAllCurrenciesAndLocationsOnRedis()
    {
        try {

            // Instancia o método local para retornar a tabela que precisamos para extrair os dados
            $table = $this->getPageTable();

            // Retorna um json com os dados extraidos
            $data = $this->getTableJson($table);

            // Salva os dados no json e os guarda por 24h
            Redis::set('currencies:all', json_encode($data), 'ex', 3600*24);

            // Retorna os dados
            return $data;
            
        } catch (RequestException $e) {
            throw new RequestException($e->getMessage(), $e->getCode());
        } catch(Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Retorna as informações da tabela
     * @return Crawler
     */
    private function getPageTable()
    {
        try {

            // Criar uma instância do cliente Guzzle
            $client = new GuzzleClient();

            // Fazer a requisição GET à URL
            $response = $client->get(config('logcomex.currency_data_source_en'));
            
            // Obter o conteúdo HTML da resposta
            $html = $response->getBody()->getContents();

            // Criar uma instância do Crawler para analisar o HTML
            $crawler = new Crawler($html);

            // Encontrar a tabela (substitua '#tabela_id' pelo seletor correto da sua tabela)
            return $crawler->filter('.wikitable')->first();

        } catch (RequestException $e) {
            throw new RequestException($e->getMessage(), $e->getCode());
        } catch(Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Busca as informações das locations no Redis e faz o Crawler se não encontrar
     * @param Collection $currencies
     * @param array $filters
     * @return array
     */
    public function getCurrencyLocations($currencies, $filters)
    {  
        try {

            // Buscamos no Redis, e se o dado tiver inexistente ou expirado, refazemos a carga
            $redisCurrencies = Redis::get('currencies:all');

            if (!$redisCurrencies) {
                $redisCurrencies = $this->saveAllCurrenciesAndLocationsOnRedis();
            } else {
                $redisCurrencies = json_decode($redisCurrencies, 1);
            }

            // Vamos extrair os códigos das currencies
            $currenciesCodes = $currencies->pluck('code')->toArray();
            $currenciesResponse = [];

            // Atribuimos as locations a currencies response de acordo com o index que é a propria currency
            foreach($redisCurrencies as $currency) {
                if(in_array($currency['code'], $currenciesCodes)) {
                    $currenciesResponse[$currency['code']] = $currency['locations'];
                }
            }

            /**
             * Observação, o Crawler para buscar as flags na versao da base fonte em português, não possui todas as flags dos países.
             * O dado não segue um padrão <span> <img>, não sendo possível encontrar uma característica para casar as duas informações
             * O mesmo problema não ocorre quando a fonte é em inglês.
             * Para reverter isso, o Crawler foi modificado para buscar as flags também na wikipedia e posteriormente atribuir aos resultados
             */

            $currencies->transform(function($currency) use ($currenciesResponse, $filters) {
                // Agora precisamos buscar os registros das flags de cada uma das locations pertencentes a moeda
                $currency->locations = collect($currenciesResponse[$currency->code])->transform(function($location) {
                    // Fazemos a atribuição caso seja encontrada ou retornamos null
                    $flag = $this->countryRepository->findFlagByCountry($location['country'])->toArray();
                    if(count($flag)) {
                        $location = array_merge($location, $flag);
                    }
                    return $location;
                });
                /**
                 * Caso o crawler não consiga encontrar correspondencias entre o as duas moedas do exhange rate,
                 * um valor null será retornado na flag exchange_rate
                 */
                if(!empty($filters['exchange'])) {
                    $currency->exchange_rate = $this->getExchange($currency->code, strtoupper($filters['exchange']));
                }
                return $currency;
            });

            return $currencies;

        } catch(Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Busca uma flag referente ao país
     * @param string $countryName
     * @return array
     */
    private function getCountryFlag($countryName)
    {
        try {
            // Criar uma instância do cliente Guzzle
            $client = new GuzzleClient();

            // Fazer a requisição GET à URL
            $response = $client->get(config('logcomex.currency_data_source_sumary_base_en') . $countryName);
            
            // Obter o conteúdo HTML da resposta
            $html = $response->getBody()->getContents();

            $response = json_decode($html, 1);

            // Verifica se há resposta válida
            if(empty($response['thumbnail'])) {
                return false;
            }

            // Reterona o dado completo com source e sizes
            return $response['thumbnail'];
        } catch (RequestException $e) {
            throw new RequestException($e->getMessage(), $e->getCode());
        } catch(Exception $e) { // I guess its InvalidArgumentException in this case
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Busca uma flag referente ao país
     * @param string $from
     * @param string $to
     * @return array
     */
    private function getExchange($from, $to)
    {
        try {

            $from = strtolower($from);
            $to = strtolower($to);
    
            // Verifica se as moedas são as mesmas
            if($from === $to) {
                return 1;
            }
    
            // Verifica se já não existe no Redis
            $redisExchange = Redis::get('currencies:exchange:' . $from . '-' . $to);
            if ($redisExchange) {
                return $redisExchange;
            }
            
            // Criar uma instância do cliente Guzzle
            $client = new GuzzleClient();
    
            // Fazer a requisição GET à URL
            $response = $client->get(config('logcomex.exchange') . $from . '-' . $to);
            
            // Obter o conteúdo HTML da resposta
            $html = $response->getBody()->getContents();
    
            // Criar uma instância do Crawler para analisar o HTML
            $crawler = new Crawler($html);
    
            // Encontrar onde encontra-se a cotação
            $exchange = $crawler->filter('[data-test="instrument-price-last"]')->first();
    
            // Retorna um valor float da cotação
            $formatedExchange = floatval(str_replace(',', '.', $exchange->text()));
    
            // Salva os dados no json e os guarda por 1h
            Redis::set('currencies:exchange:' . $from . '-' . $to, $formatedExchange, 'ex', 3600);
    
            return $formatedExchange;
            
        } catch (RequestException $e) {
            return null;
        } catch(Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

}


