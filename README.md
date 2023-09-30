#Desafio backend Logcomex (Captação)

## Desafio
Desenvolver um scrapping/crawler para capturar dados de uma fonte externa que retorne um JSON contendo informações de moedas e os países que a utilizam.

## Stacks utilizadas
O teste foi desenvolvido utilizando as seguintes s stacks:

- PHP
	- Laravel

- Banco de Dados Relacional
	- MySQL

- Caching
	- Redis

- Configuração da infra
	-	Docker

## Como iniciar o projeto

### Clonar  repositorio

Para clonar o repositório, você deve executar o seguinte comando:

`git clone git@github.com:marcuscarvalho6/logcomex.git`


### Inicializar o docker

Após clonar o repositório, vamos criar o nosso .env a partir do nosso .env.example com o comando abaixo:

`cp .env.example .env`

Agora precisamos buildar nossas imagens do docker. Para isso vá até a pasta do projeto e execute:

`docker compose up -d`

Depois de completado, vamos verificar se todos os containers subiram encontram-se ativos.

`docker ps`

Você deve encontrar 5 containers atvios com os seguintes nomes:


- logcomex-nginx-1
- logcomex-db-1
- logcomex-redis-1
- logcomex-app-1

### Configurar o projeto Laravel

Após isso, podemos verificar que todos os serviços encontram-se ativos, e podemos dar início aos procedimentos restantes.

Para isso precisamos entrar no bash do container `logcomex-app-1` da seguinte forma:

`docker exec -it logcomex-app-1 /bin/bash`

Você deve ver algo similar a:

`logcomex@abbcb1ef5717:/var/www$`

Agora você está acessando o container com um usuário não root, e vamos instalar as dependencias do projeto através do composer. Então, execute dentro da mesma pasta citada acima:

`composer install`

Gere uma nova chave para sua aplicação laravel:

`php artisan key:generate`

### Gerando a estrutura da dados do MySQL

Vamos orecisar rodar nossas migrations para que as tabelas necessárias sejam criadas:

`php artisan:migrate`

Pronto, provavelmente você ja pode visualizar suas tabelas recém criadas, e agora vamos iniciar a população das mesmas através de um crawler/scrapping.

Ainda dentro do container `logcomex-app-1` em `/var/www` vamos executar o comando:

`php artisan app:crawler`

!Aguarde, isso pode demorar um pouco. Você será ciente do início ao fim do procedimento através do seu terminal sobre o processo em execução, e assim que finalizado, uma mensagem será exibida no seu console e o processo terminará automaticamente.

Verifique se as tabelas `currencies` e `countries` foram populadas corretamente.

Caso estejam. Seu projeto estará pronto para uso, e você pode utilizar o swagger através da url:

`http://localhost:8090/api/documentation`



