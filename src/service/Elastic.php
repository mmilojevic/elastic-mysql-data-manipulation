<?php
namespace Application;

use Elasticsearch\ClientBuilder;

class Elastic
{
    /**
     * @var ClientBuilder 
     */
    protected $client;
            
    public function __construct($config)
    {

        $params = [
            'hosts' => [
                $config['host'].':'.$config['port']
            ],
            'retries' => 2,
            'handler' => ClientBuilder::multiHandler()
        ];


        $this->client =ClientBuilder::fromConfig($params);
    }

    /**
     * 
     * @param string $index - Elastic index 
     * @param string $type - Elastic type
     * @param string|array $body - Elastic query 
     * @return array
     */
    public function insert(string $index, string $type, $body)
    {
        $params = $this->createParams($index, $type, $body);
        
        return $this->client->index($params);
    }
    
    /**
     * 
     * @param string $index - Elastic index 
     * @param string $type - Elastic type
     * @param string|array $body - Elastic query 
     * @return array
     */
    public function delete(string $index,string  $type, $body)
    {
        $params = $this->createParams($index, $type, $body);
        return $this->client->delete($params);
    }
    
    /**
     * 
     * @param string $index - Elastic index 
     * @param string $type - Elastic type
     * @param string|array $body - Elastic query 
     * @return array
     */    
    public function search($index, $type, $body)
    {
        $params = $this->createParams($index, $type, $body);
        return $this->client->search($params);
    }
    
    /**
     * 
     * @param string $index - Elastic index 
     * @param string $type - Elastic type
     * @param string|array $body - Elastic query 
     * @return array
     */
    protected function createParams($index, $type, $body){
        
        $params =  [
            "index" => $index,
            "type" => $type,
            "body" => $body
        ];
        
        if ( isset($body["id"]) ) {
            $params["id"] = $body["id"];
        }
        
        return $params;
    }

}
