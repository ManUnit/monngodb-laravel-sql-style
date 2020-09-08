<?php

/*
 *
 *  Nandev :
 *  Create by : Anan Paenthongkham
 *  Update : 2020-7-27
 *  Class Connection 
 *  version 1.0
 *  revision 1.1
 */

namespace Nantaburi\Mongodb\MongoNativeDriver ;

use Nantaburi\Mongodb\MongoNativeDriver\Classifier ;
use PHPUnit\Framework\Exception;
use MongoDB\BSON\Regex;

trait Commands {    
    use Classifier ;
    public static function  GetGroupType () {
        $mongodata=new Group_type;
        // using studio 3T conversion SQL to mongoDB query format on PHP
        // https://studio3t.com/
        $cursor=array([ '$project'=> [ '_id'=> 0, 'group_type'=> '$$ROOT']],
            [ '$lookup'=> [ 'localField'=> 'group_type.group_id', 'from'=> 'products_type', 'foreignField'=> 'group_id', 'as'=> 'products_type']],
            [ '$unwind'=> [ 'path'=> '$products_type', 'preserveNullAndEmptyArrays'=> true]],
            [ '$match'=> [ 'products_type.group_id'=> [ '$ne'=> null]]],
            [ '$sort'=> [ 'group_type.group_id'=> 1]],
            [ '$project'=> [ 'group_type.group_id'=> '$group_type.group_id',
            'group_type.type_groupname_en'=> '$group_type.type_groupname_en',
            'group_type.type_groupname_th'=> '$group_type.type_groupname_th',
            'products_type.type_id'=> '$products_type.type_id',
            'products_type.description'=> '$products_type.description',
            'products_type.description_th'=> '$products_type.description_th',
            '_id'=> 0]]);

        $group_type=$mongodata->raw(function($collection) use ($cursor) {
                return $collection->aggregate($cursor);
            }

        );
        //dd ( $group_type ) ;
        $i=0;
        $keyid=NULL;
        $grouptype_array=array();

        foreach ($group_type as $key=> $value) {

            if ($keyid !==$value['group_type']->group_id) {
                $grouptype_array[$i]['group_id']=$value['group_type']->group_id;
                $grouptype_array[$i]['gname_en']=$value['group_type']->type_groupname_en;
                $grouptype_array[$i]['gname_th']=$value['group_type']->type_groupname_th;
                $grouptype_array[$i]['types']=array();
                $subarray=array('type_id'=> $value['products_type']->type_id, 'desc_en'=> $value['products_type']->description, 'desc_th'=> $value['products_type']->description_th);
                array_push($grouptype_array[$i]['types'], $subarray);
                $last_i=$i;
                $i++;
                $keyid=$value['group_type']->group_id;
            }else{
                if(isset($last_i)) {
                    $subarray=array('type_id'=> $value['products_type']->type_id, 'desc_en'=> $value['products_type']->description, 'desc_th'=> $value['products_type']->description_th);
                    array_push($grouptype_array[$last_i]['types'], $subarray);
                }
            }
        }

        $jsondata=json_decode(json_encode($grouptype_array));

        return $jsondata;

    }

    public static function InitIndexAutoInc () { 
        $this->fillable( (array) [] );
    }

    public function getModifySequence(String $autoIncName) { 
        $this->fillable( (array) [] );  // Pre scan schema to create all first since empty indecies and counter collection 
        $config = new Config ;
        $config->setDb($this->getDbNonstatic());
        $conclude = new BuildConnect ;
        $result = $conclude->getModifySequence($config,$this->getCollectNonstatic(),$autoIncName); 
        return $result; 
    }

    public function paginate(int $perpage) {   
        
 
        if(!isset($_GET['page'] )){$page=(int) 1 ;}else{  $page= (int) $_GET['page'];}
        $outlet = new Outlet ;
        $outlet->items = $this->pageget($perpage);
        $outlet->total = count($outlet->items);
        $outlet->perPage = $perpage;
        $outlet->path = 'http'.(empty($_SERVER['HTTPS'])?'':'s').'://'.$_SERVER['HTTP_HOST'].'/'.str_replace ('/','',$_SERVER['PATH_INFO']);
        $outlet->currentPage = $page  ; 
        $this->getAllwhere()  ;  // update last query
        $outlet->query = self::$querys ; 
        $outlet->pageName = 'page'  ; 
        $outlet->links   = $this->pagedrawing($perpage ,$outlet->total,$page ) ;      
        $outlet->options   =   array_merge($outlet->options , [ 'path' => $outlet->path , 'pageName' => $outlet->pageName  ]) ; 
        if( env('DEV_DEBUG' )) { 
            dd( "DEBU paginate : this values " ,  $this , "perpage" , $perpage  , "Paginatge outlet" , $outlet ) ; 
   
           }
              
        return $outlet ;
    }

