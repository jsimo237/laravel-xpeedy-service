<?php

namespace Kirago\Xpeedy\Services;

use Exception;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Spatie\Valuestore\Valuestore;

class XpeedyService
{



    protected string $apiKey;
    protected string $apiSecret;
    protected string $apiId;
    protected string $baseUrl;
    protected Client $clientHttp;
    protected mixed $store;


    /**
     * @return mixed
     */
    public function getApiKey(): mixed
    {
        return $this->apiKey;
    }

    /**
     * @param mixed $apiKey
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return string
     */
    public function getApiSecret(): string
    {
        return $this->apiSecret;
    }

    /**
     * @param string $apiSecret
     */
    public function setApiSecret(string $apiSecret): void
    {
        $this->apiSecret = $apiSecret;
    }

    /**
     * @return string
     */
    public function getApiId(): string
    {
        return $this->apiId;
    }

    /**
     * @param string $apiId
     */
    public function setApiId(string $apiId): void
    {
        $this->apiId = $apiId;
    }

    /**
     * @return string
     */
    public function getBaseUrl() : string
    {
        return $this->baseUrl;
    }

    /**
     * @param string $baseUrl
     */
    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public  function __construct(array $config=[])
    {

     $confApp = array_merge(config('xpeedy-service') ?? [],$config);

     $this->setApiKey($config['api_key']);

     $this->setApiSecret($config['api_secret']);

     $this->setBaseUrL($confApp['base_url']);

     $this->setApiId($config['api_id']);

     $filePath = storage_path('app/'.$this->getApiId().'.auth.json');

     $this->store = Valuestore::make($filePath);


     $this->clientHttp = new Client(
         [
             'base_uri'=>$this->getBaseUrl(),
             'verify'=> false,
             'headers'=>[
                    'Accept'=>'application/json',
                    'Content-Type'=>'application/json',
                    'x-api-id'=>$this->getApiId(),

                ],
             'http_errors' => false,
         ]
     );
    }

    /**
     * @throws Exception
     */
    public  function authenticate(): array
    {

        $options = [
            "exception"=> "Echec d'authentification",
            "method" => "post",
            "data" => [
                'api_key' => $this->getApiKey(),
                'api_secret' => $this->getApiSecret(),
            ],
            "headers" => [
             'x-api-id'=>$this->getApiId()
            ],
        ];
        return $this->request("/authenticate",$options);


    }

    /**
     * @throws Exception
     */
    public function request($endpoint, $options = []): array
    {
//         $headers =  $options['headers'] ?? null;
        $data  = $options['data']?? null ;

        $method =  $options['method'] ?? "get";

        $method = strtolower($method);

        $body = "query";

        if (in_array($method,['post',"put","delete"])){
            $body = "form_params";
        }

        unset($options['method'],$options['data']);

        $options[$body] = $data;
         $endpoint = $this->getBaseUrl().$endpoint;
         $exception = $options['exception'] ?? 'Request unsuccessful.';
        try {
            $response  =  $this->clientHttp->request($method,$endpoint,$options);

             $status = $response->getStatusCode();

            $response = json_decode($response->getBody(), true);

            return [ $response ,$status];
        }catch (Exception $ex) {
            throw new Exception($exception, 0, $ex);
        }
    }

    /**
     * @throws Exception
     */
    public function getAccessToken(){

        $token = $this->store->get('access_token');
        $expiredAt = $this->store->get('expired_at');

        $expired = true ;

        if (filled($expiredAt)) {
            $expired = date_is_expired($expiredAt);
        }

        if (blank($token) or $expired){
            [$response,$status] = $this->authenticate();

            if (isset($response['access_token'])) {
                $this->store->put($response);
                return $this->getAccessToken();
            }
        }
        return $token;
    }

    /**
     * @throws Exception
     */
    public  function initTransaction($data): array
    {
//        $data = [
//            'method' =>'MTN-MOMO',
//            'country' => 'CMR',
//            'payer_number' => 691894015,
//            'amount' =>100,
//            'details' =>'' ,
//        ];

        $rules = [
            'method' =>'required',
            'amount' =>'required',
            'payer_number' =>'required',
            'country' =>'nullable',
            'details' =>'nullable',
        ];
        $validator =  validator($data,$rules);
        $validator->validate();


        $token = $this->getAccessToken();

        $options = [
           "method" => "post",
           "data" => [
               "exception"=> "Echec d'initialisation de transaction",
               'method' => $data['method'],
               'country' => $data['country'] ?? null,
               'payer_number' => $data['payer_number'],
               'amount' => $data['amount'],
               'details' => $data['details'] ?? null,
           ],
           "headers" => [
               'Authorization' => "Bearer $token",
           ],
       ];

       return $this->request("/collections/init",$options);
   }

    /**
     * @throws Exception
     */
    public  function checkTransaction($transactionId): array
    {
        $token = $this->getAccessToken();

          $options = [
              "exception"=> "Echec de transaction check incomplet",
              "method" => "get",
              "headers" => [
                  'Authorization' => "Bearer $token",
              ],
          ];

            return $this->request("/collections/$transactionId",$options);

   }


    /**
     * @throws Exception
     */
    public function walletDetails(){

        $token = $this->getAccessToken();

       $options = [
           "method" => "get",
           "headers" => [
               'Authorization' => "Bearer $token",
           ],
       ];
       return $this->request("/wallet/infos",$options);

   }
}
