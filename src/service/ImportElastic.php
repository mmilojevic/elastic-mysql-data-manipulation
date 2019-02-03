<?php
namespace Application;

/**
 * For importing data from mysql to elastic
 *
 * @author milos
 */
class ImportElastic {
    
    /**
     *
     * @var \ParagonIE\EasyDB\EasyDB
     */
    protected $dbMysql;
    /**
     *
     * @var \Application\Elastic
     */
    protected $dbElastic;
            
    /**
     * 
     * @param \ParagonIE\EasyDB\EasyDB $dbMysql
     * @param \Application\Elastic $dbElastic
     */
    public function __construct(\ParagonIE\EasyDB\EasyDB $dbMysql,\Application\Elastic $dbElastic) {
        $this->dbMysql = $dbMysql;
        $this->dbElastic = $dbElastic;
    }
    
    /**
     * 
     * @param string $whereClause
     * @param array $params
     * @return array Documents ready to be indexed in Elastic
     * 
     * $docs["id"]              video id
     *      ["title"]           video title
     *      ["description"]     video description
     *      ["actors"]          all actors for video concatenated with space
     *      ["categories"]      all categories for video concatenated with space
     *      ["tags"]            all tags for video concatenated with space
     */
    public function getVideoDocuments(string $whereClause = '', array $params = []) {
        $docs= [];
        
        // Get video data 
        $videos = $this->dbMysql->safeQuery("SELECT v.id, v.title, v.description  FROM videos v " . 
                $whereClause, $params);
        foreach ($videos as $row) {
            $docs[$row["id"]]= [
                "id" => $row["id"],
                "title" => $row["title"],
                "description" => $row["description"]
            ];
            
        }
        
        // Get actor data 
        $actorRows = $this->dbMysql->safeQuery("SELECT v.id AS video_id,  GROUP_CONCAT(a.title SEPARATOR ' ') AS actors FROM videos v 
                    JOIN video_has_actors vha ON v.id = vha.video_id 
                    JOIN actors a ON vha.actor_id = a.id ". $whereClause ." GROUP BY v.id;", $params);
        
        foreach ($actorRows as $actorRow) {
            $docs[$actorRow["video_id"]]["actors"] = $actorRow["actors"];
        }
        
        // Get category data 
        $categoryRows = $this->dbMysql->safeQuery("SELECT v.id AS video_id,  GROUP_CONCAT(c.title SEPARATOR ' ') AS categories from videos v 
                    JOIN video_has_categories vhc ON v.id = vhc.video_id  
                    JOIN categories c ON vhc.category_id = c.id ". $whereClause ." GROUP BY v.id;", $params);
        
        foreach ($categoryRows as $categoryRow) {
            $docs[$categoryRow["video_id"]]["categories"] = $categoryRow["categories"];
        }
        
        // Get tag data 
        $tagRows = $this->dbMysql->safeQuery("SELECT v.id AS video_id,  GROUP_CONCAT(t.title SEPARATOR ' ') AS tags FROM videos v 
                    JOIN video_has_tags vht ON v.id = vht.video_id  
                    JOIN tags t ON vht.tag_id = t.id ". $whereClause ." GROUP BY v.id;", $params);
        
        foreach ($tagRows as $tagRow) {
            $docs[$tagRow["video_id"]]["tags"] = $tagRow["tags"];
        }
        
        
        return $docs;
    }
    
    /**
     * Inserting all records from mysql (rebuilding Elastic)
     */
    public function insertAll() {
        $docs = $this->getVideoDocuments();
        $counter = 0;
        foreach ($docs as $doc) {
            $doc["date"] = date('Y-m-d H:i:s');
            $this->dbElastic->insert('application','video',$doc);
            $counter++;
        }
    }
    
    /**
     * Frist querying for last video id by date from Elastic
     * then getting videos from that date from mysql and inserting them in Elastic
     */
    public function insertLatest() {
        // get last from elastic
        $query_body = '{
                        "query": {
                           "match_all": {}
                        },
                        "size": 1,
                        "sort": [
                           {
                              "id": {
                                 "order": "desc"
                              }
                           }
                        ]
                     }';
        
        $result = $this->dbElastic->search('application','video',$query_body);
        $elasticLastId = $result["hits"]["hits"][0]["_id"];
                
        $whereClause = ' WHERE  v.id > ?';
        $docs = $this->getVideoDocuments($whereClause, [$elasticLastId]);
        
        foreach ($docs as $doc) {
            $doc["date"] = date('Y-m-d H:i:s');
            $this->dbElastic->insert('application','video',$doc);
        }
        
    }
    
    /**
     * 
     * @param array $ids Video ids
     */
    public function updateRecords(array $ids) {
        
        $questionmarks = str_repeat("?,", count($ids)-1) . "?";
        $whereClause = ' WHERE  v.id IN  ('.$questionmarks.')';
        
        $docs = $this->getVideoDocuments($whereClause, $ids);
        
        foreach ($docs as $doc) {
            $doc["date"] = date('Y-m-d H:i:s');
            $this->dbElastic->insert('application','video',$doc);
        }
        
    }
    
}