    private function pageget(int $perpage) {   
        $this->getAllwhere() ;  // Intregate everywhere  
        $config=new Config ;
        $config->setDb($this->getDbNonstatic()) ;
        $conclude=new BuildConnect; 

         dd( " Command.php  : paginate Get all where : " ,  self::$querys ) ;
        //=================
        //----- Paginate Limit documents calculation --// 
        $totalDocment=json_decode(json_encode($count_products))[0]->count;
        //^above line was  shortcut of $totalDocment= json_decode( json_encode(  $count_products ) ) ; $totalDocment[0]->count  
                    // // Convert skip to selected page 
                    // $totalpage=(int) ($totalDocment / $perpage);
                    // if (($totalDocment% $perpage) !=0) $totalpage=$totalpage+1;
                    // if ($request_page_number > $totalpage) $request_page_number=$totalpage; // limit when over request
                    // if ($request_page_number < 1) $request_page_number=1; // set positive number  lowest page limiter 

                    // $page_offset=($request_page_number - 1) * $perpage; // find skip number 

                    // if ($request_page_number > $totalpage) $request_page_number=$totalpage; // max page limiter

                    // array_push ($pipeline, [ '$skip'=> $page_offset]);
                    // array_push ($pipeline, [ '$limit'=> $perpage]);

                    // $data_products=$mongodata->raw(function($collection) use ($pipeline) {
                    //         return $collection->aggregate($pipeline);
                    //     }

                    // );
        //==================== 
        if(!null == self::$joincollections){
            return $this->getJoin($conclude,$config,$perpage,true);
        }elseif(!null == self::$groupby && null ==  self::$joincollections ){ 
            return $this->getGroup($conclude ,$config,$perpage,true) ;
        }else{  
            return $this->getFind($conclude,$config,$perpage,true);
        } 

       return $conclude->result ;
      
    } 
       
    public function first (){
        $this->limit(1);
        return $this->get(); 
    }
    
