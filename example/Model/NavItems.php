<?php


namespace Model;



class NavItems  implements \IteratorAggregate  {

    private $navItems = array();

    public function __construct() {
        $this->navItems = [
            new NavItem('/syntaxExample', 'Basic syntax'),
            new NavItem('/functionExample', 'Functions'),
            new NavItem('/blockExample', 'Blocks'),
            new NavItem('/bindingDataExample', 'Binding data'),
            new NavItem('/formExample', 'Form'),
        ];
    }


    /**
     * @return \Model\NavItem[]
     */
    public function getIterator() {
       return new \ArrayIterator($this->navItems);
    }
}

 