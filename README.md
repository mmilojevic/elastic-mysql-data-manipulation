Description
-----------
Application for manipulating data between mysql and elastic.

Installation
------------

Clone the application

Run  ```  composer install ``` in the root

Create in mysql database with command ```  create database application default charset utf8; ```

Import video.sql with command: ``` mysql -u root -proot application < video.sql  ```

Create Elastic index with command: 
```
PUT application
{
   "mappings": {
         "video": {
            "properties": {
                "id": {
                  "type": "long"
               },
               "title": {
                  "type": "text"
               },
               "description": {
                  "type": "text"
               },
               "actors": {
                  "type": "text"
               },
               "categories": {
                  "type": "text"
               },
               "tags": {
                  "type": "text"
               },
               "date": {
                  "type": "date",
                  "format": "yyyy-MM-dd HH:mm:ss"
               }
            }
         }
      }
}
```
Usage
------------

Inserting all data from mysql to elastic:
```php src/index.php insert_all```

Inserting latest  data from mysql to elastic:
```php src/index.php insert_latest ```

Updating some videos. 
``` php index.php update 1 2 45 85 ```

Where 1 2 45 85 represents video ids

Searching videos 
```php index.php search "thought dolores" 450 1 ```

Where 
- "thought dolores" is search string 
- 450 is offset
- 1 is size