    /*
    *
    * @Pagedraw calculation page number 
    *
    */
    public static function pagedrawing($perpage, $totaldocument, $request_page_number) {
      
        $stly_class="page-item";
        $stly_class_opt_active='active';
        $stly_class_opt_disabled="disabled";
        $totalpage=(int) ($totaldocument / $perpage);
        if (($totaldocument % $perpage) !=0) $totalpage=$totalpage+1;   // mod checking 
        $data_array=array();
        $start_range=7;
        if ($totalpage > 1) {
            if ($request_page_number==1) {
                $clickable=0;
                $pagevalue=null;
                $class_using=$stly_class." ".$stly_class_opt_disabled;
            }else {
                $clickable=1;
                $pagevalue=$request_page_number - 1;
                $class_using=$stly_class;
            };
            array_push ($data_array, ['page'=> $pagevalue, 'selected'=> 0, 'clickable'=> $clickable, 'stly_classes'=> $class_using, 'icon'=> '<']); // option push at start
        }

        if ($totalpage >=2 && $totalpage <=11) {
            for ($i=1; $i <=$totalpage; $i++) {
            
                if ((int) $request_page_number===$i) {
                    $selected=1;
                    $clickable=0;
                    $class_using=$stly_class." ".$stly_class_opt_active;
                }else {
                    $selected=0;
                    $clickable=1;
                    $class_using=$stly_class;
                };
                array_push ($data_array, ['page'=> $i, 'selected'=> $selected, 'clickable'=> $clickable, 'stly_classes'=> $class_using, 'icon'=> strval($i)]);
            }
        }

        elseif ($totalpage > 11) {
            if ($request_page_number < $start_range) {
                $start_edge=8;
            }else {
                $start_edge=2;
            }

            for ($i=1; $i <=$start_edge; $i++) {

                if ((int) $request_page_number===$i) {
                    $selected=1;
                    $clickable=0;
                    $class_using=$stly_class."  ".$stly_class_opt_active;
                }else {
                    $selected=0;
                    $clickable=1;
                    $class_using=$stly_class;
                };
                array_push ($data_array, ['page'=> $i, 'selected'=> $selected, 'clickable'=> $clickable, 'stly_classes'=> $class_using, 'icon'=> strval($i)]);
            }
            array_push ($data_array, ['page'=> null, 'selected'=> 0, 'clickable'=> 0, 'stly_classes'=> $stly_class." ".$stly_class_opt_disabled, 'icon'=> "..."]);
            // middle 
            if ($request_page_number >=$start_range && $request_page_number <=$totalpage - 6) {
                $middle_range=$request_page_number+3;
                $middle_start_count=$request_page_number - 3;
            }else {
                $middle_range=0;
                $middle_start_count=1; // to disable middle
            };

            for ($i=$middle_start_count; $i <=$middle_range; $i++) {
                if ((int) $request_page_number===$i) {
                    $selected=1;
                    $clickable=0;
                    $class_using=$stly_class."  ".$stly_class_opt_active;
                }else {
                    $selected=0;
                    $clickable=1;
                    $class_using=$stly_class;
                };
                array_push ($data_array, ['page'=> $i, 'selected'=> $selected, 'clickable'=> $clickable, 'stly_classes'=> $class_using, 'icon'=> strval($i)]);
            }
            // // ending 
            if ((int) $request_page_number <=$totalpage - 6) {
                if ($request_page_number > 6) array_push ($data_array, ['page'=> null, 'selected'=> 0, 'stly_classes'=> $stly_class." ".$stly_class_opt_disabled, 'clickable'=> 0, 'icon'=> "..."]);
                for ($i=$totalpage - 1; $i <=$totalpage; $i++) {
                    if ((int) $request_page_number===$i) {
                        $selected=1;
                        $clickable=0;
                        $class_using=$stly_class."  ".$stly_class_opt_active;
                    } else {
                        $selected=0;
                        $clickable=1;
                        $class_using=$stly_class;
                    };
                    array_push ($data_array, ['page'=> $i, 'selected'=> $selected, 'clickable'=> $clickable, 'stly_classes'=> $class_using, 'icon'=> strval($i)]);
                }
            }else {
                for ($i=$totalpage - 8; $i <=$totalpage; $i++) {
                    if ((int) $request_page_number===$i) {
                        $selected=1;
                        $clickable=0;
                        $class_using=$stly_class."  ".$stly_class_opt_active;
                    }else {
                        $selected=0;
                        $clickable=1;
                        $class_using=$stly_class;
                    };
                    array_push ($data_array, ['page'=> $i, 'selected'=> $selected, 'clickable'=> $clickable, 'stly_classes'=> $class_using, 'icon'=> strval($i)]);
                }
            }


        }

        if ($totalpage > 1) {
            if ($request_page_number==$totalpage) {
                $clickable=0;
                $request_page_number=null;
                $class_using=$stly_class." ".$stly_class_opt_disabled;
            }else {
                $clickable=1;
                $request_page_number++;
                $class_using=$stly_class;
            };
            array_push ($data_array, ['page'=> $request_page_number, 'selected'=> 0, 'clickable'=> $clickable, 'stly_classes'=> $class_using, 'icon'=> '>']); // option push at end
        }
        return $data_array;
    }

     /****** @Private Zone ******/   
   
     private function anError(int $err , $value ){
        array_push ( self::$ClassError ,   [$err,$value] ) ;
        
    }

    private function setCollection($collection){
        $this->collection = $collection ;
        return $this ; 
    }


    private function setDatabase($database){
        $this->database = $database ;
        return $this ; 
    }

    private function setDatabaseCollection($database , $collection){
        $this->database = $database ;
        $this->collection = $collection ;
        return $this ; 
    }
    
