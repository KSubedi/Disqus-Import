<?php

// Reads XML
class ThreadReader{
    private $xmlThread;
    private $xmlArray;

    public function __construct($xmlString){
        // Convert text to xml object
        $xmlObject = simplexml_load_string($xmlString);
        
        // Convert to xml array and assign it to the $xmlArray property
        $this->xmlArray = $this->xmlObjToArr($xmlObject);
    }

    // Returns raw array
    public function getArray(){
        return $this->xmlArray;
    }

    // Reads xml node & attributes based on a string
    // Format is: threads._c--title
    // Where _c is children.
    public function read($node, $topNode = NULL){
        // Split by -- to parse attributes attached at the end
        $twoHalves = explode("--", $node);

        $firstHalf = $twoHalves[0];

        // Start the marker on the array at the base of the array
        $marker = $this->xmlArray;

        // If top node is provided, assign the marker to topnode
        if($topNode != NULL) $marker = $topNode;

        // Make sure there is something on the first half
        if($firstHalf != ''){
            // Explode first half of the string by .
            $nodes = explode(".", $firstHalf);
            
            // Move marker based on the strings
            foreach($nodes as $node){
                if($node == "_c"){
                    $node = "children";
                }
    
                $marker = $marker[$node];
            }
        }

        // If there is attributes to be read, set it to that on the array
        if(count($twoHalves) > 1){
            $attribute = $twoHalves[1]; 

            $marker = $marker['attributes'][$attribute];
        }

        return $marker;
    }

    private function xmlObjToArr($obj) { 
        $namespace = $obj->getDocNamespaces(true); 
        $namespace[NULL] = NULL; 
        
        $children = array(); 
        $attributes = array(); 
        $name = strtolower((string)$obj->getName()); 
        
        $text = trim((string)$obj); 
        if( strlen($text) <= 0 ) { 
            $text = NULL; 
        } 
        
        // get info for all namespaces 
        if(is_object($obj)) { 
            foreach( $namespace as $ns=>$nsUrl ) { 
                // atributes 
                $objAttributes = $obj->attributes($ns, true); 
                foreach( $objAttributes as $attributeName => $attributeValue ) { 
                    $attribName = strtolower(trim((string)$attributeName)); 
                    $attribVal = trim((string)$attributeValue); 
                    if (!empty($ns)) { 
                        $attribName = $ns . ':' . $attribName; 
                    } 
                    $attributes[$attribName] = $attribVal; 
                } 
                
                // children 
                $objChildren = $obj->children($ns, true); 
                foreach( $objChildren as $childName=>$child ) { 
                    $childName = strtolower((string)$childName); 
                    if( !empty($ns) ) { 
                        $childName = $ns.':'.$childName; 
                    } 
                    $children[$childName][] = $this->xmlObjToArr($child); 
                } 
            } 
        } 
        
        return array( 
            'name'=>$name, 
            'text'=>$text, 
            'attributes'=>$attributes, 
            'children'=>$children 
        ); 
    } 

}