<?php


namespace JigDemo\Model;



class NavItems implements \IteratorAggregate  {

    private $navItems = array();

    public function __construct() {
        $this->navItems = [
            new NavItem('/syntaxExample', 'Basic syntax'),
            new NavItem('/functionExample', 'Functions'),
            new NavItem('/blockExample', 'Blocks'),
            new NavItem('/bindingDataExample', 'Binding data'),
            new NavItem('/reuseExample', 'Reusing templates'),
            new NavItem('/formExample', 'Form'),
        ];
    }

    /**
     * @return \JigDemo\Model\NavItem[]
     */
    public function getIterator() {
       return new \ArrayIterator($this->navItems);
    }
}

 