    private function whereConversion(String $Key ,String $Operation , $Value) {
        if ( $Operation  == "=" ){
            return [ "$Key"=>  $Value ];                    // SQL transform select * from table where 'key' = 'value'  ; 
        }elseif( $Operation  == "!=" ) {
            return [ "$Key" => ['$ne' => $Value ]  ];       // SQL transform select * from table where 'key' != 'value'
        }elseif($Operation  == "<="){
            return [ "$Key" => [ '$lte' =>  $Value ]  ];   // SQL transform select * from table where 'Key' <= 'value'
        }elseif($Operation  == ">="){
            return [ "$Key" => [ '$gte' =>  $Value ]  ];    // SQL transform select * from table where 'Key' >= 'value'
        }elseif($Operation  == "<"){
            return [ "$Key" => [ '$lt' =>  $Value ]  ];     // SQL transform select * from table where 'Key' < 'value'
        }elseif($Operation  == ">"){
            return [ "$Key" => [ '$gt' =>   $Value ]  ];    // SQL transform select * from table where 'Key' > 'value'
        }elseif( $Operation  == "like" ) {
            if (   $Value[0]  != "%" && substr(  "$Value" , -1 ) =="%"  ) { 
               return [  "$Key" => new Regex('^'. substr( "$Value" ,0,-1 ) .'.*$', 'i') ]  ;     // SQL transform select * from table where 'Key' like 'value%'   ; find begin with ?    
            }elseif (   $Value[0]  == "%"  &&  substr( "$Value" , -1 ) !=  "%"  ) {
                return [  "$Key" => new Regex('^.*'.substr( "$Value" ,1 ) .'$', 'i') ];          // SQL transform select * from table where 'Key' like '%value'   ; find end with ?
            }elseif (  $Value[0]  == "%"  &&  substr( "$Value" , -1 ) =="%"   ) {
                return [ "$Key" => new Regex('^.*'.substr( "$Value" ,1 ,-1)  .'.*$', 'i')];     // SQL transform select * from table where 'Key' like '%value%'  ; find where ever with ?
            }else{
                return [ "$Key" => new Regex('^.'."$Value".'.$', 'i')];   //  SQL transform select * from table where 'key' like 'value'
            }
        }else{
               throw new Exception(" Error operator   '$Operation'  not support  for this module ");
        }
    }

    private function findCreateIndexMany(String $IndexMany){ 
        $config = new Config ;
        $config->setDb( $this->getDbNonstatic() ) ;
        $conclude = new BuildConnect ; 
        $index =  $this->schema[$this->getCollectNonstatic()][$IndexMany] ;  
        $index['ns'] = $config->getDb().".".$this->getCollectNonstatic()  ;
        $result =  $conclude->getIndex($config , $this->getCollectNonstatic() , $index['name'] );
         if (  !$result ) { 
              $reactionInsert = $conclude->createIndex($config , $this->getCollectNonstatic() , $index  );
          }else{
              $reactionInsert = false ;
          }  
         return  $reactionInsert  ; 
    } 
      
    
    private function findCreateIndexOne(String $fieldIndex){  
        $config = new Config ;
        $config->setDb( $this->getDbNonstatic() ) ;
        $conclude = new BuildConnect ; 
         if(isset($this->schema[$this->getCollectNonstatic()][$fieldIndex]['Unique'])){
            $index_unique =  $this->schema[$this->getCollectNonstatic()][$fieldIndex]['Unique'] ;
         }else{
            $index_unique  = false ; 
         }
        
         if(isset($this->schema[$this->getCollectNonstatic()][$fieldIndex]['Sparse'])){
            $index_Sparse =  $this->schema[$this->getCollectNonstatic()][$fieldIndex]['Sparse'] ;
         }else{
            $index_Sparse  = false ; 
         }

        $index = [
                    "name" =>  "\$__INDEX_".strtoupper($fieldIndex)."_"  ,
                    "key"  =>  [$fieldIndex=>1] ,
                    "unique" => $index_unique  ,
                    "sparse" => $index_Sparse  ,
                    "ns" => $config->getDb().".".$this->getCollectNonstatic()  
                ];
        $result =  $conclude->getIndex($config , $this->getCollectNonstatic() , $index['name'] );
         if ( !$result ) { 
              $reactionInsert = $conclude->createIndex($config , $this->getCollectNonstatic() , $index  );
          }else{
              $reactionInsert = false ;
          }  
         return  $reactionInsert  ; 
    }

    private function findCreateIndexAutoInc(String $fieldIndex , String $collection ){  
        $config = new Config ;
        $config->setDb( $this->getDbNonstatic() ) ;
        $conclude = new BuildConnect ; 
        $index = [
                    "name" =>"\$__IDX_AUTOINC_".$this->getDbNonstatic()."_counters",
                    "key"  =>['inc_field'=>1,'collection'=>1],
                    "unique" => true ,
                    "ns" => $config->getDb().".".$this->getDbNonstatic()."_counters"
                ];
        $result =  $conclude->getIndex($config , $this->getCollectNonstatic() , $index['name'] );
         if ( !$result ) { 
              $reactionInsert = $conclude->createIndex($config ,  $this->getDbNonstatic()."_counters" , $index  );
          }else{
              $reactionInsert = false ;
          }  
         return  $reactionInsert  ; 
    }

