<?php


namespace Intahwebz\Jig\Converter;

use PHPParser_NodeVisitorAbstract;

class PHPNodeVisitor implements  \PHPParser_NodeVisitor {


    function leaveNode(\PHPParser_Node $node) {
        
        echo "LeaveNode: \n";
        var_dump($node);
    }

//The enterNode and leaveNode methods are called on every node, the former when it is entered, i.e. before its subnodes are traversed, the latter when it is left.
//
//All four methods can either return the changed node or not return at all (i.e. null) in which case the current node is not changed. The leaveNode method can furthermore return two special values: If false is returned the current node will be removed from the parent array. If an array is returned the current node will be merged into the parent array at the offset of the current node. I.e. if in array(A, B, C) the node B should be replaced with array(X, Y, Z) the result will be array(A, X, Y, Z, C).
    
    
    public function beforeTraverse(array $nodes)    { }
    public function enterNode(\PHPParser_Node $node) { }

    public function afterTraverse(array $nodes)     { }
    
}



?>