    private  function findCreateAutoInc(String $fieldNameToInc , int $StartSeq  ) {
        $config = new Config ;
        $config->setDb( $this->getDbNonstatic() ) ;
        $conclude = new BuildConnect ; 
        $collection_counter  = $this->getDbNonstatic().'_counters' ; 
        $this->findCreateIndexAutoInc( $fieldNameToInc, $this->getCollectNonstatic() ) ; // Magic create index 
        $query = [  'inc_field' => $fieldNameToInc , 'collection' => $this->getCollectNonstatic() ]  ;
        
        $conclude->findDoc($config , $collection_counter ,$query ) ; 
         if ( null == $conclude->result ) {
           
            $reactionInsert = $conclude->insertDoc($config ,$collection_counter ,[
                                                     	'inc_field' => $fieldNameToInc ,
						                            	'collection'=> $this->getCollectNonstatic(),
                                                        'sequence_value' => 0.0 + $StartSeq ]) ; // conversion datatype to be double
         }  
        
        return $conclude->result ; 
    } 

    private function fillable(array $arrVals , $option = [] ) { 
        $collections=[];
        $fillables=[];
        $updateProtected=[];
        foreach ( array_keys( $this->schema ) as $each_coll  ) { 
            array_push($collections,$each_coll) ; 
        }

        if( !in_array( $this->collection , $collections)  ){
             return [0 , "Error ! collection:$this->collection aren't in member of schema check your Model class ".get_class($this) ] ;
        }else{ 
             foreach (  $this->schema[ $this->collection]  as $keys =>  $values ) { 
                if ( is_array($values) ){
                     
                    if(isset($this->schema[$this->collection][$keys]['AutoIncStartwith'])){ 
                        $startseq = $this->schema[$this->collection][$keys]['AutoIncStartwith'] ; 
                    }else{
                        $startseq = 0 ;
                    }
                     
                    foreach ( $values  as $key => $value  ) { 
                        if ( $key === "AutoInc"  &&  $value === true )   $this->findCreateAutoInc($keys, $startseq )  ; 
                        if ( $key === "Index"  &&  $value === true ) $this->findCreateIndexOne($keys) ;
                        if ( $key === "UpdateProtected"  &&  $value === true  ) { 
                           array_push( $updateProtected ,  $keys );
                        }
                            
                    } 
                    $findmultiIndex = substr( $keys  , 0, strlen( "\$__MULTIPLE_INDEX")  );
                    if ( "\$__MULTIPLE_INDEX" === $findmultiIndex ){ 
                             $this->findCreateIndexMany($keys) ;  
                    }else{
                        array_push ($fillables , $keys) ; 
                    }

                }else{
                    array_push ($fillables , $values) ; 
                }
             } 
        }
         
        foreach ( array_keys($arrVals) as $key  ) {  
  
            if (  !in_array( $key , $fillables ) ) { return  [  0 , "ERROR ! input fail -> field name:".$key. " aren't  member in schema check your Model ".get_class($this) ]; } 
            if( isset($option['update']) ){ 
                if (  in_array( $key,$updateProtected)  &&  $option['update'] == true  ){    return  [  0 , "ERROR ! update collection:". $this->collection."->feild:".$key. " has protected in ".get_class($this) ];  }
            }
        }
        return [1,"fillable OK good luck my friend ! "] ;
    }

    private function getAllwhere(){ 
        $allAnd = ['$and'=>[]] ;
        if (  isset (self::$orderTerm) &&  count(self::$orderTerm) == 1 ){  return ;
        }elseif( count(self::$orderTerm) > 1) {
          // Find all term are AND   // to Check all term is and(s)  where()->andwhere()->andwhere()->get()
          $andCount=0; 
          foreach(  self::$orderTerm as $key => $terms ){
               if(array_keys($terms)[0]==='$and'){$andCount++;}
               array_push($allAnd['$and'],$terms[array_keys($terms)[0]] ) ; 
          }
          if( $andCount == count(self::$orderTerm) -1 ){   // All term is ANDs
            self::$querys = $allAnd ; 
            return $allAnd;
          }
        }   
        $finalwhere=self::$orderTerm;
        $beforeOps = null ;
        $beforeTerm = [] ;
        $order = 0 ;
        $termCount = 0 ;
        $terms = [] ;
        $finalTerms = ['$or'=>[]] ;
        $andTerms = ['$and'=>[]] ; 
        // Collector terms 
        // @conversion SQL to  logic precendence order using Mongodb's format
        // term (and)(and)  + term(or)(and)(and)  + term (or)(and)  + term(or) + term(or) 
      
        foreach($finalwhere as $operator => $term){  
            if(   $beforeOps == null  && array_keys($term)[0] === 'mostleft' ){ 
                $termCount++ ; 
                if(!isset($terms[$termCount])) { $terms[$termCount] = [] ;}
                array_push($terms[$termCount] , $term[array_keys($term)[0]] ); 
            }elseif( $beforeOps == 'mostleft'  && array_keys($term)[0] === '$or' ){
                $termCount++ ; 
                if(!isset($terms[$termCount])) { $terms[$termCount] = [] ;}
                 array_push($terms[$termCount] , $term[array_keys($term)[0]] ); 
            }elseif( $beforeOps == 'mostleft'  && array_keys($term)[0] === '$and' ){
                if(!isset($terms[$termCount])) { $terms[$termCount] = [] ;}
                array_push($terms[$termCount] , $term[array_keys($term)[0]] ); 
            }elseif( $beforeOps == '$or'  && array_keys($term)[0] === '$or' ){
                $termCount++ ;
                if(!isset($terms[$termCount])) { $terms[$termCount] = [] ;}
                array_push($terms[$termCount] , $term[array_keys($term)[0]] ); 
            }
             elseif( $beforeOps == '$or'  && array_keys($term)[0] === '$and' ){
                if(!isset($terms[$termCount])) { $terms[$termCount] = [] ;}
                array_push($terms[$termCount] , $term[array_keys($term)[0]] ); 
            }elseif( $beforeOps == '$and'  && array_keys($term)[0] === '$or' ){
                $termCount++ ; 
                if(!isset($terms[$termCount])) { $terms[$termCount] = [] ;}
                array_push($terms[$termCount] , $term[array_keys($term)[0]] ); 
            }elseif( $beforeOps == '$and'  && array_keys($term)[0] === '$and' ){
              if(!isset($terms[$termCount])) { $terms[$termCount] = [] ;}
                array_push($terms[$termCount] , $term[array_keys($term)[0]] ); 
            }
             array_keys($term)[0] ; 
             $beforeOps = array_keys($term)[0] ;
             $beforeTerm = $term[array_keys($term)[0]] ;
        } 
        // conversion Terms to OR term
           foreach( $terms as $term){
               if ( count($term) == 1 ){ 
                    array_push ($finalTerms['$or'],$term[0]);  
                }elseif(count($term)  > 1){
                    $andTerms = ['$and'=>[]] ;
                    foreach( $term as $andTerm  ){ 
                       array_push($andTerms['$and'] , $andTerm)   ;
                    } 
                    array_push ($finalTerms['$or'],$andTerms);
                }
           } 
           if ($finalTerms == ['$or'=>[]] ){ self::$querys =[] ;}else{
               self::$querys = $finalTerms ;
           } ;
       return  self::$querys;
    }
    
    public function findNormal() {
        $config=new Config ;
        $config->setDb($this->getDbNonstatic()) ;
        $conclude=new BuildConnect; 
        $conclude->findDoc($config,$this->collection,self::$querys,self::$options); 
        $renewdisplay = [] ;
        $groupresult = json_decode( json_encode( $conclude->result ) , true ) ;
            if ( !null == self::$mappingAs ){
                            foreach ($groupresult  as $keys => $datas){ 
                            $docs = [] ;
                            foreach($datas as $key => $data){
                                $docs = array_merge($docs, [self::$mappingAs[$key]  => $data ]  );
                            }
                            $renewdisplay = array_merge($renewdisplay, [$docs]  );
                            }
                            return $renewdisplay ;
            }else{
            
                return $groupresult ;
            }

   } 


    public function findGroup () { 
            $config=new Config ;
            $config->setDb($this->getDbNonstatic()) ;
            $conclude=new BuildConnect; 
            $findMatch = 0 ;
            foreach( self::$pipeline as $key => $dat  ){
                if ( $key === '$match'){ $findMatch++;}
            }
            if ( $findMatch == 0  ){
            $swap = self::$pipeline ;
            self::$pipeline = [] ;
            self::$pipeline = array_merge(self::$pipeline,[ ['$match' => self::$querys] ]);
            self::$pipeline = array_merge(self::$pipeline,$swap);
            }
            self::$pipeline = array_merge( self::$pipeline , [self::$groupby] );

            foreach (self::$options as $mainkey => $mainOption){
                    if('projection'===$mainkey){   
                        $project['$project'] = [];
                        foreach (self::$options['projection'] as $key => $option ){ 
                            substr($option,0,1) === "$" ?     
                                    $option = substr($option,1) :
                                    $option = substr($option,0) ;
                            if ($key !== '_id') $project['$project'] = array_merge( $project['$project'] , [ $key => "\$_id.$option" ]); 
                        }
                        $project['$project']= array_merge( $project['$project'],[ '_id' =>  0]); 
                        self::$pipeline = array_merge(self::$pipeline,[$project]); 
                    }else{
                        self::$pipeline = array_merge(self::$pipeline,[["\$".$mainkey => $mainOption ]]); 
                    }
            }
            $options = [
                'allowDiskUse' => TRUE
            ];
            $conclude->aggregate($config,$this->collection,self::$pipeline,$options); 
            $renewdisplay = [] ;
            $groupresult = json_decode( json_encode( $conclude->result ) , true ) ;

            foreach ($groupresult  as $keys => $datas){ 
                $docs = [] ;
                foreach($datas as $key => $data){
                    $docs = array_merge($docs, [self::$mappingAs[$key]  => $data ]  );
                }
                $renewdisplay = array_merge($renewdisplay, [$docs]  );
            }
        return $renewdisplay ;
    } 

    public function findJoin() { 
        if(env('DEV_DEBUG')) print ("   --> in function Join : <br>\n") ; 
        $config=new Config ;
        $config->setDb($this->getDbNonstatic()) ;
        $conclude=new BuildConnect; 
        self::$pipeline = array_merge(self::$pipeline,self::$joincollections) ; 
        if(!null==self::$querys)self::$pipeline=array_merge(self::$pipeline,[['$match'=>self::$querys]]) ; 
     
               if (!null == self::$groupby )  self::$pipeline = array_merge(self::$pipeline,[self::$groupby]);   
                 foreach (self::$options as $mainkey => $mainOption){
                     if('projection'===$mainkey){   
                         $project['$project'] = [];
                         foreach (self::$options['projection'] as $key => $option ){ 
                             substr($option,0,1) === "$" ?   
                             $option = substr($option,1) :
                             $option = substr($option,0) ;
                             if ($key !== '_id'  ) { 
                                if ( !null == self::$groupby ) {
                                   $project['$project'] = array_merge( $project['$project'] ,
                                                                    [ $key => '$_id.'. str_replace(".",dotter(),$option ) ]
                                                                    ); 
                                }else{
                                    $project['$project'] = array_merge( $project['$project'] ,
                                    [ $key => '$'.$option  ]
                                    ); 
                                }
                            }
                        }
                        $project['$project']= array_merge( $project['$project'],[ '_id' =>  0]); 
                        self::$pipeline = array_merge(self::$pipeline,[$project]); 
                    }else{
                        self::$pipeline = array_merge(self::$pipeline,[["\$".$mainkey => $mainOption ]]); 
                    }
                }
                $options = [ 'allowDiskUse' => TRUE ]; 
                // @dev debug  dd('pine line' ,self::$pipeline ,  'optons' ,  $options );
        $conclude->aggregate($config,$this->collection,self::$pipeline,$options);  
        //
        // @ re-building new output
        // 
         $displayjoin = [] ;
         $joinresult = json_decode( json_encode( $conclude->result ) , true ) ;
         // Conversion to SQL data list style
         foreach ($joinresult  as $keys => $datas){ 
             $eachdoc = [] ;
             foreach ($datas as $key => $data) {
                 foreach($data as $in_key => $in_data ){ 
                  $eachdoc = array_merge($eachdoc , [ self::$mappingAs[$key.".".$in_key] => $in_data ]);
                 }
            }
                $displayjoin = array_merge( $displayjoin ,[$eachdoc] );
         }
        return  $displayjoin ;
    } 
}